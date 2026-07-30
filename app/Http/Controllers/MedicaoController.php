<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicaoRequest;
use App\Http\Requests\UpdateMedicaoRequest;
use App\Models\Medicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicaoController extends Controller
{
    public function index(Request $request): View
    {
        $medicoes = Medicao::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome_medicao', 'like', '%'.$request->search.'%'))
            ->orderByDesc('data_inicio')
            ->paginate(10)
            ->withQueryString();

        return view('medicoes.index', compact('medicoes'));
    }

    public function create(): View
    {
        return view('medicoes.create');
    }

    public function store(StoreMedicaoRequest $request): RedirectResponse
    {
        Medicao::create($request->validated());

        return redirect()->route('medicoes.index')
            ->with('success', 'Medição criada com sucesso.');
    }

    public function edit(Medicao $medicao): View
    {
        return view('medicoes.edit', compact('medicao'));
    }

    public function update(UpdateMedicaoRequest $request, Medicao $medicao): RedirectResponse
    {
        $medicao->update($request->validated());

        return redirect()->route('medicoes.index')
            ->with('success', 'Medição atualizada com sucesso.');
    }

    public function destroy(Medicao $medicao): RedirectResponse
    {
        $medicao->delete();

        return redirect()->route('medicoes.index')
            ->with('success', 'Medição removida com sucesso.');
    }
}
