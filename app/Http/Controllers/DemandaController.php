<?php

namespace App\Http\Controllers;

use App\Enums\TipoCadastro;
use App\Http\Requests\StoreDemandaRequest;
use App\Http\Requests\UpdateDemandaRequest;
use App\Models\Demanda;
use App\Models\Equipamento;
use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DemandaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('q');
        $status = $request->input('status');
        $tipo = $request->input('tipo');
        $prefixo = $request->input('prefixo');
        $dataDE = $request->input('data_de');
        $dataAte = $request->input('data_ate');

        $demandas = Demanda::query()
            ->with(['equipamento', 'localOrigem', 'localDestino', 'criador'])
            ->when($search, fn ($q) => $q->where('numero_demanda', 'like', "%{$search}%"))
            ->when($status, fn ($q) => $q->where('status_demanda', $status))
            ->when($tipo, fn ($q) => $q->where('tipo_demanda', $tipo))
            ->when($prefixo, fn ($q) => $q->whereHas('equipamento', fn ($eq) => $eq->where('prefixo', 'like', "%{$prefixo}%")))
            ->when($dataDE, fn ($q) => $q->whereDate('created_at', '>=', $dataDE))
            ->when($dataAte, fn ($q) => $q->whereDate('created_at', '<=', $dataAte))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $locais = Local::ativo()->orderBy('nome')->get();
        $equipamentos = Equipamento::query()
            ->whereHas('tipo', fn ($q) => $q->where('nome', 'Motorizado'))
            ->whereNotNull('prefixo')
            ->orderBy('prefixo')
            ->get(['id', 'prefixo', 'placa']);

        return view('demandas.index', compact('demandas', 'locais', 'equipamentos', 'search', 'status', 'tipo', 'prefixo', 'dataDE', 'dataAte'));
    }

    public function export(Request $request): Response
    {
        $demandas = Demanda::query()
            ->with(['equipamento', 'localOrigem', 'localDestino', 'criador'])
            ->when($request->input('q'), fn ($q, $v) => $q->where('numero_demanda', 'like', "%{$v}%"))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status_demanda', $v))
            ->when($request->input('tipo'), fn ($q, $v) => $q->where('tipo_demanda', $v))
            ->when($request->input('prefixo'), fn ($q, $v) => $q->whereHas('equipamento', fn ($eq) => $eq->where('prefixo', 'like', "%{$v}%")))
            ->when($request->input('data_de'), fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->input('data_ate'), fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->get();

        $fmt = fn ($dt) => $dt?->format('d/m/Y H:i') ?? '';

        $headers = [
            'Número', 'Tipo', 'Tipo Cadastro', 'Veículo (Prefixo)', 'Veículo (Placa)',
            'Origem', 'Destino',
            'Prazo', 'Agendamento',
            'Ini. Carregamento', 'Fim Carregamento',
            'Saída Origem', 'Chegada Destino',
            'Ini. Descarregamento', 'Fim Descarregamento',
            'Status', 'Criado por', 'Cadastrado em',
        ];

        $rows = $demandas->map(fn (Demanda $d) => [
            $d->numero_demanda,
            $d->tipo_demanda?->label() ?? '',
            $d->tipo_cadastro->label(),
            $d->equipamento?->prefixo ?? '',
            $d->equipamento?->placa ?? '',
            $d->localOrigem?->nome ?? '',
            $d->localDestino?->nome ?? '',
            $fmt($d->prazo_atendimento_demanda),
            $fmt($d->data_hora_agendamento),
            $fmt($d->data_hora_inicio_carregamento),
            $fmt($d->data_hora_fim_carregamento),
            $fmt($d->data_hora_saida_origem),
            $fmt($d->data_hora_chegada_destino),
            $fmt($d->data_hora_inicio_descarregamento),
            $fmt($d->data_hora_fim_descarregamento),
            $d->status_demanda->label(),
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

    public function destroy(Demanda $demanda): RedirectResponse
    {
        abort_unless(auth()->user()->role->value === 'administrador', 403, 'Apenas administradores podem excluir demandas.');

        $demanda->delete();

        return redirect()->back()->with('success', 'Demanda #'.$demanda->numero_demanda.' removida.');
    }
}
