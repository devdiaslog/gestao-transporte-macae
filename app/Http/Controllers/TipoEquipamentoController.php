<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoEquipamentoRequest;
use App\Http\Requests\UpdateTipoEquipamentoRequest;
use App\Models\TipoEquipamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TipoEquipamentoController extends Controller
{
    public function index(Request $request): View
    {
        $tipos = TipoEquipamento::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('tipos_equipamentos.index', compact('tipos'));
    }

    public function export(Request $request): Response
    {
        $tipos = TipoEquipamento::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'tipos_equipamentos_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Nome', 'Status', 'Cadastrado em']])
            ->concat($tipos->map(fn (TipoEquipamento $tipo) => [
                $tipo->nome,
                $tipo->status ? 'Ativo' : 'Inativo',
                $tipo->created_at->format('d/m/Y H:i'),
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
        return view('tipos_equipamentos.create');
    }

    public function store(StoreTipoEquipamentoRequest $request): RedirectResponse
    {
        TipoEquipamento::create($request->validated());

        return redirect()->route('tipos-equipamentos.index')
            ->with('success', 'Tipo de equipamento criado com sucesso.');
    }

    public function edit(TipoEquipamento $tipoEquipamento): View
    {
        return view('tipos_equipamentos.edit', compact('tipoEquipamento'));
    }

    public function update(UpdateTipoEquipamentoRequest $request, TipoEquipamento $tipoEquipamento): RedirectResponse
    {
        $tipoEquipamento->update($request->validated());

        return redirect()->route('tipos-equipamentos.index')
            ->with('success', 'Tipo de equipamento atualizado com sucesso.');
    }

    public function destroy(TipoEquipamento $tipoEquipamento): RedirectResponse
    {
        $tipoEquipamento->delete();

        return redirect()->route('tipos-equipamentos.index')
            ->with('success', 'Tipo de equipamento removido com sucesso.');
    }
}
