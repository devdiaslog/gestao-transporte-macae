<x-layouts.app title="Mapa Geral">

    {{-- Leaflet.js CSS --}}
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css"/>
    <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

    <div class="-mx-4 -mt-4 flex flex-col overflow-hidden rounded-none border-0 bg-white sm:-mx-6 lg:-mx-8 dark:bg-zinc-900"
         style="height: calc(100vh - 4rem)">

        {{-- Cabeçalho --}}
        <div style="flex-shrink:0;"
             class="flex items-center justify-between border-b px-5 py-3.5 border-slate-200 dark:border-zinc-800">
            <div>
                <p id="mapa-geral-info" class="text-sm text-zinc-500 dark:text-zinc-400">Carregando…</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Recarregar posições --}}
                <button type="button" id="mapa-geral-btn-refresh" onclick="sincronizarERecarregar()" title="Sincronizar posições com a API"
                        class="rounded-lg p-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700
                               dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                    <svg id="mapa-geral-refresh-icon" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Busca rápida + Modo Interativo --}}
        <div style="flex-shrink:0;" class="border-b px-4 py-2 border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2">
                {{-- Input de busca --}}
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <input id="mapa-geral-search" type="text" autocomplete="off"
                           placeholder="Ir para prefixo ou placa…"
                           oninput="filtrarMapaGeral()"
                           onkeydown="if(event.key==='Enter') navegarMapaGeralPrimeiro(); if(event.key==='Escape') document.getElementById('mapa-geral-search-dropdown').classList.add('hidden');"
                           class="w-full rounded-lg border border-slate-200 bg-white py-1.5 pl-8 pr-3 text-sm outline-none
                                  placeholder:text-zinc-400 focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                                  dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-500">
                    <div id="mapa-geral-search-dropdown"
                         class="absolute left-0 right-0 top-full z-50 mt-1 hidden max-h-52 overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg
                                dark:border-zinc-700 dark:bg-zinc-800">
                    </div>
                </div>

                {{-- Modo Interativo --}}
                <select id="mi-intervalo"
                        class="rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs text-zinc-500 outline-none
                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    <option value="5000">5s</option>
                    <option value="10000">10s</option>
                    <option value="15000" selected>15s</option>
                    <option value="30000">30s</option>
                    <option value="45000">45s</option>
                    <option value="60000">60s</option>
                </select>
                <select id="mi-zoom"
                        class="rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-xs text-zinc-500 outline-none
                               focus:border-zinc-400 focus:ring-2 focus:ring-zinc-900/10
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                    @for($z = 14; $z <= 25; $z++)
                        <option value="{{ $z }}" @selected($z === 18)>zoom {{ $z }}</option>
                    @endfor
                </select>
                <button type="button" id="btn-modo-interativo" onclick="toggleModoInterativo()"
                        title="Modo Interativo — percorre todos os veículos automaticamente"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors
                               border-slate-200 bg-white text-zinc-500 hover:border-slate-300 hover:bg-slate-50
                               dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600">
                    <span id="mi-dot" class="h-2 w-2 rounded-full bg-zinc-300 dark:bg-zinc-600 transition-colors"></span>
                    <span id="mi-label">Modo Interativo</span>
                </button>
            </div>
        </div>

        {{-- Faixa de alterações recentes Vfleets --}}
        <div id="mapa-geral-recentes" style="flex-shrink:0; display:none;"
             class="border-b border-slate-200 dark:border-zinc-800 px-4 py-1.5 overflow-x-auto whitespace-nowrap">
        </div>

        {{-- Faixa de alterações recentes Elog --}}
        <div id="mapa-geral-recentes-elog" style="flex-shrink:0; display:none;"
             class="border-b border-slate-200 dark:border-zinc-800 px-4 py-1.5 overflow-x-auto whitespace-nowrap">
        </div>

        {{-- Container do mapa geral --}}
        <div id="leaflet-map-geral" style="flex:1;"></div>
    </div>

    {{-- Leaflet.js --}}
    <script src="/vendor/leaflet/leaflet.js"></script>

    <script>
    (function () {
        var _leafletMapGeral        = null;
        var _leafletLayerGeral      = null;
        var _leafletLayerCercas     = null;
        var _cercasDesenhadas       = false;
        var _mapaGeralIndex         = []; // [{prefixo, placa, lat, lng, marker}]

        var _PALETA_CERCAS = [
            '#f59e0b','#3b82f6','#10b981','#ef4444','#8b5cf6',
            '#f97316','#06b6d4','#ec4899','#84cc16','#6366f1',
            '#14b8a6','#e11d48',
        ];

        var _cercasData = []; // [{nome, atividade, poligono}] — preenchido na 1ª carga

        function _escHtml(str) {
            return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        /** Ray casting — retorna true se [lat, lng] está dentro do polígono [[lat,lng],...] */
        function _pontoDentro(lat, lng, poligono) {
            var dentro = false;
            var n = poligono.length;
            for (var i = 0, j = n - 1; i < n; j = i++) {
                var xi = poligono[i][0], yi = poligono[i][1];
                var xj = poligono[j][0], yj = poligono[j][1];
                if (((yi > lng) !== (yj > lng)) && (lat < (xj - xi) * (lng - yi) / (yj - yi) + xi)) {
                    dentro = !dentro;
                }
            }
            return dentro;
        }

        /** Distância Haversine em km entre dois pontos */
        function _haversineKm(lat1, lng1, lat2, lng2) {
            var R = 6371, dLat = (lat2 - lat1) * Math.PI / 180, dLng = (lng2 - lng1) * Math.PI / 180;
            var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)*Math.sin(dLng/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        /** Centroide do polígono */
        function _centroide(poligono) {
            var n = poligono.length, sumLat = 0, sumLng = 0;
            poligono.forEach(function (p) { sumLat += p[0]; sumLng += p[1]; });
            return [sumLat / n, sumLng / n];
        }

        /** Retorna {dentroDeAlguma, nome, atividade, distKm} para a cerca mais relevante */
        function _cercaParaVeiculo(lat, lng) {
            if (!_cercasData.length) { return null; }
            var melhor = null, melhorDist = Infinity;
            for (var i = 0; i < _cercasData.length; i++) {
                var c = _cercasData[i];
                if (_pontoDentro(lat, lng, c.poligono)) {
                    return { dentro: true, nome: c.nome, atividade: c.atividade, distKm: 0 };
                }
                var centro = _centroide(c.poligono);
                var dist = _haversineKm(lat, lng, centro[0], centro[1]);
                if (dist < melhorDist) { melhorDist = dist; melhor = c; }
            }
            return melhor ? { dentro: false, nome: melhor.nome, atividade: melhor.atividade, distKm: melhorDist } : null;
        }

        window.filtrarMapaGeral = function () {
            var q        = (document.getElementById('mapa-geral-search').value || '').trim().toLowerCase();
            var dropdown = document.getElementById('mapa-geral-search-dropdown');

            if (! q) { dropdown.classList.add('hidden'); return; }

            var matches = _mapaGeralIndex.filter(function (v) {
                return v.prefixo.includes(q) || v.placa.includes(q);
            });

            if (matches.length === 0) {
                dropdown.innerHTML = '<div class="px-3 py-2.5 text-xs text-zinc-400 dark:text-zinc-500">Nenhum veículo encontrado</div>';
                dropdown.classList.remove('hidden');
                return;
            }

            dropdown.innerHTML = matches.slice(0, 12).map(function (v) {
                return '<div class="cursor-pointer px-3 py-2 text-sm text-zinc-800 hover:bg-slate-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60"'
                    + ' onmousedown="event.preventDefault(); navegarMapaGeral(' + JSON.stringify(v.label) + ')">'
                    + '<span class="font-semibold">' + _escHtml(v.label) + '</span>'
                    + (v.placa_orig && v.placa_orig !== v.label ? '<span class="ml-2 text-xs text-zinc-400 dark:text-zinc-500">— ' + _escHtml(v.placa_orig) + '</span>' : '')
                    + '</div>';
            }).join('');

            dropdown.classList.remove('hidden');
        };

        window.navegarMapaGeral = function (label) {
            var v = _mapaGeralIndex.find(function (v) { return v.label === label; });
            if (! v || ! _leafletMapGeral) { return; }

            document.getElementById('mapa-geral-search').value = label;
            document.getElementById('mapa-geral-search-dropdown').classList.add('hidden');

            _leafletMapGeral.flyTo([v.lat, v.lng], 17, { duration: 0.7 });
            setTimeout(function () { v.marker.openPopup(); }, 750);
        };

        window.navegarMapaGeralPrimeiro = function () {
            var q = (document.getElementById('mapa-geral-search').value || '').trim().toLowerCase();
            if (! q) { return; }
            var v = _mapaGeralIndex.find(function (v) { return v.prefixo.includes(q) || v.placa.includes(q); });
            if (v) { navegarMapaGeral(v.label); }
        };

        document.addEventListener('click', function (e) {
            var wrapper = document.getElementById('mapa-geral-search');
            var drop    = document.getElementById('mapa-geral-search-dropdown');
            if (wrapper && drop && ! wrapper.contains(e.target) && ! drop.contains(e.target)) {
                drop.classList.add('hidden');
            }
        });

        // ─── Modo Interativo ─────────────────────────────────────────────────────
        var _miAtivo         = false;
        var _miTimer         = null;
        var _miRefreshTimer  = null;
        var _miOrdem         = []; // índice ordenado por proximidade
        var _miIdx           = 0;
        var _MI_REFRESH_MS   = 5 * 60 * 1000; // 5 minutos

        function _nearestNeighborSort(veiculos) {
            if (veiculos.length === 0) { return []; }
            var restantes = veiculos.slice();
            var resultado = [restantes.splice(0, 1)[0]];
            while (restantes.length > 0) {
                var atual      = resultado[resultado.length - 1];
                var menorDist  = Infinity;
                var menorIdx   = 0;
                for (var i = 0; i < restantes.length; i++) {
                    var d = _haversineKm(atual.lat, atual.lng, restantes[i].lat, restantes[i].lng);
                    if (d < menorDist) { menorDist = d; menorIdx = i; }
                }
                resultado.push(restantes.splice(menorIdx, 1)[0]);
            }
            return resultado;
        }

        function _miAvancar() {
            if (! _miAtivo || _miOrdem.length === 0) { return; }
            var v = _miOrdem[_miIdx];
            _miIdx = (_miIdx + 1) % _miOrdem.length;

            // Atualiza contador no botão
            document.getElementById('mi-label').textContent =
                'Interativo (' + (_miIdx) + '/' + _miOrdem.length + ')';

            var zoom = parseInt(document.getElementById('mi-zoom').value, 10) || 18;
            _leafletMapGeral.flyTo([v.lat, v.lng], zoom, { duration: 0.8 });
            setTimeout(function () {
                if (_miAtivo) { v.marker.openPopup(); }
            }, 850);

            var pausa = parseInt(document.getElementById('mi-intervalo').value, 10) || 15000;
            _miTimer = setTimeout(_miAvancar, pausa);
        }

        window.toggleModoInterativo = function () {
            _miAtivo = ! _miAtivo;
            var dot   = document.getElementById('mi-dot');
            var label = document.getElementById('mi-label');
            var btn   = document.getElementById('btn-modo-interativo');

            var select     = document.getElementById('mi-intervalo');
            var selectZoom = document.getElementById('mi-zoom');

            if (_miAtivo) {
                // Ordena por proximidade e inicia
                _miOrdem             = _nearestNeighborSort(_mapaGeralIndex.slice());
                _miIdx               = 0;
                select.disabled      = true;
                selectZoom.disabled  = true;
                dot.style.background  = '#16a34a';
                dot.style.animation   = 'spin 2s linear infinite';
                btn.style.borderColor = '#16a34a';
                btn.style.color       = '#16a34a';
                _miAvancar();
                // Recarrega posições do banco a cada 6 minutos sem parar o loop
                _miRefreshTimer = setInterval(function () {
                    if (! _miAtivo) { return; }
                    _fetchEPlotarMarcadores().then(function () {
                        // Re-ordena mantendo o mesmo ponto de referência
                        _miOrdem = _nearestNeighborSort(_mapaGeralIndex.slice());
                        _miIdx   = _miIdx % (_miOrdem.length || 1);
                    });
                }, _MI_REFRESH_MS);
            } else {
                clearTimeout(_miTimer);
                clearInterval(_miRefreshTimer);
                _miTimer        = null;
                _miRefreshTimer = null;
                select.disabled     = false;
                selectZoom.disabled = false;
                dot.style.background  = '';
                dot.style.animation   = '';
                btn.style.borderColor = '';
                btn.style.color       = '';
                label.textContent     = 'Modo Interativo';
                if (_leafletMapGeral) { _leafletMapGeral.closePopup(); }
            }
        };

        var _mapaGeralUrl         = '{{ route("control-tower.mapa-geral") }}';
        var _sincronizarUrl       = '{{ route("control-tower.sincronizar-posicoes") }}';
        var _sincronizarStatusUrl = '{{ route("control-tower.sincronizar-status-operacional") }}';
        var _csrfToken            = '{{ csrf_token() }}';
        var _sincStatusEmAndamento = false;

        function _fetchEPlotarMarcadores() {
            var info = document.getElementById('mapa-geral-info');
            info.textContent = 'Atualizando mapa…';

            return fetch(_mapaGeralUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var veiculos = data.veiculos || [];

                    // Inicializa o mapa na primeira chamada
                    if (!_leafletMapGeral) {
                        _leafletMapGeral = L.map('leaflet-map-geral').setView([-22.409748797155576, -41.86951960989981], 12);

                        var layerRua = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            maxZoom: 21,
                            maxNativeZoom: 19
                        });
                        var layerSatelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri',
                            maxZoom: 21,
                            maxNativeZoom: 19
                        });
                        layerRua.addTo(_leafletMapGeral);
                        _leafletLayerCercas = L.layerGroup().addTo(_leafletMapGeral);
                        _leafletLayerGeral  = L.layerGroup().addTo(_leafletMapGeral);
                        L.control.layers(
                            { 'Mapa': layerRua, 'Satélite': layerSatelite },
                            { 'Cercas': _leafletLayerCercas },
                            { position: 'topright' }
                        ).addTo(_leafletMapGeral);
                    }

                    // Guarda cercas para uso no popup dos veículos
                    if (!_cercasDesenhadas && data.cercas && data.cercas.length) {
                        _cercasData = data.cercas;
                    }

                    // Desenha cercas apenas uma vez (não mudam em tempo real)
                    if (!_cercasDesenhadas && data.cercas && data.cercas.length) {
                        _cercasDesenhadas = true;
                        data.cercas.forEach(function (c, i) {
                            var cor = _PALETA_CERCAS[i % _PALETA_CERCAS.length];
                            L.polygon(c.poligono, {
                                color: cor,
                                weight: 2,
                                fillColor: cor,
                                fillOpacity: 0.12,
                            })
                            .bindTooltip(
                                '<strong>' + c.nome + '</strong>' + (c.atividade ? '<br>' + c.atividade : ''),
                                { sticky: true, direction: 'top' }
                            )
                            .addTo(_leafletLayerCercas);
                        });
                    }

                    // Limpa marcadores e índice anteriores
                    _leafletLayerGeral.clearLayers();
                    _mapaGeralIndex = [];

                    var bounds = [];

                    veiculos.forEach(function (v) {
                        // ── Cor do ícone ─────────────────────────────────────────
                        var bgColor = v.sem_sinal ? '#52525b'
                                    : (v.tracker_state === 'Em Movimento' ? '#16a34a'
                                    : (v.tracker_state === 'Parado'       ? '#b91c1c'
                                    : '#3f3f46'));

                        var icon = L.divIcon({
                            html: '<div style="width:60px;display:flex;justify-content:center;align-items:center;">'
                                  + '<span style="background:' + bgColor + ';color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;white-space:nowrap;box-shadow:0 1px 4px rgba(0,0,0,.4)">'
                                  + _escHtml(v.prefixo || v.placa) + '</span></div>',
                            className: '',
                            iconSize: [60, 22],
                            iconAnchor: [30, 11]
                        });

                        // ── Localidade: cerca ou endereço ────────────────────────
                        var _PROX_KM  = 0.3;
                        var cercaInfo = _cercaParaVeiculo(v.lat, v.lng);
                        var usarCerca = cercaInfo && (cercaInfo.dentro || cercaInfo.distKm <= _PROX_KM);

                        // ── Popup (tabela) ────────────────────────────────────────
                        var _row = function (lbl, val) {
                            return '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">' + lbl + '</td>'
                                 + '<td style="font-weight:600">' + val + '</td></tr>';
                        };
                        var _hr = '<tr><td colspan="2" style="padding:3px 0"><hr style="border:none;border-top:1px solid #e5e7eb;margin:0"></td></tr>';

                        var popup = '<div style="min-width:500px;font-size:22px;line-height:1.6">'
                            + '<p style="font-weight:600;font-size:24px;margin:0 0 12px">'
                            + _escHtml(v.prefixo) + ' <span style="font-weight:400;color:#71717a">' + _escHtml(v.placa) + '</span></p>'
                            + '<table style="border-collapse:collapse;width:100%">';

                        // Status Elog
                        if (v.status_elog) {
                            var elogVal = _escHtml(v.status_elog);
                            if (v.tempo_elog) { elogVal += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.tempo_elog) + '</span>'; }
                            popup += _row('Status Elog', elogVal);
                        }
                        if (v.atendimento) {
                            var atendVal = _escHtml(v.atendimento);
                            if (v.tempo_atendimento) { atendVal += ' <span style="color:#6b7280;font-weight:400">(' + _escHtml(v.tempo_atendimento) + ' total)</span>'; }
                            popup += _row('Atendimento', atendVal);
                        }
                        if (v.observacao) {
                            var obs = v.observacao.length > 100 ? v.observacao.substring(0, 100) + '…' : v.observacao;
                            popup += _row('Observação', '<span title="' + v.observacao.replace(/"/g, '&quot;') + '">' + _escHtml(obs) + '</span>');
                        }

                        popup += _hr;

                        // Rastreador
                        if (v.sem_sinal) {
                            var semSinalVal = '⬛ <span style="font-weight:600">Desconhecido</span>';
                            if (v.sem_sinal_duration) { semSinalVal += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.sem_sinal_duration) + '</span>'; }
                            popup += _row('Rastreador', semSinalVal);
                        } else {
                            var trackerIcon  = v.tracker_state === 'Em Movimento' ? '🟢' : (v.tracker_state === 'Parado' ? '🔴' : '⚫');
                            var trackerLabel = trackerIcon + ' ' + (v.tracker_state || 'Sem Sinal');
                            if (v.state_duration) { trackerLabel += ' <span style="color:#6b7280;font-weight:400">há ' + _escHtml(v.state_duration) + '</span>'; }
                            popup += _row('Rastreador', trackerLabel);
                            popup += _row('Motor', v.ignition ? '🔵 <span style="font-weight:600">Ligado</span>' : '⚪ <span style="font-weight:600">Desligado</span>');
                            popup += _row('Velocidade', (v.speed || 0) + ' km/h');
                        }

                        // Cerca
                        if (usarCerca) {
                            popup += _hr;
                            popup += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Cerca</td>'
                                   + '<td style="font-weight:600;white-space:nowrap">' + _escHtml(cercaInfo.nome)
                                   + '</td></tr>';
                            if (v.tempo_cerca_duracao) {
                                var cercaBg = v.cerca_bar_color || '#6b7280';
                                popup += '<tr><td style="color:#6b7280;padding:2px 16px 2px 0;white-space:nowrap">Tempo Cerca</td>'
                                       + '<td><div style="background:' + cercaBg + ';padding:4px 16px;color:#fff;font-size:21px;font-weight:600;display:inline-block">'
                                       + _escHtml(v.tempo_cerca_duracao) + '</div></td></tr>';
                            }
                        } else {
                            popup += _hr;
                            popup += '<tr><td colspan="2"><p style="margin:0">📍 <span id="loc-' + _escHtml(v.placa) + '" style="color:#9ca3af">Carregando endereço…</span></p></td></tr>';
                        }

                        // Condutor
                        if (v.motorista) {
                            popup += _hr;
                            popup += _row('Condutor', _escHtml(v.motorista));
                        }

                        // Rodapé com posição
                        if (!v.sem_sinal && v.position_at) {
                            popup += '<tr><td colspan="2" style="color:#9ca3af;font-size:11px;padding-top:5px">Posição: ' + _escHtml(v.position_at) + '</td></tr>';
                        }

                        popup += '</table></div>';

                        var marker = L.marker([v.lat, v.lng], { icon: icon })
                            .bindPopup(popup, { maxWidth: 600 })
                            .addTo(_leafletLayerGeral);

                        // Nominatim apenas para veículos fora do raio de qualquer cerca
                        if (!usarCerca) {
                            (function (placa, lat, lng) {
                                marker.on('popupopen', function () {
                                    var el = document.getElementById('loc-' + placa);
                                    if (!el || el.dataset.loaded) { return; }
                                    el.dataset.loaded = '1';
                                    fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&accept-language=pt-BR', {
                                        headers: { 'Accept': 'application/json' }
                                    })
                                    .then(function (r) { return r.json(); })
                                    .then(function (d) {
                                        if (!el) { return; }
                                        var road = d.address && (d.address.road || d.address.suburb || d.address.neighbourhood || d.address.city_district || d.address.town || d.address.city);
                                        el.textContent = road || d.display_name || 'Endereço não encontrado';
                                        el.style.color = '';
                                    })
                                    .catch(function () {
                                        if (el) { el.textContent = 'Endereço indisponível'; }
                                    });
                                });
                            }(v.placa, v.lat, v.lng));
                        }

                        _mapaGeralIndex.push({
                            prefixo: (v.prefixo || '').toLowerCase(),
                            placa:   (v.placa   || '').toLowerCase(),
                            label:   v.prefixo || v.placa,
                            placa_orig: v.placa || '',
                            lat: v.lat, lng: v.lng,
                            marker: marker,
                        });

                        bounds.push([v.lat, v.lng]);
                    });

                    if (bounds.length > 0) {
                        _leafletMapGeral.fitBounds(bounds, { padding: [40, 40] });
                    }

                    info.textContent = veiculos.length + ' veículo(s) com posição registrada';
                    setTimeout(function () { _leafletMapGeral.invalidateSize(); }, 200);

                    // Faixa de alterações recentes (últimos 15 min)
                    var recentes = veiculos
                        .filter(function (v) { return v.state_since_mins !== null && v.state_since_mins <= 60; })
                        .sort(function (a, b) { return a.state_since_mins - b.state_since_mins; });

                    var faixaEl = document.getElementById('mapa-geral-recentes');
                    if (recentes.length > 0) {
                        var pills = '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#a1a1aa;margin-right:6px;vertical-align:middle">⚡ Recentes Vfleets:</span>';
                        recentes.forEach(function (v) {
                            var emoji = v.tracker_state === 'Em Movimento' ? '🟢' : (v.tracker_state === 'Parado' ? '🔴' : '⚫');
                            var dur   = v.state_since_mins < 60
                                ? v.state_since_mins + 'm'
                                : Math.floor(v.state_since_mins / 60) + 'h ' + (v.state_since_mins % 60) + 'm';
                            var bg    = v.tracker_state === 'Em Movimento' ? 'background:#f0fdf4;color:#15803d;box-shadow:inset 0 0 0 1px #bbf7d0'
                                      : (v.tracker_state === 'Parado'      ? 'background:#fff1f2;color:#be123c;box-shadow:inset 0 0 0 1px #fecdd3'
                                      :                                       'background:#f4f4f5;color:#52525b;box-shadow:inset 0 0 0 1px #e4e4e7');
                            pills += '<span style="display:inline-flex;align-items:center;gap:5px;border-radius:9999px;padding:3px 10px;font-size:11px;font-weight:500;margin-right:6px;vertical-align:middle;cursor:pointer;' + bg + '"'
                                + ' onclick="navegarMapaGeral(' + JSON.stringify(v.prefixo || v.placa) + ')"'
                                + ' title="' + _escHtml(v.tracker_state) + ' — há ' + dur + '">'
                                + emoji + ' <strong>' + _escHtml(v.prefixo || v.placa) + '</strong>'
                                + ' <span style="opacity:.6">há ' + dur + '</span>'
                                + '</span>';
                        });
                        faixaEl.innerHTML   = pills;
                        faixaEl.style.display = '';
                    } else {
                        faixaEl.style.display = 'none';
                        faixaEl.innerHTML     = '';
                    }

                    // Faixa de recentes Elog (última hora)
                    var recentesElog = data.recentes_elog || [];
                    var faixaElog    = document.getElementById('mapa-geral-recentes-elog');
                    if (recentesElog.length > 0) {
                        var pillsElog = '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#a1a1aa;margin-right:6px;vertical-align:middle">📋 Recentes Elog:</span>';
                        recentesElog.forEach(function (v) {
                            var mins = v.entrada_em || 0;
                            var dur  = mins < 60
                                ? mins + 'm'
                                : Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm';
                            var bg   = v.cor ? v.cor : '#f4f4f5';
                            var fg   = v.cor ? '#fff' : '#18181b';
                            pillsElog += '<span style="display:inline-flex;align-items:center;gap:4px;margin-right:6px;padding:2px 8px;border-radius:4px;background:' + bg + ';color:' + fg + ';font-size:11px;cursor:default;vertical-align:middle"'
                                + ' title="' + _escHtml(v.status_operacional) + (v.documento ? ' — Doc: ' + _escHtml(v.documento) : '') + ' — há ' + dur + '">'
                                + '<strong>' + _escHtml(v.prefixo || v.placa) + '</strong>'
                                + ' <span style="opacity:.85">' + _escHtml(v.status_operacional) + '</span>'
                                + ' <span style="opacity:.7">há ' + dur + '</span>'
                                + '</span>';
                        });
                        faixaElog.innerHTML     = pillsElog;
                        faixaElog.style.display = '';
                    } else {
                        faixaElog.style.display = 'none';
                        faixaElog.innerHTML     = '';
                    }
                })
                .catch(function () {
                    document.getElementById('mapa-geral-info').textContent = 'Erro ao carregar posições.';
                });
        }

        window.sincronizarERecarregar = function () {
            var btn  = document.getElementById('mapa-geral-btn-refresh');
            var icon = document.getElementById('mapa-geral-refresh-icon');
            var info = document.getElementById('mapa-geral-info');
            btn.disabled         = true;
            icon.style.animation = 'spin 1s linear infinite';
            info.textContent     = 'Sincronizando com a API…';

            // Sincroniza status operacional em paralelo (fire-and-forget)
            if (!_sincStatusEmAndamento) {
                _sincStatusEmAndamento = true;
                fetch(_sincronizarStatusUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': _csrfToken,
                        'Content-Type': 'application/json'
                    }
                })
                .catch(function () {})
                .finally(function () { _sincStatusEmAndamento = false; });
            }

            fetch(_sincronizarUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': _csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(function (response) {
                if (response.status === 429) {
                    info.textContent = 'Limite da API atingido — exibindo última sincronização salva.';
                    return _fetchEPlotarMarcadores();
                }
                return response.json().then(function (data) {
                    if (!data.ok) {
                        info.textContent = 'Limite da API atingido — exibindo última sincronização salva.';
                        return _fetchEPlotarMarcadores();
                    }
                    info.textContent = 'Sincronizados ' + data.total + ' veículo(s). Atualizando mapa…';
                    return _fetchEPlotarMarcadores();
                });
            })
            .catch(function () {
                info.textContent = 'Erro ao sincronizar — exibindo última sincronização salva.';
                _fetchEPlotarMarcadores();
            })
            .finally(function () {
                btn.disabled         = false;
                icon.style.animation = '';
            });
        };

        // Carrega o mapa automaticamente ao abrir a página
        document.getElementById('mapa-geral-search').value = '';
        sincronizarERecarregar();
    })();
    </script>

</x-layouts.app>
