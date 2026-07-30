<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportarCsv extends Command
{
    /**
     * Importa CSVs (exportados da produção) para as tabelas locais, casando as
     * colunas pelo cabeçalho. Aceita um arquivo (tabela = nome do arquivo) ou
     * uma pasta com vários CSVs.
     *
     * Exemplos:
     *   php artisan app:importar-csv storage/dados/sub_divisoes.csv
     *   php artisan app:importar-csv storage/dados --truncar
     */
    protected $signature = 'app:importar-csv
        {caminho : Arquivo .csv ou pasta com vários CSVs (nome do arquivo = nome da tabela)}
        {--truncar : Esvazia a tabela antes de importar}
        {--delimitador= : Força o delimitador (, ou ;). Padrão: detecta pelo cabeçalho}';

    protected $description = 'Importa CSVs de produção para as tabelas locais (casando colunas pelo cabeçalho)';

    public function handle(): int
    {
        $caminho = $this->argument('caminho');

        if (! file_exists($caminho)) {
            $this->error("Caminho não encontrado: {$caminho}");

            return self::FAILURE;
        }

        $arquivos = is_dir($caminho)
            ? glob(rtrim($caminho, '/\\').DIRECTORY_SEPARATOR.'*.csv')
            : [$caminho];

        if ($arquivos === []) {
            $this->warn('Nenhum arquivo .csv encontrado.');

            return self::SUCCESS;
        }

        // Em SQLite, desliga as FKs durante a carga para permitir qualquer ordem.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($arquivos as $arquivo) {
            $this->importarArquivo($arquivo);
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        $this->info('Concluído.');

        return self::SUCCESS;
    }

    private function importarArquivo(string $arquivo): void
    {
        $tabela = pathinfo($arquivo, PATHINFO_FILENAME);

        if (! Schema::hasTable($tabela)) {
            $this->warn("• {$tabela}: tabela inexistente, pulando.");

            return;
        }

        $handle = fopen($arquivo, 'r');
        if ($handle === false) {
            $this->warn("• {$tabela}: não foi possível abrir o arquivo.");

            return;
        }

        // Detecta o delimitador pela primeira linha (vírgula ou ponto-e-vírgula).
        $primeira = fgets($handle);
        $primeira = $primeira !== false ? ltrim($primeira, "\xEF\xBB\xBF") : '';
        $delimitador = $this->option('delimitador')
            ?: (substr_count($primeira, ';') > substr_count($primeira, ',') ? ';' : ',');
        rewind($handle);

        $cabecalho = fgetcsv($handle, 0, $delimitador);
        if ($cabecalho === false) {
            fclose($handle);
            $this->warn("• {$tabela}: arquivo vazio.");

            return;
        }
        $cabecalho[0] = ltrim($cabecalho[0], "\xEF\xBB\xBF");

        $colunasTabela = Schema::getColumnListing($tabela);
        $indices = [];
        foreach ($cabecalho as $i => $coluna) {
            $coluna = trim((string) $coluna);
            if (in_array($coluna, $colunasTabela, true)) {
                $indices[$coluna] = $i;
            }
        }

        if ($indices === []) {
            fclose($handle);
            $this->warn("• {$tabela}: nenhuma coluna do CSV bate com a tabela.");

            return;
        }

        if ($this->option('truncar')) {
            DB::table($tabela)->delete();
        }

        $lote = [];
        $total = 0;

        while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
            $registro = [];
            foreach ($indices as $coluna => $i) {
                $valor = $linha[$i] ?? null;
                // "NULL" e "\N" (MySQL) viram null de fato.
                $registro[$coluna] = ($valor === null || $valor === 'NULL' || $valor === '\\N') ? null : $valor;
            }

            $lote[] = $registro;
            $total++;

            if (count($lote) >= 500) {
                DB::table($tabela)->insert($lote);
                $lote = [];
            }
        }

        if ($lote !== []) {
            DB::table($tabela)->insert($lote);
        }

        fclose($handle);

        $this->info("• {$tabela}: {$total} registro(s) importado(s) (".implode(', ', array_keys($indices)).').');
    }
}
