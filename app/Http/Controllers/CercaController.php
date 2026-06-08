<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCercaRequest;
use App\Http\Requests\UpdateCercaRequest;
use App\Models\Cerca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CercaController extends Controller
{
    public function index(Request $request): View
    {
        $cercas = Cerca::query()
            ->when($request->filled('search'), fn ($q) => $q->where('nome', 'like', '%'.$request->search.'%')
                ->orWhere('atividade', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('cercas.index', compact('cercas'));
    }

    public function create(): View
    {
        $cercasExistentes = Cerca::query()
            ->whereNotNull('poligono')
            ->where('status', true)
            ->get(['id', 'nome', 'atividade', 'poligono'])
            ->map(fn (Cerca $c) => [
                'nome' => $c->nome,
                'atividade' => $c->atividade?->label(),
                'poligono' => $c->poligono,
            ])
            ->filter(fn (array $c) => is_array($c['poligono']) && count($c['poligono']) >= 3)
            ->values();

        return view('cercas.create', compact('cercasExistentes'));
    }

    public function store(StoreCercaRequest $request): RedirectResponse
    {
        Cerca::create($request->validated());

        return redirect()->route('cercas.index')
            ->with('success', 'Cerca criada com sucesso.');
    }

    public function edit(Cerca $cerca): View
    {
        return view('cercas.edit', compact('cerca'));
    }

    public function update(UpdateCercaRequest $request, Cerca $cerca): RedirectResponse
    {
        $cerca->update($request->validated());

        return redirect()->route('cercas.index')
            ->with('success', 'Cerca atualizada com sucesso.');
    }

    public function destroy(Cerca $cerca): RedirectResponse
    {
        $cerca->delete();

        return redirect()->route('cercas.index')
            ->with('success', 'Cerca removida com sucesso.');
    }
}
