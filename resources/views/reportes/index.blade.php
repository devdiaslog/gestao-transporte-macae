<x-layouts.app title="Reportes">

    @php
        $currentBusca      = request('busca');
        $currentStatus     = request('status');
        $currentDataInicio = request('data_inicio');
        $currentDataFim    = request('data_fim');
        $hasFilters        = request()->hasAny(['busca', 'status', 'data_inicio', 'data_fim']);
        $tz                = config('app.timezone');
    @endphp

    {{-- Header --}}
    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Reportes</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Momentos de reporte operacional.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reportes.index', array_merge(request()->only(['busca','status','data_inicio','data_fim']), ['export' => 1])) }}"
               title="Exportar para CSV"
               class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2.5 text-sm font-medium shadow-xs transition-all duration-200
                      border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
                <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Exportar
            </a>
            <button type="button" onclick="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                           bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Criar Reporte
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('reportes.index') }}" class="mt-6">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Busca --}}
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Pesquisa</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/>
                    </svg>
                    <input type="text" name="busca" value="{{ $currentBusca }}" placeholder="Buscar prefixo, nome ou nº..."
                           class="w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none transition-all
                                  focus:border-zinc-400 focus:ring-1 focus:ring-zinc-300
                                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500">
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status</label>
                <select name="status"
                        onchange="this.form.submit()"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition-all
                               focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="">Todos</option>
                    <option value="publicado" @selected($currentStatus === 'publicado')>Publicado</option>
                    <option value="rascunho"  @selected($currentStatus === 'rascunho')>Rascunho</option>
                </select>
            </div>

            {{-- Data início --}}
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Data Início</label>
                <input type="date" name="data_inicio" value="{{ $currentDataInicio }}"
                       onchange="this.form.submit()"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition-all
                              focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            </div>

            {{-- Data fim --}}
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Data Fim</label>
                <input type="date" name="data_fim" value="{{ $currentDataFim }}"
                       onchange="this.form.submit()"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition-all
                              focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            </div>
        </div>

        <div class="mt-2 flex items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700
                           hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Filtrar
            </button>
            @if($hasFilters)
                <a href="{{ route('reportes.index') }}"
                   class="text-sm text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    Limpar filtros
                </a>
            @endif
        </div>
    </form>

    {{-- Tabela --}}
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        @if($itens->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <svg class="h-10 w-10 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
                <p class="mt-4 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ $hasFilters ? 'Nenhum resultado para os filtros aplicados.' : 'Nenhum reporte criado ainda.' }}
                </p>
                @if(! $hasFilters)
                    <button type="button" onclick="openCreateModal()"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                                   bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                        Criar primeiro reporte
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Nº Reporte</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Nome</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Prefixo</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Status Op.</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Tempo Parado</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Observação</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Emissão</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Emitido Por</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                        @foreach($itens as $item)
                            @php $reporte = $item->reporte; @endphp
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('reportes.show', $reporte) }}" target="_blank"
                                       class="font-mono text-xs font-semibold text-zinc-700 underline decoration-dotted underline-offset-2
                                              hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-zinc-100">
                                        {{ $reporte?->numero_reporte ?? '—' }}
                                    </a>
                                    @if($reporte?->status === 'rascunho')
                                        <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:ring-amber-800/50">
                                            Rascunho
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $reporte?->nome ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $item->prefixo ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $item->status_operacional ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $item->tempo_parado ?? '—' }}</td>
                                <td class="max-w-xs px-4 py-3 text-zinc-500 dark:text-zinc-500">
                                    <span class="line-clamp-2">{{ $item->observacao ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $reporte?->data_hora_emissao?->setTimezone($tz)->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $reporte?->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($loop->last || $itens[$loop->index + 1]->reporte_id !== $item->reporte_id)
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    onclick="openEditModal({{ $reporte->id }}, '{{ route('reportes.data', $reporte) }}', '{{ route('reportes.update', $reporte) }}', '{{ $reporte->numero_reporte }}')"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium
                                                           text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                                </svg>
                                                Editar
                                            </button>
                                            <form action="{{ route('reportes.destroy', $reporte) }}" method="POST"
                                                  data-confirm="true"
                                                  data-user-name="o reporte {{ $reporte?->numero_reporte }} (todos os itens serão removidos)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium
                                                               text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                    </svg>
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($itens->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 dark:border-zinc-800">
                    {{ $itens->links() }}
                </div>
            @endif

            {{-- Datalist de prefixos motorizados --}}
            <datalist id="prefixos-motorizado">
                @foreach($prefixosMotorizados as $eq)
                    <option value="{{ $eq->prefixo }}">{{ $eq->prefixo }}{{ $eq->placa ? ' — ' . $eq->placa : '' }}</option>
                @endforeach
            </datalist>
        @endif
    </div>

    {{-- Modal de criação --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-2">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCreateModal()"></div>

        <div class="relative z-10 flex h-full w-full max-h-[98vh] flex-col rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-zinc-800">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Criar Reporte</h3>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        Nº <span id="modal-numero" class="font-mono font-semibold">—</span>
                        &nbsp;·&nbsp; <span id="modal-datetime">—</span>
                        &nbsp;·&nbsp; {{ auth()->user()->name }}
                    </p>
                </div>
                <button type="button" onclick="closeCreateModal()"
                        class="rounded-lg p-1.5 text-zinc-400 hover:bg-slate-100 hover:text-zinc-600 dark:hover:bg-zinc-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form id="create-form" action="{{ route('reportes.store') }}" method="POST" class="flex flex-1 flex-col overflow-hidden">
                @csrf
                <input type="hidden" name="_method"    id="form-method"   value="POST">
                <input type="hidden" name="salvar_como" id="salvar_como"  value="publicado">

                {{-- Nome do reporte --}}
                <div class="border-b border-slate-200 px-6 py-4 dark:border-zinc-800">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-1.5">
                        Nome do Reporte <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nome" required placeholder="Ex: Reporte Operacional — Turno Manhã"
                           class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition-all
                                  focus:border-zinc-400 focus:ring-1 focus:ring-zinc-300
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-500">
                </div>

                {{-- Tabela de itens --}}
                <div class="flex-1 overflow-auto px-6 py-4">
                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-zinc-700">
                        <table class="w-full text-sm" id="itens-table">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-zinc-800">
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-28">Prefixo</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-36">Documento</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-48">Status Operacional</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-28">Tempo Parado</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-32">Previsão</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-36">1º Contato</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-36">2º Contato</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Observação</th>
                                    <th class="px-3 py-2.5 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="itens-body" class="divide-y divide-slate-100 dark:divide-zinc-800">
                            </tbody>
                        </table>
                    </div>

                    <button type="button" onclick="addRow()"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-3 py-2
                                   text-xs font-medium text-zinc-500 hover:border-zinc-400 hover:text-zinc-700 transition-colors
                                   dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:text-zinc-300">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Incluir Linha
                    </button>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4 dark:border-zinc-800">
                    <button type="button" onclick="closeCreateModal()"
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700
                                   hover:bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                        Cancelar
                    </button>
                    <button type="button" onclick="submitForm('rascunho')"
                            class="inline-flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700
                                   hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-400 dark:hover:bg-amber-950/50">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V16.5L12 14.25 7.5 16.5V3.75m9 0H18A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0h-9"/>
                        </svg>
                        Salvar Rascunho
                    </button>
                    <button type="button" onclick="submitForm('publicado')"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                                   bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Publicar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        var statusOptions = @json($statusOperacionais->pluck('nome'));
        var rowIndex = 0;

        function buildRow(index) {
            var statusOpts = statusOptions.map(function (s) {
                return '<option value="' + s + '">' + s + '</option>';
            }).join('');

            return '<tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/40">' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][prefixo]" list="prefixos-motorizado" placeholder="Prefixo..."' +
                           ' autocomplete="off"' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][documento]" placeholder="Nº documento..."' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<select name="itens[' + index + '][status_operacional]"' +
                            ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                   ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                        '<option value="">— Selecione —</option>' + statusOpts +
                    '</select>' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][tempo_parado]" placeholder="Ex: 2h 30m"' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][data_hora_previsao]" placeholder="Ex: 14:30"' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][primeiro_contato]" placeholder="Nome / Telefone"' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][segundo_contato]" placeholder="Nome / Telefone"' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5">' +
                    '<input type="text" name="itens[' + index + '][observacao]" placeholder="Observação..."' +
                           ' class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm outline-none' +
                                  ' focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">' +
                '</td>' +
                '<td class="px-2 py-1.5 text-center">' +
                    '<button type="button" onclick="removeRow(this)"' +
                            ' class="rounded p-1 text-zinc-300 hover:text-red-500 dark:text-zinc-600 dark:hover:text-red-400">' +
                        '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>' +
                        '</svg>' +
                    '</button>' +
                '</td>' +
            '</tr>';
        }

        window.addRow = function () {
            document.getElementById('itens-body').insertAdjacentHTML('beforeend', buildRow(rowIndex++));
        };

        window.removeRow = function (btn) {
            var tbody = document.getElementById('itens-body');
            if (tbody.rows.length > 1) {
                btn.closest('tr').remove();
            }
        };

        window.submitForm = function (status) {
            document.getElementById('salvar_como').value = status;
            document.getElementById('create-form').submit();
        };

        function openModal() {
            var modal = document.getElementById('create-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        window.openCreateModal = function () {
            var now = new Date();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var dateStr = now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate());

            document.getElementById('modal-datetime').textContent =
                pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() +
                ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes());

            document.getElementById('modal-numero').textContent = dateStr + '-???';
            document.querySelector('[name="nome"]').value = '';
            document.getElementById('create-form').action = '{{ route('reportes.store') }}';
            document.getElementById('form-method').value = 'POST';

            document.getElementById('itens-body').innerHTML = '';
            rowIndex = 0;
            addRow();

            openModal();
        };

        window.openEditModal = function (id, dataUrl, updateUrl, numero) {
            fetch(dataUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    document.getElementById('modal-numero').textContent = numero;
                    document.getElementById('modal-datetime').textContent = '—';
                    document.querySelector('[name="nome"]').value = data.nome || '';
                    document.getElementById('create-form').action = updateUrl;
                    document.getElementById('form-method').value = 'PUT';

                    document.getElementById('itens-body').innerHTML = '';
                    rowIndex = 0;

                    data.itens.forEach(function (item) {
                        var idx = rowIndex++;
                        document.getElementById('itens-body').insertAdjacentHTML('beforeend', buildRow(idx));
                        var row = document.getElementById('itens-body').lastElementChild;
                        row.querySelector('[name="itens[' + idx + '][prefixo]"]').value             = item.prefixo || '';
                        row.querySelector('[name="itens[' + idx + '][documento]"]').value            = item.documento || '';
                        row.querySelector('[name="itens[' + idx + '][status_operacional]"]').value   = item.status_operacional || '';
                        row.querySelector('[name="itens[' + idx + '][tempo_parado]"]').value         = item.tempo_parado || '';
                        row.querySelector('[name="itens[' + idx + '][data_hora_previsao]"]').value   = item.data_hora_previsao || '';
                        row.querySelector('[name="itens[' + idx + '][primeiro_contato]"]').value     = item.primeiro_contato || '';
                        row.querySelector('[name="itens[' + idx + '][segundo_contato]"]').value      = item.segundo_contato || '';
                        row.querySelector('[name="itens[' + idx + '][observacao]"]').value           = item.observacao || '';
                    });

                    if (rowIndex === 0) { addRow(); }

                    openModal();
                });
        };

        window.closeCreateModal = function () {
            var modal = document.getElementById('create-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('itens-body').innerHTML = '';
            document.getElementById('form-method').value = 'POST';
            document.getElementById('create-form').action = '{{ route('reportes.store') }}';
            rowIndex = 0;
        };
    })();
    </script>
    @endpush

</x-layouts.app>
