<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quem teve a senha resetada pelo administrador navega apenas para a tela de
 * troca de senha (e logout) até definir uma nova — evita senha padrão em uso.
 */
class ForcarTrocaDeSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $liberadas = ['senha.editar', 'senha.atualizar', 'logout'];

        if ($user?->deve_trocar_senha && ! $request->routeIs(...$liberadas)) {
            if ($request->expectsJson()) {
                abort(403, 'Defina uma nova senha para continuar.');
            }

            return redirect()->route('senha.editar')
                ->with('error', 'Sua senha foi redefinida pelo administrador. Defina uma nova senha para continuar.');
        }

        return $next($request);
    }
}
