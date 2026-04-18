<x-layouts.app title="Torre de Controle">

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
        $currentDivisao = request('divisao_id');
        $currentModelo  = request('modelo_id');
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

            @if($currentDivisao || $currentModelo)
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
        </form>

        {{-- Column toggles --}}
        <div class="ml-auto flex items-center gap-1.5">
            <span class="text-xs text-zinc-400 dark:text-zinc-600">Colunas:</span>
            @foreach([
                ['col' => 'prefixo',   'label' => 'Prefixo'],
                ['col' => 'divisao',   'label' => 'Divisão'],
                ['col' => 'status-op', 'label' => 'Status Op.'],
                ['col' => 'documento', 'label' => 'Documento'],
                ['col' => 'obs',       'label' => 'Observação'],
            ] as $tog)
                <button type="button"
                        data-toggle-col="{{ $tog['col'] }}"
                        onclick="toggleColumn('{{ $tog['col'] }}')"
                        class="col-toggle rounded-md border px-2 py-1 text-xs font-medium transition-colors
                               border-zinc-200 bg-white text-zinc-600
                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    {{ $tog['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ─── Flash / errors ─────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div id="flash-success"
             class="mt-3 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700
                    dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-400">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mt-3 flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700
                    dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-400">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            {{ $errors->first() }}
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
                @if($currentDivisao || $currentModelo)
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
            {{-- Scrollable area --}}
            <div class="overflow-auto" style="max-height: calc(100vh - 260px)">
                <table id="ct-table" class="w-full text-sm">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Placa
                            </th>
                            <th data-col="prefixo" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Prefixo
                            </th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Modelo / Implemento
                            </th>
                            <th data-col="divisao" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Divisão
                            </th>
                            <th data-col="status-op" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Status Op.
                            </th>
                            <th data-col="documento" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Documento
                            </th>
                            <th data-col="obs" class="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Observação
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

                                $searchText = implode(' ', array_filter([
                                    $equipamento->placa,
                                    $equipamento->prefixo,
                                    $equipamento->modelo?->nome,
                                    $equipamento->divisao?->nome,
                                    $equipamento->status_operacional,
                                    $equipamento->documento_demanda,
                                    $impNome,
                                ]));
                            @endphp

                            {{-- ─── Data row ──────────────────────────────── --}}
                            <tr id="row-{{ $equipamento->id }}"
                                data-search="{{ strtolower($searchText) }}"
                                class="ct-row transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">

                                <td class="px-3 py-2 whitespace-nowrap">
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $equipamento->placa }}</p>
                                    @if($equipamento->id_elog)
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-600">{{ $equipamento->id_elog }}</p>
                                    @endif
                                </td>

                                <td data-col="prefixo" class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $equipamento->prefixo ?? '—' }}
                                </td>

                                <td class="px-3 py-2">
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
                                                onclick="openImplementoModal({{ $equipamento->id }}, '{{ addslashes($equipamento->placa) }}', {{ $equipamento->implemento_id ?? 'null' }}, '{{ addslashes($equipamento->implemento_nome_override ?? '') }}')"
                                                title="Vincular / alterar implemento"
                                                class="rounded p-0.5 text-zinc-300 transition-colors hover:text-zinc-600
                                                       dark:text-zinc-700 dark:hover:text-zinc-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 1 1.242 7.244"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <td data-col="divisao" class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $equipamento->divisao?->nome ?? '—' }}
                                </td>

                                <td data-col="status-op" class="px-3 py-2 whitespace-nowrap">
                                    @if($equipamento->status_operacional)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium text-blue-700
                                                     dark:bg-blue-950/40 dark:text-blue-400">
                                            {{ $equipamento->status_operacional }}
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>

                                <td data-col="documento" class="px-3 py-2 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $equipamento->documento_demanda ?? '—' }}
                                </td>

                                <td data-col="obs" class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                    <span class="line-clamp-1 max-w-[180px] block">{{ $equipamento->observacao_operacional ?? '—' }}</span>
                                </td>

                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <button type="button"
                                            onclick="toggleEditRow({{ $equipamento->id }})"
                                            title="Editar dados operacionais"
                                            class="inline-flex items-center gap-1 rounded border px-2 py-1 text-[11px] font-medium transition-colors
                                                   border-zinc-200 bg-white text-zinc-500 hover:bg-zinc-50 hover:text-zinc-800
                                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-500 dark:hover:bg-zinc-700 dark:hover:text-zinc-200">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                        </svg>
                                        Editar
                                    </button>
                                </td>
                            </tr>

                            {{-- ─── Edit row ───────────────────────────────── --}}
                            <tr id="edit-row-{{ $equipamento->id }}" class="hidden border-t-0 bg-slate-50/80 dark:bg-zinc-800/20">
                                <td colspan="8" class="px-3 pb-3 pt-2">
                                    <form method="POST"
                                          action="{{ route('equipamentos.operacional', $equipamento) }}"
                                          class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        @method('PATCH')

                                        <div class="flex min-w-[150px] flex-1 flex-col gap-1">
                                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Status Operacional</label>
                                            <input type="text" name="status_operacional"
                                                   value="{{ old('status_operacional', $equipamento->status_operacional) }}"
                                                   placeholder="Ex.: Em Trânsito"
                                                   class="rounded-lg border px-2.5 py-1.5 text-sm outline-none transition-all
                                                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-300
                                                          focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:placeholder-zinc-600
                                                          dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                                        </div>

                                        <div class="flex min-w-[160px] flex-1 flex-col gap-1">
                                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Documento de Demanda</label>
                                            <input type="text" name="documento_demanda"
                                                   value="{{ old('documento_demanda', $equipamento->documento_demanda) }}"
                                                   placeholder="Nº do documento"
                                                   class="rounded-lg border px-2.5 py-1.5 text-sm outline-none transition-all
                                                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-300
                                                          focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:placeholder-zinc-600
                                                          dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                                        </div>

                                        <div class="flex min-w-[200px] flex-[2] flex-col gap-1">
                                            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Observação</label>
                                            <input type="text" name="observacao_operacional"
                                                   value="{{ old('observacao_operacional', $equipamento->observacao_operacional) }}"
                                                   placeholder="Observação operacional"
                                                   class="rounded-lg border px-2.5 py-1.5 text-sm outline-none transition-all
                                                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-300
                                                          focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:placeholder-zinc-600
                                                          dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <button type="submit"
                                                    class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors
                                                           bg-zinc-900 text-white hover:bg-zinc-700
                                                           dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                                                Salvar
                                            </button>
                                            <button type="button" onclick="toggleEditRow({{ $equipamento->id }})"
                                                    class="rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                                                           border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50
                                                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
                                                Cancelar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach

                        {{-- Empty search state --}}
                        <tr id="no-results" class="hidden">
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-zinc-400 dark:text-zinc-600">
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
            @if($currentDivisao || $currentModelo) · filtros ativos @endif
        </p>
    @endif

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
        var ALL_COLS  = ['prefixo', 'divisao', 'status-op', 'documento', 'obs'];

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
                var btn = document.querySelector('[data-toggle-col="' + col + '"]');
                if (btn) {
                    if (isHidden) {
                        btn.classList.remove('border-zinc-200', 'bg-white', 'text-zinc-600', 'dark:border-zinc-700', 'dark:bg-zinc-900', 'dark:text-zinc-400');
                        btn.classList.add('border-zinc-400', 'bg-zinc-900', 'text-white', 'dark:border-zinc-500', 'dark:bg-zinc-700', 'dark:text-zinc-200');
                    } else {
                        btn.classList.add('border-zinc-200', 'bg-white', 'text-zinc-600', 'dark:border-zinc-700', 'dark:bg-zinc-900', 'dark:text-zinc-400');
                        btn.classList.remove('border-zinc-400', 'bg-zinc-900', 'text-white', 'dark:border-zinc-500', 'dark:bg-zinc-700', 'dark:text-zinc-200');
                    }
                }
            });
        }

        window.toggleColumn = function (col) {
            var hidden = hiddenCols();
            var idx = hidden.indexOf(col);
            if (idx === -1) { hidden.push(col); } else { hidden.splice(idx, 1); }
            localStorage.setItem(STORE_KEY, JSON.stringify(hidden));
            applyColVisibility();
        };

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
                // Also hide the paired edit row
                var editRow = document.getElementById('edit-row-' + row.id.replace('row-', ''));
                if (editRow) { editRow.style.display = match ? '' : 'none'; }
                if (match) { visible++; }
            });
            noResults.style.display = visible === 0 ? '' : 'none';
            updateCounter(visible);
        });

        // Auto-dismiss flash
        var flash = document.getElementById('flash-success');
        if (flash) { setTimeout(function () { flash.style.display = 'none'; }, 4000); }
    })();

    // ─── Inline edit rows ────────────────────────────────────────────────────
    function toggleEditRow(id) {
        var editRow = document.getElementById('edit-row-' + id);
        var isHidden = editRow.classList.contains('hidden');
        document.querySelectorAll('[id^="edit-row-"]').forEach(function (r) { r.classList.add('hidden'); });
        if (isHidden) {
            editRow.classList.remove('hidden');
            var first = editRow.querySelector('input');
            if (first) { first.focus(); }
        }
    }

    // ─── Implemento modal ────────────────────────────────────────────────────
    var IMPLEMENTOS      = @json($implementos->values());
    var PATCH_URL_TMPL   = '{{ route('control-tower.implemento', ['equipamento' => '__ID__']) }}';
    var _currentMotoId   = null;
    var _selectedImpId   = null;

    function openImplementoModal(motoId, motoPlaca, currentImpId, currentNomeOverride) {
        _currentMotoId = motoId;
        _selectedImpId = currentImpId;

        document.getElementById('modal-subtitle').textContent = motoPlaca;
        document.getElementById('implemento-form').action = PATCH_URL_TMPL.replace('__ID__', motoId);
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
        if (e.key === 'Escape') { closeImplementoModal(); }
    });
    </script>

</x-layouts.app>
