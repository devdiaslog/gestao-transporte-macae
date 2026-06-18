<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoEtapaRequest;
use App\Http\Requests\UpdateTipoEtapaRequest;
use App\Models\TipoEtapa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoEtapaController extends Controller
{
    public function index(Request $request): View
    {
        $tiposEtapa = TipoEtapa::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->ativo))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('tipos-etapa.index', compact('tiposEtapa'));
    }

    public function store(StoreTipoEtapaRequest $request): RedirectResponse
    {
        TipoEtapa::create([
            'nome' => $request->validated('nome'),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('tipos-etapa.index')->with('success', 'Tipo de etapa criado com sucesso.');
    }

    public function update(UpdateTipoEtapaRequest $request, TipoEtapa $tipoEtapa): RedirectResponse
    {
        $tipoEtapa->update([
            'nome' => $request->validated('nome'),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('tipos-etapa.index')->with('success', 'Tipo de etapa atualizado com sucesso.');
    }

    public function destroy(TipoEtapa $tipoEtapa): RedirectResponse
    {
        $tipoEtapa->delete();

        return redirect()->route('tipos-etapa.index')->with('success', 'Tipo de etapa removido com sucesso.');
    }
}
