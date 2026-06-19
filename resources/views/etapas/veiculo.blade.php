<x-layouts.app title="Etapas — {{ $equipamento->prefixo ?? $equipamento->placa }}">

    {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('control-tower.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                  border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50
                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Torre de Controle
        </a>
    </div>

    {{-- ─── Vehicle card ────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center gap-6 rounded-xl border px-6 py-4
                border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Prefixo</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $equipamento->prefixo ?? '—' }}
            </p>
        </div>
        <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Placa</p>
            <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->placa }}</p>
        </div>
        @if($equipamento->modelo)
            <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Modelo</p>
                <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->modelo->nome }}</p>
            </div>
        @endif
        @if($equipamento->divisao)
            <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Divisão</p>
                <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->divisao->nome }}</p>
            </div>
        @endif
        <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Total de etapas</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $etapas->total() }}</p>
        </div>
        <div class="ml-auto">
            @if (! $ultimaEtapa || $ultimaEtapa->data_hora_fim)
                <button type="button" onclick="openCreateModal()"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5
                               text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                               bg-zinc-900 text-white hover:bg-zinc-700
                               dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Nova Etapa
                </button>
            @endif
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    @php
        $currentTipo       = request('tipo_etapa_id');
        $currentStatus     = request('status', '');
        $currentDataInicio = request('data_inicio');
        $currentDataFim    = request('data_fim');
        $currentDocumento  = request('documento');
        $hasFilters        = request()->hasAny(['tipo_etapa_id', 'status', 'data_inicio', 'data_fim', 'documento']);
    @endphp

    <form method="GET" action="{{ route('etapas.veiculo', $equipamento) }}"
          class="mt-4 flex flex-wrap items-end gap-3">

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Tipo</label>
            <select name="tipo_etapa_id"
                    class="rounded-lg border px-3 py-1.5 text-sm
                           border-slate-200 bg-white text-zinc-700
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                           focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
                <option value="">Todos os tipos</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo->id }}" @selected($currentTipo == $tipo->id)>{{ titulo($tipo->nome) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Status</label>
            <select name="status"
                    class="rounded-lg border px-3 py-1.5 text-sm
                           border-slate-200 bg-white text-zinc-700
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                           focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
                <option value="">Todos</option>
                <option value="aberto"     @selected($currentStatus === 'aberto')>Em Aberto</option>
                <option value="finalizado" @selected($currentStatus === 'finalizado')>Finalizado</option>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Período</label>
            <div class="flex items-center gap-1.5">
                <input type="date" name="data_inicio" value="{{ $currentDataInicio }}"
                       class="rounded-lg border px-3 py-1.5 text-sm
                              border-slate-200 bg-white text-zinc-700
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                              focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
                <span class="text-xs text-zinc-400">até</span>
                <input type="date" name="data_fim" value="{{ $currentDataFim }}"
                       class="rounded-lg border px-3 py-1.5 text-sm
                              border-slate-200 bg-white text-zinc-700
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                              focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
            </div>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Documento</label>
            <input type="text" name="documento" value="{{ $currentDocumento }}" placeholder="Ex.: 509533234…"
                   class="rounded-lg border px-3 py-1.5 text-sm w-40
                          border-slate-200 bg-white text-zinc-700 placeholder-zinc-400
                          dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:placeholder-zinc-600
                          focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
        </div>

        <button type="submit"
                class="rounded-lg border px-4 py-1.5 text-sm font-medium transition-colors
                       border-zinc-900 bg-zinc-900 text-white hover:bg-zinc-700
                       dark:border-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Filtrar
        </button>

        @if($hasFilters)
            <a href="{{ route('etapas.veiculo', $equipamento) }}"
               class="rounded-lg border px-4 py-1.5 text-sm font-medium transition-colors
                      border-slate-200 bg-white text-zinc-600 hover:bg-zinc-50
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
                Limpar
            </a>
        @endif
    </form>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <div class="mt-4 overflow-hidden rounded-xl border shadow-sm
                border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">

        @if($etapas->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                    </svg>
                </div>
                @if($hasFilters)
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma etapa encontrada</h3>
                    <a href="{{ route('etapas.veiculo', $equipamento) }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium transition-colors
                              border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                        Limpar filtros
                    </a>
                @else
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma etapa registrada para este veículo</h3>
                    <button type="button" onclick="openCreateModal()"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                                   bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar etapa
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Etapa</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Cerca</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Status</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Início</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Fim</th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 lg:table-cell">Condutor</th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 lg:table-cell">Documento</th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 xl:table-cell">Emissor</th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($etapas as $etapa)
                            <tr class="transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $etapa->tipoEtapa?->nome ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $etapa->cerca?->nome ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($etapa->data_hora_fim)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Finalizado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Em Aberto
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $etapa->data_hora_inicio->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $etapa->data_hora_fim?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400 lg:table-cell">
                                    {{ $etapa->motorista?->nome ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400 lg:table-cell">
                                    {{ $etapa->documento ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400 xl:table-cell">
                                    {{ $etapa->emissor?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Ver --}}
                                        <button type="button"
                                                onclick="openViewModal(this)"
                                                data-view="{{ json_encode([
                                                    'id'               => $etapa->getRouteKey(),
                                                    'veiculo'          => ($equipamento->placa).($equipamento->prefixo ? ' — '.$equipamento->prefixo : ''),
                                                    'tipo'             => $etapa->tipoEtapa?->nome ?? '—',
                                                    'cerca'            => $etapa->cerca?->nome ?? '—',
                                                    'status'           => $etapa->data_hora_fim ? 'Finalizado' : 'Em Aberto',
                                                    'data_hora_inicio' => $etapa->data_hora_inicio->format('d/m/Y H:i'),
                                                    'data_hora_fim'    => $etapa->data_hora_fim?->format('d/m/Y H:i') ?? '—',
                                                    'condutor'         => $etapa->motorista?->nome ?? '—',
                                                    'documento'        => $etapa->documento ?? '—',
                                                    'observacao'       => $etapa->observacao ?? '—',
                                                    'emissor'          => $etapa->emissor?->name ?? '—',
                                                    'finalizador'      => $etapa->finalizador?->name ?? '—',
                                                    'auditado_por'     => $etapa->auditor?->name ?? '—',
                                                    'auditado_em'      => $etapa->auditado_em?->format('d/m/Y H:i') ?? '—',
                                                ]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                       border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                       dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>
                                            Ver
                                        </button>

                                        {{-- Finalizar (só etapas em aberto) --}}
                                        @unless($etapa->data_hora_fim)
                                            <button type="button"
                                                    onclick="openFinalizarModal(this)"
                                                    data-finalizar="{{ json_encode([
                                                        'id'               => $etapa->getRouteKey(),
                                                        'tipo'             => $etapa->tipoEtapa?->nome ?? '—',
                                                        'cerca'            => $etapa->cerca?->nome ?? '—',
                                                        'inicio'           => $etapa->data_hora_inicio->format('d/m/Y H:i'),
                                                        'inicio_iso'       => $etapa->data_hora_inicio->format('Y-m-d\TH:i'),
                                                        'cerca_id'   => $etapa->cerca_id,
                                                        'motorista_id'     => $etapa->motorista_id,
                                                        'motorista_nome'   => $etapa->motorista?->nome ?? '',
                                                        'documento'        => $etapa->documento ?? '',
                                                        'observacao'       => $etapa->observacao ?? '',
                                                        'url'              => route('etapas.finalizar', $etapa),
                                                    ]) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                           border-emerald-200 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-50
                                                           dark:border-emerald-800/50 dark:text-emerald-400 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                </svg>
                                                Finalizar
                                            </button>
                                        @endunless

                                        {{-- Editar --}}
                                        <button type="button"
                                                onclick="openEditModal(this)"
                                                data-etapa="{{ json_encode([
                                                    'id'               => $etapa->getRouteKey(),
                                                    'tipo_etapa_id'    => $etapa->tipo_etapa_id,
                                                    'cerca_id'   => $etapa->cerca_id,
                                                    'motorista_id'     => $etapa->motorista_id,
                                                    'documento'        => $etapa->documento,
                                                    'data_hora_inicio' => $etapa->data_hora_inicio?->format('Y-m-d\TH:i'),
                                                    'data_hora_fim'          => $etapa->data_hora_fim?->format('Y-m-d\TH:i'),
                                                    'has_fim'                => ! is_null($etapa->data_hora_fim),
                                                    'observacao'             => $etapa->observacao,
                                                    'motivo_longa_duracao'   => $etapa->motivo_longa_duracao,
                                                ]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                       border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                       dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                            Editar
                                        </button>

                                        <form method="POST" action="{{ route('etapas.destroy', $etapa) }}"
                                              data-confirm="true" data-user-name="etapa #{{ $etapa->id }}">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                           border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50
                                                           dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($etapas->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $etapas->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ─── Modal de visualização ──────────────────────────────────────────────── --}}
    <div id="view-backdrop"
         onclick="closeViewModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    <div id="view-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-lg -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        <div class="flex items-center justify-between border-b px-6 py-4 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 id="view-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Etapa</h3>
                <p id="view-status-badge" class="mt-0.5"></p>
            </div>
            <button type="button" onclick="closeViewModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-zinc-800">
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Veículo</p>
                    <p id="view-veiculo" class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Tipo de Etapa</p>
                    <p id="view-tipo" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Cerca</p>
                    <p id="view-cerca" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Condutor</p>
                    <p id="view-condutor" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Início</p>
                    <p id="view-inicio" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Fim</p>
                    <p id="view-fim" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-6 py-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Documento</p>
                    <p id="view-documento" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Emissor</p>
                    <p id="view-emissor" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Finalizador</p>
                    <p id="view-finalizador" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Auditor</p>
                    <p id="view-auditor" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
            </div>
            <div class="px-6 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Observação</p>
                <p id="view-observacao" class="mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300"></p>
            </div>
        </div>

        <div class="flex justify-end border-t px-6 py-4 border-slate-100 dark:border-zinc-800">
            <button type="button" onclick="closeViewModal()"
                    class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                           border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                           dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                Fechar
            </button>
        </div>
    </div>

    {{-- ─── Modal backdrop ──────────────────────────────────────────────────────── --}}
    <div id="etapa-backdrop"
         onclick="closeEtapaModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    {{-- ─── Modal criar / editar ────────────────────────────────────────────────── --}}
    <div id="etapa-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-5xl -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        <div class="flex items-center justify-between border-b px-6 py-4 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 id="modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Nova Etapa</h3>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $equipamento->placa }}{{ $equipamento->prefixo ? ' — '.$equipamento->prefixo : '' }}
                </p>
            </div>
            <button type="button" onclick="closeEtapaModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="max-h-[85vh] overflow-y-auto">
            <form id="etapa-form" method="POST" action="{{ route('etapas.store') }}" novalidate>
                @csrf
                <input type="hidden" id="modal-method"     name="_method"        value="">
                <input type="hidden" id="modal-id-etapa"   name="id_etapa"       value="">
                <input type="hidden"                        name="equipamento_id" value="{{ $equipamento->id }}">

                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-4">

                    {{-- Tipo de Etapa --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tipo de Etapa <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" id="combo-tipo-wrapper">
                            <input type="hidden" id="modal-tipo-etapa" name="tipo_etapa_id">
                            <input type="text" id="combo-tipo-search" autocomplete="off"
                                   placeholder="Selecione ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          {{ $errors->has('tipo_etapa_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <div id="combo-tipo-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="combo-tipo-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        @error('tipo_etapa_id')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Cerca --}}
                    <div class="space-y-1.5 sm:col-span-2 sm:col-start-3">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Cerca <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" id="combo-cerca-wrapper">
                            <input type="hidden" id="modal-cerca" name="cerca_id">
                            <input type="text" id="combo-cerca-search" autocomplete="off"
                                   placeholder="Selecione ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          {{ $errors->has('cerca_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <div id="combo-cerca-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="combo-cerca-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        @error('cerca_id')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($ultimaEtapa?->cerca)
                            <button type="button"
                                    onclick="window._comboCerca.setValue('{{ $ultimaEtapa->cerca_id }}')"
                                    class="anterior-hint flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                                </svg>
                                Anterior: <span class="font-medium">{{ $ultimaEtapa->cerca->nome }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Condutor --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Condutor</label>
                        <div class="relative" id="combo-motorista-wrapper">
                            <input type="hidden" id="modal-motorista" name="motorista_id">
                            <input type="text" id="combo-motorista-search" autocomplete="off"
                                   placeholder="Nenhum ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                            <div id="combo-motorista-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="combo-motorista-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        @if($ultimaEtapa?->motorista)
                            <button type="button"
                                    onclick="window._comboMotorista.setValue('{{ $ultimaEtapa->motorista_id }}')"
                                    class="anterior-hint flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                                </svg>
                                Anterior: <span class="font-medium">{{ titulo($ultimaEtapa->motorista->nome) }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Documento --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-documento" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Documento</label>
                        <input id="modal-documento" type="text" name="documento" maxlength="100"
                               placeholder="Ex.: 509533234…"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                      {{ $errors->has('documento') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('documento')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($ultimaEtapa?->documento)
                            <button type="button"
                                    onclick="document.getElementById('modal-documento').value = '{{ e($ultimaEtapa->documento) }}'"
                                    class="anterior-hint flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                                </svg>
                                Anterior: <span class="font-medium">{{ $ultimaEtapa->documento }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Data/Hora Início --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-data-inicio" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Data/Hora Início <span class="text-red-500">*</span>
                        </label>
                        <input id="modal-data-inicio" type="datetime-local" name="data_hora_inicio"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                      {{ $errors->has('data_hora_inicio') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('data_hora_inicio')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($ultimaEtapa?->data_hora_fim)
                            <button type="button"
                                    onclick="document.getElementById('modal-data-inicio').value = '{{ $ultimaEtapa->data_hora_fim->format('Y-m-d\TH:i') }}'"
                                    class="anterior-hint flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                                </svg>
                                Fim da anterior: <span class="font-medium">{{ $ultimaEtapa->data_hora_fim->format('d/m H:i') }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Data/Hora Fim --}}
                    <div id="modal-data-fim-wrapper" class="space-y-1.5 sm:col-span-2">
                        <label for="modal-data-fim" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Data/Hora Fim</label>
                        <input id="modal-data-fim" type="datetime-local" name="data_hora_fim"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                      {{ $errors->has('data_hora_fim') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('data_hora_fim')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Aviso + Motivo longa duração (> 24h) --}}
                    <div id="modal-longa-duracao-wrapper" class="sm:col-span-4" style="display:none">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-700/40 dark:bg-amber-950/30">
                            <div class="flex items-start gap-2.5">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Duração superior a 24 horas</p>
                                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">Verifique se não é um erro de lançamento. Se estiver correto, informe o motivo abaixo.</p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1.5">
                                <label for="modal-motivo-longa" class="block text-xs font-semibold text-amber-800 dark:text-amber-300">
                                    Motivo da longa duração <span class="text-red-500">*</span>
                                </label>
                                <input id="modal-motivo-longa" type="text" name="motivo_longa_duracao" maxlength="500"
                                       placeholder="Ex.: Veículo aguardou operação de carga durante a madrugada…"
                                       class="block w-full rounded-lg border px-3.5 py-2 text-sm outline-none transition-all focus:ring-2
                                              bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                              border-amber-300 focus:border-amber-500 focus:ring-amber-500/10 dark:border-amber-600 dark:focus:border-amber-400">
                                @error('motivo_longa_duracao')
                                    <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Observação --}}
                    <div class="space-y-1.5 sm:col-span-4">
                        <label for="modal-observacao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observação</label>
                        <textarea id="modal-observacao" name="observacao" rows="5"
                                  class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                         bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                         {{ $errors->has('observacao') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"></textarea>
                        @error('observacao')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if($ultimaEtapa?->observacao)
                            <button type="button"
                                    onclick="document.getElementById('modal-observacao').value = {{ Js::from($ultimaEtapa->observacao) }}"
                                    class="anterior-hint flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                                </svg>
                                Anterior: <span class="truncate font-medium">{{ Str::limit($ultimaEtapa->observacao, 60) }}</span>
                            </button>
                        @endif
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 border-t px-6 py-4 border-slate-100 dark:border-zinc-800">
                    <button type="button" onclick="closeEtapaModal()"
                            class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                                   border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                                   dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                        Cancelar
                    </button>
                    <button type="submit" id="modal-submit-btn"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        <span id="modal-submit-label">Registrar Etapa</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ─── Backdrop modal finalizar ────────────────────────────────────────────── --}}
    <div id="finalizar-backdrop"
         onclick="closeFinalizarModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    {{-- ─── Modal Finalizar Etapa ───────────────────────────────────────────────── --}}
    <div id="finalizar-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-5xl -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        <div class="flex items-center justify-between border-b px-6 py-4 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Finalizar Etapa</h3>
                <p id="finalizar-subtitulo" class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400"></p>
            </div>
            <button type="button" onclick="closeFinalizarModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="max-h-[85vh] overflow-y-auto">
            <form id="finalizar-form" method="POST" action="" novalidate>
                @csrf

                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-4">

                    {{-- ── Seção: Encerrar etapa atual ─────────────────────────── --}}
                    <div class="sm:col-span-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-white dark:bg-white dark:text-zinc-900">1</span>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Encerrar etapa atual</p>
                        </div>
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
                            <p id="finalizar-etapa-info" class="text-sm text-zinc-600 dark:text-zinc-400"></p>
                        </div>
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="fin-data-fim" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Data/Hora Fim <span class="text-red-500">*</span>
                        </label>
                        <input id="fin-data-fim" type="datetime-local" name="data_hora_fim"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                      border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        @error('data_hora_fim')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ── Divisor ──────────────────────────────────────────────── --}}
                    <div class="sm:col-span-4">
                        <div class="relative flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-200 dark:bg-zinc-700"></div>
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-white dark:bg-white dark:text-zinc-900">2</span>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Próxima etapa</p>
                            <div class="h-px flex-1 bg-slate-200 dark:bg-zinc-700"></div>
                        </div>
                        <p class="mt-1 text-center text-xs text-zinc-400 dark:text-zinc-600">Obrigatório — a etapa atual só será encerrada se a próxima for informada</p>
                    </div>

                    {{-- Tipo --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tipo de Etapa <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" id="fin-combo-tipo-wrapper">
                            <input type="hidden" id="fin-tipo-etapa" name="proxima_tipo_etapa_id">
                            <input type="text" id="fin-combo-tipo-search" autocomplete="off"
                                   placeholder="Selecione ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                            <div id="fin-combo-tipo-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="fin-combo-tipo-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        @error('proxima_tipo_etapa_id')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Cerca --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Cerca <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" id="fin-combo-cerca-wrapper">
                            <input type="hidden" id="fin-cerca" name="proxima_cerca_id">
                            <input type="text" id="fin-combo-cerca-search" autocomplete="off"
                                   placeholder="Selecione ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                            <div id="fin-combo-cerca-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="fin-combo-cerca-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        @error('proxima_cerca_id')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="button" id="fin-hint-cerca" style="display:none"
                                class="flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                            </svg>
                            Anterior: <span id="fin-hint-cerca-nome" class="font-medium ml-0.5"></span>
                        </button>
                    </div>

                    {{-- Condutor — 50% --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Condutor</label>
                        <div class="relative" id="fin-combo-motorista-wrapper">
                            <input type="hidden" id="fin-motorista" name="proxima_motorista_id">
                            <input type="text" id="fin-combo-motorista-search" autocomplete="off"
                                   placeholder="Nenhum ou filtre…"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                          bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                          border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                            <div id="fin-combo-motorista-dropdown"
                                 class="absolute z-[60] mt-1 hidden w-full overflow-hidden rounded-lg border shadow-lg
                                        border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div id="fin-combo-motorista-list" class="max-h-48 overflow-y-auto"></div>
                            </div>
                        </div>
                        <button type="button" id="fin-hint-motorista" style="display:none"
                                class="flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                            </svg>
                            Anterior: <span id="fin-hint-motorista-nome" class="font-medium ml-0.5"></span>
                        </button>
                    </div>

                    {{-- Documento — 25% --}}
                    <div class="space-y-1.5 sm:col-span-1">
                        <label for="fin-documento" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Documento</label>
                        <input id="fin-documento" type="text" name="proxima_documento" maxlength="100"
                               placeholder="Ex.: 509533234…"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                      border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        @error('proxima_documento')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="button" id="fin-hint-documento" style="display:none"
                                class="flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                            </svg>
                            Anterior: <span id="fin-hint-documento-valor" class="font-medium ml-0.5"></span>
                        </button>
                    </div>

                    {{-- Data/Hora Início (próxima) — 25% --}}
                    <div class="space-y-1.5 sm:col-span-1">
                        <label for="fin-proxima-inicio" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Início da Próxima Etapa <span class="text-red-500">*</span>
                        </label>
                        <input id="fin-proxima-inicio" type="datetime-local" name="proxima_data_hora_inicio"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                      border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-600">Preenchido automaticamente com o fim; ajuste se necessário.</p>
                        @error('proxima_data_hora_inicio')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observação --}}
                    <div class="space-y-1.5 sm:col-span-4">
                        <label for="fin-observacao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observação</label>
                        <textarea id="fin-observacao" name="proxima_observacao" rows="3"
                                  class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                         bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                         border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"></textarea>
                        @error('proxima_observacao')
                            <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <button type="button" id="fin-hint-observacao" style="display:none"
                                class="flex items-center gap-1 text-[11px] text-zinc-400 transition-colors hover:text-indigo-600 dark:hover:text-indigo-400">
                            <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/>
                            </svg>
                            Anterior: <span id="fin-hint-observacao-valor" class="font-medium truncate ml-0.5"></span>
                        </button>
                    </div>

                    {{-- Aviso + Motivo longa duração (> 24h) --}}
                    <div id="fin-longa-duracao-wrapper" class="sm:col-span-4" style="display:none">
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-700/40 dark:bg-amber-950/30">
                            <div class="flex items-start gap-2.5">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Duração superior a 24 horas</p>
                                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">Verifique se não é um erro de lançamento. Se estiver correto, informe o motivo abaixo.</p>
                                </div>
                            </div>
                            <div class="mt-3 space-y-1.5">
                                <label for="fin-motivo-longa" class="block text-xs font-semibold text-amber-800 dark:text-amber-300">
                                    Motivo da longa duração <span class="text-red-500">*</span>
                                </label>
                                <input id="fin-motivo-longa" type="text" name="motivo_longa_duracao" maxlength="500"
                                       placeholder="Ex.: Veículo aguardou operação de carga durante a madrugada…"
                                       class="block w-full rounded-lg border px-3.5 py-2 text-sm outline-none transition-all focus:ring-2
                                              bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                              border-amber-300 focus:border-amber-500 focus:ring-amber-500/10 dark:border-amber-600 dark:focus:border-amber-400">
                                @error('motivo_longa_duracao')
                                    <p class="fin-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex items-center justify-end gap-3 border-t px-6 py-4 border-slate-100 dark:border-zinc-800">
                    <button type="button" onclick="closeFinalizarModal()"
                            class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                                   border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                                   dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-emerald-700 active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Finalizar e Registrar Próxima
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var storeUrl   = '{{ route('etapas.store') }}';
        var updateBase = '{{ url('etapas') }}';

        var errorBorderClasses  = ['border-red-400', 'focus:border-red-500', 'focus:ring-red-500/10', 'dark:border-red-700'];
        var normalBorderClasses = ['border-slate-300', 'focus:border-zinc-900', 'focus:ring-zinc-900/10', 'dark:border-zinc-700', 'dark:focus:border-zinc-400', 'dark:focus:ring-zinc-400/10'];
        var modalFieldIds       = ['combo-tipo-search', 'combo-cerca-search', 'combo-motorista-search', 'modal-documento', 'modal-data-inicio', 'modal-data-fim', 'modal-observacao'];

        // ─── Combobox filtrável ───────────────────────────────────────────────────
        function makeCombobox(hiddenId, searchId, dropdownId, listId, items) {
            var hidden   = document.getElementById(hiddenId);
            var search   = document.getElementById(searchId);
            var list     = document.getElementById(listId);
            var wrapper  = search.closest('.relative');
            var selected = { value: '', label: '' };
            var highlighted = -1;

            // Move dropdown para body para escapar do overflow do modal
            var dropdown = document.getElementById(dropdownId);
            dropdown.classList.remove('absolute');
            dropdown.classList.add('fixed');
            document.body.appendChild(dropdown);

            function positionDropdown() {
                var r = search.getBoundingClientRect();
                dropdown.style.top   = (r.bottom + 4) + 'px';
                dropdown.style.left  = r.left + 'px';
                dropdown.style.width = r.width + 'px';
            }

            function getButtons() {
                return Array.prototype.slice.call(list.querySelectorAll('button[data-value]'));
            }

            function applyHighlight() {
                getButtons().forEach(function (btn, i) {
                    if (i === highlighted) {
                        btn.classList.add('bg-slate-100', 'dark:bg-zinc-700');
                        btn.scrollIntoView({ block: 'nearest' });
                    } else {
                        btn.classList.remove('bg-slate-100', 'dark:bg-zinc-700');
                    }
                });
            }

            function renderList(filter) {
                var q    = (filter || '').toLowerCase().trim();
                var html = '';
                var found = false;
                items.forEach(function (item) {
                    if (!q || item.label.toLowerCase().includes(q)) {
                        var active = String(item.value) === String(selected.value);
                        html += '<button type="button" data-value="' + item.value + '" '
                            + 'class="w-full px-3.5 py-2 text-left text-sm transition-colors '
                            + 'text-zinc-900 dark:text-zinc-100 '
                            + (active ? 'bg-slate-100 dark:bg-zinc-700 font-medium '
                                      : 'hover:bg-slate-50 dark:hover:bg-zinc-700/60 ')
                            + '">' + item.label + '</button>';
                        found = true;
                    }
                });
                list.innerHTML = found
                    ? html
                    : '<p class="px-3.5 py-2.5 text-sm text-zinc-400 dark:text-zinc-500">Nenhum resultado</p>';
                highlighted = -1;
            }

            function openDropdown() {
                positionDropdown();
                renderList(search.value);
                dropdown.classList.remove('hidden');
            }

            function closeDropdown() {
                dropdown.classList.add('hidden');
                search.value = selected.label;
                highlighted  = -1;
            }

            function selectItem(value, label) {
                selected     = { value: value, label: label };
                hidden.value = value;
                search.value = label;
                dropdown.classList.add('hidden');
                highlighted  = -1;
            }

            list.addEventListener('mousedown', function (e) {
                // mousedown antes do blur para garantir a seleção
                e.preventDefault();
                var btn = e.target.closest('button[data-value]');
                if (btn) { selectItem(btn.dataset.value, btn.textContent.trim()); }
            });

            search.addEventListener('focus', openDropdown);
            search.addEventListener('blur', function () {
                // pequeno delay para que mousedown no item seja processado primeiro
                setTimeout(closeDropdown, 120);
            });
            search.addEventListener('input', function () {
                selected     = { value: '', label: '' };
                hidden.value = '';
                openDropdown();
            });
            search.addEventListener('keydown', function (e) {
                var btns = getButtons();
                if (e.key === 'Escape') { closeDropdown(); e.stopPropagation(); return; }
                if (e.key === 'Tab')    { closeDropdown(); return; }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (dropdown.classList.contains('hidden')) { openDropdown(); }
                    highlighted = Math.min(highlighted + 1, btns.length - 1);
                    applyHighlight();
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlighted = Math.max(highlighted - 1, -1);
                    applyHighlight();
                    return;
                }
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlighted >= 0 && btns[highlighted]) {
                        selectItem(btns[highlighted].dataset.value, btns[highlighted].textContent.trim());
                    }
                }
            });

            window.addEventListener('scroll', positionDropdown, true);
            window.addEventListener('resize', positionDropdown);

            document.addEventListener('click', function (e) {
                if (!wrapper.contains(e.target) && !dropdown.contains(e.target)) {
                    closeDropdown();
                }
            });

            return {
                clear: function () {
                    selected     = { value: '', label: '' };
                    hidden.value = '';
                    search.value = '';
                },
                setValue: function (value) {
                    if (!value) { this.clear(); return; }
                    var item = items.filter(function (i) { return String(i.value) === String(value); })[0];
                    if (item) {
                        selected     = { value: item.value, label: item.label };
                        hidden.value = item.value;
                        search.value = item.label;
                    }
                },
            };
        }

        var tipoItems      = @json($tipos->map(fn($t) => ['value' => $t->id, 'label' => titulo($t->nome)]));
        var cercaItems     = @json($cercas->map(fn($l) => ['value' => $l->id, 'label' => $l->nome]));
        var motoristaItems = @json($motoristas->map(fn($m) => ['value' => $m->id, 'label' => titulo($m->nome)]));
        motoristaItems.unshift({ value: '', label: '— Nenhum —' });

        var comboTipo      = makeCombobox('modal-tipo-etapa',  'combo-tipo-search',      'combo-tipo-dropdown',      'combo-tipo-list',      tipoItems);
        var comboCerca     = makeCombobox('modal-cerca', 'combo-cerca-search',     'combo-cerca-dropdown',     'combo-cerca-list',     cercaItems);
        var comboMotorista = makeCombobox('modal-motorista',   'combo-motorista-search', 'combo-motorista-dropdown', 'combo-motorista-list', motoristaItems);

        // Expõe para uso nos hints de anterior (onclick inline no Blade)
        window._comboCerca     = comboCerca;
        window._comboMotorista = comboMotorista;

        function clearModalErrors() {
            document.querySelectorAll('.modal-field-error').forEach(function (el) { el.remove(); });
            modalFieldIds.forEach(function (id) {
                var el = document.getElementById(id);
                if (! el) { return; }
                errorBorderClasses.forEach(function (c) { el.classList.remove(c); });
                normalBorderClasses.forEach(function (c) { el.classList.add(c); });
            });
        }

        function resetForm() {
            document.getElementById('modal-id-etapa').value   = '';
            comboTipo.clear();
            comboCerca.clear();
            comboMotorista.clear();
            document.getElementById('modal-documento').value   = '';
            document.getElementById('modal-data-inicio').value = '';
            document.getElementById('modal-data-fim').value    = '';
            document.getElementById('modal-observacao').value  = '';
            document.getElementById('modal-motivo-longa').value = '';
            document.getElementById('modal-longa-duracao-wrapper').style.display = 'none';
            // Garante que o campo fim fique visível (pode ter sido ocultado na edição)
            var fimWrapper = document.getElementById('modal-data-fim-wrapper');
            if (fimWrapper) { fimWrapper.style.display = ''; }
        }

        function openModal() {
            document.getElementById('etapa-backdrop').classList.remove('hidden');
            document.getElementById('etapa-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        window.closeEtapaModal = function () {
            document.getElementById('etapa-backdrop').classList.add('hidden');
            document.getElementById('etapa-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        // ─── Modal Finalizar ─────────────────────────────────────────────────────
        var finComboTipo      = null;
        var finComboCerca     = null;
        var finComboMotorista = null;

        (function initFinalizarCombos() {
            finComboTipo      = makeCombobox('fin-tipo-etapa',  'fin-combo-tipo-search',      'fin-combo-tipo-dropdown',      'fin-combo-tipo-list',      @json($tipos->map(fn($t) => ['value' => $t->id, 'label' => $t->nome])));
            finComboCerca     = makeCombobox('fin-cerca', 'fin-combo-cerca-search',     'fin-combo-cerca-dropdown',     'fin-combo-cerca-list',     @json($cercas->map(fn($l) => ['value' => $l->id, 'label' => $l->nome])));
            finComboMotorista = makeCombobox('fin-motorista',   'fin-combo-motorista-search', 'fin-combo-motorista-dropdown', 'fin-combo-motorista-list', @json($motoristas->map(fn($m) => ['value' => $m->id, 'label' => $m->nome])));
        })();

        // ─── Longa duração (> 24h) ───────────────────────────────────────────────
        function diffHours(a, b) {
            if (! a || ! b) { return 0; }
            return Math.abs(new Date(b) - new Date(a)) / 36e5;
        }

        function toggleLongaDuracao(wrapperId, inputId, horas) {
            var wrapper = document.getElementById(wrapperId);
            var input   = document.getElementById(inputId);
            if (horas > 24) {
                wrapper.style.display = '';
                input.focus();
            } else {
                wrapper.style.display = 'none';
                input.value = '';
            }
        }

        // Modal criar/editar — verifica ao alterar início ou fim
        function checkModalLonga() {
            var inicio = document.getElementById('modal-data-inicio').value;
            var fim    = document.getElementById('modal-data-fim').value;
            toggleLongaDuracao('modal-longa-duracao-wrapper', 'modal-motivo-longa', diffHours(inicio, fim));
        }
        document.getElementById('modal-data-inicio').addEventListener('change', checkModalLonga);
        document.getElementById('modal-data-fim').addEventListener('change', checkModalLonga);

        // Modal finalizar — verifica ao alterar fim (início vem do data-finalizar)
        var _finInicioISO = '';
        document.getElementById('fin-data-fim').addEventListener('change', function () {
            var proximaInicio = document.getElementById('fin-proxima-inicio');
            if (! proximaInicio.value) {
                proximaInicio.value = this.value;
            }
            toggleLongaDuracao('fin-longa-duracao-wrapper', 'fin-motivo-longa', diffHours(_finInicioISO, this.value));
        });

        window.closeFinalizarModal = function () {
            document.getElementById('finalizar-backdrop').classList.add('hidden');
            document.getElementById('finalizar-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.openFinalizarModal = function (btn) {
            var el = btn.closest('[data-finalizar]');
            if (! el) { return; }
            var data = JSON.parse(el.dataset.finalizar);

            // Remove erros anteriores
            document.querySelectorAll('.fin-field-error').forEach(function (e) { e.remove(); });

            // Subtítulo e info da etapa
            document.getElementById('finalizar-subtitulo').textContent =
                '{{ $equipamento->placa }}{{ $equipamento->prefixo ? ' — '.$equipamento->prefixo : '' }}';
            document.getElementById('finalizar-etapa-info').textContent =
                data.tipo + ' · ' + data.cerca + ' · Início: ' + data.inicio;

            // Guarda início para cálculo de longa duração
            _finInicioISO = data.inicio_iso || '';

            // Limpa campos
            document.getElementById('fin-data-fim').value       = '';
            document.getElementById('fin-proxima-inicio').value = '';
            document.getElementById('fin-motivo-longa').value   = '';
            document.getElementById('fin-longa-duracao-wrapper').style.display = 'none';
            document.getElementById('fin-documento').value      = '';
            document.getElementById('fin-observacao').value     = '';
            finComboTipo.clear();
            finComboCerca.clear();
            finComboMotorista.clear();

            // ── Hints "Anterior:" ─────────────────────────────────────────────
            var hintCerca      = document.getElementById('fin-hint-cerca');
            var hintMotorista  = document.getElementById('fin-hint-motorista');
            var hintDocumento  = document.getElementById('fin-hint-documento');
            var hintObservacao = document.getElementById('fin-hint-observacao');

            if (data.cerca_id && data.cerca) {
                document.getElementById('fin-hint-cerca-nome').textContent = data.cerca;
                hintCerca.style.display = '';
                hintCerca.onclick = function () { finComboCerca.setValue(data.cerca_id); };
            } else {
                hintCerca.style.display = 'none';
            }

            if (data.motorista_id && data.motorista_nome) {
                document.getElementById('fin-hint-motorista-nome').textContent = data.motorista_nome;
                hintMotorista.style.display = '';
                hintMotorista.onclick = function () { finComboMotorista.setValue(data.motorista_id); };
            } else {
                hintMotorista.style.display = 'none';
            }

            if (data.documento) {
                document.getElementById('fin-hint-documento-valor').textContent = data.documento;
                hintDocumento.style.display = '';
                hintDocumento.onclick = function () { document.getElementById('fin-documento').value = data.documento; };
            } else {
                hintDocumento.style.display = 'none';
            }

            if (data.observacao) {
                document.getElementById('fin-hint-observacao-valor').textContent =
                    data.observacao.length > 60 ? data.observacao.substring(0, 60) + '…' : data.observacao;
                hintObservacao.style.display = '';
                hintObservacao.onclick = function () { document.getElementById('fin-observacao').value = data.observacao; };
            } else {
                hintObservacao.style.display = 'none';
            }

            // Aponta form para a rota correta
            document.getElementById('finalizar-form').action = data.url;

            document.getElementById('finalizar-backdrop').classList.remove('hidden');
            document.getElementById('finalizar-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.getElementById('fin-data-fim').focus();
        };

        function setAnteriorHintsVisible(visible) {
            document.querySelectorAll('.anterior-hint').forEach(function (el) {
                el.style.display = visible ? '' : 'none';
            });
        }

        // Ocultar hints na carga; só serão exibidos quando o modal abrir corretamente.
        setAnteriorHintsVisible(false);

        window.openCreateModal = function () {
            clearModalErrors();
            document.getElementById('modal-title').textContent    = 'Nova Etapa';
            document.getElementById('modal-submit-label').textContent = 'Registrar Etapa';
            document.getElementById('modal-submit-btn').querySelector('svg').classList.remove('hidden');
            document.getElementById('modal-method').value         = '';
            document.getElementById('etapa-form').action         = storeUrl;
            resetForm();
            setAnteriorHintsVisible({{ $ultimaEtapa ? 'true' : 'false' }});
            openModal();
            document.getElementById('combo-tipo-search').focus();
        };

        window.openEditModal = function (btn) {
            clearModalErrors();
            var el = btn.closest('[data-etapa]');
            if (! el) { return; }
            var data = JSON.parse(el.dataset.etapa);

            document.getElementById('modal-title').textContent           = 'Editar Etapa';
            document.getElementById('modal-submit-label').textContent    = 'Salvar Alterações';
            document.getElementById('modal-submit-btn').querySelector('svg').classList.add('hidden');
            document.getElementById('modal-method').value                = 'PUT';
            document.getElementById('modal-id-etapa').value             = data.id;
            document.getElementById('etapa-form').action                = updateBase + '/' + data.id;

            comboTipo.setValue(data.tipo_etapa_id   || '');
            comboCerca.setValue(data.cerca_id  || '');
            comboMotorista.setValue(data.motorista_id || '');
            document.getElementById('modal-documento').value        = data.documento             || '';
            document.getElementById('modal-data-inicio').value      = data.data_hora_inicio      || '';
            document.getElementById('modal-data-fim').value         = data.data_hora_fim         || '';
            document.getElementById('modal-observacao').value       = data.observacao            || '';
            document.getElementById('modal-motivo-longa').value     = data.motivo_longa_duracao  || '';

            // Oculta o campo fim para etapas em aberto — finalização deve usar o fluxo correto
            var fimWrapper = document.getElementById('modal-data-fim-wrapper');
            if (fimWrapper) {
                fimWrapper.style.display = data.has_fim ? '' : 'none';
            }

            // Verifica longa duração ao abrir edição
            checkModalLonga();

            setAnteriorHintsVisible(false);
            openModal();
        };

        // ─── View modal ──────────────────────────────────────────────────────────
        window.closeViewModal = function () {
            document.getElementById('view-backdrop').classList.add('hidden');
            document.getElementById('view-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.openViewModal = function (btn) {
            var el = btn.closest('[data-view]');
            if (! el) { return; }
            var data = JSON.parse(el.dataset.view);

            document.getElementById('view-title').textContent      = 'Etapa';
            document.getElementById('view-veiculo').textContent    = data.veiculo;
            document.getElementById('view-tipo').textContent       = data.tipo;
            document.getElementById('view-cerca').textContent      = data.cerca;
            document.getElementById('view-condutor').textContent   = data.condutor;
            document.getElementById('view-inicio').textContent     = data.data_hora_inicio;
            document.getElementById('view-fim').textContent        = data.data_hora_fim;
            document.getElementById('view-documento').textContent  = data.documento;
            document.getElementById('view-emissor').textContent    = data.emissor;
            document.getElementById('view-finalizador').textContent = data.finalizador;
            document.getElementById('view-auditor').textContent    = data.auditado_por;
            document.getElementById('view-observacao').textContent = data.observacao;

            var badge = document.getElementById('view-status-badge');
            if (data.status === 'Finalizado') {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Finalizado</span>';
            } else {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Em Aberto</span>';
            }

            document.getElementById('view-backdrop').classList.remove('hidden');
            document.getElementById('view-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        // ─── Reabrir modal com erros de validação ────────────────────────────────
        @if($errors->any())
        (function () {
            var old     = @json(old());
            var isEdit  = old._method === 'PUT';
            var idEtapa = old.id_etapa;

            // Modal de finalização — identificado pelo campo exclusivo proxima_tipo_etapa_id
            if (old.proxima_tipo_etapa_id !== undefined) {
                if (old.proxima_tipo_etapa_id)    { finComboTipo.setValue(old.proxima_tipo_etapa_id); }
                if (old.proxima_cerca_id)   { finComboCerca.setValue(old.proxima_cerca_id); }
                if (old.proxima_motorista_id)     { finComboMotorista.setValue(old.proxima_motorista_id); }
                if (old.proxima_documento)         { document.getElementById('fin-documento').value     = old.proxima_documento; }
                if (old.proxima_data_hora_inicio)  { document.getElementById('fin-proxima-inicio').value = old.proxima_data_hora_inicio; }
                if (old.data_hora_fim)             { document.getElementById('fin-data-fim').value      = old.data_hora_fim; }
                if (old.proxima_observacao)        { document.getElementById('fin-observacao').value    = old.proxima_observacao; }
                document.getElementById('finalizar-backdrop').classList.remove('hidden');
                document.getElementById('finalizar-modal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
                return;
            }

            // Modal de criar / editar
            if (isEdit && idEtapa) {
                document.getElementById('etapa-form').action    = updateBase + '/' + idEtapa;
                document.getElementById('modal-method').value   = 'PUT';
                document.getElementById('modal-id-etapa').value = idEtapa;
                document.getElementById('modal-title').textContent           = 'Editar Etapa';
                document.getElementById('modal-submit-label').textContent    = 'Salvar Alterações';
            }

            if (old.tipo_etapa_id)  { comboTipo.setValue(old.tipo_etapa_id); }
            if (old.cerca_id) { comboCerca.setValue(old.cerca_id); }
            if (old.motorista_id)   { comboMotorista.setValue(old.motorista_id); }
            if (old.documento)        { document.getElementById('modal-documento').value   = old.documento; }
            if (old.data_hora_inicio) { document.getElementById('modal-data-inicio').value = old.data_hora_inicio; }
            if (old.data_hora_fim)    { document.getElementById('modal-data-fim').value    = old.data_hora_fim; }
            if (old.observacao)       { document.getElementById('modal-observacao').value  = old.observacao; }

            setAnteriorHintsVisible(isEdit ? false : {{ $ultimaEtapa ? 'true' : 'false' }});
            openModal();
        })();
        @endif

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.closeEtapaModal();
                window.closeViewModal();
                window.closeFinalizarModal();
            }
        });
    })();
    </script>

</x-layouts.app>
