<?php

namespace App\Http\Controllers;

use App\Models\DashboardSnapshot;
use App\Services\BigcoreService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function status(): View
    {
        $snapshot = DashboardSnapshot::latest('capturado_em')->first();

        $historico = DashboardSnapshot::query()
            ->latest('capturado_em')
            ->limit(48)
            ->get(['capturado_em', 'dados'])
            ->reverse()
            ->values();

        return view('dashboard.status', [
            'snapshot' => $snapshot,
            'historico' => $historico,
        ]);
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

        $agrupado = collect($veiculos)
            ->filter(fn ($v) => ! empty($v['availability']['state']))
            ->groupBy(fn ($v) => $v['availability']['state'])
            ->map(function ($grupo, $status) {
                $mediaMins = $grupo->map(function ($v) {
                    $stateStart = $v['state']['stateStart'] ?? null;
                    if (! $stateStart) {
                        return 0;
                    }

                    return now()->diffInMinutes(Carbon::parse($stateStart));
                })->avg();

                return [
                    'status' => $status,
                    'quantidade' => $grupo->count(),
                    'media_horas' => round($mediaMins / 60, 1),
                ];
            })
            ->values()
            ->toArray();

        DashboardSnapshot::create([
            'capturado_em' => now(),
            'dados' => $agrupado,
        ]);

        return response()->json([
            'ok' => true,
            'capturado' => now()->toDateTimeString(),
            'total_status' => count($agrupado),
        ]);
    }
}
