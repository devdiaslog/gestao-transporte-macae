<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJustificativaRequest;
use App\Http\Requests\UpdateJustificativaRequest;
use App\Models\Justificativa;
use App\Models\TipoOcorrencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JustificativaController extends Controller
{
    public function index(Request $request): View
    {
        $justificativas = Justificativa::query()
            ->when($request->filled('search'), fn ($q) => $q->where('descricao', 'like', '%'.$request->search.'%'))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->ativo))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('justificativas.index', compact('justificativas'));
    }

    public function create(): View
    {
        $tipos = TipoOcorrencia::orderBy('descricao')->get();

        return view('justificativas.create', compact('tipos'));
    }

    public function store(StoreJustificativaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo');
        $data['obrigar_observacao'] = $request->boolean('obrigar_observacao');

        $justificativa = Justificativa::create($data);
        $justificativa->tiposOcorrencia()->sync($request->input('tipos', []));

        return redirect()->route('justificativas.index')
            ->with('success', 'Justificativa criada com sucesso.');
    }

    public function edit(Justificativa $justificativa): View
    {
        $tipos = TipoOcorrencia::orderBy('descricao')->get();
        $tiposVinculados = $justificativa->tiposOcorrencia->pluck('id_tipo')->toArray();

        return view('justificativas.edit', compact('justificativa', 'tipos', 'tiposVinculados'));
    }

    public function update(UpdateJustificativaRequest $request, Justificativa $justificativa): RedirectResponse
    {
        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo');
        $data['obrigar_observacao'] = $request->boolean('obrigar_observacao');

        $justificativa->update($data);
        $justificativa->tiposOcorrencia()->sync($request->input('tipos', []));

        return redirect()->route('justificativas.index')
            ->with('success', 'Justificativa atualizada com sucesso.');
    }

    public function destroy(Justificativa $justificativa): RedirectResponse
    {
        $justificativa->delete();

        return redirect()->route('justificativas.index')
            ->with('success', 'Justificativa removida com sucesso.');
    }
}
