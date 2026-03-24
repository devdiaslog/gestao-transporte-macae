<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Gestão de Transporte' }} — Macaé</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">

<div class="flex h-full">

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-white border-r border-zinc-200 dark:bg-zinc-900 dark:border-zinc-800 transition-transform duration-200 -translate-x-full lg:translate-x-0">

        {{-- Logo --}}
        <div class="flex h-16 items-center gap-3 border-b border-zinc-200 px-6 dark:border-zinc-800">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-zinc-900 dark:bg-white">
                <svg class="h-4 w-4 text-white dark:text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <span class="text-sm font-semibold tracking-tight">Gestão de Transporte</span>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
            <p class="px-3 pb-1 text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Menu</p>

            <a href="{{ route('users.index') }}"
               class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('users.*') ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Usuários
            </a>
        </nav>

        {{-- User footer --}}
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            <div class="relative">
                <button id="profile-btn"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800 transition-colors">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-300">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                    <div class="flex-1 truncate text-left">
                        <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ auth()->user()->name ?? 'Usuário' }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                    </svg>
                </button>

                {{-- Profile dropdown --}}
                <div id="profile-dropdown"
                     class="absolute bottom-full left-0 right-0 mb-1 hidden rounded-lg border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    {{-- Sidebar overlay (mobile) --}}
    <div id="sidebar-overlay"
         class="fixed inset-0 z-40 hidden bg-zinc-900/50 lg:hidden"
         aria-hidden="true"></div>

    {{-- Main content --}}
    <div class="flex flex-1 flex-col lg:pl-64">

        {{-- Top header --}}
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-zinc-200 bg-white/80 px-6 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/80">

            {{-- Mobile menu toggle --}}
            <button id="sidebar-toggle" class="lg:hidden rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>

            <h1 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $title ?? 'Dashboard' }}
            </h1>

            <div class="ml-auto flex items-center gap-3">
                {{-- Dark mode toggle --}}
                <button id="theme-toggle"
                        class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                        title="Alternar tema">
                    <svg id="icon-moon" class="h-4 w-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                    </svg>
                    <svg id="icon-sun" class="h-4 w-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                    </svg>
                </button>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 px-6 py-8">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-400"
                     id="flash-success">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                    <button onclick="document.getElementById('flash-success').remove()" class="ml-auto shrink-0 opacity-60 hover:opacity-100 transition-opacity">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

<script>
    // Sidebar mobile toggle
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleBtn = document.getElementById('sidebar-toggle');

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }

    toggleBtn?.addEventListener('click', () => {
        sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);

    // Profile dropdown
    const profileBtn = document.getElementById('profile-btn');
    const profileDropdown = document.getElementById('profile-dropdown');

    profileBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', () => profileDropdown?.classList.add('hidden'));

    // Dark mode
    const themeToggle = document.getElementById('theme-toggle');
    const root = document.documentElement;

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        root.classList.add('dark');
    }

    themeToggle?.addEventListener('click', () => {
        root.classList.toggle('dark');
        localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light');
    });
</script>

</body>
</html>
