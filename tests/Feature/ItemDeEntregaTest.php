<?php

namespace Tests\Feature;

use App\Enums\OrigemPrevisao;
use App\Enums\StatusSap;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1: o item de entrega passa a existir antes da demanda, guarda os dados
 * da RT e mantém histórico das previsões prometidas ao cliente.
 */
class ItemDeEntregaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $extra
     */
    private function itemLiberado(array $extra = []): DemandaItem
    {
        return DemandaItem::create(array_merge([
            'numero_rt' => '326213060',
            'numero_item' => '5',
            'subitem' => '2',
            'status_sap' => StatusSap::Liberado,
            'prazo_item' => now()->addDays(3)->setTime(23, 59, 59),
            'data_hora_criacao_rt' => now()->subDays(5),
            'data_hora_liberacao_rt' => now()->subDays(4),
            'doc_unitizacao_superior' => '4803478',
            'grupo_planejamento' => 'T44',
        ], $extra));
    }

    public function test_item_pode_existir_sem_demanda(): void
    {
        $item = $this->itemLiberado();

        $this->assertNull($item->demanda_id);
        $this->assertNull($item->fresh()->demanda);
        $this->assertSame(StatusSap::Liberado, $item->fresh()->status_sap);
    }

    public function test_item_sem_demanda_e_adotado_quando_e_programado(): void
    {
        $item = $this->itemLiberado();
        $demanda = Demanda::factory()->create();

        $item->update([
            'demanda_id' => $demanda->id,
            'status_sap' => StatusSap::Programado,
        ]);

        $this->assertTrue($item->fresh()->demanda->is($demanda));
    }

    public function test_registrar_previsao_guarda_historico_e_data_vigente(): void
    {
        $item = $this->itemLiberado();
        $usuario = User::factory()->create();
        $primeira = now()->addDay()->startOfSecond();
        $segunda = now()->addDays(2)->startOfSecond();

        $item->registrarPrevisao($primeira, OrigemPrevisao::Manual, $usuario->id);
        $item->registrarPrevisao($segunda, OrigemPrevisao::Lote, $usuario->id, 'Reprogramado');

        $item->refresh();

        $this->assertCount(2, $item->previsoes);
        $this->assertTrue($item->data_hora_previsao_entrega->equalTo($segunda));
        $this->assertTrue($item->previsaoAtual->data_hora_previsao->equalTo($segunda));
        $this->assertSame('Reprogramado', $item->previsaoAtual->motivo);
        $this->assertSame(OrigemPrevisao::Lote, $item->previsaoAtual->origem);
    }

    public function test_previsao_repetida_nao_gera_nova_linha(): void
    {
        $item = $this->itemLiberado();
        $previsao = now()->addDay()->startOfSecond();

        $this->assertNotNull($item->registrarPrevisao($previsao));
        $this->assertNull($item->registrarPrevisao($previsao));

        $this->assertCount(1, $item->fresh()->previsoes);
    }

    public function test_previsao_de_automacao_dispensa_usuario(): void
    {
        $item = $this->itemLiberado();

        $item->registrarPrevisao(now()->addDay(), OrigemPrevisao::Automacao);

        $this->assertNull($item->fresh()->previsaoAtual->definido_por);
        $this->assertSame('Automação', $item->fresh()->previsaoAtual->autorLabel());
    }

    public function test_semaforo_da_previsao_diante_do_prazo(): void
    {
        $item = $this->itemLiberado();
        $this->assertSame('sem_previsao', $item->situacaoPrevisao());

        $item->registrarPrevisao($item->prazo_item->copy()->subDay());
        $this->assertSame('no_prazo', $item->fresh()->situacaoPrevisao());

        $item->fresh()->registrarPrevisao($item->prazo_item->copy()->addDay());
        $this->assertSame('fora_do_prazo', $item->fresh()->situacaoPrevisao());

        $semPrazo = $this->itemLiberado(['numero_item' => '6', 'prazo_item' => null]);
        $this->assertSame('sem_prazo', $semPrazo->situacaoPrevisao());
    }

    public function test_marcar_fora_do_escopo_exige_registro_de_quem_e_quando(): void
    {
        $item = $this->itemLiberado();
        $usuario = User::factory()->create();

        $item->marcarForaDoEscopo('Transporte próprio do cliente', $usuario->id);
        $item->refresh();

        $this->assertTrue($item->fora_escopo);
        $this->assertSame('Transporte próprio do cliente', $item->fora_escopo_justificativa);
        $this->assertTrue($item->marcadoForaDoEscopoPor->is($usuario));
        $this->assertNotNull($item->fora_escopo_em);
        $this->assertFalse($item->emCobranca());

        $item->reverterForaDoEscopo();
        $item->refresh();

        $this->assertFalse($item->fora_escopo);
        $this->assertNull($item->fora_escopo_justificativa);
        $this->assertNull($item->marcadoForaDoEscopoPor);
        $this->assertTrue($item->emCobranca());
    }

    /**
     * O cliente cobra enquanto o item não foi atendido, cancelado ou suspenso.
     */
    public function test_cobranca_do_cliente_segue_o_status_do_sap(): void
    {
        $emCobranca = [StatusSap::Liberado, StatusSap::Programado];
        $encerrados = [StatusSap::Atendido, StatusSap::Cancelado];
        $suspensos = [StatusSap::SuspensoInterno, StatusSap::SuspensoExterno];

        foreach ($emCobranca as $status) {
            $this->assertTrue($status->emCobranca(), $status->label());
        }

        foreach ([...$encerrados, ...$suspensos] as $status) {
            $this->assertFalse($status->emCobranca(), $status->label());
        }

        foreach ($encerrados as $status) {
            $this->assertTrue($status->encerrado());
        }

        foreach ($suspensos as $status) {
            $this->assertTrue($status->suspenso());
            $this->assertFalse($status->encerrado());
        }
    }

    public function test_apenas_a_suspensao_18_e_responsabilidade_do_cliente(): void
    {
        $this->assertTrue(StatusSap::SuspensoExterno->responsabilidadeCliente());
        $this->assertFalse(StatusSap::SuspensoInterno->responsabilidadeCliente());
    }

    public function test_traducao_do_codigo_bruto_do_sap(): void
    {
        $this->assertSame(StatusSap::Liberado, StatusSap::fromCodigo('03'));
        $this->assertSame(StatusSap::Programado, StatusSap::fromCodigo('4'));
        $this->assertSame(StatusSap::SuspensoExterno, StatusSap::fromCodigo(18));

        $this->assertNull(StatusSap::fromCodigo(null));
        $this->assertNull(StatusSap::fromCodigo(''));
        // Código fora do ciclo de vida conhecido não pode derrubar a importação.
        $this->assertNull(StatusSap::fromCodigo('99'));
    }
}
