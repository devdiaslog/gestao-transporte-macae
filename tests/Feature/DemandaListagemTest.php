<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaListagemTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessao_com_filtros_vazios_nao_causa_loop_de_redirect(): void
    {
        // Estado que causava ERR_TOO_MANY_REDIRECTS: filtros salvos todos vazios/null.
        session(['demandas.filtros' => [
            'q' => null, 'status' => null, 'tipo' => null, 'fonte' => null,
            'prefixo' => null, 'data_de' => null, 'data_ate' => null,
            'prazo' => null, 'prazo_de' => null, 'prazo_ate' => null,
        ]]);

        $this->actingAs(User::factory()->create())
            ->get(route('demandas.index'))
            ->assertOk();
    }

    public function test_sessao_com_filtro_real_redireciona_uma_vez(): void
    {
        session(['demandas.filtros' => ['status' => 'finalizado']]);

        $this->actingAs(User::factory()->create())
            ->get(route('demandas.index'))
            ->assertRedirect(route('demandas.index', ['status' => 'finalizado']));
    }

    public function test_listagem_abre_sem_sessao(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('demandas.index'))
            ->assertOk();
    }
}
