<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\CatalogoPermissoes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /** Senha aplicada no reset feito pelo administrador. */
    public const SENHA_PADRAO = '12345678';

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'statuses' => UserStatus::cases(),
            'perfis' => Role::orderBy('name')->get(),
            'grupos' => CatalogoPermissoes::grupos(),
            'papelAtual' => null,
            'extras' => [],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'role' => $request->role,
        ]);

        $this->sincronizarAcessos($user, $request);

        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
            'perfis' => Role::orderBy('name')->get(),
            'grupos' => CatalogoPermissoes::grupos(),
            'papelAtual' => $user->papelPrincipal(),
            'extras' => $user->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only('name', 'email', 'status', 'role');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->sincronizarAcessos($user, $request);

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    /**
     * Aplica o perfil (papel) e as permissões extras individuais do usuário.
     */
    private function sincronizarAcessos(User $user, Request $request): void
    {
        $papel = $request->input('perfil');
        $user->syncRoles($papel && Role::where('name', $papel)->exists() ? [$papel] : []);

        // Extras: concedidas além do perfil, validadas pelo catálogo.
        $extras = array_values(array_intersect(
            (array) $request->input('permissions', []),
            CatalogoPermissoes::todas()
        ));

        $user->syncPermissions($extras);
    }

    /**
     * Redefine a senha para o padrão e obriga a troca no próximo acesso.
     */
    public function resetarSenha(User $user): RedirectResponse
    {
        $user->update([
            'password' => Hash::make(self::SENHA_PADRAO),
            'deve_trocar_senha' => true,
        ]);

        return redirect()->route('users.index')->with(
            'success',
            "Senha de {$user->name} redefinida para ".self::SENHA_PADRAO.'. O usuário deverá trocá-la no próximo acesso.'
        );
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }

    public function export(Request $request): Response
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'usuarios_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Nome', 'E-mail', 'Status', 'Cadastrado em']])
            ->concat($users->map(fn (User $user) => [
                $user->name,
                $user->email,
                $user->status->label(),
                $user->created_at->format('d/m/Y H:i'),
            ]))
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', $cell).'"', $row)))
            ->implode("\n");

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }
}
