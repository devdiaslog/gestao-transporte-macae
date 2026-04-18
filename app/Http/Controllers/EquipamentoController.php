<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipamentoRequest;
use App\Http\Requests\UpdateEquipamentoRequest;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\ModeloEquipamento;
use App\Models\SubDivisao;
use App\Models\TipoEquipamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EquipamentoController extends Controller
{
    public function index(Request $request): View
    {
        $equipamentos = Equipamento::query()
            ->with(['tipo', 'modelo', 'divisao', 'subDivisao'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('placa', 'like', '%'.$request->search.'%')
                    ->orWhere('prefixo', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('tipo_id'), fn ($q) => $q->where('tipo_id', $request->tipo_id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $tipos = TipoEquipamento::query()->where('status', true)->orderBy('nome')->get();
        $divisoes = Divisao::query()->where('status', true)->orderBy('nome')->get();

        return view('equipamentos.index', compact('equipamentos', 'tipos', 'divisoes'));
    }

    public function export(Request $request): Response
    {
        $equipamentos = Equipamento::query()
            ->with(['tipo', 'modelo', 'divisao', 'subDivisao'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('placa', 'like', '%'.$request->search.'%')
                    ->orWhere('prefixo', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('tipo_id'), fn ($q) => $q->where('tipo_id', $request->tipo_id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'equipamentos_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Placa', 'Prefixo', 'ID Elog', 'Tipo', 'Modelo', 'Divisão', 'Subdivisão', 'Status', 'Cadastrado em']])
            ->concat($equipamentos->map(fn (Equipamento $eq) => [
                $eq->placa,
                $eq->prefixo ?? '',
                $eq->id_elog ?? '',
                $eq->tipo?->nome ?? '',
                $eq->modelo?->nome ?? '',
                $eq->divisao?->nome ?? '',
                $eq->subDivisao?->nome ?? '',
                $eq->status ? 'Ativo' : 'Inativo',
                $eq->created_at->format('d/m/Y H:i'),
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
        $modelos = ModeloEquipamento::query()->where('status', true)->orderBy('nome')->get();
        $divisoes = Divisao::query()->where('status', true)->orderBy('nome')->get();
        $subDivisoes = SubDivisao::query()->where('status', true)->orderBy('nome')->get();

        return view('equipamentos.create', compact('tipos', 'modelos', 'divisoes', 'subDivisoes'));
    }

    public function store(StoreEquipamentoRequest $request): RedirectResponse
    {
        Equipamento::create($request->validated());

        return redirect()->route('equipamentos.index')
            ->with('success', 'Equipamento criado com sucesso.');
    }

    public function edit(Equipamento $equipamento): View
    {
        $tipos = TipoEquipamento::query()->where('status', true)->orderBy('nome')->get();
        $modelos = ModeloEquipamento::query()->where('status', true)->orderBy('nome')->get();
        $divisoes = Divisao::query()->where('status', true)->orderBy('nome')->get();
        $subDivisoes = SubDivisao::query()->where('status', true)->orderBy('nome')->get();

        return view('equipamentos.edit', compact('equipamento', 'tipos', 'modelos', 'divisoes', 'subDivisoes'));
    }

    public function update(UpdateEquipamentoRequest $request, Equipamento $equipamento): RedirectResponse
    {
        $equipamento->update($request->validated());

        return redirect()->route('equipamentos.index')
            ->with('success', 'Equipamento atualizado com sucesso.');
    }

    public function updateOperacional(Request $request, Equipamento $equipamento): RedirectResponse
    {
        $validated = $request->validate([
            'status_operacional' => ['nullable', 'string', 'max:255'],
            'documento_demanda' => ['nullable', 'string', 'max:255'],
            'observacao_operacional' => ['nullable', 'string'],
        ]);

        $equipamento->update($validated);

        return redirect()->back()->with('success', 'Dados operacionais atualizados.');
    }

    public function destroy(Equipamento $equipamento): RedirectResponse
    {
        $equipamento->delete();

        return redirect()->route('equipamentos.index')
            ->with('success', 'Equipamento removido com sucesso.');
    }
}
