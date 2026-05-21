<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte {{ $reporte->numero_reporte }}</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" href="/favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-shadow { box-shadow: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 py-10 text-zinc-900 antialiased">

    @php $tz = config('app.timezone'); @endphp

    {{-- Toolbar --}}
    <div class="no-print mx-auto mb-6 flex max-w-4xl items-center justify-between px-4">
        <a href="{{ route('reportes.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-xs hover:bg-slate-50">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Voltar
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-xs hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                </svg>
                Imprimir
            </button>
            <button onclick="copyLink()"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-3.5 py-2 text-sm font-medium text-white shadow-xs hover:bg-zinc-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                </svg>
                <span id="copy-label">Copiar Link</span>
            </button>
        </div>
    </div>

    {{-- Documento --}}
    <div class="print-shadow mx-auto max-w-4xl rounded-2xl bg-white px-10 py-10 shadow-lg">

        {{-- Cabeçalho --}}
        <div class="flex items-start justify-between border-b border-slate-200 pb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Gestão de Transporte — Macaé</p>
                        <h1 class="text-xl font-bold text-zinc-900">Reporte Operacional</h1>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <p class="font-mono text-2xl font-bold text-zinc-800">{{ $reporte->numero_reporte }}</p>
                @if($reporte->status === 'rascunho')
                    <span class="mt-1 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                        Rascunho
                    </span>
                @else
                    <span class="mt-1 inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                        Publicado
                    </span>
                @endif
            </div>
        </div>

        {{-- Metadados --}}
        <div class="mt-6 grid grid-cols-3 gap-4">
            <div class="rounded-lg bg-slate-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Nome</p>
                <p class="mt-1 text-sm font-semibold text-zinc-800">{{ $reporte->nome ?? '—' }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Data / Hora de Emissão</p>
                <p class="mt-1 text-sm font-semibold text-zinc-800">
                    {{ $reporte->data_hora_emissao?->setTimezone($tz)->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
            <div class="rounded-lg bg-slate-50 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Emitido Por</p>
                <p class="mt-1 text-sm font-semibold text-zinc-800">{{ $reporte->creator?->name ?? '—' }}</p>
            </div>
        </div>

        {{-- Tabela de itens --}}
        <div class="mt-8">
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-zinc-400">
                Veículos — {{ $reporte->itens->count() }} {{ $reporte->itens->count() === 1 ? 'item' : 'itens' }}
            </h2>

            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">#</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Prefixo</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Status Operacional</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Tempo Parado</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($reporte->itens as $i => $item)
                            <tr class="{{ $loop->even ? 'bg-slate-50/50' : 'bg-white' }}">
                                <td class="px-4 py-3 text-xs text-zinc-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-zinc-800">{{ $item->prefixo ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $item->status_operacional ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $item->tempo_parado ?? '—' }}</td>
                                <td class="px-4 py-3 text-zinc-500">{{ $item->observacao ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-400">Nenhum item registrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Rodapé --}}
        <div class="mt-10 border-t border-slate-100 pt-6 text-center text-xs text-zinc-400">
            Gestão de Transporte — Macaé &mdash; Reporte {{ $reporte->numero_reporte }} &mdash;
            Gerado em {{ now()->setTimezone($tz)->format('d/m/Y H:i') }}
        </div>
    </div>

    <script>
    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(function () {
            var label = document.getElementById('copy-label');
            label.textContent = 'Link copiado!';
            setTimeout(function () { label.textContent = 'Copiar Link'; }, 2000);
        });
    }
    </script>

</body>
</html>
