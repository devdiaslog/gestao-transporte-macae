<x-layouts.app title="Trechos SAP">

@php
    $podeCriar = auth()->user()->can('trechos-sap.criar');
    $podeEditar = auth()->user()->can('trechos-sap.editar');
    $podeExcluir = auth()->user()->can('trechos-sap.excluir');

    $campo = 'h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-zinc-900 shadow-xs outline-none
              focus:border-zinc-400 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100';
    $rotulo = 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';

    $coresPrazo = [
        'normal' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        'expresso' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
        'manual' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400',
    ];
@endphp

<div class="mt-4 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Trechos SAP</h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Prazo acordado de cada origem→destino, usado para cobrar a entrega dos itens.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('trechos-sap.export', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                  text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Exportar
        </a>

        @if($podeCriar)
            <a href="{{ route('trechos-sap.modelo') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                      text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Baixar modelo
            </a>

            <button type="button" onclick="abrirModal('modal-importar')"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                           text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
                Importar planilha
            </button>

            <button type="button" onclick="abrirNovo()"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white
                           shadow-xs transition-all hover:bg-zinc-700 active:scale-[0.98]
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Novo trecho
            </button>
        @endif
    </div>
</div>

{{-- Conflitos da importação: a planilha inteira foi recusada, e aqui está o porquê --}}
@if(session('conflitos'))
    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 dark:border-rose-900/40 dark:bg-rose-950/20">
        <p class="text-sm font-semibold text-rose-800 dark:text-rose-300">
            Corrija a planilha: a mesma rota aparece com valores diferentes.
        </p>
        <p class="mt-1 text-xs text-rose-700 dark:text-rose-400">
            Cada trecho pode ter um único km e um único prazo. Deixe uma linha por rota e importe de novo.
        </p>
        <ul class="mt-3 max-h-64 space-y-1 overflow-y-auto text-xs text-rose-700 dark:text-rose-400">
            @foreach(session('conflitos') as $conflito)
                <li class="font-mono">{{ $conflito }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($aPreencher > 0 && ($filtros['preenchimento'] ?? '') !== 'incompletos')
    <a href="{{ route('trechos-sap.index', array_merge(request()->except('page'), ['preenchimento' => 'incompletos'])) }}"
       class="mt-4 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 transition-colors hover:bg-amber-100
              dark:border-amber-900/40 dark:bg-amber-950/20 dark:hover:bg-amber-950/40">
        <span class="text-lg font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ $aPreencher }}</span>
        <span class="text-sm text-amber-800 dark:text-amber-300">
            de {{ $total }} {{ $total === 1 ? 'rota espera' : 'rotas esperam' }} distância e prazo — clique para ver
        </span>
    </a>
@endif

<form method="GET" action="{{ route('trechos-sap.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
    <div class="min-w-64 flex-1">
        <label class="{{ $rotulo }}">Buscar</label>
        <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}" autocomplete="off"
               placeholder="Origem, destino ou trecho…" class="{{ $campo }}">
    </div>

    <div>
        <label class="{{ $rotulo }}">Preenchimento</label>
        <select name="preenchimento" class="{{ $campo }} w-44">
            <option value="">Todos</option>
            <option value="incompletos" @selected(($filtros['preenchimento'] ?? '') === 'incompletos')>A preencher</option>
            <option value="completos" @selected(($filtros['preenchimento'] ?? '') === 'completos')>Preenchidos</option>
        </select>
    </div>

    <div>
        <label class="{{ $rotulo }}">Prazo padrão</label>
        <select name="prazo_padrao" class="{{ $campo }} w-44">
            <option value="">Todos</option>
            @foreach($prazosPadrao as $p)
                <option value="{{ $p->value }}" @selected(($filtros['prazo_padrao'] ?? '') === $p->value)>{{ $p->label() }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit"
            class="h-10 rounded-lg bg-zinc-900 px-5 text-sm font-semibold text-white transition-colors hover:bg-zinc-700
                   dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        Filtrar
    </button>

    @if(array_filter($filtros))
        <a href="{{ route('trechos-sap.index') }}" class="h-10 self-end px-2 text-sm text-zinc-500 hover:text-zinc-800 dark:text-zinc-400">
            Limpar
        </a>
    @endif
</form>

<div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    @if($trechos->isEmpty())
        <div class="px-6 py-20 text-center">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Nenhum trecho cadastrado</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Importe a planilha de prazos ou cadastre um trecho.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-zinc-800/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Origem</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Destino</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Distância</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Normal</th>
                        <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Expresso</th>
                        <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Padrão</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                    @foreach($trechos as $t)
                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                @if($t->incompleto())
                                    <span class="mr-1 inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/50 dark:text-amber-400"
                                          title="Rota descoberta na importação de itens — falta informar distância e prazo">
                                        a preencher
                                    </span>
                                @endif
                                {{ $t->origem_sap }}
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $t->destino_sap }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ $t->km_trecho !== null ? number_format($t->km_trecho, 1, ',', '.').' km' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ $t->prazo_horas_normal !== null ? $t->prazo_horas_normal.' h' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ $t->prazo_horas_expresso !== null ? $t->prazo_horas_expresso.' h' : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $coresPrazo[$t->prazo_padrao?->value] ?? '' }}">
                                    {{ $t->prazo_padrao?->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($podeEditar)
                                    <button type="button" data-trecho="{{ json_encode([
                                        'id' => $t->id,
                                        'origem_sap' => $t->origem_sap,
                                        'destino_sap' => $t->destino_sap,
                                        'km_trecho' => $t->km_trecho,
                                        'prazo_horas_normal' => $t->prazo_horas_normal,
                                        'prazo_horas_expresso' => $t->prazo_horas_expresso,
                                        'prazo_padrao' => $t->prazo_padrao?->value,
                                    ]) }}" onclick="abrirEdicao(JSON.parse(this.dataset.trecho))"
                                            class="text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                        Editar
                                    </button>
                                @endif
                                @if($podeExcluir)
                                    <form method="POST" action="{{ route('trechos-sap.destroy', $t) }}" class="ml-2 inline"
                                          onsubmit="return confirm('Remover o trecho {{ $t->origem_sap }} → {{ $t->destino_sap }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 dark:text-rose-400">
                                            Excluir
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 px-4 py-3 dark:border-zinc-800">
            {{ $trechos->links() }}
        </div>
    @endif
</div>

{{-- Modal: novo / editar --}}
@if($podeCriar || $podeEditar)
<div id="modal-trecho" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <form method="POST" id="form-trecho" action="{{ route('trechos-sap.store') }}"
          class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
        @csrf
        <input type="hidden" name="_method" id="trecho-method" value="POST">

        <h3 id="trecho-titulo" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Novo trecho</h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Origem e destino identificam o trecho. Grafias diferentes do mesmo lugar contam como um só.
        </p>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div>
                <label class="{{ $rotulo }}">Origem SAP</label>
                <input type="text" name="origem_sap" id="trecho-origem" required maxlength="255" class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $rotulo }}">Destino SAP</label>
                <input type="text" name="destino_sap" id="trecho-destino" required maxlength="255" class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $rotulo }}">Distância (km)</label>
                <input type="number" name="km_trecho" id="trecho-km" step="0.1" min="0" class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $rotulo }}">Prazo padrão</label>
                <select name="prazo_padrao" id="trecho-padrao" required class="{{ $campo }}">
                    @foreach($prazosPadrao as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $rotulo }}">Prazo normal (horas)</label>
                <input type="number" name="prazo_horas_normal" id="trecho-normal" min="1" max="8760" class="{{ $campo }}">
            </div>
            <div>
                <label class="{{ $rotulo }}">Prazo expresso (horas)</label>
                <input type="number" name="prazo_horas_expresso" id="trecho-expresso" min="1" max="8760" class="{{ $campo }}">
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="fecharModal('modal-trecho')"
                    class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                Cancelar
            </button>
            <button type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                Salvar
            </button>
        </div>
    </form>
</div>
@endif

{{-- Modal: importar --}}
@if($podeCriar)
<div id="modal-importar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <form method="POST" action="{{ route('trechos-sap.importar') }}" enctype="multipart/form-data"
          id="form-importar-trechos" data-importacao class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
        @csrf
        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Importar trechos</h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Reimportar atualiza os trechos já cadastrados.
        </p>
        <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
            Cada rota deve aparecer uma vez só. Se a mesma origem→destino vier com km ou prazo diferentes,
            nada é importado e os conflitos são listados.
        </p>

        <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Planilha (.xlsx)</label>
        <input type="file" name="arquivo" accept=".xlsx,.xls" required
               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-zinc-900
                      file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium
                      dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:file:bg-zinc-700">

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="fecharModal('modal-importar')"
                    class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                Cancelar
            </button>
            <button type="submit" data-importacao-botao
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                Importar
            </button>
        </div>
    </form>
</div>
@endif

@push('scripts')
<script>
    window.abrirModal = function (id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
    };

    window.fecharModal = function (id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
    };

    window.abrirNovo = function () {
        document.getElementById('trecho-titulo').textContent = 'Novo trecho';
        document.getElementById('form-trecho').action = '{{ route('trechos-sap.store') }}';
        document.getElementById('trecho-method').value = 'POST';
        ['origem', 'destino', 'km', 'normal', 'expresso'].forEach(function (campo) {
            document.getElementById('trecho-' + campo).value = '';
        });
        document.getElementById('trecho-padrao').value = 'normal';
        window.abrirModal('modal-trecho');
    };

    window.abrirEdicao = function (t) {
        document.getElementById('trecho-titulo').textContent = 'Editar trecho';
        document.getElementById('form-trecho').action = '/trechos-sap/' + t.id;
        document.getElementById('trecho-method').value = 'PUT';
        document.getElementById('trecho-origem').value = t.origem_sap || '';
        document.getElementById('trecho-destino').value = t.destino_sap || '';
        document.getElementById('trecho-km').value = t.km_trecho !== null ? t.km_trecho : '';
        document.getElementById('trecho-normal').value = t.prazo_horas_normal !== null ? t.prazo_horas_normal : '';
        document.getElementById('trecho-expresso').value = t.prazo_horas_expresso !== null ? t.prazo_horas_expresso : '';
        document.getElementById('trecho-padrao').value = t.prazo_padrao || 'normal';
        window.abrirModal('modal-trecho');
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { ['modal-trecho', 'modal-importar'].forEach(window.fecharModal); }
    });

    ['modal-trecho', 'modal-importar'].forEach(function (id) {
        var m = document.getElementById(id);
        if (m) {
            m.addEventListener('click', function (e) { if (e.target === m) { window.fecharModal(id); } });
        }
    });

    // Erro de validação reabre o modal com o que foi digitado.
    @if($errors->any() && old('origem_sap'))
        window.abrirNovo();
        document.getElementById('trecho-origem').value = @json(old('origem_sap'));
        document.getElementById('trecho-destino').value = @json(old('destino_sap'));
        document.getElementById('trecho-km').value = @json(old('km_trecho'));
        document.getElementById('trecho-normal').value = @json(old('prazo_horas_normal'));
        document.getElementById('trecho-expresso').value = @json(old('prazo_horas_expresso'));
        document.getElementById('trecho-padrao').value = @json(old('prazo_padrao'));
    @endif
</script>
@endpush

</x-layouts.app>
