<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SenhaController extends Controller
{
    /** Tela de troca da própria senha. */
    public function editar(): View
    {
        return view('auth.senha', [
            'obrigatoria' => (bool) auth()->user()->deve_trocar_senha,
        ]);
    }

    /** Troca a senha do usuário autenticado. */
    public function atualizar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'senha_atual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:senha_atual'],
        ], [
            'senha_atual.required' => 'Informe a senha atual.',
            'senha_atual.current_password' => 'A senha atual não confere.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação não corresponde à nova senha.',
            'password.different' => 'A nova senha deve ser diferente da atual.',
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
            'deve_trocar_senha' => false,
        ]);

        return redirect()->route('senha.editar')->with('success', 'Senha alterada com sucesso.');
    }
}
