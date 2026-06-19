<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipamentoRequest;
use App\Http\Requests\UpdateEquipamentoRequest;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\EquipamentoLog;
use App\Models\ModeloEquipamento;
use App\Models\Motorista;
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

        $destination = $request->input('redirect') === 'control-tower'
            ? route('control-tower.index')
            : route('equipamentos.index');

        return redirect($destination)
            ->with('success', 'Equipamento atualizado com sucesso.');
    }

    public function updateOperacional(Request $request, Equipamento $equipamento): RedirectResponse
    {
        $validated = $request->validate([
            'status_operacional' => ['nullable', 'string', 'max:255'],
            'documento_demanda' => ['nullable', 'string', 'max:255'],
            'observacao_operacional' => ['nullable', 'string'],
            'origem' => ['nullable', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'motorista_id' => ['nullable', 'integer', 'exists:motoristas,id'],
        ]);

        // Log de campos de texto
        $campos = [
            'status_operacional' => 'Status Operacional',
            'documento_demanda' => 'Documento de Demanda',
            'observacao_operacional' => 'Observação',
            'origem' => 'Origem',
            'destino' => 'Destino',
        ];

        foreach ($campos as $field => $label) {
            $anterior = $equipamento->$field ?: null;
            $novo = ! empty($validated[$field]) ? $validated[$field] : null;

            if ($anterior !== $novo) {
                EquipamentoLog::create([
                    'equipamento_id' => $equipamento->id,
                    'user_id' => auth()->id(),
                    'campo' => $label,
                    'valor_anterior' => $anterior,
                    'valor_novo' => $novo,
                ]);
            }
        }

        // Garante que o motorista não está alocado em outro equipamento
        $novoMotoId = ! empty($validated['motorista_id']) ? (int) $validated['motorista_id'] : null;
        if ($novoMotoId && $novoMotoId !== $equipamento->motorista_id) {
            $jaAlocado = Equipamento::where('motorista_id', $novoMotoId)
                ->where('id', '!=', $equipamento->id)
                ->exists();

            if ($jaAlocado) {
                return redirect()->back()->withErrors([
                    'motorista_id' => 'Este condutor já está alocado em outro equipamento.',
                ]);
            }
        }

        // Log do condutor (nome, não ID)
        if ($equipamento->motorista_id !== $novoMotoId) {
            $nomeAnterior = $equipamento->motorista?->nome;
            $nomeNovo = $novoMotoId ? Motorista::find($novoMotoId)?->nome : null;

            EquipamentoLog::create([
                'equipamento_id' => $equipamento->id,
                'user_id' => auth()->id(),
                'campo' => 'Condutor',
                'valor_anterior' => $nomeAnterior,
                'valor_novo' => $nomeNovo,
            ]);
        }

        $validated['motorista_id'] = $novoMotoId;

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
