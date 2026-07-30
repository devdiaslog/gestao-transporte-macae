<x-layouts.app title="Nova Medição">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('medicoes.index') }}"
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
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Nova Medição</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cadastre um ciclo de medição.</p>
        </div>
    </div>

    <div class="mt-6 max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form method="POST" action="{{ route('medicoes.store') }}" class="space-y-5" novalidate>
            @csrf
            @include('medicoes._form')

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-zinc-800">
                <a href="{{ route('medicoes.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all duration-200 hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Criar Medição
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
