<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMotoristaRequest;
use App\Http\Requests\UpdateMotoristaRequest;
use App\Models\ContatoMotorista;
use App\Models\Motorista;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MotoristaController extends Controller
{
    public function index(Request $request): View
    {
        $motoristas = Motorista::query()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('nome', 'like', '%'.$request->search.'%')
                    ->orWhere('matricula', 'like', '%'.$request->search.'%')
                    ->orWhere('cpf', 'like', '%'.$request->search.'%');
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('motoristas.index', compact('motoristas'));
    }

    public function create(): View
    {
        return view('motoristas.create');
    }

    public function store(StoreMotoristaRequest $request): RedirectResponse
    {
        $motorista = Motorista::create([
            'matricula' => $request->matricula,
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'cargo' => $request->cargo,
            'status' => $request->boolean('status'),
        ]);

        $this->syncContatos($motorista, $request->input('contatos', []));

        return redirect()->route('motoristas.index')
            ->with('success', 'Motorista cadastrado com sucesso.');
    }

    public function edit(Motorista $motorista): View
    {
        $motorista->load('contatos');

        return view('motoristas.edit', compact('motorista'));
    }

    public function update(UpdateMotoristaRequest $request, Motorista $motorista): RedirectResponse
    {
        $motorista->update([
            'matricula' => $request->matricula,
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'cargo' => $request->cargo,
            'status' => $request->boolean('status'),
        ]);

        $this->syncContatos($motorista, $request->input('contatos', []));

        return redirect()->route('motoristas.edit', $motorista)
            ->with('success', 'Motorista atualizado com sucesso.');
    }

    public function destroy(Motorista $motorista): RedirectResponse
    {
        $motorista->delete();

        return redirect()->route('motoristas.index')
            ->with('success', 'Motorista removido com sucesso.');
    }

    /** @param array<int, array<string, mixed>> $contatos */
    private function syncContatos(Motorista $motorista, array $contatos): void
    {
        $submittedIds = [];

        foreach ($contatos as $dados) {
            if (empty($dados['telefone']) && empty($dados['email'])) {
                continue;
            }

            $id = $dados['id'] ?? null;

            $contato = $id
                ? ContatoMotorista::find($id)
                : new ContatoMotorista(['motorista_id' => $motorista->id]);

            if (! $contato) {
                continue;
            }

            $contato->fill([
                'motorista_id' => $motorista->id,
                'telefone' => $dados['telefone'] ?? null,
                'email' => $dados['email'] ?? null,
                'status' => ! empty($dados['status']),
            ])->save();

            $submittedIds[] = $contato->id;
        }

        // Remove contatos não enviados no form
        $motorista->contatos()
            ->when(! empty($submittedIds), fn ($q) => $q->whereNotIn('id', $submittedIds))
            ->delete();
    }
}
