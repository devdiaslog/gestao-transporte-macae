<x-layouts.app title="Dashboard — Status da Frota">

<style>
/* Sub-menu lateral do dashboard */
#dash-sidebar {
    width: 200px;
    min-height: calc(100vh - 56px);
}
@media (max-width: 768px) {
    #dash-sidebar { display: none; }
}
</style>

<div class="-mx-4 -mt-4 flex min-h-screen sm:-mx-6 lg:-mx-8">

    {{-- Sub-menu lateral --}}
    <nav id="dash-sidebar"
         class="shrink-0 border-r border-slate-200 bg-white px-3 py-4 dark:border-zinc-800 dark:bg-zinc-950">
        <p class="mb-3 px-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-600">Dashboard</p>
        <ul class="space-y-0.5">
            <li>
                <a href="{{ route('dashboard.status') }}"
                   class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium
                          {{ request()->routeIs('dashboard.status') ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:bg-slate-50 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                    </svg>
                    Status da Frota
                </a>
            </li>
        </ul>
    </nav>

    {{-- Conteúdo principal --}}
    <div class="min-w-0 flex-1 overflow-auto px-4 py-4 sm:px-6 lg:px-8">

        {{-- Cabeçalho --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Status da Frota</h2>
                @if($snapshot)
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        Última captura: {{ $snapshot->capturado_em->format('d/m/Y H:i') }}
                    </p>
                @else
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">Nenhuma captura registrada ainda.</p>
                @endif
            </div>
        </div>

        @if(! $snapshot)
            <div class="mt-10 flex flex-col items-center justify-center gap-3 text-center text-zinc-400 dark:text-zinc-600">
                <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                <p class="text-sm">Aguardando primeira captura via <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">/dashboard/capturar-status?key=…</code></p>
            </div>
        @else
            @php
                $dados = collect($snapshot->dados)->sortByDesc('quantidade')->values();
                $total = $dados->sum('quantidade');

                $colorPalette = [
                    'Ag-Carregamento'      => ['bg' => 'bg-amber-500',   'text' => 'text-amber-600',  'dark' => 'dark:text-amber-400',  'hex' => '#f59e0b'],
                    'Ag-Descarregamento'   => ['bg' => 'bg-orange-500',  'text' => 'text-orange-600', 'dark' => 'dark:text-orange-400', 'hex' => '#f97316'],
                    'Ag-Documentação'      => ['bg' => 'bg-yellow-500',  'text' => 'text-yellow-600', 'dark' => 'dark:text-yellow-400', 'hex' => '#eab308'],
                    'Ag-Motorista'         => ['bg' => 'bg-lime-500',    'text' => 'text-lime-600',   'dark' => 'dark:text-lime-400',   'hex' => '#84cc16'],
                    'Disponível'           => ['bg' => 'bg-lime-500',    'text' => 'text-lime-600',   'dark' => 'dark:text-lime-400',   'hex' => '#84cc16'],
                    'Ag-Programação'       => ['bg' => 'bg-cyan-500',    'text' => 'text-cyan-600',   'dark' => 'dark:text-cyan-400',   'hex' => '#06b6d4'],
                    'Carregado'            => ['bg' => 'bg-emerald-500', 'text' => 'text-emerald-600','dark' => 'dark:text-emerald-400','hex' => '#10b981'],
                    'Carregando'           => ['bg' => 'bg-teal-500',    'text' => 'text-teal-600',   'dark' => 'dark:text-teal-400',   'hex' => '#14b8a6'],
                    'Em Trânsito'          => ['bg' => 'bg-blue-500',    'text' => 'text-blue-600',   'dark' => 'dark:text-blue-400',   'hex' => '#3b82f6'],
                    'Em Viagem'            => ['bg' => 'bg-indigo-500',  'text' => 'text-indigo-600', 'dark' => 'dark:text-indigo-400', 'hex' => '#6366f1'],
                    'Em Operação Interna'  => ['bg' => 'bg-violet-500',  'text' => 'text-violet-600', 'dark' => 'dark:text-violet-400', 'hex' => '#8b5cf6'],
                    'Descarregando'        => ['bg' => 'bg-purple-500',  'text' => 'text-purple-600', 'dark' => 'dark:text-purple-400', 'hex' => '#a855f7'],
                    'Descarregado'         => ['bg' => 'bg-purple-500',  'text' => 'text-purple-600', 'dark' => 'dark:text-purple-400', 'hex' => '#a855f7'],
                    'Manutenção'           => ['bg' => 'bg-rose-500',    'text' => 'text-rose-600',   'dark' => 'dark:text-rose-400',   'hex' => '#f43f5e'],
                    'Recusa'               => ['bg' => 'bg-rose-500',    'text' => 'text-rose-600',   'dark' => 'dark:text-rose-400',   'hex' => '#f43f5e'],
                    'Parado'               => ['bg' => 'bg-zinc-400',    'text' => 'text-zinc-500',   'dark' => 'dark:text-zinc-400',   'hex' => '#a1a1aa'],
                    'Reserva'              => ['bg' => 'bg-zinc-400',    'text' => 'text-zinc-500',   'dark' => 'dark:text-zinc-400',   'hex' => '#a1a1aa'],
                ];
                $defaultColor = ['bg' => 'bg-zinc-300', 'text' => 'text-zinc-500', 'dark' => 'dark:text-zinc-400', 'hex' => '#d4d4d8'];
            @endphp

            {{-- Cards de status --}}
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($dados as $item)
                    @php $cor = $colorPalette[$item['status']] ?? $defaultColor; @endphp
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-2">
                            <div class="h-2.5 w-2.5 mt-1.5 shrink-0 rounded-full {{ $cor['bg'] }}"></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-zinc-600 dark:text-zinc-400" title="{{ $item['status'] }}">
                                    {{ $item['status'] }}
                                </p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $item['quantidade'] }}
                                </p>
                                <p class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600">
                                    {{ number_format($item['media_horas'], 1, ',', '.') }}h média
                                    · {{ $total > 0 ? number_format(($item['quantidade'] / $total) * 100, 0) : 0 }}%
                                </p>
                            </div>
                        </div>
                        {{-- Barra de progresso --}}
                        <div class="mt-3 h-1 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full {{ $cor['bg'] }} opacity-70"
                                 style="width: {{ $total > 0 ? ($item['quantidade'] / $total) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Gráfico de rosca + gráfico de barras --}}
            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">

                {{-- Rosca --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Distribuição por Status</p>
                    <div id="chart-donut" class="min-h-[280px]"></div>
                </div>

                {{-- Barras horizontais — média de horas --}}
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Média de Horas no Status</p>
                    <div id="chart-bars" class="min-h-[280px]"></div>
                </div>
            </div>

            {{-- Totalizador --}}
            <div class="mt-4 rounded-xl border border-slate-200 bg-white px-5 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Total de veículos monitorados: <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $total }}</span>
                </p>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function () {
            var isDark = document.documentElement.classList.contains('dark');
            var labelColor  = isDark ? '#a1a1aa' : '#71717a';
            var bgColor     = isDark ? '#18181b' : '#ffffff';
            var borderColor = isDark ? '#27272a' : '#f1f5f9';

            var dados = @json($dados);
            var labels  = dados.map(function(d) { return d.status; });
            var counts  = dados.map(function(d) { return d.quantidade; });
            var horas   = dados.map(function(d) { return d.media_horas; });

            var paletteMap = @json(array_map(fn($v) => $v['hex'], $colorPalette));
            var defaultHex = '{{ $defaultColor['hex'] }}';
            var colors = labels.map(function(l) { return paletteMap[l] || defaultHex; });

            // Rosca
            new ApexCharts(document.getElementById('chart-donut'), {
                chart: { type: 'donut', height: 300, background: 'transparent', toolbar: { show: false } },
                series: counts,
                labels: labels,
                colors: colors,
                legend: { position: 'bottom', labels: { colors: labelColor }, fontSize: '11px' },
                dataLabels: { enabled: false },
                plotOptions: { pie: { donut: { size: '60%', labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Total',
                        color: labelColor,
                        formatter: function(w) { return w.globals.seriesTotals.reduce(function(a,b){ return a+b; }, 0); }
                    }
                } } } },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                theme: { mode: isDark ? 'dark' : 'light' },
            }).render();

            // Barras
            new ApexCharts(document.getElementById('chart-bars'), {
                chart: { type: 'bar', height: 300, background: 'transparent', toolbar: { show: false } },
                series: [{ name: 'Horas', data: horas }],
                xaxis: { categories: labels, labels: { style: { colors: Array(labels.length).fill(labelColor), fontSize: '11px' } } },
                yaxis: { labels: { style: { colors: [labelColor] }, formatter: function(v) { return v.toFixed(1) + 'h'; } } },
                colors: colors,
                plotOptions: { bar: { distributed: true, borderRadius: 4, horizontal: false, columnWidth: '55%' } },
                legend: { show: false },
                dataLabels: { enabled: false },
                grid: { borderColor: borderColor },
                tooltip: { theme: isDark ? 'dark' : 'light', y: { formatter: function(v) { return v.toFixed(1) + 'h'; } } },
                theme: { mode: isDark ? 'dark' : 'light' },
            }).render();
            }); // DOMContentLoaded
            </script>
        @endif
    </div>
</div>

</x-layouts.app>
