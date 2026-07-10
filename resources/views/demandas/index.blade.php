<x-layouts.app title="Demandas">

@php
    $statusColors = [
        'pendente'     => 'zinc',
        'em_andamento' => 'blue',
        'concluida'    => 'emerald',
        'cancelada'    => 'rose',
    ];
    $tipoColors = [
        'load'         => 'blue',
        'backload'     => 'amber',
        'transferencia'=> 'violet',
    ];
    $isAdmin = auth()->user()->role->value === 'administrador';
@endphp

<div class="py-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-0">
            <h2 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Demandas</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                Registro e acompanhamento de demandas de transporte
            </p>
        </div>
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
               class="h-9 w-48 rounded-lg border border-slate-200 bg-white px-3 text-sm
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
        <button type="submit"
                class="h-9 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium
                       text-zinc-700 shadow-xs transition-colors hover:bg-zinc-50
                       dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            Filtrar
        </button>
        @if($search || $status || $tipo)
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
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 dark:border-zinc-800 dark:bg-zinc-950/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Número</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Veículo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Origem → Destino</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Prazo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Cadastro</th>
                            <th class="w-10 px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60">
                        @foreach($demandas as $demanda)
                            @php
                                $sc = $statusColors[$demanda->status_demanda->value] ?? 'zinc';
                                $tc = $demanda->tipo_demanda ? ($tipoColors[$demanda->tipo_demanda->value] ?? 'zinc') : null;
                                $prazoVencido = $demanda->prazo_atendimento_demanda && $demanda->prazo_atendimento_demanda->isPast()
                                    && !in_array($demanda->status_demanda->value, ['concluida', 'cancelada']);
                            @endphp
                            <tr class="group transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-3">
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
                                <td class="px-4 py-3">
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
                                <td class="px-4 py-3">
                                    @if($demanda->equipamento)
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $demanda->equipamento->prefixo }}
                                        </span>
                                        <span class="text-zinc-400 dark:text-zinc-600">
                                            {{ $demanda->equipamento->placa }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $demanda->localOrigem?->nome ?? '—' }}
                                    @if($demanda->localOrigem && $demanda->localDestino)
                                        <span class="mx-1 text-zinc-300 dark:text-zinc-700">→</span>
                                    @endif
                                    {{ $demanda->localDestino?->nome ?? '' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($demanda->prazo_atendimento_demanda)
                                        <span class="{{ $prazoVencido ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400' }}">
                                            {{ $demanda->prazo_atendimento_demanda->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                                 bg-{{ $sc }}-100 text-{{ $sc }}-700
                                                 dark:bg-{{ $sc }}-950/40 dark:text-{{ $sc }}-400">
                                        {{ $demanda->status_demanda->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-400 dark:text-zinc-600">
                                    {{ $demanda->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button"
                                                onclick="editDemanda({{ $demanda->id }}, {{ $demanda->toJson() }})"
                                                class="rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                                                       dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                        </button>
                                        @if($isAdmin)
                                        <form method="POST" action="{{ route('demandas.destroy', $demanda) }}"
                                              data-confirm data-user-name="a demanda #{{ $demanda->numero_demanda }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-md p-1.5 text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600
                                                           dark:hover:bg-red-950/40 dark:hover:text-red-400">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
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
             class="flex w-full max-w-3xl scale-95 flex-col opacity-0 overflow-hidden rounded-2xl border shadow-2xl
                    transition-all duration-200
                    border-slate-200 bg-white
                    dark:border-zinc-800 dark:bg-zinc-950"
             style="max-height: 80vh;">

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
                                <select name="tipo_demanda" id="f-tipo-demanda"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                               text-zinc-900 outline-none shadow-xs
                                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                               dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    <option value="">Selecione…</option>
                                    @foreach(\App\Enums\TipoDemanda::cases() as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Veículo (Prefixo)
                            </label>
                            <select name="equipamento_id" id="f-equipamento"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                           text-zinc-900 outline-none shadow-xs
                                           focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                           dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                           dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                <option value="">Selecione…</option>
                                @foreach($equipamentos as $eq)
                                    <option value="{{ $eq->id }}">{{ $eq->prefixo }} — {{ $eq->placa }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Local de Origem
                                </label>
                                <select name="local_origem_id" id="f-origem"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                               text-zinc-900 outline-none shadow-xs
                                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                               dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    <option value="">Selecione…</option>
                                    @foreach($locais as $local)
                                        <option value="{{ $local->id }}">{{ $local->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Local de Destino
                                </label>
                                <select name="local_destino_id" id="f-destino"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                               text-zinc-900 outline-none shadow-xs
                                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                               dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    <option value="">Selecione…</option>
                                    @foreach($locais as $local)
                                        <option value="{{ $local->id }}">{{ $local->nome }}</option>
                                    @endforeach
                                </select>
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
                            <div id="f-status-wrapper">
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Status
                                </label>
                                <select name="status_demanda" id="f-status"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                               text-zinc-900 outline-none shadow-xs
                                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                               dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                               dark:focus:border-zinc-500 dark:focus:ring-zinc-800">
                                    @foreach(\App\Enums\StatusDemanda::cases() as $s)
                                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                    @endforeach
                                </select>
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
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Início do Carregamento
                                </label>
                                <input type="datetime-local" name="data_hora_inicio_carregamento" id="f-ini-car"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Fim do Carregamento
                                </label>
                                <input type="datetime-local" name="data_hora_fim_carregamento" id="f-fim-car"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Saída da Origem
                                </label>
                                <input type="datetime-local" name="data_hora_saida_origem" id="f-saida"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Chegada ao Destino
                                </label>
                                <input type="datetime-local" name="data_hora_chegada_destino" id="f-chegada"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Início do Descarregamento
                                </label>
                                <input type="datetime-local" name="data_hora_inicio_descarregamento" id="f-ini-des"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                    Fim do Descarregamento
                                </label>
                                <input type="datetime-local" name="data_hora_fim_descarregamento" id="f-fim-des"
                                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                              text-zinc-900 outline-none shadow-xs
                                              focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                              dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                              dark:focus:border-zinc-500 dark:focus:ring-zinc-800
                                              dark:[color-scheme:dark]">
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
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                                             text-zinc-900 outline-none shadow-xs resize-none
                                             focus:border-zinc-400 focus:ring-2 focus:ring-zinc-200
                                             dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100
                                             dark:focus:border-zinc-500 dark:focus:ring-zinc-800"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Mensagem de erro --}}
                <div id="demanda-error"
                     class="hidden mx-6 mb-0 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700
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
                                       disabled:opacity-60 disabled:cursor-not-allowed">
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
    var modal   = document.getElementById('demanda-modal');
    var overlay = document.getElementById('demanda-modal-overlay');
    var panel   = document.getElementById('demanda-modal-panel');
    var title   = document.getElementById('demanda-modal-title');
    var form    = document.getElementById('demanda-form');
    var errBox  = document.getElementById('demanda-error');
    var btnSalvar = document.getElementById('btn-salvar');
    var currentStep = 1;
    var totalSteps  = 3;
    var editingId   = null;

    var STORE_URL  = '{{ route('demandas.store') }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';

    // ── Modal open/close ──────────────────────────────────────────────────────
    window.openDemandaModal = function () {
        editingId = null;
        title.textContent = 'Nova Demanda';
        form.reset();
        document.getElementById('demanda-method').value = 'POST';
        document.getElementById('f-numero-demanda').disabled = false;
        document.getElementById('f-status-wrapper').classList.add('hidden');
        errBox.classList.add('hidden');
        goToStep(1);
        openModal();
    };

    window.editDemanda = function (id, data) {
        editingId = id;
        title.textContent = 'Editar Demanda #' + data.numero_demanda;
        form.reset();
        document.getElementById('demanda-method').value = 'PUT';
        document.getElementById('f-numero-demanda').disabled = true;
        document.getElementById('f-status-wrapper').classList.remove('hidden');
        errBox.classList.add('hidden');

        document.getElementById('f-numero-demanda').value = data.numero_demanda || '';
        document.getElementById('f-tipo-demanda').value   = data.tipo_demanda   || '';
        document.getElementById('f-equipamento').value    = data.equipamento_id || '';
        document.getElementById('f-origem').value         = data.local_origem_id || '';
        document.getElementById('f-destino').value        = data.local_destino_id || '';
        document.getElementById('f-prazo').value          = fmtDatetime(data.prazo_atendimento_demanda);
        document.getElementById('f-status').value         = data.status_demanda || '';
        document.getElementById('f-ini-car').value        = fmtDatetime(data.data_hora_inicio_carregamento);
        document.getElementById('f-fim-car').value        = fmtDatetime(data.data_hora_fim_carregamento);
        document.getElementById('f-saida').value          = fmtDatetime(data.data_hora_saida_origem);
        document.getElementById('f-chegada').value        = fmtDatetime(data.data_hora_chegada_destino);
        document.getElementById('f-ini-des').value        = fmtDatetime(data.data_hora_inicio_descarregamento);
        document.getElementById('f-fim-des').value        = fmtDatetime(data.data_hora_fim_descarregamento);
        document.getElementById('f-obs').value            = data.observacao_adicional || '';

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

        var url    = editingId ? '{{ url('/demandas') }}/' + editingId : STORE_URL;
        var data   = new FormData(form);

        // Remove empty datetime fields so backend treats them as null
        var dtFields = ['prazo_atendimento_demanda','data_hora_inicio_carregamento','data_hora_fim_carregamento',
                        'data_hora_saida_origem','data_hora_chegada_destino',
                        'data_hora_inicio_descarregamento','data_hora_fim_descarregamento'];
        dtFields.forEach(function (k) {
            if (! data.get(k)) { data.delete(k); }
        });

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

    // ── Fechar ao clicar fora ─────────────────────────────────────────────────
    modal.addEventListener('click', function (e) {
        if (e.target === modal || e.target === overlay) { window.closeDemandaModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && ! modal.classList.contains('hidden')) { window.closeDemandaModal(); }
    });

    // ── Util ──────────────────────────────────────────────────────────────────
    function fmtDatetime(iso) {
        if (! iso) { return ''; }
        // "2026-07-10T14:30:00.000000Z" → "2026-07-10T14:30"
        return iso.replace('Z', '').replace(/ /, 'T').substring(0, 16);
    }
})();
</script>
@endpush

</x-layouts.app>
