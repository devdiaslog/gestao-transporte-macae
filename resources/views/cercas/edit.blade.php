<x-layouts.app title="Editar Cerca">

    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css"/>

    {{-- Page header --}}
    <div class="mt-4 flex items-center gap-4">
        <a href="{{ route('cercas.index') }}"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200
                  text-slate-500 transition-all duration-200
                  hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700
                  dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            <span class="sr-only">Voltar</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">Editar Cerca</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $cerca->nome }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('cercas.update', $cerca) }}" id="cerca-form" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="poligono" id="poligono-input" value="{{ $cerca->poligono ? json_encode($cerca->poligono) : '' }}">

        {{-- ─── Dados básicos ───────────────────────────────────────────────── --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Dados da Cerca</h3>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Nome --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label for="nome" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Nome <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="nome"
                        type="text"
                        name="nome"
                        value="{{ old('nome', $cerca->nome) }}"
                        autofocus
                        placeholder="Ex: Parque de Tubos - Macaé"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                               placeholder:text-zinc-400 focus:ring-2
                               {{ $errors->has('nome')
                                   ? 'border-red-400 bg-white text-zinc-900 focus:border-red-500 focus:ring-red-500/20'
                                   : 'border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10' }}"
                    />
                    @error('nome')
                        <p class="flex items-center gap-1.5 text-xs text-red-500">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Atividade --}}
                <div class="space-y-1.5">
                    <label for="atividade" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Atividade
                    </label>
                    <input
                        id="atividade"
                        type="text"
                        name="atividade"
                        value="{{ old('atividade', $cerca->atividade) }}"
                        placeholder="Ex: Carga/Descarga"
                        class="block w-full rounded-lg border px-3.5 py-2.5 text-sm shadow-xs outline-none transition-all duration-200
                               placeholder:text-zinc-400 focus:ring-2
                               border-slate-300 bg-white text-zinc-900 focus:border-zinc-900 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-zinc-400 dark:focus:ring-zinc-400/10"
                    />
                </div>

                {{-- Status --}}
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-6 py-1">
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="status" value="1"
                                   @checked(old('status', $cerca->status ? '1' : '0') === '1')
                                   class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Ativa</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <input type="radio" name="status" value="0"
                                   @checked(old('status', $cerca->status ? '1' : '0') === '0')
                                   class="h-4 w-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900/20 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Inativa</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Mapa / Polígono ─────────────────────────────────────────────── --}}
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-zinc-800">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-600">Polígono no Mapa</h3>
                    <p id="poligono-status" class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-600">Nenhum vértice adicionado</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btn-minha-loc"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all
                                   border-slate-200 text-zinc-600 hover:border-slate-300 hover:bg-slate-50
                                   dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                        Minha localização
                    </button>
                    <button type="button" id="btn-desfazer"
                            disabled
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all disabled:opacity-40
                                   border-slate-200 text-zinc-600 hover:border-slate-300 hover:bg-slate-50
                                   dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                        Desfazer
                    </button>
                    <button type="button" id="btn-limpar"
                            disabled
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all disabled:opacity-40
                                   border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50
                                   dark:border-red-900/50 dark:text-red-400 dark:hover:border-red-800 dark:hover:bg-red-950/40">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        Limpar
                    </button>
                </div>
            </div>

            {{-- Instrução --}}
            <div id="map-instrucao" class="flex items-center gap-2 border-b border-slate-100 bg-blue-50 px-6 py-2.5 text-xs text-blue-700 dark:border-zinc-800 dark:bg-blue-950/30 dark:text-blue-400">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <span>Clique no mapa para adicionar vértices. Use "Limpar" para redesenhar o polígono do zero.</span>
            </div>

            {{-- Mapa --}}
            <div id="cerca-map" style="height: 420px; border-radius: 0 0 1rem 1rem;"></div>
        </div>

        {{-- ─── Ações ──────────────────────────────────────────────────────── --}}
        <div class="mt-6 flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <a href="{{ route('cercas.index') }}"
               class="inline-flex items-center rounded-lg border px-4 py-2.5 text-sm font-medium transition-all
                      border-slate-200 text-zinc-700 hover:border-slate-300 hover:bg-slate-50
                      dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-800">
                Cancelar
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all
                           hover:bg-zinc-700 active:scale-[0.98]
                           dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Salvar Alterações
            </button>
        </div>
    </form>

    <script src="/vendor/leaflet/leaflet.js"></script>
    <script>
    (function () {
        var MACAE        = [-22.3756, -41.7769];
        var poligonoExistente = @json($cerca->poligono ?? []);

        var map = L.map('cerca-map').setView(MACAE, 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 19,
        }).addTo(map);

        var vertices    = [];
        var markers     = [];
        var polygon     = null;

        var inputPoligono = document.getElementById('poligono-input');
        var statusEl      = document.getElementById('poligono-status');
        var btnDesfazer   = document.getElementById('btn-desfazer');
        var btnLimpar     = document.getElementById('btn-limpar');

        function atualizarStatus() {
            var n = vertices.length;
            if (n === 0) {
                statusEl.textContent = 'Nenhum vértice adicionado';
            } else if (n === 1) {
                statusEl.textContent = '1 vértice — adicione mais 2 para fechar o polígono';
            } else if (n === 2) {
                statusEl.textContent = '2 vértices — adicione mais 1 para fechar o polígono';
            } else {
                statusEl.textContent = n + ' vértices — polígono definido ✓';
            }
            btnDesfazer.disabled = n === 0;
            btnLimpar.disabled   = n === 0;
            inputPoligono.value  = n >= 3 ? JSON.stringify(vertices) : '';
        }

        function redesenharPoligono() {
            if (polygon) { map.removeLayer(polygon); polygon = null; }
            if (vertices.length >= 3) {
                polygon = L.polygon(vertices, {
                    color: '#3b82f6',
                    weight: 2,
                    fillColor: '#3b82f6',
                    fillOpacity: 0.15,
                }).addTo(map);
            }
        }

        function adicionarVertice(lat, lng) {
            vertices.push([lat, lng]);
            var m = L.circleMarker([lat, lng], {
                radius: 6,
                color: '#1d4ed8',
                weight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.9,
            }).addTo(map);
            markers.push(m);
            redesenharPoligono();
            atualizarStatus();
        }

        map.on('click', function (e) {
            adicionarVertice(e.latlng.lat, e.latlng.lng);
        });

        btnDesfazer.addEventListener('click', function () {
            if (markers.length === 0) { return; }
            map.removeLayer(markers.pop());
            vertices.pop();
            redesenharPoligono();
            atualizarStatus();
        });

        btnLimpar.addEventListener('click', function () {
            markers.forEach(function (m) { map.removeLayer(m); });
            markers   = [];
            vertices  = [];
            if (polygon) { map.removeLayer(polygon); polygon = null; }
            atualizarStatus();
        });

        document.getElementById('btn-minha-loc').addEventListener('click', function () {
            if (! navigator.geolocation) { return; }
            navigator.geolocation.getCurrentPosition(function (pos) {
                map.flyTo([pos.coords.latitude, pos.coords.longitude], 15, { duration: 1 });
            });
        });

        // Carregar polígono existente (ou o do old() em caso de erro)
        var fonte = null;
        @if(old('poligono'))
        try { fonte = JSON.parse('{{ old('poligono') }}'); } catch (e) {}
        @else
        fonte = poligonoExistente;
        @endif

        if (Array.isArray(fonte) && fonte.length > 0) {
            fonte.forEach(function (pt) { adicionarVertice(pt[0], pt[1]); });
            if (polygon) {
                map.fitBounds(polygon.getBounds().pad(0.3));
            }
        }

        atualizarStatus();
    })();
    </script>

</x-layouts.app>
