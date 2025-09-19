<script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
<main class="agendasya">
    <h2 class="agendasya__heading">Agenda por Zona</h2>
    <p class="agendasya__descripcion">Selecciona tu zona de logeo para ver el calendario y el clima específico.</p>

    <section style="max-width:1000px;margin:1rem auto;padding:1rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
        <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:260px">
                <label for="zonaSelect" style="display:block;font-weight:600;color:#374151">Zona</label>
                <select id="zonaSelect" style="width:100%;padding:.5rem;border:1px solid #d1d5db;border-radius:6px">
                    <option value="">Cargando zonas...</option>
                </select>
                <div id="zonaStatus" style="margin-top:.25rem;color:#6b7280;font-size:.9rem"></div>
            </div>

            <div id="climaPanel" style="flex:1;min-width:260px;border:1px solid #e5e7eb;border-radius:8px;padding:.75rem">
                <div style="font-weight:700;color:#111827">Clima</div>
                <div id="climaContenido" style="margin-top:.25rem;color:#374151;font-size:.95rem">Selecciona una zona...</div>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center">
                    <button id="btnClima" class="boton" style="padding:.45rem .8rem;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer;display:none">Mostrar clima actual</button>
                    <span id="climaMsg" style="color:#6b7280;font-size:.9rem"></span>
                </div>
            </div>
        </div>

        <div style="margin-top:1rem">
            <div id="calendar" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem"></div>
        </div>

        <pre id="consolaAgendaya" style="margin-top:1rem;background:#0b1020;color:#cbd5e1;padding:.75rem;border-radius:6px;overflow:auto;max-height:300px"></pre>
    </section>

    <script>
        (function() {
            const OPENWEATHER_API_KEY = <?php echo json_encode($_ENV['OPENWEATHER_API_KEY'] ?? ($_SERVER['OPENWEATHER_API_KEY'] ?? '')); ?>;

            const zonaSelect = document.getElementById('zonaSelect');
            const zonaStatus = document.getElementById('zonaStatus');
            const climaContenido = document.getElementById('climaContenido');
            const consola = document.getElementById('consolaAgendaya');
            const calendarEl = document.getElementById('calendar');
            const btnClima = document.getElementById('btnClima');
            const climaMsg = document.getElementById('climaMsg');
            let calendar = null;
            let currentZona = null;
            let currentSlotKey = null;
            let slotInterval = null;

            function log(msg, obj) {
                const line = typeof obj !== 'undefined' ? `${msg} ${JSON.stringify(obj, null, 2)}` : msg;
                consola.textContent += (consola.textContent ? '\n' : '') + line;
                console.log(msg, obj ?? '');
            }

            function initCalendar() {
                if (calendar) {
                    calendar.destroy();
                }
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: []
                });
                calendar.render();
            }

            function setCalendarEventsForZone(zona) {
                // TODO: Reemplazar con eventos/demanda reales por zona desde tu backend.
                // Por ahora generamos eventos de ejemplo dependientes de la zona para demostrar el cambio.
                const hoy = new Date();
                const y = hoy.getFullYear();
                const m = String(hoy.getMonth() + 1).padStart(2, '0');
                const d = String(hoy.getDate()).padStart(2, '0');
                const base = `${y}-${m}-${d}`;

                const sample = [{
                        title: `Demanda Alta - ${zona.nombres}`,
                        start: base
                    },
                    {
                        title: `Demanda Media - ${zona.nombres}`,
                        start: `${y}-${m}-${String(hoy.getDate()+2).padStart(2, '0')}`
                    },
                    {
                        title: `Demanda Baja - ${zona.nombres}`,
                        start: `${y}-${m}-${String(hoy.getDate()+5).padStart(2, '0')}`
                    }
                ];

                calendar.removeAllEvents();
                calendar.addEventSource(sample);
            }

            async function fetchMisZonas() {
                zonaStatus.textContent = 'Cargando zonas...';
                try {
                    const resp = await fetch('/api/mis-zonas');
                    if (resp.status === 401) {
                        zonaSelect.innerHTML = '<option value="">No autenticado. Inicia sesión.</option>';
                        zonaStatus.textContent = 'Debes iniciar sesión para ver tus zonas.';
                        return [];
                    }
                    const data = await resp.json();
                    log('Zonas del usuario:', data);
                    if (!data.ok) {
                        zonaSelect.innerHTML = '<option value="">No se pudieron cargar las zonas</option>';
                        zonaStatus.textContent = data.error || '';
                        return [];
                    }
                    const zonas = data.zonas || [];
                    if (zonas.length === 0) {
                        zonaSelect.innerHTML = '<option value="">No tienes zonas asignadas</option>';
                        zonaStatus.textContent = 'Asigna zonas en tu perfil.';
                        return [];
                    }
                    // Popular selector
                    zonaSelect.innerHTML = '<option value="">Selecciona una zona</option>' +
                        zonas.map(z => `<option value="${z.id}" data-lat="${z.lat ?? ''}" data-lon="${z.lon ?? ''}" data-nombre="${z.nombres}">${z.nombres}</option>`).join('');
                    zonaStatus.textContent = `Tienes ${zonas.length} zona(s)`;
                    return zonas;
                } catch (e) {
                    zonaSelect.innerHTML = '<option value="">Error cargando zonas</option>';
                    zonaStatus.textContent = e.message;
                    log('Error cargando zonas:', {
                        message: e.message
                    });
                    return [];
                }
            }

            function getTodayStr() {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function getCurrentSlotKey() {
                const now = new Date();
                const y = now.getFullYear();
                const m = String(now.getMonth() + 1).padStart(2, '0');
                const d = String(now.getDate()).padStart(2, '0');
                const H = String(now.getHours()).padStart(2, '0');
                return `${y}-${m}-${d} ${H}:00:00`;
            }

            function startSlotWatcher() {
                if (slotInterval) clearInterval(slotInterval);
                currentSlotKey = getCurrentSlotKey();
                slotInterval = setInterval(() => {
                    const key = getCurrentSlotKey();
                    if (key !== currentSlotKey) {
                        currentSlotKey = key;
                        log('Cambio de franja detectado', {
                            key
                        });
                        if (currentZona) {
                            climaContenido.textContent = 'Nueva hora: presiona “Mostrar clima actual”';
                            btnClima.style.display = 'inline-block';
                            btnClima.disabled = false;
                        }
                    }
                }, 30000);
            }

            async function getProveedorActual(fecha) {
                const resp = await fetch(`/api/clima/proveedor-actual?fecha=${encodeURIComponent(fecha)}`);
                const data = await resp.json();
                if (!data.ok) throw new Error(data.error || 'Error proveedor');
                return data.provider;
            }

            function renderClimaFromPayload(provider, payload, nombre) {
                try {
                    const p = typeof payload === 'string' ? JSON.parse(payload) : payload;
                    if (provider === 'owm') {
                        const temp = p.main && typeof p.main.temp !== 'undefined' ? Math.round(p.main.temp) : 'N/D';
                        const desc = (p.weather && p.weather[0] && p.weather[0].description) ? p.weather[0].description : 'Sin datos';
                        climaContenido.innerHTML = `<strong>${nombre}</strong>: ${temp}°C, ${desc.charAt(0).toUpperCase() + desc.slice(1)}`;
                    } else {
                        // open-meteo
                        const cw = p.current_weather || {};
                        const temp = typeof cw.temperature !== 'undefined' ? Math.round(cw.temperature) : 'N/D';
                        const desc = typeof cw.weathercode !== 'undefined' ? `Código ${cw.weathercode}` : 'Sin datos';
                        climaContenido.innerHTML = `<strong>${nombre}</strong>: ${temp}°C, ${desc}`;
                    }
                } catch (e) {
                    climaContenido.textContent = 'No se pudo renderizar el clima';
                }
            }

            async function fetchOWM(lat, lon) {
                if (!OPENWEATHER_API_KEY) {
                    throw new Error('OPENWEATHER_API_KEY no está configurada');
                }
                const url = `https://api.openweathermap.org/data/2.5/weather?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&units=metric&lang=es&appid=${OPENWEATHER_API_KEY}`;
                const resp = await fetch(url);
                const data = await resp.json();
                if (data.cod && +data.cod !== 200) throw new Error(data.message || 'Error OWM');
                return data;
            }

            async function fetchOpenMeteo(lat, lon) {
                const url = `https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&current_weather=true&timezone=auto&forecast_days=1`;
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error Open-Meteo');
                const data = await resp.json();
                return data;
            }

            async function guardarLectura(provider, zonaId, lat, lon, payload) {
                const resp = await fetch('/api/clima/guardar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        provider,
                        zona_id: zonaId,
                        lat,
                        lon,
                        payload
                    })
                });
                const data = await resp.json();
                return {
                    status: resp.status,
                    ...data
                };
            }

            function onZonaChange() {
                const opt = zonaSelect.options[zonaSelect.selectedIndex];
                const id = zonaSelect.value;
                if (!id) {
                    climaContenido.textContent = 'Selecciona una zona...';
                    calendar && calendar.removeAllEvents();
                    btnClima.style.display = 'none';
                    currentZona = null;
                    return;
                }
                const nombre = opt.getAttribute('data-nombre') || 'Zona';
                const lat = parseFloat(opt.getAttribute('data-lat'));
                const lon = parseFloat(opt.getAttribute('data-lon'));
                currentZona = {
                    id,
                    nombres: nombre,
                    lat,
                    lon
                };
                // Cargar estado actual para la franja vigente (no llama proveedor externo)
                if (Number.isFinite(lat) && Number.isFinite(lon)) {
                    fetch(`/api/clima/estado-actual?zona_id=${encodeURIComponent(id)}`)
                        .then(r => r.json())
                        .then(data => {
                            log('Estado actual clima:', data);
                            if (data.ok && data.lectura) {
                                renderClimaFromPayload(data.lectura.provider, data.lectura.payload, nombre);
                                btnClima.style.display = 'none';
                            } else {
                                climaContenido.textContent = 'Sin clima para esta hora. Presiona “Mostrar clima actual”';
                                btnClima.style.display = 'inline-block';
                                btnClima.disabled = false;
                            }
                        })
                        .catch(e => {
                            climaContenido.textContent = 'Error consultando estado';
                            btnClima.style.display = 'inline-block';
                            btnClima.disabled = false;
                        });
                } else {
                    climaContenido.textContent = `${nombre}: coordenadas no configuradas`;
                    btnClima.style.display = 'none';
                }
                setCalendarEventsForZone({
                    id,
                    nombres: nombre
                });
            }

            btnClima.addEventListener('click', async () => {
                if (!currentZona) return;
                const {
                    id,
                    nombres,
                    lat,
                    lon
                } = currentZona;
                if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                    climaMsg.textContent = 'Coordenadas no configuradas';
                    return;
                }
                btnClima.disabled = true;
                climaMsg.textContent = 'Consultando clima...';
                try {
                    const providerPref = await getProveedorActual(getTodayStr());
                    let provider = providerPref;
                    let payload = null;
                    if (provider === 'owm') {
                        try {
                            payload = await fetchOWM(lat, lon);
                            const res = await guardarLectura('owm', id, lat, lon, payload);
                            if (!res.ok && res.status === 429 && res.error === 'owm_limit_reached') {
                                provider = 'open-meteo';
                            } else if (!res.ok) {
                                throw new Error(res.error || 'Error guardando OWM');
                            } else {
                                renderClimaFromPayload('owm', payload, nombres);
                                btnClima.style.display = 'none';
                                climaMsg.textContent = '';
                                return;
                            }
                        } catch (e) {
                            // Si falla OWM por otro motivo, intentar Open-Meteo como respaldo
                            provider = 'open-meteo';
                        }
                    }

                    // Fallback o preferencia a Open-Meteo
                    if (provider === 'open-meteo') {
                        payload = await fetchOpenMeteo(lat, lon);
                        const res2 = await guardarLectura('open-meteo', id, lat, lon, payload);
                        if (!res2.ok) throw new Error(res2.error || 'Error guardando Open-Meteo');
                        renderClimaFromPayload('open-meteo', payload, nombres);
                        btnClima.style.display = 'none';
                        climaMsg.textContent = '';
                    }
                } catch (e) {
                    climaMsg.textContent = `Error: ${e.message}`;
                    btnClima.disabled = false;
                }
            });

            // Init
            initCalendar();
            startSlotWatcher();
            fetchMisZonas().then(() => {
                zonaSelect.addEventListener('change', onZonaChange);
            });
        })();
    </script>