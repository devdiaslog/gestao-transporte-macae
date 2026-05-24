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
    <div class="mb-2 mt-4 flex items-center gap-4 px-1">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">HJ:</span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/40">
                <svg class="h-2.5 w-2.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </span>
            Menos de 12h
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-950/30">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 dark:bg-amber-500"></span>
            </span>
            Entre 12h e 24h
        </span>
        <span class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-50 dark:bg-rose-950/30">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-400 dark:bg-rose-500"></span>
            </span>
            Mais de 24h ou sem reporte
        </span>
    </div>

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
                                                onclick="openMapModal({{ $hasLocation ? $posicao->latitude : 'null' }}, {{ $hasLocation ? $posicao->longitude : 'null' }}, '{{ addslashes($mapLabel) }}', '{{ addslashes($equipamento->id_rastreador) }}', '{{ $posicaoAt ?? '' }}', '{{ $syncedAt ?? '' }}', {{ $equipamento->id }})"
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
        <div id="leaflet-map" style="height:400px;"></div>
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

    {{-- ─── Scripts ─────────────────────────────────────────────────────────── --}}
    <script>
    (function () {
        // ─── Column visibility (localStorage) ──────────────────────────────
        var STORE_KEY = 'ct_hidden_cols';
        var ALL_COLS  = ['placa', 'tempo', 'status-op', 'condutor', 'documento', 'origem', 'destino', 'obs', 'modelo', 'divisao'];

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
    var _posicaoBaseUrl  = '{{ url("torre-de-controle/posicao") }}';

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
            overlay.style.width        = '680px';
            overlay.style.maxWidth     = 'calc(100vw - 2rem)';
            overlay.style.transform    = 'translate(-50%, -50%)';
            overlay.style.borderRadius = '1rem';
            overlay.style.boxShadow    = '0 25px 50px -12px rgba(0,0,0,.35)';
            mapDiv.style.height        = '400px';
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
                maxZoom: 19
            });

            var layerSatelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles &copy; Esri &mdash; Source: Esri, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
                maxZoom: 19
            });

            layerRua.addTo(_leafletMap);
            L.control.layers({ 'Mapa': layerRua, 'Satélite': layerSatelite }, {}, { position: 'topright' }).addTo(_leafletMap);

            _leafletMarker = L.marker([lat, lng])
                .addTo(_leafletMap)
                .bindPopup('<strong>' + label + '</strong>')
                .openPopup();
        } else {
            _leafletMap.setView([lat, lng], 15);
            _leafletMarker.setLatLng([lat, lng])
                .setPopupContent('<strong>' + label + '</strong>')
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

    function openMapModal(lat, lng, label, plate, posicaoAt, syncedAt, rowId) {
        _currentMapPlate = plate;
        _currentRowId    = rowId || null;
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
    </script>

</x-layouts.app>
