<x-layouts.app title="Locais de Demandas">

<div class="py-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <a href="{{ route('demandas.index') }}"
                   class="text-sm text-zinc-400 transition-colors hover:text-zinc-600 dark:text-zinc-600 dark:hover:text-zinc-400">
                    Demandas
                </a>
                <svg class="h-3.5 w-3.5 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Locais</span>
            </div>
            <h2 class="mt-1 text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Locais de Demandas</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Origens e destinos disponíveis para cadastro de demandas</p>
        </div>
        <button type="button" onclick="openLocalModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white
                       shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Novo Local
        </button>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('locais.index') }}"
          class="mb-5 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nome…"
               class="h-9 w-56 rounded-lg border border-slate-200 bg-white px-3 text-sm
                      text-zinc-900 placeholder-zinc-400 shadow-xs outline-none
                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                      dark:placeholder-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
        <div class="flex items-center gap-1 rounded-lg border p-1 shadow-xs
                    border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            @foreach(['' => 'Todos', '1' => 'Ativos', '0' => 'Inativos'] as $val => $label)
                <a href="{{ route('locais.index', array_filter(['q' => request('q'), 'ativo' => $val], fn ($v) => $v !== '')) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                          {{ request('ativo', '') === (string)$val
                              ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                              : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @if(request('q'))
            <a href="{{ route('locais.index') }}"
               class="h-9 inline-flex items-center gap-1 rounded-lg px-3 text-sm text-zinc-400 hover:text-zinc-700
                      dark:hover:text-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
                Limpar
            </a>
        @endif
    </form>

    {{-- Tabela --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if($locais->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-20 text-center text-zinc-400 dark:text-zinc-600">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                <p class="text-sm">Nenhum local encontrado.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Agendamento</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Cadastrado em</th>
                        <th class="w-10 px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                    @foreach($locais as $local)
                        <tr class="group transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $local->nome }}
                            </td>
                            <td class="px-6 py-4">
                                @if($local->ativo)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700
                                                 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/50">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600
                                                 ring-1 ring-zinc-200 dark:bg-zinc-800/60 dark:text-zinc-400 dark:ring-zinc-700/50">
                                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($local->precisa_agendamento)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700
                                                 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800/50">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                        </svg>
                                        Necessário
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-600">Não necessário</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-500 dark:text-zinc-400">
                                {{ $local->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <button type="button"
                                            onclick="editLocal({{ $local->id }}, {{ json_encode(['nome' => $local->nome, 'ativo' => $local->ativo, 'precisa_agendamento' => $local->precisa_agendamento]) }})"
                                            class="rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                                                   dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                        </svg>
                                    </button>
                                    <form method="POST" action="{{ route('locais.destroy', $local) }}"
                                          data-confirm data-user-name="{{ $local->nome }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600
                                                       dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($locais->hasPages())
                <div class="border-t border-slate-100 px-6 py-3 dark:border-zinc-800">
                    {{ $locais->links() }}
                </div>
            @endif
        @endif
    </div>

    @if(! $locais->isEmpty())
        <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-600">
            {{ $locais->total() }} {{ $locais->total() === 1 ? 'local' : 'locais' }} no total
        </p>
    @endif
</div>

{{-- ─── Modal Criar / Editar Local ───────────────────────────────────────── --}}
<div id="local-modal" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true">
    <div id="local-modal-overlay"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div id="local-modal-panel"
             class="w-full max-w-md scale-95 overflow-hidden rounded-2xl border opacity-0 shadow-2xl
                    transition-all duration-200
                    border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">

            <div class="flex items-center justify-between border-b px-6 py-4
                        border-slate-100 dark:border-zinc-800">
                <h2 id="local-modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    Novo Local
                </h2>
                <button type="button" onclick="closeLocalModal()"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="local-form" class="px-6 py-5">
                @csrf
                <input type="hidden" name="_method" id="local-method" value="POST">

                <div class="space-y-4">
                    <div>
                        <label for="f-local-nome"
                               class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Nome <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="f-local-nome" name="nome" autocomplete="off"
                               placeholder="Ex: Porto de Macaé"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                      text-zinc-900 outline-none shadow-xs
                                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                      dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="ativo" value="0">
                        <input type="checkbox" id="f-local-ativo" name="ativo" value="1" checked
                               class="h-4 w-4 rounded border-slate-300 bg-white text-zinc-900 outline-none
                                      focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-900
                                      dark:focus:ring-zinc-700">
                        <label for="f-local-ativo" class="text-sm text-zinc-700 dark:text-zinc-300">
                            Local ativo (disponível para seleção)
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="precisa_agendamento" value="0">
                        <input type="checkbox" id="f-local-agendamento" name="precisa_agendamento" value="1"
                               class="h-4 w-4 rounded border-slate-300 bg-white text-zinc-900 outline-none
                                      focus:ring-2 focus:ring-zinc-200 dark:border-zinc-600 dark:bg-zinc-900
                                      dark:focus:ring-zinc-700">
                        <label for="f-local-agendamento" class="text-sm text-zinc-700 dark:text-zinc-300">
                            Requer data de agendamento nas demandas
                        </label>
                    </div>
                </div>

                <div id="local-error"
                     class="mt-4 hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700
                            dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-400"></div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeLocalModal()"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors
                                   hover:bg-zinc-100 hover:text-zinc-900
                                   dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-local-salvar"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white
                                   shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                                   dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200
                                   disabled:cursor-not-allowed disabled:opacity-60">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal   = document.getElementById('local-modal');
    var overlay = document.getElementById('local-modal-overlay');
    var panel   = document.getElementById('local-modal-panel');
    var title   = document.getElementById('local-modal-title');
    var form    = document.getElementById('local-form');
    var errBox  = document.getElementById('local-error');
    var btnSave = document.getElementById('btn-local-salvar');
    var editId  = null;

    var STORE_URL  = '{{ route('locais.store') }}';
    var BASE_URL   = '{{ url('/locais') }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';

    window.openLocalModal = function () {
        editId = null;
        title.textContent = 'Novo Local';
        form.reset();
        document.getElementById('f-local-ativo').checked = true;
        document.getElementById('local-method').value = 'POST';
        errBox.classList.add('hidden');
        openModal();
        setTimeout(function () { document.getElementById('f-local-nome').focus(); }, 200);
    };

    window.editLocal = function (id, data) {
        editId = id;
        title.textContent = 'Editar Local';
        document.getElementById('local-method').value = 'PUT';
        document.getElementById('f-local-nome').value = data.nome || '';
        document.getElementById('f-local-ativo').checked = !! data.ativo;
        document.getElementById('f-local-agendamento').checked = !! data.precisa_agendamento;
        errBox.classList.add('hidden');
        openModal();
        setTimeout(function () { document.getElementById('f-local-nome').focus(); }, 200);
    };

    window.closeLocalModal = function () {
        overlay.classList.remove('opacity-100');
        panel.classList.add('opacity-0', 'scale-95');
        panel.classList.remove('opacity-100', 'scale-100');
        setTimeout(function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    };

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            overlay.classList.add('opacity-100');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errBox.classList.add('hidden');
        btnSave.disabled = true;

        var url  = editId ? BASE_URL + '/' + editId : STORE_URL;
        var data = new FormData(form);

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: data,
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (res) {
            if (res.ok && res.data.ok) {
                window.location.reload();
            } else {
                var msgs = res.data.errors
                    ? Object.values(res.data.errors).flat().join(' ')
                    : (res.data.message || 'Erro ao salvar o local.');
                errBox.textContent = msgs;
                errBox.classList.remove('hidden');
                btnSave.disabled = false;
            }
        })
        .catch(function () {
            errBox.textContent = 'Falha na conexão. Tente novamente.';
            errBox.classList.remove('hidden');
            btnSave.disabled = false;
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target === overlay) { window.closeLocalModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.closeLocalModal(); }
    });
})();
</script>
@endpush

</x-layouts.app>
