<x-layouts.app title="Justificativas">

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Justificativas</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Gerencie as justificativas de ocorrência.</p>
        </div>
        <a href="{{ route('justificativas.create') }}"
           class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5
                  text-sm font-semibold shadow-xs transition-all duration-200 active:scale-[0.98]
                  bg-zinc-900 text-white hover:bg-zinc-700
                  dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nova Justificativa
        </a>
    </div>

    @php
        $currentSearch = request('search');
        $currentAtivo  = request('ativo');
    @endphp

    <form id="filter-form" method="GET" action="{{ route('justificativas.index') }}" class="mt-6">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex min-w-56 flex-1 overflow-hidden rounded-lg border shadow-xs
                        border-slate-300 bg-white focus-within:border-zinc-900 focus-within:ring-2 focus-within:ring-zinc-900/10
                        dark:border-zinc-800 dark:bg-zinc-950 dark:focus-within:border-blue-500 dark:focus-within:ring-1 dark:focus-within:ring-blue-500/30
                        transition-all duration-200">
                <span class="flex items-center pl-3.5 text-zinc-400 dark:text-zinc-600">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="Buscar por descrição…"
                       autocomplete="off"
                       class="flex-1 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100 dark:placeholder:text-zinc-600">
                <button type="submit"
                        class="flex items-center gap-1.5 border-l px-4 py-2.5 text-sm font-medium transition-colors duration-150
                               border-slate-200 bg-slate-50 text-zinc-600 hover:bg-slate-100 hover:text-zinc-900
                               dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                    Filtrar
                </button>
            </div>

            <div class="flex items-center gap-1 rounded-lg border p-1 shadow-xs border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <a href="{{ route('justificativas.index', array_filter(['search' => $currentSearch])) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                          {{ $currentAtivo === null || $currentAtivo === '' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">Todos</a>
                <a href="{{ route('justificativas.index', array_filter(['search' => $currentSearch, 'ativo' => '1'])) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                          {{ $currentAtivo === '1' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">Ativos</a>
                <a href="{{ route('justificativas.index', array_filter(['search' => $currentSearch, 'ativo' => '0'], fn ($v) => $v !== null)) }}"
                   class="rounded-md px-3 py-1.5 text-sm font-medium transition-all duration-150
                          {{ $currentAtivo === '0' ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}">Inativos</a>
            </div>
        </div>

        @if($currentSearch || $currentAtivo !== null)
            <div class="mt-3 flex items-center gap-2">
                <span class="text-xs text-zinc-400 dark:text-zinc-600">Filtrando por:</span>
                @if($currentSearch)
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300">"{{ $currentSearch }}"</span>
                @endif
                <a href="{{ route('justificativas.index') }}" class="text-xs text-zinc-400 underline underline-offset-2 hover:text-zinc-700 dark:text-zinc-600 dark:hover:text-zinc-400">Limpar</a>
            </div>
        @endif
    </form>

    <div class="mt-8 overflow-hidden rounded-xl border shadow-sm border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
        @if($justificativas->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                    </svg>
                </div>
                @if($currentSearch || $currentAtivo !== null)
                    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma justificativa encontrada</h3>
                    <a href="{{ route('justificativas.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">Limpar filtros</a>
                @else
                    <h3 class="mt-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma justificativa cadastrada</h3>
                    <a href="{{ route('justificativas.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Criar primeira justificativa
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Descrição</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Obrigar Obs.</th>
                            <th scope="col" class="px-6 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($justificativas as $justificativa)
                            <tr class="transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $justificativa->descricao }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($justificativa->obrigar_observacao)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-500/30">Sim</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-500 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-800/60 dark:text-zinc-500 dark:ring-zinc-700/50">Não</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($justificativa->ativo)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Ativo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-800/60 dark:text-zinc-400 dark:ring-zinc-700/50">
                                            <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>Inativo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('justificativas.edit', $justificativa) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
                                                  border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                  dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('justificativas.destroy', $justificativa) }}"
                                              data-confirm="true" data-user-name="{{ $justificativa->descricao }}">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all duration-150
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

            @if($justificativas->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $justificativas->links() }}
                </div>
            @endif
        @endif
    </div>

    @if(!$justificativas->isEmpty())
        <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-600">
            {{ $justificativas->total() }} {{ $justificativas->total() === 1 ? 'justificativa' : 'justificativas' }} no total
        </p>
    @endif

</x-layouts.app>
