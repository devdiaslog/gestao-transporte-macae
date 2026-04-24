<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoOcorrenciaRequest;
use App\Http\Requests\UpdateTipoOcorrenciaRequest;
use App\Models\Responsavel;
use App\Models\TipoOcorrencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoOcorrenciaController extends Controller
{
    public function index(Request $request): View
    {
        $tipos = TipoOcorrencia::query()
            ->when($request->filled('search'), fn ($q) => $q->where('descricao', 'like', '%'.$request->search.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tipos-ocorrencia.index', compact('tipos'));
    }

    public function create(): View
    {
        $responsaveis = Responsavel::where('ativo', true)->orderBy('nome')->get();

        return view('tipos-ocorrencia.create', compact('responsaveis'));
    }

    public function store(StoreTipoOcorrenciaRequest $request): RedirectResponse
    {
        $tipo = TipoOcorrencia::create($request->only('descricao'));

        $tipo->responsaveis()->sync($request->input('responsaveis', []));

        return redirect()->route('tipos-ocorrencia.index')
            ->with('success', 'Tipo de ocorrência criado com sucesso.');
    }

    public function edit(TipoOcorrencia $tipoOcorrencia): View
    {
        $responsaveis = Responsavel::where('ativo', true)->orderBy('nome')->get();
        $responsaveisSelecionados = $tipoOcorrencia->responsaveis->pluck('id_responsavel')->toArray();

        return view('tipos-ocorrencia.edit', compact('tipoOcorrencia', 'responsaveis', 'responsaveisSelecionados'));
    }

    public function update(UpdateTipoOcorrenciaRequest $request, TipoOcorrencia $tipoOcorrencia): RedirectResponse
    {
        $tipoOcorrencia->update($request->only('descricao'));

        $tipoOcorrencia->responsaveis()->sync($request->input('responsaveis', []));

        return redirect()->route('tipos-ocorrencia.index')
            ->with('success', 'Tipo de ocorrência atualizado com sucesso.');
    }

    public function destroy(TipoOcorrencia $tipoOcorrencia): RedirectResponse
    {
        $tipoOcorrencia->delete();

        return redirect()->route('tipos-ocorrencia.index')
            ->with('success', 'Tipo de ocorrência removido com sucesso.');
    }
}
