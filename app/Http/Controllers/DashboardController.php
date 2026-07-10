<?php

namespace App\Http\Controllers;

use App\Models\DashboardSnapshot;
use App\Services\BigcoreService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function graficos(): View
    {
        $snapshot = DashboardSnapshot::latest('capturado_em')->first();

        return view('dashboard.graficos', compact('snapshot'));
    }

    public function status(): View
    {
        $snapshot = DashboardSnapshot::latest('capturado_em')->first();

        $anterior = $snapshot
            ? DashboardSnapshot::query()
                ->where('capturado_em', '<', $snapshot->capturado_em)
                ->latest('capturado_em')
                ->first()
            : null;

        return view('dashboard.status', compact('snapshot', 'anterior'));
    }

    public function capturarStatus(Request $request, BigcoreService $bigcore): JsonResponse
    {
        if ($request->input('key') !== config('services.bigcore.sync_key') || ! config('services.bigcore.sync_key')) {
            abort(403);
        }

        $veiculos = $bigcore->buscarTodos();

        if (empty($veiculos)) {
            return response()->json(['ok' => false, 'erro' => 'Sem dados da API.'], 500);
        }

        $agora = now();

        // Estado do rastreador direto do Vfleets (mais preciso que o E-log)
        $posicoes = DB::table('posicao_veiculos')
            ->select('license_plate', 'tracker_state', 'state_since', 'position_at')
            ->get()
            ->keyBy('license_plate');

        // Snapshot anterior para calcular status_desde por veículo
        $snapAnterior = DashboardSnapshot::latest('capturado_em')->first();
        $anteriorPorCm = [];
        foreach ($snapAnterior?->dados ?? [] as $grupo) {
            foreach ($grupo['veiculos'] ?? [] as $v) {
                $anteriorPorCm[$v['cm']] = [
                    'status' => $grupo['status'],
                    'document_code' => $v['document_code'] ?? null,
                    'status_desde' => $v['status_desde'] ?? null,
                ];
            }
        }

        $ignorar = ['Em Trânsito', 'Em Operação Interna'];

        $agrupado = collect($veiculos)
            ->filter(fn ($v) => ($v['divisionId'] ?? null) === 114
                && ! empty($v['observationOne'])
                && ! in_array($v['observationOne'], $ignorar))
            ->groupBy(fn ($v) => $v['observationOne'] === 'Início de Carga' ? 'Ag-Carregamento' : $v['observationOne'])
            ->map(function ($grupo, $status) use ($agora, $posicoes, $anteriorPorCm) {
                $itens = $grupo->map(function ($v) use ($agora, $posicoes, $anteriorPorCm, $status) {
                    $cm = $v['fleetCode'] ?? '';
                    $placa = $v['licensePlate'] ?? '';

                    // Rastreador via posicao_veiculos (Vfleets)
                    $posicao = $posicoes->get($placa);
                    $trackerEstado = match ($posicao?->tracker_state) {
                        'Em Movimento' => 1,
                        'Parado' => 0,
                        default => -1,
                    };
                    $trackerMinutos = $posicao?->state_since
                        ? (int) abs(Carbon::parse($posicao->state_since)->diffInMinutes($agora))
                        : 0;

                    // Sem sinal: último envio há mais de 3h → forçar estado e usar tempo desde o último sinal
                    if ($trackerEstado !== -1 && $posicao?->position_at) {
                        $ultimoSinal = Carbon::parse($posicao->position_at);
                        if ($ultimoSinal->diffInHours($agora) >= 3) {
                            $trackerEstado = -1;
                            $trackerMinutos = (int) abs($ultimoSinal->diffInMinutes($agora));
                        }
                    }

                    // Tempo no status operacional via comparação com snapshot anterior
                    $documentCode = $v['document']['documentCode']
                        ?? $v['loadScheduleCurrent']['documentCode']
                        ?? null;

                    $ant = $anteriorPorCm[$cm] ?? null;
                    $docMudou = $documentCode !== null
                        && ($ant['document_code'] ?? null) !== null
                        && $ant['document_code'] !== $documentCode;

                    $statusDesde = ($ant
                        && ($ant['status'] ?? '') === $status
                        && ! $docMudou
                        && ! empty($ant['status_desde'])
                    )
                        ? $ant['status_desde']
                        : $agora->toIso8601String();

                    $minutos = (int) abs(Carbon::parse($statusDesde)->diffInMinutes($agora));

                    return [
                        'cm' => $cm,
                        'placa' => $placa,
                        'minutos' => $minutos,
                        'status_desde' => $statusDesde,
                        'document_code' => $documentCode,
                        'tracker_estado' => $trackerEstado,
                        'tracker_minutos' => $trackerMinutos,
                    ];
                })->sortByDesc('minutos')->values();

                $totalMinutos = $itens->sum('minutos');
                $mediaMinutos = (int) round($itens->avg('minutos') ?? 0);
                $top1 = $itens->first();

                return [
                    'status' => $status,
                    'quantidade' => $itens->count(),
                    'media_minutos' => $mediaMinutos,
                    'media_horas' => round($mediaMinutos / 60, 1),
                    'top1' => $top1 ? [
                        'cm' => $top1['cm'],
                        'placa' => $top1['placa'],
                        'minutos' => $top1['minutos'],
                        'pct' => $totalMinutos > 0 ? round(($top1['minutos'] / $totalMinutos) * 100, 1) : 0,
                    ] : null,
                    'veiculos' => $itens->take(15)->toArray(),
                ];
            })
            ->values()
            ->toArray();

        DashboardSnapshot::create([
            'capturado_em' => $agora,
            'dados' => $agrupado,
        ]);

        return response()->json([
            'ok' => true,
            'capturado' => $agora->toDateTimeString(),
            'total_status' => count($agrupado),
        ]);
    }
}
