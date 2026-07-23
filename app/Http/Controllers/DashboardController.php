<?php

namespace App\Http\Controllers;

use App\Enums\FonteDemanda;
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

            // Do E-log cadastramos apenas o número da demanda e o veículo.
            // Rota, itens e status chegam depois pela importação do SAP, que
            // localiza esta demanda pelo número e completa o resto.
            Demanda::create([
                'numero_demanda' => $numeroDoc,
                'tipo_cadastro' => TipoCadastro::Integracao,
                'fonte_demanda' => FonteDemanda::fromNumeroDemanda($numeroDoc),
                'status_demanda' => StatusDemanda::Pendente,
                'equipamento_id' => $equipamentoId,
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
            ->filter(fn ($v) => ! empty($v['status']) && ! in_array($v['status'], ['Em Operação Interna', 'Frota Reserva', 'Manutenção']))
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

    public function demandas(): View
    {
        $agora = now();

        $demandas = Demanda::query()->with(['equipamento:id,prefixo', 'itens'])->get();

        $total = $demandas->count();

        // ── Contagem por status ──────────────────────────────────────────────────
        $pendentes = $demandas->where('status_demanda', StatusDemanda::Pendente)->count();
        $emAndamento = $demandas->where('status_demanda', StatusDemanda::EmAndamento)->count();
        $finalizadas = $demandas->where('status_demanda', StatusDemanda::Finalizado)->count();
        $canceladas = $demandas->where('status_demanda', StatusDemanda::Cancelada)->count();
        $recusadas = $demandas->where('status_demanda', StatusDemanda::Recusa)->count();
        $suspensas = $demandas->where('status_demanda', StatusDemanda::Suspensa)->count();
        $emAberto = $pendentes + $emAndamento;

        // ── Vencidas: prazo já passou e ainda em aberto ──────────────────────────
        $vencidas = $demandas
            ->filter(fn (Demanda $d) => $d->prazo_demanda !== null
                && $d->prazo_demanda->isBefore($agora)
                && in_array($d->status_demanda, [StatusDemanda::Pendente, StatusDemanda::EmAndamento]))
            ->count();

        // ── Vence nas próximas 24h (em aberto) ───────────────────────────────────
        $venceEm24h = $demandas
            ->filter(fn (Demanda $d) => $d->prazo_demanda !== null
                && $d->prazo_demanda->isBetween($agora, $agora->copy()->addDay())
                && in_array($d->status_demanda, [StatusDemanda::Pendente, StatusDemanda::EmAndamento]))
            ->count();

        // ── Não classificadas: sem tipo definido e ainda em aberto ───────────────
        $naoClassificadas = $demandas
            ->filter(fn (Demanda $d) => $d->tipo_demanda === null
                && in_array($d->status_demanda, [StatusDemanda::Pendente, StatusDemanda::EmAndamento]))
            ->count();

        // ── Tempo médio de atendimento (fim − início) das finalizadas ────────────
        $finalizadasComTempo = $demandas
            ->filter(fn (Demanda $d) => $d->status_demanda === StatusDemanda::Finalizado
                && $d->data_hora_inicio_demanda !== null
                && $d->data_hora_fim_demanda !== null);
        $tempoMedioAtendMin = $finalizadasComTempo->isNotEmpty()
            ? (int) round($finalizadasComTempo->avg(fn (Demanda $d) => abs($d->data_hora_inicio_demanda->diffInMinutes($d->data_hora_fim_demanda))))
            : 0;

        // ── Distribuição por status (donut) ──────────────────────────────────────
        $porStatus = [
            ['label' => 'Pendente',     'valor' => $pendentes,   'cor' => '#a1a1aa'],
            ['label' => 'Em Andamento', 'valor' => $emAndamento, 'cor' => '#3b82f6'],
            ['label' => 'Finalizado',   'valor' => $finalizadas, 'cor' => '#10b981'],
            ['label' => 'Cancelada',    'valor' => $canceladas,  'cor' => '#f43f5e'],
            ['label' => 'Recusa',       'valor' => $recusadas,   'cor' => '#f97316'],
            ['label' => 'Suspensa',     'valor' => $suspensas,   'cor' => '#f59e0b'],
        ];

        // ── Distribuição por tipo (barra) ────────────────────────────────────────
        $porTipo = [
            ['label' => 'Load',            'valor' => $demandas->where('tipo_demanda', TipoDemanda::Load)->count(),          'cor' => '#3b82f6'],
            ['label' => 'Backload',        'valor' => $demandas->where('tipo_demanda', TipoDemanda::Backload)->count(),      'cor' => '#f59e0b'],
            ['label' => 'Transferência',   'valor' => $demandas->where('tipo_demanda', TipoDemanda::Transferencia)->count(), 'cor' => '#8b5cf6'],
            ['label' => 'Não classificada', 'valor' => $demandas->whereNull('tipo_demanda')->count(),                        'cor' => '#d4d4d8'],
        ];

        // ── Cumprimento de prazo (No prazo x Vencidas) ───────────────────────────
        // Base: demandas com prazo definido, exceto canceladas.
        $comPrazo = $demandas->filter(fn (Demanda $d) => $d->prazo_demanda !== null
            && $d->status_demanda !== StatusDemanda::Cancelada);

        $foraPrazoQtd = $comPrazo->filter(function (Demanda $d) use ($agora) {
            if ($d->status_demanda === StatusDemanda::Finalizado) {
                return $d->data_hora_fim_demanda !== null
                    && $d->data_hora_fim_demanda->isAfter($d->prazo_demanda);
            }

            return $d->prazo_demanda->isBefore($agora);
        })->count();

        $noPrazoQtd = $comPrazo->count() - $foraPrazoQtd;
        $pctNoPrazo = $comPrazo->count() > 0 ? round($noPrazoQtd / $comPrazo->count() * 100, 1) : 0;

        $porPrazo = [
            ['label' => 'No prazo', 'valor' => $noPrazoQtd,   'cor' => '#10b981'],
            ['label' => 'Vencidas', 'valor' => $foraPrazoQtd, 'cor' => '#f43f5e'],
        ];

        // ── Origem do dado (SAP LT x SAP TM) ─────────────────────────────────────
        $porFonte = collect(FonteDemanda::cases())
            ->map(fn (FonteDemanda $f) => [
                'label' => $f->label(),
                'valor' => $demandas->where('fonte_demanda', $f)->count(),
                'cor' => $f === FonteDemanda::SapLt ? '#06b6d4' : '#8b5cf6',
            ])
            ->push([
                'label' => 'Sem fonte',
                'valor' => $demandas->whereNull('fonte_demanda')->count(),
                'cor' => '#d4d4d8',
            ])
            ->filter(fn ($f) => $f['valor'] > 0)
            ->values()
            ->all();

        // ── Evolução diária — criadas nos últimos 14 dias ────────────────────────
        $criadasPorDia = $demandas->groupBy(fn (Demanda $d) => $d->created_at->toDateString());
        $finalizadasPorDia = $finalizadasComTempo
            ->merge($demandas->where('status_demanda', StatusDemanda::Finalizado))
            ->unique('id')
            ->filter(fn (Demanda $d) => $d->data_hora_fim_demanda !== null)
            ->groupBy(fn (Demanda $d) => $d->data_hora_fim_demanda->toDateString());

        $evolucao = collect(range(13, 0))->map(function ($diasAtras) use ($agora, $criadasPorDia, $finalizadasPorDia) {
            $dia = $agora->copy()->subDays($diasAtras)->toDateString();
            $criadas = $criadasPorDia->get($dia)?->count() ?? 0;
            $finalizadas = $finalizadasPorDia->get($dia)?->count() ?? 0;

            return [
                'dia' => Carbon::parse($dia)->format('d/m'),
                'criadas' => $criadas,
                'finalizadas' => $finalizadas,
                'taxa' => $criadas > 0 ? round($finalizadas / $criadas * 100, 1) : null,
            ];
        })->all();

        // ── Metadados de status para empilhamento nos gráficos ───────────────────
        $statusMeta = [
            ['key' => 'pendente',     'label' => 'Pendente',     'cor' => '#a1a1aa'],
            ['key' => 'em_andamento', 'label' => 'Em Andamento', 'cor' => '#3b82f6'],
            ['key' => 'finalizado',   'label' => 'Finalizado',   'cor' => '#10b981'],
            ['key' => 'cancelada',    'label' => 'Cancelada',    'cor' => '#f43f5e'],
            ['key' => 'recusa',       'label' => 'Recusa',       'cor' => '#f97316'],
            ['key' => 'suspensa',     'label' => 'Suspensa',     'cor' => '#f59e0b'],
        ];

        $porStatusDe = fn ($grupo): array => [
            'pendente' => $grupo->where('status_demanda', StatusDemanda::Pendente)->count(),
            'em_andamento' => $grupo->where('status_demanda', StatusDemanda::EmAndamento)->count(),
            'finalizado' => $grupo->where('status_demanda', StatusDemanda::Finalizado)->count(),
            'cancelada' => $grupo->where('status_demanda', StatusDemanda::Cancelada)->count(),
            'recusa' => $grupo->where('status_demanda', StatusDemanda::Recusa)->count(),
            'suspensa' => $grupo->where('status_demanda', StatusDemanda::Suspensa)->count(),
        ];

        // ── Top rotas (origens → destinos dos itens), segmentadas por status ─────
        $rotasGroup = $demandas
            ->filter(fn (Demanda $d) => $d->locaisOrigem() !== [] || $d->locaisDestino() !== [])
            ->groupBy(fn (Demanda $d) => $d->rota());

        $topRotas = $rotasGroup
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->map(fn ($total, $rota) => [
                'rota' => $rota,
                'total' => $total,
                'por_status' => $porStatusDe($rotasGroup->get($rota)),
            ])
            ->values()
            ->all();

        // ── Top veículos por nº de demandas, segmentados por status ──────────────
        $veiculosGroup = $demandas
            ->filter(fn (Demanda $d) => $d->equipamento !== null)
            ->groupBy(fn (Demanda $d) => $d->equipamento->prefixo);

        $topVeiculos = $veiculosGroup
            ->map->count()
            ->sortDesc()
            ->take(10)
            ->map(fn ($total, $prefixo) => [
                'prefixo' => $prefixo,
                'total' => $total,
                'por_status' => $porStatusDe($veiculosGroup->get($prefixo)),
            ])
            ->values()
            ->all();

        // ── Visão operacional ────────────────────────────────────────────────────
        $abertas = $demandas->filter(fn (Demanda $d) => in_array($d->status_demanda, [StatusDemanda::Pendente, StatusDemanda::EmAndamento]));

        $rotuloTipo = fn (?TipoDemanda $t): string => $t?->label() ?? 'Não classificada';

        // 1. Em atendimento agora, por tipo.
        $emAtendimentoPorTipo = $demandas
            ->where('status_demanda', StatusDemanda::EmAndamento)
            ->groupBy(fn (Demanda $d) => $rotuloTipo($d->tipo_demanda))
            ->map->count()
            ->sortDesc();

        // 2. Vencem hoje (em aberto).
        $venceHoje = $abertas
            ->filter(fn (Demanda $d) => $d->prazo_demanda?->isSameDay($agora) === true)
            ->count();

        // 3. Realizadas (finalizadas), por tipo.
        $finalizadasPorTipo = $demandas
            ->where('status_demanda', StatusDemanda::Finalizado)
            ->groupBy(fn (Demanda $d) => $rotuloTipo($d->tipo_demanda))
            ->map->count()
            ->sortDesc();

        // 4. Veículo destaque: maior nº de demandas e maior média de itens/demanda.
        $porVeiculo = $demandas
            ->filter(fn (Demanda $d) => $d->equipamento !== null)
            ->groupBy(fn (Demanda $d) => $d->equipamento->prefixo)
            ->map(fn ($grupo, $prefixo) => [
                'prefixo' => $prefixo,
                'demandas' => $grupo->count(),
                'media_itens' => round($grupo->avg(fn (Demanda $d) => $d->itens->count()), 1),
            ]);

        $veiculoTopDemandas = $porVeiculo->sortByDesc('demandas')->first();
        $veiculoTopMediaItens = $porVeiculo->sortByDesc('media_itens')->first();

        // 5 e 7. Atenção/prioridade: abertas ordenadas por vencimento (vencidas
        // primeiro, prazo mais próximo em seguida); empate decide pelo nº de itens.
        $atencao = $abertas
            ->sortBy([
                fn (Demanda $a, Demanda $b) => ($a->prazo_demanda?->getTimestamp() ?? PHP_INT_MAX) <=> ($b->prazo_demanda?->getTimestamp() ?? PHP_INT_MAX),
                fn (Demanda $a, Demanda $b) => $b->itens->count() <=> $a->itens->count(),
            ])
            ->take(8)
            ->values();

        $prioridade = $atencao->first();

        // 6. Tendência (últimos 7 dias): finalizar no ritmo em que se cria.
        $criadas7d = $demandas->filter(fn (Demanda $d) => $d->created_at->greaterThanOrEqualTo($agora->copy()->subDays(7)))->count();
        $finalizadas7d = $demandas
            ->filter(fn (Demanda $d) => $d->status_demanda === StatusDemanda::Finalizado
                && $d->data_hora_fim_demanda !== null
                && $d->data_hora_fim_demanda->greaterThanOrEqualTo($agora->copy()->subDays(7)))
            ->count();
        $tendenciaBoa = $finalizadas7d >= $criadas7d;

        // 8. Tempo médio de atendimento por tipo (finalizadas com início e fim),
        // considerando também os itens: minutos totais ÷ itens entregues no grupo.
        $duracaoMin = fn (Demanda $d): int => (int) abs($d->data_hora_inicio_demanda->diffInMinutes($d->data_hora_fim_demanda));

        $tempoMedioPorTipo = $finalizadasComTempo
            ->groupBy(fn (Demanda $d) => $rotuloTipo($d->tipo_demanda))
            ->map(function ($grupo) use ($duracaoMin) {
                $totalMin = $grupo->sum($duracaoMin);
                $totalItens = $grupo->sum(fn (Demanda $d) => $d->itens->count());

                return [
                    'demandas' => $grupo->count(),
                    'itens' => $totalItens,
                    'media_demanda' => (int) round($totalMin / $grupo->count()),
                    'media_item' => $totalItens > 0 ? (int) round($totalMin / $totalItens) : null,
                ];
            })
            ->sortByDesc('media_demanda');

        return view('dashboard.demandas', compact(
            'total', 'emAberto', 'pendentes', 'emAndamento', 'finalizadas', 'canceladas',
            'vencidas', 'venceEm24h', 'naoClassificadas', 'tempoMedioAtendMin',
            'porStatus', 'porTipo', 'porPrazo', 'pctNoPrazo', 'porFonte', 'evolucao', 'topRotas', 'topVeiculos', 'statusMeta', 'agora',
            'emAtendimentoPorTipo', 'venceHoje', 'finalizadasPorTipo', 'veiculoTopDemandas', 'veiculoTopMediaItens',
            'atencao', 'prioridade', 'criadas7d', 'finalizadas7d', 'tendenciaBoa', 'tempoMedioPorTipo'
        ));
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

                $top5 = $itens->take(5)->map(fn ($v) => [
                    'cm' => $v['cm'],
                    'placa' => $v['placa'],
                    'minutos' => $v['minutos'],
                    'pct' => $totalMinutos > 0 ? round(($v['minutos'] / $totalMinutos) * 100, 1) : 0,
                ])->values()->all();

                return [
                    'status' => $status,
                    'quantidade' => $itens->count(),
                    'media_minutos' => $mediaMinutos,
                    'media_horas' => round($mediaMinutos / 60, 1),
                    'top1' => $top5[0] ?? null,
                    'top5' => $top5,
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
