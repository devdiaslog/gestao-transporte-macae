<x-layouts.app title="Editar Justificativa">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('justificativas.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Justificativa</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $justificativa->descricao }}</p>
        </div>
    </div>

    <div class="mt-6 max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form method="POST" action="{{ route('justificativas.update', $justificativa) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="descricao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Descrição <span class="text-red-500">*</span>
                </label>
                <input id="descricao" type="text" name="descricao" value="{{ old('descricao', $justificativa->descricao) }}" autofocus
                       class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2
                              {{ $errors->has('descricao') ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100' : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}">
                @error('descricao')
                    <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Opções</label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="hidden" name="ativo" value="0">
                    <input type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $justificativa->ativo))
                           class="h-4 w-4 rounded border-slate-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativo</span>
                </label>
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="hidden" name="obrigar_observacao" value="0">
                    <input type="checkbox" id="obrigar_observacao" name="obrigar_observacao" value="1" @checked(old('obrigar_observacao', $justificativa->obrigar_observacao))
                           class="h-4 w-4 rounded border-slate-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Obrigar preenchimento da observação</span>
                </label>
            </div>

            @if($tipos->isNotEmpty())
                <div class="space-y-1.5">
                    <label for="tipos" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Tipos de Ocorrência vinculados
                        <span class="ml-1 text-xs font-normal text-zinc-400 dark:text-zinc-500">(segure Ctrl para selecionar vários)</span>
                    </label>
                    @php $tiposSelected = old('tipos', $tiposVinculados); @endphp
                    <select id="tipos" name="tipos[]" multiple size="5"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 focus:ring-2
                                   border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo->id_tipo }}" @selected(in_array($tipo->id_tipo, $tiposSelected))>
                                {{ $tipo->descricao }}
                            </option>
                        @endforeach
                    </select>
                    @error('tipos')
                        <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-zinc-800">
                <a href="{{ route('justificativas.index') }}"
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

</x-layouts.app>
