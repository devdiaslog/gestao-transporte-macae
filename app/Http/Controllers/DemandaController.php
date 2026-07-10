<?php

namespace App\Http\Controllers;

use App\Enums\TipoCadastro;
use App\Http\Requests\StoreDemandaRequest;
use App\Http\Requests\UpdateDemandaRequest;
use App\Models\Demanda;
use App\Models\Equipamento;
use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemandaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('q');
        $status = $request->input('status');
        $tipo = $request->input('tipo');

        $demandas = Demanda::query()
            ->with(['equipamento', 'localOrigem', 'localDestino', 'criador'])
            ->when($search, fn ($q) => $q->where('numero_demanda', 'like', "%{$search}%"))
            ->when($status, fn ($q) => $q->where('status_demanda', $status))
            ->when($tipo, fn ($q) => $q->where('tipo_demanda', $tipo))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $locais = Local::ativo()->orderBy('nome')->get();
        $equipamentos = Equipamento::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', 'Motorizado'))
            ->whereNotNull('prefixo')
            ->orderBy('prefixo')
            ->get(['id', 'prefixo', 'placa']);

        return view('demandas.index', compact('demandas', 'locais', 'equipamentos', 'search', 'status', 'tipo'));
    }

    public function store(StoreDemandaRequest $request): JsonResponse
    {
        $demanda = Demanda::create(array_merge(
            $request->validated(),
            [
                'tipo_cadastro' => TipoCadastro::Manual,
                'criado_por' => auth()->id(),
            ]
        ));

        return response()->json(['ok' => true, 'id' => $demanda->id]);
    }

    public function update(UpdateDemandaRequest $request, Demanda $demanda): JsonResponse
    {
        $demanda->update($request->validated());

        return response()->json(['ok' => true]);
    }

    public function destroy(Demanda $demanda): RedirectResponse
    {
        abort_unless(auth()->user()->role->value === 'administrador', 403, 'Apenas administradores podem excluir demandas.');

        $demanda->delete();

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' removida.');
    }
}
