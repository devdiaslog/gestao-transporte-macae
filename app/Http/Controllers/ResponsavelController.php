<?php

namespace App\Http\Controllers;

use App\Enums\TipoResponsavel;
use App\Http\Requests\StoreResponsavelRequest;
use App\Http\Requests\UpdateResponsavelRequest;
use App\Models\Responsavel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResponsavelController extends Controller
{
    public function index(Request $request): View
    {
        $responsaveis = Responsavel::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->ativo))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $tipos = TipoResponsavel::cases();

        return view('responsaveis.index', compact('responsaveis', 'tipos'));
    }

    public function create(): View
    {
        $tipos = TipoResponsavel::cases();

        return view('responsaveis.create', compact('tipos'));
    }

    public function store(StoreResponsavelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo');

        Responsavel::create($data);

        return redirect()->route('responsaveis.index')
            ->with('success', 'Responsável criado com sucesso.');
    }

    public function edit(Responsavel $responsavel): View
    {
        $tipos = TipoResponsavel::cases();

        return view('responsaveis.edit', compact('responsavel', 'tipos'));
    }

    public function update(UpdateResponsavelRequest $request, Responsavel $responsavel): RedirectResponse
    {
        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo');

        $responsavel->update($data);

        return redirect()->route('responsaveis.index')
            ->with('success', 'Responsável atualizado com sucesso.');
    }

    public function destroy(Responsavel $responsavel): RedirectResponse
    {
        $responsavel->delete();

        return redirect()->route('responsaveis.index')
            ->with('success', 'Responsável removido com sucesso.');
    }
}
