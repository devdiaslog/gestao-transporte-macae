<x-layouts.app title="Tabela Veículos — Poli Macaé" :no-header="true">

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
                Tabela Veículos
            </a>
            <a href="{{ route('dashboard.indicadores') }}"
               class="flex items-center gap-2 px-3 py-3 text-sm font-medium transition-colors {{ request()->routeIs('dashboard.indicadores') ? $navActive : $navInactive }}">
                Indicadores
            </a>
        </div>
    </nav>

    {{-- Barra de info --}}
    <div class="shrink-0 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-2 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ $veiculos->count() }} veículos</span>
            <span id="sort-label" class="text-xs text-zinc-400 dark:text-zinc-600">clique no cabeçalho para ordenar</span>
        </div>
        <span class="text-xs text-zinc-400 dark:text-zinc-600">Atualizado: {{ $agora->format('H:i:s') }}</span>
    </div>

    {{-- Tabela com rolagem vertical interna --}}
    <div class="min-h-0 flex-1 overflow-y-auto bg-white dark:bg-zinc-950">
        <table class="w-full border-collapse">
            <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-zinc-900">
                <tr>
                    <th data-col="prefixo"  class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Prefixo</th>
                    <th data-col="placa"    class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Placa</th>
                    <th data-col="status"   class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Status</th>
                    <th data-col="doc"      class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Documento</th>
                    <th data-col="cerca"    class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Cerca</th>
                    <th data-col="tracker"  class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Rastreador</th>
                    <th data-col="atend"    class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Atendimento</th>
                    <th data-col="statmin"  class="sortable-th whitespace-nowrap border-b border-slate-200 px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Tempo Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($veiculos as $v)
                @php
                    $hex = $statusHex[$v['status']] ?? '#a1a1aa';
                    [$trackerLabel, $trackerCls] = match($v['tracker_estado']) {
                        0  => ['Parado',        'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'],
                        1  => ['Em Movimento',  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
                        default => ['Sem Sinal', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'],
                    };
                    $isParado = $v['tracker_estado'] === 0;
                @endphp
                <tr class="border-b border-slate-100 hover:bg-slate-50 dark:border-zinc-800/60 dark:hover:bg-zinc-900/40"
                    style="border-left: 4px solid {{ $hex }}"
                    data-s-prefixo="{{ $v['prefixo'] }}"
                    data-s-placa="{{ $v['placa'] }}"
                    data-s-status="{{ $v['status'] }}"
                    data-s-doc="{{ $v['documento'] }}"
                    data-s-cerca="{{ $v['cerca_minutos'] }}"
                    data-s-tracker="{{ $v['tracker_minutos'] }}"
                    data-s-atend="{{ $v['atendimento_minutos'] }}"
                    data-s-statmin="{{ $v['status_minutos'] }}">

                    {{-- Prefixo --}}
                    <td class="px-5 py-4 font-bold text-base text-zinc-900 dark:text-zinc-100 whitespace-nowrap">
                        {{ $v['prefixo'] }}
                    </td>

                    {{-- Placa --}}
                    <td class="px-5 py-4 font-mono text-sm text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                        {{ $v['placa'] }}
                    </td>

                    {{-- Status --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($v['status'])
                            <span class="inline-block max-w-[180px] truncate rounded px-2 py-0.5 text-xs font-semibold text-white"
                                  style="background-color: {{ $hex }}; title='{{ $v['status'] }}'">
                                {{ $v['status'] }}
                            </span>
                        @else
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                        @endif
                    </td>

                    {{-- Documento --}}
                    <td class="px-5 py-4 font-mono text-sm text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                        {{ $v['documento'] ?: '—' }}
                    </td>

                    {{-- Cerca --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($v['cerca_nome'])
                            <div class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $v['cerca_nome'] }}</div>
                            <div class="text-xs font-semibold tabular-nums text-zinc-400 dark:text-zinc-500">{{ $fmtMin($v['cerca_minutos']) }}</div>
                        @else
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                        @endif
                    </td>

                    {{-- Rastreador --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $trackerCls }}">
                            {{ $trackerLabel }}
                        </span>
                        @if($v['tracker_minutos'] > 0)
                            <div class="mt-1 text-sm font-extrabold tabular-nums {{ $isParado ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                {{ $fmtMin($v['tracker_minutos']) }}
                            </div>
                        @endif
                    </td>

                    {{-- Atendimento --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($v['atendimento_minutos'] > 0)
                            <span class="text-base font-bold tabular-nums text-zinc-800 dark:text-zinc-200">
                                {{ $fmtMin($v['atendimento_minutos']) }}
                            </span>
                        @else
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                        @endif
                    </td>

                    {{-- Tempo Status --}}
                    <td class="px-5 py-4 whitespace-nowrap">
                        @if($v['status_minutos'] > 0)
                            <span class="text-base font-bold tabular-nums text-zinc-800 dark:text-zinc-200">
                                {{ $fmtMin($v['status_minutos']) }}
                            </span>
                        @else
                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach

                @if($veiculos->isEmpty())
                <tr>
                    <td colspan="8" class="px-5 py-16 text-center text-sm text-zinc-400 dark:text-zinc-600">
                        Nenhum veículo motorizado ativo encontrado.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var ths   = document.querySelectorAll('th.sortable-th');
    var tbody = document.querySelector('table tbody');
    var label = document.getElementById('sort-label');
    var state = { col: null, dir: 1 };

    var colLabels = {
        prefixo: 'Prefixo', placa: 'Placa', status: 'Status', doc: 'Documento',
        cerca: 'Cerca', tracker: 'Rastreador', atend: 'Atendimento', statmin: 'Tempo Status'
    };

    ths.forEach(function (th) {
        th.style.cursor = 'pointer';
        th.style.userSelect = 'none';

        var ind = document.createElement('span');
        ind.className = 'sort-ind ml-1 opacity-40';
        ind.textContent = '⇅';
        th.appendChild(ind);

        th.addEventListener('click', function () {
            var col = th.getAttribute('data-col');
            if (state.col === col) {
                state.dir *= -1;
            } else {
                state.col = col;
                state.dir = -1; // primeira vez: decrescente (maior primeiro)
            }
            sortRows(col, state.dir);
            updateHeaders();
        });
    });

    function sortRows(col, dir) {
        var rows = Array.from(tbody.querySelectorAll('tr[data-s-' + col + ']'));
        rows.sort(function (a, b) {
            var av = a.getAttribute('data-s-' + col) || '';
            var bv = b.getAttribute('data-s-' + col) || '';
            var an = parseFloat(av);
            var bn = parseFloat(bv);
            if (!isNaN(an) && !isNaN(bn)) { return (an - bn) * dir; }
            return av.localeCompare(bv, 'pt-BR', { sensitivity: 'base' }) * dir;
        });
        var frag = document.createDocumentFragment();
        rows.forEach(function (r) { frag.appendChild(r); });
        tbody.appendChild(frag);
    }

    function updateHeaders() {
        ths.forEach(function (th) {
            var col = th.getAttribute('data-col');
            var ind = th.querySelector('.sort-ind');
            if (col === state.col) {
                ind.textContent = state.dir === 1 ? ' ▲' : ' ▼';
                ind.classList.remove('opacity-40');
                ind.classList.add('opacity-100');
            } else {
                ind.textContent = ' ⇅';
                ind.classList.remove('opacity-100');
                ind.classList.add('opacity-40');
            }
        });
        if (label) {
            label.textContent = 'ordenado por ' + colLabels[state.col]
                + (state.dir === 1 ? ' (crescente)' : ' (decrescente)');
        }
    }
})();
</script>

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
