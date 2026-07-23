<?php

namespace Tests\Feature;

use App\Models\Demanda;
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

    public function test_contador_do_rodape_soma_os_itens_de_todas_as_demandas_filtradas(): void
    {
        $a = Demanda::factory()->create(['numero_demanda' => 509700001, 'status_demanda' => 'pendente']);
        $b = Demanda::factory()->create(['numero_demanda' => 509700002, 'status_demanda' => 'pendente']);

        foreach (range(1, 3) as $i) {
            $a->itens()->create(['numero_rt' => '32610000'.$i, 'numero_item' => '1']);
        }
        foreach (range(1, 2) as $i) {
            $b->itens()->create(['numero_rt' => '32620000'.$i, 'numero_item' => '1']);
        }

        $this->actingAs(User::factory()->create())
            ->get(route('demandas.index', ['status' => 'pendente']))
            ->assertOk()
            // Soma dos itens do filtro (3 + 2), não os itens da última linha da página.
            ->assertSeeText('5 itens de demanda no filtro atual');
    }

    public function test_listagem_abre_sem_sessao(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('demandas.index'))
            ->assertOk();
    }
}
