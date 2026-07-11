<x-layouts.app title="Status Operacional — Poli Macaé" :no-header="true">

<div class="-mx-4 flex min-h-screen flex-col sm:-mx-6 lg:-mx-8">

    {{-- Sub-menu superior --}}
    <nav class="shrink-0 border-b border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center gap-1 px-4 sm:px-6 lg:px-8">
            <p class="mr-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-600">Dashboard</p>
            <a href="{{ route('dashboard.status') }}"
               class="flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard.status') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}">
                Status da Frota
            </a>
            <a href="{{ route('dashboard.graficos') }}"
               class="flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard.graficos') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}">
                Gráficos por Veículo
            </a>
            <a href="{{ route('dashboard.tabela') }}"
               class="flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard.tabela') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}">
                Tabela Veículos
            </a>
        </div>
    </nav>

    {{-- Conteúdo principal --}}
    <div class="min-w-0 flex-1 overflow-auto bg-slate-50 px-6 py-8 dark:bg-black lg:px-10">

        @if(! $snapshot)
            <div class="flex flex-col items-center justify-center gap-3 py-24 text-center text-zinc-400 dark:text-zinc-600">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                <p class="text-sm">Aguardando primeira captura via <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">/dashboard/capturar-status?key=…</code></p>
            </div>
        @else
            @php
                $anteriorMap = collect($anterior?->dados ?? [])->keyBy('status');
                $dados = collect($snapshot->dados)->sortByDesc('quantidade')->values();

                $colorPalette = [
                    'Ag-Carregamento'     => ['hex' => '#f59e0b', 'bg' => 'bg-amber-500'],
                    'Ag-Descarregamento'  => ['hex' => '#f97316', 'bg' => 'bg-orange-500'],
                    'Ag-Documentação'     => ['hex' => '#eab308', 'bg' => 'bg-yellow-500'],
                    'Ag-Motorista'        => ['hex' => '#84cc16', 'bg' => 'bg-lime-500'],
                    'Disponível'          => ['hex' => '#84cc16', 'bg' => 'bg-lime-500'],
                    'Ag-Programação'      => ['hex' => '#06b6d4', 'bg' => 'bg-cyan-500'],
                    'Carregado'           => ['hex' => '#10b981', 'bg' => 'bg-emerald-500'],
                    'Carregando'          => ['hex' => '#14b8a6', 'bg' => 'bg-teal-500'],
                    'Em Trânsito'         => ['hex' => '#3b82f6', 'bg' => 'bg-blue-500'],
                    'Em Viagem'           => ['hex' => '#6366f1', 'bg' => 'bg-indigo-500'],
                    'Em Operação Interna' => ['hex' => '#8b5cf6', 'bg' => 'bg-violet-500'],
                    'Descarregando'       => ['hex' => '#a855f7', 'bg' => 'bg-purple-500'],
                    'Descarregado'        => ['hex' => '#a855f7', 'bg' => 'bg-purple-500'],
                    'Manutenção'          => ['hex' => '#f43f5e', 'bg' => 'bg-rose-500'],
                    'Recusa'              => ['hex' => '#f43f5e', 'bg' => 'bg-rose-500'],
                    'Frota Reserva'       => ['hex' => '#a1a1aa', 'bg' => 'bg-zinc-400'],
                    'Parado'              => ['hex' => '#a1a1aa', 'bg' => 'bg-zinc-400'],
                    'Reserva'             => ['hex' => '#a1a1aa', 'bg' => 'bg-zinc-400'],
                ];
                $defaultColor = ['hex' => '#a1a1aa', 'bg' => 'bg-zinc-400'];

                $fmtMin = function (int $min): string {
                    $min = abs($min);
                    $d = intdiv($min, 1440);
                    $h = intdiv($min % 1440, 60);
                    $m = $min % 60;
                    return $d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
                };
            @endphp

            {{-- Cabeçalho da página --}}
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Poli Macaé — {{ $dados->sum('quantidade') }} Veículos
                </h1>
                <div class="text-right">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Última captura</p>
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $snapshot->capturado_em->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>

            {{-- Grid de cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach($dados as $item)
                    @php
                        $cor       = $colorPalette[$item['status']] ?? $defaultColor;
                        $prevItem  = $anteriorMap->get($item['status']);
                        $prevMin   = $prevItem['media_minutos'] ?? null;
                        $diffPct   = ($prevMin && $prevMin > 0)
                            ? (int) round((($item['media_minutos'] - $prevMin) / $prevMin) * 100)
                            : null;
                        $tempoMedia = $fmtMin($item['media_minutos']);
                        $top1       = $item['top1'] ?? null;
                        $top1tempo  = $top1 ? $fmtMin($top1['minutos']) : null;
                    @endphp

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

                        {{-- Faixa colorida no topo --}}
                        <div class="h-1 w-full {{ $cor['bg'] }}"></div>

                        <div class="p-4">
                            {{-- Status --}}
                            <p class="truncate text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                               title="{{ $item['status'] }}">{{ $item['status'] }}</p>

                            {{-- Linha principal: qtd | tempo | d(-1) --}}
                            <div class="mt-3 flex items-end gap-2">
                                <div class="shrink-0">
                                    <p class="text-2xl font-bold tabular-nums leading-none text-zinc-900 dark:text-zinc-100">{{ $item['quantidade'] }}</p>
                                    <p class="mt-1 text-[10px] text-zinc-400">veículos</p>
                                </div>

                                <p class="flex-1 text-center text-4xl font-extrabold tabular-nums leading-none text-zinc-900 dark:text-zinc-100 sm:text-5xl">
                                    {{ $tempoMedia }}
                                </p>

                                <div class="shrink-0 text-right">
                                    @if($diffPct !== null)
                                        <p class="text-sm font-bold tabular-nums leading-none
                                                   {{ $diffPct > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $diffPct > 0 ? '▲' : '▼' }}&nbsp;{{ abs($diffPct) }}%
                                        </p>
                                    @else
                                        <p class="text-sm leading-none text-zinc-300 dark:text-zinc-700">—</p>
                                    @endif
                                    <p class="mt-1 text-[10px] text-zinc-400">d(-1)</p>
                                </div>
                            </div>

                            {{-- Top 1 — maior tempo, requer atenção --}}
                            @if($top1 && $top1tempo)
                                <div class="mt-3 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 dark:border-amber-900/40 dark:bg-amber-950/20">
                                    <svg class="h-3 w-3 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="flex-1 truncate text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                        {{ $top1['cm'] }} – {{ $top1['placa'] }}
                                    </span>
                                    <span class="shrink-0 tabular-nums text-[11px] font-bold text-amber-700 dark:text-amber-400">
                                        {{ $top1tempo }}
                                    </span>
                                </div>
                            @else
                                <div class="mt-3 h-7 rounded-lg bg-slate-50 dark:bg-zinc-800"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Gráficos divididos: Parado | Em Movimento + Sem Sinal --}}
            @php
                $veiculosBase = $dados->filter(fn ($d) => ! in_array($d['status'], ['Manutenção', 'Frota Reserva']))
                    ->flatMap(fn ($d) => $d['veiculos'] ?? [])
                    ->sortByDesc('tracker_minutos')
                    ->values();

                $veiculosParados   = $veiculosBase->filter(fn ($v) => ($v['tracker_estado'] ?? -1) === 0)->values();
                $veiculosMovimento = $veiculosBase->filter(fn ($v) => ($v['tracker_estado'] ?? -1) !== 0)->values();

                $widthParados   = max($veiculosParados->count() * 52, 500);
                $widthMovimento = max($veiculosMovimento->count() * 52, 500);
            @endphp

            @if($veiculosParados->isNotEmpty() || $veiculosMovimento->isNotEmpty())
            <div class="mt-6 grid grid-cols-2 gap-4">

                {{-- Gráfico 1: Parado --}}
                @if($veiculosParados->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Rastreador — Parado</p>
                        <span class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span>Parado
                        </span>
                    </div>
                    <div class="overflow-x-auto px-2 pb-2 pt-1">
                        <div id="chart-parados" style="min-width: {{ $widthParados }}px"></div>
                    </div>
                </div>
                @endif

                {{-- Gráfico 2: Em Movimento + Sem Sinal --}}
                @if($veiculosMovimento->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Rastreador — Em Movimento / Sem Sinal</p>
                        <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Em Movimento</span>
                            <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-zinc-400"></span>Sem Sinal</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto px-2 pb-2 pt-1">
                        <div id="chart-movimento" style="min-width: {{ $widthMovimento }}px"></div>
                    </div>
                </div>
                @endif

            </div>
            @endif

            <script>
            // TV MODE — detecta ?tv=1 / ?tv=0, persiste no localStorage
            window.TV_MODE = false;
            (function () {
                var p = new URLSearchParams(window.location.search);
                if (p.get('tv') === '1') { localStorage.setItem('tvMode', '1'); }
                if (p.get('tv') === '0') { localStorage.removeItem('tvMode'); }
                window.TV_MODE = localStorage.getItem('tvMode') === '1';
                if (! window.TV_MODE) { return; }

                // Escala rem para aumentar todas as fontes Tailwind proporcionalmente
                document.documentElement.style.fontSize = '21px';
                document.documentElement.classList.add('tv');

                // Remove scrollbars e fixa altura na viewport
                var s = document.createElement('style');
                s.textContent = [
                    'html.tv, html.tv body { overflow: hidden !important; height: 100vh !important; }',
                    'html.tv .overflow-x-auto { overflow: hidden !important; }',
                    'html.tv #chart-parados, html.tv #chart-movimento { min-width: 0 !important; width: 100% !important; }',
                ].join('');
                document.head.appendChild(s);
            })();
            </script>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var isDark  = document.documentElement.classList.contains('dark');
                var lblClr  = isDark ? '#a1a1aa' : '#71717a';
                var gridClr = isDark ? '#27272a' : '#f1f5f9';
                var tv      = window.TV_MODE;
                var chartH  = tv
                    ? Math.max(180, Math.floor((window.innerHeight - 340) / 2))
                    : Math.max(200, Math.floor((window.innerHeight - 420) / 2));
                var yMax    = 36; // máximo 36 horas no eixo Y
                var lblSize = tv ? '14px' : (window.innerWidth >= 1280 ? '11px' : '9px');
                var axisSize = tv ? '13px' : '10px';

                function fmtMin(m) {
                    m = Math.abs(Math.round(m));
                    var d = Math.floor(m / 1440);
                    var h = Math.floor((m % 1440) / 60);
                    var mn = m % 60;
                    if (d > 0) { return d + 'd ' + h + 'h ' + mn + 'm'; }
                    if (h > 0) { return h + 'h ' + mn + 'm'; }
                    return mn + 'm';
                }

                function makeChart(elId, veiculos, colorFn) {
                    var el = document.getElementById(elId);
                    if (! el || ! veiculos.length) { return; }
                    var labels = veiculos.map(function(v) { return v.cm || v.placa; });
                    var data   = veiculos.map(function(v) { return +((v.tracker_minutos || 0) / 60).toFixed(2); });
                    var colors = veiculos.map(colorFn);
                    var maxVal = data.reduce(function(a, b) { return Math.max(a, b); }, 0);
                    var yAxisOpts = { labels: { style: { colors: [lblClr], fontSize: axisSize }, formatter: function(v) { return fmtMin(v * 60); } } };
                    if (maxVal > yMax) { yAxisOpts.max = yMax; }
                    new ApexCharts(el, {
                        chart: { type: 'bar', height: chartH, background: 'transparent', toolbar: { show: false } },
                        series: [{ name: 'Tempo', data: data }],
                        xaxis: {
                            categories: labels,
                            labels: { rotate: -45, style: { colors: Array(labels.length).fill(lblClr), fontSize: axisSize } },
                            axisBorder: { color: gridClr }, axisTicks: { color: gridClr },
                        },
                        yaxis: yAxisOpts,
                        colors: colors,
                        plotOptions: { bar: { distributed: true, borderRadius: 3, columnWidth: '60%', dataLabels: { position: 'top' } } },
                        dataLabels: {
                            enabled: true, offsetY: -18,
                            style: { fontSize: lblSize, fontWeight: '600', colors: [lblClr] },
                            formatter: function(v) { return fmtMin(v * 60); },
                        },
                        legend: { show: false },
                        grid: { borderColor: gridClr, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
                        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v) { return fmtMin(v * 60); } } },
                        theme: { mode: isDark ? 'dark' : 'light' },
                    }).render();
                }

                var parados   = @json($veiculosParados);
                var movimento = @json($veiculosMovimento);

                makeChart('chart-parados', parados, function() { return '#f43f5e'; });

                makeChart('chart-movimento', movimento, function(v) {
                    var estado = v.tracker_estado;
                    if (estado === 1 && (v.tracker_minutos || 0) > 180) { estado = -1; }
                    return estado === 1 ? '#10b981' : '#a1a1aa';
                });
            });
            </script>

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

        @endif
    </div>
</div>

</x-layouts.app>
