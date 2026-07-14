<?php

namespace App\Http\Controllers;

use App\Enums\StatusDemanda;
use App\Enums\TipoCadastro;
use App\Enums\TipoDemanda;
use App\Models\CercaEvento;
use App\Models\DashboardSnapshot;
use App\Models\Demanda;
use App\Models\Equipamento;
use App\Models\StatusEvento;
use App\Models\TipoEquipamento;
use App\Services\BigcoreService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Registra demandas novas detectadas na resposta da API do E-log.
     * Ignora documentos já cadastrados e valores que não sejam numéricos de 9-10 dígitos.
     *
     * @param  array<int, array<string, mixed>>  $veiculos
     */
    private function registrarDemandasNovas(array $veiculos): int
    {
        // Apenas veículos da divisão Poli Macaé (divisionId 114)
        $veiculos = array_filter($veiculos, fn ($v) => ($v['divisionId'] ?? null) === 114);

        // Coleta todos os documentCodes válidos vindos da API
        $documentos = collect($veiculos)
            ->map(fn ($v) => $v['document']['documentCode'] ?? $v['loadScheduleCurrent']['documentCode'] ?? null)
            ->filter(fn ($d) => $d !== null && ctype_digit((string) $d) && strlen((string) $d) >= 9 && strlen((string) $d) <= 10)
            ->map(fn ($d) => (int) $d)
            ->unique()
            ->values();

        if ($documentos->isEmpty()) {
            return 0;
        }

        // Quais já estão cadastrados
        $existentes = Demanda::withTrashed()
            ->whereIn('numero_demanda', $documentos)
            ->pluck('numero_demanda')
            ->all();

        $novos = $documentos->diff($existentes);

        if ($novos->isEmpty()) {
            return 0;
        }

        // Mapa placa → equipamento_id para associar o veículo
        $placaParaId = Equipamento::query()
            ->whereNotNull('placa')
            ->pluck('id', 'placa')
            ->mapWithKeys(fn ($id, $placa) => [strtoupper($placa) => $id]);

        // Indexa veículos por documentCode para lookup rápido
        $veiculoPorDoc = collect($veiculos)
            ->filter(function ($v) {
                $d = $v['document']['documentCode'] ?? $v['loadScheduleCurrent']['documentCode'] ?? null;

                return $d !== null && ctype_digit((string) $d) && strlen((string) $d) >= 9 && strlen((string) $d) <= 10;
            })
            ->keyBy(fn ($v) => (int) ($v['document']['documentCode'] ?? $v['loadScheduleCurrent']['documentCode']));

        $criados = 0;

        foreach ($novos as $numeroDoc) {
            $veiculo = $veiculoPorDoc->get($numeroDoc);
            $placa = strtoupper($veiculo['licensePlate'] ?? '');
            $equipamentoId = $placa ? ($placaParaId->get($placa) ?? null) : null;

            $etaDocs = $veiculo['etaDocument']['etaDocuments'] ?? [];
            $origem = isset($etaDocs[0]) ? strtoupper(trim($etaDocs[0]['destinationName'] ?? '')) : null;
            $destino = isset($etaDocs[1]) ? strtoupper(trim($etaDocs[1]['destinationName'] ?? '')) : null;

            if ($destino === 'BMAC') {
                $tipo = TipoDemanda::Load;
            } elseif ($origem === 'BMAC') {
                $tipo = TipoDemanda::Backload;
            } else {
                $tipo = TipoDemanda::Transferencia;
            }

            Demanda::create([
                'numero_demanda' => $numeroDoc,
                'tipo_cadastro' => TipoCadastro::Integracao,
                'tipo_demanda' => $tipo,
                'status_demanda' => StatusDemanda::Pendente,
                'equipamento_id' => $equipamentoId,
                'origem' => $origem,
                'destino' => $destino,
            ]);

            $criados++;
        }

        return $criados;
    }

    public function tabela(): View
    {
        $agora = now();
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();

        $equipamentos = Equipamento::query()
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->whereNotNull('prefixo')
            ->with('posicao')
            ->orderBy('prefixo')
            ->get();

        $ids = $equipamentos->pluck('id')->all();

        $statusAbertos = StatusEvento::query()
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $ids)
            ->get()
            ->keyBy('equipamento_id');

        $cercasAbertas = CercaEvento::query()
            ->with('cerca')
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $ids)
            ->get()
            ->keyBy('equipamento_id');

        $minutosAtendimento = StatusEvento::query()
            ->whereIn('equipamento_id', $ids)
            ->whereNotNull('saida_em')
            ->whereNotNull('documento')
            ->selectRaw('equipamento_id, documento, SUM(duracao_minutos) as total_minutos')
            ->groupBy('equipamento_id', 'documento')
            ->get()
            ->keyBy(fn ($r) => $r->equipamento_id.'_'.$r->documento);

        $veiculos = $equipamentos
            ->map(function (Equipamento $e) use ($agora, $statusAbertos, $cercasAbertas, $minutosAtendimento) {
                $posicao = $e->posicao;
                $statusEvento = $statusAbertos->get($e->id);
                $cercaEvento = $cercasAbertas->get($e->id);

                $trackerEstado = match ($posicao?->tracker_state) {
                    'Em Movimento' => 1,
                    'Parado' => 0,
                    default => -1,
                };
                $trackerMinutos = $posicao?->state_since
                    ? (int) abs(Carbon::parse($posicao->state_since)->diffInMinutes($agora))
                    : 0;

                if ($trackerEstado !== -1 && $posicao?->position_at) {
                    if (Carbon::parse($posicao->position_at)->diffInHours($agora) >= 3) {
                        $trackerEstado = -1;
                        $trackerMinutos = (int) abs(Carbon::parse($posicao->position_at)->diffInMinutes($agora));
                    }
                }

                $status = $statusEvento?->status_operacional ?? $e->status_operacional ?? '';
                $statusMinutos = $statusEvento?->entrada_em
                    ? (int) abs($statusEvento->entrada_em->diffInMinutes($agora))
                    : 0;

                $documento = $statusEvento?->documento ?? $e->documento_demanda;
                $documentoValido = $documento
                    && ctype_digit((string) $documento)
                    && strlen((string) $documento) >= 9
                    && strlen((string) $documento) <= 10;
                $atendimentoMinutos = 0;
                if ($documentoValido) {
                    $minutosPassados = $statusEvento
                        ? (int) ($minutosAtendimento->get($e->id.'_'.$documento)?->total_minutos ?? 0)
                        : 0;
                    $atendimentoMinutos = $minutosPassados + $statusMinutos;
                }

                $cercaNome = $cercaEvento?->cerca?->nome;
                $cercaMinutos = $cercaEvento?->entrada_em
                    ? (int) abs($cercaEvento->entrada_em->diffInMinutes($agora))
                    : 0;

                return [
                    'prefixo' => $e->prefixo,
                    'placa' => $e->placa ?? '—',
                    'status' => $status,
                    'documento' => $documento ?? '',
                    'cerca_nome' => $cercaNome,
                    'cerca_minutos' => $cercaMinutos,
                    'tracker_estado' => $trackerEstado,
                    'tracker_minutos' => $trackerMinutos,
                    'atendimento_minutos' => $atendimentoMinutos,
                    'status_minutos' => $statusMinutos,
                ];
            })
            ->sort(function ($a, $b) {
                $order = fn ($v) => $v['tracker_estado'] === 0 ? 0 : ($v['tracker_estado'] === 1 ? 1 : 2);
                $oa = $order($a);
                $ob = $order($b);
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }
                if ($oa === 0) {
                    return $b['tracker_minutos'] <=> $a['tracker_minutos'];
                }

                return strcmp($a['prefixo'], $b['prefixo']);
            })
            ->values();

        return view('dashboard.tabela', compact('veiculos', 'agora'));
    }

    public function indicadores(): View
    {
        $snapshot = DashboardSnapshot::latest('capturado_em')->first();

        if (! $snapshot) {
            return view('dashboard.indicadores', ['snapshot' => null]);
        }

        $statusExcluidos = ['Manutenção', 'Frota Reserva', 'Reserva'];
        $dados = collect($snapshot->dados);

        // ── Manutenção ───────────────────────────────────────────────────────────
        $totalFrota = $dados->sum('quantidade');
        $grpManutencao = $dados->firstWhere('status', 'Manutenção');
        $emManutencao = (int) ($grpManutencao['quantidade'] ?? 0);
        $pctManutencao = $totalFrota > 0 ? round($emManutencao / $totalFrota * 100, 1) : 0;
        $veiculosManutencao = collect($grpManutencao['veiculos'] ?? [])->sortByDesc('minutos')->values()->all();

        // ── Sem sinal ────────────────────────────────────────────────────────────
        $semSinalVeiculos = $dados
            ->flatMap(fn ($d) => collect($d['veiculos'] ?? [])->map(fn ($v) => array_merge($v, ['status_op' => $d['status']])))
            ->filter(fn ($v) => ($v['tracker_estado'] ?? -1) === -1)
            ->sortByDesc('tracker_minutos')
            ->values()
            ->all();

        // ── Base de veículos parados (excluindo status previsíveis) ──────────────
        $parados = $dados
            ->filter(fn ($d) => ! in_array($d['status'], $statusExcluidos))
            ->flatMap(fn ($d) => $d['veiculos'] ?? [])
            ->filter(fn ($v) => ($v['tracker_estado'] ?? -1) === 0)
            ->sortByDesc('tracker_minutos')
            ->values();

        $mediaParadosMin = $parados->isNotEmpty() ? (int) round($parados->avg('tracker_minutos')) : 0;
        $mediaParadosHHMM = sprintf('%02d:%02d', intdiv($mediaParadosMin, 60), $mediaParadosMin % 60);

        // ── Faixas de tempo parado (exclusivas — sem sobreposição entre cards) ──
        $faixas = array_map(function ($f) use ($parados, $totalFrota) {
            $lista = $parados->filter(function ($v) use ($f) {
                $min = $v['tracker_minutos'] ?? 0;

                return $min >= $f['limiar'] && ($f['limite'] === null || $min < $f['limite']);
            })->values();

            $qtd = $lista->count();

            return array_merge($f, [
                'quantidade' => $qtd,
                'pct' => $totalFrota > 0 ? round($qtd / $totalFrota * 100, 1) : null,
                'veiculos' => $lista->take(8)->all(),
            ]);
        }, [
            ['horas' => 24, 'limiar' => 1440, 'limite' => null],
            ['horas' => 12, 'limiar' => 720,  'limite' => 1440],
            ['horas' => 6,  'limiar' => 360,  'limite' => 720],
            ['horas' => 3,  'limiar' => 180,  'limite' => 360],
        ]);

        return view('dashboard.indicadores', compact(
            'snapshot', 'totalFrota', 'emManutencao', 'pctManutencao', 'veiculosManutencao',
            'semSinalVeiculos', 'parados', 'mediaParadosMin', 'mediaParadosHHMM',
            'faixas', 'statusExcluidos'
        ));
    }

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

        $novasDemandas = $this->registrarDemandasNovas($veiculos);

        return response()->json([
            'ok' => true,
            'capturado' => $agora->toDateTimeString(),
            'total_status' => count($agrupado),
            'demandas_registradas' => $novasDemandas,
        ]);
    }
}
