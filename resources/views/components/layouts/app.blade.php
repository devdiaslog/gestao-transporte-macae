<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Gestão de Transporte' }} — Macaé</title>

    {{-- Anti-flash: inline sync antes de qualquer CSS para evitar piscar --}}
    <script>
        (function () {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    Paleta AMOLED Dark:
    body      → dark:bg-black
    sidebar   → dark:bg-zinc-950  border dark:border-zinc-800
    header    → dark:bg-black/90  border dark:border-zinc-800
    cards     → dark:bg-zinc-900/50  border dark:border-zinc-800
    text-1    → dark:text-zinc-100
    text-2    → dark:text-zinc-400
    text-3    → dark:text-zinc-600
--}}
<body class="h-full overflow-x-hidden bg-slate-50 text-zinc-900 antialiased dark:bg-black dark:text-zinc-100">

<div class="flex h-full w-full overflow-x-hidden">

    {{-- ─── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 flex w-16 flex-col
                  border-r border-slate-200 bg-white
                  dark:border-zinc-800 dark:bg-zinc-950
                  -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0">

        {{-- Logo --}}
        <div id="sidebar-logo"
             class="flex h-16 shrink-0 items-center justify-center border-b border-slate-200 px-2
                    transition-all duration-300 dark:border-zinc-800">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 dark:bg-white">
                <svg class="h-4 w-4 text-white dark:text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="sidebar-label-group hidden min-w-0">
                <p class="truncate text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Gestão de Transporte
                </p>
                <p class="truncate text-[11px] text-zinc-400 dark:text-zinc-600">Macaé — RJ</p>
            </div>
        </div>

        {{-- Navigation --}}
        @php
            $isCadastrosActive = request()->routeIs('users.*', 'divisoes.*', 'subdivisoes.*', 'equipamentos.*', 'tipos-equipamentos.*', 'modelos-equipamentos.*', 'motoristas.*');
            $isOcorrenciasActive = request()->routeIs('responsaveis.*', 'tipos-ocorrencia.*', 'justificativas.*', 'ocorrencias.*');
        @endphp
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-4">

            {{-- Cadastros (accordion) --}}
            <div class="accordion-group">
                <button type="button"
                        title="Cadastros"
                        data-accordion="cadastros"
                        class="nav-link accordion-trigger flex w-full items-center justify-center rounded-lg py-2.5 text-sm font-medium
                               transition-all duration-200
                               {{ $isCadastrosActive ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}
                               hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100">
                    {{-- Ícone de grade --}}
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                    </svg>
                    <span class="nav-label hidden flex-1 whitespace-nowrap text-left font-semibold">Cadastros</span>
                    <svg class="accordion-chevron nav-label hidden h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div id="accordion-cadastros"
                     class="accordion-panel mt-0.5 hidden space-y-0.5 pl-3"
                     {{ $isCadastrosActive ? 'data-active' : '' }}>

                    {{-- Usuários --}}
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('users.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Usuários
                    </a>

                    {{-- Divisões --}}
                    <a href="{{ route('divisoes.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('divisoes.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Divisões
                    </a>

                    {{-- Subdivisões --}}
                    <a href="{{ route('subdivisoes.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('subdivisoes.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Subdivisões
                    </a>

                    {{-- Equipamentos --}}
                    <a href="{{ route('equipamentos.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('equipamentos.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Equipamentos
                    </a>

                    {{-- Tipos de Equipamentos --}}
                    <a href="{{ route('tipos-equipamentos.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('tipos-equipamentos.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Tipos de Equipamentos
                    </a>

                    {{-- Modelos de Equipamentos --}}
                    <a href="{{ route('modelos-equipamentos.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('modelos-equipamentos.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Modelos de Equipamentos
                    </a>

                    {{-- Motoristas --}}
                    <a href="{{ route('motoristas.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('motoristas.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Motoristas
                    </a>
                </div>
            </div>

            {{-- Torre de Controle --}}
            <a href="{{ route('control-tower.index') }}"
               title="Torre de Controle"
               class="nav-link flex items-center justify-center rounded-lg py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('control-tower.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                </svg>
                <span class="nav-label hidden whitespace-nowrap">Torre de Controle</span>
            </a>

            {{-- Ocorrências (accordion) --}}
            <div class="accordion-group">
                <button type="button"
                        title="Ocorrências"
                        data-accordion="ocorrencias"
                        class="nav-link accordion-trigger flex w-full items-center justify-center rounded-lg py-2.5 text-sm font-medium
                               transition-all duration-200
                               {{ $isOcorrenciasActive ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}
                               hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                    </svg>
                    <span class="nav-label hidden flex-1 whitespace-nowrap text-left font-semibold">Ocorrências</span>
                    <svg class="accordion-chevron nav-label hidden h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div id="accordion-ocorrencias"
                     class="accordion-panel mt-0.5 hidden space-y-0.5 pl-3"
                     {{ $isOcorrenciasActive ? 'data-active' : '' }}>

                    <a href="{{ route('ocorrencias.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('ocorrencias.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Ocorrências
                    </a>

                    <a href="{{ route('responsaveis.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('responsaveis.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Responsáveis
                    </a>

                    <a href="{{ route('tipos-ocorrencia.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('tipos-ocorrencia.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Tipos de Ocorrência
                    </a>

                    <a href="{{ route('justificativas.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('justificativas.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Justificativas
                    </a>
                </div>
            </div>

        </nav>

        {{-- User footer --}}
        <div class="shrink-0 border-t border-slate-200 p-2 dark:border-zinc-800">
            <div class="relative" id="profile-wrapper">
                <button id="profile-btn" aria-expanded="false" aria-haspopup="true"
                        class="flex w-full items-center justify-center rounded-lg py-2.5 text-sm
                               transition-colors duration-200
                               hover:bg-zinc-100 dark:hover:bg-zinc-800/70">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                bg-zinc-200 ring-2 ring-zinc-300/50
                                dark:bg-zinc-800 dark:ring-zinc-700/50">
                        <span class="text-[11px] font-bold text-zinc-600 dark:text-zinc-300">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                        </span>
                    </div>
                    <div class="sidebar-label-group hidden min-w-0 flex-1 text-left">
                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ auth()->user()->name ?? 'Usuário' }}
                        </p>
                        <p class="truncate text-[11px] text-zinc-400 dark:text-zinc-600">
                            {{ auth()->user()->email ?? '' }}
                        </p>
                    </div>
                    <svg id="profile-chevron"
                         class="sidebar-label-group hidden h-3.5 w-3.5 shrink-0 text-zinc-400 transition-transform duration-200 dark:text-zinc-600"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                {{-- Profile dropdown --}}
                <div id="profile-dropdown" role="menu"
                     class="absolute bottom-full left-0 mb-2 hidden w-56 rounded-xl border bg-white shadow-xl
                            border-slate-200 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="p-1">
                        <div class="border-b border-slate-100 px-3 py-2.5 dark:border-zinc-800">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-400 dark:text-zinc-600">Conta</p>
                            <p class="mt-0.5 truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ auth()->user()->name ?? 'Usuário' }}
                            </p>
                        </div>
                        <div class="pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" role="menuitem"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm
                                               text-red-600 transition-colors duration-150
                                               hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                                    </svg>
                                    Sair da conta
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Mobile backdrop --}}
    <div id="sidebar-overlay"
         class="fixed inset-0 z-40 hidden bg-black/60 backdrop-blur-sm lg:hidden"></div>

    {{-- ─── Main ────────────────────────────────────────────────────────────── --}}
    <div id="main-wrapper" class="flex h-full min-w-0 flex-1 flex-col transition-all duration-300 lg:pl-16">

        {{-- Header --}}
        <header class="flex h-16 shrink-0 items-center gap-4 px-6
                       border-b border-slate-200 bg-white
                       dark:border-zinc-800 dark:bg-zinc-950">

            <button id="sidebar-toggle"
                    class="-ml-1 rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800/70">
                <span class="sr-only">Abrir menu</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <h1 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 lg:text-base">
                {{ $title ?? 'Dashboard' }}
            </h1>

            <div class="ml-auto flex items-center gap-2">
                {{-- Theme toggle (Sun/Moon) --}}
                <button id="theme-toggle" aria-label="Alternar tema"
                        class="relative h-8 w-8 rounded-lg text-zinc-500 transition-colors duration-200
                               hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800/70">
                    {{-- Sol: visível no dark --}}
                    <svg class="absolute inset-0 m-auto h-4 w-4 rotate-90 scale-0 transition-all duration-300 dark:rotate-0 dark:scale-100"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                    </svg>
                    {{-- Lua: visível no light --}}
                    <svg class="absolute inset-0 m-auto h-4 w-4 rotate-0 scale-100 transition-all duration-300 dark:-rotate-90 dark:scale-0"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
                    </svg>
                </button>
            </div>
        </header>

        {{-- Content --}}
        <main class="min-h-0 flex-1 overflow-y-auto [overflow-x:clip] px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div id="flash-success"
                     class="mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm
                            border-emerald-200 bg-emerald-50 text-emerald-700
                            dark:border-emerald-800/40 dark:bg-emerald-950/30 dark:text-emerald-400">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button onclick="document.getElementById('flash-success').remove()"
                            class="shrink-0 rounded p-0.5 opacity-50 transition-opacity hover:opacity-100">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

{{-- ─── Confirm Modal ─────────────────────────────────────────────────────── --}}
<div id="confirm-modal"
     class="fixed inset-0 z-[100] hidden"
     role="dialog" aria-modal="true" aria-labelledby="confirm-title">

    {{-- Overlay --}}
    <div id="confirm-overlay"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    {{-- Centering wrapper --}}
    <div class="relative flex min-h-full items-center justify-center p-4">

        {{-- Panel --}}
        <div id="confirm-panel"
             class="w-full max-w-sm scale-95 rounded-xl border opacity-0 shadow-xl transition-all duration-200
                    border-slate-200 bg-white
                    dark:border-zinc-800 dark:bg-zinc-950 dark:shadow-2xl">

            {{-- Body --}}
            <div class="p-6">
                <div class="flex items-start gap-4">

                    {{-- Warning icon --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1
                                bg-red-50 ring-red-200
                                dark:bg-red-950/50 dark:ring-red-900/50">
                        <svg class="h-5 w-5 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        </svg>
                    </div>

                    {{-- Text --}}
                    <div class="min-w-0 flex-1">
                        <h3 id="confirm-title" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            Confirmar remoção
                        </h3>
                        <p id="confirm-message" class="mt-1 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                            Deseja remover este usuário? Esta ação não pode ser desfeita.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2 border-t px-6 py-4
                        border-slate-100 dark:border-zinc-800">
                <button id="confirm-cancel" type="button"
                        class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150
                               text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900
                               dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                    Cancelar
                </button>
                <button id="confirm-ok" type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-xs
                               transition-all duration-150 hover:bg-red-500 active:scale-[0.98]">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                    Remover
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    // ── Sidebar collapse (desktop) ───────────────────────────────────────────
    var COLLAPSED_KEY = 'sidebar-collapsed';
    var sidebar     = document.getElementById('sidebar');
    var mainWrapper = document.getElementById('main-wrapper');
    var overlay     = document.getElementById('sidebar-overlay');
    var logoEl      = document.getElementById('sidebar-logo');
    var profileBtn  = document.getElementById('profile-btn');

    function isDesktop() { return window.innerWidth >= 1024; }

    function isCollapsed() {
        var val = localStorage.getItem(COLLAPSED_KEY);
        return val === null ? true : val === 'true';
    }

    function applySidebarState(collapsed) {
        if (collapsed) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-16');
            mainWrapper.classList.remove('lg:pl-64');
            mainWrapper.classList.add('lg:pl-16');

            document.querySelectorAll('.nav-label, .sidebar-label-group').forEach(function (el) {
                el.classList.add('hidden');
            });

            document.querySelectorAll('.accordion-panel').forEach(function (el) {
                el.classList.add('hidden');
            });
            document.querySelectorAll('.accordion-chevron').forEach(function (el) {
                el.style.transform = '';
            });

            document.querySelectorAll('.nav-link').forEach(function (el) {
                el.classList.add('justify-center');
                el.classList.remove('gap-3', 'px-3');
            });

            if (logoEl) {
                logoEl.classList.add('justify-center');
                logoEl.classList.remove('gap-3', 'px-5');
                logoEl.classList.add('px-2');
            }

            if (profileBtn) {
                profileBtn.classList.add('justify-center');
                profileBtn.classList.remove('gap-3', 'px-3');
            }
        } else {
            sidebar.classList.add('w-64');
            sidebar.classList.remove('w-16');
            mainWrapper.classList.add('lg:pl-64');
            mainWrapper.classList.remove('lg:pl-16');

            document.querySelectorAll('.nav-label, .sidebar-label-group').forEach(function (el) {
                el.classList.remove('hidden');
            });

            document.querySelectorAll('.nav-link').forEach(function (el) {
                el.classList.remove('justify-center');
                el.classList.add('gap-3', 'px-3');
            });

            if (logoEl) {
                logoEl.classList.remove('justify-center', 'px-2');
                logoEl.classList.add('gap-3', 'px-5');
            }

            if (profileBtn) {
                profileBtn.classList.remove('justify-center');
                profileBtn.classList.add('gap-3', 'px-3');
            }
        }
    }

    function toggleSidebar() {
        var collapsed = !isCollapsed();
        localStorage.setItem(COLLAPSED_KEY, String(collapsed));
        applySidebarState(collapsed);
    }

    // ── Mobile off-canvas ────────────────────────────────────────────────────
    function openMobileSidebar() {
        // Ensure full sidebar on mobile
        sidebar.classList.add('w-64');
        sidebar.classList.remove('w-16');
        document.querySelectorAll('.nav-label, .sidebar-label-group').forEach(function (el) {
            el.classList.remove('hidden');
        });
        document.querySelectorAll('.nav-link').forEach(function (el) {
            el.classList.remove('justify-center');
            el.classList.add('gap-3', 'px-3');
        });
        if (logoEl) {
            logoEl.classList.remove('justify-center', 'px-2');
            logoEl.classList.add('gap-3', 'px-5');
        }
        if (profileBtn) {
            profileBtn.classList.remove('justify-center');
            profileBtn.classList.add('gap-3', 'px-3');
        }

        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';

        // Restore desktop collapsed state after slide-out
        setTimeout(function () {
            if (isDesktop()) { applySidebarState(isCollapsed()); }
        }, 300);
    }

    // Apply saved state on load without animation
    if (isDesktop()) {
        sidebar.style.transition = 'none';
        mainWrapper.style.transition = 'none';
        applySidebarState(isCollapsed());
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                sidebar.style.transition = '';
                mainWrapper.style.transition = '';
            });
        });
    }

    // Toggle button: desktop ↔ collapse/expand | mobile ↔ off-canvas
    document.getElementById('sidebar-toggle')?.addEventListener('click', function () {
        if (isDesktop()) {
            toggleSidebar();
        } else {
            sidebar.classList.contains('-translate-x-full') ? openMobileSidebar() : closeMobileSidebar();
        }
    });

    overlay?.addEventListener('click', closeMobileSidebar);

    // ── Profile dropdown ────────────────────────────────────────────────────
    var profileDropdown = document.getElementById('profile-dropdown');
    var profileChevron  = document.getElementById('profile-chevron');

    function setProfile(open) {
        profileDropdown.classList.toggle('hidden', !open);
        profileBtn.setAttribute('aria-expanded', String(open));
        if (profileChevron) {
            profileChevron.style.transform = open ? 'rotate(180deg)' : '';
        }
    }

    profileBtn?.addEventListener('click', function (e) {
        e.stopPropagation();
        setProfile(profileDropdown.classList.contains('hidden'));
    });
    document.addEventListener('click', function () { setProfile(false); });
    profileDropdown?.addEventListener('click', function (e) { e.stopPropagation(); });

    // ── Accordion nav ───────────────────────────────────────────────────────
    document.querySelectorAll('.accordion-trigger').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            if (isDesktop() && isCollapsed()) { return; }

            var key    = trigger.dataset.accordion;
            var panel  = document.getElementById('accordion-' + key);
            var chevron = trigger.querySelector('.accordion-chevron');
            if (!panel) { return; }

            var isOpen = !panel.classList.contains('hidden');
            panel.classList.toggle('hidden', isOpen);
            if (chevron) { chevron.style.transform = isOpen ? '' : 'rotate(180deg)'; }
        });
    });

    // Auto-open accordion if panel has data-active attribute (set server-side)
    // Skip if sidebar is collapsed — panels must stay hidden when icons-only
    if (!isDesktop() || !isCollapsed()) {
        document.querySelectorAll('.accordion-panel[data-active]').forEach(function (panel) {
            panel.classList.remove('hidden');
            var trigger = panel.previousElementSibling;
            var chevron = trigger && trigger.querySelector('.accordion-chevron');
            if (chevron) { chevron.style.transform = 'rotate(180deg)'; }
        });
    }

    // ── Theme toggle ────────────────────────────────────────────────────────
    document.getElementById('theme-toggle')?.addEventListener('click', function () {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });

    // ── Confirm Modal ───────────────────────────────────────────────────────
    var modal       = document.getElementById('confirm-modal');
    var mOverlay    = document.getElementById('confirm-overlay');
    var mPanel      = document.getElementById('confirm-panel');
    var mMessage    = document.getElementById('confirm-message');
    var mCancel     = document.getElementById('confirm-cancel');
    var mOk         = document.getElementById('confirm-ok');
    var pendingForm = null;

    function openModal(form, userName) {
        pendingForm = form;
        mMessage.textContent = 'Deseja remover ' + userName + '? Esta ação não pode ser desfeita.';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            mOverlay.classList.add('opacity-100');
            mPanel.classList.remove('opacity-0', 'scale-95');
            mPanel.classList.add('opacity-100', 'scale-100');
        });
    }

    function closeModal() {
        mOverlay.classList.remove('opacity-100');
        mPanel.classList.add('opacity-0', 'scale-95');
        mPanel.classList.remove('opacity-100', 'scale-100');
        setTimeout(function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            pendingForm = null;
        }, 200);
    }

    mCancel?.addEventListener('click', closeModal);

    mOk?.addEventListener('click', function () {
        if (pendingForm) { pendingForm.submit(); }
    });

    modal?.addEventListener('click', function (e) {
        if (e.target === modal || e.target === mOverlay) { closeModal(); }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) { closeModal(); }
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            openModal(form, form.dataset.userName || 'este usuário');
        });
    });
})();
</script>

</body>
</html>
