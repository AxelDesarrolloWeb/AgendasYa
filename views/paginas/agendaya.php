<script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
<main class="agendasya">
    <h2 class="agendasya__heading">Agenda por Zona</h2>
    <p class="agendasya__descripcion">Selecciona tu zona de logeo para ver el calendario y el clima específico.</p>

    <section class="agendasya__section">
        <div class="agendasya__zonas">
            <div class="agendasya__zona">
                <label for="zonaSelect" class="agendasya__label">Zona</label>
                <select id="zonaSelect" class="agendasya__select">
                    <option value="">Cargando zonas...</option>
                </select>
                <div id="zonaStatus" class="agendasya__status"></div>
            </div>

            <div id="climaPanel" class="agendasya__panel agendasya__clima-panel">
                <div class="agendasya__clima-title">Clima</div>
                <div id="climaContenido" class="agendasya__clima-content">Selecciona una zona...</div>
                <div class="agendasya__clima-actions">
                    <button id="btnClima" class="boton boton--primary" style="display:none">Mostrar clima actual</button>
                    <button id="btnClimaUbicacion" class="boton boton--primary">Clima en mi ubicación</button>
                    <span id="climaMsg" class="agendasya__clima-msg"></span>
                </div>
                <div class="agendasya__forecast-actions">
                    <button id="btnPronostico" class="boton boton--success" style="display:none">Mostrar 16 días siguientes</button>
                    <label class="agendasya__toggle">
                        <input type="checkbox" id="chkAutoPronostico"> Auto-actualizar pronóstico cada hora
                    </label>
                </div>
                <div class="agendasya__hourly-actions">
                    <button id="btnHorasDia" class="boton boton--violet" style="display:none">Mostrar horas del día seleccionado</button>
                    <label class="agendasya__toggle">
                        <input type="checkbox" id="chkAutoHoras"> Auto-actualizar horas (día)
                    </label>
                </div>
            </div>
        </div>

        <div class="agendasya__calendar">
            <div id="calendar" class="agendasya__calendar-inner"></div>
        </div>

        <pre id="consolaAgendaya" class="agendasya__console"></pre>
    </section>

    <script>
        // Extras: WKT utils, centroid/bbox, point-in-polygon, fetch patch for /api/mis-zonas,
        // and geolocalización para "Clima en mi ubicación".
        (function(){
            // --- WKT helpers ---
            function parseWKTPolygon(wkt) {
                if (!wkt || typeof wkt !== 'string') return [];
                const s = wkt.trim().replace(/^POLYGON\s*\(\(/i, '').replace(/\)\)$/,'');
                const pairs = s.split(',').map(p => p.trim().split(/\s+/).map(Number));
                if (pairs.length > 1) {
                    const [x0,y0] = pairs[0], [xn,yn] = pairs[pairs.length - 1];
                    if (x0 === xn && y0 === yn) pairs.pop();
                }
                return pairs; // [[lon, lat], ...]
            }

            function polygonCentroid(coords) {
                let A = 0, Cx = 0, Cy = 0;
                const n = coords.length;
                if (n < 3) return null;
                for (let i = 0; i < n; i++) {
                    const [x0, y0] = coords[i];
                    const [x1, y1] = coords[(i + 1) % n];
                    const cross = x0 * y1 - x1 * y0;
                    A += cross;
                    Cx += (x0 + x1) * cross;
                    Cy += (y0 + y1) * cross;
                }
                A = A / 2;
                if (Math.abs(A) < 1e-12) return null;
                Cx = Cx / (6 * A);
                Cy = Cy / (6 * A);
                return [Cx, Cy];
            }

            function bboxCenter(coords) {
                let minX=Infinity, minY=Infinity, maxX=-Infinity, maxY=-Infinity;
                for (const [x,y] of coords) {
                    if (x < minX) minX = x;
                    if (y < minY) minY = y;
                    if (x > maxX) maxX = x;
                    if (y > maxY) maxY = y;
                }
                return [(minX + maxX) / 2, (minY + maxY) / 2];
            }

            function pointInPolygon(point, vs) {
                const [x, y] = point;
                let inside = false;
                for (let i = 0, j = vs.length - 1; i < vs.length; j = i++) {
                    const xi = vs[i][0], yi = vs[i][1];
                    const xj = vs[j][0], yj = vs[j][1];
                    const intersect = ((yi > y) !== (yj > y)) &&
                                      (x < ((xj - xi) * (y - yi)) / (yj - yi + 0.0) + xi);
                    if (intersect) inside = !inside;
                }
                return inside;
            }

            function centerFromWKT(wkt) {
                const coords = parseWKTPolygon(wkt);
                if (coords.length < 3) return null;
                const c = polygonCentroid(coords) || bboxCenter(coords);
                if (!pointInPolygon(c, coords)) return bboxCenter(coords);
                return c;
            }

            // --- Patch fetch for /api/mis-zonas to inject lat/lon from WKT centroid ---
            const originalFetch = window.fetch ? window.fetch.bind(window) : null;
            if (originalFetch) {
                window.fetch = async function(input, init) {
                    const url = typeof input === 'string' ? input : (input && input.url ? input.url : '');
                    const resp = await originalFetch(input, init);
                    if (url && url.includes('/api/mis-zonas')) {
                        try {
                            const data = await resp.clone().json();
                            if (data && Array.isArray(data.zonas)) {
                                for (const z of data.zonas) {
                                    let lat = Number(z.lat), lon = Number(z.lon);
                                    const wkt = z.wkt || z.WKT || z.wkt_poligono || z.geom_wkt || z.polygon;
                                    if ((!Number.isFinite(lat) || !Number.isFinite(lon)) && typeof wkt === 'string' && /^(POLYGON|polygon)/.test(wkt)) {
                                        const c = centerFromWKT(wkt);
                                        if (c) { lon = c[0]; lat = c[1]; z.lat = lat; z.lon = lon; }
                                    }
                                }
                            }
                            const headers = new Headers(resp.headers);
                            headers.set('Content-Type', 'application/json');
                            return new Response(JSON.stringify(data), { status: resp.status, statusText: resp.statusText, headers });
                        } catch (e) {
                            console.warn('agendaya_extras: no se pudo ajustar /api/mis-zonas', e);
                        }
                    }
                    return resp;
                }
            }

            // --- Geolocalización: Clima en mi ubicación ---
            document.addEventListener('DOMContentLoaded', function(){
                const btn = document.getElementById('btnClimaUbicacion');
                const climaMsg = document.getElementById('climaMsg');
                const climaContenido = document.getElementById('climaContenido');
                if (!btn) return;
                btn.addEventListener('click', () => {
                    if (!navigator.geolocation) {
                        if (climaMsg) climaMsg.textContent = 'Geolocalización no soportada por tu navegador.';
                        return;
                    }
                    btn.disabled = true;
                    if (climaMsg) climaMsg.textContent = 'Obteniendo ubicación...';
                    navigator.geolocation.getCurrentPosition(async pos => {
                        try {
                            const lat = pos.coords.latitude;
                            const lon = pos.coords.longitude;

                            // Intentar detectar si está dentro de alguna zona (si la API trae WKT)
                            let zonaDentro = null;
                            try {
                                const r = await fetch('/api/mis-zonas');
                                const dj = await r.json();
                                const zonas = Array.isArray(dj.zonas) ? dj.zonas : [];
                                for (const z of zonas) {
                                    const wkt = z.wkt || z.WKT;
                                    if (wkt && /^(POLYGON|polygon)/.test(wkt)) {
                                        const coords = parseWKTPolygon(wkt);
                                        if (coords.length >= 3 && pointInPolygon([lon, lat], coords)) { zonaDentro = z; break; }
                                    }
                                }
                            } catch (e) { /* silencioso */ }

                            if (climaMsg) climaMsg.textContent = 'Consultando clima...';
                            const url = `https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&current_weather=true&timezone=auto&forecast_days=1`;
                            const r2 = await fetch(url);
                            if (!r2.ok) throw new Error('Error Open-Meteo');
                            const payload = await r2.json();
                            const cw = payload.current_weather || {};
                            const temp = typeof cw.temperature !== 'undefined' ? Math.round(cw.temperature) : 'N/D';
                            const desc = typeof cw.weathercode !== 'undefined' ? `Código ${cw.weathercode}` : 'Sin datos';
                            const nombreZona = zonaDentro ? (zonaDentro.nombres || zonaDentro.Nombre || zonaDentro['Zona de Logueo'] || 'Zona') : 'Mi ubicación';
                            if (climaContenido) climaContenido.innerHTML = `<strong>${nombreZona}</strong>: ${temp}°C, ${desc}`;
                            if (climaMsg) climaMsg.textContent = zonaDentro ? `Dentro de zona: ${nombreZona}` : '';
                        } catch (e) {
                            if (climaMsg) climaMsg.textContent = `Error: ${e.message}`;
                        } finally {
                            btn.disabled = false;
                        }
                    }, err => {
                        if (climaMsg) climaMsg.textContent = `No se pudo obtener la ubicación: ${err.message}`;
                        btn.disabled = false;
                    }, { enableHighAccuracy: true, timeout: 10000 });
                });
            });
        })();
    </script>
    <script src="/src/js/agendaya.js"></script>