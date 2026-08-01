<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Rota raiz redireciona visitante para login. */
    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /** Com acesso ao dashboard, a raiz leva à visão gerencial. */
    public function test_root_redirects_authenticated_user_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard.demandas'));
    }

    /** Sem dashboard, a raiz leva à listagem de demandas. */
    public function test_root_redirects_operador_to_demandas(): void
    {
        $user = User::factory()->comPerfil('Operador')->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('demandas.index'));
    }
}
