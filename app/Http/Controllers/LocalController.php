<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocalRequest;
use App\Http\Requests\UpdateLocalRequest;
use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocalController extends Controller
{
    public function index(Request $request): View
    {
        $locais = Local::query()
            ->when($request->filled('q'), fn ($q) => $q->where('nome', 'like', '%'.$request->input('q').'%'))
            ->when($request->filled('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();

        return view('locais.index', compact('locais'));
    }

    public function store(StoreLocalRequest $request): JsonResponse
    {
        $local = Local::create([
            'nome' => $request->validated()['nome'],
            'ativo' => $request->boolean('ativo', true),
            'precisa_agendamento' => $request->boolean('precisa_agendamento', false),
        ]);

        return response()->json(['ok' => true, 'id' => $local->id]);
    }

    public function update(UpdateLocalRequest $request, Local $local): JsonResponse
    {
        $local->update([
            'nome' => $request->validated()['nome'],
            'ativo' => $request->boolean('ativo', true),
            'precisa_agendamento' => $request->boolean('precisa_agendamento', false),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Local $local): RedirectResponse
    {
        $local->delete();

        return redirect()->route('locais.index')
            ->with('success', 'Local "'.$local->nome.'" removido.');
    }
}
