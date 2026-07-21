<?php

namespace App\Services;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\Equipamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Importa itens de demanda a partir do export do SAP em planilha.
 *
 * Cada linha do arquivo é um item. A demanda (cabeçalho) é localizada pelo
 * número da Nota: se não existir é criada, se existir o item é associado a ela.
 */
class ImportadorDemandas
{
    /**
     * Cabeçalhos do export do SAP, na ordem em que aparecem. Os nomes vêm
     * truncados da origem, por isso o mapeamento é posicional por rótulo.
     */
    private const COLUNAS = [
        'nota' => 'Nota',
        'numero_rt' => 'RT',
        'numero_item' => 'Item da RT',
        'subitem' => 'Subitem da',
        'local_origem' => 'Origem',
        'local_destino' => 'Destino',
        'descricao_local_retirada' => 'Local retirada',
        'descricao_item' => 'Descri',
        'status_item' => 'Status do',
        'prazo_data' => 'Data + tar',
        'prazo_hora' => 'Hora + tar',
        'equipamento' => 'equipamento',
    ];

    public function __construct(private DemandaCalculadora $calculadora) {}

    /**
     * @return array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, linhas_ignoradas: int, erros: array<int, string>}
     */
    public function importar(string $caminho, ?int $usuarioId = null): array
    {
        $resultado = [
            'demandas_criadas' => 0,
            'itens_criados' => 0,
            'itens_atualizados' => 0,
            'linhas_ignoradas' => 0,
            'erros' => [],
        ];

        $linhas = $this->lerPlanilha($caminho);

        if ($linhas === []) {
            $resultado['erros'][] = 'Nenhuma linha de dados encontrada na planilha.';

            return $resultado;
        }

        $prefixoParaId = $this->mapaPrefixoEquipamento();
        $demandasTocadas = [];

        DB::transaction(function () use ($linhas, $prefixoParaId, $usuarioId, &$resultado, &$demandasTocadas) {
            foreach ($linhas as $numeroLinha => $linha) {
                $nota = $this->limpar($linha['nota'] ?? null);
                $numeroRt = $this->limpar($linha['numero_rt'] ?? null);

                if ($nota === null || ! ctype_digit($nota)) {
                    $resultado['linhas_ignoradas']++;

                    continue;
                }

                if ($numeroRt === null) {
                    $resultado['erros'][] = "Linha {$numeroLinha}: Nota {$nota} sem número de RT.";
                    $resultado['linhas_ignoradas']++;

                    continue;
                }

                $demanda = Demanda::firstOrNew(['numero_demanda' => (int) $nota]);

                if (! $demanda->exists) {
                    $demanda->tipo_cadastro = TipoCadastro::Integracao;
                    $demanda->status_demanda = StatusDemanda::Pendente;
                    $demanda->criado_por = $usuarioId;
                    $demanda->save();
                    $resultado['demandas_criadas']++;
                }

                // Veículo vem como "VIX 1993 - AXOR ..."; o prefixo é o 2º token.
                if ($demanda->equipamento_id === null) {
                    $prefixo = $this->extrairPrefixo($linha['equipamento'] ?? null);
                    if ($prefixo !== null && isset($prefixoParaId[$prefixo])) {
                        $demanda->equipamento_id = $prefixoParaId[$prefixo];
                        $demanda->save();
                    }
                }

                $chave = [
                    'demanda_id' => $demanda->id,
                    'numero_rt' => $numeroRt,
                    'numero_item' => $this->limpar($linha['numero_item'] ?? null) ?? '1',
                    'subitem' => $this->limpar($linha['subitem'] ?? null),
                ];

                $existente = DemandaItem::where($chave)->exists();

                DemandaItem::updateOrCreate($chave, [
                    'local_origem' => $this->limpar($linha['local_origem'] ?? null),
                    'local_destino' => $this->limpar($linha['local_destino'] ?? null),
                    'descricao_local_retirada' => $this->limpar($linha['descricao_local_retirada'] ?? null),
                    'descricao_item' => $this->limpar($linha['descricao_item'] ?? null),
                    'status_item' => $this->limpar($linha['status_item'] ?? null),
                    'prazo_item' => $this->montarPrazo($linha['prazo_data'] ?? null, $linha['prazo_hora'] ?? null),
                ]);

                $existente ? $resultado['itens_atualizados']++ : $resultado['itens_criados']++;
                $demandasTocadas[$demanda->id] = true;
            }

            // Recalcula os derivados só depois que todos os itens entraram.
            Demanda::with('itens')->whereIn('id', array_keys($demandasTocadas))->get()
                ->each(fn (Demanda $d) => $this->calculadora->recalcular($d));
        });

        return $resultado;
    }

    /**
     * Lê a planilha e devolve as linhas já indexadas pelas chaves de COLUNAS.
     *
     * @return array<int, array<string, string|null>>
     */
    private function lerPlanilha(string $caminho): array
    {
        $reader = new XlsxReader;
        $reader->open($caminho);

        $mapa = [];
        $linhas = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $indice => $row) {
                $celulas = $row->toArray();

                if ($indice === 1) {
                    $mapa = $this->mapearCabecalho($celulas);

                    continue;
                }

                $linha = [];
                foreach ($mapa as $campo => $posicao) {
                    $valor = $celulas[$posicao] ?? null;
                    $linha[$campo] = $valor instanceof \DateTimeInterface
                        ? $valor->format('d.m.Y H:i:s')
                        : $valor;
                }

                $linhas[$indice] = $linha;
            }

            break; // apenas a primeira aba
        }

        $reader->close();

        return $linhas;
    }

    /**
     * Casa os rótulos truncados do SAP com as chaves internas.
     *
     * @param  array<int, mixed>  $cabecalho
     * @return array<string, int>
     */
    private function mapearCabecalho(array $cabecalho): array
    {
        $normalizado = array_map(
            fn ($c) => mb_strtolower(trim((string) $c)),
            $cabecalho
        );

        $mapa = [];

        foreach (self::COLUNAS as $campo => $rotulo) {
            $alvo = mb_strtolower($rotulo);

            foreach ($normalizado as $posicao => $valor) {
                if ($valor === '' || isset($mapa[$campo])) {
                    continue;
                }

                // "Descri" casa com "Descrição" mas não com "Descrição equipamento";
                // "equipamento" casa apenas com a coluna de descrição do veículo.
                $casou = $campo === 'descricao_item'
                    ? str_starts_with($valor, 'descri') && ! str_contains($valor, 'equipa') && ! str_contains($valor, 'destino') && ! str_contains($valor, 'carga')
                    : str_contains($valor, $alvo);

                if ($casou) {
                    $mapa[$campo] = $posicao;
                }
            }
        }

        return $mapa;
    }

    /**
     * @return array<string, int>
     */
    private function mapaPrefixoEquipamento(): array
    {
        return Equipamento::query()
            ->whereNotNull('prefixo')
            ->pluck('id', 'prefixo')
            ->mapWithKeys(fn ($id, $prefixo) => [strtoupper(trim($prefixo)) => $id])
            ->all();
    }

    /**
     * Extrai "1993" de "VIX 1993 - AXOR 1933 S 2P T44".
     */
    private function extrairPrefixo(?string $descricao): ?string
    {
        if (! $descricao) {
            return null;
        }

        if (preg_match('/^VIX\s+([A-Z0-9]+)/i', trim($descricao), $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }

    /**
     * Combina data (dd.mm.aaaa) e hora do SAP num datetime.
     * Tolera hora com ou sem segundos e data já com hora embutida.
     */
    private function montarPrazo(?string $data, ?string $hora): ?Carbon
    {
        $data = $this->limpar($data);

        if ($data === null) {
            return null;
        }

        // O SAP pode exportar a data já com hora embutida.
        $data = explode(' ', $data)[0];

        $hora = $this->limpar($hora) ?? '00:00:00';
        $hora = explode(' ', $hora)[0];

        // Normaliza para H:i:s ("10:00" → "10:00:00", "10" → "10:00:00").
        $partes = array_pad(explode(':', $hora), 3, '00');
        $hora = implode(':', array_map(
            fn ($p) => str_pad(substr(trim($p), 0, 2), 2, '0', STR_PAD_LEFT),
            array_slice($partes, 0, 3)
        ));

        foreach (['d.m.Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d H:i:s'] as $formato) {
            try {
                $parsed = Carbon::createFromFormat($formato, "{$data} {$hora}");
            } catch (\Throwable) {
                continue;
            }

            $erros = Carbon::getLastErrors();

            if ($parsed !== false && (! is_array($erros) || $erros['error_count'] === 0)) {
                return $parsed;
            }
        }

        return null;
    }

    private function limpar(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
