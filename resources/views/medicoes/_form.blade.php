@php
    $medicao = $medicao ?? null;
    $inputCls = 'block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200 placeholder:text-zinc-400 focus:ring-2 border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10 dark:[color-scheme:dark]';
@endphp

<div class="space-y-1.5">
    <label for="nome_medicao" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
        Nome da Medição <span class="text-red-500">*</span>
    </label>
    <input id="nome_medicao" type="text" name="nome_medicao" value="{{ old('nome_medicao', $medicao?->nome_medicao) }}" autofocus
           placeholder="Ex.: Medição Julho/2026" class="{{ $inputCls }}">
    @error('nome_medicao')
        <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div class="space-y-1.5">
        <label for="data_inicio" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            Data de Início <span class="text-red-500">*</span>
        </label>
        <input id="data_inicio" type="date" name="data_inicio"
               value="{{ old('data_inicio', $medicao?->data_inicio?->format('Y-m-d')) }}" class="{{ $inputCls }}">
        @error('data_inicio')
            <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    <div class="space-y-1.5">
        <label for="data_fim" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
            Data de Fim <span class="text-red-500">*</span>
        </label>
        <input id="data_fim" type="date" name="data_fim"
               value="{{ old('data_fim', $medicao?->data_fim?->format('Y-m-d')) }}" class="{{ $inputCls }}">
        @error('data_fim')
            <p class="text-xs text-red-500 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
<p class="text-xs text-zinc-400 dark:text-zinc-600">O período é inclusivo: demandas do dia da data de fim (até 23:59) entram na medição.</p>
