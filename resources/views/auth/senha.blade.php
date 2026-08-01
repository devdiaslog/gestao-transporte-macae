<x-layouts.app title="Minha Senha">

    <div class="mx-auto mt-8 max-w-lg">
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Alterar minha senha</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $obrigatoria
                ? 'Sua senha foi redefinida pelo administrador. Defina uma nova senha para continuar usando o sistema.'
                : 'Escolha uma senha de pelo menos 8 caracteres.' }}
        </p>

        @if($obrigatoria)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                Troca de senha obrigatória — o acesso às demais telas fica bloqueado até você definir a nova senha.
            </div>
        @endif

        <form method="POST" action="{{ route('senha.atualizar') }}"
              class="mt-6 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" novalidate>
            @csrf
            @method('PUT')

            @php
                $campo = 'block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all focus:ring-2 border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100';
            @endphp

            <div>
                <label for="senha_atual" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Senha atual <span class="text-red-500">*</span>
                </label>
                <input id="senha_atual" type="password" name="senha_atual" autocomplete="current-password" class="{{ $campo }}">
                @error('senha_atual')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Nova senha <span class="text-red-500">*</span>
                </label>
                <input id="password" type="password" name="password" autocomplete="new-password" class="{{ $campo }}">
                @error('password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Confirmar nova senha <span class="text-red-500">*</span>
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="{{ $campo }}">
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5 dark:border-zinc-800">
                @unless($obrigatoria)
                    <a href="{{ url()->previous() }}"
                       class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        Cancelar
                    </a>
                @endunless
                <button type="submit"
                        class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900">
                    Salvar nova senha
                </button>
            </div>
        </form>
    </div>

</x-layouts.app>
