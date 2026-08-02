@if(session('success'))
    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
        </ul>
    </div>
@endif

@if(in_array(\App\Enums\StatusSap::SuspensoExterno->value, $statusSelecionados, true))
    <p class="mt-4 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300">
        Suspenso por fator do cliente — material indisponível, unitização inadequada, dados incorretos.
        Cada item permanece suspenso como base de cobrança; quando o cliente resolve, um subitem novo é criado no SAP.
    </p>
@endif

{{-- Semáforo da previsão: os três números que dizem o que falta replanejar --}}
<div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
    @foreach($situacoesResumo as $situacao)
        @php $ativo = request('situacao') === $situacao; @endphp
        <a href="{{ request()->fullUrlWithQuery(['situacao' => $ativo ? null : $situacao, 'page' => null]) }}"
           class="rounded-xl border px-4 py-3 transition-all
                  {{ $ativo
                      ? 'border-zinc-900 bg-zinc-50 dark:border-zinc-100 dark:bg-zinc-800'
                      : 'border-slate-200 bg-white hover:border-slate-300 dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-zinc-700' }}">
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full {{ $cores[$situacao]['dot'] }}"></span>
                <span class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $cores[$situacao]['label'] }}</span>
            </div>
            <p class="mt-1 text-2xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $resumo[$situacao] }}</p>
        </a>
    @endforeach
</div>
