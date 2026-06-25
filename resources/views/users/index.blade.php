<x-layouts.app title="Usuários">

    {{-- ─── Page header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4 mt-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Usuários</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Gerencie os usuários do sistema.</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5
                  text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                  bg-zinc-900 text-white hover:bg-zinc-700
                  dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Novo Usuário
        </a>
    </div>

    {{-- ─── Toolbar ─────────────────────────────────────────────────────────── --}}
    @php
        $currentSearch = request('search');
        $currentStatus = request('status');
    @endphp

    <form id="filter-form" method="GET" action="{{ route('users.index') }}" class="mt-6">
        <div class="flex flex-wrap items-center gap-3">

            {{-- Search com botão Filtrar manual --}}
            <div class="flex flex-1 min-w-56 overflow-hidden rounded-lg border shadow-xs
                        border-slate-300 bg-white
                        focus-within:border-zinc-900 focus-within:ring-2 focus-within:ring-zinc-900/10
                        dark:border-zinc-800 dark:bg-zinc-950
                        dark:focus-within:border-blue-500 dark:focus-within:ring-1 dark:focus-within:ring-blue-500/30
                        transition-all duration-200">
                <label for="search" class="sr-only">Buscar usuários</label>
                <span class="flex items-center pl-3.5 text-zinc-400 dark:text-zinc-600">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </span>
                <input
                    type="text"
                    name="search"
                    id="search"
                    value="{{ $currentSearch }}"
                    placeholder="Buscar por nome ou e-mail…"
                    autocomplete="off"
                    class="flex-1 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none
                           placeholder:text-zinc-400
                           dark:text-zinc-100 dark:placeholder:text-zinc-600"
                />
                <button type="submit"
                        class="flex items-center gap-1.5 border-l px-4 py-2.5
                               text-sm font-medium transition-colors duration-150
                               border-slate-200 bg-slate-50 text-zinc-600 hover:bg-slate-100 hover:text-zinc-900
                               dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                    Filtrar
                </button>
            </div>

            {{-- Filtros de status --}}
            <div class="flex items-center gap-1 rounded-lg border p-1 shadow-xs
                        border-slate-200 bg-white
                        dark:border-zinc-800 dark:bg-zinc-950">
                <a href="{{ route('users.index', $currentSearch ? ['search' => $currentSearch] : []) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                          {{ !$currentStatus ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">
                    Todos
                </a>
                @foreach($statuses as $status)
                    <a href="{{ route('users.index', array_filter(['search' => $currentSearch, 'status' => $status->value])) }}"
                       class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                              {{ $currentStatus === $status->value ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">
                        {{ $status->label() }}
                    </a>
                @endforeach
            </div>

            {{-- Exportar CSV --}}
            <a href="{{ route('users.export', array_filter(['search' => $currentSearch, 'status' => $currentStatus])) }}"
               title="Exportar para CSV/Excel"
               class="inline-flex items-center gap-2 rounded-lg border px-3.5 py-2.5
                      text-sm font-medium shadow-xs transition-all duration-200
                      border-slate-200 bg-white text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
                <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Exportar
            </a>
        </div>

        @if($currentSearch || $currentStatus)
            <div class="mt-3 flex items-center gap-2">
                <span class="text-xs text-zinc-400 dark:text-zinc-600">Filtrando por:</span>
                @if($currentSearch)
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium
                                 border-zinc-200 bg-zinc-50 text-zinc-700
                                 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        "{{ $currentSearch }}"
                    </span>
                @endif
                @if($currentStatus)
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium
                                 border-zinc-200 bg-zinc-50 text-zinc-700
                                 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">
                        {{ collect($statuses)->firstWhere('value', $currentStatus)?->label() }}
                    </span>
                @endif
                <a href="{{ route('users.index') }}"
                   class="text-xs text-zinc-400 underline underline-offset-2 transition-colors
                          hover:text-zinc-700 dark:text-zinc-600 dark:hover:text-zinc-400">
                    Limpar
                </a>
            </div>
        @endif
    </form>

    {{-- ─── Table card ──────────────────────────────────────────────────────── --}}
    <div id="table-wrapper"
         class="mt-8 overflow-hidden rounded-xl border shadow-sm transition-opacity duration-200
                border-slate-200 bg-white
                dark:border-zinc-800 dark:bg-zinc-900/50">

        @if($users->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                </div>
                @if($currentSearch || $currentStatus)
                    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Nenhum usuário encontrado
                    </h3>
                    <p class="mt-1.5 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">
                        Nenhum usuário corresponde aos critérios informados.
                    </p>
                    <a href="{{ route('users.index') }}"
                       class="mt-5 inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors
                              border-zinc-200 text-zinc-700 hover:bg-zinc-50
                              dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">
                        Limpar filtros
                    </a>
                @else
                    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        Nenhum usuário cadastrado
                    </h3>
                    <p class="mt-1.5 max-w-xs text-sm text-zinc-500 dark:text-zinc-400">
                        Cadastre o primeiro usuário para dar início ao gerenciamento.
                    </p>
                    <a href="{{ route('users.create') }}"
                       class="mt-5 inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all active:scale-[0.98]
                              bg-zinc-900 text-white hover:bg-zinc-700
                              dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Criar primeiro usuário
                    </a>
                @endif
            </div>

        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Usuário
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Status
                            </th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 md:table-cell">
                                Perfil
                            </th>
                            <th scope="col" class="hidden px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 sm:table-cell">
                                Cadastrado em
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Ações
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($users as $user)
                            <tr class="transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                                                    bg-zinc-200 ring-2 ring-zinc-300/40
                                                    dark:bg-zinc-800 dark:ring-zinc-700/40">
                                            <span class="text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $user->name }}</p>
                                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php $color = $user->status->color(); @endphp
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1
                                        @if($color === 'emerald') bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/50 @endif
                                        @if($color === 'red')     bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-800/50 @endif
                                        @if($color === 'amber')   bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800/50 @endif">
                                        <span class="h-1.5 w-1.5 rounded-full
                                            @if($color === 'emerald') bg-emerald-500 @endif
                                            @if($color === 'red')     bg-red-500 @endif
                                            @if($color === 'amber')   bg-amber-500 @endif">
                                        </span>
                                        {{ $user->status->label() }}
                                    </span>
                                </td>
                                <td class="hidden px-6 py-4 md:table-cell">
                                    @php $roleColor = $user->role->color(); @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1
                                        @if($roleColor === 'violet') bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-400 dark:ring-violet-800/50 @endif
                                        @if($roleColor === 'blue')   bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-800/50 @endif
                                        @if($roleColor === 'zinc')   bg-zinc-50 text-zinc-700 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:ring-zinc-700 @endif
                                        @if($roleColor === 'emerald') bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/50 @endif">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td class="hidden px-6 py-4 text-zinc-500 dark:text-zinc-400 sm:table-cell">
                                    <time datetime="{{ $user->created_at->toISOString() }}"
                                          title="{{ $user->created_at->format('d/m/Y H:i') }}">
                                        {{ $user->created_at->format('d/m/Y') }}
                                    </time>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5
                                                  text-xs font-medium transition-all duration-150
                                                  border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                  dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              data-confirm="true"
                                              data-user-name="{{ $user->name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5
                                                           text-xs font-medium transition-all duration-150
                                                           border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50
                                                           dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                                Remover
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>

    @if(!$users->isEmpty())
        <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-600">
            {{ $users->total() }} {{ $users->total() === 1 ? 'usuário' : 'usuários' }} no total
            @if($currentSearch || $currentStatus)
                · filtros ativos
            @endif
        </p>
    @endif

    <script>
    (function () {
        var wrapper = document.getElementById('table-wrapper');
        function dim() { wrapper.style.opacity = '0.4'; wrapper.style.pointerEvents = 'none'; }
        wrapper.querySelectorAll('nav a').forEach(function (a) { a.addEventListener('click', dim); });
        document.querySelectorAll('[href*="status"]').forEach(function (a) { a.addEventListener('click', dim); });
    })();
    </script>

</x-layouts.app>
