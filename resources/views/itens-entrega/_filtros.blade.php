@php
    /** @var string $rota — nome da rota do formulário; os demais vêm do controller */
    $campo = 'rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100';
    $rotulo = 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
    $temFiltro = array_filter($filtros) || request('situacao') || request('status') || $dias !== 3;
@endphp

<form method="GET" action="{{ route($rota) }}" class="mt-6 flex flex-wrap items-end gap-3">
    @if($rota === 'itens-entrega.trecho')
        <input type="hidden" name="origem_norm" value="{{ $origemTrecho }}">
        <input type="hidden" name="destino_norm" value="{{ $destinoTrecho }}">
    @endif
    @if(request('situacao'))<input type="hidden" name="situacao" value="{{ request('situacao') }}">@endif

    <div class="flex min-w-56 flex-1 overflow-hidden rounded-lg border border-slate-300 bg-white shadow-xs focus-within:border-zinc-900 dark:border-zinc-800 dark:bg-zinc-950">
        <span class="flex items-center pl-3.5 text-zinc-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
        </span>
        <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}" placeholder="RT, carga ou contentor…" autocomplete="off"
               class="flex-1 bg-transparent px-3 py-2.5 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100">
    </div>

    {{-- Status do SAP: vários de uma vez --}}
    <div>
        <label class="{{ $rotulo }}">Status no SAP</label>
        <select name="status[]" multiple size="3" class="{{ $campo }} min-w-52 py-1.5">
            @foreach($statusDisponiveis as $s)
                <option value="{{ $s->value }}" @selected(in_array($s->value, $statusSelecionados, true))>
                    {{ $s->value }} — {{ $s->label() }}
                </option>
            @endforeach
        </select>
        <p class="mt-0.5 text-[10px] text-zinc-400">Ctrl para escolher mais de um</p>
    </div>

    {{-- Recorte de previsão: é por aqui que o operador acha o que replanejar --}}
    <div>
        <label class="{{ $rotulo }}">Previsão</label>
        <select name="previsao" class="{{ $campo }}" onchange="alternarDiasPrevisao(this)">
            <option value="">Todas</option>
            @foreach($filtrosPrevisao as $chave => $texto)
                <option value="{{ $chave }}" @selected(($filtros['previsao'] ?? '') === $chave)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>

    <div id="campo-dias-previsao" class="{{ ($filtros['previsao'] ?? '') === 'proxima' ? '' : 'hidden' }}">
        <label class="{{ $rotulo }}">Dias</label>
        <input type="number" name="dias_previsao" value="{{ $diasPrevisao }}" min="0" max="90"
               class="{{ $campo }} w-20">
    </div>

    <div>
        <label class="{{ $rotulo }}">Prazo vence em até</label>
        <select name="dias" class="{{ $campo }}">
            @foreach([1 => 'D+1', 3 => 'D+3', 7 => 'D+7', 15 => 'D+15', 30 => 'D+30', 0 => 'Todos'] as $valor => $texto)
                <option value="{{ $valor }}" @selected($dias === $valor)>{{ $texto }}</option>
            @endforeach
        </select>
    </div>

    <label class="flex items-center gap-2 pb-2.5 text-sm text-zinc-700 dark:text-zinc-300">
        <input type="checkbox" name="ausentes" value="1" @checked(request()->boolean('ausentes'))
               class="h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-700">
        Sumiram do SAP
    </label>

    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        Filtrar
    </button>

    @if($temFiltro)
        <a href="{{ route($rota, $rota === 'itens-entrega.trecho' ? ['origem_norm' => $origemTrecho, 'destino_norm' => $destinoTrecho] : []) }}"
           class="pb-2.5 text-sm text-zinc-500 hover:text-zinc-800 dark:text-zinc-400">Limpar</a>
    @endif
</form>

@push('scripts')
<script>
    // O campo de dias só faz sentido para "vence em até".
    window.alternarDiasPrevisao = function (select) {
        var campo = document.getElementById('campo-dias-previsao');
        if (campo) { campo.classList.toggle('hidden', select.value !== 'proxima'); }
    };
</script>
@endpush
