<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportarCsvBanco extends Command
{
    /**
     * Importa um CSV com o banco inteiro (export "todas as tabelas" do
     * phpMyAdmin) no banco local. O arquivo não traz nomes de tabela — cada
     * bloco começa com uma linha de cabeçalho (nomes de coluna); o comando
     * detecta a tabela casando o cabeçalho com o schema local e importa as
     * linhas seguintes até o próximo cabeçalho. Usa fgetcsv (aspas nativas).
     *
     * Exemplo:  php artisan app:importar-csv-banco vixpla20_transporte.csv --truncar
     */
    protected $signature = 'app:importar-csv-banco
        {arquivo : Caminho do CSV com o banco inteiro}
        {--truncar : Esvazia cada tabela detectada antes de importar}
        {--apenas= : Lista de tabelas a importar, separadas por vírgula (padrão: todas)}';

    protected $description = 'Importa um CSV do banco inteiro (phpMyAdmin) no banco local, detectando tabelas pelo cabeçalho';

    /** Tabelas de infraestrutura que não devem vir do dump. */
    private const IGNORAR = [
        'migrations', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    public function handle(): int
    {
        $arquivo = $this->argument('arquivo');

        if (! is_file($arquivo)) {
            $this->error("Arquivo não encontrado: {$arquivo}");

            return self::FAILURE;
        }

        $apenas = $this->option('apenas')
            ? array_map('trim', explode(',', $this->option('apenas')))
            : null;

        // Assinatura (conjunto de colunas) → tabela, para detectar cabeçalhos.
        $colunasPorTabela = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table'") as $row) {
            $t = $row->name;
            if (! str_starts_with($t, 'sqlite_')) {
                $colunasPorTabela[$t] = Schema::getColumnListing($t);
            }
        }

        $handle = fopen($arquivo, 'r');
        if ($handle === false) {
            $this->error('Não foi possível abrir o arquivo.');

            return self::FAILURE;
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        $tabela = null;      // tabela atual
        $mapa = [];          // índice da coluna no CSV => nome da coluna local
        $ordemCsv = [];      // nomes das colunas do cabeçalho atual
        $lote = [];
        $contagem = [];
        $truncadas = [];

        $flush = function () use (&$lote, &$tabela) {
            if ($tabela !== null && $lote !== []) {
                DB::table($tabela)->insert($lote);
            }
            $lote = [];
        };

        while (($linha = fgetcsv($handle)) !== false) {
            if ($linha === [null] || $linha === []) {
                continue;
            }

            $linha[0] = ltrim((string) $linha[0], "\xEF\xBB\xBF");

            // Toda linha que parece cabeçalho (só identificadores) encerra o
            // bloco anterior — cabeçalhos separam as tabelas no arquivo.
            if ($this->pareceCabecalho($linha)) {
                $flush();

                $detectada = $this->resolverTabela($linha, $colunasPorTabela);
                $ordemCsv = $linha;
                $tabela = ($detectada === null
                    || in_array($detectada, self::IGNORAR, true)
                    || ($apenas !== null && ! in_array($detectada, $apenas, true)))
                    ? null   // desconhecida/ambígua/fora do escopo → ignora o bloco
                    : $detectada;

                if ($tabela !== null) {
                    $locais = $colunasPorTabela[$tabela];
                    $mapa = [];
                    foreach ($ordemCsv as $i => $col) {
                        if (in_array($col, $locais, true)) {
                            $mapa[$i] = $col;
                        }
                    }
                    if ($this->option('truncar') && ! isset($truncadas[$tabela])) {
                        DB::table($tabela)->delete();
                        $truncadas[$tabela] = true;
                    }
                    $contagem[$tabela] ??= 0;
                }

                continue;
            }

            if ($tabela === null) {
                continue; // dados de uma tabela ignorada/desconhecida
            }

            // Linha de dados: só vale se tiver o mesmo nº de colunas do cabeçalho.
            if (count($linha) !== count($ordemCsv)) {
                continue;
            }

            $registro = [];
            foreach ($mapa as $i => $col) {
                $valor = $linha[$i];
                $registro[$col] = ($valor === 'NULL' || $valor === '\\N') ? null : $valor;
            }

            $lote[] = $registro;
            $contagem[$tabela]++;

            if (count($lote) >= 300) {
                $flush();
            }
        }

        $flush();
        fclose($handle);

        DB::statement('PRAGMA foreign_keys = ON');

        foreach ($contagem as $t => $n) {
            $this->info("• {$t}: {$n} registro(s).");
        }
        $this->info('Concluído.');

        return self::SUCCESS;
    }

    /**
     * A linha parece um cabeçalho? Todas as células são identificadores
     * (minúsculas, sem espaço) — como os nomes de coluna.
     *
     * @param  array<int, string|null>  $linha
     */
    private function pareceCabecalho(array $linha): bool
    {
        foreach ($linha as $c) {
            if ((string) $c === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', (string) $c)) {
                return false;
            }
        }

        return $linha !== [];
    }

    /**
     * Resolve a tabela do cabeçalho: match exato do conjunto de colunas; se não
     * houver, subconjunto único (produção pode não ter colunas novas do local);
     * ambíguo ou desconhecido → null (bloco ignorado, sem misroute).
     *
     * @param  array<int, string|null>  $linha
     * @param  array<string, array<int, string>>  $colunasPorTabela
     */
    private function resolverTabela(array $linha, array $colunasPorTabela): ?string
    {
        $set = array_map(fn ($c) => (string) $c, $linha);
        sort($set);

        $exatas = [];
        $subconjuntos = [];

        foreach ($colunasPorTabela as $tabela => $colunas) {
            $cols = $colunas;
            sort($cols);

            if ($set === $cols) {
                $exatas[] = $tabela;
            } elseif (array_diff($set, $colunas) === []) {
                $subconjuntos[] = $tabela;
            }
        }

        if (count($exatas) === 1) {
            return $exatas[0];
        }

        if ($exatas === [] && count($subconjuntos) === 1) {
            return $subconjuntos[0];
        }

        return null;
    }
}
