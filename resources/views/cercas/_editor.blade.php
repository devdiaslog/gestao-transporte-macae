@php
    /** @var array $poligono, array $vizinhas — recebidos via @include */
    $poligono = $poligono ?? [];
    $vizinhas = $vizinhas ?? [];
@endphp

{{--
    Painel de desenho da cerca (Leaflet + Geoman + Turf).
    Usado por create e edit — toda a lógica vive em resources/js/cercas.js.
--}}

<style>
    .cerca-tooltip {
        background: rgba(0, 0, 0, .75);
        border: none;
        border-radius: 6px;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        padding: 4px 8px;
        white-space: nowrap;
    }
    .cerca-tooltip::before { display: none; }
    #cerca-map { height: 520px; }
    #map-card:fullscreen #cerca-map { height: 100vh; }
    #map-card:fullscreen { border-radius: 0; }
    /* Barra do Geoman alinhada ao visual do sistema */
    .leaflet-pm-toolbar .leaflet-buttons-control-button { border-radius: 8px !important; }
</style>

<div id="map-card"
     class="relative mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

    {{-- Barra superior: busca de endereço + ações --}}
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-2.5 dark:border-zinc-800">
        <form id="form-busca" class="flex min-w-64 flex-1 items-center gap-2" onsubmit="return false">
            <div class="flex flex-1 overflow-hidden rounded-lg border border-slate-200 dark:border-zinc-700">
                <span class="flex items-center pl-2.5 text-zinc-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </span>
                <input type="text" id="input-busca" placeholder="Buscar endereço ou local…"
                       class="flex-1 bg-transparent px-2.5 py-2 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 dark:text-zinc-100">
                <button type="submit" class="border-l border-slate-200 bg-slate-50 px-3 text-xs font-medium text-zinc-600 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    Ir
                </button>
            </div>
        </form>

        <div class="flex items-center gap-1.5">
            <button type="button" id="btn-minha-loc" title="Minha localização"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                Minha localização
            </button>
            <button type="button" id="btn-centralizar" data-precisa-cerca disabled title="Centralizar na cerca"
                    class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                Centralizar
            </button>
            <button type="button" id="btn-fullscreen" title="Tela cheia"
                    class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                <svg id="icon-expand" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                </svg>
                <svg id="icon-shrink" class="hidden h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                </svg>
            </button>
        </div>
    </div>

    <div id="cerca-map"></div>

    {{-- Rodapé: medidas, avisos e ferramentas --}}
    <div class="border-t border-slate-100 dark:border-zinc-800">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Área</p>
                <p id="info-area" class="text-sm font-bold tabular-nums text-zinc-900 dark:text-zinc-100">—</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Perímetro</p>
                <p id="info-perimetro" class="text-sm font-bold tabular-nums text-zinc-900 dark:text-zinc-100">—</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">Vértices</p>
                <p id="info-vertices" class="text-sm font-bold tabular-nums text-zinc-900 dark:text-zinc-100">0</p>
            </div>

            <div class="ml-auto flex flex-wrap items-center gap-1.5">
                <button type="button" id="btn-desfazer" disabled title="Desfazer (Ctrl+Z)"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                    </svg>
                    Desfazer
                </button>
                <button type="button" id="btn-refazer" disabled title="Refazer (Ctrl+Shift+Z)"
                        class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Refazer
                </button>
                <button type="button" id="btn-limpar" data-precisa-cerca disabled
                        class="inline-flex h-8 items-center rounded-lg border border-red-200 px-2.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-40 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/30">
                    Limpar
                </button>

                <span class="mx-1 h-5 w-px bg-slate-200 dark:bg-zinc-700"></span>

                <label class="inline-flex h-8 cursor-pointer items-center rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Importar GeoJSON
                    <input type="file" id="input-importar-geojson" accept=".geojson,.json" class="hidden">
                </label>
                <button type="button" id="btn-exportar" data-precisa-cerca disabled
                        class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-medium text-zinc-700 hover:bg-slate-50 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                    Exportar
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 px-4 pb-3">
            <p id="poligono-status" class="text-xs text-zinc-600 dark:text-zinc-300">Nenhuma área desenhada</p>
            <p id="busca-retorno" class="text-xs text-zinc-400 dark:text-zinc-600"></p>
        </div>

        <p id="info-aviso" class="hidden border-t border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300"></p>

        <details class="border-t border-slate-100 px-4 py-2.5 dark:border-zinc-800">
            <summary class="cursor-pointer text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                Inserir coordenadas manualmente
            </summary>
            <form id="form-coordenadas" class="mt-2 flex flex-col gap-2 sm:flex-row" onsubmit="return false">
                <textarea id="input-coordenadas" rows="3" placeholder="-22.3756, -41.7769&#10;-22.3760, -41.7750&#10;-22.3770, -41.7760"
                          class="flex-1 rounded-lg border border-slate-200 px-3 py-2 font-mono text-xs text-zinc-900 outline-none focus:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                <button type="submit"
                        class="h-9 shrink-0 self-start rounded-lg bg-zinc-900 px-3 text-xs font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                    Aplicar
                </button>
            </form>
            <p class="mt-1 text-[10px] text-zinc-400 dark:text-zinc-600">Uma coordenada por linha, no formato latitude, longitude.</p>
        </details>
    </div>
</div>

@push('scripts')
    @vite('resources/js/cercas.js')
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            window.criarEditorCercas({
                poligonoInicial: @json($poligono ?: []),
                vizinhas: @json($vizinhas ?: []),
            });
        });
    </script>
@endpush
