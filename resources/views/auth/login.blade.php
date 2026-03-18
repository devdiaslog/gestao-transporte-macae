<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Gestão de Transporte</title>

    {{-- Anti-flash: must be the first script in <head> --}}
    <script>
        (function () {
            const theme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || (!theme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full flex-col items-center justify-center bg-slate-50 antialiased transition-colors duration-300 dark:bg-slate-950">

    {{-- Theme toggle (top right) --}}
    <div class="fixed right-4 top-4">
        <button id="theme-toggle"
                title="Alternar tema"
                aria-label="Alternar tema claro/escuro"
                class="relative h-8 w-8 rounded-lg text-slate-400 transition-colors duration-200
                       hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="absolute inset-0 m-auto h-4 w-4
                        rotate-90 scale-0 transition-all duration-300
                        dark:rotate-0 dark:scale-100"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
            </svg>
            <svg class="absolute inset-0 m-auto h-4 w-4
                        rotate-0 scale-100 transition-all duration-300
                        dark:-rotate-90 dark:scale-0"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
            </svg>
        </button>
    </div>

    <div class="w-full max-w-sm px-6">

        {{-- Brand --}}
        <div class="mb-8 flex flex-col items-center gap-4 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-900 shadow-sm dark:bg-white">
                <svg class="h-6 w-6 text-white dark:text-slate-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    Gestão de Transporte
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Acesse sua conta para continuar
                </p>
            </div>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">

            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                {{-- E-mail --}}
                <div class="space-y-1.5">
                    <label for="email"
                           class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        E-mail
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="seu@email.com"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200
                               placeholder:text-slate-400
                               focus:ring-2
                               {{ $errors->has('email')
                                   ? 'border-red-400 bg-white text-slate-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-900 dark:text-slate-100'
                                   : 'border-slate-300 bg-white text-slate-900 focus:border-slate-900 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-slate-400 dark:focus:ring-slate-400/10' }}"
                    />
                    @error('email')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Senha --}}
                <div class="space-y-1.5">
                    <label for="password"
                           class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Senha
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                               shadow-xs outline-none transition-all duration-200
                               placeholder:text-slate-400
                               focus:ring-2
                               {{ $errors->has('password')
                                   ? 'border-red-400 bg-white text-slate-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-900 dark:text-slate-100'
                                   : 'border-slate-300 bg-white text-slate-900 focus:border-slate-900 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-slate-400 dark:focus:ring-slate-400/10' }}"
                    />
                    @error('password')
                        <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Lembrar-me --}}
                <div class="flex items-center gap-2.5">
                    <input
                        id="remember"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 accent-slate-900
                               focus:ring-2 focus:ring-slate-900/20
                               dark:border-slate-600 dark:accent-white dark:focus:ring-slate-300/20"
                    />
                    <label for="remember" class="select-none text-sm text-slate-600 dark:text-slate-400">
                        Lembrar de mim
                    </label>
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5
                           text-sm font-semibold text-white shadow-xs
                           transition-all duration-200
                           hover:bg-slate-700 active:scale-[0.98]
                           dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    Entrar na conta
                    <svg class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="mt-8 text-center text-xs text-slate-400 dark:text-slate-600">
            Gestão de Transporte — Macaé &copy; {{ date('Y') }}
        </p>
    </div>

<script>
(function () {
    const btn = document.getElementById('theme-toggle');
    const root = document.documentElement;
    btn?.addEventListener('click', function () {
        const isDark = root.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
})();
</script>

</body>
</html>
