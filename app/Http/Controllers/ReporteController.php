<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateReporteRequest;
use App\Models\Equipamento;
use App\Models\Reporte;
use App\Models\ReporteItem;
use App\Models\StatusOperacional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $query = ReporteItem::query()
            ->with(['reporte.creator'])
            ->whereHas('reporte')
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = '%'.$request->busca.'%';
                $q->where(function ($q) use ($busca) {
                    $q->where('prefixo', 'like', $busca)
                        ->orWhereHas('reporte', fn ($r) => $r->where('nome', 'like', $busca)
                            ->orWhere('numero_reporte', 'like', $busca));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->whereHas('reporte', fn ($r) => $r->where('status', $request->status)))
            ->when($request->filled('data_inicio'), fn ($q) => $q->whereHas('reporte', fn ($r) => $r->whereDate('data_hora_emissao', '>=', $request->data_inicio)))
            ->when($request->filled('data_fim'), fn ($q) => $q->whereHas('reporte', fn ($r) => $r->whereDate('data_hora_emissao', '<=', $request->data_fim)))
            ->orderByDesc(
                Reporte::select('data_hora_emissao')
                    ->whereColumn('reportes.id', 'reporte_itens.reporte_id')
                    ->limit(1)
            );

        if ($request->boolean('export')) {
            $this->export($query->get());
        }

        $itens = $query->paginate(10)->withQueryString();
        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $prefixosMotorizados = Equipamento::where('tipo_id', 1)
            ->whereNull('deleted_at')
            ->orderBy('prefixo')
            ->get(['prefixo', 'placa']);

        return view('reportes.index', compact('itens', 'statusOperacionais', 'prefixosMotorizados'));
    }

    public function create(): View
    {
        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $prefixosMotorizados = Equipamento::where('tipo_id', 1)
            ->whereNull('deleted_at')
            ->orderBy('prefixo')
            ->get(['prefixo', 'placa']);

        return view('reportes.create', compact('statusOperacionais', 'prefixosMotorizados'));
    }

    public function store(CreateReporteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $now = Carbon::now();
        $numero = $this->gerarNumero($now);

        $reporte = Reporte::create([
            'numero_reporte' => $numero,
            'nome' => $validated['nome'],
            'status' => $validated['salvar_como'],
            'data_hora_emissao' => $now,
            'created_by' => auth()->id(),
        ]);

        $itens = collect($validated['itens'])
            ->filter(fn ($item) => ! empty(array_filter($item)))
            ->map(fn ($item) => [
                'reporte_id' => $reporte->id,
                'prefixo' => $item['prefixo'] ?? null,
                'documento' => $item['documento'] ?? null,
                'status_operacional' => $item['status_operacional'] ?? null,
                'tempo_parado' => $item['tempo_parado'] ?? null,
                'data_hora_previsao' => $item['data_hora_previsao'] ?? null,
                'primeiro_contato' => $item['primeiro_contato'] ?? null,
                'segundo_contato' => $item['segundo_contato'] ?? null,
                'observacao' => $item['observacao'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if (! empty($itens)) {
            ReporteItem::insert($itens);
        }

        $msg = $validated['salvar_como'] === 'rascunho'
            ? 'Rascunho salvo com sucesso.'
            : 'Reporte publicado com sucesso.';

        return redirect()->route('reportes.index')->with('success', $msg);
    }

    public function show(Reporte $reporte): View
    {
        abort_if($reporte->status === 'rascunho', 403, 'Rascunhos não geram relatório.');

        $reporte->load(['itens', 'creator']);

        return view('reportes.show', compact('reporte'));
    }

    public function data(Reporte $reporte): JsonResponse
    {
        $reporte->load('itens');

        return response()->json([
            'id' => $reporte->id,
            'nome' => $reporte->nome,
            'status' => $reporte->status,
            'numero' => $reporte->numero_reporte,
            'itens' => $reporte->itens->map(fn (ReporteItem $i) => [
                'prefixo' => $i->prefixo,
                'documento' => $i->documento,
                'status_operacional' => $i->status_operacional,
                'tempo_parado' => $i->tempo_parado,
                'data_hora_previsao' => $i->data_hora_previsao,
                'primeiro_contato' => $i->primeiro_contato,
                'segundo_contato' => $i->segundo_contato,
                'observacao' => $i->observacao,
            ]),
        ]);
    }

    public function edit(Reporte $reporte): View
    {
        $reporte->load('itens');
        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $prefixosMotorizados = Equipamento::where('tipo_id', 1)
            ->whereNull('deleted_at')
            ->orderBy('prefixo')
            ->get(['prefixo', 'placa']);

        return view('reportes.edit', compact('reporte', 'statusOperacionais', 'prefixosMotorizados'));
    }

    public function update(CreateReporteRequest $request, Reporte $reporte): RedirectResponse
    {
        $validated = $request->validated();

        $reporte->update([
            'nome' => $validated['nome'],
            'status' => $validated['salvar_como'],
        ]);

        $now = Carbon::now();

        $reporte->itens()->delete();

        $itens = collect($validated['itens'])
            ->filter(fn ($item) => ! empty(array_filter($item)))
            ->map(fn ($item) => [
                'reporte_id' => $reporte->id,
                'prefixo' => $item['prefixo'] ?? null,
                'documento' => $item['documento'] ?? null,
                'status_operacional' => $item['status_operacional'] ?? null,
                'tempo_parado' => $item['tempo_parado'] ?? null,
                'data_hora_previsao' => $item['data_hora_previsao'] ?? null,
                'primeiro_contato' => $item['primeiro_contato'] ?? null,
                'segundo_contato' => $item['segundo_contato'] ?? null,
                'observacao' => $item['observacao'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if (! empty($itens)) {
            ReporteItem::insert($itens);
        }

        $msg = $validated['salvar_como'] === 'rascunho'
            ? 'Rascunho atualizado com sucesso.'
            : 'Reporte atualizado com sucesso.';

        return redirect()->route('reportes.index')->with('success', $msg);
    }

    public function destroy(Reporte $reporte): RedirectResponse
    {
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte excluído com sucesso.');
    }

    private function export(Collection $itens): never
    {
        $tz = config('app.timezone');

        $rows = collect([['Nº Reporte', 'Nome', 'Status Reporte', 'Prefixo', 'Documento', 'Status Operacional', 'Tempo Parado', 'Previsão', '1º Contato', '2º Contato', 'Observação', 'Data/Hora Emissão', 'Emitido Por']])
            ->concat($itens->map(fn (ReporteItem $i) => [
                $i->reporte?->numero_reporte ?? '',
                $i->reporte?->nome ?? '',
                $i->reporte?->status ?? '',
                $i->prefixo ?? '',
                $i->documento ?? '',
                $i->status_operacional ?? '',
                $i->tempo_parado ?? '',
                $i->data_hora_previsao ? \Carbon\Carbon::parse($i->data_hora_previsao)->format('d/m/Y H:i') : '',
                $i->primeiro_contato ?? '',
                $i->segundo_contato ?? '',
                $i->observacao ?? '',
                $i->reporte?->data_hora_emissao?->setTimezone($tz)->format('d/m/Y H:i') ?? '',
                $i->reporte?->creator?->name ?? '',
            ]));

        $csv = "\xEF\xBB\xBF".implode("\n", $rows->map(fn ($r) => implode(';', array_map(fn ($c) => '"'.str_replace('"', '""', $c).'"', $r)))->all());

        response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reportes-'.now()->format('Ymd-His').'.csv"',
        ])->send();

        exit;
    }

    private function gerarNumero(Carbon $now): string
    {
        $prefix = $now->format('Ymd');

        $ultimo = Reporte::where('numero_reporte', 'like', $prefix.'-%')
            ->max('numero_reporte');

        $seq = $ultimo ? ((int) substr($ultimo, -3)) + 1 : 1;

        return $prefix.'-'.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
