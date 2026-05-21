<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReporteRequest;
use App\Models\Reporte;
use App\Models\ReporteItem;
use App\Models\StatusOperacional;
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

        $itens = $query->paginate(50)->withQueryString();
        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();

        return view('reportes.index', compact('itens', 'statusOperacionais'));
    }

    public function store(StoreReporteRequest $request): RedirectResponse
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
                'status_operacional' => $item['status_operacional'] ?? null,
                'tempo_parado' => $item['tempo_parado'] ?? null,
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

    public function destroy(Reporte $reporte): RedirectResponse
    {
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte excluído com sucesso.');
    }

    private function export(Collection $itens): never
    {
        $tz = config('app.timezone');

        $rows = collect([['Nº Reporte', 'Nome', 'Status Reporte', 'Prefixo', 'Status Operacional', 'Tempo Parado', 'Observação', 'Data/Hora Emissão', 'Emitido Por']])
            ->concat($itens->map(fn (ReporteItem $i) => [
                $i->reporte?->numero_reporte ?? '',
                $i->reporte?->nome ?? '',
                $i->reporte?->status ?? '',
                $i->prefixo ?? '',
                $i->status_operacional ?? '',
                $i->tempo_parado ?? '',
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
        $count = Reporte::whereDate('data_hora_emissao', $now->toDateString())->count();

        return $prefix.'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
