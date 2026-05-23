<x-layouts.app title="Novo Reporte">

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('reportes.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Novo Reporte</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Preencha os dados e adicione os veículos do reporte.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('reportes.store') }}" id="reporte-form" novalidate>
        @csrf
        <input type="hidden" name="salvar_como" id="salvar_como" value="publicado">

        {{-- ─── Dados do Reporte ───────────────────────────────────────────── --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Dados do Reporte</h3>

            <div class="max-w-lg">
                <label for="nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Nome do Reporte <span class="text-red-500">*</span>
                </label>
                <input
                    id="nome"
                    type="text"
                    name="nome"
                    value="{{ old('nome') }}"
                    autofocus
                    placeholder="Ex: Reporte Matutino 23/05"
                    class="mt-1.5 block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                           placeholder:text-zinc-400 focus:ring-2
                           {{ $errors->has('nome')
                               ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20'
                               : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"
                />
                @error('nome')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ─── Veículos ───────────────────────────────────────────────────── --}}
        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Veículos</h3>
                <button type="button" onclick="addItem()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5
                               text-sm font-medium text-zinc-700 shadow-xs transition-all
                               hover:border-slate-300 hover:bg-slate-50
                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Adicionar Veículo
                </button>
            </div>

            @error('itens')
                <p class="mb-3 text-sm text-red-500">{{ $message }}</p>
            @enderror

            <div id="itens-container" class="space-y-4">
                {{-- Cards adicionados via JS --}}
            </div>

            <button type="button" onclick="addItem()"
                    id="add-btn-bottom"
                    class="mt-4 hidden w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-3
                           text-sm font-medium text-zinc-400 transition-colors
                           hover:border-zinc-300 hover:text-zinc-600
                           dark:border-zinc-800 dark:hover:border-zinc-600 dark:hover:text-zinc-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Adicionar mais um veículo
            </button>
        </div>

        {{-- ─── Ações ──────────────────────────────────────────────────────── --}}
        <div class="mt-6 flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <a href="{{ route('reportes.index') }}"
               class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                      border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                Cancelar
            </a>
            <button type="button" onclick="submitAs('rascunho')"
                    class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                           border-zinc-300 bg-white text-zinc-700 hover:bg-slate-50
                           dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Salvar Rascunho
            </button>
            <button type="button" onclick="submitAs('publicado')"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all
                           hover:bg-zinc-700 active:scale-[0.98]
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Publicar
            </button>
        </div>
    </form>

    {{-- Datalist de prefixos --}}
    <datalist id="prefixos-motorizado">
        @foreach($prefixosMotorizados as $eq)
            <option value="{{ $eq->prefixo }}">{{ $eq->prefixo }}{{ $eq->placa ? ' — '.$eq->placa : '' }}</option>
        @endforeach
    </datalist>

    {{-- Status operacionais para o JS --}}
    @php $statusOpcoes = $statusOperacionais->pluck('nome')->all(); @endphp

    <script>
    (function () {
        var statusOpcoes  = @json($statusOpcoes);
        var oldItens      = @json(old('itens', []));
        var container     = document.getElementById('itens-container');
        var addBtnBottom  = document.getElementById('add-btn-bottom');
        var itemIndex     = 0;

        function fieldClass(hasError) {
            var base = 'block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2 ';
            return hasError
                ? base + 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:bg-zinc-800 dark:text-zinc-100'
                : base + 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10';
        }

        function errorHtml(msg) {
            return msg ? '<p class="mt-1 text-xs text-red-500">' + msg + '</p>' : '';
        }

        function buildStatusOptions(selected) {
            return statusOpcoes.map(function (s) {
                var sel = selected && selected === s ? ' selected' : '';
                return '<option value="' + s + '"' + sel + '>' + s + '</option>';
            }).join('');
        }

        function buildCard(idx, data, errors) {
            data    = data    || {};
            errors  = errors  || {};

            var eP  = errors['itens.' + idx + '.prefixo']            || '';
            var eS  = errors['itens.' + idx + '.status_operacional']  || '';
            var eC  = errors['itens.' + idx + '.primeiro_contato']    || '';
            var eO  = errors['itens.' + idx + '.observacao']          || '';

            return '<div id="item-' + idx + '" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">' +

                {{-- Header do card --}}
                '<div class="mb-4 flex items-center justify-between">' +
                    '<span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Veículo ' + (idx + 1) + '</span>' +
                    '<button type="button" onclick="removeItem(' + idx + ')" ' +
                            'class="inline-flex items-center gap-1 rounded-lg border border-red-100 px-2.5 py-1 text-xs font-medium text-red-500 transition-colors hover:bg-red-50 dark:border-red-900/40 dark:hover:bg-red-950/30">' +
                        '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>' +
                        '</svg>' +
                        'Remover' +
                    '</button>' +
                '</div>' +

                {{-- Linha 1: campos obrigatórios --}}
                '<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Prefixo <span class="text-red-500">*</span></label>' +
                        '<input type="text" name="itens[' + idx + '][prefixo]" list="prefixos-motorizado" autocomplete="off"' +
                               ' value="' + (data.prefixo || '') + '"' +
                               ' placeholder="Ex: CAV-001"' +
                               ' class="mt-1 ' + fieldClass(eP) + '">' +
                        errorHtml(eP) +
                    '</div>' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Status Operacional <span class="text-red-500">*</span></label>' +
                        '<select name="itens[' + idx + '][status_operacional]" class="mt-1 ' + fieldClass(eS) + '">' +
                            '<option value="">— Selecione —</option>' +
                            buildStatusOptions(data.status_operacional) +
                        '</select>' +
                        errorHtml(eS) +
                    '</div>' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">1º Contato <span class="text-red-500">*</span></label>' +
                        '<input type="text" name="itens[' + idx + '][primeiro_contato]"' +
                               ' value="' + (data.primeiro_contato || '') + '"' +
                               ' placeholder="Nome do responsável"' +
                               ' class="mt-1 ' + fieldClass(eC) + '">' +
                        errorHtml(eC) +
                    '</div>' +

                '</div>' +

                {{-- Linha 2: campos opcionais --}}
                '<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Documento</label>' +
                        '<input type="text" name="itens[' + idx + '][documento]"' +
                               ' value="' + (data.documento || '') + '"' +
                               ' placeholder="Nº do documento"' +
                               ' class="mt-1 ' + fieldClass('') + '">' +
                    '</div>' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Tempo Parado</label>' +
                        '<input type="text" name="itens[' + idx + '][tempo_parado]"' +
                               ' value="' + (data.tempo_parado || '') + '"' +
                               ' placeholder="Ex: 2h30"' +
                               ' class="mt-1 ' + fieldClass('') + '">' +
                    '</div>' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">2º Contato</label>' +
                        '<input type="text" name="itens[' + idx + '][segundo_contato]"' +
                               ' value="' + (data.segundo_contato || '') + '"' +
                               ' placeholder="Nome do segundo responsável"' +
                               ' class="mt-1 ' + fieldClass('') + '">' +
                    '</div>' +

                '</div>' +

                {{-- Linha 3: previsão + observação --}}
                '<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">' +

                    '<div>' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Previsão</label>' +
                        '<input type="datetime-local" name="itens[' + idx + '][data_hora_previsao]"' +
                               ' value="' + (data.data_hora_previsao || '') + '"' +
                               ' class="mt-1 ' + fieldClass('') + '">' +
                    '</div>' +

                    '<div class="sm:col-span-3">' +
                        '<label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Observação <span class="text-red-500">*</span></label>' +
                        '<input type="text" name="itens[' + idx + '][observacao]"' +
                               ' value="' + (data.observacao || '') + '"' +
                               ' placeholder="Observações adicionais"' +
                               ' class="mt-1 ' + fieldClass(eO) + '">' +
                        errorHtml(eO) +
                    '</div>' +

                '</div>' +
            '</div>';
        }

        window.addItem = function () {
            container.insertAdjacentHTML('beforeend', buildCard(itemIndex, {}, {}));
            itemIndex++;
            updateAddBtn();
        };

        window.removeItem = function (idx) {
            var card = document.getElementById('item-' + idx);
            if (card) { card.remove(); }
            updateAddBtn();
        };

        function updateAddBtn() {
            var hasCards = container.children.length > 0;
            addBtnBottom.classList.toggle('hidden', ! hasCards);
            addBtnBottom.classList.toggle('flex', hasCards);
        }

        window.submitAs = function (tipo) {
            document.getElementById('salvar_como').value = tipo;
            document.getElementById('reporte-form').submit();
        };

        // ─── Restaurar itens após erro de validação ─────────────────────────
        @if($errors->any())
        (function () {
            var erros = @json($errors->messages());
            oldItens.forEach(function (data, idx) {
                container.insertAdjacentHTML('beforeend', buildCard(idx, data, erros));
                itemIndex = idx + 1;
            });
            updateAddBtn();
        })();
        @else
        addItem();
        @endif
    })();
    </script>

</x-layouts.app>
