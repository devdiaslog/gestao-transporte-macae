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
                ['label' => 'Total de demandas',   'valor' => $total,                    'sub' => 'no período',                  'cor' => 'text-zinc-900 dark:text-zinc-100', 'bar' => 'bg-zinc-400'],
                ['label' => 'Em aberto',            'valor' => $emAberto,                 'sub' => "{$pendentes} pend. · {$emAndamento} em and.", 'cor' => 'text-blue-600 dark:text-blue-400', 'bar' => 'bg-blue-500'],
                ['label' => 'Vencidas',             'valor' => $vencidas,                 'sub' => 'prazo estourado, em aberto',  'cor' => 'text-rose-600 dark:text-rose-400', 'bar' => 'bg-rose-500'],
                ['label' => 'Vence em 24h',         'valor' => $venceEm24h,               'sub' => 'requer atenção',              'cor' => 'text-amber-600 dark:text-amber-400', 'bar' => 'bg-amber-500'],
                ['label' => 'Não classificadas',    'valor' => $naoClassificadas,         'sub' => 'sem tipo, em aberto',         'cor' => 'text-zinc-600 dark:text-zinc-300', 'bar' => 'bg-zinc-300 dark:bg-zinc-600'],
                ['label' => 'Taxa de conclusão',    'valor' => $taxaConclusao.'%',        'sub' => "{$finalizadas} fin. · {$canceladas} canc.", 'cor' => 'text-emerald-600 dark:text-emerald-400', 'bar' => 'bg-emerald-500'],
                ['label' => 'Tempo médio atend.',   'valor' => $tempoMedioAtendMin > 0 ? $fmtMin($tempoMedioAtendMin) : '—', 'sub' => 'fim − início (finalizadas)', 'cor' => 'text-zinc-900 dark:text-zinc-100', 'bar' => 'bg-violet-500'],
            ];
        @endphp
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            @foreach($kpis as $k)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 {{ ($k['alerta'] ?? false) ? 'ring-1 ring-rose-200 dark:ring-rose-900/40' : '' }}">
                    <div class="h-1 w-full {{ $k['bar'] }}"></div>
                    <div class="p-3">
                        <p class="truncate text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500" title="{{ $k['label'] }}">{{ $k['label'] }}</p>
                        <p class="mt-1 text-2xl font-extrabold tabular-nums leading-none {{ $k['cor'] }}">{{ $k['valor'] }}</p>
                        <p class="mt-1 truncate text-[10px] text-zinc-400 dark:text-zinc-600" title="{{ $k['sub'] }}">{{ $k['sub'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Linha 1: Status · Tipo · Cadastro --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Por Status</p>
                </div>
                <div class="p-4"><div id="chart-status"></div></div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Por Tipo</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Load / Backload / Transferência (regra BMAC · PACU · PBG)</p>
                </div>
                <div class="p-4"><div id="chart-tipo"></div></div>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Cumprimento de Prazo</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">% no prazo — demandas com prazo definido (exceto canceladas)</p>
                </div>
                <div class="p-4"><div id="chart-prazo"></div></div>
            </div>
        </div>

        {{-- Linha 2: Evolução diária --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Evolução — últimos 14 dias</p>
                <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span>Criadas</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Finalizadas</span>
                </div>
            </div>
            <div class="p-4"><div id="chart-evolucao"></div></div>
        </div>

        {{-- Linha 3: Top rotas · Top veículos --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Top 10 Rotas</p>
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
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Top 10 Veículos</p>
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
            var evolucao    = @json($evolucao);
            var topRotas    = @json($topRotas);
            var topVeiculos = @json($topVeiculos);

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

            // Barra agrupada — Evolução
            new ApexCharts(document.getElementById('chart-evolucao'), {
                chart: { type: 'bar', height: 280, background: 'transparent', toolbar: { show: false } },
                series: [
                    { name: 'Criadas', data: evolucao.map(function (e) { return e.criadas; }) },
                    { name: 'Finalizadas', data: evolucao.map(function (e) { return e.finalizadas; }) },
                ],
                xaxis: { categories: evolucao.map(function (e) { return e.dia; }), labels: { style: { colors: Array(evolucao.length).fill(lblClr), fontSize: '11px' } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                yaxis: { labels: { style: { colors: [lblClr] } } },
                colors: ['#3b82f6', '#10b981'],
                plotOptions: { bar: { borderRadius: 3, columnWidth: '65%' } },
                dataLabels: { enabled: false },
                legend: { show: false },
                grid: { borderColor: gridClr },
                tooltip: { theme: mode },
                theme: { mode: mode },
            }).render();

            // Barra horizontal — Top rotas
            if (topRotas.length) {
                new ApexCharts(document.getElementById('chart-rotas'), {
                    chart: { type: 'bar', height: Math.max(240, topRotas.length * 34), background: 'transparent', toolbar: { show: false } },
                    series: [{ name: 'Demandas', data: topRotas.map(function (r) { return r.total; }) }],
                    xaxis: { categories: topRotas.map(function (r) { return r.rota; }), labels: { style: { colors: [lblClr] } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                    yaxis: { labels: { style: { colors: [lblClr], fontSize: '11px' } } },
                    colors: ['#6366f1'],
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%', dataLabels: { position: 'center' } } },
                    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: '600', colors: ['#fff'] } },
                    legend: { show: false },
                    grid: { borderColor: gridClr },
                    tooltip: { theme: mode },
                    theme: { mode: mode },
                }).render();
            }

            // Barra horizontal — Top veículos
            if (topVeiculos.length) {
                new ApexCharts(document.getElementById('chart-veiculos'), {
                    chart: { type: 'bar', height: Math.max(240, topVeiculos.length * 34), background: 'transparent', toolbar: { show: false } },
                    series: [{ name: 'Demandas', data: topVeiculos.map(function (v) { return v.total; }) }],
                    xaxis: { categories: topVeiculos.map(function (v) { return v.prefixo; }), labels: { style: { colors: [lblClr] } }, axisBorder: { color: gridClr }, axisTicks: { color: gridClr } },
                    yaxis: { labels: { style: { colors: [lblClr], fontSize: '11px' } } },
                    colors: ['#0ea5e9'],
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%', dataLabels: { position: 'center' } } },
                    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: '600', colors: ['#fff'] } },
                    legend: { show: false },
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

</x-layouts.app>
