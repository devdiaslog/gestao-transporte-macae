<x-layouts.app title="Métricas de Produção">

    {{-- ─── Header ─────────────────────────────────────────────────────────── --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Métricas de Produção</h2>
            <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Ranking e evolução de emissões de reportes e ocorrências.</p>
        </div>
        {{-- Filtro de período --}}
        <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            @foreach(['hoje' => 'Hoje', '7' => '7 dias', '30' => '30 dias', '90' => '90 dias'] as $valor => $label)
                <a href="{{ route('metricas.index', $valor) }}"
                   class="rounded-lg px-3.5 py-1.5 text-sm font-medium transition-all
                          {{ $periodo === $valor
                              ? 'bg-zinc-900 text-white shadow-xs dark:bg-white dark:text-zinc-900'
                              : 'text-zinc-500 hover:bg-slate-50 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- ─── KPIs ────────────────────────────────────────────────────────────── --}}
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Reportes</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $kpis['reportes_total'] }}</p>
            <p class="mt-1 text-xs text-zinc-400">publicados no período</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Veículos Cobertos</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $kpis['veiculos_cobertos'] }}</p>
            <p class="mt-1 text-xs text-zinc-400">entradas em reportes</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ocorrências</p>
            <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $kpis['ocorrencias_total'] }}</p>
            <p class="mt-1 text-xs text-zinc-400">abertas no período</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Em Aberto</p>
            <p class="mt-2 text-3xl font-bold {{ $kpis['ocorrencias_abertas'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ $kpis['ocorrencias_abertas'] }}
            </p>
            <p class="mt-1 text-xs text-zinc-400">ocorrências sem fechamento</p>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- REPORTES                                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="mt-10">
        <h3 class="flex items-center gap-2 text-base font-semibold text-zinc-900 dark:text-zinc-100">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-zinc-900 dark:bg-white">
                <svg class="h-3.5 w-3.5 text-white dark:text-zinc-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
                </svg>
            </span>
            Reportes
        </h3>

        @if($rankingReportes->isEmpty())
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-400">Nenhum reporte publicado no período selecionado.</p>
            </div>
        @else
            {{-- Ranking --}}
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ranking por Emissor</p>
                </div>
                @php $maxReportes = $rankingReportes->max('reportes'); @endphp
                <div class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @foreach($rankingReportes as $pos => $row)
                        <div class="flex items-center gap-4 px-5 py-3.5">
                            {{-- Posição --}}
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $pos === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                 : ($pos === 1 ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                 : ($pos === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400'
                                 : 'bg-slate-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500')) }}">
                                {{ $pos + 1 }}
                            </span>
                            {{-- Nome --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['user'] }}</p>
                                {{-- Barra --}}
                                <div class="mt-1.5 h-1.5 w-full rounded-full bg-slate-100 dark:bg-zinc-800">
                                    <div class="h-1.5 rounded-full bg-zinc-900 dark:bg-zinc-300"
                                         style="width: {{ $maxReportes > 0 ? round(($row['reportes'] / $maxReportes) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            {{-- Stats --}}
                            <div class="flex shrink-0 items-center gap-6">
                                <div class="text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $row['reportes'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Reportes</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $row['veiculos'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Veículos</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $row['media'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Média/rep.</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Perfil por status operacional --}}
            @if($todosStatus->isNotEmpty())
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Perfil por Status Operacional</p>
                        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-600">Quantidade de veículos registrados por status em reportes</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-zinc-800">
                                    <th class="sticky left-0 z-10 whitespace-nowrap border-r border-slate-100 bg-white px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">Emissor</th>
                                    @foreach($todosStatus as $status)
                                        <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                            {{ $status }}
                                        </th>
                                    @endforeach
                                    <th class="sticky right-0 z-10 border-l border-slate-100 bg-white px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                @foreach($rankingReportes as $row)
                                    @php
                                        $userStatus = $statusPorUser->get($row['user_id'], collect());
                                        $maxStatus  = $userStatus->max() ?: 1;
                                    @endphp
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                        <td class="sticky left-0 z-10 whitespace-nowrap border-r border-slate-100 bg-white px-5 py-3 font-medium text-zinc-800 group-hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:group-hover:bg-zinc-800/40">
                                            {{ $row['user'] }}
                                        </td>
                                        @foreach($todosStatus as $status)
                                            @php $qtd = $userStatus->get($status, 0); @endphp
                                            <td class="px-4 py-3 text-center">
                                                @if($qtd > 0)
                                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-md px-2 py-0.5 text-xs font-semibold
                                                        {{ $qtd >= $maxStatus ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                                                         : ($qtd >= $maxStatus * 0.5 ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
                                                         : 'bg-slate-50 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-500') }}">
                                                        {{ $qtd }}
                                                    </span>
                                                @else
                                                    <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="sticky right-0 z-10 border-l border-slate-100 bg-white px-4 py-3 text-center group-hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:group-hover:bg-zinc-800/40">
                                            <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $userStatus->sum() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Evolução diária --}}
            @if($evolucaoReportes->isNotEmpty())
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Evolução Diária — Reportes</p>
                    </div>
                    <div class="p-5">
                        @php $maxDia = $evolucaoReportes->max() ?: 1; @endphp
                        <div class="flex items-end gap-2">
                            @foreach($evolucaoReportes as $dia => $qtd)
                                <div class="flex flex-1 flex-col items-center gap-1">
                                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $qtd }}</span>
                                    <div class="w-full rounded-t-md bg-zinc-900 dark:bg-zinc-300"
                                         style="height: {{ max(4, round(($qtd / $maxDia) * 80)) }}px"></div>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-600">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $dia)->format('d/m') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    {{-- OCORRÊNCIAS                                                            --}}
    {{-- ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="mt-10 mb-10">
        <h3 class="flex items-center gap-2 text-base font-semibold text-zinc-900 dark:text-zinc-100">
            <span class="flex h-6 w-6 items-center justify-center rounded-md bg-rose-600">
                <svg class="h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
            </span>
            Ocorrências
        </h3>

        @if($rankingOcorrencias->isEmpty())
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-10 text-center dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-sm text-zinc-400">Nenhuma ocorrência registrada no período selecionado.</p>
            </div>
        @else
            {{-- Ranking --}}
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Ranking por Emissor</p>
                </div>
                @php $maxOcorr = $rankingOcorrencias->max('total'); @endphp
                <div class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @foreach($rankingOcorrencias as $pos => $row)
                        <div class="flex items-center gap-4 px-5 py-3.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $pos === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                 : ($pos === 1 ? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                 : ($pos === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400'
                                 : 'bg-slate-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500')) }}">
                                {{ $pos + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['user'] }}</p>
                                @if($row['tipo_top_nome'])
                                    <p class="mt-0.5 truncate text-xs text-zinc-400 dark:text-zinc-500">
                                        Principal: <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ $row['tipo_top_nome'] }}</span>
                                    </p>
                                @endif
                                <div class="mt-1.5 h-1.5 w-full rounded-full bg-slate-100 dark:bg-zinc-800">
                                    <div class="h-1.5 rounded-full bg-rose-500 dark:bg-rose-400"
                                         style="width: {{ $maxOcorr > 0 ? round(($row['total'] / $maxOcorr) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-6">
                                <div class="text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $row['total'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Total</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ $row['abertas'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Em Aberto</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $row['fechadas'] }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Fechadas</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $row['pct'] }}%</p>
                                    <p class="text-[10px] uppercase tracking-wide text-zinc-400">Fechamento</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Perfil por tipo de ocorrência --}}
            @if($todosTipos->isNotEmpty() && $tiposPorUser->isNotEmpty())
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Perfil por Tipo de Ocorrência</p>
                        <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-600">Quantidade de ocorrências abertas por tipo por emissor</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-zinc-800">
                                    <th class="sticky left-0 z-10 whitespace-nowrap border-r border-slate-100 bg-white px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">Emissor</th>
                                    @foreach($todosTipos as $tipo)
                                        <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">
                                            {{ $tipo->descricao }}
                                        </th>
                                    @endforeach
                                    <th class="sticky right-0 z-10 border-l border-slate-100 bg-white px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                                @foreach($tiposPorUser as $userId => $tiposUser)
                                    @php $maxTipo = $tiposUser->max() ?: 1; @endphp
                                    <tr class="group hover:bg-slate-50 dark:hover:bg-zinc-800/40">
                                        <td class="sticky left-0 z-10 whitespace-nowrap border-r border-slate-100 bg-white px-5 py-3 font-medium text-zinc-800 group-hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:group-hover:bg-zinc-800/40">
                                            {{ $nomesUsersOcorrencias->get($userId, 'Desconhecido') }}
                                        </td>
                                        @foreach($todosTipos as $tipoId => $tipo)
                                            @php $qtd = $tiposUser->get($tipoId, 0); @endphp
                                            <td class="px-4 py-3 text-center">
                                                @if($qtd > 0)
                                                    <span class="inline-flex min-w-[2rem] items-center justify-center rounded-md px-2 py-0.5 text-xs font-semibold
                                                        {{ $qtd >= $maxTipo ? 'bg-rose-600 text-white dark:bg-rose-500'
                                                         : ($qtd >= $maxTipo * 0.5 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400'
                                                         : 'bg-slate-50 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-500') }}">
                                                        {{ $qtd }}
                                                    </span>
                                                @else
                                                    <span class="text-zinc-300 dark:text-zinc-700">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="sticky right-0 z-10 border-l border-slate-100 bg-white px-4 py-3 text-center group-hover:bg-slate-50 dark:border-zinc-800 dark:bg-zinc-900 dark:group-hover:bg-zinc-800/40">
                                            <span class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $tiposUser->sum() }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Evolução diária --}}
            @if($evolucaoOcorrencias->isNotEmpty())
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="border-b border-slate-100 px-5 py-3.5 dark:border-zinc-800">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Evolução Diária — Ocorrências</p>
                    </div>
                    <div class="p-5">
                        @php $maxDiaOcorr = $evolucaoOcorrencias->max() ?: 1; @endphp
                        <div class="flex items-end gap-2">
                            @foreach($evolucaoOcorrencias as $dia => $qtd)
                                <div class="flex flex-1 flex-col items-center gap-1">
                                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $qtd }}</span>
                                    <div class="w-full rounded-t-md bg-rose-500 dark:bg-rose-400"
                                         style="height: {{ max(4, round(($qtd / $maxDiaOcorr) * 80)) }}px"></div>
                                    <span class="text-[10px] text-zinc-400 dark:text-zinc-600">{{ \Carbon\Carbon::createFromFormat('Y-m-d', $dia)->format('d/m') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

</x-layouts.app>
