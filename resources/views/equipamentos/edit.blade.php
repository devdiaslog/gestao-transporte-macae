<x-layouts.app title="Editar Equipamento">

    {{-- Page header --}}
    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('equipamentos.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Equipamento</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Atualize os dados do equipamento <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->placa }}</span>.</p>
        </div>
    </div>

    {{-- Form card --}}
    <div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

        <form method="POST" action="{{ route('equipamentos.update', $equipamento) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            {{-- Linha: Tipo + Modelo --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Tipo --}}
                <div class="space-y-1.5">
                    <label for="tipo_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="tipo_id"
                        name="tipo_id"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200 focus:ring-2
                               {{ $errors->has('tipo_id')
                                   ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100'
                                   : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        <option value="">Selecione…</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo->id }}" @selected(old('tipo_id', $equipamento->tipo_id) == $tipo->id)>{{ $tipo->nome }}</option>
                        @endforeach
                    </select>
                    @error('tipo_id')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Modelo --}}
                <div class="space-y-1.5">
                    <label for="modelo_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Modelo
                    </label>
                    <select
                        id="modelo_id"
                        name="modelo_id"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200 focus:ring-2
                               border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">Nenhum</option>
                        @foreach($modelos as $modelo)
                            <option value="{{ $modelo->id }}"
                                    data-tipo="{{ $modelo->tipo_equipamento_id }}"
                                    @selected(old('modelo_id', $equipamento->modelo_id) == $modelo->id)>
                                {{ $modelo->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Linha: Divisão + Subdivisão --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Divisão --}}
                <div class="space-y-1.5">
                    <label for="divisao_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Divisão
                    </label>
                    <select
                        id="divisao_id"
                        name="divisao_id"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200 focus:ring-2
                               border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">Nenhuma</option>
                        @foreach($divisoes as $divisao)
                            <option value="{{ $divisao->id }}" @selected(old('divisao_id', $equipamento->divisao_id) == $divisao->id)>{{ $divisao->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subdivisão --}}
                <div class="space-y-1.5">
                    <label for="sub_divisao_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Subdivisão
                    </label>
                    <select
                        id="sub_divisao_id"
                        name="sub_divisao_id"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200 focus:ring-2
                               border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">Nenhuma</option>
                        @foreach($subDivisoes as $sub)
                            <option value="{{ $sub->id }}"
                                    data-divisao="{{ $sub->divisao_id }}"
                                    @selected(old('sub_divisao_id', $equipamento->sub_divisao_id) == $sub->id)>
                                {{ $sub->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Linha: Placa + Prefixo --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Placa --}}
                <div class="space-y-1.5">
                    <label for="placa" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Placa <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="placa"
                        type="text"
                        name="placa"
                        value="{{ old('placa', $equipamento->placa) }}"
                        required
                        autofocus
                        placeholder="Ex: ABC-1234"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200
                               placeholder:text-zinc-400 focus:ring-2
                               {{ $errors->has('placa')
                                   ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100'
                                   : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"
                    />
                    @error('placa')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Prefixo --}}
                <div class="space-y-1.5">
                    <label for="prefixo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Prefixo
                    </label>
                    <input
                        id="prefixo"
                        type="text"
                        name="prefixo"
                        value="{{ old('prefixo', $equipamento->prefixo) }}"
                        placeholder="Ex: CAV-001"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200
                               placeholder:text-zinc-400 focus:ring-2
                               border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"
                    />
                </div>
            </div>

            {{-- ID Elog --}}
            <div class="space-y-1.5">
                <label for="id_elog" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    ID Elog
                </label>
                <input
                    id="id_elog"
                    type="text"
                    name="id_elog"
                    value="{{ old('id_elog', $equipamento->id_elog) }}"
                    placeholder="Código interno do sistema Elog"
                    class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-zinc-400 focus:ring-2
                           border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"
                />
            </div>

            {{-- Status --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-6">
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="radio" name="status" value="1"
                               @checked(old('status', $equipamento->status ? '1' : '0') === '1')
                               class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400/20">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativo</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="radio" name="status" value="0"
                               @checked(old('status', $equipamento->status ? '1' : '0') === '0')
                               class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400/20">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Inativo</span>
                    </label>
                </div>
                @error('status')
                    <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-zinc-800">
                <a href="{{ route('equipamentos.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5
                          text-sm font-medium text-zinc-700
                          transition-all duration-200
                          hover:border-slate-300 hover:bg-slate-50
                          dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5
                               text-sm font-semibold text-white shadow-xs
                               transition-all duration-200
                               hover:bg-zinc-700 active:scale-[0.98]
                               dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                    </svg>
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var tipoSel    = document.getElementById('tipo_id');
        var modeloSel  = document.getElementById('modelo_id');
        var divisaoSel = document.getElementById('divisao_id');
        var subSel     = document.getElementById('sub_divisao_id');

        var allModeloOptions = Array.from(modeloSel.options).slice(1);
        var allSubOptions    = Array.from(subSel.options).slice(1);

        function filterModelos() {
            var tipoId  = tipoSel.value;
            var current = modeloSel.value;

            while (modeloSel.options.length > 1) { modeloSel.remove(1); }

            allModeloOptions.forEach(function (opt) {
                if (!tipoId || opt.dataset.tipo === tipoId) {
                    modeloSel.appendChild(opt.cloneNode(true));
                }
            });

            modeloSel.value = current;
            if (!modeloSel.value) { modeloSel.value = ''; }
        }

        function filterSubDivisoes() {
            var divisaoId = divisaoSel.value;
            var current   = subSel.value;

            while (subSel.options.length > 1) { subSel.remove(1); }

            allSubOptions.forEach(function (opt) {
                if (!divisaoId || opt.dataset.divisao === divisaoId) {
                    subSel.appendChild(opt.cloneNode(true));
                }
            });

            subSel.value = current;
            if (!subSel.value) { subSel.value = ''; }
        }

        tipoSel.addEventListener('change', filterModelos);
        divisaoSel.addEventListener('change', filterSubDivisoes);

        filterModelos();
        filterSubDivisoes();
    })();
    </script>

</x-layouts.app>
