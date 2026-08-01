<x-layouts.app title="Editar Perfil">

    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('perfis.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition-all
                  hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Perfil</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $perfil->name }} · {{ $perfil->users()->count() }} usuário(s)</p>
        </div>
    </div>

    @if($protegido)
        <div class="mt-6 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800 dark:border-violet-900/40 dark:bg-violet-950/20 dark:text-violet-300">
            O perfil <strong>Administrador</strong> é do sistema: mantém acesso total automaticamente, inclusive a módulos criados no futuro.
        </div>
    @endif

    <form method="POST" action="{{ route('perfis.update', $perfil) }}" class="mt-6 space-y-6" novalidate>
        @csrf
        @method('PUT')

        <div class="max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Nome do perfil <span class="text-red-500">*</span>
            </label>
            <input id="name" type="text" name="name" value="{{ old('name', $perfil->name) }}" @disabled($protegido)
                   class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all focus:ring-2 disabled:opacity-60
                          {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700' }}
                          bg-white text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
            @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Permissões</p>
            @include('perfis._matriz', [
                'grupos' => $grupos,
                'selecionadas' => old('permissions', $selecionadas),
                'somenteLeitura' => $protegido,
            ])
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('perfis.index') }}"
               class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Cancelar
            </a>
            <button type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900">
                Salvar alterações
            </button>
        </div>
    </form>

</x-layouts.app>
