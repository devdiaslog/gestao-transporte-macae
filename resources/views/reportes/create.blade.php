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

    <div id="draft-banner" class="mt-4"></div>

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

    {{-- Modal: veículo não localizado no Elog --}}
    <div id="elog-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="fecharElogModal()"></div>
        <div class="relative z-10 w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 ring-1 ring-amber-200 dark:bg-amber-950/40 dark:ring-amber-800/50">
                    <svg class="h-7 w-7 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Veículo não localizado</h3>
                <p id="elog-modal-msg" class="mt-2 text-sm text-zinc-500 dark:text-zinc-400"></p>
            </div>
            <button type="button" onclick="fecharElogModal()"
                    class="mt-6 w-full rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white transition-all hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Entendido
            </button>
        </div>
    </div>

    {{-- Status operacionais para o JS --}}
    @php
        $statusOpcoes  = $statusOperacionais->pluck('nome')->all();
        $prefixosList  = $prefixosMotorizados->map(fn ($eq) => ['prefixo' => $eq->prefixo, 'placa' => $eq->placa])->values();
    @endphp

    <script>
    (function () {
        var statusOpcoes   = @json($statusOpcoes);
        var prefixosList   = @json($prefixosList);
        var prefixoToPlaca = @json($prefixosMotorizados->pluck('placa', 'prefixo'));
        var BIGCORE_URL         = '{{ route('bigcore.veiculo') }}';
        var REPORTE_PREFIXO_URL = '{{ route('reportes.ultimo-por-prefixo') }}';
        var oldItens        = @json(old('itens', []));
        var container     = document.getElementById('itens-container');
        var addBtnBottom  = document.getElementById('add-btn-bottom');
        var itemIndex     = 0;

        // ─── Rascunho Local (localStorage) ──────────────────────────────────
        var DRAFT_KEY = 'reporte_draft_create';

        function coletarDadosFormulario() {
            var nome  = document.getElementById('nome').value;
            var itens = [];
            container.querySelectorAll('[id^="item-"]').forEach(function (card) {
                var idx = card.id.replace('item-', '');
                itens.push({
                    prefixo:             (card.querySelector('[name="itens[' + idx + '][prefixo]"]') || {}).value || '',
                    status_operacional:  (card.querySelector('[name="itens[' + idx + '][status_operacional]"]') || {}).value || '',
                    primeiro_contato:    (card.querySelector('[name="itens[' + idx + '][primeiro_contato]"]') || {}).value || '',
                    documento:           (card.querySelector('[name="itens[' + idx + '][documento]"]') || {}).value || '',
                    tempo_parado:        (card.querySelector('[name="itens[' + idx + '][tempo_parado]"]') || {}).value || '',
                    segundo_contato:     (card.querySelector('[name="itens[' + idx + '][segundo_contato]"]') || {}).value || '',
                    data_hora_previsao:  (card.querySelector('[name="itens[' + idx + '][data_hora_previsao]"]') || {}).value || '',
                    observacao:          (card.querySelector('[name="itens[' + idx + '][observacao]"]') || {}).value || '',
                });
            });
            return { nome: nome, itens: itens, savedAt: Date.now() };
        }

        function salvarRascunhoLocal() {
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(coletarDadosFormulario())); } catch (e) {}
        }

        window.limparRascunhoLocal = function () {
            try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
            var banner = document.getElementById('draft-banner');
            if (banner) { banner.innerHTML = ''; }
        };

        function formatarHora(ts) {
            var d = new Date(ts);
            return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }

        function exibirBannerRascunho(savedAt) {
            var banner = document.getElementById('draft-banner');
            if (! banner) { return; }
            banner.innerHTML =
                '<div class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-800/40 dark:bg-amber-950/30">' +
                    '<div class="flex items-center gap-2 text-amber-800 dark:text-amber-300">' +
                        '<svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>' +
                        '<span>Rascunho local recuperado — salvo às <strong>' + formatarHora(savedAt) + '</strong></span>' +
                    '</div>' +
                    '<button type="button" onclick="limparRascunhoLocal()" class="ml-4 shrink-0 text-xs font-medium text-amber-700 underline hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-200">Descartar</button>' +
                '</div>';
        }

        // Salva a cada mudança no formulário
        document.getElementById('reporte-form').addEventListener('input', salvarRascunhoLocal);
        document.getElementById('reporte-form').addEventListener('change', salvarRascunhoLocal);

        // Salva automaticamente a cada 30 segundos
        setInterval(salvarRascunhoLocal, 30000);

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
                        '<div class="relative mt-1">' +
                            '<input type="text" name="itens[' + idx + '][prefixo]" autocomplete="off"' +
                                   ' id="prefixo-input-' + idx + '"' +
                                   ' value="' + (data.prefixo || '') + '"' +
                                   ' placeholder="Ex: 1803"' +
                                   ' oninput="filtrarPrefixos(' + idx + ')"' +
                                   ' onfocus="filtrarPrefixos(' + idx + ')"' +
                                   ' class="' + fieldClass(eP) + '">' +
                            '<div id="ac-' + idx + '" class="absolute z-30 mt-1 hidden max-h-52 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"></div>' +
                        '</div>' +
                        '<button type="button" id="btn-elog-' + idx + '" onclick="buscarElog(' + idx + ')"' +
                                ' class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-blue-600 hover:text-blue-800 disabled:opacity-40 dark:text-blue-400 dark:hover:text-blue-300">' +
                            '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/></svg>' +
                            'Pesquisar no Elog' +
                        '</button>' +
                        '<button type="button" id="btn-reporte-' + idx + '" onclick="buscarReporte(' + idx + ')"' +
                                ' class="ml-3 mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 hover:text-emerald-800 disabled:opacity-40 dark:text-emerald-400 dark:hover:text-emerald-300">' +
                            '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/></svg>' +
                            'Pesquisar no Reporte' +
                        '</button>' +
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
            limparRascunhoLocal();
            document.getElementById('salvar_como').value = tipo;
            document.getElementById('reporte-form').submit();
        };

        function abrirElogModal(msg) {
            document.getElementById('elog-modal-msg').textContent = msg;
            var m = document.getElementById('elog-modal');
            m.classList.remove('hidden');
            m.classList.add('flex');
        }

        window.fecharElogModal = function () {
            var m = document.getElementById('elog-modal');
            m.classList.add('hidden');
            m.classList.remove('flex');
        };

        window.filtrarPrefixos = function (idx) {
            var input    = document.getElementById('prefixo-input-' + idx);
            var dropdown = document.getElementById('ac-' + idx);
            var val      = (input.value || '').toLowerCase().trim();

            var matches = val
                ? prefixosList.filter(function (p) {
                    return p.prefixo.toLowerCase().includes(val) || (p.placa || '').toLowerCase().includes(val);
                })
                : prefixosList;

            if (matches.length === 0) { dropdown.classList.add('hidden'); return; }

            dropdown.innerHTML = matches.slice(0, 30).map(function (p) {
                return '<div class="cursor-pointer px-3 py-2 text-sm text-zinc-800 hover:bg-slate-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60"' +
                       ' onmousedown="event.preventDefault(); selecionarPrefixo(' + idx + ', \'' + p.prefixo.replace(/\\/g, '\\\\').replace(/'/g, "\\'") + '\')">' +
                       '<span class="font-semibold">' + p.prefixo + '</span>' +
                       (p.placa ? '<span class="ml-2 text-xs text-zinc-400 dark:text-zinc-500">— ' + p.placa + '</span>' : '') +
                       '</div>';
            }).join('');

            dropdown.classList.remove('hidden');
        };

        window.selecionarPrefixo = function (idx, prefixo) {
            document.getElementById('prefixo-input-' + idx).value = prefixo;
            document.getElementById('ac-' + idx).classList.add('hidden');
        };

        document.addEventListener('click', function (e) {
            document.querySelectorAll('[id^="ac-"]').forEach(function (d) {
                var input = document.getElementById('prefixo-input-' + d.id.replace('ac-', ''));
                if (! d.contains(e.target) && e.target !== input) {
                    d.classList.add('hidden');
                }
            });
        });

        window.buscarElog = function (idx) {
            var card    = document.getElementById('item-' + idx);
            var prefixo = card.querySelector('[name="itens[' + idx + '][prefixo]"]').value.trim();

            if (! prefixo) {
                abrirElogModal('Informe o prefixo antes de pesquisar.');
                return;
            }

            var placa = prefixoToPlaca[prefixo];
            if (! placa) {
                abrirElogModal('O prefixo "' + prefixo + '" não foi encontrado no cadastro de veículos.');
                return;
            }

            var btn     = document.getElementById('btn-elog-' + idx);
            var svgBusy = '<svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';
            btn.disabled   = true;
            btn.innerHTML  = svgBusy + ' Buscando...';

            fetch(BIGCORE_URL + '?placa=' + encodeURIComponent(placa), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (! res.ok) {
                    abrirElogModal(res.data.erro || 'Veículo não localizado no Elog para a placa ' + placa + '.');
                    return;
                }
                var d = res.data;
                if (d.tempo_parado)       card.querySelector('[name="itens[' + idx + '][tempo_parado]"]').value       = d.tempo_parado;
                if (d.status_operacional) card.querySelector('[name="itens[' + idx + '][status_operacional]"]').value = d.status_operacional;
                if (d.documento)          card.querySelector('[name="itens[' + idx + '][documento]"]').value          = d.documento;
                if (d.observacao)         card.querySelector('[name="itens[' + idx + '][observacao]"]').value         = d.observacao;
            })
            .catch(function () { abrirElogModal('Não foi possível conectar ao Elog. Tente novamente.'); })
            .finally(function () {
                var svgSearch = '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/></svg>';
                btn.disabled  = false;
                btn.innerHTML = svgSearch + ' Pesquisar no Elog';
            });
        };

        window.buscarReporte = function (idx) {
            var card    = document.getElementById('item-' + idx);
            var prefixo = card.querySelector('[name="itens[' + idx + '][prefixo]"]').value.trim();

            if (! prefixo) {
                abrirElogModal('Informe o prefixo antes de pesquisar.');
                return;
            }

            var btn     = document.getElementById('btn-reporte-' + idx);
            var svgBusy = '<svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>';
            btn.disabled  = true;
            btn.innerHTML = svgBusy + ' Buscando...';

            fetch(REPORTE_PREFIXO_URL + '?prefixo=' + encodeURIComponent(prefixo), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
            .then(function (res) {
                if (! res.ok) {
                    abrirElogModal(res.data.erro || 'Nenhum reporte encontrado para o prefixo "' + prefixo + '".');
                    return;
                }
                var d = res.data;
                if (d.status_operacional) card.querySelector('[name="itens[' + idx + '][status_operacional]"]').value    = d.status_operacional;
                if (d.documento)          card.querySelector('[name="itens[' + idx + '][documento]"]').value             = d.documento;
                if (d.primeiro_contato)   card.querySelector('[name="itens[' + idx + '][primeiro_contato]"]').value      = d.primeiro_contato;
                if (d.segundo_contato)    card.querySelector('[name="itens[' + idx + '][segundo_contato]"]').value        = d.segundo_contato;
                if (d.data_hora_previsao) card.querySelector('[name="itens[' + idx + '][data_hora_previsao]"]').value     = d.data_hora_previsao;
                if (d.observacao)         card.querySelector('[name="itens[' + idx + '][observacao]"]').value             = d.observacao;
            })
            .catch(function () { abrirElogModal('Não foi possível buscar o último reporte. Tente novamente.'); })
            .finally(function () {
                var svgSearch = '<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/></svg>';
                btn.disabled  = false;
                btn.innerHTML = svgSearch + ' Pesquisar no Reporte';
            });
        };

        // ─── Restaurar itens (erro de validação do servidor ou rascunho local) ──
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
        (function () {
            var restored = false;
            try {
                var draft = JSON.parse(localStorage.getItem(DRAFT_KEY));
                if (draft && draft.itens && draft.itens.length > 0) {
                    document.getElementById('nome').value = draft.nome || '';
                    draft.itens.forEach(function (data, idx) {
                        container.insertAdjacentHTML('beforeend', buildCard(idx, data, {}));
                        itemIndex = idx + 1;
                    });
                    updateAddBtn();
                    exibirBannerRascunho(draft.savedAt);
                    restored = true;
                }
            } catch (e) {}
            if (! restored) { addItem(); }
        })();
        @endif
    })();
    </script>

</x-layouts.app>
