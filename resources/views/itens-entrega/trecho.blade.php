<x-layouts.app title="Itens de Entrega">

    @php
        $podePrever = auth()->user()->can('itens-entrega.prever');
        $podePrazo = auth()->user()->can('itens-entrega.prazo');
        $podeEscopo = auth()->user()->can('itens-entrega.escopo');
        $rotuloTrecho = ($origemTrecho ?? 'SEM ORIGEM').' → '.($destinoTrecho ?? 'SEM DESTINO');
    @endphp

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('itens-entrega.index', request()->except(['origem_norm', 'destino_norm', 'page'])) }}"
               class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Todas as rotas
            </a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                @if($origemTrecho || $destinoTrecho)
                    {{ $rotuloTrecho }}
                @else
                    Itens de Entrega
                @endif
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ $itens->total() }} item(ns) nesta rota. Selecione para informar a previsão ou corrigir a rota.
            </p>
        </div>
        <a href="{{ route('itens-entrega.export', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold
                  text-zinc-700 shadow-xs transition-all duration-200 hover:bg-slate-50 active:scale-[0.98]
                  dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Exportar
        </a>
    </div>

    @include('itens-entrega._cabecalho')

    @include('itens-entrega._filtros', ['rota' => 'itens-entrega.trecho'])

    {{-- Barra de ações em lote, revelada quando há seleção --}}
    @if($podePrever || $podePrazo || $podeEscopo)
    @php
        $acaoLote = 'rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700
                     transition-colors hover:bg-slate-50 hover:text-zinc-900
                     dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100';
    @endphp
    <div id="barra-lote" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-100 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                <span id="contador-selecao">0</span> selecionado(s)
            </span>
            @if($podePrever)
                <button type="button" onclick="abrirModal('modal-previsao')"
                        class="rounded-lg bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Definir previsão
                </button>
                <button type="button" onclick="abrirModal('modal-rota')" class="{{ $acaoLote }}">
                    Corrigir rota
                </button>
            @endif
            @if($podePrazo)
                <button type="button" onclick="abrirModal('modal-prazo')" class="{{ $acaoLote }}">
                    Renegociar prazo
                </button>
            @endif
            @if($podeEscopo)
                <button type="button" onclick="abrirModal('modal-escopo')" class="{{ $acaoLote }}">
                    Não é nossa responsabilidade
                </button>
            @endif
            <button type="button" onclick="limparSelecao()"
                    class="ml-auto text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                Limpar seleção
            </button>
        </div>
    </div>
    @endif

    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
        @if($itens->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Nenhum item neste recorte</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajuste os filtros ou importe o export do SAP.</p>
            </div>
        @else
            {{-- Scroll interno: o cabeçalho e os totais ficam à vista por mais
                 longa que seja a lista, e a página não estica sem fim. --}}
            <div class="max-h-[60vh] overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-20 bg-white shadow-[0_1px_0_0_rgb(226_232_240)] dark:bg-zinc-900 dark:shadow-[0_1px_0_0_rgb(39_39_42)]">
                        <tr>
                            @if($podePrever || $podePrazo || $podeEscopo)
                            <th class="w-10 px-4 py-4">
                                <input type="checkbox" id="marcar-todos" onclick="alternarTodos(this)"
                                       class="h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-700">
                            </th>
                            @endif
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Item</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Carga</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Retirada</th>
                            @unless($origemTrecho || $destinoTrecho)
                                <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Rota</th>
                            @endunless
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Liberada</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Prazo</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Previsão</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Situação</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">SAP</th>
                        </tr>
                    </thead>
                    @php $colunas = ($podePrever || $podePrazo || $podeEscopo ? 1 : 0) + (($origemTrecho || $destinoTrecho) ? 7 : 8); @endphp
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @php $embalagemAnterior = false; @endphp
                        @foreach($itens as $item)
                            @php
                                $situacao = $item->fora_escopo ? 'fora_escopo' : $item->situacaoPrevisao();
                                $vencido = $item->prazo_item && $item->prazo_item->isPast();
                                $embalagem = $item->embalagemSuperior();
                                $abreGrupo = $embalagem !== null && $embalagem !== $embalagemAnterior;
                                $embalagemAnterior = $embalagem;
                            @endphp

                            {{-- Cabeçalho da embalagem: o que de fato ocupa o piso --}}
                            @if($abreGrupo)
                                @php $grupo = $embalagens[$embalagem] ?? null; @endphp
                                <tr class="bg-slate-50/80 dark:bg-zinc-800/40">
                                    @if($podePrever || $podePrazo || $podeEscopo)
                                        <td class="px-4 py-2">
                                            <input type="checkbox" title="Selecionar todos os itens desta embalagem"
                                                   class="chk-embalagem h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-700"
                                                   onclick="alternarEmbalagem(this, '{{ $embalagem }}')">
                                        </td>
                                    @endif
                                    <td colspan="{{ $colunas - ($podePrever || $podePrazo || $podeEscopo ? 1 : 0) }}" class="px-4 py-2">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">📦 {{ $embalagem }}</span>
                                            @if($grupo && $grupo['descricao'])
                                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $grupo['descricao'] }}</span>
                                            @endif
                                            @if($grupo)
                                                <span class="text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
                                                    {{ $grupo['itens'] }} {{ $grupo['itens'] === 1 ? 'item' : 'itens' }}
                                                    @if($grupo['peso'] > 0) · {{ number_format($grupo['peso'], 0, ',', '.') }} kg @endif
                                                    @if($grupo['area']) · <span class="font-semibold">{{ number_format($grupo['area'], 2, ',', '.') }} m² de piso</span> @endif
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                @if($podePrever || $podePrazo || $podeEscopo)
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="chk-item h-4 w-4 rounded border-slate-300 text-zinc-900 dark:border-zinc-700"
                                           value="{{ $item->id }}"
                                           data-origem="{{ $item->local_origem }}"
                                           data-destino="{{ $item->local_destino }}"
                                           data-contentor="{{ $embalagem }}"
                                           onclick="atualizarSelecao()">
                                </td>
                                @endif

                                <td class="px-4 py-3 {{ $embalagem ? 'border-l-2 border-slate-300 pl-6 dark:border-zinc-600' : '' }}">
                                    <p class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $item->numero_rt }}<span class="text-zinc-400">/{{ $item->numero_item }}@if($item->subitem)/{{ $item->subitem }}@endif</span>
                                    </p>
                                    <p class="mt-0.5 max-w-72 truncate text-xs text-zinc-500 dark:text-zinc-400" title="{{ $item->descricao_item }}">
                                        {{ $item->descricao_item ?? '—' }}
                                    </p>
                                    @if($item->ausente_no_sap_em || $item->voltouAoSap())
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @if($item->ausente_no_sap_em)
                                                <span title="Não veio na importação de {{ $item->ausente_no_sap_em->format('d/m/Y H:i') }}"
                                                      class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">
                                                    conferir no SAP
                                                </span>
                                            @elseif($item->previsaoAnteriorAoRetorno())
                                                <span title="Voltou em {{ $item->retornou_ao_sap_em->format('d/m/Y H:i') }} com a previsão dada antes de sumir — confirme a data com o cliente"
                                                      class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-400">
                                                    previsão anterior ao retorno
                                                </span>
                                            @endif
                                            @if($item->vezes_ausente > 0)
                                                <span title="Saiu e voltou {{ $item->vezes_ausente }}x no SAP. Último retorno em {{ $item->retornou_ao_sap_em?->format('d/m/Y H:i') }}"
                                                      class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                                    {{ $item->vezes_ausente }}x fora do SAP
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- Carga: o que a operação precisa saber para dimensionar o veículo --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item->peso_total)
                                        <p class="text-xs font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">
                                            {{ number_format((float) $item->peso_total, 0, ',', '.') }} kg
                                        </p>
                                    @else
                                        <p class="text-xs text-zinc-400">sem peso</p>
                                    @endif
                                    @if($item->dimensoes())
                                        <p class="mt-0.5 text-[11px] tabular-nums {{ $item->medidaSuspeita() ? 'text-amber-700 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400' }}"
                                           title="{{ $item->medidaSuspeita()
                                                ? 'Medida fora de escala para transporte rodoviário — o SAP deve ter enviado em centímetros ou milímetros. Não entra nos totais.'
                                                : 'Comprimento × Largura × Altura, em metros' }}">
                                            {{ $item->dimensoes() }} m
                                            @if($item->medidaSuspeita()) ⚠ @endif
                                        </p>
                                    @endif
                                    @if($embalagem)
                                        <p class="text-[10px] text-zinc-400"
                                           title="Vai dentro da embalagem {{ $embalagem }} — quem ocupa o piso é ela">
                                            área contada na embalagem
                                        </p>
                                    @elseif($item->area())
                                        <p class="text-[10px] tabular-nums text-zinc-400"
                                           title="Área de piso ocupada: comprimento × largura">
                                            {{ number_format($item->area(), 2, ',', '.') }} m² de piso
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <p class="max-w-40 truncate text-xs text-zinc-700 dark:text-zinc-300" title="{{ $item->descricao_local_retirada }}">
                                        {{ $item->descricao_local_retirada ?? '—' }}
                                    </p>
                                </td>

                                @unless($origemTrecho || $destinoTrecho)
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-zinc-700 dark:text-zinc-300">{{ $item->local_origem ?? '—' }}</p>
                                        <p class="text-xs text-zinc-400">↓</p>
                                        <p class="text-xs text-zinc-700 dark:text-zinc-300">{{ $item->local_destino ?? '—' }}</p>
                                    </td>
                                @endunless

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item->data_hora_liberacao_rt)
                                        <p class="text-xs tabular-nums text-zinc-700 dark:text-zinc-300">{{ $item->data_hora_liberacao_rt->format('d/m/Y H:i') }}</p>
                                        <p class="text-[10px] text-zinc-400" title="Tempo desde que o cliente liberou o item">
                                            {{ $item->data_hora_liberacao_rt->diffForHumans() }}
                                        </p>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item->prazo_item)
                                        <p class="text-xs tabular-nums {{ $vencido ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                                            {{ $item->prazo_item->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="text-[10px] text-zinc-400">{{ $item->prazo_item->diffForHumans() }}</p>
                                        @if($item->prazoRenegociado())
                                            <p class="mt-0.5 text-[10px] text-amber-600 dark:text-amber-500"
                                               title="{{ $item->prazo_motivo }}">
                                                renegociado &middot; SAP: {{ $item->prazo_sap->format('d/m/Y H:i') }}
                                            </p>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item->data_hora_previsao_entrega)
                                        <p class="text-xs tabular-nums text-zinc-700 dark:text-zinc-300">{{ $item->data_hora_previsao_entrega->format('d/m/Y H:i') }}</p>
                                        @if($item->previsaoAtual)
                                            <p class="text-[10px] text-zinc-400" title="{{ $item->previsaoAtual->motivo }}">
                                                {{ $item->previsaoAtual->autorLabel() }} · {{ $item->previsaoAtual->created_at->format('d/m H:i') }}
                                            </p>
                                        @endif
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $cores[$situacao]['chip'] }}">
                                        {{ $cores[$situacao]['label'] }}
                                    </span>
                                    @if($item->fora_escopo && $item->fora_escopo_justificativa)
                                        <p class="mt-1 max-w-56 truncate text-[10px] text-zinc-500" title="{{ $item->fora_escopo_justificativa }}">
                                            {{ $item->fora_escopo_justificativa }}
                                        </p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($item->status_sap)
                                        <span class="text-xs text-zinc-700 dark:text-zinc-300" title="{{ $item->status_sap->descricao() }}">
                                            {{ $item->status_sap->label() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                    @if($item->demanda)
                                        <p class="text-[10px] text-zinc-400">
                                            {{ $item->demanda->numero_demanda }}
                                            @if($item->demanda->equipamento) · {{ $item->demanda->equipamento->prefixo }} @endif
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="sticky bottom-0 z-20">
                        @php
                            // Somatórios da página exibida — é o que a operação
                            // enxerga ao decidir se cabe num veículo só. A área
                            // vem do controller porque cada contentor conta uma
                            // vez, independente de quantos itens carrega.
                            $pesoPagina = $itens->sum('peso_total');
                            $areaPagina = $areaDePiso;
                        @endphp
                        <tr class="border-t-2 border-slate-200 bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800">
                            @if($podePrever || $podePrazo || $podeEscopo)<td></td>@endif
                            <td class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ $itens->count() }} nesta página
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-bold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $pesoPagina > 0 ? number_format((float) $pesoPagina, 0, ',', '.').' kg' : '—' }}
                                </p>
                                @if($areaPagina > 0)
                                    <p class="text-[10px] tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ number_format($areaPagina, 2, ',', '.') }} m² de piso
                                    </p>
                                @endif
                            </td>
                            <td colspan="{{ ($origemTrecho || $destinoTrecho) ? 5 : 6 }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-3 dark:border-zinc-800">
                {{ $itens->links() }}
            </div>
        @endif
    </div>

    {{-- Modal: definir previsão --}}
    @if($podePrever)
    <div id="modal-previsao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <form method="POST" action="{{ route('itens-entrega.previsao') }}" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            @csrf
            <div id="itens-previsao"></div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Definir previsão de entrega</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span id="resumo-previsao"></span>
            </p>

            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Data e hora previstas</label>
            <input type="datetime-local" name="data_hora_previsao" required
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">

            <label class="mt-3 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Motivo (opcional)</label>
            <input type="text" name="motivo" maxlength="500" placeholder="Ex.: aguardando carreta disponível"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="fecharModal('modal-previsao')" class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Cancelar</button>
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Registrar</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Modal: fora do escopo --}}
    @if($podeEscopo)
    <div id="modal-escopo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <form method="POST" action="{{ route('itens-entrega.escopo') }}" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            @csrf
            <div id="itens-escopo"></div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Não é nossa responsabilidade</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span id="resumo-escopo"></span> — o item sai da fila de previsão, mas continua visível com a justificativa.
            </p>

            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Justificativa</label>
            <textarea name="justificativa" rows="3" required minlength="5" maxlength="500"
                      placeholder="Ex.: transporte próprio do cliente"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>

            <div class="mt-5 flex items-center justify-between gap-2">
                <button type="submit" name="fora_escopo" value="0"
                        class="text-xs font-medium text-zinc-500 underline hover:text-zinc-800 dark:text-zinc-400">
                    Devolver ao nosso escopo
                </button>
                <div class="flex gap-2">
                    <button type="button" onclick="fecharModal('modal-escopo')" class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Cancelar</button>
                    <button type="submit" name="fora_escopo" value="1" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Marcar</button>
                </div>
            </div>
        </form>
    </div>
    @endif

    {{-- Modal: renegociar prazo --}}
    @if($podePrazo)
    <div id="modal-prazo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <form method="POST" action="{{ route('itens-entrega.prazo') }}" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            @csrf
            <div id="itens-prazo"></div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Renegociar prazo</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span id="resumo-prazo"></span>
            </p>
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                O novo prazo passa a valer sobre o do SAP e não será desfeito pela próxima importação.
                O prazo original continua visível ao lado.
            </p>

            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Novo prazo acordado</label>
            <input type="datetime-local" name="prazo_item" required
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">

            <label class="mt-3 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Motivo</label>
            <textarea name="motivo" rows="2" required minlength="5" maxlength="500"
                      placeholder="Com quem foi acordado e por quê"
                      class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="fecharModal('modal-prazo')" class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Cancelar</button>
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Registrar</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Modal: corrigir rota --}}
    @if($podePrever)
    <div id="modal-rota" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <form method="POST" action="{{ route('itens-entrega.rota') }}" class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-zinc-900">
            @csrf
            <div id="itens-rota"></div>
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Corrigir rota</h3>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                <span id="resumo-rota"></span>
            </p>
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
                O que você corrigir aqui não será desfeito pela próxima importação do SAP.
                Deixe em branco o campo que não quiser alterar.
            </p>

            <label class="mt-4 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Origem</label>
            <input type="text" name="local_origem" list="locais-conhecidos" maxlength="255" placeholder="Manter como está"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">

            <label class="mt-3 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Destino</label>
            <input type="text" name="local_destino" list="locais-conhecidos" maxlength="255" placeholder="Manter como está"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">

            <datalist id="locais-conhecidos">
                @foreach($locais as $local)<option value="{{ $local }}">@endforeach
            </datalist>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="fecharModal('modal-rota')" class="rounded-lg px-4 py-2 text-sm text-zinc-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800">Cancelar</button>
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Corrigir</button>
            </div>
        </form>
    </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            var barra = document.getElementById('barra-lote');

            function selecionados() {
                return Array.prototype.slice.call(document.querySelectorAll('.chk-item:checked'));
            }

            /**
             * Descreve o que foi selecionado pelo que os itens têm em comum —
             * é assim que a operação pensa ao definir a previsão: um trecho
             * inteiro ou um contentor de uma vez.
             */
            function descrever(marcados) {
                if (marcados.length === 0) { return ''; }

                var trechos = {}, contentores = {};
                marcados.forEach(function (c) {
                    trechos[(c.dataset.origem || '?') + ' → ' + (c.dataset.destino || '?')] = true;
                    if (c.dataset.contentor) { contentores[c.dataset.contentor] = true; }
                });

                var listaTrechos = Object.keys(trechos);
                var listaContentores = Object.keys(contentores);
                var texto = marcados.length + ' item(ns)';

                if (listaTrechos.length === 1) {
                    texto += ' · ' + listaTrechos[0];
                } else {
                    texto += ' · ' + listaTrechos.length + ' trechos diferentes';
                }

                if (listaContentores.length === 1) {
                    texto += ' · contentor ' + listaContentores[0];
                }

                return texto;
            }

            window.atualizarSelecao = function () {
                var marcados = selecionados();
                document.getElementById('contador-selecao').textContent = marcados.length;
                if (barra) { barra.classList.toggle('hidden', marcados.length === 0); }

                var descricao = descrever(marcados);
                ['resumo-previsao', 'resumo-escopo', 'resumo-rota', 'resumo-prazo'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el) { el.textContent = descricao; }
                });

                // Os ids seguem para o POST como campos ocultos dos formulários.
                ['itens-previsao', 'itens-escopo', 'itens-rota', 'itens-prazo'].forEach(function (id) {
                    var alvo = document.getElementById(id);
                    if (!alvo) { return; }
                    alvo.innerHTML = marcados.map(function (c) {
                        return '<input type="hidden" name="itens[]" value="' + c.value + '">';
                    }).join('');
                });
            };

            window.alternarTodos = function (origem) {
                document.querySelectorAll('.chk-item').forEach(function (c) { c.checked = origem.checked; });
                document.querySelectorAll('.chk-embalagem').forEach(function (c) { c.checked = origem.checked; });
                window.atualizarSelecao();
            };

            /**
             * Seleciona de uma vez todos os itens da embalagem: a previsão
             * costuma valer para o contentor inteiro, não item a item.
             */
            window.alternarEmbalagem = function (origem, embalagem) {
                document.querySelectorAll('.chk-item[data-contentor="' + embalagem + '"]')
                    .forEach(function (c) { c.checked = origem.checked; });
                window.atualizarSelecao();
            };

            window.limparSelecao = function () {
                document.querySelectorAll('.chk-item, .chk-embalagem').forEach(function (c) { c.checked = false; });
                var todos = document.getElementById('marcar-todos');
                if (todos) { todos.checked = false; }
                window.atualizarSelecao();
            };

            window.abrirModal = function (id) {
                var m = document.getElementById(id);
                if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
            };

            window.fecharModal = function (id) {
                var m = document.getElementById(id);
                if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
            };

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { ['modal-previsao', 'modal-escopo', 'modal-rota', 'modal-prazo'].forEach(window.fecharModal); }
            });

            // Fecha ao clicar no fundo, não no conteúdo do modal.
            ['modal-previsao', 'modal-escopo', 'modal-rota', 'modal-prazo'].forEach(function (id) {
                var m = document.getElementById(id);
                if (m) {
                    m.addEventListener('click', function (e) {
                        if (e.target === m) { window.fecharModal(id); }
                    });
                }
            });
        })();
    </script>
    @endpush

</x-layouts.app>
