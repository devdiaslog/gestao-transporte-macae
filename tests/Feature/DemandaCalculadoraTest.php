<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Enums\StatusItemDemanda;
use App\Enums\TipoDemanda;
use App\Models\Demanda;
use App\Services\DemandaCalculadora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaCalculadoraTest extends TestCase
{
    use RefreshDatabase;

    private function calculadora(): DemandaCalculadora
    {
        return app(DemandaCalculadora::class);
    }

    /**
     * @param  array<int, array<string, mixed>>  $itens
     */
    private function demandaCom(array $itens, int $numero = 509000001): Demanda
    {
        $demanda = Demanda::factory()->create(['numero_demanda' => $numero]);

        foreach ($itens as $i => $item) {
            $demanda->itens()->create(array_merge([
                'numero_rt' => '32600000'.$i,
                'numero_item' => '1',
                'subitem' => (string) $i,
            ], $item));
        }

        return $demanda->load('itens');
    }

    public function test_fonte_sap_lt_quando_numero_comeca_com_50(): void
    {
        $demanda = $this->demandaCom([], 509538496);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(FonteDemanda::SapLt, $demanda->fresh()->fonte_demanda);
    }

    public function test_fonte_sap_tm_quando_numero_comeca_com_61(): void
    {
        $demanda = $this->demandaCom([], 610123456);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(FonteDemanda::SapTm, $demanda->fresh()->fonte_demanda);
    }

    public function test_tipo_load_quando_algum_destino_e_ponto_chave(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'SEROPEDICA'],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'BMAC'],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(TipoDemanda::Load, $demanda->fresh()->tipo_demanda);
    }

    public function test_tipo_backload_quando_origem_e_ponto_chave_e_destino_nao(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'PACU', 'local_destino' => 'ARM-MACAE'],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(TipoDemanda::Backload, $demanda->fresh()->tipo_demanda);
    }

    public function test_tipo_transferencia_quando_nenhum_ponto_chave_envolvido(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'SEROPEDICA'],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(TipoDemanda::Transferencia, $demanda->fresh()->tipo_demanda);
    }

    public function test_prazo_usa_menor_data_ainda_exequivel_ignorando_vencidas(): void
    {
        $vencida = now()->subDays(3);
        $proxima = now()->addDay();
        $distante = now()->addDays(10);

        $demanda = $this->demandaCom([
            ['prazo_item' => $vencida, 'status_item' => StatusItemDemanda::Pendente],
            ['prazo_item' => $distante, 'status_item' => StatusItemDemanda::Pendente],
            ['prazo_item' => $proxima, 'status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertTrue(
            $proxima->isSameMinute($demanda->fresh()->prazo_demanda),
            'Deveria usar a menor data ainda alcançável, não a vencida nem a distante.'
        );
    }

    public function test_prazo_usa_a_mais_antiga_quando_todas_estao_vencidas(): void
    {
        $maisAntiga = now()->subDays(9);
        $menosAntiga = now()->subDay();

        $demanda = $this->demandaCom([
            ['prazo_item' => $menosAntiga, 'status_item' => StatusItemDemanda::Pendente],
            ['prazo_item' => $maisAntiga, 'status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertTrue(
            $maisAntiga->isSameMinute($demanda->fresh()->prazo_demanda),
            'Sem data exequível, deveria cair na mais antiga.'
        );
    }

    public function test_status_finalizado_quando_todos_os_itens_entregues(): void
    {
        $demanda = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Entregue],
            ['status_item' => StatusItemDemanda::Entregue],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(StatusDemanda::Finalizado, $demanda->fresh()->status_demanda);
    }

    public function test_status_cancelada_quando_todos_os_itens_cancelados(): void
    {
        $demanda = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Cancelado],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(StatusDemanda::Cancelada, $demanda->fresh()->status_demanda);
    }

    public function test_status_recusa_apenas_quando_todos_os_itens_recusados(): void
    {
        $todos = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Recusado],
            ['status_item' => StatusItemDemanda::Recusado],
        ], 509000010);
        $this->calculadora()->recalcular($todos);
        $this->assertSame(StatusDemanda::Recusa, $todos->fresh()->status_demanda);

        // Um item ainda pendente impede a Recusa da demanda.
        $parcial = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Recusado],
            ['status_item' => StatusItemDemanda::Pendente],
        ], 509000011);
        $this->calculadora()->recalcular($parcial);
        $this->assertSame(StatusDemanda::EmAndamento, $parcial->fresh()->status_demanda);
    }

    public function test_status_em_andamento_quando_ha_itens_encerrados_e_abertos(): void
    {
        $demanda = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Entregue],
            ['status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(StatusDemanda::EmAndamento, $demanda->fresh()->status_demanda);
    }

    public function test_status_pendente_quando_todos_os_itens_estao_abertos(): void
    {
        $demanda = $this->demandaCom([
            ['status_item' => StatusItemDemanda::Pendente],
            ['status_item' => StatusItemDemanda::Pendente],
        ]);

        $this->calculadora()->recalcular($demanda);

        $this->assertSame(StatusDemanda::Pendente, $demanda->fresh()->status_demanda);
    }

    public function test_rota_consolida_origens_e_destinos_distintos_dos_itens(): void
    {
        $demanda = $this->demandaCom([
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'SEROPEDICA'],
            ['local_origem' => 'ARM-MACAE', 'local_destino' => 'CENPES'],
        ]);

        $this->assertSame('ARM-MACAE → SEROPEDICA, CENPES', $demanda->rota());
    }
}
