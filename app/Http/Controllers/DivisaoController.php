<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDivisaoRequest;
use App\Http\Requests\UpdateDivisaoRequest;
use App\Models\Divisao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DivisaoController extends Controller
{
    public function index(Request $request): View
    {
        $divisoes = Divisao::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('divisoes.index', compact('divisoes'));
    }

    public function export(Request $request): Response
    {
        $divisoes = Divisao::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'divisoes_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Nome', 'Status', 'Cadastrado em']])
            ->concat($divisoes->map(fn (Divisao $divisao) => [
                $divisao->nome,
                $divisao->status ? 'Ativo' : 'Inativo',
                $divisao->created_at->format('d/m/Y H:i'),
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
        return view('divisoes.create');
    }

    public function store(StoreDivisaoRequest $request): RedirectResponse
    {
        Divisao::create($request->validated());

        return redirect()->route('divisoes.index')
            ->with('success', 'Divisão criada com sucesso.');
    }

    public function edit(Divisao $divisao): View
    {
        return view('divisoes.edit', compact('divisao'));
    }

    public function update(UpdateDivisaoRequest $request, Divisao $divisao): RedirectResponse
    {
        $divisao->update($request->validated());

        return redirect()->route('divisoes.index')
            ->with('success', 'Divisão atualizada com sucesso.');
    }

    public function destroy(Divisao $divisao): RedirectResponse
    {
        $divisao->delete();

        return redirect()->route('divisoes.index')
            ->with('success', 'Divisão removida com sucesso.');
    }
}
