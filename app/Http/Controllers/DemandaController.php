<?php

namespace App\Http\Controllers;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Http\Requests\StoreDemandaRequest;
use App\Http\Requests\UpdateDemandaRequest;
use App\Models\Demanda;
use App\Models\Equipamento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DemandaController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $filtroKeys = ['q', 'status', 'tipo', 'prefixo', 'data_de', 'data_ate', 'prazo', 'prazo_de', 'prazo_ate'];

        if ($request->boolean('reset')) {
            session()->forget('demandas.filtros');

            return redirect()->route('demandas.index');
        }

        $temFiltro = $request->hasAny($filtroKeys) || $request->has('page');

        if (! $temFiltro) {
            $salvos = session('demandas.filtros', []);
            if (! empty($salvos)) {
                return redirect()->route('demandas.index', $salvos);
            }
        } elseif ($request->hasAny($filtroKeys)) {
            session(['demandas.filtros' => $request->only($filtroKeys)]);
        }

        $search = $request->input('q');
        $status = $request->input('status', 'active');
        $tipo = $request->input('tipo');
        $prefixo = $request->input('prefixo');
        $dataDE = $request->input('data_de');
        $dataAte = $request->input('data_ate');
        $prazo = $request->input('prazo');
        $prazoDE = $request->input('prazo_de');
        $prazoAte = $request->input('prazo_ate');

        $demandas = Demanda::query()
            ->with(['equipamento', 'criador'])
            ->when($search, fn ($q) => $q->where(fn ($sub) => $sub->where('numero_demanda', 'like', "%{$search}%")
                ->orWhere('documento_demanda', 'like', "%{$search}%")))
            ->when($status === 'active', fn ($q) => $q->whereIn('status_demanda', ['pendente', 'em_andamento']))
            ->when($status && $status !== 'active', fn ($q) => $q->where('status_demanda', $status))
            ->when($tipo, fn ($q) => $q->where('tipo_demanda', $tipo))
            ->when($prefixo, fn ($q) => $q->whereHas('equipamento', fn ($eq) => $eq->where('prefixo', 'like', "%{$prefixo}%")))
            ->when($dataDE, fn ($q) => $q->whereDate('created_at', '>=', $dataDE))
            ->when($dataAte, fn ($q) => $q->whereDate('created_at', '<=', $dataAte))
            ->when($prazo, fn ($q) => $this->filtrarPorPrazo($q, $prazo, $prazoDE, $prazoAte))
            ->when($prazo, fn ($q) => $q->orderBy('prazo_referencia'), fn ($q) => $q->latest())
            ->paginate(25)
            ->withQueryString();

        $equipamentos = Equipamento::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', 'Motorizado'))
            ->whereNotNull('prefixo')
            ->orderBy('prefixo')
            ->get(['id', 'prefixo', 'placa']);

        return view('demandas.index', compact('demandas', 'equipamentos', 'search', 'status', 'tipo', 'prefixo', 'dataDE', 'dataAte', 'prazo', 'prazoDE', 'prazoAte'));
    }

    /**
     * Aplica o filtro de prazo (vencimento) a demandas ainda em aberto.
     */
    private function filtrarPorPrazo(Builder $query, string $prazo, ?string $de = null, ?string $ate = null): Builder
    {
        $agora = now();

        $query->whereNotNull('prazo_referencia')
            ->whereNotIn('status_demanda', ['finalizado', 'cancelada']);

        return match ($prazo) {
            'vencidas' => $query->where('prazo_referencia', '<', $agora),
            'hoje' => $query->whereDate('prazo_referencia', $agora->toDateString()),
            '24h' => $query->whereBetween('prazo_referencia', [$agora, $agora->copy()->addDay()]),
            '3d' => $query->whereBetween('prazo_referencia', [$agora, $agora->copy()->addDays(3)]),
            '7d' => $query->whereBetween('prazo_referencia', [$agora, $agora->copy()->addDays(7)]),
            'personalizado' => $query
                ->when($de, fn ($q) => $q->whereDate('prazo_referencia', '>=', $de))
                ->when($ate, fn ($q) => $q->whereDate('prazo_referencia', '<=', $ate)),
            default => $query,
        };
    }

    public function export(Request $request): Response
    {
        $status = $request->input('status');

        $demandas = Demanda::query()
            ->with(['equipamento', 'criador'])
            ->when($request->input('q'), fn ($q, $v) => $q->where(fn ($sub) => $sub->where('numero_demanda', 'like', "%{$v}%")
                ->orWhere('documento_demanda', 'like', "%{$v}%")))
            ->when($status === 'active', fn ($q) => $q->whereIn('status_demanda', ['pendente', 'em_andamento']))
            ->when($status && $status !== 'active', fn ($q) => $q->where('status_demanda', $status))
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('tipo_demanda', $v))
            ->when($request->input('prefixo'), fn ($q, $v) => $q->whereHas('equipamento', fn ($eq) => $eq->where('prefixo', 'like', "%{$v}%")))
            ->when($request->input('data_de'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('data_ate'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($request->input('prazo'), fn ($q, $v) => $this->filtrarPorPrazo($q, $v, $request->input('prazo_de'), $request->input('prazo_ate')))
            ->when($request->input('prazo'), fn ($q) => $q->orderBy('prazo_referencia'), fn ($q) => $q->latest())
            ->get();

        $fmt = fn ($dt) => $dt?->format('d/m/Y H:i') ?? '';

        $headers = [
            'Número', 'Tipo', 'Tipo Cadastro',
            'Veículo (Prefixo)', 'Veículo (Placa)',
            'Documento', 'Origem', 'Destino', 'Prazo Referência',
            'Início', 'Fim',
            'Status', 'Auditado', 'Criado por', 'Cadastrado em',
        ];

        $rows = $demandas->map(fn (Demanda $d) => [
            $d->numero_demanda,
            $d->tipo_demanda?->label() ?? '',
            $d->tipo_cadastro->label(),
            $d->equipamento?->prefixo ?? '',
            $d->equipamento?->placa ?? '',
            $d->documento_demanda ?? '',
            $d->origem ?? '',
            $d->destino ?? '',
            $fmt($d->prazo_referencia),
            $fmt($d->data_hora_inicio_demanda),
            $fmt($d->data_hora_fim_demanda),
            $d->status_demanda->label(),
            $d->status_auditoria ? 'Sim' : 'Não',
            $d->criador?->name ?? '',
            $fmt($d->created_at),
        ]);

        $csv = collect([$headers])
            ->concat($rows)
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n");

        $filename = 'demandas_'.now()->format('Y-m-d_H-i').'.csv';

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    public function store(StoreDemandaRequest $request): JsonResponse
    {
        $demanda = Demanda::create(array_merge(
            $request->validated(),
            [
                'tipo_cadastro' => TipoCadastro::Manual,
                'status_demanda' => StatusDemanda::Pendente,
                'criado_por' => auth()->id(),
            ]
        ));

        return response()->json(['ok' => true, 'id' => $demanda->id]);
    }

    public function update(UpdateDemandaRequest $request, Demanda $demanda): JsonResponse
    {
        $demanda->update($request->validated());

        return response()->json(['ok' => true]);
    }

    public function cancelar(Demanda $demanda): RedirectResponse
    {
        abort_if($demanda->status_demanda === StatusDemanda::Finalizado, 403, 'Demanda já finalizada.');

        $demanda->update(['status_demanda' => StatusDemanda::Cancelada]);

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' cancelada.');
    }

    public function finalizar(Request $request, Demanda $demanda): RedirectResponse
    {
        abort_if($demanda->data_hora_inicio_demanda === null, 403, 'Demanda precisa ser iniciada antes de finalizar.');

        $request->validate(['data_hora_fim_demanda' => ['nullable', 'date']]);

        $demanda->update([
            'data_hora_fim_demanda' => $request->filled('data_hora_fim_demanda')
                ? $request->date('data_hora_fim_demanda')
                : now(),
            'status_demanda' => StatusDemanda::Finalizado,
        ]);

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' finalizada.');
    }

    public function auditar(Demanda $demanda): RedirectResponse
    {
        $demanda->update(['status_auditoria' => ! $demanda->status_auditoria]);

        return redirect()->back()->with('success', 'Auditoria da demanda #'.$demanda->numero_demanda.' atualizada.');
    }

    public function destroy(Demanda $demanda): RedirectResponse
    {
        abort_unless(auth()->user()->role->value === 'administrador', 403, 'Apenas administradores podem excluir demandas.');

        $demanda->delete();

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' removida.');
    }
}
