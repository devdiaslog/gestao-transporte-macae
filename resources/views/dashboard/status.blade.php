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
                Cards
            </a>
            <a href="{{ route('dashboard.indicadores') }}"
               class="flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard.indicadores') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}">
                Indicadores
            </a>
            <a href="{{ route('dashboard.demandas') }}"
               class="flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard.demandas') ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200' }}">
                Demandas
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
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Poli Macaé — {{ $dados->sum('quantidade') }} Veículos
                    </h1>
                    <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-600">
                        Não inclui: Em Trânsito · Em Operação Interna
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Última atualização</p>
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $snapshot->capturado_em->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>

            {{-- Grid de cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach($dados as $item)
                    @php
                        $cor        = $colorPalette[$item['status']] ?? $defaultColor;
                        $prevItem   = $anteriorMap->get($item['status']);
                        $prevMin    = $prevItem['media_minutos'] ?? null;
                        $diffPct    = ($prevMin && $prevMin > 0)
                            ? (int) round((($item['media_minutos'] - $prevMin) / $prevMin) * 100)
                            : null;
                        $tempoMedia = $fmtMin($item['media_minutos']);
                        $top5       = $item['top5'] ?? ($item['top1'] ? [$item['top1']] : []);
                    @endphp

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

                        {{-- Faixa colorida no topo --}}
                        <div class="h-1 w-full {{ $cor['bg'] }}"></div>

                        <div class="p-3">
                            {{-- Status --}}
                            <p class="truncate text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400"
                               title="{{ $item['status'] }}">{{ $item['status'] }}</p>

                            {{-- Linha resumo: qtd | média | d(-1) --}}
                            <div class="mt-2 flex items-center gap-2">
                                <div class="shrink-0">
                                    <p class="text-lg font-bold tabular-nums leading-none text-zinc-900 dark:text-zinc-100">{{ $item['quantidade'] }}</p>
                                    <p class="text-[10px] text-zinc-400">veíc.</p>
                                </div>

                                <p class="flex-1 text-center text-2xl font-extrabold tabular-nums leading-none text-zinc-900 dark:text-zinc-100">
                                    {{ $tempoMedia }}
                                </p>

                                <div class="shrink-0 text-right">
                                    @if($diffPct !== null)
                                        <p class="text-xs font-bold tabular-nums leading-none
                                                   {{ $diffPct > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $diffPct > 0 ? '▲' : '▼' }}&nbsp;{{ abs($diffPct) }}%
                                        </p>
                                    @else
                                        <p class="text-xs leading-none text-zinc-300 dark:text-zinc-700">—</p>
                                    @endif
                                    <p class="text-[10px] text-zinc-400">d(-1)</p>
                                </div>
                            </div>

                            {{-- Top 5 --}}
                            @if(count($top5) > 0)
                                <div class="mt-2 space-y-1 border-t border-slate-100 pt-2 dark:border-zinc-800">
                                    @foreach($top5 as $i => $tv)
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-3.5 text-center text-[9px] font-bold tabular-nums text-zinc-300 dark:text-zinc-600">{{ $i + 1 }}</span>
                                            <span class="flex-1 truncate text-[11px] font-medium text-zinc-700 dark:text-zinc-300">
                                                {{ $tv['cm'] }} <span class="text-zinc-400 dark:text-zinc-500">{{ $tv['placa'] }}</span>
                                            </span>
                                            <span class="shrink-0 tabular-nums text-[11px] font-bold text-zinc-600 dark:text-zinc-400">
                                                {{ $fmtMin($tv['minutos']) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-2 border-t border-slate-100 pt-2 dark:border-zinc-800">
                                    <p class="text-center text-[10px] text-zinc-300 dark:text-zinc-600">—</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Gráficos divididos: Parado | Em Movimento + Sem Sinal --}}
            @php
                $statusExcluidos = ['Manutenção', 'Frota Reserva', 'Reserva'];

                $veiculosBase = $dados->filter(fn ($d) => ! in_array($d['status'], $statusExcluidos))
                    ->flatMap(fn ($d) => $d['veiculos'] ?? [])
                    ->sortByDesc('tracker_minutos')
                    ->values();

                $veiculosParados   = $veiculosBase->filter(fn ($v) => ($v['tracker_estado'] ?? -1) === 0)->values();
                $veiculosMovimento = $veiculosBase->filter(fn ($v) => ($v['tracker_estado'] ?? -1) !== 0)->values();

                $widthParados   = max($veiculosParados->count() * 52, 500);
                $widthMovimento = max($veiculosMovimento->count() * 52, 500);

                $mediaParadosMin  = $veiculosParados->isNotEmpty()
                    ? (int) round($veiculosParados->avg('tracker_minutos'))
                    : 0;
                $mediaParadosHHMM = sprintf('%02d:%02d', intdiv($mediaParadosMin, 60), $mediaParadosMin % 60);
            @endphp

            @if($veiculosParados->isNotEmpty() || $veiculosMovimento->isNotEmpty())
            <div class="mt-6 flex flex-col gap-4">

                {{-- Gráfico 1: Parado — linha inteira --}}
                @if($veiculosParados->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Rastreador — Parado</p>
                            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                Exceto: {{ implode(', ', $statusExcluidos) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 dark:border-zinc-700 dark:bg-zinc-800">
                                <p class="text-[10px] font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Média</p>
                                <p class="tabular-nums text-sm font-bold leading-tight text-zinc-700 dark:text-zinc-200">{{ $mediaParadosHHMM }}</p>
                            </div>
                            <span class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span>Parado
                            </span>
                            <span class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                <span class="h-0.5 w-3.5 rounded-sm bg-indigo-500"></span>% Acumulado
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto px-2 pb-2 pt-1">
                        <div id="chart-parados" style="min-width: {{ $widthParados }}px"></div>
                    </div>
                </div>
                @endif

                {{-- Gráfico 2: Em Movimento + Sem Sinal — linha inteira --}}
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
                    ? Math.max(160, Math.floor((window.innerHeight - 320) / 2))
                    : Math.max(180, Math.floor((window.innerHeight - 380) / 2));
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

                function fmtMinHHMM(m) {
                    m = Math.abs(Math.round(m));
                    var h  = Math.floor(m / 60);
                    var mn = m % 60;
                    return (h < 10 ? '0' : '') + h + ':' + (mn < 10 ? '0' : '') + mn;
                }

                function makeChart(elId, veiculos, colorFn, pareto) {
                    var el = document.getElementById(elId);
                    if (! el || ! veiculos.length) { return; }
                    var labels = veiculos.map(function(v) { return v.cm || v.placa; });
                    var data   = veiculos.map(function(v) { return +((v.tracker_minutos || 0) / 60).toFixed(2); });
                    var colors = veiculos.map(colorFn);
                    var maxVal = data.reduce(function(a, b) { return Math.max(a, b); }, 0);
                    var yAxisOpts = { labels: { style: { colors: [lblClr], fontSize: axisSize }, formatter: function(v) { return fmtMin(v * 60); } } };
                    if (maxVal > yMax) { yAxisOpts.max = yMax; }

                    if (pareto) {
                        var total  = data.reduce(function(a, b) { return a + b; }, 0);
                        var cum    = 0;
                        var cumPct = data.map(function(v) { cum += v; return total > 0 ? +((cum / total) * 100).toFixed(1) : 0; });
                        var barColor  = colors[0] || '#f43f5e';
                        var lineColor = isDark ? '#818cf8' : '#4f46e5';
                        yAxisOpts.seriesName = 'Tempo';
                        new ApexCharts(el, {
                            chart: { type: 'line', height: chartH, background: 'transparent', toolbar: { show: false } },
                            series: [
                                { name: 'Tempo', type: 'column', data: data },
                                { name: '% Acumulado', type: 'line', data: cumPct },
                            ],
                            xaxis: {
                                categories: labels,
                                labels: { rotate: -45, style: { colors: Array(labels.length).fill(lblClr), fontSize: axisSize } },
                                axisBorder: { color: gridClr }, axisTicks: { color: gridClr },
                            },
                            yaxis: [
                                yAxisOpts,
                                {
                                    seriesName: '% Acumulado', opposite: true, min: 0, max: 100, tickAmount: 5,
                                    labels: { style: { colors: [lblClr], fontSize: axisSize }, formatter: function(v) { return Math.round(v) + '%'; } },
                                },
                            ],
                            colors: [barColor, lineColor],
                            stroke: { width: [0, 2.5], curve: 'straight' },
                            markers: { size: [0, 3.5] },
                            plotOptions: { bar: { borderRadius: 3, columnWidth: '60%', dataLabels: { position: 'top' } } },
                            dataLabels: {
                                enabled: true, enabledOnSeries: [0, 1], offsetY: -18,
                                style: { fontSize: lblSize, fontWeight: '600', colors: [lblClr, lineColor] },
                                formatter: function(v, opts) { return opts && opts.seriesIndex === 1 ? Math.round(v) + '%' : fmtMinHHMM(v * 60); },
                                background: { enabled: false },
                            },
                            legend: { show: false },
                            grid: { borderColor: gridClr, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
                            tooltip: {
                                theme: isDark ? 'dark' : 'light', shared: true, intersect: false,
                                y: { formatter: function(v, opts) { return opts && opts.seriesIndex === 1 ? Math.round(v) + '%' : fmtMin(v * 60); } },
                            },
                            theme: { mode: isDark ? 'dark' : 'light' },
                        }).render();
                        return;
                    }

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
                            formatter: function(v) { return fmtMinHHMM(v * 60); },
                        },
                        legend: { show: false },
                        grid: { borderColor: gridClr, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
                        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v) { return fmtMin(v * 60); } } },
                        theme: { mode: isDark ? 'dark' : 'light' },
                    }).render();
                }

                var parados   = @json($veiculosParados);
                var movimento = @json($veiculosMovimento);

                makeChart('chart-parados', parados, function() { return '#f43f5e'; }, true);

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
