<x-layouts.app title="Perfis de Acesso">

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Perfis de Acesso</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Cada perfil é um conjunto de permissões. Um usuário recebe um perfil e pode ter permissões extras individuais.
            </p>
        </div>
        @can('perfis.criar')
            <a href="{{ route('perfis.create') }}"
               class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                      bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Novo Perfil
            </a>
        @endcan
    </div>

    <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-zinc-800">
                    <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Perfil</th>
                    <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Permissões</th>
                    <th class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Usuários</th>
                    <th class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                @foreach($perfis as $perfil)
                    <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $perfil->name }}
                                @if($perfil->name === 'Administrador')
                                    <span class="ml-1.5 rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/40 dark:text-violet-400">sistema</span>
                                @endif
                            </p>
                        </td>
                        <td class="px-6 py-4 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $perfil->permissions_count }}</td>
                        <td class="px-6 py-4 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $perfil->users_count }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @can('perfis.editar')
                                    <a href="{{ route('perfis.edit', $perfil) }}"
                                       class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all
                                              border-zinc-200 text-zinc-700 hover:bg-zinc-50
                                              dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800/70">
                                        Editar
                                    </a>
                                @endcan
                                @can('perfis.excluir')
                                    @if($perfil->name !== 'Administrador' && $perfil->users_count === 0)
                                        <form method="POST" action="{{ route('perfis.destroy', $perfil) }}"
                                              data-confirm="true" data-user-name="{{ $perfil->name }}">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all
                                                           border-red-200 text-red-600 hover:bg-red-50
                                                           dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40">
                                                Remover
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($perfis->hasPages())
            <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">{{ $perfis->links() }}</div>
        @endif
    </div>

</x-layouts.app>
