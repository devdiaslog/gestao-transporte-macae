<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReporteRapidoRequest;
use App\Models\Cerca;
use App\Models\CercaEvento;
use App\Models\Divisao;
use App\Models\Equipamento;
use App\Models\EquipamentoLog;
use App\Models\ModeloEquipamento;
use App\Models\Motorista;
use App\Models\Reporte;
use App\Models\ReporteItem;
use App\Models\StatusOperacional;
use App\Models\TipoEquipamento;
use App\Services\VfleetsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
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
            ->when($request->filled('status_operacional'), fn ($q) => $q->where('status_operacional', $request->status_operacional))
            ->when($request->filled('implemento_modelo_id'), fn ($q) => $q->whereHas('implemento', fn ($q) => $q->where('modelo_id', $request->implemento_modelo_id)))
            ->when($request->filled('motorista_id'), fn ($q) => $q->where('motorista_id', $request->motorista_id))
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

        return view('control-tower.index', compact('equipamentos', 'divisoes', 'modelos', 'modelosImplemento', 'implementos', 'statusOperacionais', 'statusCores', 'motoristas', 'motoristaOcupado', 'ultimosReportes', 'recentementeAlterados', 'eventosAbertos'));
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

        return view('control-tower.painel', compact('equipamentos', 'divisoes', 'statusCores', 'eventosAbertos'));
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
            ->when($request->filled('status_operacional'), fn ($q) => $q->where('status_operacional', $request->status_operacional))
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

        $veiculos = Equipamento::query()
            ->with(['posicao', 'motorista'])
            ->where('status', true)
            ->when($tipoMotorizado, fn ($q) => $q->where('tipo_id', $tipoMotorizado->id))
            ->get()
            ->filter(fn ($e) => $e->posicao?->latitude && $e->posicao?->longitude)
            ->map(function ($e) use ($tz, $eventosAbertos) {
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

                // Tempo na cerca (evento aberto)
                $eventoAberto = $eventosAbertos->get($e->id);
                $tempoCercaMins = $eventoAberto
                    ? (int) $eventoAberto->entrada_em->diffInMinutes(now())
                    : null;

                return [
                    'prefixo' => $e->prefixo,
                    'placa' => $e->placa,
                    'lat' => (float) $e->posicao->latitude,
                    'lng' => (float) $e->posicao->longitude,
                    'status' => $e->status_operacional,
                    'motorista' => $e->motorista?->nome,
                    'ignition' => (bool) $e->posicao->ignition,
                    'speed' => (int) $e->posicao->speed,
                    'tracker_state' => $e->posicao->tracker_state,
                    'state_duration' => $duration,
                    'state_since_mins' => $mins,
                    'position_at' => $positionAt?->setTimezone($tz)->format('d/m/Y H:i'),
                    'synced_at' => $e->posicao->synced_at?->setTimezone($tz)->format('d/m/Y H:i'),
                    'sem_sinal' => $semSinal,
                    'tempo_cerca_mins' => $tempoCercaMins,
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

        return response()->json(['veiculos' => $veiculos, 'cercas' => $cercas]);
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
