<x-layouts.app title="Editar Ocorrência">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('ocorrencias.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Ocorrência</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $ocorrencia->veiculo?->placa }} · {{ $ocorrencia->data_hora_inicio->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    <div class="mt-6 max-w-2xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form method="POST" action="{{ route('ocorrencias.update', $ocorrencia) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="id_veiculo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Veículo <span class="text-red-500">*</span>
                    </label>
                    <select id="id_veiculo" name="id_veiculo"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                   {{ $errors->has('id_veiculo') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        <option value="">— Selecione —</option>
                        @foreach($veiculos as $veiculo)
                            <option value="{{ $veiculo->id }}" @selected(old('id_veiculo', $ocorrencia->id_veiculo) == $veiculo->id)>
                                {{ $veiculo->placa }}{{ $veiculo->prefixo ? ' — '.$veiculo->prefixo : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_veiculo')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="id_tipo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo de Ocorrência <span class="text-red-500">*</span>
                    </label>
                    <select id="id_tipo" name="id_tipo"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                   {{ $errors->has('id_tipo') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                        <option value="">— Selecione —</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo->id_tipo }}" @selected(old('id_tipo', $ocorrencia->id_tipo) == $tipo->id_tipo)>{{ $tipo->descricao }}</option>
                        @endforeach
                    </select>
                    @error('id_tipo')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="id_responsavel" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Responsável</label>
                    <select id="id_responsavel" name="id_responsavel"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                   border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">— Nenhum —</option>
                        @foreach($responsaveis as $responsavel)
                            <option value="{{ $responsavel->id_responsavel }}" @selected(old('id_responsavel', $ocorrencia->id_responsavel) == $responsavel->id_responsavel)>
                                {{ $responsavel->nome }} ({{ $responsavel->tipo->label() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="id_justificativa" id="label_justificativa" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Justificativa</label>
                    <select id="id_justificativa" name="id_justificativa"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                   border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        <option value="">— Nenhuma —</option>
                        @foreach($justificativas as $justificativa)
                            <option value="{{ $justificativa->id_justificativa }}"
                                    data-obrigar="{{ $justificativa->obrigar_observacao ? '1' : '0' }}"
                                    @selected(old('id_justificativa', $ocorrencia->id_justificativa) == $justificativa->id_justificativa)>
                                {{ $justificativa->descricao }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="data_hora_inicio" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Data/Hora Início <span class="text-red-500">*</span>
                    </label>
                    <input id="data_hora_inicio" type="datetime-local" name="data_hora_inicio"
                           value="{{ old('data_hora_inicio', $ocorrencia->data_hora_inicio?->format('Y-m-d\TH:i')) }}"
                           class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                  {{ $errors->has('data_hora_inicio') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                    @error('data_hora_inicio')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="data_hora_fim" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Data/Hora Fim</label>
                    <input id="data_hora_fim" type="datetime-local" name="data_hora_fim"
                           value="{{ old('data_hora_fim', $ocorrencia->data_hora_fim?->format('Y-m-d\TH:i')) }}"
                           class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                  {{ $errors->has('data_hora_fim') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                    @error('data_hora_fim')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="observacao" id="label_observacao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observação</label>
                <textarea id="observacao" name="observacao" rows="3"
                          class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                 border-slate-300 bg-white text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-zinc-900/10
                                 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">{{ old('observacao', $ocorrencia->observacao) }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-zinc-800">
                <a href="{{ route('ocorrencias.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all duration-200 hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const tiposMap = @json($tiposMap);
        const tipoSel  = document.getElementById('id_tipo');
        const justSel  = document.getElementById('id_justificativa');
        const obsArea  = document.getElementById('observacao');
        const obsLabel = document.getElementById('label_observacao');

        // Snapshot all non-empty options once at page load
        const allOpts = [...justSel.options].filter(o => o.value !== '');

        function filterJustificativas() {
            const tipoId  = tipoSel.value;
            const linked  = tipoId ? (tiposMap[tipoId] || []) : [];
            const limited = linked.length > 0;
            const current = justSel.value;

            // Rebuild keeping only blank + allowed options
            while (justSel.options.length > 1) { justSel.remove(1); }

            allOpts.forEach(function (opt) {
                if (!limited || linked.includes(Number(opt.value))) {
                    justSel.appendChild(opt.cloneNode(true));
                }
            });

            // Restore previous selection if still available, otherwise clear
            justSel.value = current;
            if (justSel.value !== current) { justSel.value = ''; }

            updateObsRequired();
        }

        function updateObsRequired() {
            const sel     = justSel.selectedOptions[0];
            const obrigar = sel && sel.dataset.obrigar === '1';

            obsArea.required = obrigar;

            // Toggle asterisk on label
            const existing = obsLabel.querySelector('.obs-star');
            if (obrigar && !existing) {
                const star = document.createElement('span');
                star.className = 'obs-star text-red-500';
                star.textContent = ' *';
                obsLabel.appendChild(star);
            } else if (!obrigar && existing) {
                existing.remove();
            }
        }

        tipoSel.addEventListener('change', filterJustificativas);
        justSel.addEventListener('change', updateObsRequired);
        filterJustificativas();
    })();
    </script>

</x-layouts.app>
