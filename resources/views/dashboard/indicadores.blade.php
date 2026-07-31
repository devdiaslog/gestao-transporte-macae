<x-layouts.app title="Indicadores — Poli Macaé" :no-header="true">

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
@endphp

<div class="-mx-4 flex min-h-screen flex-col sm:-mx-6 lg:-mx-8">

    {{-- Sub-menu superior --}}
    <nav class="shrink-0 border-b border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center gap-1 px-4 sm:px-6 lg:px-8">
            <button id="sidebar-toggle" type="button" title="Abrir/fechar menu"
                    class="-ml-1 mr-1 rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800/70">
                <span class="sr-only">Abrir menu</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
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
            <div class="ml-auto flex items-center gap-2 py-1.5 pr-1">
                @include('dashboard._atualizar')
                <form method="GET" class="flex items-center">
                <select name="grupo" onchange="this.form.submit()"
                        class="h-8 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-zinc-700 shadow-xs outline-none
                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                    <option value="">Todos os grupos</option>
                    @foreach($grupos as $chave => $label)
                        <option value="{{ $chave }}" @selected($grupo === $chave)>{{ $label }}</option>
                    @endforeach
                </select>
                </form>
            </div>
        </div>
    </nav>

    {{-- Conteúdo --}}
    <div class="min-w-0 flex-1 overflow-auto bg-slate-50 px-6 py-8 dark:bg-black lg:px-10">

        @if(! $snapshot)
            <div class="flex flex-col items-center justify-center gap-3 py-24 text-center text-zinc-400 dark:text-zinc-600">
                <p class="text-sm">Aguardando primeira captura via <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">/dashboard/capturar-status?key=…</code></p>
            </div>
        @else

        {{-- Cabeçalho --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Indicadores Operacionais</h1>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-600">Não inclui: Em Trânsito · Em Operação Interna</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Última atualização</p>
                <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $snapshot->capturado_em->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        {{-- ── Linha 1: KPI cards ──────────────────────────────────────────────── --}}
        @php
            // Pré-computa classes completas para Tailwind não purgar as variantes dinâmicas
            $manutBg  = $emManutencao === 0 ? 'bg-emerald-500' : ($pctManutencao <= 10 ? 'bg-amber-500'  : 'bg-rose-500');
            $manutNum = $emManutencao === 0 ? 'text-emerald-600 dark:text-emerald-400' : ($pctManutencao <= 10 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');
            $manutPct = $emManutencao === 0 ? 'text-emerald-500' : ($pctManutencao <= 10 ? 'text-amber-500'  : 'text-rose-500');

            $ssBg  = count($semSinalVeiculos) === 0 ? 'bg-emerald-500' : (count($semSinalVeiculos) <= 3 ? 'bg-amber-500'  : 'bg-rose-500');
            $ssNum = count($semSinalVeiculos) === 0 ? 'text-emerald-600 dark:text-emerald-400' : (count($semSinalVeiculos) <= 3 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');

            $mediaBg  = $mediaParadosMin < 240 ? 'bg-emerald-500' : ($mediaParadosMin < 480 ? 'bg-amber-500'  : 'bg-rose-500');
            $mediaNum = $mediaParadosMin < 240 ? 'text-emerald-600 dark:text-emerald-400' : ($mediaParadosMin < 480 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');

            $pctParados = $totalFrota > 0 ? round($parados->count() / $totalFrota * 100, 0) : 0;
            $paradosBg  = $pctParados <= 50 ? 'bg-emerald-500' : ($pctParados <= 70 ? 'bg-amber-500'  : 'bg-rose-500');
            $paradosNum = $pctParados <= 50 ? 'text-emerald-600 dark:text-emerald-400' : ($pctParados <= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400');
            $paradosPct = $pctParados <= 50 ? 'text-emerald-500' : ($pctParados <= 70 ? 'text-amber-500'  : 'text-rose-500');
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Card: Manutenção --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 w-full {{ $manutBg }}"></div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Indisponível — Manutenção</p>
                    <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                        Veículos com status "Manutenção" ÷ total capturado. Considera todos os status capturados.
                    </p>
                    <div class="mt-4 flex items-end gap-3">
                        <p class="text-5xl font-extrabold tabular-nums leading-none {{ $manutNum }}">
                            {{ $emManutencao }}
                        </p>
                        <div class="mb-1">
                            <p class="text-xl font-bold tabular-nums {{ $manutPct }}">{{ $pctManutencao }}%</p>
                            <p class="text-[11px] text-zinc-400">de {{ $totalFrota }} veículos</p>
                        </div>
                    </div>
                    @if(count($veiculosManutencao) > 0)
                        <div class="mt-4 space-y-1 border-t border-slate-100 pt-3 dark:border-zinc-800">
                            @foreach(array_slice($veiculosManutencao, 0, 5) as $v)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $v['cm'] }} · {{ $v['placa'] }}</span>
                                    <span class="text-[11px] tabular-nums text-zinc-400">{{ $fmtMin((int) abs($v['minutos'])) }}</span>
                                </div>
                            @endforeach
                            @if(count($veiculosManutencao) > 5)
                                <p class="text-[11px] text-zinc-400">+ {{ count($veiculosManutencao) - 5 }} outros</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card: Sem Sinal --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 w-full {{ $ssBg }}"></div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sem Sinal GPS</p>
                    <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                        Veículos cujo último envio GPS foi há mais de 3 horas. Considera todos os status capturados.
                    </p>
                    <div class="mt-4">
                        <p class="text-5xl font-extrabold tabular-nums leading-none {{ $ssNum }}">
                            {{ count($semSinalVeiculos) }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">veículos sem sinal</p>
                    </div>
                    @if(count($semSinalVeiculos) > 0)
                        <div class="mt-4 space-y-1 border-t border-slate-100 pt-3 dark:border-zinc-800">
                            @foreach(array_slice($semSinalVeiculos, 0, 5) as $v)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $v['cm'] }} · {{ $v['placa'] }}</span>
                                    <span class="text-[11px] tabular-nums text-zinc-400">{{ $fmtMin((int) ($v['tracker_minutos'] ?? 0)) }}</span>
                                </div>
                            @endforeach
                            @if(count($semSinalVeiculos) > 5)
                                <p class="text-[11px] text-zinc-400">+ {{ count($semSinalVeiculos) - 5 }} outros</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Card: Média parado --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 w-full {{ $mediaBg }}"></div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Média — Frota Parada</p>
                    <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                        Média de tempo dos veículos com rastreador Parado. Exclui: {{ implode(', ', $statusExcluidos) }}.
                    </p>
                    <div class="mt-4">
                        <p class="text-5xl font-extrabold tabular-nums leading-none {{ $mediaNum }}">
                            {{ $mediaParadosHHMM }}
                        </p>
                        <p class="mt-1 text-[11px] text-zinc-400">hh:mm · base de {{ $parados->count() }} veículos</p>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-3 dark:border-zinc-800">
                        <p class="text-[11px] text-zinc-400">
                            <span class="inline-flex items-center gap-2">
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span><span>&lt; 4h</span></span>
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span><span>4h – 8h</span></span>
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span><span>&gt; 8h</span></span>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Card: Total parado --}}
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 w-full {{ $paradosBg }}"></div>
                <div class="p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Parado Agora</p>
                    <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                        Veículos com rastreador Parado no momento da captura. Exclui: {{ implode(', ', $statusExcluidos) }}.
                    </p>
                    <div class="mt-4 flex items-end gap-3">
                        <p class="text-5xl font-extrabold tabular-nums leading-none {{ $paradosNum }}">
                            {{ $parados->count() }}
                        </p>
                        <div class="mb-1">
                            <p class="text-xl font-bold tabular-nums {{ $paradosPct }}">
                                {{ $totalFrota > 0 ? round($parados->count() / $totalFrota * 100, 0) : 0 }}%
                            </p>
                            <p class="text-[11px] text-zinc-400">da frota</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-3 dark:border-zinc-800">
                        <p class="text-[11px] text-zinc-400">
                            <span class="inline-flex items-center gap-2">
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-500"></span><span>≤ 50%</span></span>
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span><span>50 – 70%</span></span>
                                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-rose-500"></span><span>&gt; 70%</span></span>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Linha 2: Faixas de tempo parado ───────────────────────────────── --}}
        @php
            $faixaCfg = [
                24 => ['topBg' => 'bg-rose-600',   'badge' => 'bg-rose-600 text-white',   'num' => 'text-rose-600 dark:text-rose-400'],
                12 => ['topBg' => 'bg-rose-400',   'badge' => 'bg-rose-400 text-white',   'num' => 'text-rose-500 dark:text-rose-300'],
                6  => ['topBg' => 'bg-orange-500', 'badge' => 'bg-orange-500 text-white', 'num' => 'text-orange-600 dark:text-orange-400'],
                3  => ['topBg' => 'bg-amber-500',  'badge' => 'bg-amber-500 text-white',  'num' => 'text-amber-600 dark:text-amber-400'],
            ];
        @endphp

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($faixas as $faixa)
                @php
                    $cfg    = $faixaCfg[$faixa['horas']];
                    $topBg  = $cfg['topBg'];
                    $badge  = $cfg['badge'];
                    $numCor = $cfg['num'];
                @endphp
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="h-1 w-full {{ $topBg }}"></div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    Parado &gt; {{ $faixa['horas'] }}h
                                </p>
                                <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                                    Rastreador Parado há mais de {{ $faixa['horas'] }}h. Exclui: {{ implode(', ', $statusExcluidos) }}. Não inclui Em Trânsito e Em Operação Interna.
                                </p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-sm font-bold tabular-nums {{ $badge }}">
                                {{ $faixa['quantidade'] }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-end gap-2">
                            <p class="text-4xl font-extrabold tabular-nums leading-none {{ $numCor }}">
                                {{ $faixa['quantidade'] }}<span class="text-base font-medium text-zinc-400"> veíc.</span>
                            </p>
                            @if($faixa['pct'] !== null)
                                <div class="mb-1">
                                    <p class="text-xl font-bold tabular-nums {{ $numCor }}">{{ $faixa['pct'] }}%</p>
                                    <p class="text-[11px] text-zinc-400">da frota</p>
                                </div>
                            @endif
                        </div>

                        @if($faixa['quantidade'] === 0)
                            <p class="mt-4 text-xs text-zinc-400 dark:text-zinc-600">Nenhum veículo nesta faixa no momento</p>
                        @else
                            <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-3 dark:border-zinc-800">
                                @foreach($faixa['veiculos'] as $v)
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $v['cm'] }} · {{ $v['placa'] }}</span>
                                        <span class="shrink-0 text-[11px] font-bold tabular-nums {{ $numCor }}">
                                            {{ $fmtMin((int) ($v['tracker_minutos'] ?? 0)) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

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
