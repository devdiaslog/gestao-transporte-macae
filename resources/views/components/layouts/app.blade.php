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
<body class="h-full bg-slate-50 text-zinc-900 antialiased dark:bg-black dark:text-zinc-100">

<div class="flex h-full">

    {{-- ─── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col
                  border-r border-slate-200 bg-white
                  dark:border-zinc-800 dark:bg-zinc-950
                  -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0">

        {{-- Logo --}}
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5 dark:border-zinc-800">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-900 dark:bg-white">
                <svg class="h-4 w-4 text-white dark:text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Gestão de Transporte
                </p>
                <p class="truncate text-[11px] text-zinc-400 dark:text-zinc-600">Macaé — RJ</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-600">
                Principal
            </p>

            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('users.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Usuários
            </a>

            <a href="{{ route('control-tower.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium
                      transition-all duration-200
                      {{ request()->routeIs('control-tower.*')
                          ? 'bg-zinc-900 text-white dark:bg-zinc-800 dark:text-zinc-100'
                          : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/70 dark:hover:text-zinc-100' }}">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/>
                </svg>
                Torre de Controle
            </a>
        </nav>

        {{-- User footer --}}
        <div class="shrink-0 border-t border-slate-200 p-3 dark:border-zinc-800">
            <div class="relative" id="profile-wrapper">
                <button id="profile-btn" aria-expanded="false" aria-haspopup="true"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm
                               transition-colors duration-200
                               hover:bg-zinc-100 dark:hover:bg-zinc-800/70">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full
                                bg-zinc-200 ring-2 ring-zinc-300/50
                                dark:bg-zinc-800 dark:ring-zinc-700/50">
                        <span class="text-[11px] font-bold text-zinc-600 dark:text-zinc-300">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1 text-left">
                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ auth()->user()->name ?? 'Usuário' }}
                        </p>
                        <p class="truncate text-[11px] text-zinc-400 dark:text-zinc-600">
                            {{ auth()->user()->email ?? '' }}
                        </p>
                    </div>
                    <svg id="profile-chevron"
                         class="h-3.5 w-3.5 shrink-0 text-zinc-400 transition-transform duration-200 dark:text-zinc-600"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                {{-- Profile dropdown --}}
                <div id="profile-dropdown" role="menu"
                     class="absolute bottom-full left-0 right-0 mb-2 hidden rounded-xl border bg-white shadow-xl
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
    <div class="flex min-h-full flex-1 flex-col lg:pl-64">

        {{-- Header --}}
        <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-4 px-6
                       border-b border-slate-200 bg-white/80 backdrop-blur-md
                       dark:border-zinc-800 dark:bg-black/90">

            <button id="sidebar-toggle"
                    class="lg:hidden -ml-1 rounded-lg p-1.5 text-zinc-500 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800/70">
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
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">

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
    // ── Sidebar mobile ──────────────────────────────────────────────────────
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebar-overlay');
    var toggler  = document.getElementById('sidebar-toggle');

    function openSidebar()  { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar.classList.add('-translate-x-full');    overlay.classList.add('hidden');    document.body.style.overflow = ''; }

    toggler?.addEventListener('click', function () {
        sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
    });
    overlay?.addEventListener('click', closeSidebar);

    // ── Profile dropdown ────────────────────────────────────────────────────
    var profileBtn      = document.getElementById('profile-btn');
    var profileDropdown = document.getElementById('profile-dropdown');
    var profileChevron  = document.getElementById('profile-chevron');

    function setProfile(open) {
        profileDropdown.classList.toggle('hidden', !open);
        profileBtn.setAttribute('aria-expanded', String(open));
        profileChevron.style.transform = open ? 'rotate(180deg)' : '';
    }

    profileBtn?.addEventListener('click', function (e) {
        e.stopPropagation();
        setProfile(profileDropdown.classList.contains('hidden'));
    });
    document.addEventListener('click', function () { setProfile(false); });
    profileDropdown?.addEventListener('click', function (e) { e.stopPropagation(); });

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
        // Trigger transition on next frame
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
        if (pendingForm) {
            pendingForm.submit();
        }
    });

    // Close on overlay click
    modal?.addEventListener('click', function (e) {
        if (e.target === modal || e.target === mOverlay) { closeModal(); }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) { closeModal(); }
    });

    // Intercept all delete forms
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
