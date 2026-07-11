<x-layouts.app title="Demandas">

@php
    $statusColors = [
        'pendente'      => 'zinc',
        'em_andamento'  => 'blue',
        'concluida'     => 'emerald',
        'cancelada'     => 'rose',
    ];
    $tipoColors = [
        'load'          => 'blue',
        'backload'      => 'amber',
        'transferencia' => 'violet',
    ];
    $isAdmin = auth()->user()->role->value === 'administrador';
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
        @can('manage-cadastros')
        <a href="{{ route('locais.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium
                  text-zinc-600 shadow-xs transition-colors hover:bg-zinc-50
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
            </svg>
            Gerenciar Locais
        </a>
        @endcan
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

    {{-- Filtros --}}
    <form method="GET" action="{{ route('demandas.index') }}"
          class="mb-5 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ $search }}" placeholder="Número da demanda…"
               class="h-9 w-44 rounded-lg border border-slate-200 bg-white px-3 text-sm
                      text-zinc-900 placeholder-zinc-400 shadow-xs outline-none
                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                      dark:placeholder-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
        <input type="text" name="prefixo" value="{{ $prefixo }}" placeholder="Prefixo do veículo…"
               class="h-9 w-40 rounded-lg border border-slate-200 bg-white px-3 text-sm
                      text-zinc-900 placeholder-zinc-400 shadow-xs outline-none
                      focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                      dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                      dark:placeholder-zinc-600 dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
        <select name="status"
                class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm
                       text-zinc-900 shadow-xs outline-none
                       focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                       dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            <option value="">Todos os status</option>
            @foreach(\App\Enums\StatusDemanda::cases() as $s)
                <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <select name="tipo"
                class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm
                       text-zinc-900 shadow-xs outline-none
                       focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                       dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
            <option value="">Todos os tipos</option>
            @foreach(\App\Enums\TipoDemanda::cases() as $t)
                <option value="{{ $t->value }}" @selected($tipo === $t->value)>{{ $t->label() }}</option>
            @endforeach
        </select>
        <div class="flex items-center gap-1.5">
            <span class="text-xs text-zinc-400 dark:text-zinc-600">Cadastro:</span>
            <input type="date" name="data_de" value="{{ $dataDE }}"
                   class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm
                          text-zinc-900 shadow-xs outline-none
                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                          dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
            <span class="text-xs text-zinc-400 dark:text-zinc-600">até</span>
            <input type="date" name="data_ate" value="{{ $dataAte }}"
                   class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm
                          text-zinc-900 shadow-xs outline-none
                          focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                          dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                          dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
        </div>
        <button type="submit"
                class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium
                       text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            Filtrar
        </button>
        <a id="btn-export-demandas" href="{{ route('demandas.export') }}"
           title="Exportar para CSV/Excel"
           class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm font-medium
                  text-zinc-700 shadow-xs transition-colors hover:bg-slate-50
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Exportar
        </a>
        @if($search || $status || $tipo || $prefixo || $dataDE || $dataAte)
            <a href="{{ route('demandas.index') }}"
               class="h-9 inline-flex items-center gap-1 rounded-lg px-3 text-sm text-zinc-400 hover:text-zinc-700
                      dark:hover:text-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
                Limpar
            </a>
        @endif
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
            @php
            $colunas = [
                ['label' => 'Número',               'tip' => 'Identificador único da demanda, gerado pelo sistema de gestão de carga (SGC). Composto por 9 a 10 dígitos e imutável após o cadastro.'],
                ['label' => 'Tipo',                  'tip' => 'Natureza da operação. Load = entrega de carga no porto/base; Backload = coleta de carga no porto/base para retorno; Transferência = movimentação de carga entre bases próprias.'],
                ['label' => 'Veículo',               'tip' => 'Prefixo e placa do veículo motorizado alocado para executar a demanda. O prefixo é o código interno de frota.'],
                ['label' => 'Origem',                'tip' => 'Local de partida da operação — ponto onde o veículo irá carregar ou iniciar o trajeto.'],
                ['label' => 'Destino',               'tip' => 'Local de chegada da operação — ponto de entrega ou descarregamento da carga.'],
                ['label' => 'Prazo',                 'tip' => 'Data e hora limite para conclusão da demanda. Exibido em vermelho quando vencido e a demanda ainda está em aberto.'],
                ['label' => 'Agendamento',           'tip' => 'Data e hora de acesso agendado ao local de origem ou destino. Necessário quando o local exige pré-agendamento para entrada (ex.: terminais portuários, bases offshore).'],
                ['label' => 'Ini. Carregamento',     'tip' => 'Momento em que se iniciou o carregamento da carga no veículo no local de origem.'],
                ['label' => 'Fim Carregamento',      'tip' => 'Momento em que o carregamento foi concluído e o veículo ficou liberado para partir.'],
                ['label' => 'Saída Origem',          'tip' => 'Data e hora em que o veículo efetivamente deixou o local de origem com a carga.'],
                ['label' => 'Chegada Destino',       'tip' => 'Data e hora em que o veículo chegou ao local de destino. A diferença entre saída e chegada representa o tempo de trânsito.'],
                ['label' => 'Ini. Descarregamento',  'tip' => 'Momento em que se iniciou o descarregamento da carga no local de destino.'],
                ['label' => 'Fim Descarregamento',   'tip' => 'Momento em que o descarregamento foi concluído e a operação de entrega foi encerrada.'],
                ['label' => 'Status',                'tip' => 'Situação atual da demanda: Pendente (aguardando início), Em Andamento (operação em curso), Concluída (entrega finalizada) ou Cancelada.'],
                ['label' => 'Criado por',            'tip' => 'Usuário do sistema que registrou esta demanda manualmente. Demandas integradas via API não possuem criador.'],
                ['label' => 'Cadastro',              'tip' => 'Data e hora em que a demanda foi registrada no sistema, independente do tipo de cadastro (manual ou integração).'],
            ];
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950/50">
                        <tr>
                            @foreach($colunas as $col)
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1">
                                    {{ $col['label'] }}
                                    <span class="col-tip cursor-help" data-tip="{{ $col['tip'] }}">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-zinc-300 hover:text-zinc-500 dark:text-zinc-600 dark:hover:text-zinc-400"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                                        </svg>
                                    </span>
                                </span>
                            </th>
                            @endforeach
                            <th class="w-10 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                        @foreach($demandas as $demanda)
                            @php
                                $sc = $statusColors[$demanda->status_demanda->value] ?? 'zinc';
                                $tc = $demanda->tipo_demanda ? ($tipoColors[$demanda->tipo_demanda->value] ?? 'zinc') : null;
                                $prazoVencido = $demanda->prazo_atendimento_demanda && $demanda->prazo_atendimento_demanda->isPast()
                                    && ! in_array($demanda->status_demanda->value, ['concluida', 'cancelada']);
                            @endphp
                            <tr class="group transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
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
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->equipamento)
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $demanda->equipamento->prefixo }}</span>
                                        <span class="text-zinc-400 dark:text-zinc-600"> {{ $demanda->equipamento->placa }}</span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $demanda->localOrigem?->nome ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $demanda->localDestino?->nome ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if($demanda->prazo_atendimento_demanda)
                                        <span class="{{ $prazoVencido ? 'font-semibold text-rose-600 dark:text-rose-400' : 'text-zinc-600 dark:text-zinc-400' }}">
                                            {{ $demanda->prazo_atendimento_demanda->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $demanda->data_hora_agendamento ? $demanda->data_hora_agendamento->format('d/m/Y H:i') : '—' }}
                                </td>
                                @foreach([
                                    'data_hora_inicio_carregamento',
                                    'data_hora_fim_carregamento',
                                    'data_hora_saida_origem',
                                    'data_hora_chegada_destino',
                                    'data_hora_inicio_descarregamento',
                                    'data_hora_fim_descarregamento',
                                ] as $campo)
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $demanda->$campo ? $demanda->$campo->format('d/m/Y H:i') : '—' }}
                                </td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                 bg-{{ $sc }}-100 text-{{ $sc }}-700
                                                 dark:bg-{{ $sc }}-950/40 dark:text-{{ $sc }}-400">
                                        {{ $demanda->status_demanda->label() }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $demanda->criador?->name ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-400 dark:text-zinc-600">
                                    {{ $demanda->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                onclick="editDemanda({{ $demanda->id }}, {{ $demanda->toJson() }})"
                                                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5
                                                       text-xs font-medium transition-all duration-150
                                                       border-zinc-200 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50
                                                       dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/70">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                            Atualizar
                                        </button>
                                        @if($isAdmin)
                                        <form method="POST" action="{{ route('demandas.destroy', $demanda) }}"
                                              data-confirm data-user-name="a demanda #{{ $demanda->numero_demanda }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5
                                                           text-xs font-medium transition-all duration-150
                                                           border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50
                                                           dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                                Remover
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($demandas->hasPages())
                <div class="border-t border-slate-100 px-4 py-3 dark:border-zinc-800">
                    {{ $demandas->links() }}
                </div>
            @endif
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
             class="flex w-full max-w-[62rem] scale-95 flex-col overflow-hidden rounded-2xl border opacity-0 shadow-2xl
                    transition-all duration-200
                    border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-950"
             style="max-height: 95vh;">

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

            {{-- Stepper --}}
            <div class="flex shrink-0 items-center gap-0 border-b border-slate-100 px-6 dark:border-zinc-800">
                @foreach(['Identificação', 'Datas', 'Observações'] as $idx => $label)
                <button type="button"
                        onclick="goToStep({{ $idx + 1 }})"
                        data-step-btn="{{ $idx + 1 }}"
                        class="flex items-center gap-2 border-b-2 py-3 pr-6 text-sm font-medium transition-colors
                               border-transparent text-zinc-400 hover:text-zinc-600
                               dark:text-zinc-600 dark:hover:text-zinc-400
                               [&.active]:border-zinc-900 [&.active]:text-zinc-900
                               dark:[&.active]:border-white dark:[&.active]:text-white">
                    <span data-step-num="{{ $idx + 1 }}"
                          class="flex h-5 w-5 items-center justify-center rounded-full text-xs font-bold
                                 bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500
                                 [.active_&]:bg-zinc-900 [.active_&]:text-white
                                 dark:[.active_&]:bg-white dark:[.active_&]:text-zinc-900">
                        {{ $idx + 1 }}
                    </span>
                    {{ $label }}
                </button>
                @endforeach
            </div>

            {{-- Form --}}
            <form id="demanda-form" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                @csrf
                <input type="hidden" name="_method" id="demanda-method" value="POST">
                <input type="hidden" name="tipo_cadastro" value="manual">

                {{-- Campos hidden reais dos comboboxes --}}
                <input type="hidden" name="tipo_demanda"    id="cb-tipo_demanda-value">
                <input type="hidden" name="equipamento_id"  id="cb-equipamento_id-value">
                <input type="hidden" name="local_origem_id" id="cb-local_origem_id-value">
                <input type="hidden" name="local_destino_id" id="cb-local_destino_id-value">

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">

                    {{-- Passo 1 — Identificação --}}
                    <div data-step="1" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Local de Origem
                                </label>
                                <div class="relative" data-combobox="local_origem_id">
                                    <input type="text" id="cb-local_origem_id-display" autocomplete="off"
                                           placeholder="Pesquisar local…"
                                           oninput="cbFilter('local_origem_id')" onfocus="cbFilter('local_origem_id')"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                                  text-zinc-900 outline-none shadow-xs
                                                  focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                                  dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    <div id="cb-local_origem_id-dropdown"
                                         class="absolute z-30 mt-1 hidden max-h-52 w-full overflow-y-auto rounded-lg border
                                                border-slate-200 bg-white py-1 shadow-lg
                                                dark:border-zinc-700 dark:bg-zinc-800"></div>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Local de Destino
                                </label>
                                <div class="relative" data-combobox="local_destino_id">
                                    <input type="text" id="cb-local_destino_id-display" autocomplete="off"
                                           placeholder="Pesquisar local…"
                                           oninput="cbFilter('local_destino_id')" onfocus="cbFilter('local_destino_id')"
                                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                                  text-zinc-900 outline-none shadow-xs
                                                  focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                                  dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    <div id="cb-local_destino_id-dropdown"
                                         class="absolute z-30 mt-1 hidden max-h-52 w-full overflow-y-auto rounded-lg border
                                                border-slate-200 bg-white py-1 shadow-lg
                                                dark:border-zinc-700 dark:bg-zinc-800"></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Prazo de Atendimento
                                </label>
                                <input type="datetime-local" name="prazo_atendimento_demanda" id="f-prazo"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Data de Agendamento
                                </label>
                                <input type="datetime-local" name="data_hora_agendamento" id="f-agendamento"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                        </div>
                    </div>

                    {{-- Passo 2 — Datas --}}
                    <div data-step="2" class="hidden space-y-4">
                        <p class="text-xs text-zinc-400 dark:text-zinc-600">
                            Todos os campos de data são opcionais — preencha conforme o andamento da operação.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Início do Carregamento</label>
                                <input type="datetime-local" name="data_hora_inicio_carregamento" id="f-ini-car"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Fim do Carregamento</label>
                                <input type="datetime-local" name="data_hora_fim_carregamento" id="f-fim-car"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Saída da Origem</label>
                                <input type="datetime-local" name="data_hora_saida_origem" id="f-saida"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Chegada ao Destino</label>
                                <input type="datetime-local" name="data_hora_chegada_destino" id="f-chegada"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Início do Descarregamento</label>
                                <input type="datetime-local" name="data_hora_inicio_descarregamento" id="f-ini-des"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Fim do Descarregamento</label>
                                <input type="datetime-local" name="data_hora_fim_descarregamento" id="f-fim-des"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none shadow-xs focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800 dark:[color-scheme:dark]">
                            </div>
                        </div>
                    </div>

                    {{-- Passo 3 — Observações --}}
                    <div data-step="3" class="hidden space-y-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Observação Adicional
                            </label>
                            <textarea name="observacao_adicional" id="f-obs" rows="6"
                                      placeholder="Detalhes adicionais sobre a demanda…"
                                      class="w-full resize-none rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                             text-zinc-900 outline-none shadow-xs
                                             focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                             dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                             dark:focus:border-zinc-500 dark:focus:ring-zinc-800"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Mensagem de erro --}}
                <div id="demanda-error"
                     class="mx-6 mb-0 hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700
                            dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-400"></div>

                {{-- Footer --}}
                <div class="flex shrink-0 items-center justify-between border-t px-6 py-4
                            border-slate-100 dark:border-zinc-800">
                    <div class="flex gap-2">
                        <button type="button" id="btn-prev" onclick="prevStep()"
                                class="hidden inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium
                                       text-zinc-600 shadow-xs transition-colors hover:bg-zinc-50
                                       dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                            </svg>
                            Anterior
                        </button>
                        <button type="button" id="btn-next" onclick="nextStep()"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium
                                       text-zinc-600 shadow-xs transition-colors hover:bg-zinc-50
                                       dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800">
                            Próximo
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="closeDemandaModal()"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition-colors
                                       hover:bg-zinc-100 hover:text-zinc-900
                                       dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            Cancelar
                        </button>
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

{{-- Tooltip flutuante (posicionado via JS para não ser cortado pelo overflow-x-auto) --}}
<div id="col-tip-box"
     class="pointer-events-none fixed z-[200] hidden max-w-xs rounded-lg bg-zinc-900 px-3 py-2 text-xs
            leading-relaxed text-white shadow-xl dark:bg-zinc-700"
     style="transition: opacity 0.1s;">
</div>

@push('scripts')
<script>
(function () {
    // ── Dados dos comboboxes (gerados pelo PHP) ───────────────────────────────
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
        local_origem_id: [
            @foreach($locais as $local)
            { v: {{ $local->id }}, l: '{{ addslashes($local->nome) }}' },
            @endforeach
        ],
        local_destino_id: [
            @foreach($locais as $local)
            { v: {{ $local->id }}, l: '{{ addslashes($local->nome) }}' },
            @endforeach
        ],
        status_demanda: [
            @foreach(\App\Enums\StatusDemanda::cases() as $s)
            { v: '{{ $s->value }}', l: '{{ $s->label() }}' },
            @endforeach
        ],
    };

    var DD_CLS = 'cursor-pointer px-3 py-2 text-sm text-zinc-800 hover:bg-slate-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60';

    // ── Combobox engine ───────────────────────────────────────────────────────
    window.cbFilter = function (key) {
        var inp = document.getElementById('cb-' + key + '-display');
        var dd  = document.getElementById('cb-' + key + '-dropdown');
        var q   = (inp.value || '').toLowerCase().trim();
        var opts = CB_DATA[key] || [];

        var matches = q
            ? opts.filter(function (o) { return o.l.toLowerCase().includes(q); })
            : opts;

        if (! matches.length) { dd.classList.add('hidden'); return; }

        dd.innerHTML = matches.slice(0, 40).map(function (o) {
            var safe = String(o.l).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            var safeV = String(o.v).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
            return '<div class="' + DD_CLS + '" onmousedown="event.preventDefault();cbSelect(\'' + key + '\',\'' + safeV + '\',\'' + safe + '\')">'
                + o.l
                + '</div>';
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
        document.getElementById('cb-' + key + '-dropdown').classList.add('hidden');
    }

    var ALL_CB_KEYS = ['tipo_demanda', 'equipamento_id', 'local_origem_id', 'local_destino_id'];

    // Fecha dropdowns ao clicar fora
    document.addEventListener('click', function (e) {
        document.querySelectorAll('[data-combobox]').forEach(function (el) {
            var dd = el.querySelector('[id$="-dropdown"]');
            if (dd && ! el.contains(e.target)) { dd.classList.add('hidden'); }
        });
    });

    // ── Modal open/close ──────────────────────────────────────────────────────
    var modal     = document.getElementById('demanda-modal');
    var overlay   = document.getElementById('demanda-modal-overlay');
    var panel     = document.getElementById('demanda-modal-panel');
    var titleEl   = document.getElementById('demanda-modal-title');
    var form      = document.getElementById('demanda-form');
    var errBox    = document.getElementById('demanda-error');
    var btnSalvar = document.getElementById('btn-salvar');
    var editingId = null;

    var STORE_URL  = '{{ route('demandas.store') }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';

    window.openDemandaModal = function () {
        editingId = null;
        titleEl.textContent = 'Nova Demanda';
        form.reset();
        document.getElementById('demanda-method').value = 'POST';
        document.getElementById('f-numero-demanda').disabled = false;
        errBox.classList.add('hidden');
        ALL_CB_KEYS.forEach(cbReset);
        goToStep(1);
        openModal();
    };

    window.editDemanda = function (id, data) {
        editingId = id;
        titleEl.textContent = 'Editar Demanda #' + data.numero_demanda;
        form.reset();
        document.getElementById('demanda-method').value = 'PUT';
        document.getElementById('f-numero-demanda').disabled = true;
        errBox.classList.add('hidden');

        document.getElementById('f-numero-demanda').value = data.numero_demanda || '';
        document.getElementById('f-prazo').value          = fmtDatetime(data.prazo_atendimento_demanda);
        document.getElementById('f-agendamento').value    = fmtDatetime(data.data_hora_agendamento);
        document.getElementById('f-ini-car').value        = fmtDatetime(data.data_hora_inicio_carregamento);
        document.getElementById('f-fim-car').value        = fmtDatetime(data.data_hora_fim_carregamento);
        document.getElementById('f-saida').value          = fmtDatetime(data.data_hora_saida_origem);
        document.getElementById('f-chegada').value        = fmtDatetime(data.data_hora_chegada_destino);
        document.getElementById('f-ini-des').value        = fmtDatetime(data.data_hora_inicio_descarregamento);
        document.getElementById('f-fim-des').value        = fmtDatetime(data.data_hora_fim_descarregamento);
        document.getElementById('f-obs').value            = data.observacao_adicional || '';

        cbSetValue('tipo_demanda',    data.tipo_demanda);
        cbSetValue('equipamento_id',  data.equipamento_id);
        cbSetValue('local_origem_id', data.local_origem_id);
        cbSetValue('local_destino_id',data.local_destino_id);

        goToStep(1);
        openModal();
    };

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

    // ── Steps ─────────────────────────────────────────────────────────────────
    var currentStep = 1;
    var totalSteps  = 3;

    window.goToStep = function (n) {
        currentStep = n;
        document.querySelectorAll('[data-step]').forEach(function (el) {
            el.classList.toggle('hidden', el.dataset.step != n);
        });
        document.querySelectorAll('[data-step-btn]').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.stepBtn == n);
        });
        document.getElementById('btn-prev').classList.toggle('hidden', n === 1);
        document.getElementById('btn-next').classList.toggle('hidden', n === totalSteps);
    };

    window.nextStep = function () { if (currentStep < totalSteps) { goToStep(currentStep + 1); } };
    window.prevStep = function () { if (currentStep > 1) { goToStep(currentStep - 1); } };

    // ── Submit ────────────────────────────────────────────────────────────────
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errBox.classList.add('hidden');
        btnSalvar.disabled = true;

        var url  = editingId ? '{{ url('/demandas') }}/' + editingId : STORE_URL;
        var data = new FormData(form);

        var dtFields = [
            'prazo_atendimento_demanda', 'data_hora_agendamento', 'data_hora_inicio_carregamento', 'data_hora_fim_carregamento',
            'data_hora_saida_origem', 'data_hora_chegada_destino',
            'data_hora_inicio_descarregamento', 'data_hora_fim_descarregamento',
        ];
        dtFields.forEach(function (k) { if (! data.get(k)) { data.delete(k); } });

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

    // Fechar ao clicar fora do painel
    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target === overlay) { window.closeDemandaModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.closeDemandaModal(); }
    });

    // ── Util ──────────────────────────────────────────────────────────────────
    function fmtDatetime(iso) {
        if (! iso) { return ''; }
        return iso.replace('Z', '').replace(' ', 'T').substring(0, 16);
    }

    // ── Exportar — mantém filtros no href ────────────────────────────────────
    (function () {
        var exportBtn  = document.getElementById('btn-export-demandas');
        var filterForm = exportBtn && exportBtn.closest('form') ? exportBtn.closest('form') : document.querySelector('form[action*="demandas"]');
        var baseUrl    = '{{ route('demandas.export') }}';

        if (! exportBtn || ! filterForm) { return; }

        function syncExportUrl() {
            var params = new URLSearchParams(new FormData(filterForm));
            // remove entradas vazias
            var clean = new URLSearchParams();
            params.forEach(function (v, k) { if (v.trim() !== '') { clean.set(k, v); } });
            exportBtn.href = baseUrl + (clean.toString() ? '?' + clean.toString() : '');
        }

        filterForm.querySelectorAll('input, select').forEach(function (el) {
            el.addEventListener('change', syncExportUrl);
            el.addEventListener('input',  syncExportUrl);
        });

        syncExportUrl();
    })();

    // ── Tooltips das colunas ──────────────────────────────────────────────────
    (function () {
        var box = document.getElementById('col-tip-box');
        if (! box) { return; }

        document.querySelectorAll('.col-tip').forEach(function (el) {
            el.addEventListener('mouseenter', function (e) {
                box.textContent = el.dataset.tip;
                box.classList.remove('hidden');
                positionTip(e);
            });
            el.addEventListener('mousemove', positionTip);
            el.addEventListener('mouseleave', function () {
                box.classList.add('hidden');
            });
        });

        function positionTip(e) {
            var gap = 12;
            var bw  = box.offsetWidth  || 280;
            var bh  = box.offsetHeight || 60;
            var x   = e.clientX + gap;
            var y   = e.clientY - bh - gap;

            if (x + bw > window.innerWidth  - 8) { x = e.clientX - bw - gap; }
            if (y < 8)                            { y = e.clientY + gap; }

            box.style.left = x + 'px';
            box.style.top  = y + 'px';
        }
    })();
})();
</script>
@endpush

</x-layouts.app>
