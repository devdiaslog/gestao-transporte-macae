<x-layouts.app title="Editar Cerca">


    {{-- Page header --}}
    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('cercas.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200
                  text-slate-500 transition-all duration-200
                  hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700
                  dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            <span class="sr-only">Voltar</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Cerca</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $cerca->nome }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('cercas.update', $cerca) }}" id="cerca-form" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="poligono" id="poligono-input" value="{{ $cerca->poligono ? json_encode($cerca->poligono) : '' }}">

        {{-- ─── Dados básicos ───────────────────────────────────────────────── --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Dados da Cerca</h3>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Nome --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label for="nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Nome <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="nome"
                        type="text"
                        name="nome"
                        value="{{ old('nome', $cerca->nome) }}"
                        autofocus
                        placeholder="Ex: Parque de Tubos - Macaé"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                               placeholder:text-zinc-400 focus:ring-2
                               {{ $errors->has('nome')
                                   ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20'
                                   : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"
                    />
                    @error('nome')
                        <p class="flex items-center gap-1.5 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Atividade --}}
                <div class="space-y-1.5">
                    <label for="atividade" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Atividade
                    </label>
                    <select
                        id="atividade"
                        name="atividade"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                               focus:ring-2 border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"
                    >
                        <option value="">— Selecione —</option>
                        @foreach(\App\Enums\AtividadeCerca::cases() as $caso)
                            <option value="{{ $caso->value }}"
                                @selected(old('atividade', $cerca->atividade?->value) === $caso->value)>
                                {{ $caso->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tempos --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="tempo_minimo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tempo Mínimo <span class="text-red-500">*</span>
                            <span class="ml-1 font-normal text-zinc-400">(min)</span>
                        </label>
                        <input
                            id="tempo_minimo"
                            type="number"
                            name="tempo_minimo"
                            value="{{ old('tempo_minimo', $cerca->tempo_minimo) }}"
                            min="1"
                            max="1440"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                                   focus:ring-2 border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10
                                   {{ $errors->has('tempo_minimo') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : '' }}"
                        />
                        @error('tempo_minimo')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="tempo_maximo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Tempo Máximo <span class="text-red-500">*</span>
                            <span class="ml-1 font-normal text-zinc-400">(min)</span>
                        </label>
                        <input
                            id="tempo_maximo"
                            type="number"
                            name="tempo_maximo"
                            value="{{ old('tempo_maximo', $cerca->tempo_maximo) }}"
                            min="1"
                            max="1440"
                            class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                                   focus:ring-2 border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                                   dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10
                                   {{ $errors->has('tempo_maximo') ? 'border-red-400 focus:border-red-500 focus:ring-red-500/10' : '' }}"
                        />
                        @error('tempo_maximo')
                            <p class="text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Status --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6 py-1">
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="status" value="1"
                                   @checked(old('status', $cerca->status ? '1' : '0') === '1')
                                   class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativa</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="status" value="0"
                                   @checked(old('status', $cerca->status ? '1' : '0') === '0')
                                   class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Inativa</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        @include('cercas._editor', ['poligono' => old('poligono') ? json_decode(old('poligono'), true) : ($cerca->poligono ?? []), 'vizinhas' => $cercasExistentes])


        {{-- ─── Ações ──────────────────────────────────────────────────────── --}}
        <div class="mt-6 flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <a href="{{ route('cercas.index') }}"
               class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                      border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all
                           hover:bg-zinc-700 active:scale-[0.98]
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Salvar Alterações
            </button>
        </div>

    </form>

    
</x-layouts.app>
