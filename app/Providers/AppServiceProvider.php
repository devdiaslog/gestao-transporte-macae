<?php

namespace App\Providers;

use App\Enums\UserRole;
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

        /** Apenas Administrador pode gerenciar usuários e perfis. */
        Gate::define('manage-users', fn (User $user) => $user->role === UserRole::Administrador);

        /** Administrador e Supervisor podem gerenciar tabelas de apoio (responsáveis, tipos de ocorrência, justificativas). */
        Gate::define('manage-support-tables', fn (User $user) => in_array($user->role, [UserRole::Administrador, UserRole::Supervisor]));

        /** Administrador e Supervisor podem gerenciar cadastros (equipamentos, divisões, motoristas, etc.). */
        Gate::define('manage-cadastros', fn (User $user) => in_array($user->role, [UserRole::Administrador, UserRole::Supervisor]));

        /** Administrador e Supervisor podem auditar ocorrências fechadas. */
        Gate::define('auditar-ocorrencias', fn (User $user) => in_array($user->role, [UserRole::Administrador, UserRole::Supervisor]));
    }
}
