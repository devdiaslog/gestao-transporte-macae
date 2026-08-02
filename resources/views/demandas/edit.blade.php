<x-layouts.app title="Editar Demanda #{{ $demanda->numero_demanda }}">

@php
    $isAdmin = auth()->user()->can('demandas.excluir');

    $etapas = $demanda->etapas();
    $totalItens = $demanda->itens->count();
    $encerrados = $demanda->itensEncerrados();

    // Regra: sem início não altera item; com tudo concluído exige o fim antes de novas alterações.
    $motivoBloqueio = $demanda->motivoBloqueioItens();
    $edicaoBloqueada = $motivoBloqueio !== null;

    // Dados do combobox de veículo (busca por prefixo/placa).
    $veiculosCombobox = $equipamentos->map(fn ($e) => [
        'id' => $e->id,
        'label' => $e->prefixo.' — '.$e->placa,
        'busca' => mb_strtolower($e->prefixo.' '.$e->placa),
    ])->values();

    $sc = $demanda->status_demanda->color();
    $tipoColors = ['load' => 'blue', 'backload' => 'amber', 'transferencia' => 'violet'];
    $tc = $demanda->tipo_demanda ? ($tipoColors[$demanda->tipo_demanda->value] ?? 'zinc') : null;

    $prazoVencido = $demanda->prazo_demanda
        && $demanda->prazo_demanda->isPast()
        && ! in_array($demanda->status_demanda->value, ['finalizado', 'cancelada']);

    $statusItemCores = [
        'pendente' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        'entregue' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
        'cancelado' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400',
        'suspenso' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
        'recusado' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400',
    ];
@endphp

<div class="py-8">

    {{-- Header --}}
    <div class="mb-6">
        <a href="{{ route('demandas.index') }}"
           class="mb-3 inline-flex items-center gap-1.5 text-sm text-zinc-500 transition-colors hover:text-zinc-800
                  dark:text-zinc-400 dark:hover:text-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
            Voltar para Demandas
        </a>

        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Demanda #{{ $demanda->numero_demanda }}
            </h2>

            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                         bg-{{ $sc }}-100 text-{{ $sc }}-700 dark:bg-{{ $sc }}-950/40 dark:text-{{ $sc }}-400">
                {{ $demanda->status_demanda->label() }}
            </span>

            @if($demanda->tipo_demanda)
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                             bg-{{ $tc }}-100 text-{{ $tc }}-700 dark:bg-{{ $tc }}-950/40 dark:text-{{ $tc }}-400">
                    {{ $demanda->tipo_demanda->label() }}
                </span>
            @endif

            @if($demanda->fonte_demanda)
                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium
                             bg-{{ $demanda->fonte_demanda->color() }}-100 text-{{ $demanda->fonte_demanda->color() }}-700
                             dark:bg-{{ $demanda->fonte_demanda->color() }}-950/40 dark:text-{{ $demanda->fonte_demanda->color() }}-400">
                    {{ $demanda->fonte_demanda->label() }}
                </span>
            @endif

            @if($demanda->data_hora_fim_elog)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700
                             dark:bg-emerald-950/40 dark:text-emerald-400"
                      title="O atendimento deixou de aparecer no E-log em {{ $demanda->data_hora_fim_elog->format('d/m/Y H:i') }} — concluído no TMS.">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                    Concluído no TMS
                </span>
            @endif

            @php $pesoTotalDemanda = $demanda->itens->sum(fn ($i) => (float) $i->peso_total); @endphp
            <span class="ml-auto flex flex-wrap items-center gap-2">
                @if($pesoTotalDemanda > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5
                                 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                          title="Soma do peso total dos itens, conforme o SAP">
                        <span class="text-zinc-400 dark:text-zinc-500">Peso</span>
                        <span class="font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($pesoTotalDemanda, 0, ',', '.') }} kg</span>
                    </span>
                @endif
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5
                             text-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <span class="text-zinc-400 dark:text-zinc-500">Itens concluídos</span>
                    <span class="font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $encerrados }}/{{ $totalItens }}</span>
                </span>
            </span>
        </div>

        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ $demanda->rota() ?: 'Sem rota definida' }}
            @if($demanda->prazo_demanda)
                · Prazo
                <span class="{{ $prazoVencido ? 'font-semibold text-rose-600 dark:text-rose-400' : '' }}">
                    {{ $demanda->prazo_demanda->format('d/m/Y H:i') }}
                </span>
            @endif
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

        {{-- ── Coluna 1: dados da demanda ─────────────────────────────────── --}}
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('demandas.update', $demanda) }}"
                  class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Dados da Demanda</p>
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Prazo e status são calculados pelos itens</p>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <label for="e-tipo" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Tipo
                        </label>
                        <select name="tipo_demanda" id="e-tipo"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                       focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                            <option value="" @selected(! $demanda->tipo_demanda_manual)>Automático (pelos itens)</option>
                            @foreach(\App\Enums\TipoDemanda::cases() as $t)
                                <option value="{{ $t->value }}" @selected($demanda->tipo_demanda_manual && $demanda->tipo_demanda === $t)>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[10px] text-zinc-400 dark:text-zinc-600">
                            Automático classifica pela rota dos itens (BMAC · PACU · PBG). Escolha um tipo para fixar manualmente.
                        </p>
                    </div>

                    {{-- Veículo — combobox com busca por prefixo/placa --}}
                    <div>
                        <label for="e-veiculo-busca" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Veículo
                        </label>
                        <div class="relative" id="e-veiculo-cb">
                            <input type="hidden" name="equipamento_id" id="e-veiculo-id" value="{{ $demanda->equipamento_id }}">
                            <input type="text" id="e-veiculo-busca" autocomplete="off" placeholder="Buscar prefixo ou placa…"
                                   value="{{ $demanda->equipamento ? $demanda->equipamento->prefixo.' — '.$demanda->equipamento->placa : '' }}"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 pr-8 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                            <button type="button" id="e-veiculo-limpar" title="Limpar veículo"
                                    class="absolute inset-y-0 right-2 flex items-center text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <div id="e-veiculo-lista"
                                 class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg
                                        dark:border-zinc-700 dark:bg-zinc-900"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="e-inicio" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Início
                                @if($demanda->inicio_automatico)
                                    <span class="normal-case text-[10px] font-normal text-amber-600 dark:text-amber-400">⚙ automático — confira a hora</span>
                                @endif
                            </label>
                            <input type="datetime-local" name="data_hora_inicio_demanda" id="e-inicio"
                                   value="{{ old('data_hora_inicio_demanda', $demanda->data_hora_inicio_demanda?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                          dark:[color-scheme:dark]">
                        </div>
                        <div>
                            <label for="e-fim" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Fim
                                @if($demanda->fim_automatico)
                                    <span class="normal-case text-[10px] font-normal text-amber-600 dark:text-amber-400">⚙ automático — confira a hora</span>
                                @endif
                            </label>
                            <input type="datetime-local" name="data_hora_fim_demanda" id="e-fim"
                                   value="{{ old('data_hora_fim_demanda', $demanda->data_hora_fim_demanda?->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                          dark:[color-scheme:dark]">
                        </div>
                    </div>
                    <p class="-mt-2 text-[10px] text-zinc-400 dark:text-zinc-600">
                        Início e fim automáticos vêm das entregas do SAP (hora pode ser genérica). Ao informar um horário, ele passa a ser do operador;
                        deixar o fim vazio devolve o cálculo automático (maior entrega quando todos os itens estão resolvidos).
                    </p>

                    <div>
                        <label for="e-obs" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Observação
                        </label>
                        <textarea name="observacao" id="e-obs" rows="3"
                                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                         focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                         dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">{{ old('observacao', $demanda->observacao) }}</textarea>
                    </div>

                    <dl class="space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-zinc-800">
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Cadastro</dt>
                            <dd class="text-zinc-600 dark:text-zinc-400">{{ $demanda->tipo_cadastro->label() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Criado por</dt>
                            <dd class="text-zinc-600 dark:text-zinc-400">{{ $demanda->criador?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Cadastrado em</dt>
                            <dd class="text-zinc-600 dark:text-zinc-400">{{ $demanda->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Criada no SAP</dt>
                            <dd class="text-zinc-600 dark:text-zinc-400">{{ $demanda->data_hora_criacao_sap?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Início no E-log</dt>
                            <dd class="text-zinc-600 dark:text-zinc-400">{{ $demanda->data_hora_inicio_elog?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-400 dark:text-zinc-500">Fim no E-log (TMS)</dt>
                            <dd class="{{ $demanda->data_hora_fim_elog ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                {{ $demanda->data_hora_fim_elog?->format('d/m/Y H:i') ?? '— em andamento' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="flex justify-end border-t border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white
                                   shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                                   dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        Salvar
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Coluna 2: etapas e itens ───────────────────────────────────── --}}
        <div class="lg:col-span-3">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Etapas da Demanda</p>
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            {{ $etapas->count() }} {{ $etapas->count() === 1 ? 'etapa' : 'etapas' }} · {{ $totalItens }} {{ $totalItens === 1 ? 'item' : 'itens' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        {{-- Importar itens (escopado a esta demanda) --}}
                        <form method="POST" action="{{ route('demandas.itens.importar', $demanda) }}"
                              enctype="multipart/form-data" id="form-importar-itens" data-importacao class="contents">
                            @csrf
                            <input type="file" name="arquivo" id="input-importar-itens" accept=".xlsx,.xls" class="hidden"
                                   onchange="if (this.files.length) { iniciarImportacao('form-importar-itens'); }">
                            <button type="button" onclick="document.getElementById('input-importar-itens').click()"
                                    title="Importar itens da planilha apenas para esta demanda"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium
                                           text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5"/>
                                </svg>
                                Importar itens
                            </button>
                        </form>

                        {{-- Adicionar item manualmente --}}
                        <button type="button" onclick="novoItem()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white
                                       shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Adicionar item
                        </button>
                    </div>
                </div>

                @if($edicaoBloqueada && $totalItens > 0)
                    <div class="flex items-start gap-2.5 border-b border-amber-200 bg-amber-50 px-5 py-3 text-xs text-amber-800
                                dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        <span>{{ $motivoBloqueio }}</span>
                    </div>
                @endif

                @if($totalItens === 0)
                    <p class="py-16 text-center text-sm text-zinc-400 dark:text-zinc-600">
                        Esta demanda ainda não possui itens.
                    </p>
                @else
                    {{-- Rolagem interna da lista de etapas --}}
                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-4">
                        @foreach($etapas as $rota => $itens)
                            @php
                                $etapaEncerrados = $itens->filter(fn ($i) => $i->status_item?->encerrado() === true)->count();
                                $etapaTotal = $itens->count();
                                $completa = $etapaEncerrados === $etapaTotal;
                            @endphp
                            <section class="overflow-hidden rounded-lg border border-slate-200 dark:border-zinc-700">
                                {{-- Cabeçalho da etapa --}}
                                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-2.5
                                               {{ $completa
                                                  ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                                                  : 'border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-800/40' }}">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-200" title="{{ $rota }}">
                                            {{ $rota }}
                                        </p>
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold tabular-nums
                                                     {{ $completa
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
                                                        : 'bg-white text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400' }}">
                                            {{ $etapaEncerrados }}/{{ $etapaTotal }}
                                        </span>
                                    </div>

                                    {{-- Ações em lote para todos os itens da etapa (mesmo destino) --}}
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                        {{-- Status --}}
                                        <form method="POST" action="{{ route('demandas.status-etapa', $demanda) }}"
                                              class="flex shrink-0 items-center gap-1.5">
                                            @csrf
                                            @method('PUT')
                                            @foreach($itens as $item)
                                                <input type="hidden" name="itens[]" value="{{ $item->id }}">
                                            @endforeach
                                            <span class="text-[10px] uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Status:</span>
                                            @php
                                                // Sem início, a etapa só aceita os status que não pressupõem execução.
                                                $statusDisponiveis = collect(\App\Enums\StatusItemDemanda::cases())
                                                    ->filter(fn ($s) => ! $edicaoBloqueada || $s->dispensaInicioDaDemanda());
                                            @endphp
                                            <select name="status_item" required @disabled($statusDisponiveis->isEmpty())
                                                    class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-xs text-zinc-900 shadow-xs outline-none
                                                           focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-50
                                                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                                <option value="" disabled selected>Definir…</option>
                                                @foreach($statusDisponiveis as $s)
                                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" @disabled($statusDisponiveis->isEmpty())
                                                    class="h-8 shrink-0 rounded-lg bg-zinc-900 px-3 text-xs font-semibold text-white
                                                           transition-colors hover:bg-zinc-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-zinc-900
                                                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 dark:disabled:hover:bg-white">
                                                Aplicar
                                            </button>
                                        </form>

                                        {{-- Data/hora de entrega --}}
                                        <form method="POST" action="{{ route('demandas.entrega-etapa', $demanda) }}"
                                              class="flex shrink-0 items-center gap-1.5">
                                            @csrf
                                            @method('PUT')
                                            @foreach($itens as $item)
                                                <input type="hidden" name="itens[]" value="{{ $item->id }}">
                                            @endforeach
                                            <span class="text-[10px] uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Entrega:</span>
                                            <input type="datetime-local" name="data_hora_entrega" required @disabled($edicaoBloqueada)
                                                   class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-xs text-zinc-900 shadow-xs outline-none
                                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-50
                                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                                            <button type="submit" @disabled($edicaoBloqueada)
                                                    class="h-8 shrink-0 rounded-lg bg-zinc-900 px-3 text-xs font-semibold text-white
                                                           transition-colors hover:bg-zinc-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-zinc-900
                                                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 dark:disabled:hover:bg-white">
                                                Aplicar
                                            </button>
                                        </form>

                                        {{-- Prazo --}}
                                        <form method="POST" action="{{ route('demandas.prazo-etapa', $demanda) }}"
                                              class="flex shrink-0 items-center gap-1.5">
                                            @csrf
                                            @method('PUT')
                                            @foreach($itens as $item)
                                                <input type="hidden" name="itens[]" value="{{ $item->id }}">
                                            @endforeach
                                            <span class="text-[10px] uppercase tracking-wider text-zinc-400 dark:text-zinc-500">Prazo:</span>
                                            <input type="datetime-local" name="prazo_item" required @disabled($edicaoBloqueada)
                                                   class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-xs text-zinc-900 shadow-xs outline-none
                                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 disabled:cursor-not-allowed disabled:opacity-50
                                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                                            <button type="submit" @disabled($edicaoBloqueada)
                                                    class="h-8 shrink-0 rounded-lg bg-zinc-900 px-3 text-xs font-semibold text-white
                                                           transition-colors hover:bg-zinc-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-zinc-900
                                                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 dark:disabled:hover:bg-white">
                                                Aplicar
                                            </button>
                                        </form>
                                    </div>
                                </header>

                                {{-- Itens da etapa --}}
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-white dark:bg-zinc-900">
                                            <tr class="text-left text-[10px] uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                                                <th class="px-3 py-1.5 font-semibold">Nº Entrega / Item</th>
                                                <th class="px-3 py-1.5 font-semibold">Descrição da Carga</th>
                                                <th class="px-3 py-1.5 font-semibold">Retirada</th>
                                                <th class="px-3 py-1.5 font-semibold">Prazo</th>
                                                <th class="px-3 py-1.5 font-semibold">Entregue em</th>
                                                <th class="px-3 py-1.5 font-semibold">Status</th>
                                                <th class="w-10 px-3 py-1.5"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                            @foreach($itens as $item)
                                                @php
                                                    $cor = $statusItemCores[$item->status_item?->value]
                                                        ?? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
                                                    $itemVencido = $item->prazo_item
                                                        && $item->prazo_item->isPast()
                                                        && ! ($item->status_item?->encerrado() ?? false);
                                                    $payload = [
                                                        'id' => $item->id,
                                                        'numero_rt' => $item->numero_rt,
                                                        'numero_item' => $item->numero_item,
                                                        'subitem' => $item->subitem,
                                                        'local_origem' => $item->local_origem,
                                                        'local_destino' => $item->local_destino,
                                                        'descricao_local_retirada' => $item->descricao_local_retirada,
                                                        'descricao_item' => $item->descricao_item,
                                                        'status_item' => $item->status_item?->value,
                                                        'prazo_item' => $item->prazo_item?->format('Y-m-d\TH:i'),
                                                        'data_hora_entrega' => $item->data_hora_entrega?->format('Y-m-d\TH:i'),
                                                        'observacao' => $item->observacao,
                                                        'status_sap' => $item->status_sap?->value,
                                                        'peso_total' => $item->peso_total ? number_format((float) $item->peso_total, 0, ',', '.').' kg' : null,
                                                        'dimensoes' => ($item->comprimento || $item->largura || $item->altura)
                                                            ? sprintf('%s × %s × %s m',
                                                                $item->comprimento ? number_format((float) $item->comprimento, 2, ',', '.') : '?',
                                                                $item->largura ? number_format((float) $item->largura, 2, ',', '.') : '?',
                                                                $item->altura ? number_format((float) $item->altura, 2, ',', '.') : '?')
                                                            : null,
                                                    ];
                                                @endphp
                                                <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                                    <td class="whitespace-nowrap px-3 py-2 font-mono text-[11px] text-zinc-700 dark:text-zinc-300">
                                                        {{ $item->numero_rt }} / {{ $item->numero_item }}@if($item->subitem).{{ $item->subitem }}@endif
                                                    </td>
                                                    <td class="max-w-[220px] px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                                        <div class="truncate" title="{{ $item->descricao_item }}">{{ $item->descricao_item ?: '—' }}</div>
                                                        @if($item->peso_total || $item->comprimento || $item->largura || $item->altura)
                                                            <div class="mt-0.5 truncate text-[10px] text-zinc-400 dark:text-zinc-600"
                                                                 title="Peso total e dimensões (C × L × A) vindos do SAP">
                                                                @if($item->peso_total)
                                                                    {{ number_format((float) $item->peso_total, 0, ',', '.') }} kg
                                                                @endif
                                                                @if($item->comprimento || $item->largura || $item->altura)
                                                                    · {{ $item->comprimento ? number_format((float) $item->comprimento, 1, ',', '.') : '?' }}×{{ $item->largura ? number_format((float) $item->largura, 1, ',', '.') : '?' }}×{{ $item->altura ? number_format((float) $item->altura, 1, ',', '.') : '?' }} m
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="max-w-[140px] truncate px-3 py-2 text-zinc-500 dark:text-zinc-500"
                                                        title="{{ $item->descricao_local_retirada }}">
                                                        {{ $item->descricao_local_retirada ?: '—' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 {{ $itemVencido ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                                        {{ $item->prazo_item?->format('d/m/Y H:i') ?? '—' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2 {{ $item->data_hora_entrega ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-600' }}">
                                                        {{ $item->data_hora_entrega?->format('d/m/Y H:i') ?? '—' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2">
                                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $cor }}">
                                                            {{ $item->status_item?->label() ?? '—' }}
                                                        </span>
                                                        @if($item->status_sap)
                                                            <div class="mt-0.5 text-[10px] text-zinc-400 dark:text-zinc-600"
                                                                 title="{{ $item->status_sap->descricao() }} — status no SAP, sempre atualizado pela importação; confira antes de finalizar o item.">
                                                                SAP {{ $item->status_sap->value }}: {{ $item->status_sap->label() }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-right">
                                                        <button type="button" @disabled($edicaoBloqueada)
                                                                @unless($edicaoBloqueada) data-item="{{ json_encode($payload) }}" onclick="editarItem(JSON.parse(this.dataset.item))" @endunless
                                                                title="{{ $edicaoBloqueada ? $motivoBloqueio : 'Editar item' }}"
                                                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 text-zinc-500
                                                                       transition-colors hover:bg-white hover:text-zinc-800 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-zinc-500
                                                                       dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ─── Modal de edição do item ──────────────────────────────────────────── --}}
<div id="item-modal" class="fixed inset-0 z-[90] hidden" role="dialog" aria-modal="true" aria-labelledby="item-modal-title">
    <div id="item-overlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div id="item-panel"
             class="w-full max-w-2xl scale-95 overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl
                    transition-all duration-200 dark:border-zinc-800 dark:bg-zinc-900">

            <form id="item-form" method="POST">
                @csrf
                <input type="hidden" name="_method" id="i-method" value="PUT">

                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-zinc-800">
                    <h2 id="item-modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Editar Item</h2>
                    <button type="button" onclick="fecharItem()"
                            class="rounded-lg p-1 text-zinc-400 transition-colors hover:bg-slate-100 hover:text-zinc-700
                                   dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Nº Entrega</label>
                            <input type="text" name="numero_rt" id="i-rt" required
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Item</label>
                            <input type="text" name="numero_item" id="i-item" required
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Subitem</label>
                            <input type="text" name="subitem" id="i-subitem"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Local de Origem</label>
                            <input type="text" name="local_origem" id="i-origem"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Local de Destino</label>
                            <input type="text" name="local_destino" id="i-destino"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                        </div>
                    </div>
                    <p class="-mt-2 text-[10px] text-zinc-400 dark:text-zinc-600">
                        Alterar origem ou destino move o item para outra etapa e pode mudar o tipo da demanda.
                    </p>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Descrição da Carga</label>
                        <input type="text" name="descricao_item" id="i-descricao"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Local de Retirada</label>
                        <input type="text" name="descricao_local_retirada" id="i-retirada"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
                            <select name="status_item" id="i-status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                           focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                @foreach(\App\Enums\StatusItemDemanda::cases() as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Prazo</label>
                            <input type="datetime-local" name="prazo_item" id="i-prazo"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                          dark:[color-scheme:dark]">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Data / Hora de Entrega</label>
                        <input type="datetime-local" name="data_hora_entrega" id="i-entrega"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                      dark:[color-scheme:dark]">
                    </div>
                    <p class="-mt-2 text-[10px] text-zinc-400 dark:text-zinc-600">
                        Status e prazo recalculam o status e o prazo da demanda.
                    </p>

                    {{-- Dados do SAP — somente leitura, sempre sincronizados na importação --}}
                    <div id="i-sap-bloco" class="hidden rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-900/60">
                        <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                            Dados do SAP <span class="font-normal normal-case">(sincronizados na importação)</span>
                        </p>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-600">Status SAP</p>
                                <p id="i-sap-status" class="font-mono font-semibold text-zinc-700 dark:text-zinc-300">—</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-600">Peso total</p>
                                <p id="i-sap-peso" class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">—</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-600">C × L × A</p>
                                <p id="i-sap-dimensoes" class="font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">—</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Observações</label>
                        <div id="i-obs-historico"
                             class="mb-2 hidden max-h-28 overflow-y-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-zinc-600
                                    dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400"></div>
                        <textarea name="observacao" id="i-obs" rows="2"
                                  placeholder="Adicionar observação (o histórico anterior é mantido)"
                                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs outline-none
                                         focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                         dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 dark:border-zinc-800">
                    @can('delete-demanda')
                        <button type="button" onclick="removerItem()" id="i-btn-remover"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-rose-600
                                       transition-colors hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                            </svg>
                            Remover
                        </button>
                    @else
                        <span></span>
                    @endcan

                    <div class="flex gap-2">
                        <button type="button" onclick="fecharItem()"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors hover:bg-slate-100
                                       dark:text-zinc-400 dark:hover:bg-zinc-800">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white
                                       shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Salvar Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Formulário auxiliar para remoção do item --}}
<form id="item-delete-form" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
(function () {
    var modal   = document.getElementById('item-modal');
    var overlay = document.getElementById('item-overlay');
    var panel   = document.getElementById('item-panel');
    var form    = document.getElementById('item-form');
    var delForm = document.getElementById('item-delete-form');
    var metodo  = document.getElementById('i-method');
    var titulo  = document.getElementById('item-modal-title');
    var btnRemover = document.getElementById('i-btn-remover');
    var itemId  = null;

    var UPDATE_URL = '{{ url('demanda-itens') }}';
    var STORE_URL  = '{{ route('demandas.itens.store', $demanda) }}';

    function preencher(data) {
        document.getElementById('i-rt').value        = data.numero_rt || '';
        document.getElementById('i-item').value      = data.numero_item || '';
        document.getElementById('i-subitem').value   = data.subitem || '';
        document.getElementById('i-origem').value    = data.local_origem || '';
        document.getElementById('i-destino').value   = data.local_destino || '';
        document.getElementById('i-descricao').value = data.descricao_item || '';
        document.getElementById('i-retirada').value  = data.descricao_local_retirada || '';
        document.getElementById('i-status').value    = data.status_item || '';
        document.getElementById('i-prazo').value      = data.prazo_item || '';
        document.getElementById('i-entrega').value    = data.data_hora_entrega || '';

        var historico = document.getElementById('i-obs-historico');
        historico.textContent = data.observacao || '';
        historico.classList.toggle('hidden', ! data.observacao);
        document.getElementById('i-obs').value = '';

        var temSap = !! (data.status_sap || data.peso_total || data.dimensoes);
        document.getElementById('i-sap-bloco').classList.toggle('hidden', ! temSap);
        document.getElementById('i-sap-status').textContent = data.status_sap || '—';
        document.getElementById('i-sap-peso').textContent = data.peso_total || '—';
        document.getElementById('i-sap-dimensoes').textContent = data.dimensoes || '—';
    }

    function abrir() {
        modal.classList.remove('hidden');
        requestAnimationFrame(function () {
            overlay.classList.add('opacity-100');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.add('scale-100', 'opacity-100');
        });
    }

    window.editarItem = function (data) {
        itemId = data.id;
        form.action = UPDATE_URL + '/' + data.id;
        metodo.value = 'PUT';
        titulo.textContent = 'Editar Item';
        if (btnRemover) { btnRemover.style.display = ''; }
        preencher(data);
        abrir();
    };

    window.novoItem = function () {
        itemId = null;
        form.action = STORE_URL;
        metodo.value = 'POST';
        titulo.textContent = 'Adicionar Item';
        if (btnRemover) { btnRemover.style.display = 'none'; }
        preencher({ numero_item: '1' });
        abrir();
    };

    window.fecharItem = function () {
        overlay.classList.remove('opacity-100');
        panel.classList.add('scale-95', 'opacity-0');
        panel.classList.remove('scale-100', 'opacity-100');
        setTimeout(function () { modal.classList.add('hidden'); }, 180);
    };

    window.removerItem = function () {
        if (! itemId) { return; }
        if (! confirm('Remover este item da demanda? O prazo e o status serão recalculados.')) { return; }
        delForm.action = UPDATE_URL + '/' + itemId;
        delForm.submit();
    };

    overlay.addEventListener('click', window.fecharItem);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.fecharItem(); }
    });
})();

// ── Combobox de veículo com busca por prefixo/placa ──────────────────────────
(function () {
    var EQUIPAMENTOS = @json($veiculosCombobox);

    var cb     = document.getElementById('e-veiculo-cb');
    var input  = document.getElementById('e-veiculo-busca');
    var hidden = document.getElementById('e-veiculo-id');
    var lista  = document.getElementById('e-veiculo-lista');
    var limpar = document.getElementById('e-veiculo-limpar');
    if (! cb) { return; }

    var labelAtual = input.value;

    function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]; }); }

    function render(q) {
        q = (q || '').toLowerCase().trim();
        var items = EQUIPAMENTOS.filter(function (e) { return ! q || e.busca.indexOf(q) !== -1; }).slice(0, 60);
        lista.innerHTML = items.length
            ? items.map(function (e) {
                return '<button type="button" data-id="' + e.id + '" data-label="' + esc(e.label) + '" '
                    + 'class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-slate-100 dark:text-zinc-300 dark:hover:bg-zinc-800">'
                    + esc(e.label) + '</button>';
            }).join('')
            : '<div class="px-3 py-2 text-xs text-zinc-400">Nenhum veículo encontrado.</div>';
        lista.classList.remove('hidden');
    }

    input.addEventListener('focus', function () { input.select(); render(''); });
    input.addEventListener('input', function () { render(input.value); });

    lista.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-id]');
        if (! btn) { return; }
        hidden.value = btn.dataset.id;
        labelAtual = btn.dataset.label;
        input.value = labelAtual;
        lista.classList.add('hidden');
    });

    limpar.addEventListener('click', function () {
        hidden.value = '';
        labelAtual = '';
        input.value = '';
        lista.classList.add('hidden');
        input.focus();
    });

    document.addEventListener('click', function (e) {
        if (! cb.contains(e.target)) {
            lista.classList.add('hidden');
            input.value = labelAtual; // descarta busca não confirmada
        }
    });
})();
</script>
@endpush

</x-layouts.app>
