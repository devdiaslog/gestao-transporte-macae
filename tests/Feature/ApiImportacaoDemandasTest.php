<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\StatusItemDemanda;
use App\Models\Demanda;
use App\Models\DemandaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiImportacaoDemandasTest extends TestCase
{
    use RefreshDatabase;

    public function test_exige_token_sanctum(): void
    {
        $this->postJson(route('api.demandas.importar'), ['itens' => []])
            ->assertUnauthorized();
    }

    public function test_valida_nota_e_rt_obrigatorios(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.demandas.importar'), [
            'itens' => [['local_origem' => 'PACU']],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.nota', 'itens.0.numero_rt']);
    }

    public function test_importa_itens_criando_demanda_com_as_mesmas_regras_da_planilha(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $resposta = $this->postJson(route('api.demandas.importar'), [
            'itens' => [
                [
                    'nota' => '509800030',
                    'numero_rt' => '326000200',
                    'numero_item' => '1',
                    'local_origem' => 'PACU',
                    'local_destino' => 'ARM-MACAE',
                    'status_item' => '04',
                    'prazo_data' => '25.07.2026',
                    'prazo_hora' => '10:00:00',
                ],
                [
                    'nota' => '509800030',
                    'numero_rt' => '326000201',
                    'numero_item' => '1',
                    'local_origem' => 'PACU',
                    'local_destino' => 'ARM-MACAE',
                    'status_item' => '07',
                    'entrega_data' => '24.07.2026',
                    'entrega_hora' => '18:30:00',
                ],
            ],
        ]);

        $resposta->assertOk()->assertJson([
            'ok' => true,
            'demandas_criadas' => 1,
            'itens_criados' => 2,
        ]);

        $demanda = Demanda::where('numero_demanda', 509800030)->firstOrFail();

        $this->assertSame(FonteDemanda::SapLt, $demanda->fonte_demanda);
        $this->assertSame(2, $demanda->itens()->count());

        $entregue = $demanda->itens()->where('numero_rt', '326000201')->first();
        $this->assertSame(StatusItemDemanda::Entregue, $entregue->status_item);
        $this->assertSame('24/07/2026 18:30', $entregue->data_hora_entrega->format('d/m/Y H:i'));
    }

    public function test_reenvio_preserva_status_definido_pelo_operador(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'itens' => [
                ['nota' => '509800031', 'numero_rt' => '326000210', 'numero_item' => '1', 'status_item' => '04'],
            ],
        ];

        $this->postJson(route('api.demandas.importar'), $payload)->assertOk();

        DemandaItem::where('numero_rt', '326000210')
            ->firstOrFail()
            ->update(['status_item' => StatusItemDemanda::Recusado]);

        $this->postJson(route('api.demandas.importar'), $payload)
            ->assertOk()
            ->assertJson(['itens_atualizados' => 1]);

        $this->assertSame(
            StatusItemDemanda::Recusado,
            DemandaItem::where('numero_rt', '326000210')->first()->status_item,
        );
    }
}
