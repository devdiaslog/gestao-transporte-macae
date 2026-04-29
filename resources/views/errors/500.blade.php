@extends('errors.layout')

@section('title', 'Erro interno')

@section('content')
    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full
                bg-zinc-100 ring-8 ring-zinc-50
                dark:bg-zinc-900 dark:ring-zinc-900/50">
        <svg class="h-10 w-10 text-zinc-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
        </svg>
    </div>

    <p class="mt-6 text-sm font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
        Erro 500
    </p>

    <h1 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-3xl">
        Algo deu errado
    </h1>

    <p class="mt-3 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
        Ocorreu um erro interno no servidor. Nossa equipe já foi notificada.<br>
        Tente novamente em alguns instantes.
    </p>
@endsection

@section('actions')
    <a href="{{ route('ocorrencias.index') }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all duration-200
              bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
        </svg>
        Página inicial
    </a>
@endsection
