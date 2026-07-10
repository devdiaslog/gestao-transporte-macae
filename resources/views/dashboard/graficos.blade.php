<x-layouts.app title="Gráficos por Veículo — Poli Macaé" :no-header="true">

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
        </div>
    </nav>

    {{-- Conteúdo principal --}}
    <div class="min-w-0 flex-1 overflow-auto bg-slate-50 px-6 py-8 dark:bg-black lg:px-10">

        @if(! $snapshot)
            <div class="flex flex-col items-center justify-center gap-3 py-24 text-center text-zinc-400 dark:text-zinc-600">
                <p class="text-sm">Aguardando primeira captura via <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">/dashboard/capturar-status?key=…</code></p>
            </div>
        @else
            @php
                $statusGraficos = ['Ag-Carregamento', 'Carregado', 'Ag-Programação', 'Ag-Descarregamento', 'Recusa', 'Ag-Motorista'];

                $colorPalette = [
                    'Ag-Carregamento'    => ['hex' => '#f59e0b', 'bg' => 'bg-amber-500'],
                    'Carregado'          => ['hex' => '#10b981', 'bg' => 'bg-emerald-500'],
                    'Ag-Programação'     => ['hex' => '#06b6d4', 'bg' => 'bg-cyan-500'],
                    'Ag-Descarregamento' => ['hex' => '#f97316', 'bg' => 'bg-orange-500'],
                    'Recusa'             => ['hex' => '#f43f5e', 'bg' => 'bg-rose-500'],
                    'Ag-Motorista'       => ['hex' => '#84cc16', 'bg' => 'bg-lime-500'],
                ];
                $defaultColor = ['hex' => '#a1a1aa', 'bg' => 'bg-zinc-400'];

                $dados = collect($snapshot->dados)
                    ->filter(fn ($d) => in_array($d['status'], $statusGraficos) && count($d['veiculos'] ?? []) >= 1)
                    ->sortBy(fn ($d) => array_search($d['status'], $statusGraficos))
                    ->values();

                $hexPalette = array_map(fn ($v) => $v['hex'], $colorPalette);
            @endphp

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Gráficos por Veículo</h1>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Tempo individual por veículo no status atual</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Última captura</p>
                    <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $snapshot->capturado_em->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>

            @if($dados->isEmpty())
                <p class="py-16 text-center text-sm text-zinc-400 dark:text-zinc-600">Nenhum dos status selecionados possui veículos na captura atual.</p>
            @else
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach($dados as $item)
                        @php
                            $cor = $colorPalette[$item['status']] ?? $defaultColor;
                            $top1 = $item['top1'] ?? null;
                            if ($top1) {
                                $t = abs($top1['minutos']);
                                $td = intdiv($t, 1440); $th = intdiv($t % 1440, 60); $tm = $t % 60;
                                $top1tempo = $td > 0 ? "{$td}d {$th}h {$tm}m" : ($th > 0 ? "{$th}h {$tm}m" : "{$tm}m");
                            } else { $top1tempo = null; }
                        @endphp
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="h-1 w-full {{ $cor['bg'] }}"></div>
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $item['status'] }}</p>
                                        <p class="mt-0.5 text-sm text-zinc-400 dark:text-zinc-600">{{ $item['quantidade'] }} veículos</p>
                                    </div>
                                    @if($top1 && $top1tempo)
                                        <div class="flex shrink-0 items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 dark:border-amber-900/40 dark:bg-amber-950/20">
                                            <svg class="h-3 w-3 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">{{ $top1['cm'] }} – {{ $top1['placa'] }}</span>
                                            <span class="tabular-nums text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $top1tempo }}</span>
                                        </div>
                                    @endif
                                </div>
                                <div id="chart-g-{{ $loop->index }}" class="mt-4 min-h-[300px]"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var isDark  = document.documentElement.classList.contains('dark');
                var lblClr  = isDark ? '#a1a1aa' : '#71717a';
                var gridClr = isDark ? '#27272a' : '#f1f5f9';
                var hexMap  = @json($hexPalette);
                var defHex  = '#a1a1aa';

                function fmtMin(m) {
                    m = Math.abs(Math.round(m));
                    var d = Math.floor(m / 1440);
                    var h = Math.floor((m % 1440) / 60);
                    var mn = m % 60;
                    if (d > 0) { return d + 'd ' + h + 'h ' + mn + 'm'; }
                    if (h > 0) { return h + 'h ' + mn + 'm'; }
                    return mn + 'm';
                }

                @foreach($dados as $item)
                (function () {
                    var veiculos = @json($item['veiculos']);
                    var status   = @json($item['status']);
                    var barColor = hexMap[status] || defHex;
                    var labels   = veiculos.map(function(v) { return v.cm || v.placa; });
                    var data     = veiculos.map(function(v) { return +(Math.abs(v.minutos) / 60).toFixed(2); });

                    new ApexCharts(document.getElementById('chart-g-{{ $loop->index }}'), {
                        chart: { type: 'bar', height: Math.max(260, Math.floor((window.innerHeight - 220) * 0.42)), background: 'transparent', toolbar: { show: false } },
                        series: [{ name: 'Tempo', data: data }],
                        xaxis: {
                            categories: labels,
                            labels: { rotate: -45, style: { colors: Array(labels.length).fill(lblClr), fontSize: '10px' } },
                            axisBorder: { color: gridClr }, axisTicks: { color: gridClr },
                        },
                        yaxis: { labels: { style: { colors: [lblClr] }, formatter: function(v) { return fmtMin(v * 60); } } },
                        colors: [barColor],
                        plotOptions: { bar: { borderRadius: 3, columnWidth: '58%', dataLabels: { position: 'top' } } },
                        dataLabels: {
                            enabled: true, offsetY: -18,
                            style: { fontSize: window.innerWidth >= 1280 ? '12px' : '10px', fontWeight: '600', colors: [lblClr] },
                            formatter: function(v) { return fmtMin(v * 60); },
                        },
                        legend: { show: false },
                        grid: { borderColor: gridClr, yaxis: { lines: { show: true } }, xaxis: { lines: { show: false } } },
                        tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v) { return fmtMin(v * 60); } } },
                        theme: { mode: isDark ? 'dark' : 'light' },
                    }).render();
                })();
                @endforeach
            });
            </script>
        @endif
    </div>
</div>

</x-layouts.app>
