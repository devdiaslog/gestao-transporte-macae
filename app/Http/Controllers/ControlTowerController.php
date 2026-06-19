<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReporteRapidoRequest;
use App\Models\Cerca;
use App\Models\CercaEvento;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\EquipamentoLog;
use App\Models\Etapa;
use App\Models\ModeloEquipamento;
use App\Models\Motorista;
use App\Models\Reporte;
use App\Models\ReporteItem;
use App\Models\StatusEvento;
use App\Models\StatusOperacional;
use App\Models\SubDivisao;
use App\Models\TipoEquipamento;
use App\Models\TipoEtapa;
use App\Services\BigcoreService;
use App\Services\StatusOperacionalService;
use App\Services\VfleetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ControlTowerController extends Controller
{
    public function index(Request $request): View
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();
        $tipoImplemento = TipoEquipamento::where('nome', 'Implementos')->first();

        $equipamentos = Equipamento::query()
            ->with(['modelo', 'divisao', 'implemento.modelo', 'ultimoLogOperacional', 'motorista.contatos', 'posicao'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('modelo_id'), fn ($q) => $q->where('modelo_id', $request->modelo_id))
            ->when($request->filled('tipo_etapa_id'), function ($q) use ($request): void {
                // Filtra pelo tipo da última etapa registrada para o veículo
                $q->whereRaw('(
                    SELECT tipo_etapa_id
                    FROM etapas
                    WHERE equipamento_id = equipamentos.id
                    ORDER BY data_hora_inicio DESC
                    LIMIT 1
                ) = ?', [$request->tipo_etapa_id]);
            })
            ->when($request->filled('implemento_modelo_id'), fn ($q) => $q->whereHas('implemento', fn ($q) => $q->where('modelo_id', $request->implemento_modelo_id)))
            ->when($request->filled('motorista_id'), fn ($q) => $q->where('motorista_id', $request->motorista_id))
            ->when($request->filled('sub_divisao_id'), fn ($q) => $q->whereIn('sub_divisao_id', (array) $request->input('sub_divisao_id')))
            ->orderByRaw("(
                SELECT MAX(r.data_hora_emissao)
                FROM reporte_itens ri
                JOIN reportes r ON r.id = ri.reporte_id
                WHERE ri.prefixo = equipamentos.prefixo
                AND r.status = 'publicado'
            ) ASC")
            ->orderBy('placa')
            ->paginate(100)
            ->withQueryString();

        $divisoes = Divisao::where('status', true)->orderBy('nome')->get();

        $subDivisoes = SubDivisao::where('status', true)->orderBy('nome')->get();

        $modelos = $tipoMotorizado
            ? ModeloEquipamento::where('tipo_equipamento_id', $tipoMotorizado->id)
                ->where('status', true)
                ->orderBy('nome')
                ->get()
            : collect();

        $modelosImplemento = $tipoImplemento
            ? ModeloEquipamento::where('tipo_equipamento_id', $tipoImplemento->id)
                ->where('status', true)
                ->orderBy('nome')
                ->get()
            : collect();

        // All active implementos with their modelo, plus which motorizado (if any) has linked them
        $takenImplementoIds = Equipamento::whereNotNull('implemento_id')
            ->pluck('implemento_id', 'id'); // motorizado_id => implemento_id

        $implementos = $tipoImplemento
            ? Equipamento::where('tipo_id', $tipoImplemento->id)
                ->where('status', true)
                ->with('modelo')
                ->orderBy('placa')
                ->get()
                ->map(function (Equipamento $imp) use ($takenImplementoIds) {
                    $takenByMotorizadoId = $takenImplementoIds->search($imp->id);

                    return [
                        'id' => $imp->id,
                        'placa' => $imp->placa,
                        'prefixo' => $imp->prefixo,
                        'modelo' => $imp->modelo?->nome,
                        'taken_by' => $takenByMotorizadoId ?: null,
                    ];
                })
            : collect();

        $tiposEtapa = TipoEtapa::where('ativo', true)->orderBy('nome')->get();

        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $statusCores = $statusOperacionais->pluck('cor', 'nome');

        $motoristas = Motorista::where('status', true)->orderBy('nome')->get();

        // motorista_id => equipamento_id para marcar "em uso" no select
        $motoristaOcupado = Equipamento::whereNotNull('motorista_id')
            ->pluck('id', 'motorista_id'); // motorista_id => equipamento_id

        // Último reporte publicado por prefixo — keyed by prefixo
        $prefixos = $equipamentos->pluck('prefixo')->filter()->values()->all();
        $ultimosReportes = ReporteItem::query()
            ->whereIn('prefixo', $prefixos)
            ->whereHas('reporte', fn ($q) => $q->where('status', 'publicado'))
            ->with('reporte')
            ->get()
            ->filter(fn ($item) => $item->reporte?->status === 'publicado')
            ->sortByDesc(fn ($item) => $item->reporte?->data_hora_emissao)
            ->groupBy('prefixo')
            ->map(fn ($items) => $items->first());

        // Veículos que mudaram de estado nos últimos 15 min — "linha do tempo viva"
        $recentementeAlterados = Equipamento::query()
            ->with('posicao')
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->whereHas('posicao', fn ($q) => $q->whereNotNull('state_since')
                ->where('state_since', '>=', now()->subMinutes(60)))
            ->get()
            ->filter(fn ($e) => $e->posicao?->state_since?->isPast())
            ->sortByDesc(fn ($e) => $e->posicao->state_since)
            ->values();

        // Eventos de cerca abertos para os equipamentos desta página
        $equipamentoIds = $equipamentos->pluck('id')->values()->all();
        $eventosAbertos = CercaEvento::query()
            ->with('cerca')
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $equipamentoIds)
            ->get()
            ->keyBy('equipamento_id');

        // Eventos de status operacional abertos
        $statusEventosAbertos = StatusEvento::query()
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $equipamentoIds)
            ->get()
            ->keyBy('equipamento_id');

        // Soma de minutos por (equipamento_id, documento) — eventos já fechados
        // Chave: "equipamento_id_documento"
        $minutosAtendimento = StatusEvento::query()
            ->whereIn('equipamento_id', $equipamentoIds)
            ->whereNotNull('saida_em')
            ->whereNotNull('documento')
            ->selectRaw('equipamento_id, documento, SUM(duracao_minutos) as total_minutos')
            ->groupBy('equipamento_id', 'documento')
            ->get()
            ->keyBy(fn ($r) => $r->equipamento_id.'_'.$r->documento);

        // Última etapa por equipamento
        $ultimasEtapas = Etapa::query()
            ->with(['tipoEtapa', 'cerca', 'motorista'])
            ->whereIn('equipamento_id', $equipamentoIds)
            ->latest('data_hora_inicio')
            ->get()
            ->unique('equipamento_id')
            ->keyBy('equipamento_id');

        // Contadores dos blocos de 6h — tempo decorrido desde o início da última etapa
        $etapaBloco0 = 0;  // 0–6h atrás
        $etapaBloco6 = 0;  // 6–12h atrás
        $etapaBloco12 = 0; // 12–18h atrás
        $etapaBloco18 = 0; // >18h atrás (ou sem etapa)

        $tz = config('app.timezone');

        foreach ($equipamentos as $eq) {
            $ultimaEtapa = $ultimasEtapas->get($eq->id);

            if (! $ultimaEtapa) {
                $etapaBloco18++;

                continue;
            }

            $horas = (int) $ultimaEtapa->data_hora_inicio->setTimezone($tz)->diffInHours(now());

            if ($horas < 6) {
                $etapaBloco0++;
            } elseif ($horas < 12) {
                $etapaBloco6++;
            } elseif ($horas < 18) {
                $etapaBloco12++;
            } else {
                $etapaBloco18++;
            }
        }

        // Recentes Elog — frotas que mudaram de status/documento na última hora
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
                'cor' => $statusCores->get($e->status_operacional),
                'entrada_em' => $e->entrada_em ? (int) $e->entrada_em->diffInMinutes(now()) : 0,
            ])
            ->values();

        return view('control-tower.index', compact(
            'equipamentos', 'divisoes', 'subDivisoes', 'modelos', 'modelosImplemento',
            'implementos', 'tiposEtapa', 'statusOperacionais', 'statusCores', 'motoristas', 'motoristaOcupado',
            'ultimosReportes', 'recentementeAlterados', 'eventosAbertos', 'statusEventosAbertos',
            'minutosAtendimento', 'recentesElog', 'ultimasEtapas',
            'etapaBloco0', 'etapaBloco6', 'etapaBloco12', 'etapaBloco18',
        ));
    }

    public function painel(Request $request): View
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();

        $equipamentos = Equipamento::query()
            ->with(['modelo', 'divisao', 'motorista', 'posicao'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->orderBy('prefixo')
            ->get();

        $divisoes = Divisao::where('status', true)->orderBy('nome')->get();

        $statusOperacionais = StatusOperacional::where('status', true)->orderBy('nome')->get();
        $statusCores = $statusOperacionais->pluck('cor', 'nome');

        $equipamentoIds = $equipamentos->pluck('id')->values()->all();
        $eventosAbertos = CercaEvento::query()
            ->with('cerca')
            ->whereNull('saida_em')
            ->whereIn('equipamento_id', $equipamentoIds)
            ->get()
            ->keyBy('equipamento_id');

        $cards = $equipamentos->map(function (Equipamento $e) use ($eventosAbertos, $statusCores) {
            return $this->montarCardVeiculo($e, $eventosAbertos->get($e->id), $statusCores);
        });

        return view('control-tower.painel', compact('cards', 'divisoes'));
    }

    /**
     * Formata um intervalo em minutos para exibição legível (Xd Xh Xm).
     */
    private function formatarDuracao(int $mins): string
    {
        $d = intdiv($mins, 1440);
        $h = intdiv($mins % 1440, 60);
        $m = $mins % 60;

        return $d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
    }

    /**
     * Monta o array de dados calculados de um veículo para o card do painel.
     *
     * @param  Collection<string, string>  $statusCores
     * @return array<string, mixed>
     */
    private function montarCardVeiculo(Equipamento $equipamento, ?CercaEvento $eventoAberto, $statusCores): array
    {
        $posicao = $equipamento->posicao;
        $semSinal = ! $posicao || ! $posicao->position_at || $posicao->position_at->lt(now()->subHours(3));

        // Cor do ícone (ignição)
        if ($semSinal) {
            $iconColor = '#71717a';
        } elseif ($posicao->ignition) {
            $iconColor = '#16a34a';
        } else {
            $iconColor = '#dc2626';
        }

        // Barra de tempo de status (tracker_state)
        $stateSinceRaw = $posicao?->state_since;
        $stateMins = ($stateSinceRaw && $stateSinceRaw->isPast())
            ? (int) $stateSinceRaw->diffInMinutes(now())
            : null;

        if ($semSinal && $posicao?->position_at) {
            $barMins = (int) $posicao->position_at->diffInMinutes(now());
            $barTime = $this->formatarDuracao($barMins);
            $barColor = '#71717a';
        } elseif ($stateMins !== null) {
            $barMins = $stateMins;
            $barTime = $this->formatarDuracao($stateMins);
            $barColor = $posicao?->tracker_state === 'Em Movimento' ? '#16a34a' : '#dc2626';
        } else {
            $barMins = 0;
            $barTime = '—';
            $barColor = '#71717a';
        }

        // Cerca
        $cercaMins = $eventoAberto ? (int) $eventoAberto->entrada_em->diffInMinutes(now()) : null;
        $cercaNome = $eventoAberto?->cerca?->nome;
        $cercaDuration = null;
        $cercaBarColor = null;

        if ($cercaMins !== null) {
            $cercaDuration = $this->formatarDuracao($cercaMins);
            $tMin = (int) ($eventoAberto->cerca->tempo_minimo ?? 15);
            $tMax = (int) ($eventoAberto->cerca->tempo_maximo ?? 120);

            $cercaBarColor = match (true) {
                $cercaMins < $tMin => '#2563eb',
                $cercaMins < $tMax * 0.75 => '#16a34a',
                $cercaMins < $tMax => '#ca8a04',
                default => '#dc2626',
            };
        }

        $statusOp = $equipamento->status_operacional;

        return [
            'id' => $equipamento->id,
            'prefixo' => $equipamento->prefixo,
            'placa' => $equipamento->placa,
            'divisao' => $equipamento->divisao?->nome,
            'iconColor' => $iconColor,
            'statusOp' => $statusOp,
            'statusCor' => $statusCores[$statusOp] ?? '#71717a',
            'barColor' => $barColor,
            'barTime' => $barTime,
            'barMins' => $barMins,
            'cercaNome' => $cercaNome,
            'cercaDuration' => $cercaDuration,
            'cercaBarColor' => $cercaBarColor,
            'searchKey' => strtolower(implode(' ', array_filter([
                $equipamento->prefixo,
                $equipamento->placa,
                $equipamento->divisao?->nome,
            ]))),
            'reporteUrl' => route('control-tower.reporte-rapido', $equipamento),
        ];
    }

    public function export(Request $request): Response
    {
        $tipoMotorizado = TipoEquipamento::where('nome', 'Motorizado')->first();

        $equipamentos = Equipamento::query()
            ->with(['modelo', 'divisao', 'implemento.modelo', 'ultimoLogOperacional', 'motorista.contatos', 'posicao'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->when($request->filled('divisao_id'), fn ($q) => $q->where('divisao_id', $request->divisao_id))
            ->when($request->filled('modelo_id'), fn ($q) => $q->where('modelo_id', $request->modelo_id))
            ->when($request->filled('tipo_etapa_id'), function ($q) use ($request): void {
                $q->whereRaw('(
                    SELECT tipo_etapa_id
                    FROM etapas
                    WHERE equipamento_id = equipamentos.id
                    ORDER BY data_hora_inicio DESC
                    LIMIT 1
                ) = ?', [$request->tipo_etapa_id]);
            })
            ->when($request->filled('implemento_modelo_id'), fn ($q) => $q->whereHas('implemento', fn ($q) => $q->where('modelo_id', $request->implemento_modelo_id)))
            ->when($request->filled('motorista_id'), fn ($q) => $q->where('motorista_id', $request->motorista_id))
            ->orderBy('placa')
            ->get();

        $tz = config('app.timezone');
        $filename = 'torre_controle_'.now()->format('Y-m-d_H-i').'.csv';

        $header = [
            'Prefixo', 'Placa', 'ID Elog', 'ID Rastreador', 'Modelo',
            'Semi-Reboque Prefixo', 'Semi-Reboque Placa', 'Semi-Reboque Modelo',
            'Divisão', 'Status Operacional', 'Condutor', 'Telefone Condutor',
            'Documento de Demanda', 'Origem', 'Destino', 'Observação',
            'Última Atualização', 'Campo Atualizado',
            'Latitude', 'Longitude', 'Data/Hora Posição', 'Sincronizado em',
        ];

        $rows = $equipamentos->map(function (Equipamento $eq) use ($tz) {
            $ultimoLog = $eq->ultimoLogOperacional;
            $telefone = $eq->motorista?->contatos->where('status', true)->first()?->telefone ?? '';

            return [
                $eq->prefixo ?? '',
                $eq->placa,
                $eq->id_elog ?? '',
                $eq->id_rastreador ?? '',
                $eq->modelo?->nome ?? '',
                $eq->implemento?->prefixo ?? '',
                $eq->implemento?->placa ?? '',
                $eq->implemento_nome_override ?? $eq->implemento?->modelo?->nome ?? '',
                $eq->divisao?->nome ?? '',
                $eq->status_operacional ?? '',
                $eq->motorista?->nome ?? '',
                $telefone,
                $eq->documento_demanda ?? '',
                $eq->origem ?? '',
                $eq->destino ?? '',
                $eq->observacao_operacional ?? '',
                $ultimoLog?->created_at->setTimezone($tz)->format('d/m/Y H:i') ?? '',
                $ultimoLog?->campo ?? '',
                $eq->posicao?->latitude ?? '',
                $eq->posicao?->longitude ?? '',
                $eq->posicao?->position_at?->setTimezone($tz)->format('d/m/Y H:i') ?? '',
                $eq->posicao?->synced_at?->setTimezone($tz)->format('d/m/Y H:i') ?? '',
            ];
        });

        $csv = collect([$header])
            ->concat($rows)
            ->map(fn (array $row) => implode(';', array_map(fn ($cell) => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n");

        return response("\xEF\xBB\xBF".$csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
        ]);
    }

    public function sincronizarPosicoes(VfleetsService $vfleets): JsonResponse
    {
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

    public function mapaGeral(): JsonResponse
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

    public function posicao(string $plate, VfleetsService $vfleets): JsonResponse
    {
        try {
            $pos = $vfleets->sincronizarPlaca($plate);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 500);
        }

        if (! $pos || ! $pos->latitude || ! $pos->longitude) {
            return response()->json(['ok' => false, 'erro' => 'Sem localização disponível.'], 404);
        }

        $mins = $pos->state_since ? (int) abs($pos->state_since->diffInMinutes(now())) : null;
        $d = $mins !== null ? intdiv($mins, 1440) : 0;
        $h = $mins !== null ? intdiv($mins % 1440, 60) : 0;
        $m = $mins !== null ? $mins % 60 : 0;
        $duration = $mins !== null ? ($d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m")) : null;

        $semSinal = ! $pos->position_at || $pos->position_at->diffInHours(now()) >= 3;

        return response()->json([
            'ok' => true,
            'latitude' => $pos->latitude,
            'longitude' => $pos->longitude,
            'position_at' => $pos->position_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'synced_at' => $pos->synced_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'tracker_state' => $pos->tracker_state,
            'state_duration' => $duration,
            'ignition' => (bool) $pos->ignition,
            'speed' => (int) $pos->speed,
            'sem_sinal' => $semSinal,
        ]);
    }

    public function historico(Request $request, Equipamento $equipamento): View
    {
        $campos = EquipamentoLog::where('equipamento_id', $equipamento->id)
            ->distinct()
            ->orderBy('campo')
            ->pluck('campo');

        $logs = EquipamentoLog::query()
            ->with('user')
            ->where('equipamento_id', $equipamento->id)
            ->when($request->filled('campo'), fn ($q) => $q->where('campo', $request->campo))
            ->when($request->filled('data'), fn ($q) => $q->whereDate('created_at', $request->data))
            ->when($request->filled('documento'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('valor_anterior', 'like', '%'.$request->documento.'%')
                    ->orWhere('valor_novo', 'like', '%'.$request->documento.'%');
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('control-tower.historico', compact('equipamento', 'logs', 'campos'));
    }

    public function reporteRapido(StoreReporteRapidoRequest $request, Equipamento $equipamento): JsonResponse
    {
        $validated = $request->validated();
        $now = Carbon::now();

        $prefix = $now->format('Ymd');
        $ultimo = Reporte::where('numero_reporte', 'like', $prefix.'-%')->max('numero_reporte');
        $seq = $ultimo ? ((int) substr($ultimo, -3)) + 1 : 1;
        $numero = $prefix.'-'.str_pad($seq, 3, '0', STR_PAD_LEFT);

        $reporte = Reporte::create([
            'numero_reporte' => $numero,
            'nome' => $validated['nome'],
            'status' => $validated['salvar_como'],
            'data_hora_emissao' => $now,
            'created_by' => auth()->id(),
        ]);

        ReporteItem::create([
            'reporte_id' => $reporte->id,
            'prefixo' => $equipamento->prefixo,
            'status_operacional' => $validated['status_operacional'],
            'primeiro_contato' => $validated['primeiro_contato'],
            'observacao' => $validated['observacao'],
            'documento' => $validated['documento'] ?? null,
            'tempo_parado' => $validated['tempo_parado'] ?? null,
            'segundo_contato' => $validated['segundo_contato'] ?? null,
            'data_hora_previsao' => $validated['data_hora_previsao'] ?? null,
        ]);

        $statusAnterior = $equipamento->status_operacional;
        $novoStatus = $validated['status_operacional'];

        if ($statusAnterior !== $novoStatus) {
            EquipamentoLog::create([
                'equipamento_id' => $equipamento->id,
                'user_id' => auth()->id(),
                'campo' => 'Status Operacional',
                'valor_anterior' => $statusAnterior,
                'valor_novo' => $novoStatus,
            ]);
        }

        $equipamento->update(['status_operacional' => $novoStatus]);

        $reporteUrl = $validated['salvar_como'] === 'publicado'
            ? route('reportes.show', $reporte)
            : null;

        return response()->json([
            'ok' => true,
            'numero' => $reporte->numero_reporte,
            'status' => $novoStatus,
            'reporte_url' => $reporteUrl,
        ]);
    }

    public function updateImplemento(Request $request, Equipamento $equipamento): RedirectResponse
    {
        $validated = $request->validate([
            'implemento_id' => ['nullable', 'integer', 'exists:equipamentos,id'],
            'implemento_nome_override' => ['nullable', 'string', 'max:255'],
        ]);

        // Ensure the chosen implemento is not already linked to another motorizado
        if (! empty($validated['implemento_id'])) {
            $alreadyTaken = Equipamento::where('implemento_id', $validated['implemento_id'])
                ->where('id', '!=', $equipamento->id)
                ->exists();

            if ($alreadyTaken) {
                return redirect()->back()->withErrors(['implemento_id' => 'Este implemento já está vinculado a outro motorizado.']);
            }
        }

        // Clear override when unlinking
        if (empty($validated['implemento_id'])) {
            $validated['implemento_nome_override'] = null;
        }

        $equipamento->update($validated);

        return redirect()->back()->with('success', 'Implemento atualizado com sucesso.');
    }
}
