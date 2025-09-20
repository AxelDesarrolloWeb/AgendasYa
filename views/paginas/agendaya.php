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
        (function() {
            const OPENWEATHER_API_KEY = <?php echo json_encode($_ENV['OPENWEATHER_API_KEY'] ?? ($_SERVER['OPENWEATHER_API_KEY'] ?? '')); ?>;

            const zonaSelect = document.getElementById('zonaSelect');
            const zonaStatus = document.getElementById('zonaStatus');
            const climaContenido = document.getElementById('climaContenido');
            const consola = document.getElementById('consolaAgendaya');
            const calendarEl = document.getElementById('calendar');
            const btnClima = document.getElementById('btnClima');
            const climaMsg = document.getElementById('climaMsg');
            const btnPronostico = document.getElementById('btnPronostico');
            const chkAutoPronostico = document.getElementById('chkAutoPronostico');
            const btnHorasDia = document.getElementById('btnHorasDia');
            const chkAutoHoras = document.getElementById('chkAutoHoras');
            let calendar = null;
            let currentZona = null;
            let currentSlotKey = null;
            let slotInterval = null;
            let forecastInterval = null;
            let hourlyInterval = null;
            let selectedDate = null; // YYYY-MM-DD

            function log(msg, obj) {
                const line = typeof obj !== 'undefined' ? `${msg} ${JSON.stringify(obj, null, 2)}` : msg;
                consola.textContent += (consola.textContent ? '\n' : '') + line;
                console.log(msg, obj ?? '');
            }

            function initCalendar() {
                if (calendar) {
                    calendar.destroy();
                }
                const validStart = getDateOffsetYMD(-28);
                const validEnd = getDateOffsetYMD(28);
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    height: 'auto',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    validRange: { start: validStart, end: validEnd },
                    events: [],
                    dateClick: (info) => {
                        selectedDate = info.dateStr;
                        if (currentZona) btnHorasDia.style.display = 'inline-block';
                        calendar.changeView('timeGridDay', info.date);
                    },
                    datesSet: () => {
                        const v = calendar.view.type;
                        if (v === 'timeGridDay') {
                            selectedDate = formatDateYMD(calendar.getDate());
                            if (currentZona) btnHorasDia.style.display = 'inline-block';
                        } else {
                            if (!currentZona) btnHorasDia.style.display = 'none';
                        }
                    }
                });
                calendar.render();
            }

            function setCalendarEventsForZone(zona) {
                // Cargar histórico (28 días previos) + próximos 16 días para la zona actual
                if (!zona || !Number.isFinite(zona.lat) || !Number.isFinite(zona.lon)) {
                    calendar && calendar.removeAllEvents();
                    return;
                }
                loadDailyForCalendar(zona);
            }

            function getDateOffsetYMD(days) {
                const dt = new Date();
                dt.setDate(dt.getDate() + days);
                const y = dt.getFullYear();
                const m = String(dt.getMonth() + 1).padStart(2, '0');
                const d = String(dt.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
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

            function formatDateYMD(dateObj) {
                const y = dateObj.getFullYear();
                const m = String(dateObj.getMonth() + 1).padStart(2, '0');
                const d = String(dateObj.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            function isPastDate(ymd) {
                return ymd < getTodayStr();
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

            async function fetchOpenMeteoDaily(lat, lon, days = 16) {
                const url = `https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&timezone=auto&forecast_days=${days}`;
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error Open-Meteo (daily)');
                const data = await resp.json();
                return data;
            }

            function describeWeatherCode(code) {
                const map = {
                    0: 'Despejado', 1: 'Mayormente despejado', 2: 'Parcialmente nublado', 3: 'Nublado',
                    45: 'Niebla', 48: 'Niebla escarchada', 51: 'Llovizna ligera', 53: 'Llovizna', 55: 'Llovizna intensa',
                    56: 'Llovizna helada ligera', 57: 'Llovizna helada intensa', 61: 'Lluvia ligera', 63: 'Lluvia', 65: 'Lluvia fuerte',
                    66: 'Lluvia helada ligera', 67: 'Lluvia helada fuerte', 71: 'Nieve ligera', 73: 'Nieve', 75: 'Nieve fuerte',
                    77: 'Granos de nieve', 80: 'Chubascos ligeros', 81: 'Chubascos', 82: 'Chubascos fuertes',
                    85: 'Chubascos de nieve ligeros', 86: 'Chubascos de nieve fuertes', 95: 'Tormenta', 96: 'Tormenta con granizo', 99: 'Tormenta severa'
                };
                return map[code] || `Código ${code}`;
            }

            function iconForWeatherCode(code) {
                if (code === 0) return '☀️';
                if (code === 1 || code === 2) return '🌤️';
                if (code === 3) return '☁️';
                if (code === 45 || code === 48) return '🌫️';
                if ([51,53,55,56,57].includes(code)) return '🌦️';
                if ([61,63,65,66,67,80,81,82].includes(code)) return '🌧️';
                if ([71,73,75,77,85,86].includes(code)) return '🌨️';
                if ([95,96,99].includes(code)) return '⛈️';
                return '🌡️';
            }

            function colorForWeatherCode(code) {
                if (code === 0) return '#fbbf24';        // sun - amber
                if (code === 1 || code === 2) return '#fde68a'; // partly - light amber
                if (code === 3) return '#9ca3af';        // clouds - gray
                if (code === 45 || code === 48) return '#c4b5fd'; // fog - violet
                if ([51,53,55,56,57].includes(code)) return '#93c5fd'; // drizzle - blue
                if ([61,63,65,66,67,80,81,82].includes(code)) return '#60a5fa'; // rain - blue stronger
                if ([71,73,75,77,85,86].includes(code)) return '#bfdbfe'; // snow - light blue
                if ([95,96,99].includes(code)) return '#f87171'; // storm - red
                return '#a3a3a3';
            }

            function buildWeatherEventsFromDaily(daily, zonaNombre) {
                const events = [];
                if (!daily || !daily.time || !Array.isArray(daily.time)) return events;
                const today = getTodayStr();
                for (let i = 0; i < daily.time.length; i++) {
                    const date = daily.time[i];
                    if (date === today) continue; // excluir hoy, lo consulta el repartidor
                    const tmax = Math.round(daily.temperature_2m_max?.[i] ?? NaN);
                    const tmin = Math.round(daily.temperature_2m_min?.[i] ?? NaN);
                    const code = daily.weathercode?.[i];
                    const rain = daily.precipitation_sum?.[i];
                    const icon = typeof code !== 'undefined' ? iconForWeatherCode(code) : '🌡️';
                    const parts = [];
                    if (!Number.isNaN(tmax) && !Number.isNaN(tmin)) parts.push(`${tmax}°/${tmin}°`);
                    if (typeof code !== 'undefined') parts.push(describeWeatherCode(code));
                    if (typeof rain !== 'undefined') parts.push(`${Math.round(rain)}mm`);
                    const title = `${icon} ${parts.length ? parts.join(' · ') : 'Clima'}`;
                    const color = typeof code !== 'undefined' ? colorForWeatherCode(code) : '#a3a3a3';
                    events.push({
                        title,
                        start: date,
                        allDay: true,
                        backgroundColor: color,
                        borderColor: color,
                        textColor: '#111827',
                        groupId: 'weatherForecast',
                        extendedProps: { isWeather: true, provider: 'open-meteo', zona: zonaNombre }
                    });
                }
                return events;
            }

            function updateCalendarWeatherEvents(events) {
                if (!calendar) return;
                // Eliminar eventos previos de clima
                calendar.getEvents().forEach(ev => {
                    if (ev.extendedProps && ev.extendedProps.isWeather) ev.remove();
                });
                // Agregar nuevos
                events.forEach(ev => calendar.addEvent(ev));
            }

            function updateCalendarHourlyEvents(events) {
                if (!calendar) return;
                calendar.getEvents().forEach(ev => {
                    if (ev.extendedProps && ev.extendedProps.isWeatherHourly) ev.remove();
                });
                events.forEach(ev => calendar.addEvent(ev));
            }

            async function loadForecastNext16(zona) {
                try {
                    const data = await fetchOpenMeteoDaily(zona.lat, zona.lon, 16);
                    const events = buildWeatherEventsFromDaily(data.daily, zona.nombres);
                    updateCalendarWeatherEvents(events);
                } catch (e) {
                    log('Error cargando pronóstico 16 días:', { message: e.message });
                }
            }

            async function fetchOpenMeteoDailyArchiveRange(lat, lon, startYmd, endYmd) {
                const url = `https://archive-api.open-meteo.com/v1/archive?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&daily=temperature_2m_max,temperature_2m_min,weathercode,precipitation_sum&start_date=${startYmd}&end_date=${endYmd}&timezone=auto`;
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error Open-Meteo (daily archive)');
                return await resp.json();
            }

            async function loadDailyForCalendar(zona) {
                try {
                    const start = getDateOffsetYMD(-28);
                    const end = getDateOffsetYMD(-1);
                    const [arch, fore] = await Promise.all([
                        fetchOpenMeteoDailyArchiveRange(zona.lat, zona.lon, start, end),
                        fetchOpenMeteoDaily(zona.lat, zona.lon, 16)
                    ]);
                    const eventsArchive = buildWeatherEventsFromDaily(arch.daily, zona.nombres);
                    const eventsForecast = buildWeatherEventsFromDaily(fore.daily, zona.nombres);
                    const all = [...eventsArchive, ...eventsForecast];
                    updateCalendarWeatherEvents(all);
                } catch (e) {
                    log('Error cargando diario (archivo+pronóstico):', { message: e.message });
                }
            }

            async function fetchOpenMeteoHourlyForecastDay(lat, lon, ymd) {
                const url = `https://api.open-meteo.com/v1/forecast?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&hourly=temperature_2m,weathercode,precipitation&start_date=${ymd}&end_date=${ymd}&timezone=auto`;
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error Open-Meteo (hourly forecast)');
                return await resp.json();
            }

            async function fetchOpenMeteoHourlyArchiveDay(lat, lon, ymd) {
                const url = `https://archive-api.open-meteo.com/v1/archive?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&hourly=temperature_2m,weathercode,precipitation&start_date=${ymd}&end_date=${ymd}&timezone=auto`;
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error Open-Meteo (archive hourly)');
                return await resp.json();
            }

            function buildHourlyEventsFromData(hourly, zonaNombre) {
                const events = [];
                if (!hourly || !Array.isArray(hourly.time)) return events;
                const times = hourly.time;
                const temps = hourly.temperature_2m || [];
                const codes = hourly.weathercode || [];
                const rain = hourly.precipitation || [];
                for (let i = 0; i < times.length; i++) {
                    const start = times[i];
                    const endDate = new Date(start);
                    endDate.setHours(endDate.getHours() + 1);
                    const y = endDate.getFullYear();
                    const m = String(endDate.getMonth() + 1).padStart(2, '0');
                    const d = String(endDate.getDate()).padStart(2, '0');
                    const H = String(endDate.getHours()).padStart(2, '0');
                    const end = `${y}-${m}-${d}T${H}:00:00`;
                    const t = temps[i];
                    const c = codes[i];
                    const r = rain[i];
                    const title = `${iconForWeatherCode(c)} ${Math.round(Number(t))}°C${typeof r !== 'undefined' ? ` · ${Math.round(Number(r))}mm` : ''}`;
                    const color = colorForWeatherCode(c);
                    events.push({
                        title,
                        start,
                        end,
                        display: 'block',
                        backgroundColor: color,
                        borderColor: color,
                        textColor: '#111827',
                        groupId: 'weatherHourly',
                        extendedProps: { isWeatherHourly: true, provider: 'open-meteo', zona: zonaNombre, code: c }
                    });
                }
                return events;
            }

            async function loadHourlyForDate(zona, ymd) {
                try {
                    const data = isPastDate(ymd)
                        ? await fetchOpenMeteoHourlyArchiveDay(zona.lat, zona.lon, ymd)
                        : await fetchOpenMeteoHourlyForecastDay(zona.lat, zona.lon, ymd);
                    const events = buildHourlyEventsFromData(data.hourly, zona.nombres);
                    updateCalendarHourlyEvents(events);
                    if (calendar.view.type !== 'timeGridDay') {
                        calendar.changeView('timeGridDay', ymd);
                    }
                } catch (e) {
                    log('Error cargando horas del día:', { message: e.message, ymd });
                }
            }

            function startHourlyAutoRefresh() {
                stopHourlyAutoRefresh();
                hourlyInterval = setInterval(() => {
                    if (currentZona && selectedDate) {
                        loadHourlyForDate(currentZona, selectedDate);
                    }
                }, 60 * 60 * 1000);
            }

            function stopHourlyAutoRefresh() {
                if (hourlyInterval) clearInterval(hourlyInterval);
                hourlyInterval = null;
            }

            function startForecastAutoRefresh() {
                stopForecastAutoRefresh();
                // actualizar a la hora y luego cada hora
                forecastInterval = setInterval(() => {
                    if (currentZona) loadForecastNext16(currentZona);
                }, 60 * 60 * 1000);
            }

            function stopForecastAutoRefresh() {
                if (forecastInterval) clearInterval(forecastInterval);
                forecastInterval = null;
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
                    btnPronostico.style.display = 'none';
                    btnHorasDia.style.display = 'none';
                    stopHourlyAutoRefresh();
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
                    // Mostrar opción de pronóstico y (re)iniciar auto-refresh si corresponde
                    btnPronostico.style.display = 'inline-block';
                    if (chkAutoPronostico.checked) startForecastAutoRefresh(); else stopForecastAutoRefresh();
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
                    btnPronostico.style.display = 'none';
                    btnHorasDia.style.display = 'none';
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
            // Eventos UI para pronóstico extendido
            btnPronostico.addEventListener('click', () => {
                if (currentZona) loadDailyForCalendar(currentZona);
            });
            chkAutoPronostico.addEventListener('change', (e) => {
                if (e.target.checked) {
                    startForecastAutoRefresh();
                } else {
                    stopForecastAutoRefresh();
                }
            });
            // Eventos UI para horas del día
            btnHorasDia.addEventListener('click', () => {
                if (!currentZona) return;
                const ymd = selectedDate || getTodayStr();
                loadHourlyForDate(currentZona, ymd);
            });
            chkAutoHoras.addEventListener('change', (e) => {
                if (e.target.checked) startHourlyAutoRefresh(); else stopHourlyAutoRefresh();
            });
        })();
    </script>