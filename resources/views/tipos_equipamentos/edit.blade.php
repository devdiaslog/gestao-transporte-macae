<x-layouts.app title="Editar Tipo de Equipamento">

    {{-- Page header --}}
    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('tipos-equipamentos.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Tipo de Equipamento</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Atualize os dados do tipo <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $tipoEquipamento->nome }}</span>.</p>
        </div>
    </div>

    {{-- Form card --}}
    <div class="mt-6 max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

        <form method="POST" action="{{ route('tipos-equipamentos.update', $tipoEquipamento) }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            {{-- Nome --}}
            <div class="space-y-1.5">
                <label for="nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Nome <span class="text-red-500">*</span>
                </label>
                <input
                    id="nome"
                    type="text"
                    name="nome"
                    value="{{ old('nome', $tipoEquipamento->nome) }}"
                    required
                    autofocus
                    placeholder="Ex: Motorizado"
                    class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-zinc-400 focus:ring-2
                           {{ $errors->has('nome')
                               ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-zinc-800 dark:text-zinc-100'
                               : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"
                />
                @error('nome')
                    <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-6">
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="radio" name="status" value="1"
                               @checked(old('status', $tipoEquipamento->status ? '1' : '0') === '1')
                               class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400/20">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativo</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-2.5">
                        <input type="radio" name="status" value="0"
                               @checked(old('status', $tipoEquipamento->status ? '1' : '0') === '0')
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
                <a href="{{ route('tipos-equipamentos.index') }}"
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

</x-layouts.app>
