<?php

namespace App\Services;

use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\StatusSap;
use App\Enums\TipoCadastro;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\Equipamento;
use App\Traits\LeituraPlanilhaSap;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Importa itens de demanda a partir do export do SAP em planilha.
 *
 * Cada linha do arquivo é um item. A demanda (cabeçalho) é localizada pelo
 * número da Nota: se não existir é criada, se existir o item é associado a ela.
 */
class ImportadorDemandas
{
    use LeituraPlanilhaSap;

    /**
     * Rótulos aceitos por campo. O primeiro é o nome genérico (usado no modelo);
     * os demais são aliases mantidos para compatibilidade com o export do SAP.
     * O cabeçalho é reconhecido por igualdade exata (ignorando caixa e acentos).
     *
     * @var array<string, array<int, string>>
     */
    private const COLUNAS = [
        'nota' => ['Numero Demanda Viagem', 'Nota'],
        'criacao_data' => ['Data Criação', 'Data de Criação', 'Dt criação', 'Data'],
        'criacao_hora' => ['Hora Criação', 'Hora de Criação', 'Hr criação', 'Hora'],
        'tipo_demanda' => ['Tipo Demanda'],
        'numero_rt' => ['Numero Demanda Entrega', 'Nº da RT'],
        'numero_item' => ['Item Demanda Entrega', 'Item da RT'],
        'subitem' => ['Subitem Demanda Entrega', 'Subitem da'],
        // O código do local (ex.: SEROPEDICA) vence a descrição formatada
        // (ex.: Seropédica): é o que a operação usa e o que classifica o tipo.
        'local_origem' => ['Origem', 'Descrição Origem'],
        'local_destino' => ['Destino', 'Descrição Destino'],
        'descricao_local_retirada' => ['Local de Retirada', 'Local retirada'],
        'descricao_item' => ['Descrição da Carga', 'Descrição Demanda Entrega', 'Descrição'],
        'peso_total' => ['Peso Total', 'Peso total'],
        'altura' => ['Altura', 'Altura RT(', 'Altura RT'],
        'largura' => ['Largura', 'Largura RT'],
        'comprimento' => ['Comprimento', 'Compriment', 'Comprimento RT'],
        'status_item' => ['Status Demanda Entrega', 'Status do'],
        'prazo_data' => ['Data Prazo', 'Data + tar'],
        'prazo_hora' => ['Hora Prazo', 'Hora + tar'],
        'entrega_data' => ['Data Entrega', 'Dt entregu'],
        'entrega_hora' => ['Hora Entrega', 'Hr entregu'],
        'observacao' => ['Observação', 'Observacao'],
        'equipamento' => ['Descrição Veiculo', 'Descrição equipamento'],

        // Dados da RT. Normalmente chegam antes, pela importação de itens
        // liberados (status 03), mas vêm aqui também para o item que entra
        // direto como programado sem ter passado por aquela tela.
        // Os rótulos são distintos dos de "criacao_data"/"criacao_hora", que
        // se referem à criação da demanda (viagem) e não da RT.
        'criacao_rt_data' => ['Data Criação RT', 'Data de cr'],
        'criacao_rt_hora' => ['Hora Criação RT', 'HoraCr.'],
        'liberacao_data' => ['Data Liberação', 'Data Liber'],
        'liberacao_hora' => ['Hora Liberação', 'Hora Liber'],
        'doc_unitizacao_superior' => ['Documento Unitização', 'DocUnitSup'],
        'numero_contentor' => ['Numero Contentor', 'Numero Con', 'Número Contentor'],
        'descricao_contentor' => ['Descrição Contentor', 'Descricao Contentor'],
        // Medidas da unitização: o SAP as entrega em colunas proprias, com
        // nomes truncados no ALV ("Altura Emb", "Largura  E").
        'comprimento_embalagem' => ['Comprimento Embalagem', 'Compriment Emb', 'Comprimento Emb'],
        'largura_embalagem' => ['Largura Embalagem', 'Largura  E', 'Largura Emb'],
        'altura_embalagem' => ['Altura Embalagem', 'Altura Emb'],
        'grupo_planejamento' => ['Grupo Planejamento', 'Grupo plan'],
    ];

    /**
     * Cabeçalho do modelo de importação — nomes compatíveis com o mapeamento
     * de COLUNAS, na ordem em que a RT precede o item da RT.
     *
     * @var array<int, string>
     */
    private const CABECALHO_MODELO = [
        'Numero Demanda Viagem',
        'Data Criação',
        'Hora Criação',
        'Tipo Demanda',
        'Numero Demanda Entrega',
        'Item Demanda Entrega',
        'Subitem Demanda Entrega',
        'Origem',
        'Local de Retirada',
        'Destino',
        'Descrição da Carga',
        'Peso Total',
        'Altura',
        'Largura',
        'Comprimento',
        'Descrição Veiculo',
        'Status Demanda Entrega',
        'Data Prazo',
        'Hora Prazo',
        'Data Entrega',
        'Hora Entrega',
        'Observação',
        'Data Criação RT',
        'Hora Criação RT',
        'Data Liberação',
        'Hora Liberação',
        'Documento Unitização',
        'Numero Contentor',
        'Descrição Contentor',
        'Grupo Planejamento',
    ];

    /**
     * Linhas de exemplo mostrando o formato esperado de cada coluna.
     *
     * @var array<int, array<int, string>>
     */
    private const EXEMPLOS_MODELO = [
        ['509538496', '23.07.2026', '08:00:00', 'Backload', '326741968', '1', '5', 'PACU', 'PACU-CAIS 2', 'ARM-MACAE', 'Tubos de perfuração', '2.500,50', '2,60', '2,40', '12,00', 'VIX 1993 - AXOR 1933 S 2P T44', '04', '24.07.2026', '10:00:00', '', '', '', '20.07.2026', '07:15:00', '21.07.2026', '09:30:00', '4803478', '30112162', 'CISA1010093 Container 3MDry(3,0x2,4x2,4)', 'T44'],
        ['619012345', '24.07.2026', '09:15:00', '', '326800000', '1', '1', 'ARM-MACAE', 'AL-50', 'BMAC', 'Outra carga', '800', '1,20', '1,00', '2,40', 'VIX 1994 - AXOR 1933 S 2P T44', '07', '25.07.2026', '14:30:00', '25.07.2026', '13:45:00', 'Insight da análise', '', '', '', '', '', '', '', 'T44'],
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
     * @return array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, itens_adotados: int, itens_remanejados: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}
     */
    public function importar(string $caminho, ?int $usuarioId = null, ?int $somenteNota = null): array
    {
        $linhas = $this->lerPlanilha($caminho);

        if ($linhas === []) {
            return [
                'demandas_criadas' => 0,
                'itens_criados' => 0,
                'itens_atualizados' => 0,
                'itens_adotados' => 0,
                'itens_remanejados' => 0,
                'linhas_ignoradas' => 0,
                'avisos' => [],
                'erros' => ['Nenhuma linha de dados encontrada na planilha.'],
            ];
        }

        return $this->importarLinhas($linhas, $usuarioId, $somenteNota);
    }

    /**
     * Processa linhas já estruturadas (vindas da planilha ou da API), aplicando
     * as mesmas regras de negócio: campos do operador nunca sobrescritos,
     * campos mestres sincronizados, remanejo de RT e recálculo dos derivados.
     *
     * @param  array<int|string, array<string, string|null>>  $linhas
     * @return array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, itens_adotados: int, itens_remanejados: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}
     */
    public function importarLinhas(array $linhas, ?int $usuarioId = null, ?int $somenteNota = null): array
    {
        $resultado = [
            'demandas_criadas' => 0,
            'itens_criados' => 0,
            'itens_atualizados' => 0,
            'itens_adotados' => 0,
            'itens_remanejados' => 0,
            'linhas_ignoradas' => 0,
            'avisos' => [],
            'erros' => [],
        ];

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

                // Criação no SAP: dado do SAP, sempre re-sincroniza quando presente.
                if ($criacao = $this->montarDataHora($linha['criacao_data'] ?? null, $linha['criacao_hora'] ?? null)) {
                    $demanda->data_hora_criacao_sap = $criacao;
                    $demanda->save();
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
                    // O item pode já existir sem demanda, vindo da importação de
                    // liberados (status 03). Como a chave RT + item + subitem é
                    // única no SAP, é o mesmo item ganhando atendimento: a demanda
                    // o adota, preservando a previsão e o histórico já registrados.
                    $liberado = $this->itemLiberadoSemDemanda($chave);

                    if ($liberado !== null) {
                        $item = $liberado;
                        $item->demanda_id = $demanda->id;
                        $novo = false;
                        $resultado['itens_adotados']++;
                    } else {
                        $this->cancelarRemanejados($chave, $demanda, $resultado, $demandasTocadas);
                    }
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

                // Peso e dimensões da carga: dados do SAP, re-sincronizam quando a
                // coluna existe na planilha.
                foreach (['peso_total', 'altura', 'largura', 'comprimento', 'comprimento_embalagem', 'largura_embalagem', 'altura_embalagem'] as $campoMedida) {
                    if (array_key_exists($campoMedida, $linha)) {
                        $item->{$campoMedida} = $this->numero($linha[$campoMedida]);
                    }
                }

                $this->aplicarDadosDaRt($item, $linha);

                // Status no SAP: sempre re-sincroniza — mesmo com o status do sistema
                // assumido pelo operador, ele enxerga o estado real do SAP ao
                // finalizar o item. Código fora do ciclo de vida conhecido é ignorado
                // (fica registrado na observação) para não derrubar a importação
                // inteira caso o SAP passe a emitir um status novo.
                if (array_key_exists('status_item', $linha) && ($codigoSap = $this->limpar($linha['status_item'])) !== null) {
                    $statusSap = StatusSap::fromCodigo($codigoSap);

                    if ($statusSap !== null) {
                        $item->status_sap = $statusSap;
                    } else {
                        $item->acrescentarObservacao("Status do SAP não reconhecido na importação: {$codigoSap}.");
                    }
                }

                // Status e entrega: o SAP atualiza livremente (itens podem ser
                // finalizados por operadores fora da torre) até o operador da
                // torre alterá-los pela interface — daí o campo passa a ser dele.
                if (! $item->campoEditadoPeloOperador('status_item')) {
                    $status = StatusItemDemanda::fromCodigo($this->limpar($linha['status_item'] ?? null));
                    if ($status !== null) {
                        $item->status_item = $status;
                    }
                }
                if (! $item->campoEditadoPeloOperador('data_hora_entrega')) {
                    $entrega = $this->montarDataHora($linha['entrega_data'] ?? null, $linha['entrega_hora'] ?? null);
                    if ($entrega !== null) {
                        $item->data_hora_entrega = $entrega;
                    }
                }

                // Observação é acumulativa: acrescenta ao histórico (pulando
                // uma linha), nunca sobrescreve; texto repetido não duplica.
                $item->acrescentarObservacao($linha['observacao'] ?? null);

                $item->save();

                $novo ? $resultado['itens_criados']++ : $resultado['itens_atualizados']++;
                $demandasTocadas[$demanda->id] = true;
            }

            // Recalcula os derivados só depois que todos os itens entraram.
            Demanda::with('itens')->whereIn('id', array_keys($demandasTocadas))->get()
                ->each(fn (Demanda $d) => $this->calculadora->recalcular($d, 'sap'));
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
     * @param  array{demandas_criadas: int, itens_criados: int, itens_atualizados: int, itens_adotados: int, itens_remanejados: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}  $resultado
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
        return $this->lerPlanilhaSap($caminho, self::COLUNAS);
    }

    /**
     * Dados do documento da RT, quando a planilha os traz.
     *
     * Descrevem o documento no SAP e não são editáveis pelo operador, então
     * sempre re-sincronizam. Coluna ausente não altera nada: o item que já veio
     * pela importação de liberados mantém o que tinha.
     *
     * @param  array<string, string|null>  $linha
     */
    private function aplicarDadosDaRt(DemandaItem $item, array $linha): void
    {
        $criacao = $this->montarDataHora($linha['criacao_rt_data'] ?? null, $linha['criacao_rt_hora'] ?? null);
        if ($criacao !== null) {
            $item->data_hora_criacao_rt = $criacao;
        }

        $liberacao = $this->montarDataHora($linha['liberacao_data'] ?? null, $linha['liberacao_hora'] ?? null);
        if ($liberacao !== null) {
            $item->data_hora_liberacao_rt = $liberacao;
        }

        foreach (['doc_unitizacao_superior', 'grupo_planejamento', 'numero_contentor', 'descricao_contentor'] as $campo) {
            if (array_key_exists($campo, $linha) && ($valor = $this->limpar($linha[$campo])) !== null) {
                $item->{$campo} = $valor;
            }
        }
    }

    /**
     * Item já cadastrado pela importação de liberados (status 03), que ainda
     * não tem atendimento e portanto pode ser adotado por esta demanda.
     *
     * @param  array{demanda_id: int, numero_rt: string, numero_item: string, subitem: string|null}  $chave
     */
    private function itemLiberadoSemDemanda(array $chave): ?DemandaItem
    {
        return DemandaItem::query()
            ->whereNull('demanda_id')
            ->where('numero_rt', $chave['numero_rt'])
            ->where('numero_item', $chave['numero_item'])
            ->when(
                $chave['subitem'] === null,
                fn ($q) => $q->whereNull('subitem'),
                fn ($q) => $q->where('subitem', $chave['subitem']),
            )
            ->first();
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
}
