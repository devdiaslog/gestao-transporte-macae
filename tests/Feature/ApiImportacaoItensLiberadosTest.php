<?php

namespace Tests\Feature;

use App\Enums\StatusSap;
use App\Models\DemandaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiImportacaoItensLiberadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_exige_token_sanctum(): void
    {
        $this->postJson(route('api.itens-liberados.importar'), ['itens' => []])
            ->assertUnauthorized();
    }

    public function test_valida_a_chave_do_item(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [['local_origem' => 'BASE VITORIA']],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['itens.0.numero_rt', 'itens.0.numero_item']);
    }

    public function test_importa_itens_liberados_sem_demanda(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $resposta = $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [
                [
                    'numero_rt' => '326213060',
                    'numero_item' => '5',
                    'subitem' => '2',
                    'criacao_data' => '03.07.2026',
                    'criacao_hora' => '13:56:13',
                    'liberacao_data' => '03.07.2026',
                    'liberacao_hora' => '13:56:46',
                    'prazo_data' => '10.07.2026',
                    'prazo_hora' => '14:00:00',
                    'local_origem' => 'BASE VITORIA',
                    'local_destino' => 'ARM-MACAE',
                    'descricao_item' => 'SKID P/PROTEÇÃO',
                    'peso_total' => '2.408,000',
                    'altura' => '3,6000',
                    'doc_unitizacao_superior' => '4803478',
                    'grupo_planejamento' => 'T44',
                    'status_sap' => '03',
                ],
                [
                    'numero_rt' => '326340468',
                    'numero_item' => '1',
                    'prazo_data' => '22.07.2026',
                    'prazo_hora' => '00:00:00',
                    'local_origem' => 'BASE VITORIA',
                    'local_destino' => 'ARM-MACAE',
                ],
            ],
        ]);

        $resposta->assertOk()->assertJson([
            'ok' => true,
            'itens_criados' => 2,
            'itens_atualizados' => 0,
            'linhas_ignoradas' => 0,
        ]);

        $this->assertSame(2, DemandaItem::count());
        $this->assertSame(2, DemandaItem::whereNull('demanda_id')->count());

        $primeiro = DemandaItem::where('numero_rt', '326213060')->firstOrFail();
        $this->assertSame(StatusSap::Liberado, $primeiro->status_sap);
        $this->assertSame('4803478', $primeiro->doc_unitizacao_superior);
        $this->assertSame('10/07/2026 14:00:00', $primeiro->prazo_item->format('d/m/Y H:i:s'));
        $this->assertSame('03/07/2026 13:56:46', $primeiro->data_hora_liberacao_rt->format('d/m/Y H:i:s'));

        // Hora zerada: o limite real é o fim do dia anterior.
        $segundo = DemandaItem::where('numero_rt', '326340468')->firstOrFail();
        $this->assertSame('21/07/2026 23:59:59', $segundo->prazo_item->format('d/m/Y H:i:s'));
        // Sem status na carga, o envio é sabidamente de itens liberados.
        $this->assertSame(StatusSap::Liberado, $segundo->status_sap);
    }

    public function test_reenvio_atualiza_em_vez_de_duplicar(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $item = ['numero_rt' => '326213060', 'numero_item' => '5', 'subitem' => '2', 'local_destino' => 'ARM-MACAE'];

        $this->postJson(route('api.itens-liberados.importar'), ['itens' => [$item]])->assertOk();

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [[...$item, 'local_destino' => 'ARM-RIO']],
        ])->assertOk()->assertJson(['itens_criados' => 0, 'itens_atualizados' => 1]);

        $this->assertSame(1, DemandaItem::count());
        $this->assertSame('ARM-RIO', DemandaItem::firstOrFail()->local_destino);
    }

    /**
     * A automação envia lotes parciais; marcar ausentes só faz sentido quando o
     * envio é o conjunto completo dos liberados, então é preciso pedir.
     */
    public function test_nao_marca_ausentes_por_padrao(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [
                ['numero_rt' => '326213060', 'numero_item' => '5'],
                ['numero_rt' => '326340468', 'numero_item' => '1'],
            ],
        ])->assertOk();

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [['numero_rt' => '326213060', 'numero_item' => '5']],
        ])->assertOk()->assertJson(['itens_ausentes' => 0]);

        $this->assertNull(DemandaItem::where('numero_rt', '326340468')->firstOrFail()->ausente_no_sap_em);
    }

    public function test_marca_ausentes_quando_o_envio_e_a_carga_completa(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [
                ['numero_rt' => '326213060', 'numero_item' => '5'],
                ['numero_rt' => '326340468', 'numero_item' => '1'],
            ],
        ])->assertOk();

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [['numero_rt' => '326213060', 'numero_item' => '5']],
            'marcar_ausentes' => true,
        ])->assertOk()->assertJson(['itens_ausentes' => 1]);

        $sumiu = DemandaItem::where('numero_rt', '326340468')->firstOrFail();
        $this->assertNotNull($sumiu->ausente_no_sap_em);
        $this->assertSame(StatusSap::Liberado, $sumiu->status_sap);
    }

    public function test_status_desconhecido_volta_como_aviso(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.itens-liberados.importar'), [
            'itens' => [['numero_rt' => '326213060', 'numero_item' => '5', 'status_sap' => '99']],
        ])->assertOk()
            ->assertJson(['ok' => true, 'itens_criados' => 1])
            ->assertJsonCount(1, 'avisos');

        $this->assertNull(DemandaItem::firstOrFail()->status_sap);
    }

    public function test_limita_o_tamanho_do_lote(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $itens = array_fill(0, 1001, ['numero_rt' => '326213060', 'numero_item' => '1']);

        $this->postJson(route('api.itens-liberados.importar'), ['itens' => $itens])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens');
    }
}
