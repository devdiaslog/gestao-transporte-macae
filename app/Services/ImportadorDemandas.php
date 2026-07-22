<?php

namespace App\Services;

use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoCadastro;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\Equipamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Importa itens de demanda a partir do export do SAP em planilha.
 *
 * Cada linha do arquivo é um item. A demanda (cabeçalho) é localizada pelo
 * número da Nota: se não existir é criada, se existir o item é associado a ela.
 */
class ImportadorDemandas
{
    /**
     * Rótulos aceitos por campo. O primeiro é o nome genérico (usado no modelo);
     * os demais são aliases mantidos para compatibilidade com o export do SAP.
     * O cabeçalho é reconhecido por igualdade exata (ignorando caixa e acentos).
     *
     * @var array<string, array<int, string>>
     */
    private const COLUNAS = [
        'nota' => ['Numero Demanda Viagem', 'Nota'],
        'tipo_demanda' => ['Tipo Demanda'],
        'numero_rt' => ['Numero Demanda Entrega', 'Nº da RT'],
        'numero_item' => ['Item Demanda Entrega', 'Item da RT'],
        'subitem' => ['Subitem Demanda Entrega', 'Subitem da'],
        'local_origem' => ['Descrição Origem', 'Origem'],
        'local_destino' => ['Descrição Destino', 'Destino'],
        'descricao_local_retirada' => ['Local de Retirada', 'Local retirada'],
        'descricao_item' => ['Descrição Demanda Entrega', 'Descrição'],
        'status_item' => ['Status Demanda Entrega', 'Status do'],
        'prazo_data' => ['Data Prazo', 'Data + tar'],
        'prazo_hora' => ['Hora Prazo', 'Hora + tar'],
        'entrega_data' => ['Data Entrega', 'Dt entregu'],
        'entrega_hora' => ['Hora Entrega', 'Hr entregu'],
        'equipamento' => ['Descrição Veiculo', 'Descrição equipamento'],
    ];

    /**
     * Cabeçalho do modelo de importação — nomes compatíveis com o mapeamento
     * de COLUNAS, na ordem em que a RT precede o item da RT.
     *
     * @var array<int, string>
     */
    private const CABECALHO_MODELO = [
        'Numero Demanda Viagem',
        'Tipo Demanda',
        'Numero Demanda Entrega',
        'Item Demanda Entrega',
        'Subitem Demanda Entrega',
        'Descrição Origem',
        'Local de Retirada',
        'Descrição Destino',
        'Descrição Demanda Entrega',
        'Descrição Veiculo',
        'Status Demanda Entrega',
        'Data Prazo',
        'Hora Prazo',
        'Data Entrega',
        'Hora Entrega',
    ];

    /**
     * Linhas de exemplo mostrando o formato esperado de cada coluna.
     *
     * @var array<int, array<int, string>>
     */
    private const EXEMPLOS_MODELO = [
        ['509538496', 'Backload', '326741968', '1', '5', 'PACU', 'PACU-CAIS 2', 'ARM-MACAE', 'Descrição da carga', 'VIX 1993 - AXOR 1933 S 2P T44', '04', '24.07.2026', '10:00:00', '', ''],
        ['619012345', '', '326800000', '1', '1', 'ARM-MACAE', 'AL-50', 'BMAC', 'Outra carga', 'VIX 1994 - AXOR 1933 S 2P T44', '07', '25.07.2026', '14:30:00', '25.07.2026', '13:45:00'],
    ];

    public function __construct(private DemandaCalculadora $calculadora) {}

    /**
     * Gera o modelo .xlsx de importação num arquivo temporário e devolve o caminho.
     */
    public function gerarModelo(): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'modelo_demandas_').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues(self::CABECALHO_MODELO));

        foreach (self::EXEMPLOS_MODELO as $linha) {
            $writer->addRow(Row::fromValues($linha));
        }

        $writer->close();

        return $caminho;
    }

    /**
     * @param  int|null  $somenteNota  Quando informado, processa apenas as linhas
     *                                 dessa Nota (importação escopada a 1 demanda).
     * @return array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, itens_remanejados: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}
     */
    public function importar(string $caminho, ?int $usuarioId = null, ?int $somenteNota = null): array
    {
        $resultado = [
            'demandas_criadas' => 0,
            'itens_criados' => 0,
            'itens_atualizados' => 0,
            'itens_remanejados' => 0,
            'linhas_ignoradas' => 0,
            'avisos' => [],
            'erros' => [],
        ];

        $linhas = $this->lerPlanilha($caminho);

        if ($linhas === []) {
            $resultado['erros'][] = 'Nenhuma linha de dados encontrada na planilha.';

            return $resultado;
        }

        $prefixoParaId = $this->mapaPrefixoEquipamento();
        $demandasTocadas = [];

        DB::transaction(function () use ($linhas, $prefixoParaId, $usuarioId, $somenteNota, &$resultado, &$demandasTocadas) {
            foreach ($linhas as $numeroLinha => $linha) {
                $nota = $this->limpar($linha['nota'] ?? null);
                $numeroRt = $this->limpar($linha['numero_rt'] ?? null);

                if ($nota === null || ! ctype_digit($nota)) {
                    $resultado['linhas_ignoradas']++;

                    continue;
                }

                // Importação escopada: ignora linhas de outras demandas.
                if ($somenteNota !== null && (int) $nota !== $somenteNota) {
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

                // Tipo informado pelo usuário na planilha fixa o tipo manualmente;
                // coluna vazia mantém a classificação automática pelos itens.
                if ($tipoInformado = $this->tipoDemandaDe($linha['tipo_demanda'] ?? null)) {
                    $demanda->tipo_demanda = $tipoInformado;
                    $demanda->tipo_demanda_manual = true;
                    $demanda->save();
                }

                $chave = [
                    'demanda_id' => $demanda->id,
                    'numero_rt' => $numeroRt,
                    'numero_item' => $this->limpar($linha['numero_item'] ?? null) ?? '1',
                    'subitem' => $this->limpar($linha['subitem'] ?? null),
                ];

                $item = DemandaItem::firstOrNew($chave);
                $novo = ! $item->exists;

                if ($novo) {
                    $this->cancelarRemanejados($chave, $demanda, $resultado, $demandasTocadas);
                }

                // Campos mestres do SAP: re-sincronizam quando a coluna existe na
                // planilha. Coluna ausente não altera nada (import parcial seguro)
                // e campo assumido pelo operador (campos_editados) não é tocado.
                foreach (['local_origem', 'local_destino', 'descricao_local_retirada', 'descricao_item'] as $campoMestre) {
                    if (array_key_exists($campoMestre, $linha) && ! $item->campoEditadoPeloOperador($campoMestre)) {
                        $item->{$campoMestre} = $this->limpar($linha[$campoMestre]);
                    }
                }
                if ((array_key_exists('prazo_data', $linha) || array_key_exists('prazo_hora', $linha))
                    && ! $item->campoEditadoPeloOperador('prazo_item')) {
                    $item->prazo_item = $this->montarDataHora($linha['prazo_data'] ?? null, $linha['prazo_hora'] ?? null);
                }

                // Status e entrega são geridos pelo operador da torre: o SAP só
                // preenche quando ainda estão vazios, nunca sobrescreve.
                if ($item->status_item === null) {
                    $item->status_item = StatusItemDemanda::fromCodigo($this->limpar($linha['status_item'] ?? null));
                }
                if ($item->data_hora_entrega === null) {
                    $item->data_hora_entrega = $this->montarDataHora($linha['entrega_data'] ?? null, $linha['entrega_hora'] ?? null);
                }

                $item->save();

                $novo ? $resultado['itens_criados']++ : $resultado['itens_atualizados']++;
                $demandasTocadas[$demanda->id] = true;
            }

            // Recalcula os derivados só depois que todos os itens entraram.
            Demanda::with('itens')->whereIn('id', array_keys($demandasTocadas))->get()
                ->each(fn (Demanda $d) => $this->calculadora->recalcular($d));
        });

        return $resultado;
    }

    /**
     * Quando o SAP remaneja um item para outra viagem, a RT passa a chegar com
     * outro número de demanda. Ao criar o item na demanda nova, o exemplar ainda
     * Pendente na demanda antiga é cancelado (mantém o histórico de que passou
     * por lá e libera o encerramento automático dela). Itens já encerrados pelo
     * operador na antiga não são alterados.
     *
     * @param  array{demanda_id: int, numero_rt: string, numero_item: string, subitem: string|null}  $chave
     * @param  array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, itens_remanejados: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}  $resultado
     * @param  array<int, bool>  $demandasTocadas
     */
    private function cancelarRemanejados(array $chave, Demanda $demanda, array &$resultado, array &$demandasTocadas): void
    {
        $anteriores = DemandaItem::query()
            ->where('demanda_id', '!=', $chave['demanda_id'])
            ->where('numero_rt', $chave['numero_rt'])
            ->where('numero_item', $chave['numero_item'])
            ->where(function ($query) use ($chave) {
                $chave['subitem'] === null
                    ? $query->whereNull('subitem')
                    : $query->where('subitem', $chave['subitem']);
            })
            ->where(function ($query) {
                $query->whereNull('status_item')->orWhere('status_item', StatusItemDemanda::Pendente);
            })
            ->get();

        foreach ($anteriores as $anterior) {
            $anterior->update(['status_item' => StatusItemDemanda::Cancelado]);
            $demandasTocadas[$anterior->demanda_id] = true;
            $resultado['itens_remanejados']++;
            $resultado['avisos'][] = sprintf(
                'RT %s remanejada para a demanda #%d: item cancelado na demanda anterior.',
                $chave['numero_rt'],
                $demanda->numero_demanda,
            );
        }
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
     * Casa cada campo interno com a posição da coluna no cabeçalho, por
     * igualdade exata do rótulo (ignorando caixa e acentos).
     *
     * @param  array<int, mixed>  $cabecalho
     * @return array<string, int>
     */
    private function mapearCabecalho(array $cabecalho): array
    {
        $normalizado = [];
        foreach ($cabecalho as $posicao => $valor) {
            $n = $this->normalizar((string) $valor);
            if ($n !== '') {
                $normalizado[$posicao] = $n;
            }
        }

        $mapa = [];

        foreach (self::COLUNAS as $campo => $rotulos) {
            $aceitos = array_map(fn ($r) => $this->normalizar($r), $rotulos);

            foreach ($normalizado as $posicao => $valor) {
                if (in_array($valor, $aceitos, true)) {
                    $mapa[$campo] = $posicao;

                    break;
                }
            }
        }

        return $mapa;
    }

    /**
     * Traduz o tipo informado pelo usuário na planilha (Load, Backload,
     * Transferência) tolerando caixa e acentos. Valor desconhecido vira null.
     */
    private function tipoDemandaDe(?string $valor): ?TipoDemanda
    {
        return match ($this->normalizar((string) $valor)) {
            'load' => TipoDemanda::Load,
            'backload' => TipoDemanda::Backload,
            'transferencia' => TipoDemanda::Transferencia,
            default => null,
        };
    }

    /**
     * Normaliza um rótulo para comparação: sem acentos, minúsculo, sem espaços extras.
     */
    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim(Str::ascii($texto)));

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
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
    private function montarDataHora(?string $data, ?string $hora): ?Carbon
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
