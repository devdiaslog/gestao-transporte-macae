<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\User;
use App\Support\CatalogoPermissoes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissoesTest extends TestCase
{
    use RefreshDatabase;

    /** Descarta o cache de permissões (persiste entre requisições no teste). */
    private function recarregarPermissoes(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_papeis_padrao_sao_criados_com_suas_permissoes(): void
    {
        $this->assertSame(count(CatalogoPermissoes::todas()), Role::findByName('Administrador')->permissions()->count());
        $this->assertTrue(Role::findByName('Operador')->hasPermissionTo('demandas.ver'));
        $this->assertFalse(Role::findByName('Operador')->hasPermissionTo('usuarios.ver'));
        $this->assertSame(['mapa-geral.ver'], Role::findByName('Visualizador')->permissions->pluck('name')->all());
    }

    public function test_operador_nao_acessa_gestao_de_usuarios(): void
    {
        $operador = User::factory()->comPerfil('Operador')->create();

        $this->actingAs($operador)->get(route('users.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('perfis.index'))->assertForbidden();
    }

    public function test_operador_ve_demandas_mas_nao_exclui(): void
    {
        $operador = User::factory()->comPerfil('Operador')->create();

        $this->actingAs($operador)->get(route('demandas.index'))->assertOk();

        $demanda = Demanda::factory()->create(['numero_demanda' => 509990001]);
        $this->actingAs($operador)->delete(route('demandas.destroy', $demanda))->assertForbidden();
    }

    public function test_permissao_extra_individual_libera_modulo_fora_do_perfil(): void
    {
        $operador = User::factory()->comPerfil('Operador')->create();
        $this->actingAs($operador)->get(route('medicoes.index'))->assertForbidden();

        // Concede a permissão só para este usuário (sem mexer no perfil).
        $operador->givePermissionTo('medicoes.ver');
        $this->recarregarPermissoes();

        $this->actingAs($operador->fresh())->get(route('medicoes.index'))->assertOk();
    }

    public function test_perfil_novo_criado_na_tela_concede_acesso(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('perfis.store'), [
            'name' => 'Gestão de Frota',
            'permissions' => ['equipamentos.ver', 'motoristas.ver'],
        ])->assertRedirect(route('perfis.index'));

        $usuario = User::factory()->comPerfil('Gestão de Frota')->create();
        $this->recarregarPermissoes();

        $this->actingAs($usuario->fresh())->get(route('equipamentos.index'))->assertOk();
        $this->actingAs($usuario)->get(route('demandas.index'))->assertForbidden();
    }

    public function test_perfil_administrador_nao_pode_ser_excluido(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('perfis.destroy', Role::findByName('Administrador')))
            ->assertSessionHas('error');

        $this->assertNotNull(Role::findByName('Administrador'));
    }

    public function test_admin_reseta_senha_e_usuario_e_obrigado_a_trocar(): void
    {
        $admin = User::factory()->create();
        $alvo = User::factory()->comPerfil('Operador')->create();

        $this->actingAs($admin)
            ->post(route('users.resetar-senha', $alvo))
            ->assertRedirect(route('users.index'));

        $alvo->refresh();

        $this->assertTrue($alvo->deve_trocar_senha);
        $this->assertTrue(Hash::check('12345678', $alvo->password));

        // Navegação bloqueada até trocar a senha.
        $this->actingAs($alvo)->get(route('demandas.index'))->assertRedirect(route('senha.editar'));
    }

    public function test_usuario_troca_a_propria_senha_e_libera_a_navegacao(): void
    {
        $user = User::factory()->comPerfil('Operador')->create([
            'password' => Hash::make('12345678'),
            'deve_trocar_senha' => true,
        ]);

        $this->actingAs($user)->put(route('senha.atualizar'), [
            'senha_atual' => '12345678',
            'password' => 'nova-senha-forte',
            'password_confirmation' => 'nova-senha-forte',
        ])->assertRedirect(route('senha.editar'));

        $user->refresh();

        $this->assertFalse($user->deve_trocar_senha);
        $this->assertTrue(Hash::check('nova-senha-forte', $user->password));
        $this->actingAs($user)->get(route('demandas.index'))->assertOk();
    }

    public function test_senha_atual_incorreta_nao_troca(): void
    {
        $user = User::factory()->create(['password' => Hash::make('senha-certa')]);

        $this->actingAs($user)->put(route('senha.atualizar'), [
            'senha_atual' => 'senha-errada',
            'password' => 'outra-senha-forte',
            'password_confirmation' => 'outra-senha-forte',
        ])->assertSessionHasErrors('senha_atual');

        $this->assertTrue(Hash::check('senha-certa', $user->refresh()->password));
    }
}
