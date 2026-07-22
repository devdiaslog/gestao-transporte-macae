<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Models\Alerta;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Services\DemandaCalculadora;
use App\Services\ImportadorDemandas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportadorDemandasTest extends TestCase
{
    use RefreshDatabase;

    private function planilha(): string
    {
        return base_path('tests/Fixtures/export_sap_amostra.xlsx');
    }

    private function importar(): array
    {
        return app(ImportadorDemandas::class)->importar($this->planilha());
    }

    public function test_agrupa_itens_na_mesma_demanda_pelo_numero_da_nota(): void
    {
        $resultado = $this->importar();

        $this->assertSame(2, $resultado['demandas_criadas']);
        $this->assertSame(3, $resultado['itens_criados']);
        $this->assertSame([], $resultado['erros']);

        $this->assertSame(2, Demanda::count());
        $this->assertSame(2, Demanda::where('numero_demanda', 509111111)->first()->itens()->count());
    }

    public function test_reimportar_atualiza_os_itens_sem_duplicar(): void
    {
        $this->importar();
        $segunda = $this->importar();

        $this->assertSame(0, $segunda['demandas_criadas']);
        $this->assertSame(0, $segunda['itens_criados']);
        $this->assertSame(3, $segunda['itens_atualizados']);

        $this->assertSame(2, Demanda::count());
        $this->assertSame(3, DemandaItem::count());
    }

    public function test_grava_os_campos_do_item_vindos_da_planilha(): void
    {
        $this->importar();

        $item = DemandaItem::where('numero_rt', '326000001')->firstOrFail();

        $this->assertSame('PACU', $item->local_origem);
        $this->assertSame('ARM-MACAE', $item->local_destino);
        $this->assertSame('PACU-CAIS 2', $item->descricao_local_retirada);
        $this->assertSame('Carga A', $item->descricao_item);
        $this->assertSame(StatusItemDemanda::Pendente, $item->status_item);
        $this->assertSame('24/07/2026 10:00', $item->prazo_item->format('d/m/Y H:i'));
    }

    public function test_deriva_fonte_tipo_e_status_da_demanda_importada(): void
    {
        $this->importar();

        // Origem PACU (ponto-chave) e destinos comuns → Backload.
        $backload = Demanda::where('numero_demanda', 509111111)->firstOrFail();
        $this->assertSame(FonteDemanda::SapLt, $backload->fonte_demanda);
        $this->assertSame(TipoDemanda::Backload, $backload->tipo_demanda);
        $this->assertSame(StatusDemanda::Pendente, $backload->status_demanda);

        // Destino BMAC (ponto-chave) e item entregue → Load e finalizado.
        $load = Demanda::where('numero_demanda', 509222222)->firstOrFail();
        $this->assertSame(TipoDemanda::Load, $load->tipo_demanda);
        $this->assertSame(StatusDemanda::Finalizado, $load->status_demanda);
    }

    public function test_modelo_gerado_e_compativel_com_o_proprio_importador(): void
    {
        $importador = app(ImportadorDemandas::class);

        $caminho = $importador->gerarModelo();
        $this->assertFileExists($caminho);

        $resultado = $importador->importar($caminho);

        $this->assertSame([], $resultado['erros']);
        $this->assertGreaterThan(0, $resultado['demandas_criadas']);
        $this->assertGreaterThan(0, $resultado['itens_criados']);

        @unlink($caminho);
    }

    public function test_reimportacao_preserva_status_e_entrega_do_operador_e_sincroniza_mestres(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Destino', 'Descrição Demanda Entrega', 'Status Demanda Entrega', 'Data Entrega', 'Hora Entrega'];

        // 1º import: cria o item com dados do SAP.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509500001', '326000030', '1', 'ARM-MACAE', 'Carga original', '04', '', ''],
        ]));

        // Operador da torre assume pela interface: muda o status e a entrega
        // (a UI marca os campos em campos_editados).
        $item = DemandaItem::where('numero_rt', '326000030')->firstOrFail();
        $entregaOperador = now()->subDay()->startOfMinute();
        $item->update([
            'status_item' => StatusItemDemanda::Recusado,
            'data_hora_entrega' => $entregaOperador,
            'campos_editados' => ['status_item', 'data_hora_entrega'],
        ]);

        // 2º import: SAP manda status e entrega diferentes, e destino/descrição novos.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509500001', '326000030', '1', 'SEROPEDICA', 'Carga atualizada', '07', '20.07.2026', '08:00:00'],
        ]));

        $item->refresh();

        // Gestão do operador preservada.
        $this->assertSame(StatusItemDemanda::Recusado, $item->status_item);
        $this->assertTrue($entregaOperador->equalTo($item->data_hora_entrega));

        // Campos mestres re-sincronizados com o SAP.
        $this->assertSame('SEROPEDICA', $item->local_destino);
        $this->assertSame('Carga atualizada', $item->descricao_item);
    }

    public function test_sap_finaliza_item_em_reimportacoes_sucessivas_quando_torre_nao_assumiu(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega', 'Data Entrega', 'Hora Entrega'];

        // 08:00 — item chega Pendente.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800040', '326000300', '1', '04', '', ''],
        ]));

        $item = DemandaItem::where('numero_rt', '326000300')->firstOrFail();
        $this->assertSame(StatusItemDemanda::Pendente, $item->status_item);

        // 09:00 — SAP já finalizou o item (entrega feita fora da torre).
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800040', '326000300', '1', '07', '22.07.2026', '08:45:00'],
        ]));

        $item->refresh();

        $this->assertSame(StatusItemDemanda::Entregue, $item->status_item);
        $this->assertSame('22/07/2026 08:45', $item->data_hora_entrega->format('d/m/Y H:i'));

        // A demanda encerra automaticamente com o fim vindo do SAP.
        $demanda = $item->demanda->refresh();
        $this->assertNotNull($demanda->data_hora_fim_demanda);
    }

    public function test_finalizacao_via_sap_cria_alerta_e_marca_inicio_e_fim_como_automaticos(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega', 'Data Entrega', 'Hora Entrega'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800060', '326000500', '1', '07', '22.07.2026', '00:00:00'],
        ]));

        $demanda = Demanda::where('numero_demanda', 509800060)->firstOrFail();

        // Início e fim automáticos: demanda entra na tag/filtro de ajuste.
        $this->assertTrue($demanda->inicio_automatico);
        $this->assertTrue($demanda->fim_automatico);

        // Alerta padrão criado, visível a todos, identificando a origem SAP.
        $alerta = Alerta::where('condicao', 'demanda_finalizada_sap')->first();
        $this->assertNotNull($alerta);
        $this->assertTrue($alerta->para_todos);
        $this->assertStringContainsString('509800060', $alerta->lembrete);

        // Reimportar não duplica o alerta (status já era Finalizado).
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800060', '326000500', '1', '07', '22.07.2026', '00:00:00'],
        ]));
        $this->assertSame(1, Alerta::where('condicao', 'demanda_finalizada_sap')->count());
    }

    public function test_status_vazio_ou_desconhecido_do_sap_nao_apaga_o_status_atual(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800041', '326000310', '1', '07'],
        ]));

        // Reimporta com status vazio e depois com código desconhecido.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800041', '326000310', '1', ''],
        ]));
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800041', '326000310', '1', '99'],
        ]));

        $this->assertSame(
            StatusItemDemanda::Entregue,
            DemandaItem::where('numero_rt', '326000310')->first()->status_item,
        );
    }

    public function test_reimportacao_inclui_itens_novos_que_surgiram_no_sap(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Destino', 'Status Demanda Entrega'];

        // 09:00 — primeira importação com 1 item.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800001', '326000060', '1', 'ARM-MACAE', '04'],
        ]));

        $demanda = Demanda::where('numero_demanda', 509800001)->firstOrFail();
        $this->assertSame(1, $demanda->itens()->count());

        // Operador assume o item existente nesse meio tempo (via interface).
        $demanda->itens()->first()->update([
            'status_item' => StatusItemDemanda::Entregue,
            'campos_editados' => ['status_item'],
        ]);

        // 10:00 — SAP ganhou mais 2 itens; reimporta com os 3.
        $resultado = $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800001', '326000060', '1', 'ARM-MACAE', '04'],
            ['509800001', '326000061', '1', 'SEROPEDICA', '04'],
            ['509800001', '326000062', '1', 'CENPES', '04'],
        ]));

        $this->assertSame(2, $resultado['itens_criados']);
        $this->assertSame(1, $resultado['itens_atualizados']);
        $this->assertSame(3, $demanda->itens()->count());

        // O status que o operador definiu no item antigo permanece.
        $this->assertSame(
            StatusItemDemanda::Entregue,
            $demanda->itens()->where('numero_rt', '326000060')->first()->status_item
        );
    }

    public function test_importacao_parcial_sem_colunas_mestres_nao_apaga_dados(): void
    {
        $importador = app(ImportadorDemandas::class);

        // Import completo: item com origem/destino/descrição/prazo.
        $importador->importar($this->planilhaComCabecalho(
            ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Origem', 'Descrição Destino', 'Descrição Demanda Entrega', 'Data Prazo', 'Hora Prazo'],
            ['509800002', '326000070', '1', 'PACU', 'ARM-MACAE', 'Carga X', '25.07.2026', '10:00:00']
        ));

        // Reimport PARCIAL: planilha só com número/RT/item (sem colunas mestres).
        $importador->importar($this->planilhaComCabecalho(
            ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega'],
            ['509800002', '326000070', '1']
        ));

        $item = DemandaItem::where('numero_rt', '326000070')->firstOrFail();

        // Colunas ausentes = campos intocados.
        $this->assertSame('PACU', $item->local_origem);
        $this->assertSame('ARM-MACAE', $item->local_destino);
        $this->assertSame('Carga X', $item->descricao_item);
        $this->assertSame('25/07/2026 10:00', $item->prazo_item->format('d/m/Y H:i'));
    }

    public function test_reimportacao_nao_sobrescreve_campo_mestre_editado_pelo_operador(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Origem', 'Descrição Destino'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800020', '326000100', '1', 'GENERICO SAP', 'DESTINO SAP'],
        ]));

        // Operador renomeia a origem para um termo amigável.
        $item = DemandaItem::where('numero_rt', '326000100')->firstOrFail();
        $item->update(['local_origem' => 'Empresa X - Bairro Y', 'campos_editados' => ['local_origem']]);

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800020', '326000100', '1', 'GENERICO SAP', 'DESTINO NOVO'],
        ]));

        $item->refresh();

        // Origem do operador preservada; destino (não editado) re-sincroniza.
        $this->assertSame('Empresa X - Bairro Y', $item->local_origem);
        $this->assertSame('DESTINO NOVO', $item->local_destino);
    }

    public function test_observacao_acumula_no_item_sem_duplicar_em_reimportacoes(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Observação'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800050', '326000400', '1', 'Insight A'],
        ]));

        // Reimporta com o mesmo texto (não duplica) e depois com texto novo (acrescenta).
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800050', '326000400', '1', 'Insight A'],
        ]));
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800050', '326000400', '1', 'Insight B'],
        ]));

        $this->assertSame(
            "Insight A\n\nInsight B",
            DemandaItem::where('numero_rt', '326000400')->first()->observacao,
        );
    }

    public function test_rt_remanejada_para_outra_demanda_cancela_o_item_pendente_na_antiga(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Destino', 'Status Demanda Entrega'];

        // 08:00 — RT entra Pendente na demanda antiga.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800010', '326000080', '1', 'ARM-MACAE', '04'],
        ]));

        // Importação seguinte — SAP remanejou a RT para outra viagem.
        $resultado = $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800011', '326000080', '1', 'ARM-MACAE', '04'],
        ]));

        $this->assertSame(1, $resultado['itens_remanejados']);
        $this->assertNotEmpty($resultado['avisos']);

        $antiga = Demanda::where('numero_demanda', 509800010)->firstOrFail();
        $nova = Demanda::where('numero_demanda', 509800011)->firstOrFail();

        // Cancelado na antiga (histórico preservado), criado Pendente na nova.
        $this->assertSame(StatusItemDemanda::Cancelado, $antiga->itens()->first()->status_item);
        $this->assertSame(StatusItemDemanda::Pendente, $nova->itens()->first()->status_item);

        // A antiga recalcula e encerra sozinha (único item cancelado → Cancelada).
        $this->assertSame(StatusDemanda::Cancelada, $antiga->status_demanda);
    }

    public function test_rt_remanejada_nao_altera_item_ja_encerrado_pelo_operador(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800012', '326000090', '1', '04'],
        ]));

        $antiga = Demanda::where('numero_demanda', 509800012)->firstOrFail();
        $antiga->itens()->first()->update(['status_item' => StatusItemDemanda::Entregue]);

        $resultado = $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509800013', '326000090', '1', '04'],
        ]));

        $this->assertSame(0, $resultado['itens_remanejados']);
        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertSame(StatusItemDemanda::Entregue, $antiga->itens()->first()->status_item);
    }

    public function test_tipo_informado_na_planilha_fixa_o_tipo_manualmente(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Tipo Demanda', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Origem', 'Descrição Destino', 'Status Demanda Entrega'];

        // Origem PACU derivaria Backload, mas o usuário informou Load na planilha.
        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509700001', 'Load', '326000050', '1', 'PACU', 'ARM-MACAE', '04'],
        ]));

        $demanda = Demanda::where('numero_demanda', 509700001)->firstOrFail();
        $this->assertSame(TipoDemanda::Load, $demanda->tipo_demanda);
        $this->assertTrue($demanda->tipo_demanda_manual);

        // O recálculo (ex.: edição de item) respeita o tipo fixado.
        app(DemandaCalculadora::class)->recalcular($demanda->load('itens'));
        $this->assertSame(TipoDemanda::Load, $demanda->fresh()->tipo_demanda);
    }

    public function test_tipo_vazio_na_planilha_mantem_classificacao_automatica(): void
    {
        $importador = app(ImportadorDemandas::class);
        $cabecalho = ['Numero Demanda Viagem', 'Tipo Demanda', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Origem', 'Descrição Destino', 'Status Demanda Entrega'];

        $importador->importar($this->planilhaComCabecalho($cabecalho, null, [
            ['509700002', '', '326000051', '1', 'PACU', 'ARM-MACAE', '04'],
        ]));

        $demanda = Demanda::where('numero_demanda', 509700002)->firstOrFail();
        $this->assertSame(TipoDemanda::Backload, $demanda->tipo_demanda);
        $this->assertFalse($demanda->tipo_demanda_manual);
    }

    public function test_importacao_escopada_processa_apenas_a_nota_informada(): void
    {
        $importador = app(ImportadorDemandas::class);

        $planilha = $this->planilhaComCabecalho(
            ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Descrição Destino', 'Status Demanda Entrega'],
            null,
            [
                ['509600001', '326000040', '1', 'ARM-MACAE', '04'],
                ['509600002', '326000041', '1', 'BMAC', '04'], // outra Nota — deve ser ignorada
            ]
        );

        $resultado = $importador->importar($planilha, null, 509600001);

        $this->assertSame(1, $resultado['itens_criados']);
        $this->assertSame(1, Demanda::where('numero_demanda', 509600001)->count());
        $this->assertSame(0, Demanda::where('numero_demanda', 509600002)->count());

        @unlink($planilha);
    }

    public function test_importa_a_data_hora_de_entrega_do_item(): void
    {
        $importador = app(ImportadorDemandas::class);

        $planilha = $this->planilhaComCabecalho(
            ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega', 'Data Entrega', 'Hora Entrega'],
            null,
            [
                ['509400001', '326000020', '1', '07', '16.07.2026', '04:08:37'],
                ['509400001', '326000021', '2', '04', '', ''],
            ]
        );

        $importador->importar($planilha);

        $entregue = DemandaItem::where('numero_rt', '326000020')->firstOrFail();
        $this->assertSame('16/07/2026 04:08', $entregue->data_hora_entrega->format('d/m/Y H:i'));

        $pendente = DemandaItem::where('numero_rt', '326000021')->firstOrFail();
        $this->assertNull($pendente->data_hora_entrega);

        @unlink($planilha);
    }

    public function test_traduz_os_codigos_de_status_do_sap(): void
    {
        $importador = app(ImportadorDemandas::class);

        $planilha = $this->planilhaComCabecalho(
            ['Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega', 'Status Demanda Entrega'],
            null,
            [
                ['509300001', '326000010', '1', '04'],
                ['509300001', '326000011', '2', '07'],
                ['509300001', '326000012', '3', '09'],
                ['509300001', '326000013', '4', '13'],
                ['509300001', '326000014', '5', '18'],
            ]
        );

        $importador->importar($planilha);

        $esperado = [
            '326000010' => StatusItemDemanda::Pendente,
            '326000011' => StatusItemDemanda::Entregue,
            '326000012' => StatusItemDemanda::Cancelado,
            '326000013' => StatusItemDemanda::Suspenso,
            '326000014' => StatusItemDemanda::Suspenso,
        ];

        foreach ($esperado as $rt => $status) {
            $this->assertSame($status, DemandaItem::where('numero_rt', $rt)->first()->status_item, "RT {$rt}");
        }

        @unlink($planilha);
    }

    public function test_reconhece_cabecalho_generico_e_alias_do_sap(): void
    {
        $importador = app(ImportadorDemandas::class);

        // Cabeçalho genérico (novo padrão do modelo).
        $generico = $this->planilhaComCabecalho([
            'Numero Demanda Viagem', 'Numero Demanda Entrega', 'Item Demanda Entrega',
            'Descrição Origem', 'Descrição Destino', 'Status Demanda Entrega',
        ], ['509111111', '326000001', '1', 'PACU', 'ARM-MACAE', '04']);

        // Mesmos dados com os rótulos antigos do SAP (compatibilidade).
        $sap = $this->planilhaComCabecalho([
            'Nota', 'Nº da RT', 'Item da RT', 'Origem', 'Destino', 'Status do',
        ], ['509222222', '326000002', '1', 'PACU', 'ARM-MACAE', '04']);

        $rGenerico = $importador->importar($generico);
        $rSap = $importador->importar($sap);

        $this->assertSame([], $rGenerico['erros']);
        $this->assertSame([], $rSap['erros']);
        $this->assertSame(1, $rGenerico['itens_criados']);
        $this->assertSame(1, $rSap['itens_criados']);
        $this->assertSame('PACU', DemandaItem::where('numero_rt', '326000001')->first()->local_origem);
        $this->assertSame('PACU', DemandaItem::where('numero_rt', '326000002')->first()->local_origem);

        @unlink($generico);
        @unlink($sap);
    }

    /**
     * @param  array<int, string>  $cabecalho
     * @param  array<int, string>|null  $linha  Linha única (atalho)
     * @param  array<int, array<int, string>>  $linhas  Várias linhas
     */
    private function planilhaComCabecalho(array $cabecalho, ?array $linha = null, array $linhas = []): string
    {
        $caminho = tempnam(sys_get_temp_dir(), 'planilha_').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($caminho);
        $writer->addRow(Row::fromValues($cabecalho));

        foreach ($linha !== null ? [$linha] : $linhas as $l) {
            $writer->addRow(Row::fromValues($l));
        }

        $writer->close();

        return $caminho;
    }

    public function test_prazo_da_demanda_usa_o_menor_item_ainda_exequivel(): void
    {
        $this->travelTo(now()->setDate(2026, 7, 21)->setTime(8, 0));

        $this->importar();

        // Itens em 24/07 e 25/07: o menor alcançável é 24/07.
        $demanda = Demanda::where('numero_demanda', 509111111)->firstOrFail();

        $this->assertSame('24/07/2026 10:00', $demanda->prazo_demanda->format('d/m/Y H:i'));
    }
}
