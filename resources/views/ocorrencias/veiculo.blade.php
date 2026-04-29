<x-layouts.app title="Ocorrências — {{ $equipamento->prefixo ?? $equipamento->placa }}">

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
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Total de ocorrências</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $ocorrencias->total() }}</p>
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
                Nova Ocorrência
            </button>
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    @php
        $currentTipo       = request('id_tipo');
        $currentStatus     = request()->has('status') ? request('status') : 'aberta';
        $currentDataInicio = request('data_inicio');
        $currentDataFim    = request('data_fim');
        $hasFilters        = request()->hasAny(['id_tipo', 'status', 'data_inicio', 'data_fim']);
    @endphp

    <form method="GET" action="{{ route('ocorrencias.veiculo', $equipamento) }}"
          class="mt-4 flex flex-wrap items-end gap-3">

        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Tipo</label>
            <select name="id_tipo"
                    class="rounded-lg border px-3 py-1.5 text-sm
                           border-slate-200 bg-white text-zinc-700
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                           focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:focus:ring-zinc-400/20">
                <option value="">Todos os tipos</option>
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo->id_tipo }}" @selected($currentTipo == $tipo->id_tipo)>{{ titulo($tipo->descricao) }}</option>
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
                <option value="aberta"  @selected($currentStatus === 'aberta')>Em Aberto</option>
                <option value="fechada" @selected($currentStatus === 'fechada')>Fechada</option>
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
            <a href="{{ route('ocorrencias.veiculo', $equipamento) }}"
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

        @if($ocorrencias->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                </div>
                @if($hasFilters)
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma ocorrência encontrada</h3>
                    <a href="{{ route('ocorrencias.veiculo', $equipamento) }}"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg border px-3.5 py-2 text-sm font-medium transition-colors
                              border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                        Limpar filtros
                    </a>
                @else
                    <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma ocorrência registrada para este veículo</h3>
                    <button type="button" onclick="openCreateModal()"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                                   bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar ocorrência
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Tipo</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Status</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Responsável</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Justificativa</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Início</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Fim</th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 lg:table-cell">Documento</th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 lg:table-cell">Nº RO</th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($ocorrencias as $ocorrencia)
                            <tr class="transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $ocorrencia->tipo ? titulo($ocorrencia->tipo->descricao) : '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($ocorrencia->status_ocorrencia === 'Em Aberto')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Em Aberto
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Fechada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $ocorrencia->responsavel ? titulo($ocorrencia->responsavel->nome) : '—' }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $ocorrencia->justificativa ? titulo($ocorrencia->justificativa->descricao) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $ocorrencia->data_hora_inicio->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $ocorrencia->data_hora_fim?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400 lg:table-cell">
                                    {{ $ocorrencia->documento ?? '—' }}
                                </td>
                                <td class="hidden px-6 py-4 whitespace-nowrap text-zinc-600 dark:text-zinc-400 lg:table-cell">
                                    {{ $ocorrencia->numero_ro ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Ver --}}
                                        <button type="button"
                                                onclick="openViewModal(this)"
                                                data-view="{{ json_encode([
                                                    'id'               => $ocorrencia->id_ocorrencia,
                                                    'veiculo'          => ($equipamento->placa).($equipamento->prefixo ? ' — '.$equipamento->prefixo : ''),
                                                    'tipo'             => $ocorrencia->tipo ? titulo($ocorrencia->tipo->descricao) : '—',
                                                    'status'           => $ocorrencia->status_ocorrencia,
                                                    'responsavel'      => $ocorrencia->responsavel ? titulo($ocorrencia->responsavel->nome) : '—',
                                                    'justificativa'    => $ocorrencia->justificativa ? titulo($ocorrencia->justificativa->descricao) : '—',
                                                    'data_hora_inicio' => $ocorrencia->data_hora_inicio->format('d/m/Y H:i'),
                                                    'data_hora_fim'    => $ocorrencia->data_hora_fim?->format('d/m/Y H:i') ?? '—',
                                                    'documento'        => $ocorrencia->documento ?? '—',
                                                    'numero_ro'        => $ocorrencia->numero_ro ?? '—',
                                                    'observacao'       => $ocorrencia->observacao ?? '—',
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
                                                data-ocorrencia="{{ json_encode([
                                                    'id'               => $ocorrencia->id_ocorrencia,
                                                    'id_tipo'          => $ocorrencia->id_tipo,
                                                    'id_responsavel'   => $ocorrencia->id_responsavel,
                                                    'id_justificativa' => $ocorrencia->id_justificativa,
                                                    'data_hora_inicio' => $ocorrencia->data_hora_inicio?->format('Y-m-d\TH:i'),
                                                    'data_hora_fim'    => $ocorrencia->data_hora_fim?->format('Y-m-d\TH:i'),
                                                    'documento'        => $ocorrencia->documento,
                                                    'numero_ro'        => $ocorrencia->numero_ro,
                                                    'observacao'       => $ocorrencia->observacao,
                                                ]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                       border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                       dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                            Editar
                                        </button>

                                        @if(auth()->user()->role !== \App\Enums\UserRole::Operador || $ocorrencia->created_by === auth()->id())
                                        <form method="POST" action="{{ route('ocorrencias.destroy', $ocorrencia) }}"
                                              data-confirm="true" data-user-name="ocorrência #{{ $ocorrencia->id_ocorrencia }}">
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
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($ocorrencias->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $ocorrencias->links() }}
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
                <h3 id="view-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Ocorrência</h3>
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
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Tipo</p>
                    <p id="view-tipo" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Responsável</p>
                    <p id="view-responsavel" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Justificativa</p>
                    <p id="view-justificativa" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
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
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Nº RO</p>
                    <p id="view-numero-ro" class="mt-1 text-sm text-zinc-700 dark:text-zinc-300"></p>
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
    <div id="ocorrencia-backdrop"
         onclick="closeOcorrenciaModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    {{-- ─── Modal criar / editar ────────────────────────────────────────────────── --}}
    <div id="ocorrencia-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-2xl -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl
                border-slate-200 bg-white
                dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b px-6 py-4 border-slate-200 dark:border-zinc-800">
            <div>
                <h3 id="modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Nova Ocorrência</h3>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $equipamento->placa }}{{ $equipamento->prefixo ? ' — '.$equipamento->prefixo : '' }}
                </p>
            </div>
            <button type="button" onclick="closeOcorrenciaModal()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                           dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Scrollable body --}}
        <div class="max-h-[70vh] overflow-y-auto">
            <form id="ocorrencia-form" method="POST" action="{{ route('ocorrencias.store') }}" novalidate>
                @csrf
                <input type="hidden" id="modal-method"        name="_method"       value="">
                <input type="hidden" id="modal-id-ocorrencia" name="id_ocorrencia"  value="">
                <input type="hidden"                           name="id_veiculo"    value="{{ $equipamento->id }}">

                <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2">

                    {{-- Tipo --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-id-tipo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tipo de Ocorrência <span class="text-red-500">*</span>
                        </label>
                        <select id="modal-id-tipo" name="id_tipo"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       {{ $errors->has('id_tipo') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <option value="">— Selecione —</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id_tipo }}">{{ titulo($tipo->descricao) }}</option>
                            @endforeach
                        </select>
                        @error('id_tipo')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Responsável --}}
                    <div class="space-y-1.5">
                        <label for="modal-id-responsavel" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Responsável <span class="text-red-500">*</span>
                        </label>
                        <select id="modal-id-responsavel" name="id_responsavel"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       {{ $errors->has('id_responsavel') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <option value="">— Nenhum —</option>
                            @foreach($responsaveis as $responsavel)
                                <option value="{{ $responsavel->id_responsavel }}">
                                    {{ titulo($responsavel->nome) }} ({{ $responsavel->tipo->label() }})
                                </option>
                            @endforeach
                        </select>
                        @error('id_responsavel')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Justificativa --}}
                    <div class="space-y-1.5">
                        <label for="modal-id-justificativa" id="modal-label-justificativa"
                               class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Justificativa <span class="text-red-500">*</span>
                        </label>
                        <select id="modal-id-justificativa" name="id_justificativa"
                                class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                       bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                       {{ $errors->has('id_justificativa') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            <option value="">— Nenhuma —</option>
                            @foreach($justificativas as $justificativa)
                                <option value="{{ $justificativa->id_justificativa }}"
                                        data-obrigar="{{ $justificativa->obrigar_observacao ? '1' : '0' }}">
                                    {{ titulo($justificativa->descricao) }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_justificativa')
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

                    {{-- Documento --}}
                    <div class="space-y-1.5">
                        <label for="modal-documento" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Documento</label>
                        <input id="modal-documento" type="text" name="documento" maxlength="100"
                               placeholder="Ex.: 509488769, 6100026000…"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                      {{ $errors->has('documento') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('documento')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Número RO --}}
                    <div class="space-y-1.5">
                        <label for="modal-numero-ro" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nº RO</label>
                        <input id="modal-numero-ro" type="text" name="numero_ro" maxlength="100"
                               placeholder="Ex.: 12345/2026…"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                      bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                      {{ $errors->has('numero_ro') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('numero_ro')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Observação --}}
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="modal-observacao" id="modal-label-observacao"
                               class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observação</label>
                        <textarea id="modal-observacao" name="observacao" rows="3"
                                  class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                         bg-white text-zinc-900 placeholder:text-zinc-400 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500
                                         {{ $errors->has('observacao') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"></textarea>
                        @error('observacao')
                            <p class="modal-field-error text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t px-6 py-4 border-slate-100 dark:border-zinc-800">
                    <button type="button" onclick="closeOcorrenciaModal()"
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
                        <span id="modal-submit-label">Registrar Ocorrência</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var storeUrl   = '{{ route('ocorrencias.store') }}';
        var updateBase = '{{ url('ocorrencias') }}';
        var tiposMap   = @json($tiposMap);

        var justSel     = document.getElementById('modal-id-justificativa');
        var tipoSel     = document.getElementById('modal-id-tipo');
        var obsArea     = document.getElementById('modal-observacao');
        var obsLabel    = document.getElementById('modal-label-observacao');
        var allJustOpts = Array.from(justSel.options).filter(function (o) { return o.value !== ''; });

        // ─── Justificativa filter ────────────────────────────────────────────────
        function filterJustificativas() {
            var tipoId  = tipoSel.value;
            var linked  = tipoId ? (tiposMap[tipoId] || []) : [];
            var limited = linked.length > 0;
            var current = justSel.value;

            while (justSel.options.length > 1) { justSel.remove(1); }

            allJustOpts.forEach(function (opt) {
                if (! limited || linked.indexOf(Number(opt.value)) !== -1) {
                    justSel.appendChild(opt.cloneNode(true));
                }
            });

            justSel.value = current;
            if (justSel.value !== current) { justSel.value = ''; }

            updateObsRequired();
        }

        function updateObsRequired() {
            var sel     = justSel.selectedOptions[0];
            var obrigar = sel && sel.dataset.obrigar === '1';
            obsArea.required = obrigar;
            var star = obsLabel.querySelector('.obs-star');
            if (obrigar && ! star) {
                var s = document.createElement('span');
                s.className = 'obs-star text-red-500';
                s.textContent = ' *';
                obsLabel.appendChild(s);
            } else if (! obrigar && star) {
                star.remove();
            }
        }

        tipoSel.addEventListener('change', filterJustificativas);
        justSel.addEventListener('change', updateObsRequired);

        // ─── Limpar erros ────────────────────────────────────────────────────────
        var errorBorderClasses  = ['border-red-400', 'focus:border-red-500', 'focus:ring-red-500/10', 'dark:border-red-700'];
        var normalBorderClasses = ['border-slate-300', 'focus:border-zinc-900', 'focus:ring-zinc-900/10', 'dark:border-zinc-700', 'dark:focus:border-zinc-400', 'dark:focus:ring-zinc-400/10'];
        var modalFieldIds       = ['modal-id-tipo', 'modal-id-responsavel', 'modal-id-justificativa', 'modal-data-inicio', 'modal-data-fim', 'modal-documento', 'modal-numero-ro', 'modal-observacao'];

        function clearModalErrors() {
            document.querySelectorAll('.modal-field-error').forEach(function (el) { el.remove(); });
            modalFieldIds.forEach(function (id) {
                var el = document.getElementById(id);
                if (! el) { return; }
                errorBorderClasses.forEach(function (c) { el.classList.remove(c); });
                normalBorderClasses.forEach(function (c) { el.classList.add(c); });
            });
        }

        // ─── Reset form ──────────────────────────────────────────────────────────
        function resetForm() {
            document.getElementById('modal-id-ocorrencia').value = '';
            tipoSel.value                                         = '';
            document.getElementById('modal-id-responsavel').value = '';
            document.getElementById('modal-data-inicio').value    = '';
            document.getElementById('modal-data-fim').value       = '';
            document.getElementById('modal-documento').value      = '';
            document.getElementById('modal-numero-ro').value      = '';
            obsArea.value                                          = '';
            filterJustificativas();
        }

        // ─── Open / close ────────────────────────────────────────────────────────
        function openModal() {
            document.getElementById('ocorrencia-backdrop').classList.remove('hidden');
            document.getElementById('ocorrencia-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        window.closeOcorrenciaModal = function () {
            document.getElementById('ocorrencia-backdrop').classList.add('hidden');
            document.getElementById('ocorrencia-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.openCreateModal = function () {
            clearModalErrors();
            document.getElementById('modal-title').textContent    = 'Nova Ocorrência';
            document.getElementById('modal-submit-btn').innerHTML = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg><span>Registrar Ocorrência</span>';
            document.getElementById('modal-method').value         = '';
            document.getElementById('ocorrencia-form').action     = storeUrl;
            resetForm();
            openModal();
            tipoSel.focus();
        };

        window.openEditModal = function (btn) {
            clearModalErrors();
            var el = btn.closest('[data-ocorrencia]');
            if (! el) { return; }
            var data = JSON.parse(el.dataset.ocorrencia);

            document.getElementById('modal-title').textContent         = 'Editar Ocorrência';
            document.getElementById('modal-submit-btn').innerHTML      = '<span>Salvar Alterações</span>';
            document.getElementById('modal-method').value              = 'PUT';
            document.getElementById('modal-id-ocorrencia').value       = data.id;
            document.getElementById('ocorrencia-form').action          = updateBase + '/' + data.id;

            tipoSel.value                                               = data.id_tipo         || '';
            document.getElementById('modal-id-responsavel').value      = data.id_responsavel   || '';
            document.getElementById('modal-data-inicio').value         = data.data_hora_inicio  || '';
            document.getElementById('modal-data-fim').value            = data.data_hora_fim     || '';
            document.getElementById('modal-documento').value           = data.documento         || '';
            document.getElementById('modal-numero-ro').value           = data.numero_ro         || '';
            obsArea.value                                               = data.observacao        || '';

            filterJustificativas();
            justSel.value = data.id_justificativa || '';
            updateObsRequired();

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

            document.getElementById('view-title').textContent         = 'Ocorrência #' + data.id;
            document.getElementById('view-veiculo').textContent       = data.veiculo;
            document.getElementById('view-tipo').textContent          = data.tipo;
            document.getElementById('view-responsavel').textContent   = data.responsavel;
            document.getElementById('view-justificativa').textContent = data.justificativa;
            document.getElementById('view-inicio').textContent        = data.data_hora_inicio;
            document.getElementById('view-fim').textContent           = data.data_hora_fim;
            document.getElementById('view-documento').textContent     = data.documento;
            document.getElementById('view-numero-ro').textContent     = data.numero_ro;
            document.getElementById('view-observacao').textContent    = data.observacao;

            var badge = document.getElementById('view-status-badge');
            if (data.status === 'Em Aberto') {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Em Aberto</span>';
            } else {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Fechada</span>';
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
            var idOcor = old.id_ocorrencia;

            if (isEdit && idOcor) {
                document.getElementById('ocorrencia-form').action     = updateBase + '/' + idOcor;
                document.getElementById('modal-method').value         = 'PUT';
                document.getElementById('modal-id-ocorrencia').value  = idOcor;
                document.getElementById('modal-title').textContent    = 'Editar Ocorrência';
                document.getElementById('modal-submit-btn').innerHTML = '<span>Salvar Alterações</span>';
            }

            if (old.id_tipo)          { tipoSel.value                                          = old.id_tipo; }
            if (old.id_responsavel)   { document.getElementById('modal-id-responsavel').value  = old.id_responsavel; }
            if (old.data_hora_inicio) { document.getElementById('modal-data-inicio').value     = old.data_hora_inicio; }
            if (old.data_hora_fim)    { document.getElementById('modal-data-fim').value        = old.data_hora_fim; }
            if (old.documento)        { document.getElementById('modal-documento').value       = old.documento; }
            if (old.numero_ro)        { document.getElementById('modal-numero-ro').value       = old.numero_ro; }
            if (old.observacao)       { obsArea.value                                           = old.observacao; }

            filterJustificativas();
            if (old.id_justificativa) { justSel.value = old.id_justificativa; }
            updateObsRequired();

            openModal();
        })();
        @endif

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                window.closeOcorrenciaModal();
                window.closeViewModal();
            }
        });
    })();
    </script>

</x-layouts.app>
