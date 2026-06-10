<x-layouts.app title="Painel de Frota">

    {{-- ─── Page header ─────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Painel de Frota</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Visão geral dos equipamentos motorizados.</p>
        </div>
        <a href="{{ route('control-tower.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                  border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                  dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M3.75 9h16.5M3.75 15h16.5"/>
            </svg>
            Ver Tabela
        </a>
    </div>

    {{-- ─── Toolbar ─────────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">

        {{-- Busca local --}}
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606z"/>
            </svg>
            <input id="painel-search" type="text" placeholder="Buscar placa, prefixo…"
                   oninput="filtrarPainel(this.value)"
                   class="rounded-lg border py-2 pl-8 pr-3 text-sm outline-none transition-all w-52
                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-400
                          focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                          dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:placeholder-zinc-600
                          dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
        </div>

        {{-- Filtro divisão --}}
        <form method="GET" action="{{ route('control-tower.painel') }}">
            <select name="divisao_id" onchange="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                           border-slate-200 bg-white text-zinc-700
                           focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                           dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                           dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                <option value="">Todas as divisões</option>
                @foreach($divisoes as $divisao)
                    <option value="{{ $divisao->id }}" @selected(request('divisao_id') == $divisao->id)>{{ $divisao->nome }}</option>
                @endforeach
            </select>
        </form>

        {{-- Legenda --}}
        <div class="ml-auto flex items-center gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-emerald-500"></span> Ligado</span>
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-rose-500"></span> Desligado</span>
            <span class="flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-zinc-400"></span> Desconhecido</span>
        </div>
    </div>

    {{-- ─── Contador --}}
    <p id="painel-counter" class="mt-3 text-xs text-zinc-400 dark:text-zinc-600"></p>

    {{-- ─── Grade de cards ──────────────────────────────────────────────────── --}}
    <div id="painel-grid"
         class="mt-3 grid gap-3"
         style="grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));">

        @foreach($cards as $c)
            <div class="painel-card flex flex-col rounded-xl border bg-white shadow-sm transition-shadow hover:shadow-md
                        dark:border-zinc-800 dark:bg-zinc-900"
                 data-search="{{ $c['searchKey'] }}">

                {{-- Tarja superior colorida --}}
                <div class="h-1.5 w-full rounded-t-xl" style="background: {{ $c['iconColor'] }};"></div>

                <div class="flex flex-col items-center gap-2 p-3">

                    {{-- Ícone do caminhão --}}
                    <svg style="color: {{ $c['iconColor'] }};" class="mt-1 h-12 w-12" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M1 3a1 1 0 0 1 1-1h15a1 1 0 0 1 1 1v3h2.293a1 1 0 0 1 .707.293l2 2A1 1 0 0 1 23 9v4a1 1 0 0 1-1 1h-1.126A3.001 3.001 0 0 1 15 14a3.001 3.001 0 0 1-5.874 0H7a1 1 0 0 1-1-1V3H2a1 1 0 0 1-1 0Zm6 10a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm10 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2ZM3 4v8h3.126A3.001 3.001 0 0 1 12 14h1.126A3.001 3.001 0 0 1 18 12V4H3Zm15 5v2.279a3 3 0 0 1 1.207.721H21V9.414L19.586 8H18Z"/>
                    </svg>

                    {{-- Prefixo + Placa --}}
                    <div class="text-center leading-tight">
                        @if($c['prefixo'])
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $c['prefixo'] }}</p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">{{ $c['placa'] }}</p>
                        @else
                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $c['placa'] }}</p>
                        @endif
                    </div>

                    {{-- Status operacional --}}
                    @if($c['statusOp'])
                        <span class="w-full truncate rounded px-2 py-0.5 text-center text-[10px] font-semibold"
                              style="background: {{ $c['statusCor'] }}1A; color: {{ $c['statusCor'] }}; box-shadow: inset 0 0 0 1px {{ $c['statusCor'] }}33;"
                              title="{{ $c['statusOp'] }}">
                            {{ $c['statusOp'] }}
                        </span>
                    @endif

                    {{-- Cerca --}}
                    <div class="w-full text-center">
                        @if($c['cercaNome'])
                            <p class="truncate text-[10px] font-medium text-violet-600 dark:text-violet-400" title="{{ $c['cercaNome'] }}">
                                📍 {{ $c['cercaNome'] }}
                            </p>
                        @else
                            <p class="text-[10px] text-zinc-300 dark:text-zinc-700">Sem cerca</p>
                        @endif
                    </div>

                    {{-- Barra de tempo de status --}}
                    <div class="w-full" style="background: {{ $c['barColor'] }}; padding: 3px 6px; text-align: center; color: #fff; font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums;">
                        {{ $c['barTime'] }}
                    </div>

                    {{-- Barra de tempo em cerca --}}
                    @if($c['cercaDuration'] && $c['cercaBarColor'])
                        <div class="w-full" style="background: {{ $c['cercaBarColor'] }}; padding: 3px 6px; text-align: center; color: #fff; font-size: 11px; font-weight: 600; font-variant-numeric: tabular-nums;">
                            ⏱ {{ $c['cercaDuration'] }}
                        </div>
                    @endif

                </div>
            </div>
        @endforeach
    </div>

    <script>
    (function () {
        var cards = Array.prototype.slice.call(document.querySelectorAll('.painel-card'));

        function atualizarContador() {
            var visiveis = cards.filter(function (c) { return c.style.display !== 'none'; }).length;
            document.getElementById('painel-counter').textContent = visiveis + ' de ' + cards.length + ' veículos';
        }

        window.filtrarPainel = function (q) {
            var termo = q.toLowerCase().trim();
            cards.forEach(function (c) {
                var match = !termo || (c.getAttribute('data-search') || '').includes(termo);
                c.style.display = match ? '' : 'none';
            });
            atualizarContador();
        };

        atualizarContador();
    })();
    </script>

</x-layouts.app>
