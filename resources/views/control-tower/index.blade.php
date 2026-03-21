<x-layouts.app title="Torre de Controle">

    @php
        $badgeCls = [
            'amber'   => 'bg-amber-500/10 text-amber-600 ring-amber-400/30 dark:text-amber-400 dark:ring-amber-500/30',
            'orange'  => 'bg-orange-500/10 text-orange-600 ring-orange-400/30 dark:text-orange-400 dark:ring-orange-500/30',
            'yellow'  => 'bg-yellow-500/10 text-yellow-600 ring-yellow-400/30 dark:text-yellow-400 dark:ring-yellow-500/30',
            'lime'    => 'bg-lime-500/10 text-lime-600 ring-lime-400/30 dark:text-lime-400 dark:ring-lime-500/30',
            'emerald' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-400/30 dark:text-emerald-400 dark:ring-emerald-500/30',
            'teal'    => 'bg-teal-500/10 text-teal-600 ring-teal-400/30 dark:text-teal-400 dark:ring-teal-500/30',
            'cyan'    => 'bg-cyan-500/10 text-cyan-600 ring-cyan-400/30 dark:text-cyan-400 dark:ring-cyan-500/30',
            'blue'    => 'bg-blue-500/10 text-blue-600 ring-blue-400/30 dark:text-blue-400 dark:ring-blue-500/30',
            'indigo'  => 'bg-indigo-500/10 text-indigo-600 ring-indigo-400/30 dark:text-indigo-400 dark:ring-indigo-500/30',
            'violet'  => 'bg-violet-500/10 text-violet-600 ring-violet-400/30 dark:text-violet-400 dark:ring-violet-500/30',
            'purple'  => 'bg-purple-500/10 text-purple-600 ring-purple-400/30 dark:text-purple-400 dark:ring-purple-500/30',
            'rose'    => 'bg-rose-500/10 text-rose-600 ring-rose-400/30 dark:text-rose-400 dark:ring-rose-500/30',
            'zinc'    => 'bg-zinc-500/10 text-zinc-600 ring-zinc-400/30 dark:text-zinc-400 dark:ring-zinc-500/30',
        ];
        $dotCls = [
            'amber'   => 'bg-amber-400',  'orange' => 'bg-orange-400',
            'yellow'  => 'bg-yellow-400', 'lime'   => 'bg-lime-400',
            'emerald' => 'bg-emerald-400', 'teal'  => 'bg-teal-400',
            'cyan'    => 'bg-cyan-400',   'blue'   => 'bg-blue-400',
            'indigo'  => 'bg-indigo-400', 'violet' => 'bg-violet-400',
            'purple'  => 'bg-purple-400', 'rose'   => 'bg-rose-400',
            'zinc'    => 'bg-zinc-400',
        ];
    @endphp

    {{-- ─── Sticky toolbar ─────────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-20 -mx-4 -mt-8 border-b px-4 py-3 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8
                border-slate-200 bg-white
                dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">

            {{-- Page title (inline) --}}
            <div class="w-full sm:w-auto sm:mr-auto">
                <h2 class="text-base font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Torre de Controle</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-500">Monitoramento em tempo real da frota</p>
            </div>

            {{-- Filtro por Divisão --}}
            @if(count($divisions) > 1)
            <div class="relative" id="division-filter-wrap">
                <button id="division-filter-btn"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium
                               transition-all duration-150
                               border-slate-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50
                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    Divisão
                    <span id="division-filter-count"
                          class="hidden rounded-full bg-zinc-900 px-1.5 py-0.5 text-[10px] font-bold text-white
                                 dark:bg-white dark:text-zinc-900"></span>
                    <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
                <div id="division-filter-menu"
                     class="absolute left-0 top-full z-40 mt-1.5 hidden
                            w-[calc(100vw-2rem)] max-w-xs sm:w-auto sm:min-w-48
                            rounded-xl border shadow-lg
                            border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="max-h-64 overflow-y-auto p-1.5">
                        @foreach($divisions as $div)
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2
                                      text-sm text-zinc-700 hover:bg-zinc-50
                                      dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                            <input type="checkbox"
                                   class="division-checkbox h-4 w-4 rounded border-slate-300 accent-zinc-900
                                          dark:border-zinc-600 dark:accent-white"
                                   value="{{ $div }}">
                            {{ $div === '' ? 'Sem divisão' : $div }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Filtro por Status Operacional --}}
            @if(count($statuses) > 1)
            <div class="relative" id="status-filter-wrap">
                <button id="status-filter-btn"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium
                               transition-all duration-150
                               border-slate-300 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50
                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    Status
                    <span id="status-filter-count"
                          class="hidden rounded-full bg-zinc-900 px-1.5 py-0.5 text-[10px] font-bold text-white
                                 dark:bg-white dark:text-zinc-900"></span>
                    <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
                <div id="status-filter-menu"
                     class="absolute left-0 top-full z-40 mt-1.5 hidden
                            w-[calc(100vw-2rem)] max-w-xs sm:w-auto sm:min-w-52
                            rounded-xl border shadow-lg
                            border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="max-h-64 overflow-y-auto p-1.5">
                        @foreach($statuses as $s)
                        @php $dotColor = $dotCls[$statusColorMap[$s] ?? 'zinc'] ?? $dotCls['zinc']; @endphp
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2
                                      text-sm text-zinc-700 hover:bg-zinc-50
                                      dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                            <input type="checkbox"
                                   class="status-checkbox h-4 w-4 rounded border-slate-300 accent-zinc-900
                                          dark:border-zinc-600 dark:accent-white"
                                   value="{{ $s }}">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $dotColor }}"></span>
                            {{ $s === '' ? 'Indefinido' : $s }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Busca --}}
            <div class="flex w-full sm:w-auto sm:min-w-48 overflow-hidden rounded-lg border shadow-xs
                        border-slate-300 bg-white
                        focus-within:border-zinc-900 focus-within:ring-2 focus-within:ring-zinc-900/10
                        dark:border-zinc-800 dark:bg-zinc-950
                        dark:focus-within:border-blue-500 dark:focus-within:ring-1 dark:focus-within:ring-blue-500/30
                        transition-all duration-200">
                <span class="flex items-center pl-3 text-zinc-400 dark:text-zinc-600">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </span>
                <input type="text" id="search-vehicles"
                       placeholder="Placa ou CM…" autocomplete="off"
                       class="w-full bg-transparent px-2.5 py-2 text-sm text-zinc-900 outline-none
                              placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600"/>
            </div>

            {{-- Contagem --}}
            <p id="vehicle-count" class="hidden text-xs text-zinc-500 dark:text-zinc-500">
                <span id="count-visible">{{ count($vehicles) }}</span> / {{ count($vehicles) }}
            </p>

            {{-- Atualizar --}}
            <button id="btn-refresh" onclick="refreshVehicles()"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2
                           text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                           bg-zinc-900 text-white hover:bg-zinc-700
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200
                           disabled:cursor-not-allowed disabled:opacity-60">
                <svg id="icon-refresh" class="h-4 w-4 transition-transform duration-500"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                <span id="btn-refresh-label" class="hidden sm:inline">Atualizar</span>
            </button>
        </div>
    </div>

    {{-- ─── Grid de Cards ───────────────────────────────────────────────────── --}}
    <div id="vehicles-grid"
         class="grid grid-cols-1 gap-3 pt-4 transition-opacity duration-300
                sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        {{--
            Safelist de cores para badges de status — não remova estas classes:
            bg-amber-500/10   text-amber-600   ring-amber-400/30   dark:text-amber-400   dark:ring-amber-500/30   bg-amber-400
            bg-orange-500/10  text-orange-600  ring-orange-400/30  dark:text-orange-400  dark:ring-orange-500/30  bg-orange-400
            bg-yellow-500/10  text-yellow-600  ring-yellow-400/30  dark:text-yellow-400  dark:ring-yellow-500/30  bg-yellow-400
            bg-lime-500/10    text-lime-600    ring-lime-400/30    dark:text-lime-400    dark:ring-lime-500/30    bg-lime-400
            bg-emerald-500/10 text-emerald-600 ring-emerald-400/30 dark:text-emerald-400 dark:ring-emerald-500/30 bg-emerald-400
            bg-teal-500/10    text-teal-600    ring-teal-400/30    dark:text-teal-400    dark:ring-teal-500/30    bg-teal-400
            bg-cyan-500/10    text-cyan-600    ring-cyan-400/30    dark:text-cyan-400    dark:ring-cyan-500/30    bg-cyan-400
            bg-blue-500/10    text-blue-600    ring-blue-400/30    dark:text-blue-400    dark:ring-blue-500/30    bg-blue-400
            bg-indigo-500/10  text-indigo-600  ring-indigo-400/30  dark:text-indigo-400  dark:ring-indigo-500/30  bg-indigo-400
            bg-violet-500/10  text-violet-600  ring-violet-400/30  dark:text-violet-400  dark:ring-violet-500/30  bg-violet-400
            bg-purple-500/10  text-purple-600  ring-purple-400/30  dark:text-purple-400  dark:ring-purple-500/30  bg-purple-400
            bg-rose-500/10    text-rose-600    ring-rose-400/30    dark:text-rose-400    dark:ring-rose-500/30    bg-rose-400
            bg-zinc-500/10    text-zinc-600    ring-zinc-400/30    dark:text-zinc-400    dark:ring-zinc-500/30    bg-zinc-400
        --}}

        @forelse($vehicles as $v)
            @php
                $placa    = $v['Placa']   ?? '—';
                $cm       = $v['CM']      ?? '—';
                $status   = $v['Status']  ?? '—';
                $cerca1   = $v['Cerca 1'] ?? '';
                $locFull  = ($cerca1 !== '' && $cerca1 !== null) ? $cerca1 : ($v['Local'] ?? '—');
                $condutor = $v['Condutor'] ?? '—';

                $isWaiting  = str_starts_with((string) $status, 'Ag-');
                $colorToken = $statusColorMap[(string) $status] ?? 'zinc';
                $badgeClass = $badgeCls[$colorToken] ?? $badgeCls['zinc'];
                $dotClass   = $dotCls[$colorToken]   ?? $dotCls['zinc'];
            @endphp

            <div class="vehicle-card group cursor-pointer rounded-xl border p-4
                        transition-all duration-200
                        border-slate-200 bg-white hover:border-slate-300 hover:shadow-md
                        dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700 dark:hover:bg-zinc-900"
                 data-plate="{{ strtolower($placa) }}"
                 data-cm="{{ strtolower($cm) }}"
                 data-divisao="{{ $v['Divisão'] ?? '' }}"
                 data-status="{{ $v['Status'] ?? '' }}"
                 data-vehicle="{{ json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
                 onclick="openVehicleDetail(this)">

                {{-- CM (destaque principal) + Status --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums tracking-wide text-zinc-900 dark:text-zinc-100">
                            {{ $cm }}
                        </p>
                        <p class="mt-0.5 text-xs font-semibold tracking-widest
                                  text-zinc-500 dark:text-zinc-500">
                            {{ strtoupper($placa) }}
                        </p>
                    </div>

                    {{-- Badge de status (cor fixa por token) --}}
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 {{ $badgeClass }}">
                        @if($isWaiting)
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        @else
                            <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}"></span>
                        @endif
                        {{ $status }}
                    </span>
                </div>

                {{-- Localização --}}
                <div class="mt-3 flex items-start gap-1.5">
                    <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-600"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                    </svg>
                    <p class="line-clamp-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                        {{ $locFull ?: '—' }}
                    </p>
                </div>

                {{-- Condutor --}}
                @if($condutor !== '—' && $condutor !== '')
                    <div class="mt-2 flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-600"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                        </svg>
                        <p class="truncate text-xs text-zinc-600 dark:text-zinc-400">{{ $condutor }}</p>
                    </div>
                @endif

                {{-- Hint hover --}}
                <p class="mt-3 text-[10px] text-zinc-400 opacity-0 transition-opacity duration-200
                           group-hover:opacity-100 dark:text-zinc-600">
                    Ver detalhes →
                </p>
            </div>

        @empty
            <div class="col-span-full flex flex-col items-center justify-center rounded-xl border
                        px-6 py-20 text-center
                        border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl
                            bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-600" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    Sem dados da frota
                </h3>
                <p class="mt-1.5 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">
                    Nenhum veículo retornado pela API. Verifique as configurações ou clique em Atualizar.
                </p>
            </div>
        @endforelse
    </div>

    {{-- ─── Modal de Detalhes ───────────────────────────────────────────────── --}}
    <div id="vehicle-modal"
         class="fixed inset-0 z-[100] hidden"
         role="dialog" aria-modal="true" aria-labelledby="vehicle-modal-title">

        <div id="vehicle-modal-overlay"
             class="absolute inset-0 bg-black/70 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

        <div class="relative flex min-h-full items-center justify-center p-4">

            <div id="vehicle-modal-panel"
                 class="w-full max-w-lg scale-95 rounded-xl border opacity-0 shadow-xl transition-all duration-200
                        border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 dark:shadow-2xl">

                {{-- Header do modal --}}
                <div class="flex items-start justify-between border-b px-6 py-4
                            border-slate-100 dark:border-zinc-800">
                    <div>
                        <h3 id="vehicle-modal-title"
                            class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">—</h3>
                        <p id="vehicle-modal-plate"
                           class="mt-0.5 text-xs font-semibold tracking-widest text-zinc-500 dark:text-zinc-500">—</p>
                    </div>
                    <button onclick="closeVehicleModal()"
                            class="rounded-lg p-1.5 transition-colors duration-150
                                   text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700
                                   dark:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body 2 colunas --}}
                <div class="max-h-[60vh] overflow-y-auto px-6 py-4">
                    <dl id="vehicle-detail-list"
                        class="grid grid-cols-2 gap-x-6 gap-y-3"></dl>
                </div>

                <div class="flex justify-end border-t px-6 py-4
                            border-slate-100 dark:border-zinc-800">
                    <button onclick="closeVehicleModal()"
                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium
                                   transition-all duration-150
                                   text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900
                                   dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Scripts ─────────────────────────────────────────────────────────── --}}
    <script>
    (function () {

        // ── Estado dos filtros ───────────────────────────────────────────────
        var searchInput     = document.getElementById('search-vehicles');
        var countVisible    = document.getElementById('count-visible');
        var allCards        = [];
        var activeDivisions = new Set(); // vazio = todos
        var activeStatuses  = new Set(); // vazio = todos

        function rebindCards() {
            allCards = Array.from(document.querySelectorAll('.vehicle-card'));
        }
        rebindCards();

        // ── Filtro combinado (busca + divisão + status) ──────────────────────
        function applyFilters() {
            var q   = searchInput.value.trim().toLowerCase();
            var vis = 0;
            allCards.forEach(function (card) {
                var matchSearch = ! q || card.dataset.plate.includes(q) || card.dataset.cm.includes(q);
                var matchDiv    = activeDivisions.size === 0 || activeDivisions.has(card.dataset.divisao);
                var matchStatus = activeStatuses.size === 0  || activeStatuses.has(card.dataset.status);
                var match       = matchSearch && matchDiv && matchStatus;
                card.style.display = match ? '' : 'none';
                if (match) { vis++; }
            });
            countVisible.textContent = vis;
        }

        searchInput.addEventListener('input', applyFilters);

        // ── Dropdowns de filtro ──────────────────────────────────────────────
        var divFilterBtn   = document.getElementById('division-filter-btn');
        var divFilterMenu  = document.getElementById('division-filter-menu');
        var divCountBadge  = document.getElementById('division-filter-count');
        var stFilterBtn    = document.getElementById('status-filter-btn');
        var stFilterMenu   = document.getElementById('status-filter-menu');
        var stCountBadge   = document.getElementById('status-filter-count');

        function closeAllDropdowns() {
            if (divFilterMenu) { divFilterMenu.classList.add('hidden'); }
            if (stFilterMenu)  { stFilterMenu.classList.add('hidden'); }
        }

        document.addEventListener('click', closeAllDropdowns);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeAllDropdowns(); }
        });

        function updateDivisionCount() {
            if (! divCountBadge) { return; }
            var n = activeDivisions.size;
            divCountBadge.textContent = n;
            divCountBadge.classList.toggle('hidden', n === 0);
        }

        function updateStatusCount() {
            if (! stCountBadge) { return; }
            var n = activeStatuses.size;
            stCountBadge.textContent = n;
            stCountBadge.classList.toggle('hidden', n === 0);
        }

        if (divFilterBtn && divFilterMenu) {
            divFilterBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = ! divFilterMenu.classList.contains('hidden');
                closeAllDropdowns();
                if (! isOpen) { divFilterMenu.classList.remove('hidden'); }
            });
            divFilterMenu.addEventListener('click', function (e) { e.stopPropagation(); });
            divFilterMenu.addEventListener('change', function (e) {
                var cb = e.target;
                if (! cb.classList.contains('division-checkbox')) { return; }
                cb.checked ? activeDivisions.add(cb.value) : activeDivisions.delete(cb.value);
                updateDivisionCount();
                applyFilters();
            });
        }

        if (stFilterBtn && stFilterMenu) {
            stFilterBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isOpen = ! stFilterMenu.classList.contains('hidden');
                closeAllDropdowns();
                if (! isOpen) { stFilterMenu.classList.remove('hidden'); }
            });
            stFilterMenu.addEventListener('click', function (e) { e.stopPropagation(); });
            stFilterMenu.addEventListener('change', function (e) {
                var cb = e.target;
                if (! cb.classList.contains('status-checkbox')) { return; }
                cb.checked ? activeStatuses.add(cb.value) : activeStatuses.delete(cb.value);
                updateStatusCount();
                applyFilters();
            });
        }

        // ── Helpers de status ────────────────────────────────────────────────
        // Mapa status → token de cor (gerado pelo controller, idêntico ao PHP)
        var STATUS_COLOR_MAP = @json($statusColorMap);

        function statusColor(s) {
            return STATUS_COLOR_MAP[s] || 'zinc';
        }

        var COLOR_CLASSES = {
            amber:   { bg: 'bg-amber-500/10',   text: 'text-amber-400',   ring: 'ring-amber-500/30',   dot: 'bg-amber-400'   },
            orange:  { bg: 'bg-orange-500/10',  text: 'text-orange-400',  ring: 'ring-orange-500/30',  dot: 'bg-orange-400'  },
            yellow:  { bg: 'bg-yellow-500/10',  text: 'text-yellow-400',  ring: 'ring-yellow-500/30',  dot: 'bg-yellow-400'  },
            lime:    { bg: 'bg-lime-500/10',    text: 'text-lime-400',    ring: 'ring-lime-500/30',    dot: 'bg-lime-400'    },
            emerald: { bg: 'bg-emerald-500/10', text: 'text-emerald-400', ring: 'ring-emerald-500/30', dot: 'bg-emerald-400' },
            teal:    { bg: 'bg-teal-500/10',    text: 'text-teal-400',    ring: 'ring-teal-500/30',    dot: 'bg-teal-400'    },
            cyan:    { bg: 'bg-cyan-500/10',    text: 'text-cyan-400',    ring: 'ring-cyan-500/30',    dot: 'bg-cyan-400'    },
            blue:    { bg: 'bg-blue-500/10',    text: 'text-blue-400',    ring: 'ring-blue-500/30',    dot: 'bg-blue-400'    },
            indigo:  { bg: 'bg-indigo-500/10',  text: 'text-indigo-400',  ring: 'ring-indigo-500/30',  dot: 'bg-indigo-400'  },
            violet:  { bg: 'bg-violet-500/10',  text: 'text-violet-400',  ring: 'ring-violet-500/30',  dot: 'bg-violet-400'  },
            purple:  { bg: 'bg-purple-500/10',  text: 'text-purple-400',  ring: 'ring-purple-500/30',  dot: 'bg-purple-400'  },
            rose:    { bg: 'bg-rose-500/10',    text: 'text-rose-400',    ring: 'ring-rose-500/30',    dot: 'bg-rose-400'    },
            zinc:    { bg: 'bg-zinc-500/10',    text: 'text-zinc-400',    ring: 'ring-zinc-500/30',    dot: 'bg-zinc-400'    },
        };

        function badgeHtml(status) {
            var c       = statusColor(status);
            var cls     = COLOR_CLASSES[c] || COLOR_CLASSES.zinc;
            var waiting = /^Ag-/i.test(status);
            var icon    = waiting
                ? '<svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                : '<span class="h-1.5 w-1.5 rounded-full ' + cls.dot + '"></span>';
            return '<span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 '
                + cls.bg + ' ' + cls.text + ' ' + cls.ring + '">'
                + icon + escHtml(status) + '</span>';
        }

        // ── Render card HTML (usado pelo refresh) ────────────────────────────
        function getF(v, key) {
            var val = v[key];
            return (val !== undefined && val !== null && val !== '') ? val : '—';
        }

        function cardHtml(v) {
            var cm      = getF(v, 'CM');
            var placa   = getF(v, 'Placa');
            var status  = getF(v, 'Status');
            var cerca1  = getF(v, 'Cerca 1');
            var local   = getF(v, 'Local');
            var loc     = (cerca1 !== '—') ? cerca1 : local;
            var cond    = getF(v, 'Condutor');
            var encoded = encodeForAttr(v);

            var condHtml = (cond !== '—')
                ? '<div class="mt-2 flex items-center gap-1.5">'
                    + '<svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>'
                    + '<p class="truncate text-xs text-zinc-600 dark:text-zinc-400">' + escHtml(cond) + '</p></div>'
                : '';

            return '<div class="vehicle-card group cursor-pointer rounded-xl border p-4 transition-all duration-200'
                + ' border-slate-200 bg-white hover:border-slate-300 hover:shadow-md'
                + ' dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700 dark:hover:bg-zinc-900"'
                + ' data-plate="' + placa.toLowerCase() + '" data-cm="' + cm.toLowerCase() + '"'
                + ' data-divisao="' + escHtml(String(v['Divisão'] || '')) + '"'
                + ' data-status="' + escHtml(String(v['Status'] || '')) + '"'
                + ' data-vehicle="' + encoded + '" onclick="openVehicleDetail(this)">'

                + '<div class="flex items-start justify-between gap-2">'
                    + '<div class="min-w-0">'
                        + '<p class="text-2xl font-bold tabular-nums tracking-wide text-zinc-900 dark:text-zinc-100">' + escHtml(cm) + '</p>'
                        + '<p class="mt-0.5 text-xs font-semibold tracking-widest text-zinc-500 dark:text-zinc-500">' + escHtml(placa.toUpperCase()) + '</p>'
                    + '</div>'
                    + badgeHtml(status)
                + '</div>'

                + '<div class="mt-3 flex items-start gap-1.5">'
                    + '<svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>'
                    + '<p class="line-clamp-2 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">' + escHtml(loc) + '</p>'
                + '</div>'

                + condHtml

                + '<p class="mt-3 text-[10px] text-zinc-400 opacity-0 transition-opacity duration-200 group-hover:opacity-100 dark:text-zinc-600">Ver detalhes →</p>'
                + '</div>';
        }

        function encodeForAttr(obj) {
            return JSON.stringify(obj).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function emptyState() {
            return '<div class="col-span-full flex flex-col items-center justify-center rounded-xl border px-6 py-20 text-center border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">'
                + '<div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">'
                + '<svg class="h-8 w-8 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/></svg>'
                + '</div><h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Sem dados da frota</h3>'
                + '<p class="mt-1.5 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">Nenhum veículo retornado. Clique em Atualizar.</p></div>';
        }

        // ── Atualizar via fetch ──────────────────────────────────────────────
        var refreshEndpoint = '{{ route('control-tower.dados') }}';

        window.refreshVehicles = function () {
            var btn   = document.getElementById('btn-refresh');
            var icon  = document.getElementById('icon-refresh');
            var label = document.getElementById('btn-refresh-label');
            var grid  = document.getElementById('vehicles-grid');

            btn.disabled = true;
            icon.classList.add('animate-spin');
            label.textContent = 'Consultando API…';
            grid.style.opacity = '0.35';
            grid.style.pointerEvents = 'none';

            fetch(refreshEndpoint, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        var vehicles = data.vehicles;
                        var grid     = document.getElementById('vehicles-grid');
                        grid.innerHTML = vehicles.length
                            ? vehicles.map(cardHtml).join('')
                            : emptyState();
                        rebindCards();
                        var total = data.total;
                        document.getElementById('count-visible').textContent = total;
                        document.getElementById('vehicle-count').innerHTML =
                            '<span id="count-visible">' + total + '</span> / ' + total;

                        if (data.statusColorMap) { STATUS_COLOR_MAP = data.statusColorMap; }
                        if (data.divisions)      { renderDivisionDropdown(data.divisions); }
                        if (data.statuses)       { renderStatusDropdown(data.statuses); }
                    }
                })
                .catch(function () { /* mantém grid atual */ })
                .finally(function () {
                    btn.disabled = false;
                    icon.classList.remove('animate-spin');
                    label.textContent = 'Atualizar';
                    grid.style.opacity = '';
                    grid.style.pointerEvents = '';
                    applyFilters();
                });
        };

        // ── Modal de detalhes ────────────────────────────────────────────────
        var vModal   = document.getElementById('vehicle-modal');
        var vOverlay = document.getElementById('vehicle-modal-overlay');
        var vPanel   = document.getElementById('vehicle-modal-panel');

        window.openVehicleDetail = function (el) {
            var raw = el.dataset.vehicle;
            var v;
            try {
                v = JSON.parse(raw.replace(/&quot;/g, '"').replace(/&#39;/g, "'"));
            } catch (e) { return; }

            var cm    = v['CM']    || '—';
            var placa = v['Placa'] || '—';

            document.getElementById('vehicle-modal-title').textContent = cm;
            document.getElementById('vehicle-modal-plate').textContent = placa.toUpperCase();

            var list = document.getElementById('vehicle-detail-list');
            list.innerHTML = '';

            var skip = { 'CM': true, 'Placa': true };

            Object.entries(v).forEach(function (entry) {
                var key = entry[0];
                var val = entry[1];
                if (skip[key]) { return; }
                if (val === null || val === '' || val === undefined) { return; }

                var row = document.createElement('div');
                row.className = 'flex flex-col gap-0.5 py-2 border-b last:border-0 border-slate-100 dark:border-zinc-800/60';
                row.innerHTML =
                    '<dt class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">'
                    + escHtml(key) + '</dt>'
                    + '<dd class="text-sm font-medium text-zinc-900 dark:text-zinc-100">'
                    + escHtml(String(val)) + '</dd>';
                list.appendChild(row);
            });

            vModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(function () {
                vOverlay.classList.add('opacity-100');
                vPanel.classList.remove('opacity-0', 'scale-95');
                vPanel.classList.add('opacity-100', 'scale-100');
            });
        };

        window.closeVehicleModal = function () {
            vOverlay.classList.remove('opacity-100');
            vPanel.classList.add('opacity-0', 'scale-95');
            vPanel.classList.remove('opacity-100', 'scale-100');
            setTimeout(function () {
                vModal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 200);
        };

        vModal.addEventListener('click', function (e) {
            if (e.target === vModal || e.target === vOverlay) { window.closeVehicleModal(); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !vModal.classList.contains('hidden')) { window.closeVehicleModal(); }
        });

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ── Re-renderiza dropdowns após refresh ──────────────────────────────
        function renderDivisionDropdown(divisions) {
            var wrap = document.getElementById('division-filter-wrap');
            if (wrap) { wrap.classList.toggle('hidden', divisions.length < 2); }
            var container = divFilterMenu ? divFilterMenu.querySelector('.overflow-y-auto') : null;
            if (! container) { return; }
            container.innerHTML = divisions.map(function (div) {
                var label   = div === '' ? 'Sem divisão' : div;
                var checked = activeDivisions.has(div) ? ' checked' : '';
                return '<label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2'
                    + ' text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800/60">'
                    + '<input type="checkbox" class="division-checkbox h-4 w-4 rounded border-slate-300'
                    + ' accent-zinc-900 dark:border-zinc-600 dark:accent-white"'
                    + ' value="' + escHtml(div) + '"' + checked + '>'
                    + escHtml(label) + '</label>';
            }).join('');
            updateDivisionCount();
        }

        function renderStatusDropdown(statuses) {
            var wrap = document.getElementById('status-filter-wrap');
            if (wrap) { wrap.classList.toggle('hidden', statuses.length < 2); }
            var container = stFilterMenu ? stFilterMenu.querySelector('.overflow-y-auto') : null;
            if (! container) { return; }
            container.innerHTML = statuses.map(function (st) {
                var label   = st === '' ? 'Indefinido' : st;
                var checked = activeStatuses.has(st) ? ' checked' : '';
                var cls     = COLOR_CLASSES[statusColor(st)] || COLOR_CLASSES.zinc;
                return '<label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2'
                    + ' text-sm text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800/60">'
                    + '<input type="checkbox" class="status-checkbox h-4 w-4 rounded border-slate-300'
                    + ' accent-zinc-900 dark:border-zinc-600 dark:accent-white"'
                    + ' value="' + escHtml(st) + '"' + checked + '>'
                    + '<span class="h-2 w-2 shrink-0 rounded-full ' + cls.dot + '"></span>'
                    + escHtml(label) + '</label>';
            }).join('');
            updateStatusCount();
        }

    })();
    </script>

</x-layouts.app>
