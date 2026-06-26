<x-layouts.app title="Alertas">

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Alertas da Frota</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Lembretes agendados por veículo. Crie novos alertas a partir da Torre de Controle, no botão do veículo.
            </p>
        </div>
    </div>

    {{-- Filtros --}}
    @php
        $escopos = ['todos' => 'Todos os alertas', 'meus' => 'Só os meus'];
        $statuses = ['pendentes' => 'Pendentes', 'resolvidos' => 'Resolvidos', 'todos' => 'Todos'];
    @endphp
    <div class="mt-6 flex flex-wrap items-center gap-4">
        <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 dark:border-zinc-800">
            @foreach($escopos as $key => $label)
                <a href="{{ route('alertas.index', ['escopo' => $key, 'status' => $status]) }}"
                   class="px-3.5 py-1.5 text-xs font-medium transition-colors
                          {{ $escopo === $key ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-white text-zinc-600 hover:bg-slate-50 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 dark:border-zinc-800">
            @foreach($statuses as $key => $label)
                <a href="{{ route('alertas.index', ['escopo' => $escopo, 'status' => $key]) }}"
                   class="px-3.5 py-1.5 text-xs font-medium transition-colors
                          {{ $status === $key ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-white text-zinc-600 hover:bg-slate-50 dark:bg-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-900' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Lista --}}
    <div class="mt-6 space-y-3">
        @forelse($alertas as $alerta)
            @php
                $tz = config('app.timezone');
                $disparado = $alerta->disparado;
                $resolvido = $alerta->status === 'resolvido';
                $souDono = $alerta->criado_por === auth()->id();
            @endphp
            <div class="overflow-hidden rounded-xl border shadow-sm transition-colors
                        {{ $disparado ? 'border-rose-200 bg-rose-50/60 dark:border-rose-900/50 dark:bg-rose-950/20' : 'border-slate-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/50' }}">
                <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Veículo --}}
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25"/>
                                </svg>
                                {{ trim(($alerta->equipamento?->prefixo ?? '').' '.($alerta->equipamento?->placa ?? '')) ?: '—' }}
                            </span>

                            {{-- Status badge --}}
                            @if($resolvido)
                                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Resolvido</span>
                            @elseif($disparado)
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>Disparado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:bg-sky-950/50 dark:text-sky-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>Agendado
                                </span>
                            @endif

                            {{-- Escopo --}}
                            @if($alerta->para_todos)
                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-medium text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">Para todos</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-500 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-500 dark:ring-zinc-700">Só meu</span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $alerta->lembrete }}</p>

                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-500">
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/>
                                </svg>
                                {{ $alerta->data_hora_alerta?->setTimezone($tz)->format('d/m/Y H:i') ?? '—' }}
                            </span>
                            <span>Criado por {{ $alerta->criador?->name ?? '—' }}</span>
                            @if($resolvido && $alerta->resolvedor)
                                <span>Resolvido por {{ $alerta->resolvedor->name }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Ações --}}
                    @unless($resolvido)
                        <div class="flex flex-wrap items-center gap-1.5">
                            {{-- Resolver: qualquer um que vê pode --}}
                            <form method="POST" action="{{ route('alertas.resolver', $alerta) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors
                                               border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900/50 dark:text-emerald-400 dark:hover:bg-emerald-950/40">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    Resolver
                                </button>
                            </form>

                            @if($souDono)
                                {{-- Prorrogar rápido --}}
                                <form method="POST" action="{{ route('alertas.prorrogar', $alerta) }}">
                                    @csrf
                                    <input type="hidden" name="minutos" value="60">
                                    <button type="submit" title="Adiar 1 hora"
                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors
                                                   border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800/70">
                                        +1h
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('alertas.prorrogar', $alerta) }}">
                                    @csrf
                                    <input type="hidden" name="minutos" value="1440">
                                    <button type="submit" title="Adiar 1 dia"
                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors
                                                   border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800/70">
                                        +1d
                                    </button>
                                </form>

                                {{-- Editar --}}
                                <button type="button"
                                        onclick="openEditAlerta(this)"
                                        data-alerta="{{ json_encode([
                                            'url' => route('alertas.update', $alerta),
                                            'lembrete' => $alerta->lembrete,
                                            'data_hora_alerta' => $alerta->data_hora_alerta?->setTimezone($tz)->format('Y-m-d\TH:i'),
                                            'para_todos' => $alerta->para_todos,
                                        ]) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors
                                               border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800/70">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>
                                    Editar
                                </button>

                                {{-- Remover --}}
                                <form method="POST" action="{{ route('alertas.destroy', $alerta) }}"
                                      data-confirm="true" data-user-name="este alerta">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors
                                                   border-red-200 text-red-600 hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endunless
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-16 text-center dark:border-zinc-800 dark:bg-zinc-900/50">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/60">
                    <svg class="h-7 w-7 text-zinc-400 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Nenhum alerta encontrado</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Crie alertas a partir da Torre de Controle, no botão do veículo.</p>
            </div>
        @endforelse
    </div>

    @if($alertas->hasPages())
        <div class="mt-6">{{ $alertas->withQueryString()->links() }}</div>
    @endif

    {{-- ─── Modal: Editar alerta ───────────────────────────────────────────── --}}
    <div id="alerta-backdrop" onclick="closeEditAlerta()"
         class="fixed inset-0 z-40 hidden bg-black/40 backdrop-blur-sm"></div>

    <div id="alerta-modal"
         class="fixed inset-x-4 top-1/2 z-50 hidden w-full max-w-md -translate-y-1/2 overflow-hidden
                rounded-2xl border shadow-2xl border-slate-200 bg-white dark:border-zinc-700 dark:bg-zinc-900
                sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2">
        <div class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Editar alerta</h3>
            <button type="button" onclick="closeEditAlerta()"
                    class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="alerta-form" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4 px-5 py-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Lembrete <span class="text-red-500">*</span></label>
                    <textarea id="alerta-lembrete" name="lembrete" rows="3" maxlength="500" required
                              class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                     border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                     dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400">Data e hora <span class="text-red-500">*</span></label>
                    <input id="alerta-data" name="data_hora_alerta" type="datetime-local" required
                           class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm shadow-xs outline-none transition-all
                                  border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10">
                </div>
                <label class="flex items-center gap-2">
                    <input id="alerta-todos" name="para_todos" type="checkbox" value="1"
                           class="h-4 w-4 rounded border-slate-300 accent-zinc-900 dark:accent-zinc-100">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">Alertar todos os usuários</span>
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 border-t px-5 py-3.5 border-slate-200 dark:border-zinc-800">
                <button type="button" onclick="closeEditAlerta()"
                        class="rounded-lg border px-4 py-2 text-sm font-medium border-slate-200 text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar
                </button>
            </div>
        </form>
    </div>

    <script>
    (function () {
        window.openEditAlerta = function (btn) {
            var d = JSON.parse(btn.dataset.alerta);
            document.getElementById('alerta-form').action   = d.url;
            document.getElementById('alerta-lembrete').value = d.lembrete || '';
            document.getElementById('alerta-data').value     = d.data_hora_alerta || '';
            document.getElementById('alerta-todos').checked  = !! d.para_todos;
            document.getElementById('alerta-backdrop').classList.remove('hidden');
            document.getElementById('alerta-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };
        window.closeEditAlerta = function () {
            document.getElementById('alerta-backdrop').classList.add('hidden');
            document.getElementById('alerta-modal').classList.add('hidden');
            document.body.style.overflow = '';
        };
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { window.closeEditAlerta(); }
        });
    })();
    </script>

</x-layouts.app>
