<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModeloEquipamentoRequest;
use App\Http\Requests\UpdateModeloEquipamentoRequest;
use App\Models\ModeloEquipamento;
use App\Models\TipoEquipamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ModeloEquipamentoController extends Controller
{
    public function index(Request $request): View
    {
        $modelos = ModeloEquipamento::query()
            ->with('tipoEquipamento')
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('tipo_equipamento_id'), fn ($q) => $q->where('tipo_equipamento_id', $request->tipo_equipamento_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $tipos = TipoEquipamento::query()->where('status', true)->orderBy('nome')->get();

        return view('modelos_equipamentos.index', compact('modelos', 'tipos'));
    }

    public function export(Request $request): Response
    {
        $modelos = ModeloEquipamento::query()
            ->with('tipoEquipamento')
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('tipo_equipamento_id'), fn ($q) => $q->where('tipo_equipamento_id', $request->tipo_equipamento_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'modelos_equipamentos_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Nome', 'Tipo', 'Status', 'Cadastrado em']])
            ->concat($modelos->map(fn (ModeloEquipamento $modelo) => [
                $modelo->nome,
                $modelo->tipoEquipamento?->nome ?? '',
                $modelo->status ? 'Ativo' : 'Inativo',
                $modelo->created_at->format('d/m/Y H:i'),
            ]))
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', $cell).'"', $row)))
            ->implode("\n");

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    public function create(): View
    {
        $tipos = TipoEquipamento::query()->where('status', true)->orderBy('nome')->get();

        return view('modelos_equipamentos.create', compact('tipos'));
    }

    public function store(StoreModeloEquipamentoRequest $request): RedirectResponse
    {
        ModeloEquipamento::create($request->validated());

        return redirect()->route('modelos-equipamentos.index')
            ->with('success', 'Modelo de equipamento criado com sucesso.');
    }

    public function edit(ModeloEquipamento $modeloEquipamento): View
    {
        $tipos = TipoEquipamento::query()->where('status', true)->orderBy('nome')->get();

        return view('modelos_equipamentos.edit', compact('modeloEquipamento', 'tipos'));
    }

    public function update(UpdateModeloEquipamentoRequest $request, ModeloEquipamento $modeloEquipamento): RedirectResponse
    {
        $modeloEquipamento->update($request->validated());

        return redirect()->route('modelos-equipamentos.index')
            ->with('success', 'Modelo de equipamento atualizado com sucesso.');
    }

    public function destroy(ModeloEquipamento $modeloEquipamento): RedirectResponse
    {
        $modeloEquipamento->delete();

        return redirect()->route('modelos-equipamentos.index')
            ->with('success', 'Modelo de equipamento removido com sucesso.');
    }
}
