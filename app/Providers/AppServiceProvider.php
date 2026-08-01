<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::defaultView('pagination.custom');

        /**
         * Autorização por permissões (spatie): as rotas e views usam
         * `can:modulo.acao`. O papel Administrador recebe tudo, inclusive
         * módulos criados depois — evita perfil admin ficar sem acesso novo.
         */
        Gate::before(fn (User $user) => $user->hasRole('Administrador') ? true : null);

        /**
         * Acesso geral ao sistema: qualquer permissão além do Mapa Geral.
         * Mantido como gate por ser um "guarda-chuva" usado no grupo de rotas.
         */
        Gate::define('access-app', function (User $user) {
            return $user->getAllPermissions()
                ->contains(fn ($permissao) => $permissao->name !== 'mapa-geral.ver');
        });
    }
}
