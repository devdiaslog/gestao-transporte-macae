<x-layouts.app title="Reportes">

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Reportes</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Momentos de reporte operacional.</p>
        </div>
        <button type="button" onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5
                       text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                       bg-zinc-900 text-white hover:bg-zinc-700
                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Criar Reporte
        </button>
    </div>

    {{-- Tabela de reportes --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        @if($reportes->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <svg class="h-10 w-10 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
                <p class="mt-4 text-sm font-medium text-zinc-500 dark:text-zinc-400">Nenhum reporte criado ainda.</p>
                <button type="button" onclick="openCreateModal()"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                               bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Criar primeiro reporte
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-800/50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Nº Reporte</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Data/Hora de Emissão</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Responsável</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Itens</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                        @foreach($reportes as $reporte)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ $reporte->numero_reporte }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $reporte->data_hora_emissao->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $reporte->creator?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-500">
                                    {{ $reporte->itens_count ?? '—' }} veículos
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('reportes.destroy', $reporte) }}" method="POST"
                                          onsubmit="return confirm('Excluir o reporte {{ $reporte->numero_reporte }}?')">
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($reportes->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 dark:border-zinc-800">
                    {{ $reportes->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Modal de criação --}}
    <div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCreateModal()"></div>

        <div class="relative z-10 w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900
                    flex flex-col max-h-[90vh]">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-zinc-800">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Criar Reporte</h3>
                    <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        Nº <span id="modal-numero" class="font-mono font-semibold">—</span>
                        &nbsp;·&nbsp;
                        <span id="modal-datetime">—</span>
                        &nbsp;·&nbsp;
                        {{ auth()->user()->name }}
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
            <form id="create-form" action="{{ route('reportes.store') }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf

                {{-- Tabela de itens --}}
                <div class="flex-1 overflow-auto px-6 py-4">
                    <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-zinc-700">
                        <table class="w-full text-sm" id="itens-table">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-zinc-800">
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-[120px]">Prefixo</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-[200px]">Status Operacional</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 w-[120px]">Tempo Parado</th>
                                    <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Observação</th>
                                    <th class="px-3 py-2.5 w-8"></th>
                                </tr>
                            </thead>
                            <tbody id="itens-body" class="divide-y divide-slate-100 dark:divide-zinc-800">
                                {{-- Linhas inseridas via JS --}}
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
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                                   bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Salvar Reporte
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
                    '<input type="text" name="itens[' + index + '][prefixo]" placeholder="Ex: 1234R"' +
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

        window.openCreateModal = function () {
            var now = new Date();
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var dateStr = now.getFullYear() + '-' +
                pad(now.getMonth() + 1) + '-' +
                pad(now.getDate());
            var timeStr = pad(now.getHours()) + ':' + pad(now.getMinutes());

            document.getElementById('modal-datetime').textContent =
                pad(now.getDate()) + '/' + pad(now.getMonth() + 1) + '/' + now.getFullYear() +
                ' ' + timeStr;

            // Número provisional baseado na data (o definitivo é gerado no backend)
            document.getElementById('modal-numero').textContent =
                dateStr.replace(/-/g, '') + '-???';

            // Limpa e insere primeira linha
            document.getElementById('itens-body').innerHTML = '';
            rowIndex = 0;
            addRow();

            var modal = document.getElementById('create-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeCreateModal = function () {
            var modal = document.getElementById('create-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('itens-body').innerHTML = '';
            rowIndex = 0;
        };
    })();
    </script>
    @endpush

</x-layouts.app>
