@php
    /** @var string $rota — nome da rota do formulário; os demais vêm do controller */
    $campo = 'h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-zinc-900 shadow-xs outline-none focus:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100';
    $rotulo = 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';
    $temFiltro = array_filter($filtros) || request('situacao') || request('status') || $dias !== 3;

    // Cada status ganha a própria cor quando ligado: o operador reconhece o
    // recorte pela cor antes de ler o texto.
    $coresStatus = [
        '03' => 'peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-800 dark:peer-checked:bg-sky-950/50 dark:peer-checked:text-sky-300',
        '04' => 'peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:text-indigo-800 dark:peer-checked:bg-indigo-950/50 dark:peer-checked:text-indigo-300',
        '13' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-800 dark:peer-checked:bg-amber-950/50 dark:peer-checked:text-amber-300',
        '18' => 'peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:text-orange-800 dark:peer-checked:bg-orange-950/50 dark:peer-checked:text-orange-300',
        '09' => 'peer-checked:border-zinc-500 peer-checked:bg-zinc-100 peer-checked:text-zinc-800 dark:peer-checked:bg-zinc-800 dark:peer-checked:text-zinc-200',
    ];
@endphp

<form method="GET" action="{{ route($rota) }}" class="mt-6 space-y-3">
    @if($rota === 'itens-entrega.trecho')
        <input type="hidden" name="origem_norm" value="{{ $origemTrecho }}">
        <input type="hidden" name="destino_norm" value="{{ $destinoTrecho }}">
    @endif
    @if(request('situacao'))<input type="hidden" name="situacao" value="{{ request('situacao') }}">@endif

    {{-- Cards e status dividem a mesma linha: cada centímetro de altura conta
         numa tela cuja função é mostrar tabela. --}}
    <div class="grid gap-3 xl:grid-cols-2">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            {{-- Total primeiro: é o universo do recorte; os demais explicam
                 como ele se divide. Clicar tira o filtro de situação. --}}
            @php $semSituacao = ! request('situacao'); @endphp
            <a href="{{ request()->fullUrlWithQuery(['situacao' => null, 'page' => null]) }}"
               class="flex items-center justify-between gap-3 rounded-xl border px-4 py-2.5 transition-all
                      {{ $semSituacao
                          ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800'
                          : 'border-slate-200 bg-white hover:border-slate-300 dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700' }}">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total de itens</span>
                <span class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $resumo['total'] }}</span>
            </a>

            @foreach($situacoesResumo as $situacao)
                @php $ativo = request('situacao') === $situacao; @endphp
                <a href="{{ request()->fullUrlWithQuery(['situacao' => $ativo ? null : $situacao, 'page' => null]) }}"
                   class="flex items-center justify-between gap-3 rounded-xl border px-4 py-2.5 transition-all
                          {{ $ativo
                              ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800'
                              : 'border-slate-200 bg-white hover:border-slate-300 dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700' }}">
                    <span class="flex items-center gap-1.5">
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $cores[$situacao]['dot'] }}"></span>
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $cores[$situacao]['label'] }}</span>
                    </span>
                    <span class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $resumo[$situacao] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Status: cada um é um botão que liga e desliga --}}
        <div class="flex flex-wrap content-center items-center gap-1.5">
            @foreach($statusDisponiveis as $s)
                <label class="cursor-pointer">
                    <input type="checkbox" name="status[]" value="{{ $s->value }}" class="peer sr-only"
                           @checked(in_array($s->value, $statusSelecionados, true))
                           onchange="this.form.submit()">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1.5 text-xs font-medium transition-all
                                 border-slate-200 bg-white text-zinc-500 hover:border-slate-300 hover:text-zinc-700
                                 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500 dark:hover:text-zinc-300
                                 {{ $coresStatus[$s->value] ?? '' }}">
                        <span class="font-bold tabular-nums opacity-60">{{ $s->value }}</span>
                        {{ $s->label() }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <div class="flex h-10 min-w-56 flex-1 overflow-hidden rounded-lg border border-slate-300 bg-white shadow-xs focus-within:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-950">
            <span class="flex items-center pl-3.5 text-zinc-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </span>
            <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}" autocomplete="off"
                   placeholder="RT, viagem, carga ou embalagem…"
                   class="flex-1 bg-transparent px-3 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100">
        </div>

        {{-- Ordem da lista: o critério escolhido aqui é o que a coluna "Ordem"
             numera, então o 1 é sempre a primeira rota a atender. Só existe na
             lista de rotas; a tela do trecho lista itens. --}}
        @isset($ordenacoes)
            <div>
                <label class="{{ $rotulo }}">Ordenar por</label>
                <select name="ordenar" class="{{ $campo }}">
                    @foreach($ordenacoes as $valor => $texto)
                        <option value="{{ $valor }}" @selected($ordenacao === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
        @endisset

        {{-- Pendência registrada pela equipe. Não depende do status do SAP:
             o item aparece aqui assim que a pendência é anotada. --}}
        <div>
            <label class="{{ $rotulo }}">Pendência</label>
            <select name="pendencia" class="{{ $campo }}">
                <option value="">Todas</option>
                @foreach($filtrosPendencia as $valor => $texto)
                    <option value="{{ $valor }}" @selected(request('pendencia') === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
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
            <input type="number" name="dias_previsao" value="{{ $diasPrevisao }}" min="0" max="90" class="{{ $campo }} w-20">
        </div>

        <div>
            <label class="{{ $rotulo }}">Prazo vence em até</label>
            <select name="dias" class="{{ $campo }}">
                <option value="vencidos" @selected($dias === -1)>Já vencidos</option>
                @foreach([1 => 'D+1', 3 => 'D+3', 7 => 'D+7', 15 => 'D+15', 30 => 'D+30', 0 => 'Todos'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected($dias === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex h-10 items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"
               title="Itens que não vieram na última importação">
            <input type="checkbox" name="ausentes" value="1" @checked(request()->boolean('ausentes'))
                   class="h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-700">
            Conferir no SAP
        </label>

        <button type="submit" class="h-10 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Filtrar
        </button>

        @if($temFiltro)
            <a href="{{ route($rota, $rota === 'itens-entrega.trecho' ? ['origem_norm' => $origemTrecho, 'destino_norm' => $destinoTrecho] : []) }}"
               class="flex h-10 items-center text-sm text-zinc-500 hover:text-zinc-800 dark:text-zinc-400">Limpar</a>
        @endif
    </div>
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
