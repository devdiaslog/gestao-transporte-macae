<?php

namespace App\Services;

use App\Enums\StatusItemDemanda;
use App\Enums\StatusSap;
use App\Models\DemandaItem;
use App\Models\TrechoSap;
use App\Traits\LeituraPlanilhaSap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

/**
 * Importa os itens que o cliente ainda cobra: liberados (03) e programados (04).
 *
 * O que importa para o cliente é ser atendido, então o item segue na tela com
 * sua previsão mesmo depois de um veículo ser programado — sai apenas quando é
 * atendido, cancelado ou suspenso.
 *
 * O item liberado ainda não tem atendimento: entra no sistema sem demanda e é
 * adotado por uma quando o transporte é programado, pelo
 * {@see ImportadorDemandas}.
 *
 * A chave do item é `Nota + Item + Subitem`, onde a Nota é a RT (padrão 32*) —
 * diferente do export de viagem, em que Nota é o atendimento (padrão 50*).
 */
class ImportadorItensLiberados
{
    use LeituraPlanilhaSap;

    /**
     * Rótulos aceitos por campo. O primeiro é o nome do modelo de importação;
     * os demais são os nomes que o SAP exporta, inclusive truncados pela
     * largura da coluna no ALV ("Data de cr", "Altura RT(").
     *
     * @var array<string, array<int, string>>
     */
    private const COLUNAS = [
        'numero_rt' => ['Nota', 'Nº da RT', 'Numero Demanda Entrega'],
        'numero_item' => ['Item', 'Item da RT', 'Item Demanda Entrega'],
        'subitem' => ['Subitem', 'Subitem da', 'Subitem Demanda Entrega'],

        'criacao_data' => ['Data Criação RT', 'Data de cr', 'Data de criação'],
        'criacao_hora' => ['Hora Criação RT', 'HoraCr.', 'Criado às', 'Hora de criação'],
        'liberacao_data' => ['Data Liberação', 'Data Liber'],
        'liberacao_hora' => ['Hora Liberação', 'Hora Liber'],

        'prazo_data' => ['Data Prazo', 'Data+Tarde', 'Data + tar', 'Data + tarde'],
        'prazo_hora' => ['Hora Prazo', 'Hr+Tarde', 'Hora + tar', 'Hora + tarde'],

        'local_origem' => ['Origem', 'Descrição Origem'],
        'local_destino' => ['Destino', 'Descrição Destino'],
        'descricao_local_retirada' => ['Local de Retirada', 'LocRetir', 'Local retirada'],
        'descricao_item' => ['Descrição da Carga', 'Descrição carga'],

        'peso_total' => ['Peso Total', 'Peso total'],
        'altura' => ['Altura', 'Altura RT(', 'Altura RT'],
        'largura' => ['Largura', 'Largura RT'],
        'comprimento' => ['Comprimento', 'Compriment', 'Comprimento RT'],

        'doc_unitizacao_superior' => ['Documento Unitização', 'DocUnitSup'],
        // O export de itens liberados não traz o contentor físico — quem
        // agrupa aqui é o documento de unitização. O mapeamento fica para o
        // caso de a coluna passar a ser incluída no layout.
        'numero_contentor' => ['Numero Contentor', 'Numero Con', 'Número Contentor'],
        'descricao_contentor' => ['Descrição Contentor', 'Descricao Contentor'],
        // Medidas da embalagem superior. O SAP as entrega como
        // "Comprimento EmbSup(m)"; os demais rótulos são os truncados do ALV.
        'comprimento_embalagem' => ['Comprimento Embalagem', 'Comprimento EmbSup(m)', 'Comprimento EmbSup', 'Compriment Emb', 'Comprimento Emb', 'Compriment'],
        'largura_embalagem' => ['Largura Embalagem', 'Largura EmbSup(m)', 'Largura EmbSup', 'Largura  E', 'Largura Emb', 'Largura Em'],
        'altura_embalagem' => ['Altura Embalagem', 'Altura EmbSup(m)', 'Altura EmbSup', 'Altura Emb'],
        'grupo_planejamento' => ['Grupo Planejamento', 'Grupo plan'],
        'status_sap' => ['Status', 'Status do'],
    ];

    /** @var array<int, string> */
    private const CABECALHO_MODELO = [
        'Nota',
        'Item',
        'Subitem',
        'Data Criação RT',
        'Hora Criação RT',
        'Data Liberação',
        'Hora Liberação',
        'Data Prazo',
        'Hora Prazo',
        'Origem',
        'Local de Retirada',
        'Destino',
        'Descrição da Carga',
        'Peso Total',
        'Comprimento',
        'Largura',
        'Altura',
        'Documento Unitização',
        'Descrição Contentor',
        'Comprimento EmbSup(m)',
        'Largura EmbSup(m)',
        'Altura EmbSup(m)',
        'Grupo Planejamento',
        'Status',
    ];

    /** @var array<int, array<int, string>> */
    private const EXEMPLOS_MODELO = [
        // Item dentro de uma unitização: quem ocupa o piso é ela.
        ['326213060', '5', '2', '03.07.2026', '13:56:13', '03.07.2026', '13:56:46', '10.07.2026', '00:00:00',
            'BASE VITORIA', '', 'ARM-MACAE', 'SKID P/PROTEÇÃO E TRANSPORTE', '2.408,000', '3,1000', '3,3000', '3,6000',
            '4803478', 'CISA1010093 Container 3MDry(3,0x2,4x2,4)', '3,0000', '2,4000', '2,4000', 'T44', '03'],
        // Item solto: vale pelas próprias medidas.
        ['326340468', '1', '2', '10.07.2026', '16:18:40', '10.07.2026', '16:22:02', '22.07.2026', '14:00:00',
            'BASE VITORIA', 'AL-06', 'ARM-MACAE', 'SKID P/ARMAZ.E TRANSFERÊNCIA', '12.706,000', '4,7000', '3,2000', '3,7000',
            '', '', '', '', '', 'T44', '03'],
    ];

    /**
     * Gera o modelo .xlsx de importação num arquivo temporário.
     */
    public function gerarModelo(): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'modelo_itens_liberados_').'.xlsx';

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
     * @param  bool  $marcarAusentes  Quando o arquivo é o export completo dos itens
     *                                liberados, marca para conferência os que já
     *                                não constam nele.
     * @return array{itens_criados: int, trechos_criados: int, itens_atualizados: int, itens_inalterados: int, itens_ausentes: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}
     */
    public function importar(string $caminho, ?int $usuarioId = null, bool $marcarAusentes = true): array
    {
        $linhas = $this->lerPlanilhaSap($caminho, self::COLUNAS, $this->localizarCabecalhoSap($caminho, self::COLUNAS, ['numero_rt', 'numero_item']));

        if ($linhas === []) {
            return [
                'itens_criados' => 0,
                'trechos_criados' => 0,
                'itens_atualizados' => 0,
                'itens_inalterados' => 0,
                'itens_ausentes' => 0,
                'linhas_ignoradas' => 0,
                'avisos' => [],
                'erros' => ['Nenhuma linha de dados encontrada na planilha.'],
            ];
        }

        return $this->importarLinhas($linhas, $usuarioId, $marcarAusentes);
    }

    /**
     * Processa linhas já estruturadas (da planilha ou da API).
     *
     * @param  array<int|string, array<string, string|null>>  $linhas
     * @return array{itens_criados: int, trechos_criados: int, itens_atualizados: int, itens_inalterados: int, itens_ausentes: int, linhas_ignoradas: int, erros: array<int, string>, avisos: array<int, string>}
     */
    public function importarLinhas(array $linhas, ?int $usuarioId = null, bool $marcarAusentes = true): array
    {
        $resultado = [
            'itens_criados' => 0,
            'trechos_criados' => 0,
            'itens_atualizados' => 0,
            'itens_inalterados' => 0,
            'itens_ausentes' => 0,
            'linhas_ignoradas' => 0,
            'avisos' => [],
            'erros' => [],
        ];

        /** @var array<int, int> $vistos ids dos itens presentes no arquivo */
        $vistos = [];

        DB::transaction(function () use ($linhas, $marcarAusentes, &$resultado, &$vistos) {
            foreach ($linhas as $numeroLinha => $linha) {
                $numeroRt = $this->limpar($linha['numero_rt'] ?? null);
                $numeroItem = $this->limpar($linha['numero_item'] ?? null);

                if ($numeroRt === null || ! ctype_digit($numeroRt) || $numeroItem === null) {
                    $resultado['linhas_ignoradas']++;

                    continue;
                }

                $subitem = $this->limpar($linha['subitem'] ?? null);
                $item = $this->localizarItem($numeroRt, $numeroItem, $subitem);
                $novo = ! $item->exists;

                if ($novo) {
                    $item->fill([
                        'numero_rt' => $numeroRt,
                        'numero_item' => $numeroItem,
                        'subitem' => $subitem,
                        'status_item' => StatusItemDemanda::Pendente,
                    ]);
                }

                $this->aplicarDadosDaRt($item, $linha);
                $this->aplicarCamposMestres($item, $linha);
                $this->aplicarStatusSap($item, $linha, $numeroLinha, $resultado);
                $this->aplicarPrazo($item, $linha);

                // Reapareceu no export depois de ter sumido: registra o retorno
                // para quem prometeu uma data antes saber que o item ficou fora
                // do radar nesse intervalo.
                if ($item->ausente_no_sap_em !== null) {
                    $item->retornou_ao_sap_em = now();
                    $item->vezes_ausente = (int) $item->vezes_ausente + 1;
                    $item->ausente_no_sap_em = null;
                }

                if ($item->isDirty() || $novo) {
                    $item->save();
                    $resultado[$novo ? 'itens_criados' : 'itens_atualizados']++;
                } else {
                    // Reimportação em que nada mudou: contabilizada para o
                    // total bater com o que o arquivo trouxe.
                    $resultado['itens_inalterados']++;
                }

                $vistos[] = $item->id;
            }

            if ($marcarAusentes) {
                $resultado['itens_ausentes'] = $this->marcarAusentes($vistos);
            }

            // Rota que ninguém cadastrou vira pendência visível em Cadastros,
            // em vez de a equipe descobri-la item a item ao calcular prazo.
            $resultado['trechos_criados'] = TrechoSap::garantirRotas(
                collect($linhas)->map(fn (array $l) => [
                    'origem' => $this->limpar($l['local_origem'] ?? null),
                    'destino' => $this->limpar($l['local_destino'] ?? null),
                ])
            );
        });

        return $resultado;
    }

    /**
     * Localiza o item pela chave natural do SAP, ou devolve uma instância nova.
     *
     * Um item pode aparecer mais de uma vez quando houve remanejo entre
     * demandas; nesse caso vence o mais recente, que é o que está em vigor.
     */
    private function localizarItem(string $numeroRt, string $numeroItem, ?string $subitem): DemandaItem
    {
        $item = DemandaItem::query()
            ->where('numero_rt', $numeroRt)
            ->where('numero_item', $numeroItem)
            ->when(
                $subitem === null,
                fn ($q) => $q->whereNull('subitem'),
                fn ($q) => $q->where('subitem', $subitem),
            )
            ->latest('id')
            ->first();

        return $item ?? new DemandaItem;
    }

    /**
     * Dados da RT: sempre re-sincronizam, pois descrevem o documento no SAP e
     * não são editáveis pelo operador.
     *
     * @param  array<string, string|null>  $linha
     */
    private function aplicarDadosDaRt(DemandaItem $item, array $linha): void
    {
        $criacao = $this->montarDataHora($linha['criacao_data'] ?? null, $linha['criacao_hora'] ?? null);
        if ($criacao !== null) {
            $item->data_hora_criacao_rt = $criacao;
        }

        $liberacao = $this->montarDataHora($linha['liberacao_data'] ?? null, $linha['liberacao_hora'] ?? null);
        if ($liberacao !== null) {
            $item->data_hora_liberacao_rt = $liberacao;
        }

        foreach (['doc_unitizacao_superior', 'grupo_planejamento', 'numero_contentor', 'descricao_contentor'] as $campo) {
            if (array_key_exists($campo, $linha)) {
                $item->{$campo} = $this->limpar($linha[$campo]);
            }
        }

        foreach (['peso_total', 'altura', 'largura', 'comprimento', 'comprimento_embalagem', 'largura_embalagem', 'altura_embalagem'] as $medida) {
            if (array_key_exists($medida, $linha)) {
                $item->{$medida} = $this->numero($linha[$medida]);
            }
        }
    }

    /**
     * Campos que o operador pode assumir: só sincronizam enquanto ele não os
     * tiver editado pela interface.
     *
     * @param  array<string, string|null>  $linha
     */
    private function aplicarCamposMestres(DemandaItem $item, array $linha): void
    {
        $mestres = ['local_origem', 'local_destino', 'descricao_local_retirada', 'descricao_item'];

        foreach ($mestres as $campo) {
            if (! array_key_exists($campo, $linha) || $item->campoEditadoPeloOperador($campo)) {
                continue;
            }

            $valor = $this->limpar($linha[$campo]);

            if ($valor !== null) {
                $item->{$campo} = $valor;
            }
        }
    }

    /**
     * @param  array<string, string|null>  $linha
     * @param  array{avisos: array<int, string>, ...}  $resultado
     */
    private function aplicarStatusSap(DemandaItem $item, array $linha, int|string $numeroLinha, array &$resultado): void
    {
        $codigo = $this->limpar($linha['status_sap'] ?? null);

        // Sem a coluna de status, o export é sabidamente de itens liberados.
        if ($codigo === null) {
            $item->status_sap ??= StatusSap::Liberado;

            return;
        }

        $status = StatusSap::fromCodigo($codigo);

        if ($status === null) {
            $resultado['avisos'][] = "Linha {$numeroLinha}: status do SAP não reconhecido ({$codigo}); mantido o anterior.";

            return;
        }

        $item->status_sap = $status;
    }

    /**
     * Prazo do item a partir da data e hora "mais tarde" do SAP.
     *
     * Hora zerada significa o limite do dia anterior: o SAP registra o instante
     * em que o item já está atrasado, então "10/07 00:00:00" quer dizer que a
     * entrega precisa acontecer até 09/07 23:59:59.
     *
     * @param  array<string, string|null>  $linha
     */
    private function aplicarPrazo(DemandaItem $item, array $linha): void
    {
        if (! array_key_exists('prazo_data', $linha)) {
            return;
        }

        $prazo = $this->prazoDe($linha['prazo_data'] ?? null, $linha['prazo_hora'] ?? null);

        if ($prazo === null) {
            return;
        }

        // O prazo do SAP é registrado sempre, para conferência e para tornar a
        // renegociação visível na tela.
        $item->prazo_sap = $prazo;

        // Mas não vira o prazo vigente: o que vale é a regra por trecho, e o
        // SAP não a segue — na rota BASE RIO DE JANEIRO 2 → ARM MACAE os dois
        // divergiam em até 152 horas. Item sem trecho calculado fica sem prazo,
        // porque prazo errado é pior do que prazo nenhum: ele fabrica atraso
        // onde não há, ou dá folga que a operação não tem.
    }

    public function prazoDe(?string $data, ?string $hora): ?Carbon
    {
        $prazo = $this->montarDataHora($data, $hora);

        if ($prazo === null) {
            return null;
        }

        return $this->normalizarHora($hora) === '00:00:00'
            ? $prazo->subDay()->setTime(23, 59, 59)
            : $prazo;
    }

    /**
     * Marca para conferência os itens em cobrança que já não constam no export.
     *
     * O escopo é liberado + programado porque é isso que o export traz: o
     * cliente acompanha o item até ser atendido, então o programado continua na
     * tela com sua previsão. Sair do arquivo significa ter deixado a cobrança.
     *
     * O status não é alterado sozinho: o item pode ter sido atendido, cancelado
     * ou suspenso, e inferir o desfecho errado apagaria uma previsão válida.
     * Quem decide é o operador.
     *
     * @param  array<int, int>  $vistos
     */
    private function marcarAusentes(array $vistos): int
    {
        $emCobranca = array_map(
            fn (StatusSap $s) => $s->value,
            array_filter(StatusSap::cases(), fn (StatusSap $s) => $s->emCobranca()),
        );

        return DemandaItem::query()
            ->whereIn('status_sap', $emCobranca)
            ->whereNull('ausente_no_sap_em')
            ->when($vistos !== [], fn ($q) => $q->whereNotIn('id', $vistos))
            ->update(['ausente_no_sap_em' => now()]);
    }
}
