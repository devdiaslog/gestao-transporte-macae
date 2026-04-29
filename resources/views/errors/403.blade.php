@extends('errors.layout')

@section('title', 'Acesso negado')

@section('content')
    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full
                bg-red-100 ring-8 ring-red-50
                dark:bg-red-950/50 dark:ring-red-950/30">
        <svg class="h-10 w-10 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
        </svg>
    </div>

    <p class="mt-6 text-sm font-semibold uppercase tracking-widest text-red-500 dark:text-red-400">
        Erro 403
    </p>

    <h1 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100 sm:text-3xl">
        Acesso negado
    </h1>

    <p class="mt-3 max-w-sm text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
        Você não tem permissão para acessar esta página.<br>
        Caso acredite que isso seja um erro, entre em contato com o administrador do sistema.
    </p>
@endsection

@section('actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('ocorrencias.index') }}"
       class="inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-medium transition-all duration-200
              border-zinc-200 bg-white text-zinc-700 shadow-xs hover:border-zinc-300 hover:bg-zinc-50
              dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Voltar
    </a>

    <a href="{{ route('ocorrencias.index') }}"
       class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-xs transition-all duration-200
              bg-zinc-900 text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
        </svg>
        Página inicial
    </a>
@endsection
