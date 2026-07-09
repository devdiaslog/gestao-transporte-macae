<?php

namespace App\Http\Controllers;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
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
            'roles' => UserRole::cases(),
            'allPermissions' => UserPermission::cases(),
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

        $this->syncPermissions($user, $request->input('permissions', []));

        return redirect()->route('users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function edit(User $user): View
    {
        $user->load('permissions');

        return view('users.edit', [
            'user' => $user,
            'statuses' => UserStatus::cases(),
            'roles' => UserRole::cases(),
            'allPermissions' => UserPermission::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->only('name', 'email', 'status', 'role');

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $this->syncPermissions($user, $request->input('permissions', []));

        return redirect()->route('users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    private function syncPermissions(User $user, array $permissions): void
    {
        $user->permissions()->delete();

        foreach ($permissions as $permission) {
            if (UserPermission::tryFrom($permission)) {
                $user->permissions()->create(['permission' => $permission]);
            }
        }
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
