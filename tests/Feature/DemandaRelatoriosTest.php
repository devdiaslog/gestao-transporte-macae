<?php

namespace Tests\Feature;

use App\Enums\StatusItemDemanda;
use App\Models\Demanda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaRelatoriosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cenário comum: uma demanda com itens em situações distintas.
     */
    private function cenario(): Demanda
    {
        // Relógio fixo às 8h: o item "vence hoje" (18h) fica no futuro.
        $this->travelTo(now()->startOfDay()->addHours(8));

        $demanda = Demanda::factory()->create(['numero_demanda' => 509600001]);

        // Vence hoje, ainda pendente.
        $demanda->itens()->create([
            'numero_rt' => '326900001', 'numero_item' => '1',
            'local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE',
            'prazo_item' => now()->setTime(18, 0),
            'status_item' => StatusItemDemanda::Pendente, 'status_sap' => '04',
        ]);

        // Vencido, ainda pendente.
        $demanda->itens()->create([
            'numero_rt' => '326900002', 'numero_item' => '1',
            'local_origem' => 'PACU', 'local_destino' => 'SEROPEDICA',
            'prazo_item' => now()->subDays(2),
            'status_item' => StatusItemDemanda::Pendente, 'status_sap' => '04',
        ]);

        // Encerrado pela torre, mas o SAP segue com 04 (divergência).
        $demanda->itens()->create([
            'numero_rt' => '326900003', 'numero_item' => '1',
            'local_origem' => 'BMAC', 'local_destino' => 'CENPES',
            'prazo_item' => now()->addDays(3),
            'status_item' => StatusItemDemanda::Entregue, 'status_sap' => '04',
            'data_hora_entrega' => now()->subHour(),
        ]);

        // Entregue no SAP (07), ainda pendente aqui (divergência inversa).
        $demanda->itens()->create([
            'numero_rt' => '326900004', 'numero_item' => '1',
            'local_origem' => 'BMAC', 'local_destino' => 'ARM-RIO',
            'prazo_item' => now()->addDays(4),
            'status_item' => StatusItemDemanda::Pendente, 'status_sap' => '07',
        ]);

        return $demanda->load('itens');
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private function baixar(string $relatorio, array $filtros = []): string
    {
        $resposta = $this->actingAs(User::factory()->create())
            ->get(route('demandas.relatorio', array_merge(['relatorio' => $relatorio, 'status' => ''], $filtros)));

        $resposta->assertOk();

        return $resposta->getContent();
    }

    public function test_relatorio_de_itens_que_vencem_hoje(): void
    {
        $this->cenario();

        $csv = $this->baixar('vencem_hoje');

        $this->assertStringContainsString('326900001', $csv);
        $this->assertStringNotContainsString('326900002', $csv);
        $this->assertStringNotContainsString('326900003', $csv);
    }

    public function test_relatorio_de_itens_vencidos(): void
    {
        $this->cenario();

        $csv = $this->baixar('vencidos');

        $this->assertStringContainsString('326900002', $csv);
        $this->assertStringNotContainsString('326900001', $csv);
    }

    public function test_relatorio_de_divergencia_encerrado_aqui_e_aberto_no_sap(): void
    {
        $this->cenario();

        $csv = $this->baixar('divergencia_sap');

        // Só o item encerrado pela torre que segue 04 no SAP.
        $this->assertStringContainsString('326900003', $csv);
        $this->assertStringNotContainsString('326900001', $csv);
        $this->assertStringNotContainsString('326900004', $csv);
    }

    public function test_relatorio_de_divergencia_entregue_no_sap_e_aberto_aqui(): void
    {
        $this->cenario();

        $csv = $this->baixar('divergencia_sistema');

        $this->assertStringContainsString('326900004', $csv);
        $this->assertStringNotContainsString('326900003', $csv);
    }

    public function test_relatorio_respeita_o_filtro_de_destino_da_tela(): void
    {
        $this->cenario();

        // A demanda tem item para CENPES, então entra inteira no recorte.
        $this->assertStringContainsString('326900003', $this->baixar('todos_itens', ['destino' => 'CENPES']));
        $this->assertStringNotContainsString('326900003', $this->baixar('todos_itens', ['destino' => 'INEXISTENTE']));
    }

    public function test_relatorio_desconhecido_retorna_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('demandas.relatorio', ['relatorio' => 'inventado']))
            ->assertNotFound();
    }
}
