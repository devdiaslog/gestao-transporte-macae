<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocalEtapaRequest;
use App\Http\Requests\UpdateLocalEtapaRequest;
use App\Models\LocalEtapa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocalEtapaController extends Controller
{
    public function index(Request $request): View
    {
        $locaisEtapa = LocalEtapa::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->ativo))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('locais-etapa.index', compact('locaisEtapa'));
    }

    public function store(StoreLocalEtapaRequest $request): RedirectResponse
    {
        LocalEtapa::create([
            'nome' => $request->validated('nome'),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('locais-etapa.index')->with('success', 'Local criado com sucesso.');
    }

    public function update(UpdateLocalEtapaRequest $request, LocalEtapa $localEtapa): RedirectResponse
    {
        $localEtapa->update([
            'nome' => $request->validated('nome'),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return redirect()->route('locais-etapa.index')->with('success', 'Local atualizado com sucesso.');
    }

    public function destroy(LocalEtapa $localEtapa): RedirectResponse
    {
        $localEtapa->delete();

        return redirect()->route('locais-etapa.index')->with('success', 'Local removido com sucesso.');
    }
}
