<x-layouts.app title="Sem acesso">

<div class="flex min-h-[60vh] items-center justify-center py-8">
    <div class="max-w-md text-center">
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-zinc-800">
            <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
            </svg>
        </div>

        <h1 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
            Sua conta ainda não tem acesso a nenhuma tela
        </h1>

        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            Peça ao administrador do sistema para atribuir um perfil de acesso ao seu usuário.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit"
                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700
                           transition-colors hover:bg-slate-50
                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Sair
            </button>
        </form>
    </div>
</div>

</x-layouts.app>
