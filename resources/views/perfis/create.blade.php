<x-layouts.app title="Novo Perfil">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('perfis.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-all
                  hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Novo Perfil</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Defina o nome e marque as permissões deste perfil.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('perfis.store') }}" class="mt-6 space-y-6" novalidate>
        @csrf

        <div class="max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Nome do perfil <span class="text-red-500">*</span>
            </label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autofocus placeholder="Ex.: Gestão de Frota"
                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all focus:ring-2
                          {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700' }}
                          bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Permissões</p>
            @include('perfis._matriz', ['grupos' => $grupos, 'selecionadas' => old('permissions', $selecionadas)])
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('perfis.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Cancelar
            </a>
            <button type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900">
                Criar Perfil
            </button>
        </div>
    </form>

</x-layouts.app>
