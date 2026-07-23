@props(['title' => 'Dashboard', 'noHeader' => false])
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

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

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
            $authUser = auth()->user();
            $isAdmin = $authUser?->role === \App\Enums\UserRole::Administrador;
            $isSupervisor = $authUser?->role === \App\Enums\UserRole::Supervisor;
            $isVisualizador = $authUser?->role === \App\Enums\UserRole::Visualizador;
            $canManageUsers = $isAdmin;
            $canManageCadastros = $isAdmin || $isSupervisor;
            $isCadastrosActive = request()->routeIs('users.*', 'divisoes.*', 'subdivisoes.*', 'equipamentos.*', 'tipos-equipamentos.*', 'modelos-equipamentos.*', 'motoristas.*');
            $isOcorrenciasActive = request()->routeIs('responsaveis.*', 'tipos-ocorrencia.*', 'justificativas.*', 'ocorrencias.*');
            $isReporteActive     = request()->routeIs('reportes.*');
        @endphp
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-2 py-4">

            {{-- Cadastros (accordion) — apenas Administrador e Supervisor --}}
            @if($canManageCadastros || $canManageUsers)
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
                    @if($canManageUsers)
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('users.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Usuários
                    </a>
                    @endif

                    {{-- Divisões → Motoristas — apenas Administrador e Supervisor --}}
                    @if($canManageCadastros)
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

                    {{-- Cercas --}}
                    <a href="{{ route('cercas.index') }}"
                       class="flex items-center gap-2 rounded-lg py-2 pl-3 pr-3 text-sm font-medium
                              transition-all duration-200
                              {{ request()->routeIs('cercas.*') ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800/70 dark:text-zinc-100' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-500 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                        Cercas
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Mapa Geral --}}
            <a href="{{ route('mapa-geral.index') }}"
               title="Mapa Geral"
               class="nav-link flex items-center justify-center rounded-lg py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('mapa-geral.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z"/>
                </svg>
                <span class="nav-label hidden whitespace-nowrap">Mapa Geral</span>
            </a>

            {{-- Dashboard — visível apenas para quem tem permissão --}}
            @can('access-dashboard')
            <a href="{{ route('dashboard.status') }}"
               title="Dashboard"
               class="nav-link flex items-center justify-center rounded-lg py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('dashboard.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                </svg>
                <span class="nav-label hidden whitespace-nowrap">Dashboard</span>
            </a>
            @endcan

            {{-- Alertas — indisponível para o perfil Visualizador --}}
            @unless($isVisualizador)
            <a href="{{ route('alertas.index') }}"
               title="Alertas"
               class="nav-link flex items-center justify-center rounded-lg py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('alertas.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
                <span class="nav-label hidden whitespace-nowrap">Alertas</span>
            </a>
            @endunless

            {{-- Demandas — indisponível para o perfil Visualizador --}}
            @unless($isVisualizador)
            <a href="{{ route('demandas.index') }}"
               title="Demandas"
               class="nav-link flex items-center justify-center rounded-lg py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('demandas.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                <span class="nav-label hidden whitespace-nowrap">Demandas</span>
            </a>
            @endunless

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
        @unless($noHeader)
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

                {{-- Sino de alertas — oculto para o perfil Visualizador --}}
                @unless($isVisualizador)
                <div class="relative" id="alertas-bell-wrapper">
                    <button id="alertas-bell-btn" type="button" aria-label="Alertas"
                            class="relative h-8 w-8 rounded-lg text-zinc-500 transition-colors duration-200
                                   hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800/70">
                        <svg class="absolute inset-0 m-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                        </svg>
                        <span id="alertas-bell-badge"
                              class="absolute -right-0.5 -top-0.5 hidden min-w-[16px] rounded-full bg-rose-600 px-1 text-center text-[10px] font-bold leading-4 text-white">0</span>
                    </button>

                    {{-- Dropdown --}}
                    <div id="alertas-bell-dropdown"
                         class="absolute right-0 top-full z-50 mt-2 hidden w-80 overflow-hidden rounded-xl border bg-white shadow-xl
                                border-slate-200 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-600">Alertas</p>
                            <a href="{{ route('alertas.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">Ver todos</a>
                        </div>
                        <div id="alertas-bell-list" class="max-h-80 overflow-y-auto py-1">
                            <p class="px-4 py-6 text-center text-xs text-zinc-400 dark:text-zinc-600">Carregando…</p>
                        </div>
                    </div>
                </div>
                @endunless

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
        @endunless

        {{-- Content --}}
        <main class="min-h-0 flex-1 overflow-y-auto [overflow-x:clip] px-4 sm:px-6 lg:px-8">

            @if(session('error'))
                <div id="flash-error"
                     class="mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm
                            border-rose-200 bg-rose-50 text-rose-700
                            dark:border-rose-800/40 dark:bg-rose-950/30 dark:text-rose-400">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                    </svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button onclick="document.getElementById('flash-error').remove()"
                            class="shrink-0 rounded p-0.5 opacity-50 transition-opacity hover:opacity-100">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700
                            dark:border-rose-800/40 dark:bg-rose-950/30 dark:text-rose-400">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach($errors->all() as $erro)
                            <li>{{ $erro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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
    var sidebar     = document.getElementById('sidebar');
    var mainWrapper = document.getElementById('main-wrapper');
    var overlay     = document.getElementById('sidebar-overlay');
    var logoEl      = document.getElementById('sidebar-logo');
    var profileBtn  = document.getElementById('profile-btn');

    // Sempre fechado ao carregar a página — sem persistência em localStorage
    var _collapsed = true;

    function isDesktop() { return window.innerWidth >= 1024; }

    function isCollapsed() { return _collapsed; }

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
        _collapsed = !_collapsed;
        applySidebarState(_collapsed);
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
        trigger.addEventListener('click', function (e) {
            // Detecta modo ícone verificando se o label está oculto
            var label    = trigger.querySelector('.nav-label');
            var iconOnly = isDesktop() && label && label.classList.contains('hidden');

            if (iconOnly) {
                // <a> com href: deixa o browser navegar normalmente
                if (trigger.tagName === 'A') { return; }
                // <button> com data-href: navega via JS
                var href = trigger.dataset.href;
                if (href) { window.location.href = href; }
                return;
            }

            // Sidebar expandido: comportamento de accordion
            e.preventDefault();
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

@unless($isVisualizador)
<script>
(function () {
    var btn      = document.getElementById('alertas-bell-btn');
    var dropdown = document.getElementById('alertas-bell-dropdown');
    var badge    = document.getElementById('alertas-bell-badge');
    var list     = document.getElementById('alertas-bell-list');
    var wrapper  = document.getElementById('alertas-bell-wrapper');
    if (! btn) { return; }

    var _pendentesUrl = '{{ route('alertas.pendentes') }}';
    var _disparadosAnterior = null;

    // ── Som de alerta (Web Audio) — destravado na 1ª interação do usuário ──
    var _audioCtx = null;
    function _initAudio() {
        if (! _audioCtx) {
            try { _audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
        }
        if (_audioCtx && _audioCtx.state === 'suspended') { _audioCtx.resume(); }
    }
    document.addEventListener('click', _initAudio);
    document.addEventListener('keydown', _initAudio);

    function _tocarBeep() {
        if (! _audioCtx) { return; }
        [0, 0.22].forEach(function (offset) {
            var osc  = _audioCtx.createOscillator();
            var gain = _audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            var t = _audioCtx.currentTime + offset;
            gain.gain.setValueAtTime(0.0001, t);
            gain.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.18);
            osc.connect(gain); gain.connect(_audioCtx.destination);
            osc.start(t); osc.stop(t + 0.2);
        });
    }

    function escHtml(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function render(data) {
        var disparados = data.disparados || 0;

        // Toca o som quando surge um novo alerta disparado (ou ja ha algum no 1º load)
        if ((_disparadosAnterior === null && disparados > 0) || (_disparadosAnterior !== null && disparados > _disparadosAnterior)) {
            _tocarBeep();
        }
        _disparadosAnterior = disparados;

        if (disparados > 0) {
            badge.textContent = disparados > 99 ? '99+' : disparados;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        var alertas = data.alertas || [];
        if (alertas.length === 0) {
            list.innerHTML = '<p class="px-4 py-6 text-center text-xs text-zinc-400 dark:text-zinc-600">Nenhum alerta pendente.</p>';
            return;
        }

        list.innerHTML = alertas.map(function (a) {
            var cor = a.disparado
                ? 'text-rose-600 dark:text-rose-400'
                : 'text-sky-600 dark:text-sky-400';
            var ponto = a.disparado ? 'bg-rose-500' : 'bg-sky-500';
            return '<div class="border-b border-slate-50 px-4 py-2.5 last:border-0 dark:border-zinc-800/60">'
                + '<div class="flex items-center gap-1.5">'
                + '<span class="h-1.5 w-1.5 shrink-0 rounded-full ' + ponto + '"></span>'
                + '<span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">' + escHtml(a.veiculo || '—') + '</span>'
                + '<span class="ml-auto text-[11px] ' + cor + '">' + escHtml(a.data_hora) + '</span>'
                + '</div>'
                + '<p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-500">' + escHtml(a.lembrete) + '</p>'
                + '</div>';
        }).join('');
    }

    function carregar() {
        fetch(_pendentesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(function () {});
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
        if (! dropdown.classList.contains('hidden')) { carregar(); }
    });
    document.addEventListener('click', function (e) {
        if (wrapper && ! wrapper.contains(e.target)) { dropdown.classList.add('hidden'); }
    });

    carregar();
    setInterval(carregar, 60000);
})();
</script>
@endunless

@stack('scripts')

</body>
</html>
