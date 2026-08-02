<?php

namespace Tests\Feature;

use App\Enums\FonteDemanda;
use App\Enums\StatusDemanda;
use App\Models\Demanda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiConsultaDemandasTest extends TestCase
{
    use RefreshDatabase;

    private function demanda(int $numero, StatusDemanda $status, FonteDemanda $fonte, int $itens = 0): Demanda
    {
        $demanda = Demanda::factory()->create([
            'numero_demanda' => $numero,
            'status_demanda' => $status,
            'fonte_demanda' => $fonte,
        ]);

        for ($i = 0; $i < $itens; $i++) {
            $demanda->itens()->create([
                'numero_rt' => '326000'.$numero.$i,
                'numero_item' => '1',
                'subitem' => (string) $i,
            ]);
        }

        return $demanda;
    }

    public function test_exige_token_sanctum(): void
    {
        $this->getJson(route('api.demandas.index'))->assertUnauthorized();
    }

    public function test_lista_as_demandas_com_a_contagem_de_itens(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->demanda(509000001, StatusDemanda::Pendente, FonteDemanda::SapLt, itens: 2);

        $resposta = $this->getJson(route('api.demandas.index'))->assertOk();

        $resposta->assertJsonPath('total', 1);
        $resposta->assertJsonPath('data.0.numero_demanda', 509000001);
        $resposta->assertJsonPath('data.0.fonte_demanda', 'sap_lt');
        $resposta->assertJsonPath('data.0.status_demanda', 'pendente');
        $resposta->assertJsonPath('data.0.total_itens', 2);
    }

    public function test_filtra_por_fonte(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->demanda(509000001, StatusDemanda::Pendente, FonteDemanda::SapLt);
        $this->demanda(619000001, StatusDemanda::Pendente, FonteDemanda::SapTm);

        $this->getJson(route('api.demandas.index', ['fonte' => ['sap_tm']]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.numero_demanda', 619000001);
    }

    public function test_filtra_por_lista_de_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->demanda(509000001, StatusDemanda::Pendente, FonteDemanda::SapLt);
        $this->demanda(509000002, StatusDemanda::EmAndamento, FonteDemanda::SapLt);
        $this->demanda(509000003, StatusDemanda::Finalizado, FonteDemanda::SapLt);

        $this->getJson(route('api.demandas.index', ['status' => ['pendente', 'em_andamento']]))
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_aceita_os_filtros_separados_por_virgula(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->demanda(509000001, StatusDemanda::Pendente, FonteDemanda::SapLt);
        $this->demanda(509000002, StatusDemanda::Finalizado, FonteDemanda::SapLt);

        $this->getJson(route('api.demandas.index').'?status=pendente,em_andamento&fonte=sap_lt')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.numero_demanda', 509000001);
    }

    public function test_filtra_as_demandas_sem_item(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->demanda(509000001, StatusDemanda::Pendente, FonteDemanda::SapLt, itens: 3);
        $this->demanda(509000002, StatusDemanda::Pendente, FonteDemanda::SapLt);

        $resposta = $this->getJson(route('api.demandas.index', ['sem_itens' => true]))->assertOk();

        $resposta->assertJsonPath('total', 1);
        $resposta->assertJsonPath('data.0.numero_demanda', 509000002);
        $resposta->assertJsonPath('data.0.total_itens', 0);
    }

    public function test_combina_os_tres_filtros(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // O alvo: TM, em andamento e ainda sem item.
        $this->demanda(619000001, StatusDemanda::EmAndamento, FonteDemanda::SapTm);
        // Mesma fonte e status, mas já tem item.
        $this->demanda(619000002, StatusDemanda::EmAndamento, FonteDemanda::SapTm, itens: 1);
        // Sem item, mas fonte diferente.
        $this->demanda(509000003, StatusDemanda::EmAndamento, FonteDemanda::SapLt);
        // Sem item e fonte certa, mas status fora da lista.
        $this->demanda(619000004, StatusDemanda::Finalizado, FonteDemanda::SapTm);

        $this->getJson(route('api.demandas.index', [
            'fonte' => ['sap_tm'],
            'status' => ['pendente', 'em_andamento'],
            'sem_itens' => true,
        ]))->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.numero_demanda', 619000001);
    }

    public function test_recusa_fonte_desconhecida(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.demandas.index', ['fonte' => ['sap_xx']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fonte.0']);
    }
}
