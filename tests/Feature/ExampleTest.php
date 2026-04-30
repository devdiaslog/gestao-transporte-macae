<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** Rota raiz redireciona visitante para ocorrências (que por sua vez redireciona para login). */
    public function test_root_redirects_guest_to_ocorrencias(): void
    {
        $this->get('/')->assertRedirect(route('ocorrencias.index'));
    }

    /** Rota raiz redireciona usuário autenticado para ocorrências. */
    public function test_root_redirects_authenticated_user_to_ocorrencias(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertRedirect(route('ocorrencias.index'));
    }
}
