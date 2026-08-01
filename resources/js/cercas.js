/**
 * Editor de cercas geográficas.
 *
 * Substitui o desenho manual por Leaflet-Geoman (vértices intermediários,
 * arraste, remoção pontual, snap) e usa Turf para as medidas e validações
 * geométricas. Usado nas telas de criação e edição de cerca.
 */

import L from 'leaflet';
import '@geoman-io/leaflet-geoman-free';
import 'leaflet/dist/leaflet.css';
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css';

import area from '@turf/area';
import length from '@turf/length';
import kinks from '@turf/kinks';
import booleanIntersects from '@turf/boolean-intersects';

const MACAE = [-22.3756, -41.7769];

const PALETA = [
    '#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#f97316',
    '#06b6d4', '#ec4899', '#84cc16', '#6366f1', '#14b8a6', '#e11d48',
];

/** [lat,lng][] → GeoJSON Polygon (anel fechado, ordem [lng,lat]). */
function paraGeoJSON(vertices) {
    if (!vertices || vertices.length < 3) {
        return null;
    }

    const anel = vertices.map(([lat, lng]) => [lng, lat]);
    const primeiro = anel[0];
    const ultimo = anel[anel.length - 1];

    if (primeiro[0] !== ultimo[0] || primeiro[1] !== ultimo[1]) {
        anel.push([...primeiro]);
    }

    return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [anel] } };
}

function formatarArea(m2) {
    if (m2 >= 1_000_000) {
        return (m2 / 1_000_000).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + ' km²';
    }
    if (m2 >= 10_000) {
        return (m2 / 10_000).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + ' ha';
    }
    return Math.round(m2).toLocaleString('pt-BR') + ' m²';
}

function formatarDistancia(km) {
    return km >= 1
        ? km.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + ' km'
        : Math.round(km * 1000).toLocaleString('pt-BR') + ' m';
}

export function criarEditorCercas(opcoes) {
    const {
        elementoMapa = 'cerca-map',
        inputPoligono = 'poligono-input',
        poligonoInicial = [],
        vizinhas = [],
    } = opcoes || {};

    const input = document.getElementById(inputPoligono);
    const map = L.map(elementoMapa, { zoomControl: true }).setView(MACAE, 13);

    // ── Camadas base ────────────────────────────────────────────────────────
    const ruas = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 21,
        maxNativeZoom: 19,
    });
    const satelite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 21,
        maxNativeZoom: 19,
    });
    satelite.addTo(map);

    // ── Cercas vizinhas (referência + alvo de snap) ─────────────────────────
    const camadaVizinhas = L.layerGroup().addTo(map);
    const geoVizinhas = [];

    vizinhas.forEach((c, i) => {
        if (!Array.isArray(c.poligono) || c.poligono.length < 3) {
            return;
        }

        const cor = PALETA[i % PALETA.length];
        const poly = L.polygon(c.poligono, {
            color: cor,
            weight: 2,
            fillColor: cor,
            fillOpacity: 0.15,
            interactive: true,
            pmIgnore: true, // não editável, mas continua servindo de snap
        }).addTo(camadaVizinhas);

        poly.bindTooltip(c.nome + (c.atividade ? ' — ' + c.atividade : ''), {
            direction: 'center',
            className: 'cerca-tooltip',
        });

        const geo = paraGeoJSON(c.poligono);
        if (geo) {
            geoVizinhas.push({ nome: c.nome, geo });
        }
    });

    L.control.layers(
        { 'Satélite': satelite, 'Mapa': ruas },
        { 'Cercas existentes': camadaVizinhas },
        { position: 'topright' }
    ).addTo(map);

    // ── Estado ──────────────────────────────────────────────────────────────
    let cerca = null;          // L.Polygon em edição
    const historico = [];      // pilha de undo (vértices)
    const refeitos = [];       // pilha de redo

    const el = {
        area: document.getElementById('info-area'),
        perimetro: document.getElementById('info-perimetro'),
        vertices: document.getElementById('info-vertices'),
        aviso: document.getElementById('info-aviso'),
        status: document.getElementById('poligono-status'),
    };

    function verticesAtuais() {
        if (!cerca) {
            return [];
        }
        const anel = cerca.getLatLngs()[0] || [];
        return anel.map((p) => [p.lat, p.lng]);
    }

    /** Publica o polígono no input e atualiza medidas e avisos. */
    function sincronizar({ salvarHistorico = true } = {}) {
        const vertices = verticesAtuais();

        if (salvarHistorico) {
            historico.push(JSON.stringify(vertices));
            refeitos.length = 0;
        }

        input.value = vertices.length >= 3 ? JSON.stringify(vertices) : '';

        const geo = paraGeoJSON(vertices);
        const avisos = [];

        if (geo) {
            const m2 = area(geo);
            const km = length(geo, { units: 'kilometers' });

            if (el.area) el.area.textContent = formatarArea(m2);
            if (el.perimetro) el.perimetro.textContent = formatarDistancia(km);

            // Auto-interseção: polígono "borboleta" — geometria inválida.
            if (kinks(geo).features.length > 0) {
                avisos.push('As linhas do polígono se cruzam. Ajuste os vértices para formar uma área simples.');
            }

            const sobrepostas = geoVizinhas
                .filter((v) => booleanIntersects(geo, v.geo))
                .map((v) => v.nome);

            if (sobrepostas.length) {
                avisos.push('Sobrepõe: ' + sobrepostas.join(', ') + '.');
            }
        } else {
            if (el.area) el.area.textContent = '—';
            if (el.perimetro) el.perimetro.textContent = '—';
        }

        if (el.vertices) {
            el.vertices.textContent = vertices.length;
        }

        if (el.status) {
            el.status.textContent = vertices.length === 0
                ? 'Nenhuma área desenhada'
                : vertices.length < 3
                    ? 'Área incompleta — mínimo de 3 vértices'
                    : 'Área definida ✓';
        }

        if (el.aviso) {
            el.aviso.textContent = avisos.join(' ');
            el.aviso.classList.toggle('hidden', avisos.length === 0);
        }

        atualizarBotoes();
    }

    function atualizarBotoes() {
        const temCerca = !!cerca;
        document.querySelectorAll('[data-precisa-cerca]').forEach((b) => {
            b.disabled = !temCerca;
        });

        const undo = document.getElementById('btn-desfazer');
        const redo = document.getElementById('btn-refazer');
        if (undo) undo.disabled = historico.length <= 1;
        if (redo) redo.disabled = refeitos.length === 0;
    }

    /** Adota um polígono como a cerca em edição (só existe um por vez). */
    function definirCerca(layer, { salvarHistorico = true } = {}) {
        if (cerca && cerca !== layer) {
            cerca.remove();
        }

        cerca = layer;
        cerca.setStyle({ color: '#000', weight: 2.5, fillColor: '#000', fillOpacity: 0.2 });

        // Qualquer edição (arrastar vértice, mover, cortar) republica o valor.
        cerca.on('pm:edit pm:dragend pm:markerdragend pm:vertexremoved pm:vertexadded', () => sincronizar());

        sincronizar({ salvarHistorico });
    }

    function desenharVertices(vertices, { ajustarZoom = true, salvarHistorico = true } = {}) {
        if (!Array.isArray(vertices) || vertices.length < 3) {
            return;
        }

        definirCerca(L.polygon(vertices).addTo(map), { salvarHistorico });

        if (ajustarZoom) {
            map.fitBounds(cerca.getBounds().pad(0.25));
        }
    }

    // ── Geoman: barra de ferramentas e opções ───────────────────────────────
    map.pm.setLang('pt_br');

    map.pm.addControls({
        position: 'topleft',
        drawMarker: false,
        drawCircleMarker: false,
        drawPolyline: false,
        drawCircle: false,
        drawText: false,
        cutPolygon: false,
        rotateMode: false,
        drawPolygon: true,
        drawRectangle: true,
        editMode: true,
        dragMode: true,
        removalMode: true,
    });

    map.pm.setGlobalOptions({
        snappable: true,
        snapDistance: 15,
        allowSelfIntersection: false,
        templineStyle: { color: '#000', dashArray: '6,6' },
        hintlineStyle: { color: '#000', dashArray: '6,6' },
        pathOptions: { color: '#000', fillColor: '#000', fillOpacity: 0.2 },
    });

    map.on('pm:create', (e) => {
        // Só uma cerca por tela: o desenho novo substitui o anterior.
        definirCerca(e.layer);
        map.pm.disableDraw();
    });

    map.on('pm:remove', () => {
        cerca = null;
        sincronizar();
    });

    // ── Ações da barra lateral ──────────────────────────────────────────────
    function acao(id, fn) {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', fn);
        }
    }

    acao('btn-desfazer', () => {
        if (historico.length <= 1) {
            return;
        }
        refeitos.push(historico.pop());
        const anterior = JSON.parse(historico[historico.length - 1] || '[]');

        if (cerca) {
            cerca.remove();
            cerca = null;
        }
        if (anterior.length >= 3) {
            desenharVertices(anterior, { ajustarZoom: false, salvarHistorico: false });
        } else {
            sincronizar({ salvarHistorico: false });
        }
    });

    acao('btn-refazer', () => {
        if (!refeitos.length) {
            return;
        }
        const proximo = JSON.parse(refeitos.pop());
        historico.push(JSON.stringify(proximo));

        if (cerca) {
            cerca.remove();
            cerca = null;
        }
        if (proximo.length >= 3) {
            desenharVertices(proximo, { ajustarZoom: false, salvarHistorico: false });
        } else {
            sincronizar({ salvarHistorico: false });
        }
    });

    acao('btn-limpar', () => {
        if (cerca) {
            cerca.remove();
            cerca = null;
        }
        sincronizar();
    });

    acao('btn-centralizar', () => {
        if (cerca) {
            map.fitBounds(cerca.getBounds().pad(0.25));
        }
    });

    acao('btn-minha-loc', () => {
        if (!navigator.geolocation) {
            return;
        }
        navigator.geolocation.getCurrentPosition((pos) => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 17);
        });
    });

    // Busca de endereço (Nominatim/OpenStreetMap).
    // Nota: não há <form> aqui — o editor é incluído dentro do formulário da
    // cerca, e formulários aninhados fecham o form principal no parser HTML.
    async function buscarEndereco() {
        const termo = document.getElementById('input-busca')?.value.trim();
        const retorno = document.getElementById('busca-retorno');

        if (!termo) {
            return;
        }

        if (retorno) {
            retorno.textContent = 'Buscando…';
        }

        try {
            const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(termo);
            const resp = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
            const dados = await resp.json();

            if (dados.length) {
                map.setView([parseFloat(dados[0].lat), parseFloat(dados[0].lon)], 17);
                if (retorno) retorno.textContent = dados[0].display_name;
            } else if (retorno) {
                retorno.textContent = 'Endereço não encontrado.';
            }
        } catch {
            if (retorno) retorno.textContent = 'Não foi possível buscar agora.';
        }
    }

    acao('btn-buscar', buscarEndereco);

    document.getElementById('input-busca')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault(); // não deixa o Enter salvar a cerca
            buscarEndereco();
        }
    });

    // Coordenadas coladas/digitadas: "lat, lng" por linha
    acao('btn-aplicar-coordenadas', () => {
        const texto = document.getElementById('input-coordenadas')?.value || '';

        const pontos = texto
            .split(/\r?\n/)
            .map((linha) => linha.split(/[,;\t]/).map((n) => parseFloat(n.trim())))
            .filter((p) => p.length >= 2 && Number.isFinite(p[0]) && Number.isFinite(p[1]))
            .map((p) => [p[0], p[1]]);

        if (pontos.length >= 3) {
            desenharVertices(pontos);
        }
    });

    // Exportar GeoJSON
    acao('btn-exportar', () => {
        const geo = paraGeoJSON(verticesAtuais());
        if (!geo) {
            return;
        }

        const nome = (document.getElementById('nome')?.value || 'cerca').trim();
        geo.properties = { nome };

        const blob = new Blob([JSON.stringify(geo, null, 2)], { type: 'application/geo+json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = nome.replace(/[^\w\-]+/g, '_').toLowerCase() + '.geojson';
        a.click();
        URL.revokeObjectURL(a.href);
    });

    // Importar GeoJSON
    const inputArquivo = document.getElementById('input-importar-geojson');
    if (inputArquivo) {
        inputArquivo.addEventListener('change', async (e) => {
            const arquivo = e.target.files?.[0];
            if (!arquivo) {
                return;
            }

            try {
                const json = JSON.parse(await arquivo.text());
                const geometria = json.type === 'FeatureCollection'
                    ? json.features?.[0]?.geometry
                    : (json.geometry || json);

                const anel = geometria?.type === 'Polygon' ? geometria.coordinates[0] : null;

                if (anel) {
                    desenharVertices(anel.map(([lng, lat]) => [lat, lng]));
                }
            } catch {
                /* arquivo inválido — ignora */
            }

            e.target.value = '';
        });
    }

    // ── Tela cheia (API nativa, com fallback) ───────────────────────────────
    const cartao = document.getElementById('map-card');
    acao('btn-fullscreen', () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            cartao?.requestFullscreen?.();
        }
    });

    document.addEventListener('fullscreenchange', () => {
        const cheio = !!document.fullscreenElement;
        document.getElementById('icon-expand')?.classList.toggle('hidden', cheio);
        document.getElementById('icon-shrink')?.classList.toggle('hidden', !cheio);
        setTimeout(() => map.invalidateSize(), 100);
    });

    // ── Atalhos de teclado ──────────────────────────────────────────────────
    document.addEventListener('keydown', (e) => {
        const digitando = ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName);

        if (e.key === 'Escape') {
            map.pm.disableDraw();
            map.pm.disableGlobalEditMode();
            return;
        }

        if (digitando) {
            return;
        }

        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
            e.preventDefault();
            document.getElementById(e.shiftKey ? 'btn-refazer' : 'btn-desfazer')?.click();
        }
    });

    // ── Estado inicial ──────────────────────────────────────────────────────
    if (Array.isArray(poligonoInicial) && poligonoInicial.length >= 3) {
        desenharVertices(poligonoInicial);
    } else {
        historico.push('[]');
        sincronizar({ salvarHistorico: false });
        if (vizinhas.length && camadaVizinhas.getLayers().length) {
            map.fitBounds(L.featureGroup(camadaVizinhas.getLayers()).getBounds().pad(0.2));
        }
    }

    // Impede o envio do formulário sem uma área válida.
    const form = input?.closest('form');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!input.value) {
                e.preventDefault();
                el.status?.classList.add('text-red-600');
                if (el.status) {
                    el.status.textContent = 'Desenhe a área da cerca antes de salvar (mínimo 3 vértices).';
                }
                document.getElementById(elementoMapa)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    return { map, sincronizar, desenharVertices };
}

window.criarEditorCercas = criarEditorCercas;
