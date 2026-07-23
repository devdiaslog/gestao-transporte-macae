<x-layouts.app title="Demandas — Poli Macaé" :no-header="true">

@php
    $navActive   = 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white';
    $navInactive = 'border-b-2 border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200';

    $fmtMin = function (int $min): string {
        $min = abs($min);
        $d = intdiv($min, 1440);
        $h = intdiv($min % 1440, 60);
        $m = $min % 60;
        return $d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
    };

    $info = fn (string $texto): string => '<span class="ml-1.5 inline-flex h-4 w-4 shrink-0 cursor-help items-center justify-center rounded-full border border-zinc-300 text-[10px] font-bold leading-none text-zinc-400 dark:border-zinc-600 dark:text-zinc-500" title="'.e($texto).'">i</span>';
@endphp

<div class="-mx-4 flex min-h-screen flex-col sm:-mx-6 lg:-mx-8">

    {{-- Sub-menu superior --}}
    <nav class="shrink-0 border-b border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center gap-1 px-4 sm:px-6 lg:px-8">
            <p class="mr-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-600">Dashboard</p>
            <a href="{{ route('dashboard.status') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.status') ? $navActive : $navInactive }}">
                Status da Frota
            </a>
            <a href="{{ route('dashboard.graficos') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.graficos') ? $navActive : $navInactive }}">
                Gráficos por Veículo
            </a>
            <a href="{{ route('dashboard.tabela') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.tabela') ? $navActive : $navInactive }}">
                Cards
            </a>
            <a href="{{ route('dashboard.indicadores') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.indicadores') ? $navActive : $navInactive }}">
                Indicadores
            </a>
            <a href="{{ route('dashboard.demandas') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.demandas') ? $navActive : $navInactive }}">
                Demandas
            </a>
        </div>
    </nav>

    {{-- Conteúdo principal --}}
    <div class="min-w-0 flex-1 overflow-auto bg-slate-50 px-6 py-8 dark:bg-black lg:px-10">

        {{-- Cabeçalho --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Demandas — Poli Macaé</h1>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Indicadores e distribuição das demandas de transporte</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Atualizado</p>
                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $agora->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        @if($total === 0)
            <p class="py-24 text-center text-sm text-zinc-400 dark:text-zinc-600">Nenhuma demanda cadastrada.</p>
        @else

        {{-- KPIs --}}
        @php
            $kpis = [
                ['label' => 'Total de demandas',   'valor' => $total,                    'sub' => 'no período',                  'cor' => 'text-zinc-900 dark:text-zinc-100', 'bar' => 'bg-zinc-400', 'info' => 'Todas as demandas cadastradas (não excluídas), independente de status ou origem do cadastro.'],
                ['label' => 'Em aberto',            'valor' => $emAberto,                 'sub' => "{$pendentes} pend. · {$emAndamento} em and.", 'cor' => 'text-blue-600 dark:text-blue-400', 'bar' => 'bg-blue-500', 'info' => 'Demandas ainda não encerradas: soma de Pendentes e Em Andamento. Não inclui Finalizadas nem Canceladas.'],
                ['label' => 'Vencidas',             'valor' => $vencidas,                 'sub' => 'prazo estourado, em aberto',  'cor' => 'text-rose-600 dark:text-rose-400', 'bar' => 'bg-rose-500', 'info' => 'Demandas em aberto (Pendente ou Em Andamento) cujo prazo de referência já passou. Só conta as que têm prazo definido.'],
                ['label' => 'Vence em 24h',         'valor' => $venceEm24h,               'sub' => 'requer atenção',              'cor' => 'text-amber-600 dark:text-amber-400', 'bar' => 'bg-amber-500', 'info' => 'Demandas em aberto cujo prazo de referência vence nas próximas 24 horas a partir de agora.'],
                ['label' => 'Não classificadas',    'valor' => $naoClassificadas,         'sub' => 'sem tipo, em aberto',         'cor' => 'text-zinc-600 dark:text-zinc-300', 'bar' => 'bg-zinc-300 dark:bg-zinc-600', 'info' => 'Demandas em aberto sem tipo definido (Load/Backload/Transferência). Ocorre na integração quando a API não informa origem/destino.'],
                ['label' => 'Tempo médio atend.',   'valor' => $tempoMedioAtendMin > 0 ? $fmtMin($tempoMedioAtendMin) : '—', 'sub' => 'fim − início (finalizadas)', 'cor' => 'text-zinc-900 dark:text-zinc-100', 'bar' => 'bg-violet-500', 'info' => 'Média da duração (data/hora de fim − início) das demandas Finalizadas que têm início e fim preenchidos.'],
            ];
        @endphp
        <div class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($kpis as $k)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 {{ ($k['alerta'] ?? false) ? 'ring-1 ring-rose-200 dark:ring-rose-900/40' : '' }}">
                    <div class="h-1 w-full {{ $k['bar'] }}"></div>
                    <div class="p-2.5">
                        <p class="flex items-center text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500"><span class="truncate" title="{{ $k['label'] }}">{{ $k['label'] }}</span>{!! $info($k['info']) !!}</p>
                        <p class="mt-1 text-xl font-extrabold tabular-nums leading-none {{ $k['cor'] }}">{{ $k['valor'] }}</p>
                        <p class="mt-1 truncate text-[10px] text-zinc-400 dark:text-zinc-600" title="{{ $k['sub'] }}">{{ $k['sub'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Visão operacional — as perguntas da gestão de demandas --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Em atendimento agora, por tipo --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Em atendimento agora {!! $info('Demandas com status Em Andamento neste momento, separadas por tipo.') !!}</p>
                </div>
                <div class="p-4">
                    <p class="text-3xl font-extrabold tabular-nums text-blue-600 dark:text-blue-400">{{ $emAndamento }}</p>
                    <div class="mt-3 space-y-1.5">
                        @forelse($emAtendimentoPorTipo as $tipoLabel => $qtd)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $tipoLabel }}</span>
                                <span class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $qtd }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400 dark:text-zinc-600">Nenhuma em atendimento.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Vencem hoje + realizadas por tipo --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Vencem hoje · Realizadas {!! $info('Vencem hoje: demandas em aberto com prazo para o dia de hoje. Realizadas: total de finalizadas por tipo, desde o início.') !!}</p>
                </div>
                <div class="p-4">
                    <p class="text-3xl font-extrabold tabular-nums {{ $venceHoje > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">{{ $venceHoje }}</p>
                    <p class="text-[10px] text-zinc-400 dark:text-zinc-600">vencem hoje</p>
                    <div class="mt-3 space-y-1.5">
                        @forelse($finalizadasPorTipo as $tipoLabel => $qtd)
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $tipoLabel }} realizadas</span>
                                <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $qtd }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-zinc-400 dark:text-zinc-600">Nenhuma finalizada ainda.</p>
                        @endforelse
                        @if($finalizadasPorTipo->isNotEmpty())
                            <div class="flex items-center justify-between border-t border-slate-100 pt-1.5 text-xs dark:border-zinc-800">
                                <span class="font-semibold text-zinc-600 dark:text-zinc-300">Total realizadas</span>
                                <span class="font-bold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $finalizadasPorTipo->sum() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Veículo destaque --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Veículo destaque {!! $info('Veículo com maior número de demandas e veículo com maior média de itens por demanda (itens ÷ demandas do veículo).') !!}</p>
                </div>
                <div class="space-y-3 p-4">
                    @if($veiculoTopDemandas)
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Mais demandas</p>
                            <p class="text-lg font-extrabold text-zinc-900 dark:text-zinc-100">{{ $veiculoTopDemandas['prefixo'] }}
                                <span class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">· {{ $veiculoTopDemandas['demandas'] }} demanda(s)</span></p>
                        </div>
                    @endif
                    @if($veiculoTopMediaItens)
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Maior média de itens</p>
                            <p class="text-lg font-extrabold text-zinc-900 dark:text-zinc-100">{{ $veiculoTopMediaItens['prefixo'] }}
                                <span class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">· {{ $veiculoTopMediaItens['media_itens'] }} itens/demanda</span></p>
                        </div>
                    @endif
                    @unless($veiculoTopDemandas)
                        <p class="text-xs text-zinc-400 dark:text-zinc-600">Nenhuma demanda com veículo vinculado.</p>
                    @endunless
                </div>
            </div>

            {{-- Tendência 7 dias --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Tendência (7 dias) {!! $info('Criadas vs finalizadas nos últimos 7 dias. Boa: finalizando no mesmo ritmo (ou mais rápido) do que cria — a fila não cresce. Ruim: criando mais do que finaliza — acúmulo de demandas.') !!}</p>
                </div>
                <div class="p-4">
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold
                                 {{ $tendenciaBoa ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400' }}">
                        {{ $tendenciaBoa ? '▲ Boa' : '▼ Ruim' }}
                    </span>
                    <p class="mt-2 text-[10px] leading-snug text-zinc-400 dark:text-zinc-500">
                        Regra: <strong>Boa</strong> = finalizadas ≥ criadas nos últimos 7 dias (a fila não cresce);
                        <strong>Ruim</strong> = criadas &gt; finalizadas (demandas acumulando).
                    </p>
                    <div class="mt-3 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Criadas</span>
                            <span class="font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $criadas7d }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500 dark:text-zinc-400">Finalizadas</span>
                            <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $finalizadas7d }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-100 pt-1.5 dark:border-zinc-800">
                            <span class="text-zinc-500 dark:text-zinc-400">Saldo da fila</span>
                            <span class="font-semibold tabular-nums {{ $criadas7d - $finalizadas7d > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $criadas7d - $finalizadas7d > 0 ? '+' : '' }}{{ $criadas7d - $finalizadas7d }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Prioridade + Atenção + Tempo médio por tipo --}}
        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 xl:col-span-2">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Merecem atenção agora {!! $info('Demandas em aberto ordenadas pelo vencimento (vencidas primeiro, depois prazo mais próximo); empate decidido pelo maior número de itens. A primeira da lista é a prioridade nº 1.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Vencimento + nº de itens por viagem — a 1ª é a prioridade</p>
                </div>
                @if($atencao->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-zinc-400 dark:text-zinc-600">Nenhuma demanda em aberto. 🎉</p>
                @else
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 text-[10px] uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                            <tr>
                                <th class="px-4 py-2"></th>
                                <th class="px-2 py-2">Demanda</th>
                                <th class="px-2 py-2">Veículo</th>
                                <th class="px-2 py-2">Tipo</th>
                                <th class="px-2 py-2">Vencimento</th>
                                <th class="px-2 py-2 text-right">Itens</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                            @foreach($atencao as $i => $d)
                                @php $vencida = $d->prazo_demanda?->isPast() === true; @endphp
                                <tr class="{{ $i === 0 ? 'bg-rose-50/60 dark:bg-rose-950/20' : '' }}">
                                    <td class="px-4 py-2">
                                        @if($i === 0)
                                            <span class="inline-flex items-center rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white">PRIORIDADE</span>
                                        @else
                                            <span class="tabular-nums text-zinc-400 dark:text-zinc-600">{{ $i + 1 }}º</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-2">
                                        <a href="{{ route('demandas.edit', $d) }}" class="font-mono font-semibold text-blue-600 hover:underline dark:text-blue-400">{{ $d->numero_demanda }}</a>
                                    </td>
                                    <td class="px-2 py-2 text-zinc-600 dark:text-zinc-400">{{ $d->equipamento?->prefixo ?? '—' }}</td>
                                    <td class="px-2 py-2 text-zinc-600 dark:text-zinc-400">{{ $d->tipo_demanda?->label() ?? '—' }}</td>
                                    <td class="px-2 py-2 {{ $vencida ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $d->prazo_demanda?->format('d/m/Y H:i') ?? 'sem prazo' }}{{ $vencida ? ' ⚠' : '' }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $d->itensEncerrados() }}/{{ $d->itens->count() }}</td>
                                    <td class="px-4 py-2 text-zinc-500 dark:text-zinc-400">{{ $d->status_demanda->label() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Tempo médio por tipo {!! $info('Barra: média da duração (fim − início) das demandas finalizadas de cada tipo. Abaixo de cada barra, a média por item (minutos totais do tipo ÷ itens), que normaliza demandas maiores.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">fim − início das finalizadas · por demanda e por item</p>
                </div>
                <div class="space-y-4 p-4">
                    @php
                        $coresTipo = ['Load' => 'bg-blue-500', 'Backload' => 'bg-amber-500', 'Transferência' => 'bg-violet-500'];
                        $maxTempoTipo = max(1, collect($tempoMedioPorTipo)->max('media_demanda') ?? 1);
                    @endphp
                    @forelse($tempoMedioPorTipo as $tipoLabel => $t)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $tipoLabel }}</span>
                                <span class="font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $fmtMin($t['media_demanda']) }}</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full {{ $coresTipo[$tipoLabel] ?? 'bg-zinc-400' }}"
                                     style="width: {{ max(4, round($t['media_demanda'] / $maxTempoTipo * 100)) }}%"></div>
                            </div>
                            <p class="mt-1 text-[10px] text-zinc-400 dark:text-zinc-600">
                                por item: <span class="font-semibold text-zinc-500 dark:text-zinc-400">{{ $t['media_item'] !== null ? $fmtMin($t['media_item']) : '—' }}</span>
                                · {{ $t['demandas'] }} demanda(s), {{ $t['itens'] }} item(ns)
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 dark:text-zinc-600">Nenhuma finalizada com início e fim.</p>
                    @endforelse
                    @if($tempoMedioAtendMin > 0)
                        <div class="flex items-center justify-between border-t border-slate-100 pt-3 dark:border-zinc-800">
                            <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">Geral (todas)</span>
                            <span class="text-sm font-bold tabular-nums text-violet-600 dark:text-violet-400">{{ $fmtMin($tempoMedioAtendMin) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Linha 1: Status · Tipo · Prazo · Fonte --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-4">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Por Status {!! $info('Distribuição das demandas pelos status do ciclo de vida: Pendente → Em Andamento (quando há início e veículo) → Finalizado, ou Cancelada. Total no centro do gráfico.') !!}</p>
                </div>
                <div class="p-4"><div id="chart-status"></div></div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Por Tipo {!! $info('Load: destino é BMAC, PACU ou PBG. Backload: origem é um desses pontos. Caso contrário, Transferência. Não classificada: demanda de integração sem origem/destino informados pela API.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Load / Backload / Transferência (regra BMAC · PACU · PBG)</p>
                </div>
                <div class="p-4"><div id="chart-tipo"></div></div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Cumprimento de Prazo {!! $info('Base: demandas com prazo de referência definido, exceto canceladas. Vencida = em aberto com prazo já passado, ou finalizada após o prazo. No prazo = as demais. O centro mostra o % no prazo.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">% no prazo — demandas com prazo definido (exceto canceladas)</p>
                </div>
                <div class="p-4"><div id="chart-prazo"></div></div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Por Fonte {!! $info('Origem do dado no SAP, definida pelo início do número da demanda: começa com 50 → SAP LT; começa com 61 → SAP TM.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">SAP LT (50…) · SAP TM (61…)</p>
                </div>
                <div class="p-4"><div id="chart-fonte"></div></div>
            </div>
        </div>

        {{-- Linha 2: Evolução diária --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                <div>
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Evolução — últimos 14 dias {!! $info('Eixo X por dia. Criadas: pela data de cadastro (created_at). Finalizadas: pela data de conclusão (fim da demanda). A linha é a taxa de conclusão diária (Finalizadas ÷ Criadas), no eixo direito; dias sem criadas não têm ponto.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Criadas por data de cadastro · Finalizadas por data de conclusão</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span>Criadas</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Finalizadas</span>
                    <span class="flex items-center gap-1.5"><span class="h-0.5 w-3.5 rounded-sm bg-amber-500"></span>Taxa de conclusão</span>
                </div>
            </div>
            <div class="p-4"><div id="chart-evolucao"></div></div>
        </div>

        {{-- Linha 3: Top rotas · Top veículos --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Top 10 Rotas {!! $info('As 10 rotas (origem → destino) com mais demandas. Cada barra é empilhada por status e o número ao final é o total da rota. Considera apenas demandas com origem e destino informados.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Origem → Destino mais frequentes</p>
                </div>
                <div class="p-4">
                    @if(count($topRotas) === 0)
                        <p class="py-10 text-center text-xs text-zinc-400 dark:text-zinc-600">Sem rotas com origem e destino.</p>
                    @else
                        <div id="chart-rotas"></div>
                    @endif
                </div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="flex items-center text-sm font-semibold text-zinc-800 dark:text-zinc-200">Top 10 Veículos {!! $info('Os 10 veículos (prefixo) com mais demandas associadas. Cada barra é empilhada por status e o número ao final é o total do veículo. Considera apenas demandas com veículo vinculado.') !!}</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Nº de demandas por prefixo</p>
                </div>
                <div class="p-4">
                    @if(count($topVeiculos) === 0)
                        <p class="py-10 text-center text-xs text-zinc-400 dark:text-zinc-600">Sem veículos associados.</p>
                    @else
                        <div id="chart-veiculos"></div>
                    @endif
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var isDark  = document.documentElement.classList.contains('dark');
            var lblClr  = isDark ? '#a1a1aa' : '#71717a';
            var gridClr = isDark ? '#27272a' : '#f1f5f9';
            var mode    = isDark ? 'dark' : 'light';

            function fmtMin(m) {
                m = Math.abs(Math.round(m));
                var d = Math.floor(m / 1440), h = Math.floor((m % 1440) / 60), mn = m % 60;
                if (d > 0) { return d + 'd ' + h + 'h ' + mn + 'm'; }
                if (h > 0) { return h + 'h ' + mn + 'm'; }
                return mn + 'm';
            }

            var porStatus   = @json($porStatus);
            var porTipo     = @json($porTipo);
            var porPrazo    = @json($porPrazo);
            var pctNoPrazo  = @json($pctNoPrazo);
            var porFonte    = @json($porFonte);
            var evolucao    = @json($evolucao);
            var topRotas    = @json($topRotas);
            var topVeiculos = @json($topVeiculos);
            var statusMeta  = @json($statusMeta);

            function stackSeries(rows) {
                return statusMeta.map(function (s) {
                    return { name: s.label, data: rows.map(function (r) { return r.por_status[s.key]; }) };
                });
            }
            var statusColors = statusMeta.map(function (s) { return s.cor; });

            // Donut — Status
            new ApexCharts(document.getElementById('chart-status'), {
                chart: { type: 'donut', height: 260, background: 'transparent' },
                series: porStatus.map(function (s) { return s.valor; }),
                labels: porStatus.map(function (s) { return s.label; }),
                colors: porStatus.map(function (s) { return s.cor; }),
                legend: { position: 'bottom', labels: { colors: lblClr } },
                dataLabels: { enabled: true, formatter: function (val, o) { return o.w.config.series[o.seriesIndex]; } },
                plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total', color: lblClr, formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } } } } } },
                stroke: { colors: [isDark ? '#18181b' : '#fff'] },
                tooltip: { theme: mode },
                theme: { mode: mode },
            }).render();

            // Donut — Cumprimento de prazo (No prazo vs Vencidas)
            new ApexCharts(document.getElementById('chart-prazo'), {
                chart: { type: 'donut', height: 260, background: 'transparent' },
                series: porPrazo.map(function (s) { return s.valor; }),
                labels: porPrazo.map(function (s) { return s.label; }),
                colors: porPrazo.map(function (s) { return s.cor; }),
                legend: { position: 'bottom', labels: { colors: lblClr } },
                dataLabels: { enabled: true, formatter: function (val, o) { return o.w.config.series[o.seriesIndex]; } },
                plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'No prazo', color: lblClr, formatter: function () { return pctNoPrazo + '%'; } } } } } },
                stroke: { colors: [isDark ? '#18181b' : '#fff'] },
                tooltip: { theme: mode },
                theme: { mode: mode },
            }).render();

            // Donut — Fonte (SAP LT x SAP TM)
            if (porFonte.length) {
                new ApexCharts(document.getElementById('chart-fonte'), {
                    chart: { type: 'donut', height: 260, background: 'transparent' },
                    series: porFonte.map(function (s) { return s.valor; }),
                    labels: porFonte.map(function (s) { return s.label; }),
                    colors: porFonte.map(function (s) { return s.cor; }),
                    legend: { position: 'bottom', labels: { colors: lblClr } },
                    dataLabels: { enabled: true, formatter: function (val, o) { return o.w.config.series[o.seriesIndex]; } },
                    stroke: { colors: [isDark ? '#18181b' : '#fff'] },
                    tooltip: { theme: mode },
                    theme: { mode: mode },
                }).render();
            }

            // Barra — Tipo
            new ApexCharts(document.getElementById('chart-tipo'), {
                chart: { type: 'bar', height: 260, background: 'transparent', toolbar: { show: false } },
                series: [{ name: 'Demandas', data: porTipo.map(function (t) { return t.valor; }) }],
                xaxis: { categories: porTipo.map(function (t) { return t.label; }), labels: { style: { colors: Array(porTipo.length).fill(lblClr), fontSize: '11px' } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                yaxis: { labels: { style: { colors: [lblClr] } } },
                colors: porTipo.map(function (t) { return t.cor; }),
                plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '55%', dataLabels: { position: 'top' } } },
                dataLabels: { enabled: true, offsetY: -18, style: { fontSize: '12px', fontWeight: '600', colors: [lblClr] } },
                legend: { show: false },
                grid: { borderColor: gridClr },
                tooltip: { theme: mode },
                theme: { mode: mode },
            }).render();

            // Combo — Evolução (barras Criadas/Finalizadas + linha Taxa de conclusão)
            var taxaVals = evolucao.map(function (e) { return e.taxa; }).filter(function (v) { return v !== null; });
            var maxTaxa  = Math.max.apply(null, [100].concat(taxaVals));
            new ApexCharts(document.getElementById('chart-evolucao'), {
                chart: { type: 'line', height: 300, background: 'transparent', toolbar: { show: false } },
                series: [
                    { name: 'Criadas', type: 'column', data: evolucao.map(function (e) { return e.criadas; }) },
                    { name: 'Finalizadas', type: 'column', data: evolucao.map(function (e) { return e.finalizadas; }) },
                    { name: 'Taxa de conclusão', type: 'line', data: evolucao.map(function (e) { return e.taxa; }) },
                ],
                xaxis: { categories: evolucao.map(function (e) { return e.dia; }), labels: { style: { colors: Array(evolucao.length).fill(lblClr), fontSize: '11px' } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                yaxis: [
                    { seriesName: 'Criadas', labels: { style: { colors: [lblClr] } } },
                    { seriesName: 'Finalizadas', show: false },
                    { seriesName: 'Taxa de conclusão', opposite: true, min: 0, max: maxTaxa, tickAmount: 5, labels: { style: { colors: [lblClr] }, formatter: function (v) { return Math.round(v) + '%'; } } },
                ],
                colors: ['#3b82f6', '#10b981', '#f59e0b'],
                stroke: { width: [0, 0, 2.5], curve: 'straight' },
                markers: { size: [0, 0, 4] },
                plotOptions: { bar: { borderRadius: 3, columnWidth: '65%', dataLabels: { position: 'top' } } },
                dataLabels: {
                    enabled: true, enabledOnSeries: [0, 1, 2], offsetY: -16,
                    style: { fontSize: '10px', fontWeight: '600', colors: [lblClr, lblClr, '#f59e0b'] },
                    background: { enabled: false },
                    formatter: function (v, opts) {
                        if (opts.seriesIndex === 2) { return v === null ? '' : Math.round(v) + '%'; }
                        return v > 0 ? v : '';
                    },
                },
                legend: { show: false },
                grid: { borderColor: gridClr },
                tooltip: { theme: mode, shared: true, intersect: false, y: { formatter: function (v, opts) { return opts && opts.seriesIndex === 2 ? (v === null ? '—' : Math.round(v) + '%') : v; } } },
                theme: { mode: mode },
            }).render();

            // Barra horizontal empilhada por status — Top rotas
            if (topRotas.length) {
                new ApexCharts(document.getElementById('chart-rotas'), {
                    chart: { type: 'bar', stacked: true, height: Math.max(260, topRotas.length * 36), background: 'transparent', toolbar: { show: false } },
                    series: stackSeries(topRotas),
                    xaxis: { categories: topRotas.map(function (r) { return r.rota; }), labels: { style: { colors: [lblClr] } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                    yaxis: { labels: { style: { colors: [lblClr], fontSize: '11px' } } },
                    colors: statusColors,
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%', dataLabels: { total: { enabled: true, style: { fontSize: '11px', fontWeight: '700', color: lblClr } } } } },
                    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: '600', colors: ['#fff'] }, formatter: function (v) { return v > 0 ? v : ''; } },
                    legend: { show: true, position: 'bottom', labels: { colors: lblClr } },
                    grid: { borderColor: gridClr },
                    tooltip: { theme: mode },
                    theme: { mode: mode },
                }).render();
            }

            // Barra horizontal empilhada por status — Top veículos
            if (topVeiculos.length) {
                new ApexCharts(document.getElementById('chart-veiculos'), {
                    chart: { type: 'bar', stacked: true, height: Math.max(260, topVeiculos.length * 36), background: 'transparent', toolbar: { show: false } },
                    series: stackSeries(topVeiculos),
                    xaxis: { categories: topVeiculos.map(function (v) { return v.prefixo; }), labels: { style: { colors: [lblClr] } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                    yaxis: { labels: { style: { colors: [lblClr], fontSize: '11px' } } },
                    colors: statusColors,
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%', dataLabels: { total: { enabled: true, style: { fontSize: '11px', fontWeight: '700', color: lblClr } } } } },
                    dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: '600', colors: ['#fff'] }, formatter: function (v) { return v > 0 ? v : ''; } },
                    legend: { show: true, position: 'bottom', labels: { colors: lblClr } },
                    grid: { borderColor: gridClr },
                    tooltip: { theme: mode },
                    theme: { mode: mode },
                }).render();
            }
        });
        </script>

        @endif
    </div>
</div>

<script>
(function () {
    var targets = [1, 11, 21, 31, 41, 51];
    var now = new Date();
    var m   = now.getMinutes();
    var s   = now.getSeconds();
    var ms  = now.getMilliseconds();
    var next = null;
    for (var i = 0; i < targets.length; i++) {
        if (targets[i] > m) { next = targets[i]; break; }
    }
    var delay = next !== null
        ? (next - m) * 60000 - s * 1000 - ms
        : (60 - m + targets[0]) * 60000 - s * 1000 - ms;
    setTimeout(function () { location.reload(); }, delay);
})();
</script>

</x-layouts.app>
