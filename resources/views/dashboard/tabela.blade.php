<x-layouts.app title="Cards — Poli Macaé" :no-header="true">

@php
    $fmtMin = function (int $min): string {
        $min = abs($min);
        $d = intdiv($min, 1440);
        $h = intdiv($min % 1440, 60);
        $m = $min % 60;
        return $d > 0 ? "{$d}d {$h}h {$m}m" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
    };

    $navActive   = 'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white';
    $navInactive = 'border-b-2 border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200';

    $statusHex = [
        'Ag-Carregamento'     => '#f59e0b',
        'Ag-Descarregamento'  => '#f97316',
        'Ag-Documentação'     => '#eab308',
        'Ag-Motorista'        => '#84cc16',
        'Disponível'          => '#84cc16',
        'Ag-Programação'      => '#06b6d4',
        'Carregado'           => '#10b981',
        'Carregando'          => '#14b8a6',
        'Em Trânsito'         => '#3b82f6',
        'Em Viagem'           => '#6366f1',
        'Em Operação Interna' => '#8b5cf6',
        'Descarregando'       => '#a855f7',
        'Descarregado'        => '#a855f7',
        'Manutenção'          => '#f43f5e',
        'Recusa'              => '#f43f5e',
        'Frota Reserva'       => '#a1a1aa',
        'Parado'              => '#a1a1aa',
        'Reserva'             => '#a1a1aa',
    ];
@endphp

<div class="-mx-4 flex h-full flex-col sm:-mx-6 lg:-mx-8">

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
        </div>
    </nav>

    {{-- Barra de info --}}
    <div class="shrink-0 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-2 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ $veiculos->count() }} veículos</span>
            <span class="text-xs text-zinc-400 dark:text-zinc-600">Não inclui status: Em Operação Interna · Frota Reserva · Manutenção</span>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" action="{{ route('dashboard.tabela') }}">
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
            <span class="text-xs text-zinc-400 dark:text-zinc-600">Atualizado: {{ $agora->format('H:i:s') }}</span>
        </div>
    </div>

    {{-- Cards com rolagem vertical --}}
    <div class="min-h-0 flex-1 overflow-y-auto bg-slate-50 p-4 dark:bg-black">
        @php
            $maxTracker = $veiculos->max('tracker_minutos') ?: 1;
            $maxStatus  = $veiculos->max('status_minutos')  ?: 1;
            $maxAtend   = $veiculos->max('atendimento_minutos') ?: 1;

            $colorFn = function (int $val, int $max): string {
                if ($val <= 0) { return 'text-zinc-300 dark:text-zinc-600'; }
                $pct = $val / $max;
                if ($pct > 0.66) { return 'text-rose-600 dark:text-rose-400'; }
                if ($pct > 0.33) { return 'text-amber-500 dark:text-amber-400'; }
                return 'text-emerald-600 dark:text-emerald-400';
            };
        @endphp
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8">
            @foreach($veiculos as $v)
            @php
                $trackerLabel = match($v['tracker_estado']) {
                    0       => 'Parado',
                    1       => 'Em Mov.',
                    default => 'Sem Sinal',
                };
                $clsTracker = $colorFn($v['tracker_minutos'], $maxTracker);
                $clsStatus  = $colorFn($v['status_minutos'],  $maxStatus);
                $clsAtend   = $colorFn($v['atendimento_minutos'], $maxAtend);
            @endphp
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="h-1 w-full bg-slate-200 dark:bg-zinc-700"></div>
                <div class="p-2.5">
                    {{-- Cabeçalho --}}
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-sm font-extrabold text-zinc-900 dark:text-zinc-100">{{ $v['prefixo'] }}</span>
                        <span class="font-mono text-[10px] text-zinc-400 dark:text-zinc-500">{{ $v['placa'] }}</span>
                    </div>

                    {{-- Status --}}
                    <div class="mt-1.5">
                        <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 truncate block">
                            {{ $v['status'] }}
                        </span>
                    </div>

                    {{-- Métricas --}}
                    <div class="mt-2 space-y-1 border-t border-slate-100 pt-2 dark:border-zinc-800">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">{{ $trackerLabel }}</span>
                            @if($v['tracker_minutos'] > 0)
                                <span class="text-[10px] font-bold tabular-nums {{ $clsTracker }}">{{ $fmtMin($v['tracker_minutos']) }}</span>
                            @else
                                <span class="text-[10px] text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Status</span>
                            @if($v['status_minutos'] > 0)
                                <span class="text-[10px] font-semibold tabular-nums {{ $clsStatus }}">{{ $fmtMin($v['status_minutos']) }}</span>
                            @else
                                <span class="text-[10px] text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Atend.</span>
                            @if($v['atendimento_minutos'] > 0)
                                <span class="text-[10px] font-semibold tabular-nums {{ $clsAtend }}">{{ $fmtMin($v['atendimento_minutos']) }}</span>
                            @else
                                <span class="text-[10px] text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            @if($veiculos->isEmpty())
            <div class="col-span-full py-16 text-center text-sm text-zinc-400 dark:text-zinc-600">
                Nenhum veículo encontrado.
            </div>
            @endif
        </div>
    </div>
</div>

<script>
window.TV_MODE = false;
(function () {
    var p = new URLSearchParams(window.location.search);
    if (p.get('tv') === '1') { localStorage.setItem('tvMode', '1'); }
    if (p.get('tv') === '0') { localStorage.removeItem('tvMode'); }
    window.TV_MODE = localStorage.getItem('tvMode') === '1';
    if (! window.TV_MODE) { return; }
    document.documentElement.style.fontSize = '21px';
    document.documentElement.classList.add('tv');
    var s = document.createElement('style');
    s.textContent = 'html.tv, html.tv body { overflow: hidden !important; height: 100vh !important; }';
    document.head.appendChild(s);
})();
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

</x-layouts.app>
