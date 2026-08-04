<?php

namespace App\Http\Controllers;

use App\Support\CatalogoPermissoes;
use App\Support\SincronizadorPermissoes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PerfilController extends Controller
{
    /** Papéis que não podem ser renomeados nem excluídos. */
    private const PROTEGIDOS = ['Administrador'];

    public function index(Request $request): View
    {
        $perfis = Role::query()
            ->withCount(['permissions', 'users'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('perfis.index', compact('perfis'));
    }

    public function create(): View
    {
        return view('perfis.create', [
            'grupos' => CatalogoPermissoes::grupos(),
            'selecionadas' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        $perfil = Role::create(['name' => $dados['name'], 'guard_name' => 'web']);
        $perfil->syncPermissions($this->permissoesValidas($request));

        return redirect()->route('perfis.index')->with('success', "Perfil {$perfil->name} criado com sucesso.");
    }

    public function edit(Role $perfil): View
    {
        return view('perfis.edit', [
            'perfil' => $perfil,
            'grupos' => CatalogoPermissoes::grupos(),
            'selecionadas' => $perfil->permissions->pluck('name')->all(),
            'protegido' => in_array($perfil->name, self::PROTEGIDOS, true),
        ]);
    }

    public function update(Request $request, Role $perfil): RedirectResponse
    {
        $protegido = in_array($perfil->name, self::PROTEGIDOS, true);
        $dados = $this->validar($request, $perfil->id);

        if (! $protegido) {
            $perfil->update(['name' => $dados['name']]);
            $perfil->syncPermissions($this->permissoesValidas($request));
        } else {
            // O Administrador mantém acesso total (inclusive a módulos futuros).
            SincronizadorPermissoes::garantir();
            $perfil->syncPermissions(CatalogoPermissoes::todas());
        }

        return redirect()->route('perfis.index')->with('success', "Perfil {$perfil->name} atualizado.");
    }

    public function destroy(Role $perfil): RedirectResponse
    {
        if (in_array($perfil->name, self::PROTEGIDOS, true)) {
            return redirect()->route('perfis.index')->with('error', 'O perfil Administrador não pode ser excluído.');
        }

        if ($perfil->users()->exists()) {
            return redirect()->route('perfis.index')
                ->with('error', "O perfil {$perfil->name} está em uso e não pode ser excluído.");
        }

        $nome = $perfil->name;
        $perfil->delete();

        return redirect()->route('perfis.index')->with('success', "Perfil {$nome} removido.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($ignorarId)],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ], [
            'name.required' => 'Informe o nome do perfil.',
            'name.unique' => 'Já existe um perfil com esse nome.',
        ]);
    }

    /**
     * Só aceita permissões que existem no catálogo (evita injeção de nomes).
     *
     * @return array<int, string>
     */
    private function permissoesValidas(Request $request): array
    {
        // O modulo pode ter entrado no catalogo depois do ultimo deploy.
        SincronizadorPermissoes::garantir();

        return array_values(array_intersect(
            (array) $request->input('permissions', []),
            CatalogoPermissoes::todas()
        ));
    }
}
