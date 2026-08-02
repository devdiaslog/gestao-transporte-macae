<x-layouts.app title="Demandas">

@php
    $isAdmin = auth()->user()->can('demandas.excluir');
@endphp

<div class="py-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Demandas</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Registro e acompanhamento de demandas de transporte
            </p>
        </div>
        <a href="{{ route('demandas.modelo') }}"
           title="Baixar planilha modelo para importação"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                  text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
            </svg>
            Baixar modelo
        </a>
        <form method="POST" action="{{ route('demandas.importar') }}" enctype="multipart/form-data"
              id="form-importar" data-importacao class="contents">
            @csrf
            <input type="file" name="arquivo" id="input-importar" accept=".xlsx,.xls" class="hidden"
                   onchange="if (this.files.length) { iniciarImportacao('form-importar'); }">
            <button type="button" onclick="document.getElementById('input-importar').click()"
                    title="Importar itens de demanda a partir da planilha do SAP"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                           text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5"/>
                </svg>
                Importar planilha
            </button>
        </form>
        <button type="button" onclick="openDemandaModal()"
                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white
                       shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nova Demanda
        </button>
    </div>

    {{-- Filtros — busca inline; demais critérios no modal --}}
    @php
        $inputCls = 'h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-zinc-900 placeholder-zinc-400 shadow-xs outline-none focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:placeholder-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-800';
        $dateCls = $inputCls.' dark:[color-scheme:dark]';
        $labelCls = 'mb-1 block text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400';

        // Conta os critérios ativos além da busca.
        $filtrosAtivos = collect([
            $status,
            $tipo, $fonte, $prefixo, $origem, $destino,
            $dataDE, $dataAte, $prazoDE, $prazoAte, $ajuste,
        ])->filter(fn ($v) => $v !== null && $v !== '')->count();
    @endphp

    <form id="form-filtros-demandas" method="GET" action="{{ route('demandas.index') }}"
          class="mb-5 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ $search }}" placeholder="Número ou documento…"
               class="h-9 w-56 rounded-lg border border-slate-200 bg-white px-3 text-sm
                      text-zinc-900 placeholder-zinc-400 shadow-xs outline-none
                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                      dark:placeholder-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">

        <button type="button" onclick="abrirFiltros()"
                class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium
                       text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
            </svg>
            Filtros
            @if($filtrosAtivos > 0)
                <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-zinc-900 px-1.5 text-[10px] font-bold text-white dark:bg-white dark:text-zinc-900">{{ $filtrosAtivos }}</span>
            @endif
        </button>

        <button type="submit"
                class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium
                       text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            Buscar
        </button>

        {{-- Relatórios — abre o CSV do cenário escolhido com os filtros atuais --}}
        <select id="select-relatorio" onchange="gerarRelatorio(this)"
                title="Gera um relatório em CSV, no nível do item, respeitando os filtros aplicados"
                class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm
                       text-zinc-700 shadow-xs outline-none
                       focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300
                       dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            <option value="">📊 Relatórios…</option>
            @foreach(App\Http\Controllers\DemandaController::RELATORIOS as $chave => $rel)
                <option value="{{ $chave }}">{{ $rel['label'] }}</option>
            @endforeach
        </select>

        <a id="btn-export-demandas" href="{{ route('demandas.export') }}"
           title="Exportar as demandas filtradas para CSV/Excel"
           class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium
                  text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Exportar
        </a>

        @if($search || $filtrosAtivos > 0)
            <a href="{{ route('demandas.index', ['reset' => '1']) }}"
               class="h-9 inline-flex items-center gap-1 rounded-lg px-3 text-sm text-zinc-400 hover:text-zinc-700
                      dark:hover:text-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
                Limpar
            </a>
        @endif

        {{-- ── Modal de filtros ─────────────────────────────────────────────── --}}
        <div id="modal-filtros" class="fixed inset-0 z-50 hidden">
            <div id="modal-filtros-overlay" onclick="fecharFiltros()"
                 class="absolute inset-0 bg-zinc-900/40 opacity-0 backdrop-blur-[2px] transition-opacity duration-200 dark:bg-black/60"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div id="modal-filtros-panel"
                     class="relative w-full max-w-2xl scale-95 overflow-hidden rounded-2xl border border-slate-200 bg-white opacity-0 shadow-2xl
                            transition-all duration-200 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-zinc-800">
                        <div>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Filtros</p>
                            <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Aplicados também ao export e aos relatórios</p>
                        </div>
                        <button type="button" onclick="fecharFiltros()"
                                class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-slate-100 hover:text-zinc-700
                                       dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="{{ $labelCls }}">Status</label>
                                <select name="status" class="{{ $inputCls }}">
                                    <option value="">Todos os status</option>
                                    <option value="active" @selected($status === 'active')>Pendente + Em Andamento</option>
                                    @foreach(\App\Enums\StatusDemanda::cases() as $s)
                                        <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Tipo</label>
                                <select name="tipo" class="{{ $inputCls }}">
                                    <option value="">Todos os tipos</option>
                                    @foreach(\App\Enums\TipoDemanda::cases() as $t)
                                        <option value="{{ $t->value }}" @selected($tipo === $t->value)>{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Fonte</label>
                                <select name="fonte" class="{{ $inputCls }}">
                                    <option value="">Todas as fontes</option>
                                    @foreach(\App\Enums\FonteDemanda::cases() as $f)
                                        <option value="{{ $f->value }}" @selected($fonte === $f->value)>{{ $f->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="{{ $labelCls }}">Origem</label>
                                <input type="text" name="origem" value="{{ $origem }}" list="lista-locais" placeholder="Ex.: PACU"
                                       class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Destino</label>
                                <input type="text" name="destino" value="{{ $destino }}" list="lista-locais" placeholder="Ex.: ARM-MACAE"
                                       class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Veículo (prefixo)</label>
                                <input type="text" name="prefixo" value="{{ $prefixo }}" placeholder="Ex.: 1993" class="{{ $inputCls }}">
                            </div>
                        </div>
                        <datalist id="lista-locais">
                            @foreach($locais as $loc)
                                <option value="{{ $loc }}"></option>
                            @endforeach
                        </datalist>

                        <div>
                            <label class="{{ $labelCls }}">Ajuste de início/fim</label>
                            <select name="ajuste" class="{{ $inputCls }} sm:w-64">
                                <option value="">Todas as demandas</option>
                                <option value="pendente" @selected($ajuste === 'pendente')>⚙ Precisam de ajuste</option>
                            </select>
                        </div>

                        <div>
                            <label class="{{ $labelCls }}">Vencimento (prazo)</label>
                            <div class="flex items-center gap-2">
                                <input type="datetime-local" name="prazo_de" value="{{ $prazoDE }}" class="{{ $dateCls }}">
                                <span class="shrink-0 text-xs text-zinc-400 dark:text-zinc-600">até</span>
                                <input type="datetime-local" name="prazo_ate" value="{{ $prazoAte }}" class="{{ $dateCls }}">
                            </div>
                        </div>

                        <div>
                            <label class="{{ $labelCls }}">Cadastro no sistema</label>
                            <div class="flex items-center gap-2">
                                <input type="date" name="data_de" value="{{ $dataDE }}" class="{{ $dateCls }}">
                                <span class="shrink-0 text-xs text-zinc-400 dark:text-zinc-600">até</span>
                                <input type="date" name="data_ate" value="{{ $dataAte }}" class="{{ $dateCls }}">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3 dark:border-zinc-800">
                        <a href="{{ route('demandas.index', ['reset' => '1']) }}"
                           class="text-sm text-zinc-400 transition-colors hover:text-zinc-700 dark:hover:text-zinc-200">
                            Limpar filtros
                        </a>
                        <div class="flex gap-2">
                            <button type="button" onclick="fecharFiltros()"
                                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-zinc-600
                                           transition-colors hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white transition-all
                                           hover:bg-zinc-700 active:scale-[0.98] dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                                Aplicar filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Tabela --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if($demandas->isEmpty())
            <div class="flex flex-col items-center justify-center gap-2 py-20 text-center text-zinc-400 dark:text-zinc-600">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                </svg>
                <p class="text-sm">Nenhuma demanda encontrada.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950/50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Demanda</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tipo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Fonte</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Veículo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rota</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Prazo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" title="Tempo do início até o fim; se ainda em aberto, conta até agora">Tempo atend.</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Auditoria</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Criado por</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Cadastro</th>
                            <th class="w-10 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                        @foreach($demandas as $demanda)
                            @php
                                $sc = $demanda->status_demanda->color();
                                $tipoColors = ['load' => 'blue', 'backload' => 'amber', 'transferencia' => 'violet'];
                                $tc = $demanda->tipo_demanda ? ($tipoColors[$demanda->tipo_demanda->value] ?? 'zinc') : null;
                                $prazoVencido = $demanda->prazo_demanda
                                    && $demanda->prazo_demanda->isPast()
                                    && ! in_array($demanda->status_demanda->value, ['finalizado', 'cancelada']);
                                $origens = $demanda->locaisOrigem();
                                $destinos = $demanda->locaisDestino();
                                $totalItens = $demanda->itens->count();
                                $encerrados = $demanda->itensEncerrados();
                                $tudoFeito = $totalItens > 0 && $encerrados === $totalItens;

                                // Tempo de atendimento: início → fim; sem fim, conta até agora.
                                $atendMin = null;
                                $atendAberto = false;
                                if ($demanda->data_hora_inicio_demanda) {
                                    $fimRef = $demanda->data_hora_fim_demanda ?? now();
                                    $atendAberto = $demanda->data_hora_fim_demanda === null;
                                    $atendMin = (int) abs($demanda->data_hora_inicio_demanda->diffInMinutes($fimRef));
                                }
                                $fmtDuracao = function (int $min): string {
                                    $d = intdiv($min, 1440); $h = intdiv($min % 1440, 60); $m = $min % 60;
                                    return $d > 0 ? "{$d}d {$h}h" : ($h > 0 ? "{$h}h {$m}m" : "{$m}m");
                                };
                            @endphp
                            <tr class="group transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                {{-- Número --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        #{{ $demanda->numero_demanda }}
                                    </span>
                                    @if($demanda->tipo_cadastro->value === 'integracao')
                                        <span class="ml-1.5 rounded px-1.5 py-0.5 text-[10px] font-medium
                                                     bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-400">
                                            API
                                        </span>
                                    @endif
                                </td>
                                {{-- Tipo --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->tipo_demanda)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                     bg-{{ $tc }}-100 text-{{ $tc }}-700
                                                     dark:bg-{{ $tc }}-950/40 dark:text-{{ $tc }}-400">
                                            {{ $demanda->tipo_demanda->label() }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Fonte --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->fonte_demanda)
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium
                                                     bg-{{ $demanda->fonte_demanda->color() }}-100 text-{{ $demanda->fonte_demanda->color() }}-700
                                                     dark:bg-{{ $demanda->fonte_demanda->color() }}-950/40 dark:text-{{ $demanda->fonte_demanda->color() }}-400">
                                            {{ $demanda->fonte_demanda->label() }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Veículo --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->equipamento)
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $demanda->equipamento->prefixo }}</span>
                                        <span class="text-zinc-400 dark:text-zinc-600"> {{ $demanda->equipamento->placa }}</span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Rota (origens → destinos dos itens) --}}
                                <td class="max-w-[280px] px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                    @if($origens || $destinos)
                                        <div class="truncate" title="{{ $demanda->rota() }}">
                                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ implode(', ', $origens) ?: '—' }}</span>
                                            <span class="text-zinc-400 dark:text-zinc-600"> → </span>
                                            <span>{{ implode(', ', $destinos) ?: '—' }}</span>
                                        </div>
                                        <div class="mt-1 flex items-center gap-1.5">
                                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold tabular-nums
                                                         {{ $tudoFeito
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                                            : 'bg-slate-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}"
                                                  title="{{ $encerrados }} de {{ $totalItens }} itens concluídos (entregues ou cancelados)">
                                                {{ $encerrados }}/{{ $totalItens }}
                                            </span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-600">itens</span>
                                        </div>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Prazo --}}
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    @if($demanda->prazo_demanda)
                                        <span class="{{ $prazoVencido ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                            {{ $demanda->prazo_demanda->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Tempo de atendimento --}}
                                <td class="whitespace-nowrap px-4 py-3 text-xs">
                                    @if($atendMin !== null)
                                        <span class="font-semibold tabular-nums {{ $atendAberto && $atendMin >= 2880 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-700 dark:text-zinc-300' }}"
                                              title="{{ $atendAberto ? 'Em atendimento (contando até agora)' : 'Início → fim' }}">
                                            {{ $fmtDuracao($atendMin) }}
                                        </span>
                                        @if($atendAberto)
                                            <span class="ml-0.5 inline-block h-1.5 w-1.5 rounded-full bg-blue-500 align-middle" title="Ainda em atendimento"></span>
                                        @endif
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                 bg-{{ $sc }}-100 text-{{ $sc }}-700
                                                 dark:bg-{{ $sc }}-950/40 dark:text-{{ $sc }}-400">
                                        {{ $demanda->status_demanda->label() }}
                                    </span>
                                    @if($demanda->inicio_automatico || $demanda->fim_automatico)
                                        <span class="ml-1 inline-flex items-center gap-0.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-700
                                                     dark:bg-amber-950/40 dark:text-amber-400"
                                              title="Início/fim definidos automaticamente pelo SAP (hora pode estar genérica) — ajuste em Editar; ao ajustar, o horário passa a ser do operador.">
                                            ⚙ Ajustar
                                        </span>
                                    @endif
                                    @if($demanda->data_hora_inicio_demanda)
                                        <div class="mt-0.5 text-[10px] text-zinc-400 dark:text-zinc-600">
                                            Início: {{ $demanda->data_hora_inicio_demanda->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                    @if($demanda->data_hora_fim_demanda)
                                        <div class="mt-0.5 text-[10px] text-zinc-400 dark:text-zinc-600">
                                            Fim: {{ $demanda->data_hora_fim_demanda->format('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                </td>
                                {{-- Auditoria --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->status_auditoria)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700
                                                     dark:bg-emerald-950/40 dark:text-emerald-400">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                            </svg>
                                            Auditado
                                        </span>
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                    @endif
                                </td>
                                {{-- Criado por --}}
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $demanda->criador?->name ?? '—' }}
                                </td>
                                {{-- Cadastro --}}
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-400 dark:text-zinc-600">
                                    {{ $demanda->created_at->format('d/m/Y H:i') }}
                                </td>
                                {{-- Ações --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        {{-- Editar (página completa, com etapas e itens) --}}
                                        <a href="{{ route('demandas.edit', $demanda) }}"
                                           title="Abrir demanda"
                                           class="inline-flex h-7 w-7 items-center justify-center rounded-lg border
                                                  border-zinc-200 text-zinc-500 transition-colors hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-700
                                                  dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                                            </svg>
                                        </a>



                                        {{-- Auditar (admin) --}}
                                        @can('delete-demanda')
                                        <form method="POST" action="{{ route('demandas.auditar', $demanda) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" title="{{ $demanda->status_auditoria ? 'Remover auditoria' : 'Marcar como auditado' }}"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border transition-colors
                                                           {{ $demanda->status_auditoria
                                                               ? 'border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-400'
                                                               : 'border-zinc-200 text-zinc-400 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70' }}">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @endcan

                                        {{-- Excluir (admin) --}}
                                        @can('delete-demanda')
                                        <form method="POST" action="{{ route('demandas.destroy', $demanda) }}"
                                              data-confirm data-user-name="a demanda #{{ $demanda->numero_demanda }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Excluir"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg border
                                                           border-red-200 text-red-500 transition-colors hover:border-red-300 hover:bg-red-50
                                                           dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 px-4 py-3 dark:border-zinc-800">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $totalItensFiltro }} {{ $totalItensFiltro === 1 ? 'item de demanda' : 'itens de demanda' }} no filtro atual
                </span>
                @if($demandas->hasPages())
                    <div>{{ $demandas->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- ─── Modal Criar / Editar Demanda ─────────────────────────────────────── --}}
<div id="demanda-modal"
     class="fixed inset-0 z-[90] hidden"
     role="dialog" aria-modal="true" aria-labelledby="demanda-modal-title">

    <div id="demanda-modal-overlay"
         class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-200"></div>

    <div class="relative flex min-h-full items-center justify-center p-4">
        <div id="demanda-modal-panel"
             class="flex w-full max-w-2xl scale-95 flex-col overflow-hidden rounded-2xl border opacity-0 shadow-2xl
                    transition-all duration-200
                    border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950"
             style="max-height: 90vh;">

            {{-- Header --}}
            <div class="flex shrink-0 items-center justify-between border-b px-6 py-4
                        border-slate-100 dark:border-zinc-800">
                <h2 id="demanda-modal-title" class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    Nova Demanda
                </h2>
                <button type="button" onclick="closeDemandaModal()"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Form --}}
            <form id="demanda-form" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                @csrf
                <input type="hidden" name="_method" id="demanda-method" value="POST">
                <input type="hidden" name="tipo_cadastro" value="manual">
                <input type="hidden" name="tipo_demanda"   id="cb-tipo_demanda-value">
                <input type="hidden" name="equipamento_id" id="cb-equipamento_id-value">

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5 space-y-4">

                    {{-- Linha 1: Número --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Número da Demanda <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" name="numero_demanda" id="f-numero-demanda"
                               placeholder="Ex: 123456789"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                      text-zinc-900 outline-none shadow-xs
                                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                      dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                      disabled:opacity-50">
                    </div>

                    {{-- Linha 2: Tipo + Veículo --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Tipo de Demanda
                            </label>
                            <div class="relative" data-combobox="tipo_demanda">
                                <input type="text" id="cb-tipo_demanda-display" autocomplete="off"
                                       placeholder="Pesquisar tipo…"
                                       oninput="cbFilter('tipo_demanda')" onfocus="cbFilter('tipo_demanda')"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                <div id="cb-tipo_demanda-dropdown"
                                     class="absolute z-30 mt-1 hidden max-h-52 w-full overflow-y-auto rounded-lg border
                                            border-slate-200 bg-white py-1 shadow-lg
                                            dark:border-zinc-700 dark:bg-zinc-800"></div>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Veículo (Prefixo)
                            </label>
                            <div class="relative" data-combobox="equipamento_id">
                                <input type="text" id="cb-equipamento_id-display" autocomplete="off"
                                       placeholder="Pesquisar prefixo ou placa…"
                                       oninput="cbFilter('equipamento_id')" onfocus="cbFilter('equipamento_id')"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                <div id="cb-equipamento_id-dropdown"
                                     class="absolute z-30 mt-1 hidden max-h-52 w-full overflow-y-auto rounded-lg border
                                            border-slate-200 bg-white py-1 shadow-lg
                                            dark:border-zinc-700 dark:bg-zinc-800"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Itens da demanda (somente leitura — vêm da importação do SAP) --}}
                    <div id="f-itens-group" class="hidden" style="display:none">
                        <div class="mb-1.5 flex items-baseline justify-between">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Itens da Demanda
                            </label>
                            <span id="f-itens-resumo" class="text-[11px] text-zinc-400 dark:text-zinc-600"></span>
                        </div>
                        <div class="max-h-56 overflow-y-auto rounded-lg border border-slate-200 dark:border-zinc-700">
                            <table class="w-full text-xs">
                                <thead class="sticky top-0 bg-slate-50 dark:bg-zinc-800">
                                    <tr class="text-left text-[10px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        <th class="px-2 py-1.5 font-semibold">RT / Item</th>
                                        <th class="px-2 py-1.5 font-semibold">Rota</th>
                                        <th class="px-2 py-1.5 font-semibold">Retirada</th>
                                        <th class="px-2 py-1.5 font-semibold">Prazo</th>
                                        <th class="px-2 py-1.5 font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="f-itens-body" class="divide-y divide-slate-100 dark:divide-zinc-800"></tbody>
                            </table>
                        </div>
                        <p class="mt-1 text-[10px] text-zinc-400 dark:text-zinc-600">
                            Prazo, tipo e status da demanda são calculados a partir destes itens.
                        </p>
                    </div>

                    {{-- Linha: Início + Fim (só exibido no modo edição) --}}
                    <div id="f-datas-group" class="hidden" style="display:none">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Data / Hora de Início
                            </label>
                            <input type="datetime-local" name="data_hora_inicio_demanda" id="f-inicio"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                          text-zinc-900 outline-none shadow-xs
                                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                          dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                        </div>
                    </div>

                    {{-- Observação --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            Observação
                        </label>
                        <textarea name="observacao" id="f-obs" rows="4"
                                  placeholder="Detalhes adicionais sobre a demanda…"
                                  class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                         text-zinc-900 outline-none shadow-xs
                                         focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                         dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                         dark:focus:border-zinc-500 dark:focus:ring-zinc-800"></textarea>
                    </div>
                </div>

                {{-- Mensagem de erro --}}
                <div id="demanda-error"
                     class="mx-6 mb-0 hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700
                            dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-400"></div>

                {{-- Footer --}}
                <div class="flex shrink-0 items-center justify-between border-t px-6 py-4
                            border-slate-100 dark:border-zinc-800">
                    <button type="button" onclick="closeDemandaModal()"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors
                                   hover:bg-zinc-100 hover:text-zinc-900
                                   dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                        Cancelar
                    </button>
                    <div class="flex items-center gap-3">
                        <button type="submit" id="btn-salvar"
                                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white
                                       shadow-xs transition-all duration-150 hover:bg-zinc-700 active:scale-[0.98]
                                       dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200
                                       disabled:cursor-not-allowed disabled:opacity-60">
                            Salvar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@push('scripts')
<script>
(function () {
    var CB_DATA = {
        tipo_demanda: [
            @foreach(\App\Enums\TipoDemanda::cases() as $t)
            { v: '{{ $t->value }}', l: '{{ $t->label() }}' },
            @endforeach
        ],
        equipamento_id: [
            @foreach($equipamentos as $eq)
            { v: {{ $eq->id }}, l: '{{ $eq->prefixo }} — {{ $eq->placa }}' },
            @endforeach
        ],
    };

    var DD_CLS = 'cursor-pointer px-3 py-2 text-sm text-zinc-800 hover:bg-slate-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60';

    window.cbFilter = function (key) {
        var inp  = document.getElementById('cb-' + key + '-display');
        var dd   = document.getElementById('cb-' + key + '-dropdown');
        var q    = (inp.value || '').toLowerCase().trim();
        var opts = CB_DATA[key] || [];
        var matches = q ? opts.filter(function (o) { return o.l.toLowerCase().includes(q); }) : opts;
        if (! matches.length) { dd.classList.add('hidden'); return; }
        dd.innerHTML = matches.slice(0, 40).map(function (o) {
            var safe  = String(o.l).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            var safeV = String(o.v).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            return '<div class="' + DD_CLS + '" onmousedown="event.preventDefault();cbSelect(\'' + key + '\',\'' + safeV + '\',\'' + safe + '\')">' + o.l + '</div>';
        }).join('');
        dd.classList.remove('hidden');
    };

    window.cbSelect = function (key, value, label) {
        document.getElementById('cb-' + key + '-value').value   = value;
        document.getElementById('cb-' + key + '-display').value = label;
        document.getElementById('cb-' + key + '-dropdown').classList.add('hidden');
    };

    function cbSetValue(key, value) {
        var opts = CB_DATA[key] || [];
        var opt  = opts.find(function (o) { return String(o.v) === String(value); });
        document.getElementById('cb-' + key + '-value').value   = value != null ? value : '';
        document.getElementById('cb-' + key + '-display').value = opt ? opt.l : '';
    }

    function cbReset(key) {
        document.getElementById('cb-' + key + '-value').value   = '';
        document.getElementById('cb-' + key + '-display').value = '';
        var dd = document.getElementById('cb-' + key + '-dropdown');
        if (dd) { dd.classList.add('hidden'); }
    }

    document.addEventListener('click', function (e) {
        document.querySelectorAll('[data-combobox]').forEach(function (el) {
            var dd = el.querySelector('[id$="-dropdown"]');
            if (dd && ! el.contains(e.target)) { dd.classList.add('hidden'); }
        });
    });

    var modal        = document.getElementById('demanda-modal');
    var overlay      = document.getElementById('demanda-modal-overlay');
    var panel        = document.getElementById('demanda-modal-panel');
    var titleEl      = document.getElementById('demanda-modal-title');
    var form         = document.getElementById('demanda-form');
    var errBox       = document.getElementById('demanda-error');
    var btnSalvar    = document.getElementById('btn-salvar');
    var datasGroup   = document.getElementById('f-datas-group');
    var editingId    = null;

    var STORE_URL  = '{{ route('demandas.store') }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';

    window.openDemandaModal = function () {
        editingId = null;
        titleEl.textContent = 'Nova Demanda';
        form.reset();
        document.getElementById('demanda-method').value = 'POST';
        document.getElementById('f-numero-demanda').disabled = false;
        errBox.classList.add('hidden');
        datasGroup.style.display = 'none';
        datasGroup.classList.add('hidden');
        renderItens([]);
        cbReset('tipo_demanda');
        cbReset('equipamento_id');
        openModal();
    };

    function showModalError(msg) {
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
        errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.closeDemandaModal = function () {
        overlay.classList.remove('opacity-100');
        panel.classList.add('opacity-0', 'scale-95');
        panel.classList.remove('opacity-100', 'scale-100');
        setTimeout(function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, 200);
    };

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            overlay.classList.add('opacity-100');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errBox.classList.add('hidden');
        btnSalvar.disabled = true;

        var url  = STORE_URL;
        var data = new FormData(form);

        if (! data.get('data_hora_inicio_demanda')) { data.delete('data_hora_inicio_demanda'); }

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: data,
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, data: j }; }); })
        .then(function (res) {
            if (res.ok && res.data.ok) {
                window.location.reload();
            } else {
                var msgs = res.data.errors
                    ? Object.values(res.data.errors).flat().join(' ')
                    : (res.data.message || 'Erro ao salvar a demanda.');
                errBox.textContent = msgs;
                errBox.classList.remove('hidden');
                btnSalvar.disabled = false;
            }
        })
        .catch(function () {
            errBox.textContent = 'Falha na conexão. Tente novamente.';
            errBox.classList.remove('hidden');
            btnSalvar.disabled = false;
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target === overlay) { window.closeDemandaModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.closeDemandaModal(); }
    });


    function fmtDatetime(iso) {
        if (! iso) { return ''; }
        var d = new Date(iso);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function fmtDataBr(iso) {
        if (! iso) { return '—'; }
        var d = new Date(iso);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
            + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    var STATUS_ITEM = {
        '04': { label: 'Aberto',    cls: 'text-zinc-600 dark:text-zinc-400' },
        '07': { label: 'Entregue',  cls: 'text-emerald-600 dark:text-emerald-400' },
        '18': { label: 'Cancelado', cls: 'text-rose-600 dark:text-rose-400' }
    };

    function renderItens(itens) {
        var grupo  = document.getElementById('f-itens-group');
        var corpo  = document.getElementById('f-itens-body');
        var resumo = document.getElementById('f-itens-resumo');
        if (! grupo || ! corpo) { return; }

        if (! itens.length) {
            grupo.classList.add('hidden');
            grupo.style.display = 'none';
            return;
        }

        corpo.innerHTML = itens.map(function (i) {
            var st = STATUS_ITEM[i.status_item] || { label: i.status_item || '—', cls: 'text-zinc-400' };
            var esc = function (v) {
                return String(v == null ? '' : v).replace(/[&<>"]/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
                });
            };
            return '<tr class="text-zinc-700 dark:text-zinc-300">'
                + '<td class="px-2 py-1.5 font-mono text-[11px] whitespace-nowrap">' + esc(i.numero_rt) + ' / ' + esc(i.numero_item) + (i.subitem ? '.' + esc(i.subitem) : '') + '</td>'
                + '<td class="px-2 py-1.5">' + esc(i.local_origem || '—') + ' → ' + esc(i.local_destino || '—') + '</td>'
                + '<td class="px-2 py-1.5 text-zinc-500 dark:text-zinc-500">' + esc(i.descricao_local_retirada || '—') + '</td>'
                + '<td class="px-2 py-1.5 whitespace-nowrap">' + fmtDataBr(i.prazo_item) + '</td>'
                + '<td class="px-2 py-1.5 font-medium ' + st.cls + '">' + esc(st.label) + '</td>'
                + '</tr>';
        }).join('');

        resumo.textContent = itens.length + (itens.length === 1 ? ' item' : ' itens');
        grupo.classList.remove('hidden');
        grupo.style.display = 'block';
    }

    // Modal de filtros
    (function () {
        var modal   = document.getElementById('modal-filtros');
        var overlay = document.getElementById('modal-filtros-overlay');
        var panel   = document.getElementById('modal-filtros-panel');
        if (! modal) { return; }

        window.abrirFiltros = function () {
            modal.classList.remove('hidden');
            requestAnimationFrame(function () {
                overlay.classList.add('opacity-100');
                panel.classList.remove('scale-95', 'opacity-0');
                panel.classList.add('scale-100', 'opacity-100');
            });
        };

        window.fecharFiltros = function () {
            overlay.classList.remove('opacity-100');
            panel.classList.add('scale-95', 'opacity-0');
            panel.classList.remove('scale-100', 'opacity-100');
            setTimeout(function () { modal.classList.add('hidden'); }, 180);
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.fecharFiltros(); }
        });
    })();

    // Exportar e relatórios — mantêm os filtros atuais na URL
    (function () {
        var exportBtn  = document.getElementById('btn-export-demandas');
        var relSelect  = document.getElementById('select-relatorio');
        var filterForm = document.getElementById('form-filtros-demandas');
        var baseUrl    = '{{ route('demandas.export') }}';
        var relUrl     = '{{ route('demandas.relatorio') }}';
        if (! filterForm) { return; }

        function filtrosAtuais() {
            var params = new URLSearchParams(new FormData(filterForm));
            var clean  = new URLSearchParams();
            params.forEach(function (v, k) { if (v.trim() !== '') { clean.set(k, v); } });
            return clean;
        }

        function syncExportUrl() {
            if (! exportBtn) { return; }
            var qs = filtrosAtuais().toString();
            exportBtn.href = baseUrl + (qs ? '?' + qs : '');
        }

        window.gerarRelatorio = function (select) {
            if (! select.value) { return; }
            var params = filtrosAtuais();
            params.set('relatorio', select.value);
            window.location.href = relUrl + '?' + params.toString();
            select.value = '';
        };

        filterForm.querySelectorAll('input, select').forEach(function (el) {
            if (el.id === 'select-relatorio') { return; }
            el.addEventListener('change', syncExportUrl);
            el.addEventListener('input',  syncExportUrl);
        });
        syncExportUrl();
    })();
})();
</script>
@endpush

</x-layouts.app>
