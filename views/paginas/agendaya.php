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
                </div>
            </div>

            <div style="margin-top:1rem">
                <div id="calendar" style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem"></div>
            </div>

            <pre id="consolaAgendaya" style="margin-top:1rem;background:#0b1020;color:#cbd5e1;padding:.75rem;border-radius:6px;overflow:auto;max-height:300px"></pre>
        </section>

        <script>
            (function(){
                const OPENWEATHER_API_KEY = <?php echo json_encode($_ENV['OPENWEATHER_API_KEY'] ?? ($_SERVER['OPENWEATHER_API_KEY'] ?? '')); ?>;

                const zonaSelect = document.getElementById('zonaSelect');
                const zonaStatus = document.getElementById('zonaStatus');
                const climaContenido = document.getElementById('climaContenido');
                const consola = document.getElementById('consolaAgendaya');
                const calendarEl = document.getElementById('calendar');
                let calendar = null;

                function log(msg, obj) {
                    const line = typeof obj !== 'undefined' ? `${msg} ${JSON.stringify(obj, null, 2)}` : msg;
                    consola.textContent += (consola.textContent ? '\n' : '') + line;
                    console.log(msg, obj ?? '');
                }

                function initCalendar() {
                    if (calendar) { calendar.destroy(); }
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

                    const sample = [
                        { title: `Demanda Alta - ${zona.nombres}`, start: base },
                        { title: `Demanda Media - ${zona.nombres}`, start: `${y}-${m}-${String(hoy.getDate()+2).padStart(2, '0')}` },
                        { title: `Demanda Baja - ${zona.nombres}`, start: `${y}-${m}-${String(hoy.getDate()+5).padStart(2, '0')}` }
                    ];

                    calendar.removeAllEvents();
                    calendar.addEventSource(sample);
                }

                async function fetchMisZonas(){
                    zonaStatus.textContent = 'Cargando zonas...';
                    try{
                        const resp = await fetch('/api/mis-zonas');
                        if(resp.status === 401){
                            zonaSelect.innerHTML = '<option value="">No autenticado. Inicia sesión.</option>';
                            zonaStatus.textContent = 'Debes iniciar sesión para ver tus zonas.';
                            return [];
                        }
                        const data = await resp.json();
                        log('Zonas del usuario:', data);
                        if(!data.ok){
                            zonaSelect.innerHTML = '<option value="">No se pudieron cargar las zonas</option>';
                            zonaStatus.textContent = data.error || '';
                            return [];
                        }
                        const zonas = data.zonas || [];
                        if(zonas.length === 0){
                            zonaSelect.innerHTML = '<option value="">No tienes zonas asignadas</option>';
                            zonaStatus.textContent = 'Asigna zonas en tu perfil.';
                            return [];
                        }
                        // Popular selector
                        zonaSelect.innerHTML = '<option value="">Selecciona una zona</option>' +
                            zonas.map(z => `<option value="${z.id}" data-lat="${z.lat ?? ''}" data-lon="${z.lon ?? ''}" data-nombre="${z.nombres}">${z.nombres}</option>`).join('');
                        zonaStatus.textContent = `Tienes ${zonas.length} zona(s)`;
                        return zonas;
                    }catch(e){
                        zonaSelect.innerHTML = '<option value="">Error cargando zonas</option>';
                        zonaStatus.textContent = e.message;
                        log('Error cargando zonas:', { message: e.message });
                        return [];
                    }
                }

                async function fetchClimaPorCoords(lat, lon, nombre){
                    if(!OPENWEATHER_API_KEY){
                        climaContenido.textContent = 'OPENWEATHER_API_KEY no está configurada';
                        return;
                    }
                    try {
                        const url = `https://api.openweathermap.org/data/2.5/weather?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&units=metric&lang=es&appid=${OPENWEATHER_API_KEY}`;
                        const resp = await fetch(url);
                        const data = await resp.json();
                        log('Clima por zona:', data);
                        if (data.cod && +data.cod !== 200) throw new Error(data.message || 'Error al obtener el clima');
                        const temp = data.main && typeof data.main.temp !== 'undefined' ? Math.round(data.main.temp) : 'N/D';
                        const desc = (data.weather && data.weather[0] && data.weather[0].description) ? data.weather[0].description : 'Sin datos';
                        climaContenido.innerHTML = `<strong>${nombre}</strong>: ${temp}°C, ${desc.charAt(0).toUpperCase() + desc.slice(1)}`;
                    } catch(e) {
                        climaContenido.textContent = `No se pudo obtener el clima (${e.message})`;
                    }
                }

                function onZonaChange(){
                    const opt = zonaSelect.options[zonaSelect.selectedIndex];
                    const id = zonaSelect.value;
                    if(!id){
                        climaContenido.textContent = 'Selecciona una zona...';
                        calendar && calendar.removeAllEvents();
                        return;
                    }
                    const nombre = opt.getAttribute('data-nombre') || 'Zona';
                    const lat = parseFloat(opt.getAttribute('data-lat'));
                    const lon = parseFloat(opt.getAttribute('data-lon'));
                    if (Number.isFinite(lat) && Number.isFinite(lon)) {
                        fetchClimaPorCoords(lat, lon, nombre);
                    } else {
                        climaContenido.textContent = `${nombre}: coordenadas no configuradas`;
                    }
                    setCalendarEventsForZone({ id, nombres: nombre });
                }

                // Init
                initCalendar();
                fetchMisZonas().then(() => {
                    zonaSelect.addEventListener('change', onZonaChange);
                });
            })();
        </script>