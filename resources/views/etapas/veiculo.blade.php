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
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    @php
        $currentTipo       = request('tipo_etapa_id');
        $currentStatus     = request('status', '');
        $currentDataInicio = request('data_inicio');
        $currentDataFim    = request('data_fim');
        $hasFilters        = request()->hasAny(['tipo_etapa_id', 'status', 'data_inicio', 'data_fim']);
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
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Local</th>
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
                                    {{ $etapa->localEtapa?->nome ?? '—' }}
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
                                                    'local'            => $etapa->localEtapa?->nome ?? '—',
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

                                        {{-- Editar --}}
                                        <button type="button"
                                                onclick="openEditModal(this)"
                                                data-etapa="{{ json_encode([
                                                    'id'               => $etapa->getRouteKey(),
                                                    'tipo_etapa_id'    => $etapa->tipo_etapa_id,
                                                    'local_etapa_id'   => $etapa->local_etapa_id,
                                                    'motorista_id'     => $etapa->motorista_id,
                                                    'documento'        => $etapa->documento,
                                                    'data_hora_inicio' => $etapa->data_hora_inicio?->format('Y-m-d\TH:i'),
                                                    'data_hora_fim'    => $etapa->data_hora_fim?->format('Y-m-d\TH:i'),
                                                    'observacao'       => $etapa->observacao,
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
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Local</p>
                    <p id="view-local" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
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
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-2xl -translate-y-1/2 overflow-hidden
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

        <div class="max-h-[70vh] overflow-y-auto">
            <form id="etapa-form" method="POST" action="{{ route('etapas.store') }}" novalidate>
                @csrf
                <input type="hidden" id="modal-method"     name="_method"        value="">
                <input type="hidden" id="modal-id-etapa"   name="id_etapa"       value="">
                <input type="hidden"                        name="equipamento_id" value="{{ $equipamento->id }}">

                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">

                    {{-- Tipo de Etapa --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-tipo-etapa" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tipo de Etapa <span class="text-red-500">*</span>
                        </label>
                        <select id="modal-tipo-etapa" name="tipo_etapa_id"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       {{ $errors->has('tipo_etapa_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <option value="">— Selecione —</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}">{{ titulo($tipo->nome) }}</option>
                            @endforeach
                        </select>
                        @error('tipo_etapa_id')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Local --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-local-etapa" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Local <span class="text-red-500">*</span>
                        </label>
                        <select id="modal-local-etapa" name="local_etapa_id"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       {{ $errors->has('local_etapa_id') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <option value="">— Selecione —</option>
                            @foreach($locais as $local)
                                <option value="{{ $local->id }}">{{ $local->nome }}</option>
                            @endforeach
                        </select>
                        @error('local_etapa_id')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Condutor --}}
                    <div class="space-y-1.5">
                        <label for="modal-motorista" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Condutor</label>
                        <select id="modal-motorista" name="motorista_id"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                            <option value="">— Nenhum —</option>
                            @foreach($motoristas as $motorista)
                                <option value="{{ $motorista->id }}">{{ titulo($motorista->nome) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Documento --}}
                    <div class="space-y-1.5">
                        <label for="modal-documento" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Documento</label>
                        <input id="modal-documento" type="text" name="documento" maxlength="100"
                               placeholder="Ex.: 509533234…"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                      {{ $errors->has('documento') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('documento')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Data/Hora Início --}}
                    <div class="space-y-1.5">
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
                    </div>

                    {{-- Data/Hora Fim --}}
                    <div class="space-y-1.5">
                        <label for="modal-data-fim" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Data/Hora Fim</label>
                        <input id="modal-data-fim" type="datetime-local" name="data_hora_fim"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                      {{ $errors->has('data_hora_fim') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('data_hora_fim')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observação --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-observacao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observação</label>
                        <textarea id="modal-observacao" name="observacao" rows="3"
                                  class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                         bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                         {{ $errors->has('observacao') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"></textarea>
                        @error('observacao')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
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

    <script>
    (function () {
        var storeUrl   = '{{ route('etapas.store') }}';
        var updateBase = '{{ url('etapas') }}';

        var errorBorderClasses  = ['border-red-400', 'focus:border-red-500', 'focus:ring-red-500/10', 'dark:border-red-700'];
        var normalBorderClasses = ['border-slate-300', 'focus:border-zinc-900', 'focus:ring-zinc-900/10', 'dark:border-zinc-700', 'dark:focus:border-zinc-400', 'dark:focus:ring-zinc-400/10'];
        var modalFieldIds       = ['modal-tipo-etapa', 'modal-local-etapa', 'modal-motorista', 'modal-documento', 'modal-data-inicio', 'modal-data-fim', 'modal-observacao'];

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
            document.getElementById('modal-id-etapa').value      = '';
            document.getElementById('modal-tipo-etapa').value    = '';
            document.getElementById('modal-local-etapa').value   = '';
            document.getElementById('modal-motorista').value     = '';
            document.getElementById('modal-documento').value     = '';
            document.getElementById('modal-data-inicio').value   = '';
            document.getElementById('modal-data-fim').value      = '';
            document.getElementById('modal-observacao').value    = '';
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

        window.openCreateModal = function () {
            clearModalErrors();
            document.getElementById('modal-title').textContent    = 'Nova Etapa';
            document.getElementById('modal-submit-label').textContent = 'Registrar Etapa';
            document.getElementById('modal-submit-btn').querySelector('svg').classList.remove('hidden');
            document.getElementById('modal-method').value         = '';
            document.getElementById('etapa-form').action         = storeUrl;
            resetForm();
            openModal();
            document.getElementById('modal-tipo-etapa').focus();
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

            document.getElementById('modal-tipo-etapa').value   = data.tipo_etapa_id   || '';
            document.getElementById('modal-local-etapa').value  = data.local_etapa_id  || '';
            document.getElementById('modal-motorista').value    = data.motorista_id     || '';
            document.getElementById('modal-documento').value    = data.documento        || '';
            document.getElementById('modal-data-inicio').value  = data.data_hora_inicio || '';
            document.getElementById('modal-data-fim').value     = data.data_hora_fim    || '';
            document.getElementById('modal-observacao').value   = data.observacao       || '';

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
            document.getElementById('view-local').textContent      = data.local;
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
            var old    = @json(old());
            var isEdit = old._method === 'PUT';
            var idEtapa = old.id_etapa;

            if (isEdit && idEtapa) {
                document.getElementById('etapa-form').action    = updateBase + '/' + idEtapa;
                document.getElementById('modal-method').value   = 'PUT';
                document.getElementById('modal-id-etapa').value = idEtapa;
                document.getElementById('modal-title').textContent           = 'Editar Etapa';
                document.getElementById('modal-submit-label').textContent    = 'Salvar Alterações';
            }

            if (old.tipo_etapa_id)    { document.getElementById('modal-tipo-etapa').value  = old.tipo_etapa_id; }
            if (old.local_etapa_id)   { document.getElementById('modal-local-etapa').value = old.local_etapa_id; }
            if (old.motorista_id)     { document.getElementById('modal-motorista').value   = old.motorista_id; }
            if (old.documento)        { document.getElementById('modal-documento').value   = old.documento; }
            if (old.data_hora_inicio) { document.getElementById('modal-data-inicio').value = old.data_hora_inicio; }
            if (old.data_hora_fim)    { document.getElementById('modal-data-fim').value    = old.data_hora_fim; }
            if (old.observacao)       { document.getElementById('modal-observacao').value  = old.observacao; }

            openModal();
        })();
        @endif

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.closeEtapaModal();
                window.closeViewModal();
            }
        });
    })();
    </script>

</x-layouts.app>
