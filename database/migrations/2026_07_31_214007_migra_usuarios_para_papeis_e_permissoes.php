<?php

use App\Models\User;
use App\Support\CatalogoPermissoes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Publica o catálogo de permissões, cria os papéis padrão e migra os
     * usuários existentes (coluna `role` + permissões individuais antigas)
     * para o novo modelo de papéis e permissões.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deve_trocar_senha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('deve_trocar_senha')
                    ->default(false)
                    ->after('status');
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Permissões do catálogo
        foreach (CatalogoPermissoes::todas() as $nome) {
            Permission::firstOrCreate([
                'name' => $nome,
                'guard_name' => 'web',
            ]);
        }

        // 2. Papéis padrão com suas permissões
        foreach (CatalogoPermissoes::papeisPadrao() as $nome => $config) {
            $papel = Role::firstOrCreate([
                'name' => $nome,
                'guard_name' => 'web',
            ]);

            $permissoes = ($config['todas'] ?? false)
                ? CatalogoPermissoes::todas()
                : collect($config['modulos'] ?? [])
                ->flatMap(
                    fn(array $acoes, string $modulo) =>
                    array_map(fn($a) => "{$modulo}.{$a}", $acoes)
                )
                ->intersect(CatalogoPermissoes::todas())
                ->values()
                ->all();

            $papel->syncPermissions($permissoes);
        }

        // 3. Usuários: a coluna `role` legada define o papel equivalente
        $equivalente = [
            'administrador' => 'Administrador',
            'supervisor' => 'Supervisor',
            'operador' => 'Operador',
            'visualizador' => 'Visualizador',
        ];

        $usuarios = DB::table('users')
            ->select('id', 'role')
            ->get();

        $papeis = Role::pluck('id', 'name');

        foreach ($usuarios as $usuario) {
            $nomePapel = $equivalente[$usuario->role] ?? 'Operador';

            if (isset($papeis[$nomePapel])) {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $papeis[$nomePapel],
                    'model_type' => User::class,
                    'model_id' => $usuario->id,
                ]);
            }
        }

        // 4. Permissão individual antiga de Dashboard vira permissão direta
        if (Schema::hasTable('user_permissions')) {
            $dashboard = Permission::where('name', 'dashboard.ver')->first();

            if ($dashboard) {
                $comDashboard = DB::table('user_permissions')
                    ->where('permission', 'dashboard')
                    ->pluck('user_id')
                    ->unique();

                foreach ($comDashboard as $userId) {
                    DB::table('model_has_permissions')->updateOrInsert([
                        'permission_id' => $dashboard->id,
                        'model_type' => User::class,
                        'model_id' => $userId,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'deve_trocar_senha')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('deve_trocar_senha');
            });
        }

        DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->delete();

        DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
