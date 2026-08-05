<?php

namespace App\Http\Controllers;

use App\Models\Cerca;
use App\Models\CercaEvento;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\StatusEvento;
use App\Models\StatusOperacional;
use App\Models\SubDivisao;
use App\Models\TipoEquipamento;
use App\Services\BigcoreService;
use App\Services\StatusOperacionalService;
use App\Services\VfleetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ControlTowerController extends Controller
{
    public function sincronizarPosicoes(VfleetsService $vfleets): JsonResponse
    {
        // A tela sincroniza ao abrir: sem esse limite, cada usuário que entra
        // gera uma chamada, a API responde 429 e a sincronização inteira falha
        // — deixando todos os veículos com posição velha.
        if ($vfleets->sincronizadoRecentemente()) {
            return response()->json([
                'ok' => true,
                'total' => 0,
                'reaproveitado' => true,
                'segundos' => $vfleets->segundosDesdeUltimaSincronizacao(),
            ]);
        }

        try {
            $total = $vfleets->sincronizar();
        } catch (Throwable $e) {
            $status = str_contains($e->getMessage(), '429') ? 429 : 500;

            return response()->json(['ok' => false, 'erro' => $e->getMessage()], $status);
        }

        return response()->json(['ok' => true, 'total' => $total]);
    }

    public function sincronizarStatusOperacional(BigcoreService $bigcore, StatusOperacionalService $service): JsonResponse
    {
        try {
            $registros = $bigcore->buscarTodos();
            $alteracoes = $service->sincronizar($registros, now());
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'processados' => count($registros), 'alteracoes' => $alteracoes]);
    }

    public function mapaGeralPagina(): View
    {
        $divisoes = Divisao::where('status', true)->orderBy('nome')->get();
        $subDivisoes = SubDivisao::where('status', true)->orderBy('nome')->get();
        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();

        return view('mapa-geral.index', compact('divisoes', 'subDivisoes', 'statusOperacionais'));
    }

    public function mapaGeral(Request $request): JsonResponse
    {
        $tz = config('app.timezone');
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();

        // Pré-carrega eventos de cerca em aberto (sem saida_em) para todos os equipamentos
        $eventosAbertos = CercaEvento::query()
            ->with('cerca')
            ->whereNull('saida_em')
            ->get()
            ->keyBy('equipamento_id');

        // Pré-carrega eventos de status operacional abertos
        $statusEventosAbertos = StatusEvento::query()
            ->whereNull('saida_em')
            ->get()
            ->keyBy('equipamento_id');

        // Soma de minutos por (equipamento_id, documento) — eventos já fechados
        $minutosAtendimento = StatusEvento::query()
            ->whereNotNull('saida_em')
            ->whereNotNull('documento')
            ->selectRaw('equipamento_id, documento, SUM(duracao_minutos) as total_minutos')
            ->groupBy('equipamento_id', 'documento')
            ->get()
            ->keyBy(fn ($r) => $r->equipamento_id.'_'.$r->documento);

        $veiculos = Equipamento::query()
            ->with(['posicao', 'motorista'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('sub_divisao_id'), fn ($q) => $q->whereIn('sub_divisao_id', (array) $request->input('sub_divisao_id')))
            ->when($request->filled('status_operacional'), fn ($q) => $q->whereIn('status_operacional', (array) $request->input('status_operacional')))
            ->get()
            ->filter(fn ($e) => $e->posicao?->latitude && $e->posicao?->longitude)
            ->map(function ($e) use ($tz, $eventosAbertos, $statusEventosAbertos, $minutosAtendimento) {
                $stateSince = $e->posicao->state_since;

                // state_since no futuro indica dado com timezone incorreto no banco
                $mins = ($stateSince && $stateSince->isPast())
                    ? (int) $stateSince->diffInMinutes(now())
                    : null;

                if ($mins !== null) {
                    $d = intdiv($mins, 1440);
                    $h = intdiv($mins % 1440, 60);
                    $m = $mins % 60;
                    $duration = $d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
                } else {
                    $duration = null;
                }

                // Veículo sem sinal há mais de 3 horas
                $positionAt = $e->posicao->position_at;
                $semSinal = ! $positionAt || $positionAt->diffInHours(now()) >= 3;
                if ($semSinal && $positionAt) {
                    $semSinalMins = (int) $positionAt->diffInMinutes(now());
                    $sd = intdiv($semSinalMins, 1440);
                    $sh = intdiv($semSinalMins % 1440, 60);
                    $sm = $semSinalMins % 60;
                    $semSinalDuration = $sd > 0 ? "{$sd}d {$sh}h {$sm}m" : ($sh > 0 ? "{$sh}h {$sm}m" : "{$sm}m");
                } else {
                    $semSinalDuration = null;
                }

                // Tempo na cerca (evento aberto)
                $eventoAberto = $eventosAbertos->get($e->id);
                $tempoCercaMins = $eventoAberto
                    ? (int) $eventoAberto->entrada_em->diffInMinutes(now())
                    : null;
                if ($tempoCercaMins !== null) {
                    $cd = intdiv($tempoCercaMins, 1440);
                    $ch = intdiv($tempoCercaMins % 1440, 60);
                    $cm = $tempoCercaMins % 60;
                    $tempoCercaDuracao = $cd > 0 ? "{$cd}d {$ch}h {$cm}m" : ($ch > 0 ? "{$ch}h {$cm}m" : "{$cm}m");
                    $tMin = (int) ($eventoAberto->cerca->tempo_minimo ?? 15);
                    $tMax = (int) ($eventoAberto->cerca->tempo_maximo ?? 120);
                    if ($tempoCercaMins < $tMin) {
                        $cercaBarColor = '#2563eb';
                    } elseif ($tempoCercaMins < $tMax * 0.75) {
                        $cercaBarColor = '#16a34a';
                    } elseif ($tempoCercaMins < $tMax) {
                        $cercaBarColor = '#ca8a04';
                    } else {
                        $cercaBarColor = '#dc2626';
                    }
                } else {
                    $tempoCercaDuracao = null;
                    $cercaBarColor = null;
                }

                // StatusEvento aberto
                $statusEvento = $statusEventosAbertos->get($e->id);
                $elogMins = $statusEvento ? (int) $statusEvento->entrada_em->diffInMinutes(now()) : null;
                if ($elogMins !== null) {
                    $ed = intdiv($elogMins, 1440);
                    $eh = intdiv($elogMins % 1440, 60);
                    $em = $elogMins % 60;
                    $elogDuracao = $ed > 0 ? "{$ed}d {$eh}h {$em}m" : ($eh > 0 ? "{$eh}h {$em}m" : "{$em}m");
                } else {
                    $elogDuracao = null;
                }

                // Tempo total no atendimento (documento): fechados + aberto atual
                $documento = $statusEvento?->documento;
                $minutosPassados = $documento
                    ? (int) ($minutosAtendimento->get($e->id.'_'.$documento)?->total_minutos ?? 0)
                    : 0;
                $totalAtendimento = $minutosPassados + ($elogMins ?? 0);
                if ($totalAtendimento > 0) {
                    $ta = intdiv($totalAtendimento, 1440);
                    $tb = intdiv($totalAtendimento % 1440, 60);
                    $tc = $totalAtendimento % 60;
                    $tempoAtendimento = $ta > 0 ? "{$ta}d {$tb}h {$tc}m" : ($tb > 0 ? "{$tb}h {$tc}m" : "{$tc}m");
                } else {
                    $tempoAtendimento = null;
                }

                return [
                    'prefixo' => $e->prefixo,
                    'placa' => $e->placa,
                    'lat' => (float) $e->posicao->latitude,
                    'lng' => (float) $e->posicao->longitude,
                    'status_elog' => $statusEvento?->status_operacional,
                    'tempo_elog' => $elogDuracao,
                    'atendimento' => $documento,
                    'tempo_atendimento' => $tempoAtendimento,
                    'observacao' => $statusEvento?->observacao,
                    'motorista' => $e->motorista?->nome,
                    'ignition' => (bool) $e->posicao->ignition,
                    'speed' => (int) $e->posicao->speed,
                    'tracker_state' => $e->posicao->tracker_state,
                    'state_duration' => $duration,
                    'state_since_mins' => $mins,
                    'position_at' => $positionAt?->setTimezone($tz)->format('d/m/Y H:i'),
                    'synced_at' => $e->posicao->synced_at?->setTimezone($tz)->format('d/m/Y H:i'),
                    'sem_sinal' => $semSinal,
                    'sem_sinal_duration' => $semSinalDuration,
                    'tempo_cerca_mins' => $tempoCercaMins,
                    'tempo_cerca_duracao' => $tempoCercaDuracao,
                    'cerca_bar_color' => $cercaBarColor,
                ];
            })
            ->when($request->filled('tracker_state'), function (Collection $collection) use ($request) {
                $estados = (array) $request->input('tracker_state');

                return $collection->filter(fn (array $v) => in_array($v['sem_sinal'] ? 'Sem Sinal' : ($v['tracker_state'] ?: 'Sem Sinal'), $estados, true));
            })
            ->values();

        $cercas = Cerca::query()
            ->where('status', true)
            ->whereNotNull('poligono')
            ->get(['nome', 'atividade', 'poligono'])
            ->map(fn (Cerca $c) => [
                'nome' => $c->nome,
                'atividade' => $c->atividade?->label(),
                'poligono' => $c->poligono,
            ])
            ->filter(fn (array $c) => is_array($c['poligono']) && count($c['poligono']) >= 3)
            ->values();

        // Veículos que mudaram de status/documento no Elog na última hora
        $coresStatus = StatusOperacional::pluck('cor', 'nome');

        $idsRecentesElog = StatusEvento::query()
            ->whereNotNull('saida_em')
            ->where('saida_em', '>=', now()->subHour())
            ->distinct()
            ->pluck('equipamento_id');

        $recentesElog = StatusEvento::query()
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $idsRecentesElog)
            ->with('equipamento')
            ->get()
            ->map(fn ($e) => [
                'prefixo' => $e->equipamento?->prefixo,
                'placa' => $e->equipamento?->placa,
                'status_operacional' => $e->status_operacional,
                'documento' => $e->documento,
                'cor' => $coresStatus->get($e->status_operacional),
                'entrada_em' => $e->entrada_em ? (int) $e->entrada_em->setTimezone($tz)->diffInMinutes(now()) : 0,
            ])
            ->values();

        return response()->json(['veiculos' => $veiculos, 'cercas' => $cercas, 'recentes_elog' => $recentesElog]);
    }
}
