<x-layouts.app title="Novo Usuário">

    {{-- Page header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('users.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200
                  text-slate-500 transition-all duration-200
                  hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700
                  dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            <span class="sr-only">Voltar</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Novo Usuário</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cadastre um novo usuário no sistema.</p>
        </div>
    </div>

    {{-- Focus card --}}
    <div class="mt-6 max-w-lg rounded-2xl border border-slate-200 bg-white p-8 shadow-sm dark:border-slate-800 dark:bg-slate-900">

        <form method="POST" action="{{ route('users.store') }}" class="space-y-5" novalidate>
            @csrf

            {{-- Name --}}
            <div class="space-y-1.5">
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Nome completo <span class="text-red-500">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="João da Silva"
                    class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-slate-400 focus:ring-2
                           {{ $errors->has('name')
                               ? 'border-red-400 bg-white text-slate-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-800 dark:text-slate-100'
                               : 'border-slate-300 bg-white text-slate-900 focus:border-slate-900 focus:ring-slate-900/10 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-slate-400 dark:focus:ring-slate-400/10' }}"
                />
                @error('name')
                    <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="space-y-1.5">
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    E-mail <span class="text-red-500">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="joao@exemplo.com"
                    class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-slate-400 focus:ring-2
                           {{ $errors->has('email')
                               ? 'border-red-400 bg-white text-slate-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-800 dark:text-slate-100'
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

            {{-- Status --}}
            <div class="space-y-1.5">
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Status <span class="text-red-500">*</span>
                </label>
                <select
                    id="status"
                    name="status"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900
                           shadow-xs outline-none transition-all duration-200
                           focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10
                           dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100
                           dark:focus:border-slate-400 dark:focus:ring-slate-400/10
                           {{ $errors->has('status') ? 'border-red-400 dark:border-red-600' : '' }}"
                >
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Divider --}}
            <div class="border-t border-slate-100 dark:border-slate-800"></div>

            {{-- Password --}}
            <div class="space-y-1.5">
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Senha <span class="text-red-500">*</span>
                </label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    class="block w-full rounded-lg border px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-slate-400 focus:ring-2
                           {{ $errors->has('password')
                               ? 'border-red-400 bg-white text-slate-900 focus:border-red-500 focus:ring-red-500/20 dark:border-red-600 dark:bg-slate-800 dark:text-slate-100'
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

            {{-- Password confirmation --}}
            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Confirmar senha <span class="text-red-500">*</span>
                </label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repita a senha"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm
                           shadow-xs outline-none transition-all duration-200
                           placeholder:text-slate-400 focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10
                           dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-slate-400 dark:focus:ring-slate-400/10"
                />
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-slate-800">
                <a href="{{ route('users.index') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 px-4 py-2.5
                          text-sm font-medium text-slate-700
                          transition-all duration-200
                          hover:border-slate-300 hover:bg-slate-50
                          dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                    Cancelar
                </a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5
                           text-sm font-semibold text-white shadow-xs
                           transition-all duration-200
                           hover:bg-slate-700 active:scale-[0.98]
                           dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                    </svg>
                    Criar Usuário
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
