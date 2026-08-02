<x-layouts.app title="Itens de Entrega">

    @php
        $podeImportar = auth()->user()->can('itens-entrega.importar');
        $botaoSecundario = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold
                            text-zinc-700 shadow-xs transition-all duration-200 hover:bg-slate-50 active:scale-[0.98]
                            dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800';

        $totalItens = $trechos->sum('total');
        $totalPeso = $trechos->sum('peso');
        $totalSemPrevisao = $trechos->sum('sem_previsao');
    @endphp

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Itens de Entrega</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Itens por rota
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($podeImportar)
                <a href="{{ route('itens-entrega.modelo') }}" title="Baixar planilha modelo com o cabeçalho aceito" class="{{ $botaoSecundario }}">
                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                    </svg>
                    Baixar modelo
                </a>
                <button type="button" onclick="abrirModal('modal-importar')" title="Importar a planilha de itens exportada do SAP" class="{{ $botaoSecundario }}">
                    <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5"/>
                    </svg>
                    Importar planilha
                </button>
            @endif
            <a href="{{ route('itens-entrega.export', request()->query()) }}" class="{{ $botaoSecundario }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Exportar
            </a>
        </div>
    </div>

    @include('itens-entrega._cabecalho')

    @include('itens-entrega._filtros', ['rota' => 'itens-entrega.index'])

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
        @if($trechos->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Nenhum item neste recorte</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ajuste os filtros ou importe o export do SAP.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-zinc-800">
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Rota</th>
                            <th class="px-4 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Itens</th>
                            <th class="px-4 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Área</th>
                            <th class="px-4 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Sem previsão</th>
                            <th class="px-4 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Previsão no prazo</th>
                            <th class="px-4 py-4 text-right text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Previsão fora do prazo</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Prazo mais próximo</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/70">
                        @foreach($trechos as $t)
                            @php
                                $url = route('itens-entrega.trecho', array_merge(request()->except('page'), [
                                    'origem_norm' => $t->local_origem_norm,
                                    'destino_norm' => $t->local_destino_norm,
                                ]));
                                $prazo = $t->prazo_mais_proximo ? \Carbon\Carbon::parse($t->prazo_mais_proximo) : null;
                            @endphp
                            <tr class="cursor-pointer transition-colors hover:bg-slate-50 dark:hover:bg-zinc-800/40"
                                onclick="window.location='{{ $url }}'">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-zinc-100">
                                        <span>{{ $t->local_origem_norm ?? 'SEM ORIGEM' }}</span>
                                        <svg class="h-3.5 w-3.5 shrink-0 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                                        </svg>
                                        <span>{{ $t->local_destino_norm ?? 'SEM DESTINO' }}</span>
                                    </div>
                                    @if($t->contentores > 0)
                                        <p class="mt-0.5 text-[11px] text-zinc-400">{{ $t->contentores }} contentor(es)</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $t->total }}</td>
                                <td class="px-4 py-3 text-right">
                                    <p class="tabular-nums text-zinc-600 dark:text-zinc-400"
                                       title="Área de piso somada, contando cada embalagem uma vez">
                                        {{ $t->area > 0 ? number_format((float) $t->area, 2, ',', '.').' m²' : '—' }}
                                    </p>
                                    @if($t->medidas_suspeitas > 0)
                                        <p class="text-[10px] text-amber-600 dark:text-amber-500"
                                           title="Medidas fora de escala para transporte rodoviário, provavelmente enviadas pelo SAP em centímetros ou milímetros. Ficam de fora do total.">
                                            {{ $t->medidas_suspeitas }} fora de escala
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($t->sem_previsao > 0)
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $t->sem_previsao }}</span>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-700">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($t->no_prazo > 0)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">{{ $t->no_prazo }}</span>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-700">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($t->fora_do_prazo > 0)
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold tabular-nums text-red-700 dark:bg-red-950/40 dark:text-red-400">{{ $t->fora_do_prazo }}</span>
                                    @else
                                        <span class="text-xs text-zinc-300 dark:text-zinc-700">0</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($prazo)
                                        <p class="text-xs tabular-nums {{ $prazo->isPast() ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                                            {{ $prazo->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="text-[10px] text-zinc-400">{{ $prazo->diffForHumans() }}</p>
                                    @else
                                        <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <svg class="h-4 w-4 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <td class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ $trechos->count() }} {{ $trechos->count() === 1 ? 'rota' : 'rotas' }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $totalItens }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-zinc-700 dark:text-zinc-300">
                                {{ $trechos->sum('area') > 0 ? number_format((float) $trechos->sum('area'), 2, ',', '.').' m²' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $totalSemPrevisao }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $trechos->sum('no_prazo') }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $trechos->sum('fora_do_prazo') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    @if($podeImportar)
        @include('itens-entrega._modal-importar')
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
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { window.fecharModal('modal-importar'); }
        });
        (function () {
            var m = document.getElementById('modal-importar');
            if (m) {
                m.addEventListener('click', function (e) { if (e.target === m) { window.fecharModal('modal-importar'); } });
            }
            var form = document.getElementById('form-importar');
            if (form) {
                form.addEventListener('submit', function () {
                    var btn = document.getElementById('btn-importar');
                    btn.disabled = true;
                    btn.textContent = 'Importando…';
                });
            }
        })();
    </script>
    @endpush

</x-layouts.app>
