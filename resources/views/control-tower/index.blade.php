<x-layouts.app title="Torre de Controle">

    {{-- Leaflet.js CSS --}}
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css"/>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>



    {{-- ─── Page header ──────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Torre de Controle</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Equipamentos motorizados ativos.</p>
        </div>
        {{-- Live counter --}}
        <span id="row-counter" class="rounded-full border px-3 py-1 text-xs font-medium
                                      border-zinc-200 bg-zinc-50 text-zinc-500
                                      dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400"></span>
    </div>

    {{-- ─── Toolbar ─────────────────────────────────────────────────────────── --}}
    @php
        $currentDivisao          = request('divisao_id');
        $currentModelo           = request('modelo_id');
        $currentStatusOp         = request('status_operacional');
        $currentImplementoModelo = request('implemento_modelo_id');
        $currentMotorista        = request('motorista_id');
    @endphp

    <div class="mt-4 flex flex-wrap items-center gap-2">

        {{-- Live search --}}
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.197 5.197a7.5 7.5 0 0 0 10.606 10.606z"/>
            </svg>
            <input id="live-search" type="text" placeholder="Buscar placa, prefixo, divisão…"
                   class="rounded-lg border py-2 pl-8 pr-3 text-sm outline-none transition-all w-56
                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-400
                          focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                          dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:placeholder-zinc-600
                          dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
        </div>

        {{-- Server filters --}}
        <form id="filter-form" method="GET" action="{{ route('control-tower.index') }}" class="flex items-center gap-2">
            <select name="divisao_id" onchange="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                           border-slate-200 bg-white text-zinc-700
                           focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                           dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                           dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                <option value="">Todas as divisões</option>
                @foreach($divisoes as $divisao)
                    <option value="{{ $divisao->id }}" @selected($currentDivisao == $divisao->id)>{{ $divisao->nome }}</option>
                @endforeach
            </select>

            <select name="status_operacional" onchange="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                           border-slate-200 bg-white text-zinc-700
                           focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                           dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                           dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                <option value="">Todos os status</option>
                @foreach($statusOperacionais as $statusOp)
                    <option value="{{ $statusOp->nome }}" @selected($currentStatusOp === $statusOp->nome)>
                        {{ $statusOp->nome }}
                    </option>
                @endforeach
            </select>

            <select name="modelo_id" onchange="this.form.submit()"
                    class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                           border-slate-200 bg-white text-zinc-700
                           focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                           dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                           dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                <option value="">Todos os modelos</option>
                @foreach($modelos as $modelo)
                    <option value="{{ $modelo->id }}" @selected($currentModelo == $modelo->id)>{{ $modelo->nome }}</option>
                @endforeach
            </select>

            @if($motoristas->isNotEmpty())
                <select name="motorista_id" onchange="this.form.submit()"
                        class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                               border-slate-200 bg-white text-zinc-700
                               focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                               dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                    <option value="">Todos os condutores</option>
                    @foreach($motoristas as $motorista)
                        <option value="{{ $motorista->id }}" @selected($currentMotorista == $motorista->id)>
                            {{ $motorista->nome }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if($modelosImplemento->isNotEmpty())
                <select name="implemento_modelo_id" onchange="this.form.submit()"
                        class="rounded-lg border px-3 py-2 text-sm font-medium outline-none transition-all
                               border-slate-200 bg-white text-zinc-700
                               focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300
                               dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                    <option value="">Todos os implementos</option>
                    @foreach($modelosImplemento as $mi)
                        <option value="{{ $mi->id }}" @selected($currentImplementoModelo == $mi->id)>{{ $mi->nome }}</option>
                    @endforeach
                </select>
            @endif

            @if($currentDivisao || $currentModelo || $currentStatusOp || $currentImplementoModelo || $currentMotorista)
                <a href="{{ route('control-tower.index') }}"
                   class="flex items-center gap-1 rounded-lg border px-2.5 py-2 text-xs font-medium transition-colors
                          border-zinc-200 bg-white text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700
                          dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-500 dark:hover:text-zinc-300"
                   title="Limpar filtros">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </a>
            @endif

            {{-- Exportar CSV --}}
            <a href="{{ route('control-tower.export', array_filter(['divisao_id' => $currentDivisao, 'modelo_id' => $currentModelo, 'status_operacional' => $currentStatusOp, 'implemento_modelo_id' => $currentImplementoModelo, 'motorista_id' => $currentMotorista])) }}"
               title="Exportar para CSV/Excel"
               class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                      border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
                <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Exportar
            </a>
        </form>

        {{-- Métricas --}}
        <a href="{{ route('metricas.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                  border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                  dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
            <svg class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
            </svg>
            Métricas
        </a>

        {{-- Painel --}}
        <a href="{{ route('control-tower.painel', array_filter(['divisao_id' => $currentDivisao])) }}"
           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                  border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                  dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
            <svg class="h-3.5 w-3.5 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
            </svg>
            Painel
        </a>

        {{-- Mapa Geral --}}
        <button type="button" onclick="openMapaGeral()"
                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                       border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                       dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
            <svg class="h-3.5 w-3.5 text-sky-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
            </svg>
            Mapa Geral
        </button>

        {{-- Column picker --}}
        <div class="relative ml-auto" id="col-picker-wrapper">
            <button type="button" id="col-picker-btn" onclick="toggleColPicker()"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors
                           border-slate-200 bg-white text-zinc-600 hover:border-slate-300 hover:bg-slate-50
                           dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M3.75 9h16.5M3.75 15h16.5"/>
                </svg>
                Colunas
            </button>

            {{-- Dropdown panel --}}
            <div id="col-picker-panel"
                 class="absolute right-0 top-full z-30 mt-1.5 hidden w-52 overflow-hidden rounded-xl border shadow-lg
                        border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">

                {{-- Search --}}
                <div class="border-b border-slate-100 px-3 py-2 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <input id="col-picker-search" type="text" placeholder="Filtrar…"
                               class="w-full bg-transparent text-xs outline-none text-zinc-700 placeholder-zinc-400 dark:text-zinc-300 dark:placeholder-zinc-600">
                    </div>
                </div>

                {{-- Column list --}}
                <div class="py-1.5">
                    <p class="px-3 pb-1 pt-0.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Colunas</p>
                    @foreach([
                        ['col' => 'placa',           'label' => 'Placa'],
                        ['col' => 'modelo',          'label' => 'Modelo / Implemento'],
                        ['col' => 'status-op',       'label' => 'Status'],
                        ['col' => 'tempo-status',    'label' => 'Tempo Status'],
                        ['col' => 'cerca',           'label' => 'Cerca'],
                        ['col' => 'tempo-cerca',     'label' => 'Tempo Cerca'],
                        ['col' => 'condutor',        'label' => 'Condutor'],
                        ['col' => 'ultimo-reporte',  'label' => 'Último Reporte'],
                        ['col' => 'documento',       'label' => 'Documento'],
                        ['col' => 'obs',             'label' => 'Observação'],
                        ['col' => 'divisao',         'label' => 'Divisão'],
                    ] as $tog)
                        <label data-col-label="{{ strtolower($tog['label']) }}"
                               class="col-picker-item flex cursor-pointer items-center gap-2.5 px-3 py-1.5 transition-colors
                                      hover:bg-slate-50 dark:hover:bg-zinc-800/50">
                            <input type="checkbox" data-col="{{ $tog['col'] }}"
                                   class="col-picker-check h-4 w-4 rounded border-slate-300 accent-zinc-900 dark:accent-zinc-100"
                                   onchange="toggleColumn('{{ $tog['col'] }}')">
                            <span class="text-xs text-zinc-700 dark:text-zinc-300">{{ $tog['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Flash / errors ─────────────────────────────────────────────────── --}}
    @if($errors->any())
        <div class="mt-3 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700
                    dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-400">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ─── Legenda HJ ─────────────────────────────────────────────────────── --}}
    @php
        $hjVerde    = 0;
        $hjAmbar    = 0;
        $hjVermelho = 0;
        $hjTz       = config('app.timezone');
        foreach ($equipamentos as $eq) {
            $ur    = $ultimosReportes[$eq->prefixo] ?? null;
            $horas = $ur ? (int) $ur->reporte->data_hora_emissao?->setTimezone($hjTz)->diffInHours(now()) : null;
            if ($horas !== null && $horas <= 12) {
                $hjVerde++;
            } elseif ($horas !== null && $horas <= 24) {
                $hjAmbar++;
            } else {
                $hjVermelho++;
            }
        }
    @endphp
    <div class="mb-2 mt-4 flex items-center gap-4 px-1">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">HJ:</span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/40">
                <svg class="h-2.5 w-2.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </span>
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $hjVerde }}</span>
            <span>— Menos de 12h</span>
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/30">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 dark:bg-amber-500"></span>
            </span>
            <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $hjAmbar }}</span>
            <span>— Entre 12h e 24h</span>
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-50 dark:bg-rose-950/30">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-400 dark:bg-rose-500"></span>
            </span>
            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $hjVermelho }}</span>
            <span>— Mais de 24h ou sem reporte</span>
        </span>
    </div>

    {{-- ─── Alterações Recentes (últimos 60 min) ──────────────────────────── --}}
    @if($recentementeAlterados->isNotEmpty())
        <div class="mt-2 flex items-center gap-2 overflow-x-auto pb-1 px-0.5">
            <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                ⚡ Recentes:
            </span>
            @foreach($recentementeAlterados as $eqR)
                @php
                    $posR   = $eqR->posicao;
                    $minsR  = (int) $posR->state_since->diffInMinutes(now());
                    $stateR = $posR->tracker_state;
                    if ($stateR === 'Em Movimento') {
                        $emojiR = '🟢';
                        $clsR   = 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:ring-emerald-800/40';
                    } elseif ($stateR === 'Parado') {
                        $emojiR = '🔴';
                        $clsR   = 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:ring-rose-800/40';
                    } else {
                        $emojiR = '⚫';
                        $clsR   = 'bg-zinc-100 text-zinc-500 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700';
                    }
                    $durR = $minsR < 60
                        ? $minsR.'m'
                        : intdiv($minsR, 60).'h '.($minsR % 60).'m';
                @endphp
                <span title="{{ $stateR }} — há {{ $durR }}"
                      class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 ring-inset {{ $clsR }}">
                    {{ $emojiR }}
                    <span class="font-semibold">{{ $eqR->prefixo ?? $eqR->placa }}</span>
                    <span class="opacity-60">há {{ $durR }}</span>
                </span>
            @endforeach
        </div>
    @endif

    {{-- ─── Recentes Elog (últimos 60 min) ───────────────────────────────── --}}
    @if($recentesElog->isNotEmpty())
        <div class="mt-1 flex items-center gap-2 overflow-x-auto pb-1 px-0.5">
            <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                📋 Recentes Elog:
            </span>
            @foreach($recentesElog as $elogItem)
                @php
                    $minsElog = $elogItem['entrada_em'];
                    $durElog  = $minsElog < 60
                        ? $minsElog.'m'
                        : intdiv($minsElog, 60).'h '.($minsElog % 60).'m';
                    $bgElog   = $elogItem['cor'] ?? null;
                @endphp
                <span
                    title="{{ $elogItem['status_operacional'] }}{{ $elogItem['documento'] ? ' — Doc: '.$elogItem['documento'] : '' }} — há {{ $durElog }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded px-2.5 py-1 text-[11px] font-medium"
                    style="{{ $bgElog ? 'background:'.$bgElog.';color:#fff;' : 'background:#f4f4f5;color:#18181b;' }}"
                >
                    <span class="font-semibold">{{ $elogItem['prefixo'] ?? $elogItem['placa'] }}</span>
                    <span style="opacity:.85">{{ $elogItem['status_operacional'] }}</span>
                    <span style="opacity:.7">há {{ $durElog }}</span>
                </span>
            @endforeach
        </div>
    @endif

    {{-- ─── Table card — fixed height + internal scroll ────────────────────── --}}
    <div id="table-wrapper"
         class="mt-3 overflow-hidden rounded-xl border shadow-sm
                border-slate-200 bg-white
                dark:border-zinc-800 dark:bg-zinc-900/50">

        @if($equipamentos->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>
                @if($currentDivisao || $currentModelo || $currentStatusOp || $currentImplementoModelo || $currentMotorista)
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhum equipamento encontrado</h3>
                    <a href="{{ route('control-tower.index') }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium transition-colors
                              border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                        Limpar filtros
                    </a>
                @else
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhum equipamento ativo</h3>
                @endif
            </div>

        @else
            {{-- Label de última sincronização --}}
            @php
                $tz = config('app.timezone');
                $ultimaSinc = $equipamentos
                    ->map(fn ($e) => $e->posicao?->synced_at)
                    ->filter()
                    ->max();
            @endphp
            @if($ultimaSinc)
                <div class="flex items-center gap-1.5 px-1 pb-1 text-[11px] text-zinc-400 dark:text-zinc-600">
                    <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/>
                    </svg>
                    Última sincronização do rastreador: {{ $ultimaSinc->setTimezone($tz)->format('d/m/Y H:i') }}
                </div>
            @endif

            {{-- Scrollable area --}}
            <div class="overflow-auto" style="max-height: calc(100vh - 260px)">
                <table id="ct-table" class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                            <th class="w-8 px-3 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap"
                                title="Reporte do dia">
                                Hj
                            </th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Prefixo
                            </th>
                            <th data-col="placa" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Placa
                            </th>
                            <th data-col="modelo" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Modelo / Implemento
                            </th>
                            <th data-col="status-op" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Status
                            </th>
                            <th data-col="tempo-status" data-sortable="tempo-status"
                                class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap cursor-pointer select-none hover:text-zinc-600 dark:hover:text-zinc-400"
                                onclick="sortTableByCol('tempo-status')">
                                Tempo Status <span id="sort-icon-tempo-status" class="ml-0.5 opacity-40">↕</span>
                            </th>
                            <th data-col="cerca" data-sortable="cerca"
                                class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap cursor-pointer select-none hover:text-zinc-600 dark:hover:text-zinc-400"
                                onclick="sortTableByCol('cerca')">
                                Cerca <span id="sort-icon-cerca" class="ml-0.5 opacity-40">↕</span>
                            </th>
                            <th data-col="tempo-cerca" data-sortable="tempo-cerca"
                                class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap cursor-pointer select-none hover:text-zinc-600 dark:hover:text-zinc-400"
                                onclick="sortTableByCol('tempo-cerca')">
                                Tempo Cerca <span id="sort-icon-tempo-cerca" class="ml-0.5 opacity-40">↕</span>
                            </th>
                            <th data-col="condutor" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Condutor
                            </th>
                            <th data-col="ultimo-reporte" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Último Reporte
                            </th>
                            <th data-col="documento" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Documento
                            </th>
                            <th data-col="obs" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Observação
                            </th>
                            <th data-col="divisao" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Divisão
                            </th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                <span class="sr-only">Ações</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="ct-tbody" class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($equipamentos as $equipamento)
                            @php
                                $impNome = $equipamento->implemento_nome_override
                                    ?? $equipamento->implemento?->modelo?->nome
                                    ?? $equipamento->implemento?->placa;

                                $ultimoReporte = $ultimosReportes[$equipamento->prefixo] ?? null;
                                $repDocumento  = $ultimoReporte?->documento;
                                $repStatus     = $ultimoReporte?->status_operacional;
                                $repObservacao = $ultimoReporte?->observacao;

                                $searchText = implode(' ', array_filter([
                                    $equipamento->placa,
                                    $equipamento->prefixo,
                                    $equipamento->modelo?->nome,
                                    $equipamento->divisao?->nome,
                                    $repStatus ?? $equipamento->status_operacional,
                                    $equipamento->origem,
                                    $equipamento->destino,
                                    $repDocumento ?? $equipamento->documento_demanda,
                                    $impNome,
                                    $equipamento->motorista?->nome,
                                ]));

                                $posicao        = $equipamento->posicao;
                                $hasLocation    = $posicao && $posicao->latitude && $posicao->longitude;
                                $mapLabel       = trim(($equipamento->prefixo ?? '') . ' ' . $equipamento->placa);
                                $posicaoAt      = $posicao?->position_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i');
                                $syncedAt       = $posicao?->synced_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i');

                                $stateSinceRaw  = $posicao?->state_since;
                                $stateMins      = ($stateSinceRaw && $stateSinceRaw->isPast())
                                    ? (int) $stateSinceRaw->diffInMinutes(now())
                                    : null;
                                if ($stateMins !== null) {
                                    $sd = intdiv($stateMins, 1440);
                                    $sh = intdiv($stateMins % 1440, 60);
                                    $sm = $stateMins % 60;
                                    $stateDuration = $sd > 0 ? "{$sd}d {$sh}h {$sm}m" : ($sh > 0 ? "{$sh}h {$sm}m" : "{$sm}m");
                                } else {
                                    $stateDuration = null;
                                }

                                $semSinal     = ! $posicao || ! $posicao->position_at || $posicao->position_at->lt(now()->subHours(3));
                                if ($semSinal && $posicao?->position_at) {
                                    $semSinalMins = (int) $posicao->position_at->diffInMinutes(now());
                                    $sdx = intdiv($semSinalMins, 1440);
                                    $shx = intdiv($semSinalMins % 1440, 60);
                                    $smx = $semSinalMins % 60;
                                    $semSinalDuration = $sdx > 0 ? "{$sdx}d {$shx}h {$smx}m" : ($shx > 0 ? "{$shx}h {$smx}m" : "{$smx}m");
                                } else {
                                    $semSinalDuration = null;
                                }

                                $eventoAberto  = $eventosAbertos->get($equipamento->id);
                                $cercaMins     = $eventoAberto ? (int) $eventoAberto->entrada_em->diffInMinutes(now()) : null;
                                $cercaNome     = $eventoAberto?->cerca?->nome;
                                if ($cercaMins !== null) {
                                    $cd = intdiv($cercaMins, 1440);
                                    $ch = intdiv($cercaMins % 1440, 60);
                                    $cm = $cercaMins % 60;
                                    $cercaDuration = $cd > 0 ? "{$cd}d {$ch}h {$cm}m" : ($ch > 0 ? "{$ch}h {$cm}m" : "{$cm}m");

                                    $tMin = (int) ($eventoAberto->cerca->tempo_minimo ?? 15);
                                    $tMax = (int) ($eventoAberto->cerca->tempo_maximo ?? 120);
                                    if ($cercaMins < $tMin) {
                                        $cercaBarColor = '#2563eb'; // azul — abaixo do mínimo
                                    } elseif ($cercaMins < $tMax * 0.75) {
                                        $cercaBarColor = '#16a34a'; // verde — dentro do limite
                                    } elseif ($cercaMins < $tMax) {
                                        $cercaBarColor = '#ca8a04'; // amarelo — próximo do limite
                                    } else {
                                        $cercaBarColor = '#dc2626'; // vermelho — excedeu
                                    }
                                } else {
                                    $cercaDuration = null;
                                    $cercaBarColor = null;
                                }

                                $statusEvento    = $statusEventosAbertos->get($equipamento->id);
                                $elogMins        = $statusEvento ? (int) $statusEvento->entrada_em->diffInMinutes(now()) : null;
                                if ($elogMins !== null) {
                                    $ed = intdiv($elogMins, 1440);
                                    $eh = intdiv($elogMins % 1440, 60);
                                    $em = $elogMins % 60;
                                    $elogDuracao = $ed > 0 ? "{$ed}d {$eh}h {$em}m" : ($eh > 0 ? "{$eh}h {$em}m" : "{$em}m");
                                } else {
                                    $elogDuracao = null;
                                }

                                $documento        = $statusEvento?->documento;
                                $minutosPassados  = $documento
                                    ? (int) ($minutosAtendimento->get($equipamento->id . '_' . $documento)?->total_minutos ?? 0)
                                    : 0;
                                $totalAtendimento = $minutosPassados + ($elogMins ?? 0);
                                if ($totalAtendimento > 0) {
                                    $ta = intdiv($totalAtendimento, 1440);
                                    $tb = intdiv($totalAtendimento % 1440, 60);
                                    $tc = $totalAtendimento % 60;
                                    $tempoAtendimento = $ta > 0 ? "{$ta}d {$tb}h {$tc}m" : ($tb > 0 ? "{$tb}h {$tc}m" : "{$tc}m");
                                } else {
                                    $tempoAtendimento = null;
                                }

                                $mapInfo = [
                                    'status_elog'       => $statusEvento?->status_operacional,
                                    'tempo_elog'        => $elogDuracao,
                                    'atendimento'       => $documento,
                                    'tempo_atendimento' => $tempoAtendimento,
                                    'observacao'        => $statusEvento?->observacao,
                                    'tracker_state'    => $posicao?->tracker_state,
                                    'state_duration'   => $stateDuration,
                                    'sem_sinal'        => $semSinal,
                                    'sem_sinal_duration' => $semSinalDuration ?? null,
                                    'ignition'         => (bool) ($posicao?->ignition ?? false),
                                    'speed'            => (int) ($posicao?->speed ?? 0),
                                    'motorista'        => $equipamento->motorista?->nome,
                                    'cerca_nome'       => $cercaNome ?? null,
                                    'cerca_duracao'    => $cercaDuration ?? null,
                                    'cerca_bar_color'  => $cercaBarColor ?? null,
                                ];
                                $tz             = config('app.timezone');
                                $horasDesdeReporte = $ultimoReporte
                                    ? $ultimoReporte->reporte->data_hora_emissao?->setTimezone($tz)->diffInHours(now())
                                    : null;
                                $tempoReporteLabel = $horasDesdeReporte !== null
                                    ? 'Último reporte: ' . intdiv($horasDesdeReporte, 24) . 'd ' . ($horasDesdeReporte % 24) . 'h'
                                    : 'Sem reporte';
                            @endphp

                            {{-- ─── Data row ──────────────────────────────── --}}
                            <tr id="row-{{ $equipamento->id }}"
                                data-search="{{ strtolower($searchText) }}"
                                data-tracker-state="{{ $semSinal ? 'Desconhecido' : ($posicao?->tracker_state ?? '') }}"
                                class="ct-row transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">

                                {{-- Sinalizador HJ --}}
                                <td class="px-3 py-2 text-center">
                                    @if($horasDesdeReporte !== null && $horasDesdeReporte <= 12)
                                        {{-- Verde: reporte há menos de 12h --}}
                                        <span title="{{ $tempoReporteLabel }}"
                                              class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/40">
                                            <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                            </svg>
                                        </span>
                                    @elseif($horasDesdeReporte !== null && $horasDesdeReporte <= 24)
                                        {{-- Laranja: reporte entre 12h e 24h --}}
                                        <span title="{{ $tempoReporteLabel }}"
                                              class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/30">
                                            <span class="h-2 w-2 rounded-full bg-amber-400 dark:bg-amber-500"></span>
                                        </span>
                                    @else
                                        {{-- Vermelho: sem reporte ou há mais de 24h --}}
                                        <span title="{{ $tempoReporteLabel }}"
                                              class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-50 dark:bg-rose-950/30">
                                            <span class="h-2 w-2 rounded-full bg-rose-400 dark:bg-rose-500"></span>
                                        </span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 whitespace-nowrap">
                                    <p class="text-base font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $equipamento->prefixo ?? '—' }}</p>
                                    @if($ultimoReporte)
                                        <a href="{{ route('reportes.show', $ultimoReporte->reporte) }}" target="_blank"
                                           class="font-mono text-[10px] text-zinc-400 hover:text-zinc-600 dark:text-zinc-600 dark:hover:text-zinc-400 underline decoration-dotted">
                                            {{ $ultimoReporte->reporte->numero_reporte }}
                                        </a>
                                    @else
                                        <span class="text-[10px] font-medium text-rose-400 dark:text-rose-600">sem reporte</span>
                                    @endif
                                </td>


                                <td data-col="placa" class="px-3 py-2 whitespace-nowrap">
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $equipamento->placa }}</p>
                                    @if($equipamento->id_elog)
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-600">{{ $equipamento->id_elog }}</p>
                                    @endif
                                </td>

                                <td data-col="modelo" class="px-3 py-2">
                                    <p class="whitespace-nowrap text-zinc-700 dark:text-zinc-300">{{ $equipamento->modelo?->nome ?? '—' }}</p>
                                    <div class="mt-0.5 flex items-center gap-1">
                                        @if($impNome)
                                            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700
                                                         ring-1 ring-inset ring-amber-600/20
                                                         dark:bg-amber-950/30 dark:text-amber-400 dark:ring-amber-500/20 whitespace-nowrap">
                                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 1 1.242 7.244"/>
                                                </svg>
                                                {{ $impNome }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-zinc-300 dark:text-zinc-700">sem implemento</span>
                                        @endif
                                        <button type="button"
                                                onclick="openImplementoModal('{{ route('control-tower.implemento', $equipamento) }}', {{ $equipamento->id }}, '{{ addslashes($equipamento->placa) }}', {{ $equipamento->implemento_id ?? 'null' }}, '{{ addslashes($equipamento->implemento_nome_override ?? '') }}')"
                                                title="Vincular / alterar implemento"
                                                class="rounded p-0.5 text-zinc-300 transition-colors hover:text-zinc-600
                                                       dark:text-zinc-700 dark:hover:text-zinc-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 1 1.242 7.244"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <td data-col="status-op" class="px-3 py-2 whitespace-nowrap">
                                    @if($repStatus)
                                        @php $cor = $statusCores[$repStatus] ?? '#71717A'; @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                              style="background-color: {{ $cor }}1A; color: {{ $cor }}; box-shadow: inset 0 0 0 1px {{ $cor }}33;">
                                            {{ $repStatus }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>


                                <td data-col="tempo-status" class="px-3 py-2 whitespace-nowrap"
                                    data-mins="{{ $semSinal ? ($semSinalMins ?? 0) : ($stateMins ?? 0) }}">
                                    @if($semSinal)
                                        <div style="background:#52525b;padding:4px 10px;text-align:center;color:#fff;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:0.04em;min-width:80px;">
                                            {{ $semSinalDuration ?? '—' }}
                                        </div>
                                    @elseif($posicao && $posicao->tracker_state)
                                        @php $barColor = $posicao->tracker_state === 'Em Movimento' ? '#16a34a' : '#dc2626'; @endphp
                                        <div style="background:{{ $barColor }};padding:4px 10px;text-align:center;color:#fff;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:0.04em;min-width:80px;">
                                            {{ $stateDuration ?? '—:—:—' }}
                                        </div>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>

                                <td data-col="cerca" class="px-3 py-2 whitespace-nowrap"
                                    data-cerca="{{ strtolower($cercaNome ?? '') }}">
                                    @if($cercaNome)
                                        <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">{{ $cercaNome }}</p>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>

                                <td data-col="tempo-cerca" class="px-3 py-2 whitespace-nowrap"
                                    data-mins="{{ $cercaMins ?? 0 }}">
                                    @if($cercaDuration && $cercaBarColor)
                                        <div style="background:{{ $cercaBarColor }};padding:4px 10px;text-align:center;color:#fff;font-size:13px;font-weight:600;font-variant-numeric:tabular-nums;letter-spacing:0.04em;min-width:80px;">
                                            {{ $cercaDuration }}
                                        </div>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>

                                <td data-col="condutor" class="px-3 py-2 whitespace-nowrap">
                                    @if($equipamento->motorista)
                                        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $equipamento->motorista->nome }}</p>
                                        @php $telefone = $equipamento->motorista->contatos->where('status', true)->first()?->telefone; @endphp
                                        @if($telefone)
                                            <p class="text-[11px] text-zinc-400 dark:text-zinc-600">{{ $telefone }}</p>
                                        @endif
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>

                                <td data-col="ultimo-reporte" class="px-3 py-2 whitespace-nowrap">
                                    @if($ultimoReporte)
                                        <a href="{{ route('reportes.show', $ultimoReporte->reporte) }}" target="_blank"
                                           class="flex flex-col gap-0.5 group">
                                            <span class="font-mono text-xs font-semibold text-zinc-600 underline decoration-dotted underline-offset-2
                                                         group-hover:text-zinc-900 dark:text-zinc-400 dark:group-hover:text-zinc-200">
                                                {{ $ultimoReporte->reporte->numero_reporte }}
                                            </span>
                                            <span class="text-[11px] text-zinc-400 dark:text-zinc-600">
                                                {{ $ultimoReporte->reporte->data_hora_emissao?->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                            </span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-600
                                                     ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:ring-rose-800/40">
                                            Sem reporte
                                        </span>
                                    @endif
                                </td>

                                <td data-col="documento" class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $repDocumento ?? '—' }}
                                </td>


                                <td data-col="obs" class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                    <span class="line-clamp-2 min-w-[540px] block">{{ $repObservacao ?? '—' }}</span>
                                </td>

                                <td data-col="divisao" class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $equipamento->divisao?->nome ?? '—' }}
                                </td>

                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button"
                                                onclick="openReporteRapidoModal({{ $equipamento->id }}, '{{ addslashes($equipamento->prefixo ?? '') }}', '{{ addslashes($equipamento->placa) }}', '{{ route('control-tower.reporte-rapido', $equipamento) }}')"
                                                title="Criar reporte rápido"
                                                class="inline-flex items-center gap-1 rounded border px-2 py-1 text-[11px] font-medium transition-colors
                                                       border-zinc-200 bg-white text-zinc-400 hover:bg-zinc-50 hover:text-zinc-700
                                                       dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                            Reporte
                                        </button>
                                        <button type="button"
                                                data-map-info='@json($mapInfo, JSON_HEX_APOS)'
                                                onclick="openMapModal({{ $hasLocation ? $posicao->latitude : 'null' }}, {{ $hasLocation ? $posicao->longitude : 'null' }}, '{{ addslashes($mapLabel) }}', '{{ addslashes($equipamento->id_rastreador) }}', '{{ $posicaoAt ?? '' }}', '{{ $syncedAt ?? '' }}', {{ $equipamento->id }}, JSON.parse(this.dataset.mapInfo))"
                                                title="{{ $hasLocation ? 'Ver localização no mapa' : 'Sincronizar e ver localização no mapa' }}"
                                                class="inline-flex items-center gap-1 rounded border px-2 py-1 text-[11px] font-medium transition-colors
                                                       border-zinc-200 bg-white text-zinc-400 hover:bg-zinc-50 hover:text-zinc-700
                                                       dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                                            </svg>
                                            Mapa
                                        </button>
                                        <a href="{{ route('ocorrencias.veiculo', $equipamento) }}"
                                           title="Ver ocorrências do veículo"
                                           class="inline-flex items-center gap-1 rounded border px-2 py-1 text-[11px] font-medium transition-colors
                                                  border-zinc-200 bg-white text-zinc-400 hover:bg-zinc-50 hover:text-zinc-700
                                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                            </svg>
                                            Ocorrências
                                        </a>
                                        <a href="{{ route('control-tower.historico', $equipamento) }}"
                                           title="Ver histórico de alterações"
                                           class="inline-flex items-center gap-1 rounded border px-2 py-1 text-[11px] font-medium transition-colors
                                                  border-zinc-200 bg-white text-zinc-400 hover:bg-zinc-50 hover:text-zinc-700
                                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                            </svg>
                                            Logs
                                        </a>
                                    </div>
                                </td>
                            </tr>

                        @endforeach

                        {{-- Empty search state --}}
                        <tr id="no-results" class="hidden">
                            <td colspan="12" class="px-6 py-10 text-center text-sm text-zinc-400 dark:text-zinc-600">
                                Nenhum resultado para a busca.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($equipamentos->hasPages())
                <div class="border-t border-slate-100 px-4 py-3 dark:border-zinc-800">
                    {{ $equipamentos->links() }}
                </div>
            @endif
        @endif
    </div>

    @if(!$equipamentos->isEmpty())
        <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-600">
            {{ $equipamentos->total() }} {{ $equipamentos->total() === 1 ? 'equipamento' : 'equipamentos' }} no total
            @if($currentDivisao || $currentModelo || $currentStatusOp || $currentImplementoModelo || $currentMotorista) · filtros ativos @endif
        </p>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── Mapa (Leaflet.js) ──────────────────────────────────────────────── --}}
    {{-- Backdrop e overlay 100% via inline style — sem conflito com Tailwind  --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}

    {{-- Backdrop — visível apenas no modo modal (pequeno) --}}
    <div id="map-backdrop"
         onclick="closeMapModal()"
         style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:9998; background:rgba(0,0,0,0.5); backdrop-filter:blur(2px);"></div>

    {{-- Overlay — alterna entre modal pequeno e tela cheia via JS --}}
    <div id="map-overlay"
         style="display:none; position:fixed; z-index:9999; flex-direction:column;"
         class="bg-white dark:bg-zinc-900">

        {{-- Cabeçalho --}}
        <div style="flex-shrink:0;"
             class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Localização</h3>
                <p id="map-vehicle-label" class="text-xs text-zinc-500 dark:text-zinc-400"></p>
                <p id="map-position-info" class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-600"></p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Atualizar posição --}}
                <button type="button" id="map-btn-refresh" onclick="refreshMapPosition()" title="Atualizar posição desta placa"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg id="map-refresh-icon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </button>
                {{-- Expandir / comprimir --}}
                <button type="button" id="map-btn-fs" onclick="toggleMapFullscreen()" title="Expandir mapa"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg id="map-icon-expand" style="display:block;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                    </svg>
                    <svg id="map-icon-compress" style="display:none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                    </svg>
                </button>
                <button type="button" onclick="closeMapModal()" title="Fechar mapa"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Container do mapa --}}
        <div id="leaflet-map" style="height:600px;"></div>
    </div>

    {{-- ─── Mapa Geral ─────────────────────────────────────────────────────── --}}

    <div id="mapa-geral-backdrop"
         onclick="closeMapaGeral()"
         style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:9998; background:rgba(0,0,0,0.5); backdrop-filter:blur(2px);"></div>

    <div id="mapa-geral-overlay"
         style="display:none; position:fixed; z-index:9999; flex-direction:column; overflow:hidden;"
         class="bg-white dark:bg-zinc-900">

        {{-- Cabeçalho --}}
        <div style="flex-shrink:0;"
             class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Mapa Geral</h3>
                <p id="mapa-geral-info" class="text-xs text-zinc-500 dark:text-zinc-400">Carregando…</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Recarregar posições --}}
                <button type="button" id="mapa-geral-btn-refresh" onclick="sincronizarERecarregar()" title="Sincronizar posições com a API"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg id="mapa-geral-refresh-icon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </button>
                {{-- Expandir / comprimir --}}
                <button type="button" id="mapa-geral-btn-fs" onclick="toggleMapaGeralFullscreen()" title="Expandir mapa"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg id="mapa-geral-icon-expand" style="display:block;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                    </svg>
                    <svg id="mapa-geral-icon-compress" style="display:none;" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                    </svg>
                </button>
                <button type="button" onclick="closeMapaGeral()" title="Fechar"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Busca rápida + Modo Interativo --}}
        <div style="flex-shrink:0;" class="border-b px-4 py-2 border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                {{-- Input de busca --}}
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input id="mapa-geral-search" type="text" autocomplete="off"
                           placeholder="Ir para prefixo ou placa…"
                           oninput="filtrarMapaGeral()"
                           onkeydown="if(event.key==='Enter') navegarMapaGeralPrimeiro(); if(event.key==='Escape') document.getElementById('mapa-geral-search-dropdown').classList.add('hidden');"
                           class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-8 pr-3 text-sm outline-none
                                  placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-500">
                    <div id="mapa-geral-search-dropdown"
                         class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg
                                dark:border-zinc-700 dark:bg-zinc-800">
                    </div>
                </div>

                {{-- Modo Interativo --}}
                <select id="mi-intervalo"
                        class="rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs text-zinc-500 outline-none
                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <option value="5000">5s</option>
                    <option value="10000">10s</option>
                    <option value="15000" selected>15s</option>
                    <option value="30000">30s</option>
                    <option value="45000">45s</option>
                    <option value="60000">60s</option>
                </select>
                <select id="mi-zoom"
                        class="rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs text-zinc-500 outline-none
                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    @for($z = 14; $z <= 25; $z++)
                        <option value="{{ $z }}" @selected($z === 18)>zoom {{ $z }}</option>
                    @endfor
                </select>
                <button type="button" id="btn-modo-interativo" onclick="toggleModoInterativo()"
                        title="Modo Interativo — percorre todos os veículos automaticamente"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors
                               border-slate-200 bg-white text-zinc-500 hover:border-slate-300 hover:bg-slate-50
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600">
                    <span id="mi-dot" class="h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600 transition-colors"></span>
                    <span id="mi-label">Modo Interativo</span>
                </button>
            </div>
        </div>

        {{-- Faixa de alterações recentes Vfleets --}}
        <div id="mapa-geral-recentes" style="flex-shrink:0; display:none;"
             class="border-b border-slate-200 dark:border-zinc-800 px-4 py-1.5 overflow-x-auto whitespace-nowrap">
        </div>

        {{-- Faixa de alterações recentes Elog --}}
        <div id="mapa-geral-recentes-elog" style="flex-shrink:0; display:none;"
             class="border-b border-slate-200 dark:border-zinc-800 px-4 py-1.5 overflow-x-auto whitespace-nowrap">
        </div>

        {{-- Container do mapa geral --}}
        <div id="leaflet-map-geral" style="flex:1;"></div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── Implemento modal ───────────────────────────────────────────────── --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div id="implemento-backdrop"
         onclick="closeImplementoModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    <div id="implemento-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden max-h-[88vh] w-full max-w-lg -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        <div class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Vincular Implemento</h3>
                <p id="modal-subtitle" class="text-xs text-zinc-500 dark:text-zinc-400"></p>
            </div>
            <button type="button" onclick="closeImplementoModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto" style="max-height: calc(88vh - 120px)">
            <form id="implemento-form" method="POST" action="">
                @csrf
                @method('PATCH')

                <div class="border-b px-5 py-3.5 border-slate-100 dark:border-zinc-800">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                        Nome de exibição (opcional)
                    </label>
                    <input type="text" id="modal-nome-override" name="implemento_nome_override"
                           placeholder="Deixe em branco para usar o nome do modelo"
                           class="mt-1.5 w-full rounded-lg border px-3 py-2 text-sm outline-none transition-all
                                  border-slate-200 bg-white text-zinc-700 placeholder-zinc-300
                                  focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:placeholder-zinc-600
                                  dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                    <p class="mt-1 text-[11px] text-zinc-400 dark:text-zinc-600">Personaliza como o implemento aparece na coluna "Modelo / Implemento".</p>
                </div>

                <div class="px-5 py-3.5">
                    <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                        Selecione o implemento
                    </p>
                    <div id="modal-list" class="space-y-1.5"></div>
                </div>

                <input type="hidden" id="modal-implemento-id" name="implemento_id" value="">

                <div class="flex items-center justify-between border-t px-5 py-3.5 border-slate-100 dark:border-zinc-800">
                    <button type="button" id="modal-btn-desvincular"
                            onclick="desvinculaImplemento()"
                            class="hidden text-xs font-medium text-rose-600 transition-colors hover:text-rose-800
                                   dark:text-rose-400 dark:hover:text-rose-300">
                        Desvincular implemento
                    </button>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" onclick="closeImplementoModal()"
                                class="rounded-lg border px-3.5 py-1.5 text-sm font-medium transition-colors
                                       border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50
                                       dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition-colors
                                       bg-zinc-900 text-white hover:bg-zinc-700
                                       dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── Modal: Reporte Rápido ─────────────────────────────────────────── --}}
    <div id="reporte-rapido-backdrop" onclick="closeReporteRapidoModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    <div id="reporte-rapido-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden max-h-[92vh] w-full max-w-xl -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Novo Reporte</h3>
                <p id="rr-subtitle" class="text-xs text-zinc-500 dark:text-zinc-400"></p>
            </div>
            <button type="button" onclick="closeReporteRapidoModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="overflow-y-auto px-5 py-4 space-y-4" style="max-height: calc(92vh - 130px)">

            {{-- Erros --}}
            <div id="rr-errors" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700
                                       dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-400"></div>

            {{-- Nome --}}
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Nome do Reporte <span class="text-red-500">*</span></label>
                <input id="rr-nome" type="text" placeholder="Ex: Reporte Interno 2021"
                       class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                              border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
            </div>

            {{-- Status + 1º Contato --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Status Operacional <span class="text-red-500">*</span></label>
                    <select id="rr-status"
                            class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                   border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">— Selecione —</option>
                        @foreach($statusOperacionais as $statusOp)
                            <option value="{{ $statusOp->nome }}">{{ $statusOp->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">1º Contato <span class="text-red-500">*</span></label>
                    <input id="rr-primeiro-contato" type="text" placeholder="Nome do responsável"
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                </div>
            </div>

            {{-- Documento (+ Elog) + Tempo Parado --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Documento</label>
                    <input id="rr-documento" type="text" placeholder="Nº do documento"
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                    <div class="mt-1.5 flex items-center gap-3">
                        <button type="button" id="btn-elog-rapido" onclick="buscarElogRapido()"
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600
                                       hover:text-blue-800 disabled:opacity-40 dark:text-blue-400 dark:hover:text-blue-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/>
                            </svg>
                            Pesquisar no Elog
                        </button>
                        <a id="btn-pesquisar-reporte" href="#" target="_blank"
                           class="inline-flex items-center gap-1 text-[11px] font-medium text-violet-600
                                  hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-300">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            Ver Reportes
                        </a>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Tempo Parado</label>
                    <input id="rr-tempo-parado" type="text" placeholder="Ex: 2h30"
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                </div>
            </div>

            {{-- 2º Contato + Previsão --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">2º Contato</label>
                    <input id="rr-segundo-contato" type="text" placeholder="Nome do segundo responsável"
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Previsão</label>
                    <input id="rr-previsao" type="datetime-local"
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                </div>
            </div>

            {{-- Observação --}}
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Observação <span class="text-red-500">*</span></label>
                <input id="rr-observacao" type="text" placeholder="Observações adicionais"
                       class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                              border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 border-t px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <button type="button" onclick="closeReporteRapidoModal()"
                    class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium transition-all
                           border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                           dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                Cancelar
            </button>
            <button type="button" onclick="submitReporteRapido('rascunho')"
                    class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-all
                           border-zinc-300 bg-white text-zinc-700 hover:bg-slate-50
                           dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Rascunho
            </button>
            <button type="button" onclick="submitReporteRapido('publicado')"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition-all
                           hover:bg-zinc-700 active:scale-[0.98]
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Publicar
            </button>
        </div>
    </div>

    {{-- ─── Scripts ─────────────────────────────────────────────────────────── --}}
    <script>
    (function () {
        // ─── Column visibility (localStorage) ──────────────────────────────
        var STORE_KEY = 'ct_hidden_cols';
        var ALL_COLS  = ['placa', 'modelo', 'status-op', 'tempo-status', 'condutor', 'ultimo-reporte', 'documento', 'obs', 'divisao'];

        function hiddenCols() {
            try { return JSON.parse(localStorage.getItem(STORE_KEY)) || []; } catch (e) { return []; }
        }

        function applyColVisibility() {
            var hidden = hiddenCols();
            ALL_COLS.forEach(function (col) {
                var isHidden = hidden.indexOf(col) !== -1;
                document.querySelectorAll('[data-col="' + col + '"]').forEach(function (el) {
                    el.style.display = isHidden ? 'none' : '';
                });
                var check = document.querySelector('.col-picker-check[data-col="' + col + '"]');
                if (check) { check.checked = !isHidden; }
            });
        }

        window.toggleColumn = function (col) {
            var hidden = hiddenCols();
            var idx    = hidden.indexOf(col);
            if (idx === -1) { hidden.push(col); } else { hidden.splice(idx, 1); }
            localStorage.setItem(STORE_KEY, JSON.stringify(hidden));
            applyColVisibility();
        };

        // ─── Sort de colunas ────────────────────────────────────────────────
        var _sortState = {}; // col → 'asc' | 'desc'
        var _SORT_COLS  = ['tempo-status', 'tempo-cerca', 'cerca'];

        // Prioridade de agrupamento: Parado → Desconhecido → Em Movimento → demais
        var _STATE_PRIORITY = { 'Parado': 0, 'Desconhecido': 1, 'Em Movimento': 2 };

        window.sortTableByCol = function (col) {
            var tbody = document.getElementById('ct-tbody');
            if (!tbody) { return; }

            var dir = _sortState[col] === 'asc' ? 'desc' : 'asc';
            _sortState[col] = dir;

            // Atualiza ícones — reseta os demais
            _SORT_COLS.forEach(function (c) {
                var icon = document.getElementById('sort-icon-' + c);
                if (!icon) { return; }
                if (c === col) {
                    icon.textContent = dir === 'asc' ? '↑' : '↓';
                    icon.style.opacity = '1';
                } else {
                    icon.textContent = '↕';
                    icon.style.opacity = '0.4';
                    delete _sortState[c];
                }
            });

            var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));

            rows.sort(function (a, b) {
                var tdA = a.querySelector('[data-col="' + col + '"]');
                var tdB = b.querySelector('[data-col="' + col + '"]');

                if (col === 'cerca') {
                    // Ordenação alfabética; vazios vão para o final
                    var nA = tdA ? (tdA.getAttribute('data-cerca') || '') : '';
                    var nB = tdB ? (tdB.getAttribute('data-cerca') || '') : '';
                    if (!nA && nB) { return 1; }
                    if (nA && !nB) { return -1; }
                    if (nA === nB) { return 0; }
                    return dir === 'asc' ? nA.localeCompare(nB) : nB.localeCompare(nA);
                }

                // Colunas de tempo: agrupa por status, depois ordena por minutos
                var stateA = a.getAttribute('data-tracker-state') || '';
                var stateB = b.getAttribute('data-tracker-state') || '';
                var pA = _STATE_PRIORITY[stateA] !== undefined ? _STATE_PRIORITY[stateA] : 99;
                var pB = _STATE_PRIORITY[stateB] !== undefined ? _STATE_PRIORITY[stateB] : 99;
                if (pA !== pB) { return pA - pB; }

                var vA = tdA ? parseInt(tdA.getAttribute('data-mins') || '0', 10) : 0;
                var vB = tdB ? parseInt(tdB.getAttribute('data-mins') || '0', 10) : 0;
                return dir === 'asc' ? vA - vB : vB - vA;
            });

            rows.forEach(function (row) { tbody.appendChild(row); });
        };

        // ─── Column picker dropdown ─────────────────────────────────────────
        window.toggleColPicker = function () {
            var panel = document.getElementById('col-picker-panel');
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) {
                document.getElementById('col-picker-search').value = '';
                document.querySelectorAll('.col-picker-item').forEach(function (el) { el.style.display = ''; });
                document.getElementById('col-picker-search').focus();
            }
        };

        document.addEventListener('click', function (e) {
            var wrapper = document.getElementById('col-picker-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('col-picker-panel').classList.add('hidden');
            }
        });

        document.getElementById('col-picker-search').addEventListener('input', function () {
            var q = this.value.toLowerCase();
            document.querySelectorAll('.col-picker-item').forEach(function (item) {
                var lbl = item.dataset.colLabel || '';
                item.style.display = (!q || lbl.indexOf(q) !== -1) ? '' : 'none';
            });
        });

        applyColVisibility();

        // ─── Live search ────────────────────────────────────────────────────
        var allRows    = Array.from(document.querySelectorAll('.ct-row'));
        var noResults  = document.getElementById('no-results');
        var counter    = document.getElementById('row-counter');

        function updateCounter(visible) {
            counter.textContent = visible + ' / ' + allRows.length + ' equipamentos';
        }
        updateCounter(allRows.length);

        document.getElementById('live-search').addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            var visible = 0;
            allRows.forEach(function (row) {
                var match = !q || row.dataset.search.indexOf(q) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) { visible++; }
            });
            noResults.style.display = visible === 0 ? '' : 'none';
            updateCounter(visible);
        });

    })();

    // ─── Implemento modal ────────────────────────────────────────────────────
    var IMPLEMENTOS      = @json($implementos->values());
    var _currentMotoId   = null;
    var _selectedImpId   = null;

    function openImplementoModal(patchUrl, motoId, motoPlaca, currentImpId, currentNomeOverride) {
        _currentMotoId = motoId;
        _selectedImpId = currentImpId;

        document.getElementById('modal-subtitle').textContent = motoPlaca;
        document.getElementById('implemento-form').action = patchUrl;
        document.getElementById('modal-nome-override').value = currentNomeOverride || '';

        var btnDesv = document.getElementById('modal-btn-desvincular');
        currentImpId ? btnDesv.classList.remove('hidden') : btnDesv.classList.add('hidden');

        renderImplementoList(motoId, currentImpId);
        document.getElementById('implemento-backdrop').classList.remove('hidden');
        document.getElementById('implemento-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function renderImplementoList(motoId, selectedImpId) {
        var list = document.getElementById('modal-list');

        if (IMPLEMENTOS.length === 0) {
            list.innerHTML = '<p class="py-6 text-center text-sm text-zinc-400 dark:text-zinc-600">Nenhum implemento ativo cadastrado.</p>';
            return;
        }

        list.innerHTML = IMPLEMENTOS.map(function (imp) {
            var isCurrent = imp.id == selectedImpId;
            var isTaken   = imp.taken_by !== null && imp.taken_by != motoId;
            var label     = [imp.prefixo, imp.modelo].filter(Boolean).join(' · ') || imp.placa;

            var base = 'flex items-center gap-3 rounded-xl border px-4 py-2.5 ';
            var cls  = isTaken
                ? base + 'cursor-not-allowed opacity-40 border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-800/30'
                : isCurrent
                    ? base + 'cursor-pointer border-zinc-900 bg-zinc-900/5 ring-1 ring-zinc-900/10 dark:border-zinc-400 dark:bg-zinc-400/10'
                    : base + 'cursor-pointer border-slate-200 bg-white hover:border-zinc-300 hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-600';

            var dot = isCurrent
                ? '<span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-zinc-900 dark:bg-zinc-100"><span class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-900"></span></span>'
                : '<span class="h-4 w-4 shrink-0 rounded-full border-2 border-slate-300 dark:border-zinc-600"></span>';

            var badge = isTaken
                ? '<span class="ml-auto shrink-0 text-[11px] text-zinc-400 dark:text-zinc-600">Em uso</span>'
                : (isCurrent ? '<span class="ml-auto shrink-0 text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Atual</span>' : '');

            var onclick = isTaken ? '' : 'onclick="selectImplemento(' + imp.id + ')"';

            return '<div id="imp-item-' + imp.id + '" class="' + cls + '" data-imp-id="' + imp.id + '" ' + onclick + '>'
                + dot
                + '<div class="min-w-0 flex-1">'
                + '<p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">' + escHtml(imp.placa) + '</p>'
                + '<p class="text-xs text-zinc-500 dark:text-zinc-400">' + escHtml(label) + '</p>'
                + '</div>' + badge + '</div>';
        }).join('');

        document.getElementById('modal-implemento-id').value = selectedImpId || '';
    }

    function selectImplemento(impId) {
        _selectedImpId = impId;
        document.getElementById('modal-implemento-id').value = impId;

        document.querySelectorAll('#modal-list [data-imp-id]').forEach(function (item) {
            if (item.classList.contains('cursor-not-allowed')) { return; }
            var isSelected = item.dataset.impId == impId;
            var base = 'flex items-center gap-3 rounded-xl border px-4 py-2.5 ';

            item.className = isSelected
                ? base + 'cursor-pointer border-zinc-900 bg-zinc-900/5 ring-1 ring-zinc-900/10 dark:border-zinc-400 dark:bg-zinc-400/10'
                : base + 'cursor-pointer border-slate-200 bg-white hover:border-zinc-300 hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-600';

            var dot = item.querySelector('span:first-child');
            if (dot) {
                dot.outerHTML = isSelected
                    ? '<span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-zinc-900 dark:bg-zinc-100"><span class="h-1.5 w-1.5 rounded-full bg-white dark:bg-zinc-900"></span></span>'
                    : '<span class="h-4 w-4 shrink-0 rounded-full border-2 border-slate-300 dark:border-zinc-600"></span>';
            }

            var badge = item.querySelector('.ml-auto');
            if (badge) { badge.remove(); }
            if (isSelected) {
                item.insertAdjacentHTML('beforeend', '<span class="ml-auto shrink-0 text-[11px] font-medium text-zinc-500 dark:text-zinc-400">Selecionado</span>');
            }
        });

        document.getElementById('modal-btn-desvincular').classList.remove('hidden');
    }

    function desvinculaImplemento() {
        _selectedImpId = null;
        document.getElementById('modal-implemento-id').value = '';
        renderImplementoList(_currentMotoId, null);
        document.getElementById('modal-btn-desvincular').classList.add('hidden');
    }

    function closeImplementoModal() {
        document.getElementById('implemento-backdrop').classList.add('hidden');
        document.getElementById('implemento-modal').classList.add('hidden');
        document.body.style.overflow = '';
        _currentMotoId = null;
        _selectedImpId = null;
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.getElementById('col-picker-panel').classList.add('hidden');
            closeImplementoModal();
            closeConfirmModal();
            closeMapModal();
            closeReporteRapidoModal();
        }
    });
    </script>

    {{-- Leaflet.js --}}
    <script src="/vendor/leaflet/leaflet.js"></script>

    <script>
    // ─── Mapa (Leaflet.js) ───────────────────────────────────────────────────
    var _leafletMap      = null;
    var _leafletMarker   = null;
    var _mapIsFullscreen = false;
    var _currentMapPlate = null;
    var _currentRowId    = null;
    var _currentMapInfo  = {};
    var _posicaoBaseUrl  = '{{ url("torre-de-controle/posicao") }}';

    function _buildMapPopup(label, info) {
        info = info || {};
        var semSinal = !!info.sem_sinal;
        var row = function(lbl, val) {
            return '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">' + lbl + '</td>'
                 + '<td style="font-weight:600">' + val + '</td></tr>';
        };

        var html = '<div style="min-width:500px;font-size:22px;line-height:1.6">'
            + '<p style="font-weight:600;font-size:24px;margin:0 0 12px">' + label + '</p>'
            + '<table style="border-collapse:collapse;width:100%">';

        // Status Elog
        if (info.status_elog) {
            var elogVal = info.status_elog;
            if (info.tempo_elog) { elogVal += ' <span style="color:#6b7280;font-weight:400">há ' + info.tempo_elog + '</span>'; }
            html += row('Status Elog', elogVal);
        }

        // Atendimento (documento) + tempo total
        if (info.atendimento) {
            var atendVal = info.atendimento;
            if (info.tempo_atendimento) { atendVal += ' <span style="color:#6b7280;font-weight:400">(' + info.tempo_atendimento + ' total)</span>'; }
            html += row('Atendimento', atendVal);
        }

        // Observação (truncada)
        if (info.observacao) {
            var obs = info.observacao.length > 100 ? info.observacao.substring(0, 100) + '…' : info.observacao;
            html += row('Observação', '<span title="' + info.observacao.replace(/"/g, '&quot;') + '">' + obs + '</span>');
        }

        // Divisória
        html += '<tr><td colspan="2" style="padding:3px 0"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0"></td></tr>';

        // Tracker state / Sem sinal
        if (semSinal) {
            var semSinalVal = '⬛ <span style="font-weight:600">Desconhecido</span>';
            if (info.sem_sinal_duration) { semSinalVal += ' <span style="color:#6b7280;font-weight:400">há ' + info.sem_sinal_duration + '</span>'; }
            html += row('Rastreador', semSinalVal);
        } else {
            var trackerIcon = info.tracker_state === 'Em Movimento' ? '🟢'
                            : (info.tracker_state === 'Parado'      ? '🔴' : '⚫');
            var trackerLabel = trackerIcon + ' ' + (info.tracker_state || 'Sem Sinal');
            if (info.state_duration) { trackerLabel += ' <span style="color:#6b7280;font-weight:400">há ' + info.state_duration + '</span>'; }
            html += row('Rastreador', trackerLabel);
            html += row('Motor', info.ignition ? '🔵 <span style="font-weight:600">Ligado</span>' : '⚪ <span style="font-weight:600">Desligado</span>');
            html += row('Velocidade', (info.speed || 0) + ' km/h');
        }

        // Tempo parado (state_duration quando Parado)
        if (!semSinal && info.tracker_state === 'Parado' && info.state_duration) {
            html += row('Tempo Parado', info.state_duration);
        }

        // Cerca
        if (info.cerca_nome) {
            html += '<tr><td colspan="2" style="padding:3px 0"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0"></td></tr>';
            html += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Cerca</td>'
                  + '<td style="font-weight:600;white-space:nowrap">' + info.cerca_nome + '</td></tr>';
            if (info.cerca_duracao) {
                var cercaBarBg = info.cerca_bar_color || '#6b7280';
                html += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Tempo Cerca</td>'
                      + '<td><div style="background:' + cercaBarBg + ';padding:4px 16px;color:#fff;font-size:21px;font-weight:600;display:inline-block">'
                      + info.cerca_duracao + '</div></td></tr>';
            }
        }

        // Condutor
        if (info.motorista) {
            html += '<tr><td colspan="2" style="padding:3px 0"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0"></td></tr>';
            html += row('Condutor', info.motorista);
        }

        html += '</table></div>';
        return html;
    }

    function _applyMapSize(fullscreen) {
        var overlay  = document.getElementById('map-overlay');
        var mapDiv   = document.getElementById('leaflet-map');
        var backdrop = document.getElementById('map-backdrop');
        var iconExp  = document.getElementById('map-icon-expand');
        var iconCmp  = document.getElementById('map-icon-compress');
        var btn      = document.getElementById('map-btn-fs');

        if (fullscreen) {
            overlay.style.top          = '0';
            overlay.style.left         = '0';
            overlay.style.right        = '0';
            overlay.style.bottom       = '0';
            overlay.style.width        = '';
            overlay.style.maxWidth     = '';
            overlay.style.transform    = 'none';
            overlay.style.borderRadius = '0';
            overlay.style.boxShadow    = 'none';
            mapDiv.style.height        = 'calc(100vh - 57px)';
            backdrop.style.display     = 'none';
            iconExp.style.display      = 'none';
            iconCmp.style.display      = 'block';
            btn.title                  = 'Sair da tela cheia';
        } else {
            overlay.style.top          = '50%';
            overlay.style.left         = '50%';
            overlay.style.right        = '';
            overlay.style.bottom       = '';
            overlay.style.width        = '1020px';
            overlay.style.maxWidth     = 'calc(100vw - 2rem)';
            overlay.style.transform    = 'translate(-50%, -50%)';
            overlay.style.borderRadius = '1rem';
            overlay.style.boxShadow    = '0 25px 50px -12px rgba(0,0,0,.35)';
            mapDiv.style.height        = '600px';
            backdrop.style.display     = 'block';
            iconExp.style.display      = 'block';
            iconCmp.style.display      = 'none';
            btn.title                  = 'Expandir mapa';
        }
    }

    function updateMapInfo(posicaoAt, syncedAt) {
        var el    = document.getElementById('map-position-info');
        var parts = [];
        if (posicaoAt) { parts.push('Posição: ' + posicaoAt); }
        if (syncedAt)  { parts.push('Sync: ' + syncedAt); }
        el.textContent = parts.join(' · ');
    }

    function _renderMap(lat, lng, label) {
        if (!_leafletMap) {
            _leafletMap = L.map('leaflet-map').setView([lat, lng], 15);

            var layerRua = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 21,
                maxNativeZoom: 19
            });

            var layerSatelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 21,
                maxNativeZoom: 19
            });

            layerRua.addTo(_leafletMap);
            L.control.layers({ 'Mapa': layerRua, 'Satélite': layerSatelite }, {}, { position: 'topright' }).addTo(_leafletMap);

            _leafletMarker = L.marker([lat, lng])
                .addTo(_leafletMap)
                .bindPopup(_buildMapPopup(label, _currentMapInfo))
                .openPopup();
        } else {
            _leafletMap.setView([lat, lng], 15);
            _leafletMarker.setLatLng([lat, lng])
                .setPopupContent(_buildMapPopup(label, _currentMapInfo))
                .openPopup();
        }

        setTimeout(function () { _leafletMap.invalidateSize(); }, 150);
    }

    function _syncAndRender(label) {
        var btn  = document.getElementById('map-btn-refresh');
        var icon = document.getElementById('map-refresh-icon');
        btn.disabled = true;
        icon.style.animation = 'spin 1s linear infinite';

        fetch(_posicaoBaseUrl + '/' + encodeURIComponent(_currentMapPlate), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) {
                document.getElementById('map-position-info').textContent = 'Sem localização disponível para este veículo.';
                return;
            }
            _currentMapInfo = Object.assign({}, _currentMapInfo, {
                tracker_state:  data.tracker_state,
                state_duration: data.state_duration,
                ignition:       data.ignition,
                speed:          data.speed,
                sem_sinal:      data.sem_sinal,
            });
            _renderMap(data.latitude, data.longitude, label);
            updateMapInfo(data.position_at, data.synced_at);
        })
        .catch(function () {
            document.getElementById('map-position-info').textContent = 'Erro ao buscar localização.';
        })
        .finally(function () {
            btn.disabled = false;
            icon.style.animation = '';
        });
    }

    function openMapModal(lat, lng, label, plate, posicaoAt, syncedAt, rowId, info) {
        _currentMapPlate = plate;
        _currentRowId    = rowId || null;
        _currentMapInfo  = info || {};
        _mapIsFullscreen = false;
        _applyMapSize(false);
        document.getElementById('map-overlay').style.display = 'flex';
        document.getElementById('map-vehicle-label').textContent = label;
        document.body.style.overflow = 'hidden';

        if (lat !== null && lng !== null) {
            // Posição já conhecida — exibe imediatamente
            updateMapInfo(posicaoAt, syncedAt);
            _renderMap(lat, lng, label);
        } else {
            // Sem posição — sincroniza com a API antes de exibir
            document.getElementById('map-position-info').textContent = 'Sincronizando localização...';
            _syncAndRender(label);
        }
    }

    function refreshMapPosition() {
        if (!_currentMapPlate) { return; }

        var btn   = document.getElementById('map-btn-refresh');
        var icon  = document.getElementById('map-refresh-icon');
        var label = document.getElementById('map-vehicle-label').textContent;
        btn.disabled = true;
        icon.style.animation = 'spin 1s linear infinite';

        fetch(_posicaoBaseUrl + '/' + encodeURIComponent(_currentMapPlate), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.ok) { return; }
            _currentMapInfo = Object.assign({}, _currentMapInfo, {
                tracker_state:  data.tracker_state,
                state_duration: data.state_duration,
                ignition:       data.ignition,
                speed:          data.speed,
                sem_sinal:      data.sem_sinal,
            });
            _renderMap(data.latitude, data.longitude, label);
            updateMapInfo(data.position_at, data.synced_at);
        })
        .finally(function () {
            btn.disabled = false;
            icon.style.animation = '';
        });
    }

    function toggleMapFullscreen() {
        _mapIsFullscreen = !_mapIsFullscreen;
        _applyMapSize(_mapIsFullscreen);
        if (_leafletMap) {
            setTimeout(function () { _leafletMap.invalidateSize(); }, 150);
        }
    }

    function closeMapModal() {
        document.getElementById('map-overlay').style.display  = 'none';
        document.getElementById('map-backdrop').style.display = 'none';
        document.body.style.overflow = '';
        _mapIsFullscreen  = false;
        _currentMapPlate  = null;
    }

    // ─── Mapa Geral (todas as frotas) ───────────────────────────────────────
    var _leafletMapGeral        = null;
    var _leafletLayerGeral      = null;
    var _leafletLayerCercas     = null;
    var _cercasDesenhadas       = false;
    var _mapaGeralIsFullscreen  = false;
    var _mapaGeralIndex         = []; // [{prefixo, placa, lat, lng, marker}]

    var _PALETA_CERCAS = [
        '#f59e0b','#3b82f6','#10b981','#ef4444','#8b5cf6',
        '#f97316','#06b6d4','#ec4899','#84cc16','#6366f1',
        '#14b8a6','#e11d48',
    ];

    var _cercasData = []; // [{nome, atividade, poligono}] — preenchido na 1ª carga

    /** Ray casting — retorna true se [lat, lng] está dentro do polígono [[lat,lng],...] */
    function _pontoDentro(lat, lng, poligono) {
        var dentro = false;
        var n = poligono.length;
        for (var i = 0, j = n - 1; i < n; j = i++) {
            var xi = poligono[i][0], yi = poligono[i][1];
            var xj = poligono[j][0], yj = poligono[j][1];
            if (((yi > lng) !== (yj > lng)) && (lat < (xj - xi) * (lng - yi) / (yj - yi) + xi)) {
                dentro = !dentro;
            }
        }
        return dentro;
    }

    /** Distância Haversine em km entre dois pontos */
    function _haversineKm(lat1, lng1, lat2, lng2) {
        var R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    /** Centroide do polígono */
    function _centroide(poligono) {
        var n = poligono.length, sumLat = 0, sumLng = 0;
        poligono.forEach(function (p) { sumLat += p[0]; sumLng += p[1]; });
        return [sumLat / n, sumLng / n];
    }

    /** Retorna {dentroDeAlguma, nome, atividade, distKm} para a cerca mais relevante */
    function _cercaParaVeiculo(lat, lng) {
        if (!_cercasData.length) { return null; }
        var melhor = null, melhorDist = Infinity;
        for (var i = 0; i < _cercasData.length; i++) {
            var c = _cercasData[i];
            if (_pontoDentro(lat, lng, c.poligono)) {
                return { dentro: true, nome: c.nome, atividade: c.atividade, distKm: 0 };
            }
            var centro = _centroide(c.poligono);
            var dist = _haversineKm(lat, lng, centro[0], centro[1]);
            if (dist < melhorDist) { melhorDist = dist; melhor = c; }
        }
        return melhor ? { dentro: false, nome: melhor.nome, atividade: melhor.atividade, distKm: melhorDist } : null;
    }

    window.filtrarMapaGeral = function () {
        var q        = (document.getElementById('mapa-geral-search').value || '').trim().toLowerCase();
        var dropdown = document.getElementById('mapa-geral-search-dropdown');

        if (! q) { dropdown.classList.add('hidden'); return; }

        var matches = _mapaGeralIndex.filter(function (v) {
            return v.prefixo.includes(q) || v.placa.includes(q);
        });

        if (matches.length === 0) {
            dropdown.innerHTML = '<div class="px-3 py-2.5 text-xs text-zinc-400 dark:text-zinc-500">Nenhum veículo encontrado</div>';
            dropdown.classList.remove('hidden');
            return;
        }

        dropdown.innerHTML = matches.slice(0, 12).map(function (v) {
            return '<div class="cursor-pointer px-3 py-2 text-sm text-zinc-800 hover:bg-slate-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60"'
                + ' onmousedown="event.preventDefault(); navegarMapaGeral(' + JSON.stringify(v.label) + ')">'
                + '<span class="font-semibold">' + _escHtml(v.label) + '</span>'
                + (v.placa_orig && v.placa_orig !== v.label ? '<span class="ml-2 text-xs text-zinc-400 dark:text-zinc-500">— ' + _escHtml(v.placa_orig) + '</span>' : '')
                + '</div>';
        }).join('');

        dropdown.classList.remove('hidden');
    };

    window.navegarMapaGeral = function (label) {
        var v = _mapaGeralIndex.find(function (v) { return v.label === label; });
        if (! v || ! _leafletMapGeral) { return; }

        document.getElementById('mapa-geral-search').value = label;
        document.getElementById('mapa-geral-search-dropdown').classList.add('hidden');

        _leafletMapGeral.flyTo([v.lat, v.lng], 17, { duration: 0.7 });
        setTimeout(function () { v.marker.openPopup(); }, 750);
    };

    window.navegarMapaGeralPrimeiro = function () {
        var q = (document.getElementById('mapa-geral-search').value || '').trim().toLowerCase();
        if (! q) { return; }
        var v = _mapaGeralIndex.find(function (v) { return v.prefixo.includes(q) || v.placa.includes(q); });
        if (v) { navegarMapaGeral(v.label); }
    };

    document.addEventListener('click', function (e) {
        var wrapper = document.getElementById('mapa-geral-search');
        var drop    = document.getElementById('mapa-geral-search-dropdown');
        if (wrapper && drop && ! wrapper.contains(e.target) && ! drop.contains(e.target)) {
            drop.classList.add('hidden');
        }
    });

    // ─── Modo Interativo ─────────────────────────────────────────────────────
    var _miAtivo         = false;
    var _miTimer         = null;
    var _miRefreshTimer  = null;
    var _miOrdem         = []; // índice ordenado por proximidade
    var _miIdx           = 0;
    var _MI_REFRESH_MS   = 5 * 60 * 1000; // 5 minutos

    function _haversineKm(lat1, lon1, lat2, lon2) {
        var R    = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a    = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
                 * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function _nearestNeighborSort(veiculos) {
        if (veiculos.length === 0) { return []; }
        var restantes = veiculos.slice();
        var resultado = [restantes.splice(0, 1)[0]];
        while (restantes.length > 0) {
            var atual      = resultado[resultado.length - 1];
            var menorDist  = Infinity;
            var menorIdx   = 0;
            for (var i = 0; i < restantes.length; i++) {
                var d = _haversineKm(atual.lat, atual.lng, restantes[i].lat, restantes[i].lng);
                if (d < menorDist) { menorDist = d; menorIdx = i; }
            }
            resultado.push(restantes.splice(menorIdx, 1)[0]);
        }
        return resultado;
    }

    function _miAvancar() {
        if (! _miAtivo || _miOrdem.length === 0) { return; }
        var v = _miOrdem[_miIdx];
        _miIdx = (_miIdx + 1) % _miOrdem.length;

        // Atualiza contador no botão
        document.getElementById('mi-label').textContent =
            'Interativo (' + (_miIdx) + '/' + _miOrdem.length + ')';

        var zoom = parseInt(document.getElementById('mi-zoom').value, 10) || 18;
        _leafletMapGeral.flyTo([v.lat, v.lng], zoom, { duration: 0.8 });
        setTimeout(function () {
            if (_miAtivo) { v.marker.openPopup(); }
        }, 850);

        var pausa = parseInt(document.getElementById('mi-intervalo').value, 10) || 15000;
        _miTimer = setTimeout(_miAvancar, pausa);
    }

    window.toggleModoInterativo = function () {
        _miAtivo = ! _miAtivo;
        var dot   = document.getElementById('mi-dot');
        var label = document.getElementById('mi-label');
        var btn   = document.getElementById('btn-modo-interativo');

        var select     = document.getElementById('mi-intervalo');
        var selectZoom = document.getElementById('mi-zoom');

        if (_miAtivo) {
            // Ordena por proximidade e inicia
            _miOrdem             = _nearestNeighborSort(_mapaGeralIndex.slice());
            _miIdx               = 0;
            select.disabled      = true;
            selectZoom.disabled  = true;
            dot.style.background  = '#16a34a';
            dot.style.animation   = 'spin 2s linear infinite';
            btn.style.borderColor = '#16a34a';
            btn.style.color       = '#16a34a';
            _miAvancar();
            // Recarrega posições do banco a cada 6 minutos sem parar o loop
            _miRefreshTimer = setInterval(function () {
                if (! _miAtivo) { return; }
                _fetchEPlotarMarcadores().then(function () {
                    // Re-ordena mantendo o mesmo ponto de referência
                    _miOrdem = _nearestNeighborSort(_mapaGeralIndex.slice());
                    _miIdx   = _miIdx % (_miOrdem.length || 1);
                });
            }, _MI_REFRESH_MS);
        } else {
            clearTimeout(_miTimer);
            clearInterval(_miRefreshTimer);
            _miTimer        = null;
            _miRefreshTimer = null;
            select.disabled     = false;
            selectZoom.disabled = false;
            dot.style.background  = '';
            dot.style.animation   = '';
            btn.style.borderColor = '';
            btn.style.color       = '';
            label.textContent     = 'Modo Interativo';
            if (_leafletMapGeral) { _leafletMapGeral.closePopup(); }
        }
    };
    var _mapaGeralUrl           = '{{ route("control-tower.mapa-geral") }}';
    var _sincronizarUrl         = '{{ route("control-tower.sincronizar-posicoes") }}';
    var _sincronizarStatusUrl   = '{{ route("control-tower.sincronizar-status-operacional") }}';
    var _csrfToken              = '{{ csrf_token() }}';
    var _BIGCORE_URL            = '{{ route("bigcore.veiculo") }}';

    // ─── Reporte Rápido ─────────────────────────────────────────────────────
    var _rrUrl      = null;
    var _rrPlaca    = null;
    var _rrPrefixo  = null;
    var _reportesUrl = '{{ route("reportes.index") }}';

    window.openReporteRapidoModal = function (equipamentoId, prefixo, placa, url) {
        _rrUrl     = url;
        _rrPlaca   = placa;
        _rrPrefixo = prefixo || placa;

        var busca = encodeURIComponent(prefixo || placa);
        document.getElementById('btn-pesquisar-reporte').href = _reportesUrl + '?busca=' + busca;

        document.getElementById('rr-subtitle').textContent = 'Veículo: ' + (prefixo ? prefixo + ' / ' + placa : placa);
        document.getElementById('rr-nome').value            = 'Reporte Interno ' + (prefixo || placa);
        document.getElementById('rr-status').value          = '';
        document.getElementById('rr-primeiro-contato').value = '';
        document.getElementById('rr-observacao').value      = '';
        document.getElementById('rr-documento').value       = '';
        document.getElementById('rr-tempo-parado').value    = '';
        document.getElementById('rr-segundo-contato').value = '';
        document.getElementById('rr-previsao').value        = '';

        var errEl = document.getElementById('rr-errors');
        errEl.classList.add('hidden');
        errEl.textContent = '';

        document.getElementById('reporte-rapido-backdrop').classList.remove('hidden');
        document.getElementById('reporte-rapido-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.getElementById('rr-nome').focus();
    };

    window.closeReporteRapidoModal = function () {
        document.getElementById('reporte-rapido-backdrop').classList.add('hidden');
        document.getElementById('reporte-rapido-modal').classList.add('hidden');
        document.body.style.overflow = '';
    };

    window.buscarElogRapido = function () {
        if (! _rrPlaca) { return; }
        var btn     = document.getElementById('btn-elog-rapido');
        var svgBusy = '<svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';
        btn.disabled  = true;
        btn.innerHTML = svgBusy + ' Buscando…';

        fetch(_BIGCORE_URL + '?placa=' + encodeURIComponent(_rrPlaca), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            if (! res.ok) {
                var errEl = document.getElementById('rr-errors');
                errEl.textContent = res.data.erro || 'Veículo não localizado no Elog.';
                errEl.classList.remove('hidden');
                return;
            }
            var d = res.data;
            if (d.tempo_parado)       { document.getElementById('rr-tempo-parado').value    = d.tempo_parado; }
            if (d.status_operacional) { document.getElementById('rr-status').value          = d.status_operacional; }
            if (d.documento)          { document.getElementById('rr-documento').value       = d.documento; }
            if (d.observacao)         { document.getElementById('rr-observacao').value      = d.observacao; }
        })
        .catch(function () {
            var errEl = document.getElementById('rr-errors');
            errEl.textContent = 'Não foi possível conectar ao Elog. Tente novamente.';
            errEl.classList.remove('hidden');
        })
        .finally(function () {
            var svgSearch = '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/></svg>';
            btn.disabled  = false;
            btn.innerHTML = svgSearch + ' Pesquisar no Elog';
        });
    };

    window.submitReporteRapido = function (salvarComo) {
        if (! _rrUrl) { return; }

        var errEl = document.getElementById('rr-errors');
        errEl.classList.add('hidden');
        errEl.textContent = '';

        var body = JSON.stringify({
            nome:               document.getElementById('rr-nome').value.trim(),
            salvar_como:        salvarComo,
            status_operacional: document.getElementById('rr-status').value,
            primeiro_contato:   document.getElementById('rr-primeiro-contato').value.trim(),
            observacao:         document.getElementById('rr-observacao').value.trim(),
            documento:          document.getElementById('rr-documento').value.trim() || null,
            tempo_parado:       document.getElementById('rr-tempo-parado').value.trim() || null,
            segundo_contato:    document.getElementById('rr-segundo-contato').value.trim() || null,
            data_hora_previsao: document.getElementById('rr-previsao').value || null,
        });

        fetch(_rrUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': _csrfToken,
            },
            body: body,
        })
        .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
        .then(function (res) {
            if (! res.data.ok) {
                var msgs = res.data.errors
                    ? Object.values(res.data.errors).flat().join(' ')
                    : (res.data.message || 'Erro ao salvar reporte.');
                errEl.textContent = msgs;
                errEl.classList.remove('hidden');
                return;
            }
            closeReporteRapidoModal();
            window.location.reload();
        })
        .catch(function () {
            errEl.textContent = 'Erro de conexão. Tente novamente.';
            errEl.classList.remove('hidden');
        });
    };

    function _escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function _applyMapaGeralSize(fullscreen) {
        var overlay  = document.getElementById('mapa-geral-overlay');
        var mapDiv   = document.getElementById('leaflet-map-geral');
        var backdrop = document.getElementById('mapa-geral-backdrop');
        var iconExp  = document.getElementById('mapa-geral-icon-expand');
        var iconCmp  = document.getElementById('mapa-geral-icon-compress');
        var btn      = document.getElementById('mapa-geral-btn-fs');

        if (fullscreen) {
            overlay.style.top          = '0';
            overlay.style.left         = '0';
            overlay.style.right        = '0';
            overlay.style.bottom       = '0';
            overlay.style.width        = '';
            overlay.style.maxWidth     = '';
            overlay.style.transform    = 'none';
            overlay.style.borderRadius = '0';
            overlay.style.boxShadow    = 'none';
            mapDiv.style.height        = 'calc(100vh - 57px)';
            backdrop.style.display     = 'none';
            iconExp.style.display      = 'none';
            iconCmp.style.display      = 'block';
            btn.title                  = 'Sair da tela cheia';
        } else {
            overlay.style.top          = '50%';
            overlay.style.left         = '50%';
            overlay.style.right        = '';
            overlay.style.bottom       = '';
            overlay.style.width        = '860px';
            overlay.style.maxWidth     = 'calc(100vw - 2rem)';
            overlay.style.transform    = 'translate(-50%, -50%)';
            overlay.style.borderRadius = '1rem';
            overlay.style.boxShadow    = '0 25px 50px -12px rgba(0,0,0,.35)';
            mapDiv.style.height        = '520px';
            backdrop.style.display     = 'block';
            iconExp.style.display      = 'block';
            iconCmp.style.display      = 'none';
            btn.title                  = 'Expandir mapa';
        }

        if (_leafletMapGeral) {
            setTimeout(function () { _leafletMapGeral.invalidateSize(); }, 150);
        }
    }

    function _fetchEPlotarMarcadores() {
        var info = document.getElementById('mapa-geral-info');
        info.textContent = 'Atualizando mapa…';

        return fetch(_mapaGeralUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var veiculos = data.veiculos || [];

                // Inicializa o mapa na primeira chamada
                if (!_leafletMapGeral) {
                    _leafletMapGeral = L.map('leaflet-map-geral').setView([-22.409748797155576, -41.86951960989981], 12);

                    var layerRua = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 21,
                        maxNativeZoom: 19
                    });
                    var layerSatelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Tiles &copy; Esri',
                        maxZoom: 21,
                        maxNativeZoom: 19
                    });
                    layerRua.addTo(_leafletMapGeral);
                    _leafletLayerCercas = L.layerGroup().addTo(_leafletMapGeral);
                    _leafletLayerGeral  = L.layerGroup().addTo(_leafletMapGeral);
                    L.control.layers(
                        { 'Mapa': layerRua, 'Satélite': layerSatelite },
                        { 'Cercas': _leafletLayerCercas },
                        { position: 'topright' }
                    ).addTo(_leafletMapGeral);
                }

                // Guarda cercas para uso no popup dos veículos
                if (!_cercasDesenhadas && data.cercas && data.cercas.length) {
                    _cercasData = data.cercas;
                }

                // Desenha cercas apenas uma vez (não mudam em tempo real)
                if (!_cercasDesenhadas && data.cercas && data.cercas.length) {
                    _cercasDesenhadas = true;
                    data.cercas.forEach(function (c, i) {
                        var cor = _PALETA_CERCAS[i % _PALETA_CERCAS.length];
                        L.polygon(c.poligono, {
                            color: cor,
                            weight: 2,
                            fillColor: cor,
                            fillOpacity: 0.12,
                        })
                        .bindTooltip(
                            '<strong>' + c.nome + '</strong>' + (c.atividade ? '<br>' + c.atividade : ''),
                            { sticky: true, direction: 'top' }
                        )
                        .addTo(_leafletLayerCercas);
                    });
                }

                // Limpa marcadores e índice anteriores
                _leafletLayerGeral.clearLayers();
                _mapaGeralIndex = [];

                var bounds = [];

                veiculos.forEach(function (v) {
                    // ── Cor do ícone ─────────────────────────────────────────
                    var bgColor = v.sem_sinal ? '#52525b'
                                : (v.tracker_state === 'Em Movimento' ? '#16a34a'
                                : (v.tracker_state === 'Parado'       ? '#b91c1c'
                                : '#3f3f46'));

                    var icon = L.divIcon({
                        html: '<div style="width:60px;display:flex;justify-content:center;align-items:center;">'
                              + '<span style="background:' + bgColor + ';color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,.4)">'
                              + _escHtml(v.prefixo || v.placa) + '</span></div>',
                        className: '',
                        iconSize: [60, 22],
                        iconAnchor: [30, 11]
                    });

                    // ── Localidade: cerca ou endereço ────────────────────────
                    var _PROX_KM  = 0.3;
                    var cercaInfo = _cercaParaVeiculo(v.lat, v.lng);
                    var usarCerca = cercaInfo && (cercaInfo.dentro || cercaInfo.distKm <= _PROX_KM);

                    // ── Popup (tabela) ────────────────────────────────────────
                    var _row = function (lbl, val) {
                        return '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">' + lbl + '</td>'
                             + '<td style="font-weight:600">' + val + '</td></tr>';
                    };
                    var _hr = '<tr><td colspan="2" style="padding:3px 0"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0"></td></tr>';

                    var popup = '<div style="min-width:500px;font-size:22px;line-height:1.6">'
                        + '<p style="font-weight:600;font-size:24px;margin:0 0 12px">'
                        + _escHtml(v.prefixo) + ' <span style="font-weight:400;color:#71717a">' + _escHtml(v.placa) + '</span></p>'
                        + '<table style="border-collapse:collapse;width:100%">';

                    // Status Elog
                    if (v.status_elog) {
                        var elogVal = _escHtml(v.status_elog);
                        if (v.tempo_elog) { elogVal += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.tempo_elog) + '</span>'; }
                        popup += _row('Status Elog', elogVal);
                    }
                    if (v.atendimento) {
                        var atendVal = _escHtml(v.atendimento);
                        if (v.tempo_atendimento) { atendVal += ' <span style="color:#6b7280;font-weight:400">(' + _escHtml(v.tempo_atendimento) + ' total)</span>'; }
                        popup += _row('Atendimento', atendVal);
                    }
                    if (v.observacao) {
                        var obs = v.observacao.length > 100 ? v.observacao.substring(0, 100) + '…' : v.observacao;
                        popup += _row('Observação', '<span title="' + v.observacao.replace(/"/g, '&quot;') + '">' + _escHtml(obs) + '</span>');
                    }

                    popup += _hr;

                    // Rastreador
                    if (v.sem_sinal) {
                        var semSinalVal = '⬛ <span style="font-weight:600">Desconhecido</span>';
                        if (v.sem_sinal_duration) { semSinalVal += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.sem_sinal_duration) + '</span>'; }
                        popup += _row('Rastreador', semSinalVal);
                    } else {
                        var trackerIcon  = v.tracker_state === 'Em Movimento' ? '🟢' : (v.tracker_state === 'Parado' ? '🔴' : '⚫');
                        var trackerLabel = trackerIcon + ' ' + (v.tracker_state || 'Sem Sinal');
                        if (v.state_duration) { trackerLabel += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.state_duration) + '</span>'; }
                        popup += _row('Rastreador', trackerLabel);
                        popup += _row('Motor', v.ignition ? '🔵 <span style="font-weight:600">Ligado</span>' : '⚪ <span style="font-weight:600">Desligado</span>');
                        popup += _row('Velocidade', (v.speed || 0) + ' km/h');
                    }

                    // Cerca
                    if (usarCerca) {
                        popup += _hr;
                        popup += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Cerca</td>'
                               + '<td style="font-weight:600;white-space:nowrap">' + _escHtml(cercaInfo.nome)
                               + '</td></tr>';
                        if (v.tempo_cerca_duracao) {
                            var cercaBg = v.cerca_bar_color || '#6b7280';
                            popup += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Tempo Cerca</td>'
                                   + '<td><div style="background:' + cercaBg + ';padding:4px 16px;color:#fff;font-size:21px;font-weight:600;display:inline-block">'
                                   + _escHtml(v.tempo_cerca_duracao) + '</div></td></tr>';
                        }
                    } else {
                        popup += _hr;
                        popup += '<tr><td colspan="2"><p style="margin:0">📍 <span id="loc-' + _escHtml(v.placa) + '" style="color:#9ca3af">Carregando endereço…</span></p></td></tr>';
                    }

                    // Condutor
                    if (v.motorista) {
                        popup += _hr;
                        popup += _row('Condutor', _escHtml(v.motorista));
                    }

                    // Rodapé com posição
                    if (!v.sem_sinal && v.position_at) {
                        popup += '<tr><td colspan="2" style="color:#9ca3af;font-size:11px;padding-top:5px">Posição: ' + _escHtml(v.position_at) + '</td></tr>';
                    }

                    popup += '</table></div>';

                    var marker = L.marker([v.lat, v.lng], { icon: icon })
                        .bindPopup(popup, { maxWidth: 600 })
                        .addTo(_leafletLayerGeral);

                    // Nominatim apenas para veículos fora do raio de qualquer cerca
                    if (!usarCerca) {
                        (function (placa, lat, lng) {
                            marker.on('popupopen', function () {
                                var el = document.getElementById('loc-' + placa);
                                if (!el || el.dataset.loaded) { return; }
                                el.dataset.loaded = '1';
                                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&accept-language=pt-BR', {
                                    headers: { 'Accept': 'application/json' }
                                })
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    if (!el) { return; }
                                    var road = d.address && (d.address.road || d.address.suburb || d.address.neighbourhood || d.address.city_district || d.address.town || d.address.city);
                                    el.textContent = road || d.display_name || 'Endereço não encontrado';
                                    el.style.color = '';
                                })
                                .catch(function () {
                                    if (el) { el.textContent = 'Endereço indisponível'; }
                                });
                            });
                        }(v.placa, v.lat, v.lng));
                    }

                    _mapaGeralIndex.push({
                        prefixo: (v.prefixo || '').toLowerCase(),
                        placa:   (v.placa   || '').toLowerCase(),
                        label:   v.prefixo || v.placa,
                        placa_orig: v.placa || '',
                        lat: v.lat, lng: v.lng,
                        marker: marker,
                    });

                    bounds.push([v.lat, v.lng]);
                });

                if (bounds.length > 0) {
                    _leafletMapGeral.fitBounds(bounds, { padding: [40, 40] });
                }

                info.textContent = veiculos.length + ' veículo(s) com posição registrada';
                setTimeout(function () { _leafletMapGeral.invalidateSize(); }, 200);

                // Faixa de alterações recentes (últimos 15 min)
                var recentes = veiculos
                    .filter(function (v) { return v.state_since_mins !== null && v.state_since_mins <= 60; })
                    .sort(function (a, b) { return a.state_since_mins - b.state_since_mins; });

                var faixaEl = document.getElementById('mapa-geral-recentes');
                if (recentes.length > 0) {
                    var pills = '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#a1a1aa;margin-right:6px;vertical-align:middle">⚡ Recentes Vfleets:</span>';
                    recentes.forEach(function (v) {
                        var emoji = v.tracker_state === 'Em Movimento' ? '🟢' : (v.tracker_state === 'Parado' ? '🔴' : '⚫');
                        var dur   = v.state_since_mins < 60
                            ? v.state_since_mins + 'm'
                            : Math.floor(v.state_since_mins / 60) + 'h ' + (v.state_since_mins % 60) + 'm';
                        var bg    = v.tracker_state === 'Em Movimento' ? 'background:#f0fdf4;color:#15803d;box-shadow:inset 0 0 0 1px #bbf7d0'
                                  : (v.tracker_state === 'Parado'      ? 'background:#fff1f2;color:#be123c;box-shadow:inset 0 0 0 1px #fecdd3'
                                  :                                       'background:#f4f4f5;color:#52525b;box-shadow:inset 0 0 0 1px #e4e4e7');
                        pills += '<span style="display:inline-flex;align-items:center;gap:5px;border-radius:9999px;padding:3px 10px;font-size:11px;font-weight:500;margin-right:6px;vertical-align:middle;cursor:pointer;' + bg + '"'
                            + ' onclick="navegarMapaGeral(' + JSON.stringify(v.prefixo || v.placa) + ')"'
                            + ' title="' + _escHtml(v.tracker_state) + ' — há ' + dur + '">'
                            + emoji + ' <strong>' + _escHtml(v.prefixo || v.placa) + '</strong>'
                            + ' <span style="opacity:.6">há ' + dur + '</span>'
                            + '</span>';
                    });
                    faixaEl.innerHTML   = pills;
                    faixaEl.style.display = '';
                } else {
                    faixaEl.style.display = 'none';
                    faixaEl.innerHTML     = '';
                }

                // Faixa de recentes Elog (última hora)
                var recentesElog = data.recentes_elog || [];
                var faixaElog    = document.getElementById('mapa-geral-recentes-elog');
                if (recentesElog.length > 0) {
                    var pillsElog = '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#a1a1aa;margin-right:6px;vertical-align:middle">📋 Recentes Elog:</span>';
                    recentesElog.forEach(function (v) {
                        var mins = v.entrada_em || 0;
                        var dur  = mins < 60
                            ? mins + 'm'
                            : Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm';
                        var bg   = v.cor ? v.cor : '#f4f4f5';
                        var fg   = v.cor ? '#fff' : '#18181b';
                        pillsElog += '<span style="display:inline-flex;align-items:center;gap:4px;margin-right:6px;padding:2px 8px;border-radius:4px;background:' + bg + ';color:' + fg + ';font-size:11px;cursor:default;vertical-align:middle"'
                            + ' title="' + _escHtml(v.status_operacional) + (v.documento ? ' — Doc: ' + _escHtml(v.documento) : '') + ' — há ' + dur + '">'
                            + '<strong>' + _escHtml(v.prefixo || v.placa) + '</strong>'
                            + ' <span style="opacity:.85">' + _escHtml(v.status_operacional) + '</span>'
                            + ' <span style="opacity:.7">há ' + dur + '</span>'
                            + '</span>';
                    });
                    faixaElog.innerHTML     = pillsElog;
                    faixaElog.style.display = '';
                } else {
                    faixaElog.style.display = 'none';
                    faixaElog.innerHTML     = '';
                }
            })
            .catch(function () {
                document.getElementById('mapa-geral-info').textContent = 'Erro ao carregar posições.';
            });
    }

    var _sincStatusEmAndamento = false;

    function sincronizarERecarregar() {
        var btn  = document.getElementById('mapa-geral-btn-refresh');
        var icon = document.getElementById('mapa-geral-refresh-icon');
        var info = document.getElementById('mapa-geral-info');
        btn.disabled         = true;
        icon.style.animation = 'spin 1s linear infinite';
        info.textContent     = 'Sincronizando com a API…';

        // Sincroniza status operacional em paralelo (fire-and-forget)
        if (!_sincStatusEmAndamento) {
            _sincStatusEmAndamento = true;
            fetch(_sincronizarStatusUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': _csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .catch(function () {})
            .finally(function () { _sincStatusEmAndamento = false; });
        }

        fetch(_sincronizarUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': _csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then(function (response) {
            if (response.status === 429) {
                info.textContent = 'Limite da API atingido — exibindo última sincronização salva.';
                return _fetchEPlotarMarcadores();
            }
            return response.json().then(function (data) {
                if (!data.ok) {
                    info.textContent = 'Limite da API atingido — exibindo última sincronização salva.';
                    return _fetchEPlotarMarcadores();
                }
                info.textContent = 'Sincronizados ' + data.total + ' veículo(s). Atualizando mapa…';
                return _fetchEPlotarMarcadores();
            });
        })
        .catch(function () {
            info.textContent = 'Erro ao sincronizar — exibindo última sincronização salva.';
            _fetchEPlotarMarcadores();
        })
        .finally(function () {
            btn.disabled         = false;
            icon.style.animation = '';
        });
    }

    function openMapaGeral() {
        _mapaGeralIsFullscreen = true;
        _applyMapaGeralSize(true);
        document.getElementById('mapa-geral-overlay').style.display = 'flex';
        document.getElementById('mapa-geral-search').value = '';
        document.getElementById('mapa-geral-search-dropdown').classList.add('hidden');
        document.body.style.overflow = 'hidden';
        sincronizarERecarregar();
    }

    function closeMapaGeral() {
        if (_miAtivo) { toggleModoInterativo(); } // desliga modo interativo e refresh ao fechar
        document.getElementById('mapa-geral-overlay').style.display  = 'none';
        document.getElementById('mapa-geral-backdrop').style.display = 'none';
        document.body.style.overflow = '';
        _mapaGeralIsFullscreen = false;
    }

    function toggleMapaGeralFullscreen() {
        _mapaGeralIsFullscreen = !_mapaGeralIsFullscreen;
        _applyMapaGeralSize(_mapaGeralIsFullscreen);
    }
    </script>

</x-layouts.app>
