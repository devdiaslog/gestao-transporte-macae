<?php

namespace App\Http\Controllers;

use App\Models\Ocorrencia;
use App\Models\Reporte;
use App\Models\ReporteItem;
use App\Models\TipoOcorrencia;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MetricasController extends Controller
{
    public function index(Request $request, string $periodo = 'hoje'): View
    {
        $periodo = in_array($periodo, ['hoje', '7', '30', '90']) ? $periodo : 'hoje';

        $inicio = $periodo === 'hoje' ? now()->startOfDay() : now()->subDays((int) $periodo)->startOfDay();
        $fim = now()->endOfDay();

        // ── Reportes ──────────────────────────────────────────────────────────

        $reportes = Reporte::query()
            ->where('status', 'publicado')
            ->whereBetween('data_hora_emissao', [$inicio, $fim])
            ->withCount('itens')
            ->with('creator')
            ->get();

        $rankingReportes = $reportes
            ->groupBy('created_by')
            ->map(function ($grupo, $userId) {
                return [
                    'user_id' => $userId,
                    'user' => $grupo->first()->creator?->name ?? 'Desconhecido',
                    'reportes' => $grupo->count(),
                    'veiculos' => $grupo->sum('itens_count'),
                    'media' => round($grupo->avg('itens_count'), 1),
                ];
            })
            ->sortByDesc('reportes')
            ->values();

        // Status operacional por usuário [user_id => [status => total]]
        $statusRows = ReporteItem::query()
            ->select('reporte_itens.status_operacional', 'reportes.created_by')
            ->selectRaw('COUNT(*) as total')
            ->join('reportes', 'reportes.id', '=', 'reporte_itens.reporte_id')
            ->where('reportes.status', 'publicado')
            ->whereBetween('reportes.data_hora_emissao', [$inicio, $fim])
            ->groupBy('reportes.created_by', 'reporte_itens.status_operacional')
            ->get();

        $statusPorUser = $statusRows
            ->groupBy('created_by')
            ->map(fn ($rows) => $rows->pluck('total', 'status_operacional'));

        $todosStatus = $statusRows
            ->pluck('status_operacional')
            ->unique()
            ->sort()
            ->values();

        $evolucaoReportes = $reportes
            ->groupBy(fn ($r) => $r->data_hora_emissao->format('Y-m-d'))
            ->map(fn ($g) => $g->count())
            ->sortKeys();

        // ── Ocorrências ───────────────────────────────────────────────────────

        $ocorrencias = Ocorrencia::query()
            ->whereBetween('data_hora_inicio', [$inicio, $fim])
            ->with('creator', 'tipo')
            ->get();

        $rankingOcorrencias = $ocorrencias
            ->groupBy('created_by')
            ->map(function ($grupo) {
                $fechadas = $grupo->filter(fn ($o) => ! is_null($o->data_hora_fim))->count();
                $total = $grupo->count();

                $tipoTop = $grupo
                    ->groupBy('id_tipo')
                    ->map->count()
                    ->sortDesc()
                    ->keys()
                    ->first();

                $nomeTipoTop = $grupo
                    ->first(fn ($o) => $o->id_tipo === $tipoTop)
                    ?->tipo?->descricao;

                return [
                    'user' => $grupo->first()->creator?->name ?? 'Desconhecido',
                    'total' => $total,
                    'abertas' => $total - $fechadas,
                    'fechadas' => $fechadas,
                    'pct' => $total > 0 ? round(($fechadas / $total) * 100) : 0,
                    'tipo_top_nome' => $nomeTipoTop,
                ];
            })
            ->sortByDesc('total')
            ->values();

        // [user_id => [id_tipo => total]]
        $tiposPorUser = $ocorrencias
            ->groupBy('created_by')
            ->map(fn ($rows) => $rows->groupBy('id_tipo')->map->count());

        // Mapear user_id → nome para o grid de tipos
        $nomesUsersOcorrencias = $ocorrencias
            ->groupBy('created_by')
            ->map(fn ($rows) => $rows->first()->creator?->name ?? 'Desconhecido');

        $todosTipos = TipoOcorrencia::query()
            ->orderBy('descricao')
            ->get()
            ->keyBy('id_tipo');

        $evolucaoOcorrencias = $ocorrencias
            ->groupBy(fn ($o) => $o->data_hora_inicio->format('Y-m-d'))
            ->map(fn ($g) => $g->count())
            ->sortKeys();

        // ── KPIs ──────────────────────────────────────────────────────────────

        $kpis = [
            'reportes_total' => $reportes->count(),
            'veiculos_cobertos' => $reportes->sum('itens_count'),
            'ocorrencias_total' => $ocorrencias->count(),
            'ocorrencias_abertas' => $ocorrencias->filter(fn ($o) => is_null($o->data_hora_fim))->count(),
        ];

        return view('metricas.index', compact(
            'periodo',
            'kpis',
            'rankingReportes',
            'statusPorUser',
            'todosStatus',
            'evolucaoReportes',
            'rankingOcorrencias',
            'tiposPorUser',
            'nomesUsersOcorrencias',
            'todosTipos',
            'evolucaoOcorrencias',
        ));
    }
}
