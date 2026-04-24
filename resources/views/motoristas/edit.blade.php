<x-layouts.app title="Editar Motorista">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('motoristas.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200
                  text-slate-500 transition-all duration-200
                  hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700
                  dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            <span class="sr-only">Voltar</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Motorista</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $motorista->nome }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700
                    dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-400">
            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 max-w-2xl">
        <form method="POST" action="{{ route('motoristas.update', $motorista) }}" class="space-y-6" novalidate>
            @csrf
            @method('PUT')

            {{-- Dados principais --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h3 class="mb-5 text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Dados do Motorista</h3>

                <div class="space-y-5">

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="matricula" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Matrícula <span class="text-red-500">*</span>
                            </label>
                            <input id="matricula" type="text" name="matricula" value="{{ old('matricula', $motorista->matricula) }}" autofocus
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2
                                          {{ $errors->has('matricula') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            @error('matricula')
                                <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="cpf" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                CPF <span class="text-red-500">*</span>
                            </label>
                            <input id="cpf" type="text" name="cpf" value="{{ old('cpf', $motorista->cpf) }}" placeholder="000.000.000-00"
                                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2
                                          {{ $errors->has('cpf') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                            @error('cpf')
                                <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Nome completo <span class="text-red-500">*</span>
                        </label>
                        <input id="nome" type="text" name="nome" value="{{ old('nome', $motorista->nome) }}"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2
                                      {{ $errors->has('nome') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('nome')
                            <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="cargo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Cargo <span class="text-red-500">*</span>
                        </label>
                        <input id="cargo" type="text" name="cargo" value="{{ old('cargo', $motorista->cargo) }}"
                               class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2
                                      {{ $errors->has('cargo') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        @error('cargo')
                            <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <div class="flex items-center gap-6">
                            <label class="flex cursor-pointer items-center gap-2.5">
                                <input type="radio" name="status" value="1"
                                       @checked(old('status', $motorista->status ? '1' : '0') === '1')
                                       class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativo</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2.5">
                                <input type="radio" name="status" value="0"
                                       @checked(old('status', $motorista->status ? '1' : '0') === '0')
                                       class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">Inativo</span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Contatos --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Contatos</h3>
                    <button type="button" id="btn-add-contato"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                   border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                   dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Adicionar contato
                    </button>
                </div>

                <div id="contatos-list" class="space-y-3">
                    @php
                        $contatosExibidos = old('contatos') !== null
                            ? collect(old('contatos'))->map(fn ($c) => (object) $c)
                            : $motorista->contatos;
                    @endphp

                    @forelse($contatosExibidos as $i => $contato)
                        <div class="contato-row flex items-start gap-3 rounded-xl border border-slate-200 p-4 dark:border-zinc-700/60">
                            @if(isset($contato->id))
                                <input type="hidden" name="contatos[{{ $i }}][id]" value="{{ $contato->id }}">
                            @elseif(is_array($contato) && isset($contato['id']))
                                <input type="hidden" name="contatos[{{ $i }}][id]" value="{{ $contato['id'] }}">
                            @endif
                            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Telefone</label>
                                    <input type="text" name="contatos[{{ $i }}][telefone]"
                                           value="{{ is_array($contato) ? ($contato['telefone'] ?? '') : $contato->telefone }}"
                                           placeholder="(22) 99999-9999"
                                           class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">E-mail</label>
                                    <input type="email" name="contatos[{{ $i }}][email]"
                                           value="{{ is_array($contato) ? ($contato['email'] ?? '') : $contato->email }}"
                                           placeholder="exemplo@email.com"
                                           class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                                </div>
                            </div>
                            <div class="flex flex-col items-center gap-2 pt-5">
                                <label class="flex cursor-pointer items-center gap-1.5" title="Ativo">
                                    <input type="checkbox" name="contatos[{{ $i }}][status]" value="1"
                                           @checked(is_array($contato) ? !empty($contato['status']) : $contato->status)
                                           class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Ativo</span>
                                </label>
                                <button type="button" class="btn-remove-contato rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p id="contatos-empty" class="py-6 text-center text-sm text-zinc-400 dark:text-zinc-600">
                            Nenhum contato adicionado.
                        </p>
                    @endforelse
                </div>

                @error('contatos.*.email')
                    <p class="mt-2 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <form method="POST" action="{{ route('motoristas.destroy', $motorista) }}"
                      data-confirm="true" data-user-name="{{ $motorista->nome }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-all duration-150
                                   border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50
                                   dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        Remover motorista
                    </button>
                </form>

                <div class="flex items-center gap-3">
                    <a href="{{ route('motoristas.index') }}"
                       class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all duration-200 hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Salvar alterações
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var list     = document.getElementById('contatos-list');
        var btnAdd   = document.getElementById('btn-add-contato');
        var emptyMsg = document.getElementById('contatos-empty');
        var rowIndex = {{ $contatosExibidos->count() }};

        function updateEmpty() {
            var rows = list.querySelectorAll('.contato-row');
            if (emptyMsg) { emptyMsg.style.display = rows.length === 0 ? '' : 'none'; }
        }

        function buildRow(idx) {
            var div = document.createElement('div');
            div.className = 'contato-row flex items-start gap-3 rounded-xl border border-slate-200 p-4 dark:border-zinc-700/60';
            div.innerHTML =
                '<div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">' +
                    '<div class="space-y-1">' +
                        '<label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">Telefone</label>' +
                        '<input type="text" name="contatos[' + idx + '][telefone]" placeholder="(22) 99999-9999"' +
                        ' class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">' +
                    '</div>' +
                    '<div class="space-y-1">' +
                        '<label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">E-mail</label>' +
                        '<input type="email" name="contatos[' + idx + '][email]" placeholder="exemplo@email.com"' +
                        ' class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">' +
                    '</div>' +
                '</div>' +
                '<div class="flex flex-col items-center gap-2 pt-5">' +
                    '<label class="flex cursor-pointer items-center gap-1.5" title="Ativo">' +
                        '<input type="checkbox" name="contatos[' + idx + '][status]" value="1" checked' +
                        ' class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">' +
                        '<span class="text-xs text-zinc-500 dark:text-zinc-400">Ativo</span>' +
                    '</label>' +
                    '<button type="button" class="btn-remove-contato rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-950/40 dark:hover:text-red-400">' +
                        '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                    '</button>' +
                '</div>';
            return div;
        }

        btnAdd.addEventListener('click', function () {
            var row = buildRow(rowIndex++);
            list.appendChild(row);
            updateEmpty();
            row.querySelector('input[type="text"]').focus();
        });

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remove-contato');
            if (!btn) { return; }
            btn.closest('.contato-row').remove();
            updateEmpty();
        });

        updateEmpty();
    })();
    </script>

</x-layouts.app>
