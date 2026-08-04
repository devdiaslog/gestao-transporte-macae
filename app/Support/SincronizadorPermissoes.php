<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Publica no banco as permissões declaradas em {@see CatalogoPermissoes}.
 *
 * O catálogo é código e ganha módulos a cada release; o banco só aprende
 * sobre eles quando alguém sincroniza. Enquanto isso não acontece, atribuir
 * o módulo novo a um perfil estoura com PermissionDoesNotExist e apenas o
 * Administrador enxerga a tela — ele passa pelo Gate::before sem consultar
 * permissão nenhuma.
 */
class SincronizadorPermissoes
{
    /**
     * Cria as permissões do catálogo que ainda não existem no banco.
     * Idempotente: sem novidades, não escreve nada.
     *
     * @return array<int, string> as permissões criadas agora
     */
    public static function garantir(): array
    {
        $catalogo = CatalogoPermissoes::todas();

        $existentes = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $catalogo)
            ->pluck('name')
            ->all();

        $faltantes = array_values(array_diff($catalogo, $existentes));

        if ($faltantes === []) {
            return [];
        }

        $agora = now();

        DB::table(config('permission.table_names.permissions', 'permissions'))->insert(
            array_map(fn (string $nome) => [
                'name' => $nome,
                'guard_name' => 'web',
                'created_at' => $agora,
                'updated_at' => $agora,
            ], $faltantes)
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $faltantes;
    }
}
