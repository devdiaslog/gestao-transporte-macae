<?php

namespace Tests\Feature;

use App\Enums\StatusItemDemanda;
use App\Enums\StatusSap;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Services\ImportadorDemandas;
use App\Services\ImportadorItensLiberados;
use App\Support\ContentorSap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportadorItensLiberadosTest extends TestCase
{
    use RefreshDatabase;

    /** Cabeçalho do modelo gerado pelo sistema. */
    private const CABECALHO = [
        'Nota', 'Item', 'Subitem', 'Data Criação RT', 'Hora Criação RT',
        'Data Liberação', 'Hora Liberação', 'Data Prazo', 'Hora Prazo',
        'Origem', 'Local de Retirada', 'Destino', 'Descrição da Carga',
        'Peso Total', 'Comprimento', 'Largura', 'Altura',
        'Documento Unitização', 'Grupo Planejamento', 'Status',
    ];

    /**
     * @param  array<int, array<int, string>>  $linhas
     * @param  array<int, string>|null  $cabecalho
     * @param  array<int, array<int, string>>  $antesDoCabecalho  linhas de topo do export do SAP
     */
    private function planilha(array $linhas, ?array $cabecalho = null, array $antesDoCabecalho = []): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'itens_liberados_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($caminho);

        foreach ($antesDoCabecalho as $topo) {
            $writer->addRow(Row::fromValues($topo));
        }

        $writer->addRow(Row::fromValues($cabecalho ?? self::CABECALHO));

        foreach ($linhas as $linha) {
            $writer->addRow(Row::fromValues($linha));
        }

        $writer->close();

        return $caminho;
    }

    /**
     * @param  array<string, string>  $sobrescreve
     * @return array<int, string>
     */
    private function linha(array $sobrescreve = []): array
    {
        $valores = array_merge([
            'Nota' => '326213060',
            'Item' => '5',
            'Subitem' => '2',
            'Data Criação RT' => '03.07.2026',
            'Hora Criação RT' => '13:56:13',
            'Data Liberação' => '03.07.2026',
            'Hora Liberação' => '13:56:46',
            'Data Prazo' => '10.07.2026',
            'Hora Prazo' => '14:00:00',
            'Origem' => 'BASE VITORIA',
            'Local de Retirada' => 'AL-06',
            'Destino' => 'ARM-MACAE',
            'Descrição da Carga' => 'SKID P/PROTEÇÃO',
            'Peso Total' => '2.408,000',
            'Comprimento' => '3,1000',
            'Largura' => '3,3000',
            'Altura' => '3,6000',
            'Documento Unitização' => '4803478',
            'Grupo Planejamento' => 'T44',
            'Status' => '03',
        ], $sobrescreve);

        return array_values($valores);
    }

    public function test_cria_item_sem_demanda_com_os_dados_da_rt(): void
    {
        $resultado = app(ImportadorItensLiberados::class)->importar($this->planilha([$this->linha()]));

        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertSame(0, $resultado['itens_atualizados']);

        $item = DemandaItem::firstOrFail();

        $this->assertNull($item->demanda_id);
        $this->assertSame('326213060', $item->numero_rt);
        $this->assertSame('5', $item->numero_item);
        $this->assertSame('2', $item->subitem);
        $this->assertSame(StatusSap::Liberado, $item->status_sap);
        $this->assertSame(StatusItemDemanda::Pendente, $item->status_item);

        $this->assertSame('03/07/2026 13:56', $item->data_hora_criacao_rt->format('d/m/Y H:i'));
        $this->assertSame('03/07/2026 13:56:46', $item->data_hora_liberacao_rt->format('d/m/Y H:i:s'));
        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame('T44', $item->grupo_planejamento);
        $this->assertSame('BASE VITORIA', $item->local_origem);
        $this->assertSame('ARM-MACAE', $item->local_destino);
        $this->assertSame(2408.0, (float) $item->peso_total);
        $this->assertSame(3.6, (float) $item->altura);
    }

    public function test_prazo_com_hora_preenchida_usa_a_hora_do_sap(): void
    {
        app(ImportadorItensLiberados::class)->importar($this->planilha([
            $this->linha(['Data Prazo' => '10.07.2026', 'Hora Prazo' => '14:00:00']),
        ]));

        $this->assertSame('10/07/2026 14:00:00', DemandaItem::firstOrFail()->prazo_sap->format('d/m/Y H:i:s'));
    }

    /**
     * Hora zerada é o instante em que o item já está atrasado: o limite real é
     * o fim do dia anterior.
     */
    public function test_prazo_com_hora_zerada_vira_fim_do_dia_anterior(): void
    {
        app(ImportadorItensLiberados::class)->importar($this->planilha([
            $this->linha(['Data Prazo' => '10.07.2026', 'Hora Prazo' => '00:00:00']),
        ]));

        $this->assertSame('09/07/2026 23:59:59', DemandaItem::firstOrFail()->prazo_sap->format('d/m/Y H:i:s'));
    }

    public function test_reimportacao_atualiza_em_vez_de_duplicar(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([$this->linha()]));
        $resultado = $importador->importar($this->planilha([
            $this->linha(['Destino' => 'ARM-RIO', 'Peso Total' => '3.000,000']),
        ]));

        $this->assertSame(0, $resultado['itens_criados']);
        $this->assertSame(1, $resultado['itens_atualizados']);
        $this->assertSame(1, DemandaItem::count());

        $item = DemandaItem::firstOrFail();
        $this->assertSame('ARM-RIO', $item->local_destino);
        $this->assertSame(3000.0, (float) $item->peso_total);
    }

    public function test_nao_sobrescreve_campo_assumido_pelo_operador(): void
    {
        $importador = app(ImportadorItensLiberados::class);
        $importador->importar($this->planilha([$this->linha()]));

        DemandaItem::firstOrFail()->update([
            'local_destino' => 'PÁTIO INTERNO',
            'prazo_item' => '2026-07-15 08:00:00',
            'campos_editados' => ['local_destino', 'prazo_item'],
        ]);

        $importador->importar($this->planilha([
            $this->linha(['Destino' => 'ARM-RIO', 'Data Prazo' => '20.07.2026', 'Hora Prazo' => '09:00:00']),
        ]));

        $item = DemandaItem::firstOrFail();
        $this->assertSame('PÁTIO INTERNO', $item->local_destino);
        $this->assertSame('15/07/2026 08:00', $item->prazo_item->format('d/m/Y H:i'));
        // Campos não assumidos pelo operador seguem sincronizando.
        $this->assertSame('BASE VITORIA', $item->local_origem);
    }

    /**
     * O item pode já ter sido programado (status 04) e ganhado uma demanda; o
     * export de liberados atualiza os dados da RT sem desvinculá-lo.
     */
    public function test_item_ja_vinculado_a_demanda_mantem_o_vinculo(): void
    {
        $demanda = Demanda::factory()->create();
        $item = DemandaItem::create([
            'demanda_id' => $demanda->id,
            'numero_rt' => '326213060',
            'numero_item' => '5',
            'subitem' => '2',
            'status_sap' => StatusSap::Programado,
        ]);

        app(ImportadorItensLiberados::class)->importar($this->planilha([$this->linha()]));

        $item->refresh();
        $this->assertSame($demanda->id, $item->demanda_id);
        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame(1, DemandaItem::count());
    }

    public function test_item_que_sumiu_do_export_e_marcado_para_conferencia(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([
            $this->linha(),
            $this->linha(['Nota' => '326340468', 'Item' => '1']),
        ]));

        $resultado = $importador->importar($this->planilha([$this->linha()]));

        $this->assertSame(1, $resultado['itens_ausentes']);

        $sumiu = DemandaItem::where('numero_rt', '326340468')->firstOrFail();
        $this->assertNotNull($sumiu->ausente_no_sap_em);
        // O status não é alterado sozinho: quem decide o desfecho é o operador.
        $this->assertSame(StatusSap::Liberado, $sumiu->status_sap);

        $presente = DemandaItem::where('numero_rt', '326213060')->firstOrFail();
        $this->assertNull($presente->ausente_no_sap_em);
    }

    public function test_item_que_reaparece_deixa_de_estar_ausente(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([$this->linha()]));
        $importador->importar($this->planilha([$this->linha(['Nota' => '326340468'])]));

        $this->assertNotNull(DemandaItem::where('numero_rt', '326213060')->firstOrFail()->ausente_no_sap_em);

        $importador->importar($this->planilha([$this->linha()]), null, false);

        $this->assertNull(DemandaItem::where('numero_rt', '326213060')->firstOrFail()->ausente_no_sap_em);
    }

    /**
     * O export traz liberados e programados: o cliente acompanha o item até ser
     * atendido. Some do arquivo quem deixou a cobrança.
     */
    public function test_item_programado_que_some_tambem_e_marcado_para_conferencia(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([
            $this->linha(),
            $this->linha(['Nota' => '326340468', 'Item' => '1', 'Status' => '04']),
        ]));

        $this->assertSame(StatusSap::Programado, DemandaItem::where('numero_rt', '326340468')->firstOrFail()->status_sap);

        $resultado = $importador->importar($this->planilha([$this->linha()]));

        $this->assertSame(1, $resultado['itens_ausentes']);
        $this->assertNotNull(DemandaItem::where('numero_rt', '326340468')->firstOrFail()->ausente_no_sap_em);
    }

    /**
     * Item já atendido saiu da cobrança por definição — não deve ser cobrado de
     * conferência só por não constar mais no export.
     */
    public function test_item_atendido_nao_e_marcado_como_ausente(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([
            $this->linha(),
            $this->linha(['Nota' => '326340468', 'Item' => '1', 'Status' => '07']),
        ]));

        $resultado = $importador->importar($this->planilha([$this->linha()]));

        $this->assertSame(0, $resultado['itens_ausentes']);
        $this->assertNull(DemandaItem::where('numero_rt', '326340468')->firstOrFail()->ausente_no_sap_em);
    }

    /**
     * O item sai e volta do export.
     *
     * Acontece quando muda de grupo de planejamento ou volta a um status
     * anterior à liberação: some do filtro, e reaparece quando é devolvido ao
     * grupo e liberado de novo. É o mesmo item, com a mesma chave — o que ele
     * já tinha é preservado, e a ida e volta fica registrada.
     */
    public function test_item_que_sai_e_volta_registra_o_retorno(): void
    {
        $importador = app(ImportadorItensLiberados::class);
        $importador->importar($this->planilha([$this->linha()]));

        $item = DemandaItem::firstOrFail();
        $item->registrarPrevisao(now()->addDays(3));
        $this->assertSame(0, $item->fresh()->vezes_ausente);

        // Trocou de grupo no SAP: some do export.
        $this->travel(1)->days();
        $importador->importar($this->planilha([$this->linha(['Nota' => '326999999'])]));
        $item->refresh();
        $this->assertNotNull($item->ausente_no_sap_em);
        $this->assertFalse($item->voltouAoSap());

        // Devolvido ao grupo e liberado de novo, dias depois: volta.
        $this->travel(3)->days();
        $importador->importar($this->planilha([$this->linha()]));
        $item->refresh();

        $this->assertNull($item->ausente_no_sap_em);
        $this->assertTrue($item->voltouAoSap());
        $this->assertSame(1, $item->vezes_ausente);
        // A previsão dada antes de sumir continua lá, mas sinalizada: o item
        // ficou fora do radar e a data prometida provavelmente não vale mais.
        $this->assertNotNull($item->data_hora_previsao_entrega);
        $this->assertTrue($item->previsaoAnteriorAoRetorno());
        // E nada do que a operação tinha registrado se perdeu: é o mesmo item
        // voltando, não um novo.
        $this->assertCount(1, $item->previsoes);
        $this->assertSame(1, DemandaItem::where('numero_rt', '326213060')->count());
    }

    /**
     * Cada ciclo conta: item que vive saindo e voltando é retrabalho visível.
     */
    public function test_conta_quantas_vezes_o_item_ficou_fora(): void
    {
        $importador = app(ImportadorItensLiberados::class);
        $importador->importar($this->planilha([$this->linha()]));

        foreach (range(1, 3) as $ciclo) {
            $importador->importar($this->planilha([$this->linha(['Nota' => '326999999'])]));
            $importador->importar($this->planilha([$this->linha()]));
        }

        $this->assertSame(3, DemandaItem::where('numero_rt', '326213060')->firstOrFail()->vezes_ausente);
    }

    /**
     * Previsão registrada depois do retorno não é mais a que ficou para trás.
     */
    public function test_previsao_dada_apos_o_retorno_nao_e_sinalizada(): void
    {
        $importador = app(ImportadorItensLiberados::class);
        $importador->importar($this->planilha([$this->linha()]));
        $importador->importar($this->planilha([$this->linha(['Nota' => '326999999'])]));
        $importador->importar($this->planilha([$this->linha()]));

        $this->travel(1)->days();
        $item = DemandaItem::where('numero_rt', '326213060')->firstOrFail();
        $item->registrarPrevisao(now()->addDays(5));

        $this->assertTrue($item->fresh()->voltouAoSap());
        $this->assertFalse($item->fresh()->previsaoAnteriorAoRetorno());
    }

    public function test_marcar_ausentes_pode_ser_desligado_para_importacao_parcial(): void
    {
        $importador = app(ImportadorItensLiberados::class);

        $importador->importar($this->planilha([$this->linha(), $this->linha(['Nota' => '326340468'])]));
        $resultado = $importador->importar($this->planilha([$this->linha()]), null, false);

        $this->assertSame(0, $resultado['itens_ausentes']);
        $this->assertNull(DemandaItem::where('numero_rt', '326340468')->firstOrFail()->ausente_no_sap_em);
    }

    /**
     * O export padrão do SAP reserva as quatro primeiras linhas para a data e o
     * título do relatório — o cabeçalho começa na quinta.
     */
    public function test_le_o_export_do_sap_com_linhas_de_topo(): void
    {
        $vazia = array_fill(0, count(self::CABECALHO), '');
        $dataRelatorio = array_merge(['01.08.2026'], array_slice($vazia, 1));

        $resultado = app(ImportadorItensLiberados::class)->importar($this->planilha(
            [$this->linha()],
            null,
            [$vazia, $dataRelatorio, $vazia, $vazia],
        ));

        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertSame('326213060', DemandaItem::firstOrFail()->numero_rt);
    }

    /**
     * Os nomes vêm truncados pela largura da coluna no ALV do SAP.
     */
    public function test_aceita_o_cabecalho_truncado_do_sap(): void
    {
        $cabecalho = [
            'Nota', 'Item', 'Subitem', 'Data de cr', 'HoraCr.',
            'Data Liber', 'Hora Liber', 'Data+Tarde', 'Hr+Tarde',
            'Origem', 'LocRetir', 'Destino', 'Descrição carga',
            'Peso Total', 'Compriment', 'Largura RT', 'Altura RT(',
            'DocUnitSup', 'Grupo plan', 'Status do',
        ];

        $resultado = app(ImportadorItensLiberados::class)->importar(
            $this->planilha([$this->linha()], $cabecalho)
        );

        $this->assertSame(1, $resultado['itens_criados']);

        $item = DemandaItem::firstOrFail();
        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame('SKID P/PROTEÇÃO', $item->descricao_item);
        $this->assertSame(3.6, (float) $item->altura);
    }

    /**
     * As medidas da embalagem superior vêm do SAP como "Comprimento EmbSup(m)".
     */
    public function test_le_as_medidas_da_embalagem_pelo_nome_do_sap(): void
    {
        $cabecalho = [
            'Nota', 'Item', 'Subitem', 'Documento Unitização',
            'Comprimento EmbSup(m)', 'Largura EmbSup(m)', 'Altura EmbSup(m)',
        ];

        app(ImportadorItensLiberados::class)->importar($this->planilha(
            [['326213060', '5', '2', '4810768', '3,0000', '2,4000', '2,4000']],
            $cabecalho
        ));

        $item = DemandaItem::firstOrFail();

        $this->assertSame(3.0, (float) $item->comprimento_embalagem);
        $this->assertSame(2.4, (float) $item->largura_embalagem);
        $this->assertSame(2.4, (float) $item->altura_embalagem);
        $this->assertSame(7.2, (float) $item->area_embalagem);
        $this->assertSame(7.2, $item->areaEfetiva());
    }

    /**
     * A embalagem superior nem sempre é um contentor: pode ser uma caixa de
     * madeira ou um pallet que junta duas RTs. O que agrupa é o documento de
     * unitização, com ou sem contentor.
     */
    public function test_embalagem_sem_contentor_agrupa_do_mesmo_jeito(): void
    {
        $cabecalho = ['Nota', 'Item', 'Subitem', 'Documento Unitização', 'Comprimento EmbSup(m)', 'Largura EmbSup(m)'];

        app(ImportadorItensLiberados::class)->importar($this->planilha([
            ['326213060', '1', '1', '4810768', '1,2000', '1,0000'],
            ['326999888', '1', '1', '4810768', '1,2000', '1,0000'],
        ], $cabecalho));

        $itens = DemandaItem::all();

        $this->assertCount(2, $itens);
        foreach ($itens as $item) {
            $this->assertNull($item->numero_contentor);
            $this->assertSame('4810768', $item->embalagemSuperior());
            $this->assertTrue($item->dentroDeEmbalagem());
        }

        // Duas RTs na mesma embalagem ocupam o espaço dela uma vez só.
        $this->assertSame(1.2, ContentorSap::areaDePiso($itens));
    }

    public function test_linha_sem_rt_valida_e_ignorada(): void
    {
        $resultado = app(ImportadorItensLiberados::class)->importar($this->planilha([
            $this->linha(['Nota' => '']),
            $this->linha(['Nota' => 'ABC']),
            $this->linha(),
        ]));

        $this->assertSame(2, $resultado['linhas_ignoradas']);
        $this->assertSame(1, $resultado['itens_criados']);
    }

    public function test_status_desconhecido_vira_aviso_sem_derrubar_a_importacao(): void
    {
        $resultado = app(ImportadorItensLiberados::class)->importar($this->planilha([
            $this->linha(['Status' => '99']),
        ]));

        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertCount(1, $resultado['avisos']);
        $this->assertStringContainsString('99', $resultado['avisos'][0]);
        $this->assertNull(DemandaItem::firstOrFail()->status_sap);
    }

    public function test_planilha_sem_dados_devolve_erro(): void
    {
        $resultado = app(ImportadorItensLiberados::class)->importar($this->planilha([]));

        $this->assertSame(['Nenhuma linha de dados encontrada na planilha.'], $resultado['erros']);
        $this->assertSame(0, DemandaItem::count());
    }

    /**
     * Ciclo completo: o item entra liberado (03), sem demanda, e depois aparece
     * no export de viagem já programado (04). Como a chave RT + item + subitem
     * é única no SAP, é o mesmo item ganhando atendimento — a demanda o adota
     * em vez de duplicá-lo, preservando a previsão prometida ao cliente.
     */
    public function test_item_liberado_e_adotado_pela_demanda_quando_programado(): void
    {
        app(ImportadorItensLiberados::class)->importar($this->planilha([$this->linha()]));

        $item = DemandaItem::firstOrFail();
        $item->registrarPrevisao(now()->addDays(2)->startOfSecond());
        $previsao = $item->fresh()->data_hora_previsao_entrega;

        $resultado = app(ImportadorDemandas::class)->importarLinhas([
            1 => [
                'nota' => '509538496',
                'numero_rt' => '326213060',
                'numero_item' => '5',
                'subitem' => '2',
                'status_item' => '04',
                'local_destino' => 'ARM-MACAE',
            ],
        ]);

        $this->assertSame(1, $resultado['itens_adotados']);
        $this->assertSame(0, $resultado['itens_criados']);
        $this->assertSame(1, DemandaItem::count());

        $item->refresh();
        $this->assertNotNull($item->demanda_id);
        $this->assertSame(509538496, (int) $item->demanda->numero_demanda);
        $this->assertSame(StatusSap::Programado, $item->status_sap);

        // O que a operação já havia prometido e apurado da RT sobrevive.
        $this->assertTrue($item->data_hora_previsao_entrega->equalTo($previsao));
        $this->assertCount(1, $item->previsoes);
        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame('T44', $item->grupo_planejamento);
        $this->assertNotNull($item->data_hora_liberacao_rt);
    }

    /**
     * O item pode nascer direto como programado, sem ter passado pela
     * importação de liberados — o export de viagem também traz os dados da RT.
     */
    public function test_importador_de_demandas_grava_os_dados_da_rt(): void
    {
        app(ImportadorDemandas::class)->importarLinhas([
            1 => [
                'nota' => '509538496',
                'numero_rt' => '326741968',
                'numero_item' => '1',
                'subitem' => '5',
                'status_item' => '04',
                'criacao_rt_data' => '20.07.2026',
                'criacao_rt_hora' => '07:15:00',
                'liberacao_data' => '21.07.2026',
                'liberacao_hora' => '09:30:00',
                'doc_unitizacao_superior' => '4803478',
                'grupo_planejamento' => 'T44',
            ],
        ]);

        $item = DemandaItem::firstOrFail();

        $this->assertSame('20/07/2026 07:15:00', $item->data_hora_criacao_rt->format('d/m/Y H:i:s'));
        $this->assertSame('21/07/2026 09:30:00', $item->data_hora_liberacao_rt->format('d/m/Y H:i:s'));
        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame('T44', $item->grupo_planejamento);
        $this->assertNotNull($item->demanda_id);
    }

    /**
     * Coluna ausente no export de viagem não apaga o que veio do status 03.
     */
    public function test_importador_de_demandas_preserva_dados_da_rt_ja_conhecidos(): void
    {
        app(ImportadorItensLiberados::class)->importar($this->planilha([$this->linha()]));

        app(ImportadorDemandas::class)->importarLinhas([
            1 => [
                'nota' => '509538496',
                'numero_rt' => '326213060',
                'numero_item' => '5',
                'subitem' => '2',
                'status_item' => '04',
            ],
        ]);

        $item = DemandaItem::firstOrFail();

        $this->assertSame('4803478', $item->doc_unitizacao_superior);
        $this->assertSame('T44', $item->grupo_planejamento);
        $this->assertSame('03/07/2026 13:56:46', $item->data_hora_liberacao_rt->format('d/m/Y H:i:s'));
    }

    /**
     * A correção de rota feita pela equipe não pode ser desfeita pelo SAP.
     *
     * O ciclo real: o item chega com o local errado, a operação corrige pela
     * tela, e no dia seguinte a planilha volta com o valor antigo.
     */
    public function test_rota_corrigida_pela_equipe_sobrevive_a_reimportacao(): void
    {
        $importador = app(ImportadorItensLiberados::class);
        $importador->importar($this->planilha([$this->linha()]));

        // O que a tela de itens faz ao corrigir a rota.
        $item = DemandaItem::firstOrFail();
        $item->update([
            'local_origem' => 'BASE VITORIA CORRIGIDA',
            'local_destino' => 'ARM-MACAE PATIO 2',
            'campos_editados' => ['local_origem', 'local_destino'],
        ]);

        // O SAP volta a mandar os valores originais, várias vezes.
        foreach (range(1, 3) as $reimportacao) {
            $importador->importar($this->planilha([
                $this->linha(['Origem' => 'BASE VITORIA', 'Destino' => 'ARM-MACAE']),
            ]));
        }

        $item->refresh();
        $this->assertSame('BASE VITORIA CORRIGIDA', $item->local_origem);
        $this->assertSame('ARM-MACAE PATIO 2', $item->local_destino);
        // A forma canônica acompanha a correção, então o agrupamento por rota
        // também respeita o que a equipe definiu.
        $this->assertSame('BASE VITORIA CORRIGIDA', $item->local_origem_norm);
        $this->assertSame('ARM MACAE PATIO 2', $item->local_destino_norm);
    }

    /**
     * O mesmo vale quando o item entra pela importação de demandas.
     */
    public function test_rota_corrigida_sobrevive_ao_import_de_demandas(): void
    {
        app(ImportadorItensLiberados::class)->importar($this->planilha([$this->linha()]));

        DemandaItem::firstOrFail()->update([
            'local_destino' => 'ARM-MACAE PATIO 2',
            'campos_editados' => ['local_destino'],
        ]);

        app(ImportadorDemandas::class)->importarLinhas([
            1 => [
                'nota' => '509538496',
                'numero_rt' => '326213060',
                'numero_item' => '5',
                'subitem' => '2',
                'status_item' => '04',
                'local_origem' => 'BASE VITORIA',
                'local_destino' => 'ARM-MACAE',
            ],
        ]);

        $item = DemandaItem::firstOrFail();
        $this->assertSame('ARM-MACAE PATIO 2', $item->local_destino);
        $this->assertSame('ARM MACAE PATIO 2', $item->local_destino_norm);
        // A origem não foi assumida pela equipe, então o SAP segue mandando nela.
        $this->assertSame('BASE VITORIA', $item->local_origem);
    }

    public function test_modelo_de_importacao_traz_o_cabecalho_esperado(): void
    {
        $caminho = app(ImportadorItensLiberados::class)->gerarModelo();

        $this->assertFileExists($caminho);

        $resultado = app(ImportadorItensLiberados::class)->importar($caminho);

        // As duas linhas de exemplo do modelo são importáveis.
        $this->assertSame(2, $resultado['itens_criados']);
    }

    public function test_le_o_export_bruto_do_sap_com_cabecalho_truncado(): void
    {
        // Rótulos como o SAP entrega: cortados pela largura da coluna, com
        // "Compriment" repetido para a RT e para a embalagem.
        $cabecalho = [
            'Nota', 'Item', 'Subitem', 'Data de cr', 'HoraCr.', 'Data Liber', 'Hora Liber',
            'Data+Tarde', 'Hr+Tarde', 'Origem', 'LocRetir', 'Destino', 'Descrição carga',
            'Peso Total', 'Compriment', 'Largura RT', 'Altura RT(', 'DocUnitSup',
            'Descrição Contentor', 'Compriment', 'Largura Em', 'Altura Emb',
            'Grupo plan', 'Status do',
        ];

        $caminho = $this->planilha(
            [[
                '326213060', '5', '2', '03.07.2026', '13:56:13', '03.07.2026', '13:56:46',
                '10.07.2026', '00:00:00', 'BASE VITORIA', 'AL-06 B06R13Q01A', 'ARM-MACAE',
                'SKID P/PROTEÇÃO', '2.408,000', '3,1000', '3,3000', '3,6000', '4810768',
                'CISA042074 Caixa 1M', '1,1000', '1,2000', '1,3000', 'T44', '03',
            ]],
            $cabecalho,
            // O export reserva as quatro primeiras linhas para data e título.
            [[], ['17.07.2026'], [], []],
        );

        $resultado = app(ImportadorItensLiberados::class)->importar($caminho);

        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertSame(0, $resultado['linhas_ignoradas']);

        $item = DemandaItem::firstOrFail();
        $this->assertSame('AL-06 B06R13Q01A', $item->descricao_local_retirada);
        $this->assertSame('4810768', $item->doc_unitizacao_superior);
        // Os dois "Compriment" são desempatados pela ordem em que aparecem.
        $this->assertSame(3.1, (float) $item->comprimento);
        $this->assertSame(1.1, (float) $item->comprimento_embalagem);
        $this->assertSame(1.2, (float) $item->largura_embalagem);
    }

    public function test_importador_de_demandas_le_o_export_bruto_do_sap(): void
    {
        $cabecalho = [
            'Data', 'Hora', 'Dt entregu', 'Hr entregu', 'Data + tar', 'Hora + tar',
            'Nota', 'Nº da RT', 'Item da RT', 'Subitem da', 'Descrição equipamento',
            'Origem', 'Local retirada', 'Destino', 'Status do', 'Peso total',
            'Altura RT(', 'Largura RT', 'Compriment', 'Descrição da carga',
        ];

        $caminho = $this->planilha(
            [[
                '17.07.2026', '08:21:48', '19.07.2026', '00:00:01', '11.07.2026', '00:00:00',
                '509538496', '326741968', '1', '5', 'VIX 1993 - AXOR 1933 S 2P T44',
                'PACU', 'PACU-SAIDA', 'ARM-MACAE', '07', '3.250,000',
                '1,3100', '1,2000', '14,0000', 'CISA4580034',
            ]],
            $cabecalho,
            [[], ['17.07.2026'], [], []],
        );

        $resultado = app(ImportadorDemandas::class)->importar($caminho);

        $this->assertSame(0, $resultado['linhas_ignoradas']);
        $this->assertSame(1, $resultado['itens_criados']);

        $item = DemandaItem::firstOrFail();
        $this->assertSame('326741968', $item->numero_rt);
        $this->assertSame(14.0, (float) $item->comprimento);
        // O layout de viagem não traz embalagem: a coluna da RT não é reaproveitada.
        $this->assertNull($item->comprimento_embalagem);
    }
}
