@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegação de páginas" class="flex items-center justify-between gap-4">

        {{-- Mobile --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex cursor-default items-center rounded-lg border px-4 py-2 text-sm
                             border-zinc-200 text-zinc-300 dark:border-zinc-800 dark:text-zinc-700">Anterior</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium transition-colors
                          border-zinc-200 text-zinc-700 hover:bg-zinc-50
                          dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">Anterior</a>
            @endif
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium transition-colors
                          border-zinc-200 text-zinc-700 hover:bg-zinc-50
                          dark:border-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-800/60">Próxima</a>
            @else
                <span class="inline-flex cursor-default items-center rounded-lg border px-4 py-2 text-sm
                             border-zinc-200 text-zinc-300 dark:border-zinc-800 dark:text-zinc-700">Próxima</span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">

            <p class="text-xs text-zinc-500 dark:text-zinc-500">
                Exibindo
                <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $paginator->lastItem() }}</span>
                de
                <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $paginator->total() }}</span>
                {{ $paginator->total() === 1 ? 'resultado' : 'resultados' }}
            </p>

            <div class="flex items-center gap-1">

                {{-- Anterior --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-8 w-8 cursor-default items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" aria-label="Página anterior"
                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-all duration-150
                              border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50
                              dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </a>
                @endif

                {{-- Números --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-8 w-8 cursor-default items-center justify-center text-sm text-zinc-400 dark:text-zinc-600">&hellip;</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-semibold
                                             bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Página {{ $page }}"
                                   class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-sm transition-all duration-150
                                          border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50
                                          dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Próxima --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" aria-label="Próxima página"
                       class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition-all duration-150
                              border-zinc-200 text-zinc-600 hover:border-zinc-300 hover:bg-zinc-50
                              dark:border-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </a>
                @else
                    <span class="inline-flex h-8 w-8 cursor-default items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </span>
                @endif

            </div>
        </div>
    </nav>
@endif
