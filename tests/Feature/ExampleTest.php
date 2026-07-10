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

    /** Rota raiz redireciona usuário autenticado para a torre de controle. */
    public function test_root_redirects_authenticated_user_to_control_tower(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('control-tower.index'));
    }
}
