@can('dashboard.atualizar')
<form method="POST" action="{{ route('dashboard.atualizar') }}" class="flex items-center">
    @csrf
    <button type="submit" title="Sincronizar rastreador e capturar o status agora"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium
                   text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                   dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
        </svg>
        Atualizar
    </button>
</form>
@endcan
