<x-layouts.app title="Tipos de Etapa">

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Tipos de Etapa</h1>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Gerencie os tipos de etapa disponíveis para registro.</p>
        </div>
        <button type="button" onclick="openCreateModal()"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all active:scale-[0.98]
                       bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Novo Tipo
        </button>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('tipos-etapa.index') }}"
          class="mt-4 flex flex-wrap items-end gap-3">
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome…"
                   class="rounded-lg border px-3 py-1.5 text-sm border-slate-200 bg-white text-zinc-700
                          dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                          focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
            <select name="ativo"
                    class="rounded-lg border px-3 py-1.5 text-sm border-slate-200 bg-white text-zinc-700
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                           focus:outline-none focus:ring-2 focus:ring-zinc-900/20">
                <option value="">Todos</option>
                <option value="1" @selected(request('ativo') === '1')>Ativos</option>
                <option value="0" @selected(request('ativo') === '0')>Inativos</option>
            </select>
        </div>
        <button type="submit"
                class="rounded-lg border px-4 py-1.5 text-sm font-medium
                       border-zinc-900 bg-zinc-900 text-white hover:bg-zinc-700
                       dark:border-white dark:bg-white dark:text-zinc-900">
            Filtrar
        </button>
        @if(request()->hasAny(['search', 'ativo']))
            <a href="{{ route('tipos-etapa.index') }}"
               class="rounded-lg border px-4 py-1.5 text-sm font-medium
                      border-slate-200 bg-white text-zinc-600 hover:bg-zinc-50
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                Limpar
            </a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="mt-4 overflow-hidden rounded-xl border shadow-sm border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
        @if($tiposEtapa->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhum tipo de etapa cadastrado</h3>
                <button type="button" onclick="openCreateModal()"
                        class="mt-4 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold
                               bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Criar primeiro tipo
                </button>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Nome</th>
                            <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Cerca</th>
                            <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Status</th>
                            <th class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($tiposEtapa as $tipo)
                            <tr class="hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">{{ $tipo->nome }}</td>
                                <td class="px-6 py-4">
                                    @if($tipo->necessita_cerca)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>Obrigatória
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>Opcional
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($tipo->ativo)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Ativo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>Inativo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                onclick="openEditModal(this)"
                                                data-tipo="{{ json_encode(['id' => $tipo->getRouteKey(), 'nome' => $tipo->nome, 'necessita_cerca' => $tipo->necessita_cerca, 'ativo' => $tipo->ativo]) }}"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium
                                                       border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('tipos-etapa.destroy', $tipo) }}"
                                              data-confirm="true" data-user-name="{{ $tipo->nome }}">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium
                                                           border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
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
            @if($tiposEtapa->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $tiposEtapa->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ─── Modal backdrop + Modal ─────────────────────────────────────────────── --}}
    <div id="tipo-backdrop" onclick="closeModal()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    <div id="tipo-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-md -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">

        <div class="flex items-center justify-between border-b px-6 py-4 border-slate-200 dark:border-zinc-800">
            <h3 id="modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Novo Tipo de Etapa</h3>
            <button type="button" onclick="closeModal()"
                    class="rounded-lg p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="tipo-form" method="POST" action="{{ route('tipos-etapa.store') }}" novalidate>
            @csrf
            <input type="hidden" id="modal-method" name="_method" value="">
            <div class="space-y-4 px-6 py-5">
                <div class="space-y-1.5">
                    <label for="modal-nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Nome <span class="text-red-500">*</span>
                    </label>
                    <input id="modal-nome" type="text" name="nome" maxlength="150"
                           placeholder="Ex.: Aguardando Carregamento…"
                           class="block w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition-all focus:ring-2
                                  bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100
                                  {{ $errors->has('nome') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10 dark:border-red-700' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                    @error('nome')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center gap-2">
                    <input id="modal-necessita-cerca" type="checkbox" name="necessita_cerca" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-zinc-900 focus:ring-zinc-900/20
                                  dark:border-zinc-600 dark:bg-zinc-800 dark:ring-offset-zinc-900">
                    <label for="modal-necessita-cerca" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Exige preenchimento de cerca
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <input id="modal-ativo" type="checkbox" name="ativo" value="1" checked
                           class="h-4 w-4 rounded border-slate-300 text-zinc-900 focus:ring-zinc-900/20
                                  dark:border-zinc-600 dark:bg-zinc-800 dark:ring-offset-zinc-900">
                    <label for="modal-ativo" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ativo</label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t px-6 py-4 border-slate-100 dark:border-zinc-800">
                <button type="button" onclick="closeModal()"
                        class="rounded-lg border px-4 py-2.5 text-sm font-medium
                               border-slate-200 text-zinc-700 hover:bg-slate-50
                               dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700
                               dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <span id="modal-submit-label">Criar</span>
                </button>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var storeUrl   = '{{ route('tipos-etapa.store') }}';
        var updateBase = '{{ url('tipos-etapa') }}';

        function openModal() {
            document.getElementById('tipo-backdrop').classList.remove('hidden');
            document.getElementById('tipo-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        window.closeModal = function () {
            document.getElementById('tipo-backdrop').classList.add('hidden');
            document.getElementById('tipo-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.openCreateModal = function () {
            document.getElementById('modal-title').textContent         = 'Novo Tipo de Etapa';
            document.getElementById('modal-submit-label').textContent  = 'Criar';
            document.getElementById('modal-method').value              = '';
            document.getElementById('tipo-form').action               = storeUrl;
            document.getElementById('modal-nome').value               = '';
            document.getElementById('modal-necessita-cerca').checked  = false;
            document.getElementById('modal-ativo').checked            = true;
            openModal();
            document.getElementById('modal-nome').focus();
        };

        window.openEditModal = function (btn) {
            var el = btn.closest('[data-tipo]');
            if (! el) { return; }
            var data = JSON.parse(el.dataset.tipo);

            document.getElementById('modal-title').textContent         = 'Editar Tipo de Etapa';
            document.getElementById('modal-submit-label').textContent  = 'Salvar';
            document.getElementById('modal-method').value              = 'PUT';
            document.getElementById('tipo-form').action               = updateBase + '/' + data.id;
            document.getElementById('modal-nome').value               = data.nome;
            document.getElementById('modal-necessita-cerca').checked  = !! data.necessita_cerca;
            document.getElementById('modal-ativo').checked            = !! data.ativo;
            openModal();
            document.getElementById('modal-nome').focus();
        };

        @if($errors->any())
        (function () {
            var old = @json(old());
            if (old._method === 'PUT') {
                document.getElementById('modal-title').textContent        = 'Editar Tipo de Etapa';
                document.getElementById('modal-submit-label').textContent = 'Salvar';
            }
            if (old.nome)            { document.getElementById('modal-nome').value             = old.nome; }
            if (old.necessita_cerca) { document.getElementById('modal-necessita-cerca').checked = true; }
            if (old.ativo)           { document.getElementById('modal-ativo').checked           = true; }
            openModal();
        })();
        @endif

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { window.closeModal(); }
        });
    })();
    </script>

</x-layouts.app>
