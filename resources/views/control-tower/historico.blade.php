<x-layouts.app title="Histórico — {{ $equipamento->prefixo ?? $equipamento->placa }}">

    {{-- ─── Header ──────────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center gap-3">
        <a href="{{ route('control-tower.index') }}"
           class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors
                  border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50
                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Torre de Controle
        </a>
    </div>

    {{-- ─── Vehicle card ────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center gap-6 rounded-xl border px-6 py-4
                border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Prefixo</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $equipamento->prefixo ?? '—' }}
            </p>
        </div>
        <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Placa</p>
            <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->placa }}</p>
        </div>
        @if($equipamento->modelo)
            <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Modelo</p>
                <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->modelo->nome }}</p>
            </div>
        @endif
        @if($equipamento->divisao)
            <div class="h-10 w-px bg-slate-200 dark:bg-zinc-800"></div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Divisão</p>
                <p class="mt-0.5 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $equipamento->divisao->nome }}</p>
            </div>
        @endif
        <div class="ml-auto">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Total de registros</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $logs->total() }}</p>
        </div>
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('control-tower.historico', $equipamento) }}"
          class="mt-4 flex flex-wrap items-end gap-3">

        {{-- Campo --}}
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Campo</label>
            <select name="campo"
                    class="rounded-lg border px-3 py-1.5 text-sm
                           border-slate-200 bg-white text-zinc-700
                           dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Todos</option>
                @foreach($campos as $c)
                    <option value="{{ $c }}" @selected(request('campo') === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        {{-- Data --}}
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Data</label>
            <input type="date" name="data" value="{{ request('data') }}"
                   class="rounded-lg border px-3 py-1.5 text-sm
                          border-slate-200 bg-white text-zinc-700
                          dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                          focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- Documento --}}
        <div class="flex flex-col gap-1">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Documento</label>
            <input type="text" name="documento" value="{{ request('documento') }}"
                   placeholder="Buscar valor…"
                   class="rounded-lg border px-3 py-1.5 text-sm
                          border-slate-200 bg-white text-zinc-700
                          dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300
                          placeholder:text-zinc-400 dark:placeholder:text-zinc-600
                          focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit"
                class="rounded-lg border px-4 py-1.5 text-sm font-medium transition-colors
                       border-blue-600 bg-blue-600 text-white hover:bg-blue-700
                       dark:border-blue-500 dark:bg-blue-500 dark:hover:bg-blue-600">
            Filtrar
        </button>

        @if(request()->hasAny(['campo', 'data', 'documento']))
            <a href="{{ route('control-tower.historico', $equipamento) }}"
               class="rounded-lg border px-4 py-1.5 text-sm font-medium transition-colors
                      border-slate-200 bg-white text-zinc-600 hover:bg-zinc-50
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700">
                Limpar
            </a>
        @endif
    </form>

    {{-- ─── Log table ───────────────────────────────────────────────────────── --}}
    <div class="mt-4 overflow-hidden rounded-xl border shadow-sm
                border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50">

        @if($logs->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9z"/>
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhuma alteração registrada</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    O histórico será preenchido conforme os dados operacionais forem editados.
                </p>
            </div>

        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600 whitespace-nowrap">
                                Data / Hora
                            </th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Campo
                            </th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Valor Anterior
                            </th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Valor Novo
                            </th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                Usuário
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60">
                        @foreach($logs as $log)
                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $log->created_at->format('d/m/Y') }}
                                    </p>
                                    <p class="text-[11px] text-zinc-400 dark:text-zinc-600">
                                        {{ $log->created_at->format('H:i:s') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                 @if($log->campo === 'Status Operacional') bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400
                                                 @elseif($log->campo === 'Documento de Demanda') bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400
                                                 @else bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 @endif">
                                        {{ $log->campo }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                    @if($log->valor_anterior)
                                        <span class="rounded bg-rose-50 px-1.5 py-0.5 text-xs text-rose-700
                                                     dark:bg-rose-950/30 dark:text-rose-400">
                                            {{ $log->valor_anterior }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">vazio</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                    @if($log->valor_novo)
                                        <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-xs text-emerald-700
                                                     dark:bg-emerald-950/30 dark:text-emerald-400">
                                            {{ $log->valor_novo }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">vazio</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                    {{ $log->user?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="border-t border-slate-100 px-6 py-4 dark:border-zinc-800">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

</x-layouts.app>
