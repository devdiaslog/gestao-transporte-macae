<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Erro') — Gestão de Transporte</title>

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
<body class="h-full bg-slate-50 font-[Inter] text-zinc-900 antialiased dark:bg-black dark:text-zinc-100">

    <div class="flex min-h-full flex-col items-center justify-center px-6 py-24">

        {{-- Logo --}}
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-900 shadow-sm dark:bg-white">
            <svg class="h-6 w-6 text-white dark:text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>

        {{-- Content --}}
        <div class="mt-8 text-center">
            @yield('content')
        </div>

        {{-- Actions --}}
        <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
            @yield('actions')
        </div>

        {{-- Footer --}}
        <p class="mt-16 text-xs text-zinc-400 dark:text-zinc-600">
            Gestão de Transporte &mdash; Macaé
        </p>

    </div>

</body>
</html>
