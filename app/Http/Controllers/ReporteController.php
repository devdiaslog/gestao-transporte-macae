<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReporteRequest;
use App\Models\Reporte;
use App\Models\StatusOperacional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function index(): View
    {
        $reportes = Reporte::query()
            ->with('creator')
            ->withCount('itens')
            ->latest('data_hora_emissao')
            ->paginate(15)
            ->withQueryString();

        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();

        return view('reportes.index', compact('reportes', 'statusOperacionais'));
    }

    public function store(StoreReporteRequest $request): RedirectResponse
    {
        $now = Carbon::now();
        $numero = $this->gerarNumero($now);

        $reporte = Reporte::create([
            'numero_reporte' => $numero,
            'data_hora_emissao' => $now,
            'created_by' => auth()->id(),
        ]);

        $itens = collect($request->validated()['itens'])
            ->filter(fn ($item) => ! empty(array_filter($item)))
            ->map(fn ($item) => [
                'prefixo' => $item['prefixo'] ?? null,
                'status_operacional' => $item['status_operacional'] ?? null,
                'tempo_parado' => $item['tempo_parado'] ?? null,
                'observacao' => $item['observacao'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        $reporte->itens()->insert(
            array_map(fn ($item) => array_merge($item, ['reporte_id' => $reporte->id]), $itens)
        );

        return redirect()->route('reportes.index')->with('success', 'Reporte criado com sucesso.');
    }

    public function destroy(Reporte $reporte): RedirectResponse
    {
        $reporte->delete();

        return redirect()->route('reportes.index')->with('success', 'Reporte excluído com sucesso.');
    }

    private function gerarNumero(Carbon $now): string
    {
        $prefix = $now->format('Ymd');
        $count = Reporte::whereDate('data_hora_emissao', $now->toDateString())->count();

        return $prefix.'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }
}
