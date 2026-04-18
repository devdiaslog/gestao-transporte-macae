<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubDivisaoRequest;
use App\Http\Requests\UpdateSubDivisaoRequest;
use App\Models\Divisao;
use App\Models\SubDivisao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SubDivisaoController extends Controller
{
    public function index(Request $request): View
    {
        $subDivisoes = SubDivisao::query()
            ->with('divisao')
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('subdivisoes.index', compact('subDivisoes'));
    }

    public function export(Request $request): Response
    {
        $subDivisoes = SubDivisao::query()
            ->with('divisao')
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'subdivisoes_'.now()->format('Y-m-d_H-i').'.csv';

        $csv = collect([['Nome', 'Divisão', 'Status', 'Cadastrado em']])
            ->concat($subDivisoes->map(fn (SubDivisao $sub) => [
                $sub->nome,
                $sub->divisao?->nome ?? '',
                $sub->status ? 'Ativo' : 'Inativo',
                $sub->created_at->format('d/m/Y H:i'),
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
        $divisoes = Divisao::query()->where('status', true)->orderBy('nome')->get();

        return view('subdivisoes.create', compact('divisoes'));
    }

    public function store(StoreSubDivisaoRequest $request): RedirectResponse
    {
        SubDivisao::create($request->validated());

        return redirect()->route('subdivisoes.index')
            ->with('success', 'Subdivisão criada com sucesso.');
    }

    public function edit(SubDivisao $subDivisao): View
    {
        $divisoes = Divisao::query()->where('status', true)->orderBy('nome')->get();

        return view('subdivisoes.edit', compact('subDivisao', 'divisoes'));
    }

    public function update(UpdateSubDivisaoRequest $request, SubDivisao $subDivisao): RedirectResponse
    {
        $subDivisao->update($request->validated());

        return redirect()->route('subdivisoes.index')
            ->with('success', 'Subdivisão atualizada com sucesso.');
    }

    public function destroy(SubDivisao $subDivisao): RedirectResponse
    {
        $subDivisao->delete();

        return redirect()->route('subdivisoes.index')
            ->with('success', 'Subdivisão removida com sucesso.');
    }
}
