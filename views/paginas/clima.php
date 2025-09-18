<main class="agendasya">
    <h2 class="agendasya__heading"><?php echo $titulo; ?></h2>
    <p class="agendasya__descripcion">Permite el acceso a tu ubicación para ver el clima actual donde estás.</p>

    <section class="clima" style="max-width:700px;margin:1rem auto;padding:1rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff">
        <button id="btnSolicitarUbicacion" class="boton" style="padding:.6rem 1rem;border:none;border-radius:6px;background:#2563eb;color:#fff;cursor:pointer">Obtener clima por mi ubicación</button>

        <div style="margin-top:1rem">
            <label for="inputCiudad" style="display:block;font-weight:600;color:#374151">Buscar por ciudad</label>
            <div style="display:flex;gap:.5rem;margin-top:.25rem">
                <input id="inputCiudad" type="text" placeholder="Ej: Buenos Aires, AR" style="flex:1;padding:.5rem;border:1px solid #d1d5db;border-radius:6px" />
                <button id="btnBuscarCiudad" class="boton" style="padding:.5rem 1rem;background:#059669;color:#fff;border:none;border-radius:6px;cursor:pointer">Buscar</button>
            </div>
        </div>

        <div id="status" style="margin-top:1rem;color:#4b5563"></div>
        <div id="resultado" style="margin-top:1rem;display:none">
            <h3 id="ciudad" style="margin:0 0 .5rem 0"></h3>
            <div id="detalle" style="font-size:1rem;color:#374151"></div>
        </div>
        <pre id="consola" style="margin-top:1rem;background:#0b1020;color:#cbd5e1;padding:.75rem;border-radius:6px;overflow:auto;max-height:300px"></pre>
    </section>

    <script>
        (function(){
            const statusEl = document.getElementById('status');
            const resultado = document.getElementById('resultado');
            const ciudadEl = document.getElementById('ciudad');
            const detalleEl = document.getElementById('detalle');
            const consolaEl = document.getElementById('consola');
            const btnUbic = document.getElementById('btnSolicitarUbicacion');
            const inputCiudad = document.getElementById('inputCiudad');
            const btnBuscarCiudad = document.getElementById('btnBuscarCiudad');

            const OPENWEATHER_API_KEY = <?php echo json_encode($_ENV['OPENWEATHER_API_KEY'] ?? ($_SERVER['OPENWEATHER_API_KEY'] ?? '')); ?>;

            function log(msg, obj) {
                const line = typeof obj !== 'undefined' ? `${msg} ${JSON.stringify(obj, null, 2)}` : msg;
                consolaEl.textContent += (consolaEl.textContent ? '\n' : '') + line;
                console.log(msg, obj ?? '');
            }

            function renderClima(w, origen = ''){
                const nombre = w.name || (origen || 'Ubicación');
                const temp = w.main && typeof w.main.temp !== 'undefined' ? Math.round(w.main.temp) : 'N/D';
                const desc = (w.weather && w.weather[0] && w.weather[0].description) ? w.weather[0].description : 'Sin datos';

                ciudadEl.textContent = `${nombre}`;
                detalleEl.innerHTML = `
                    <div><strong>Temperatura:</strong> ${temp}°C</div>
                    <div><strong>Condición:</strong> ${desc.charAt(0).toUpperCase() + desc.slice(1)}</div>
                    <div style="margin-top:.5rem;font-size:.9rem;color:#6b7280">(También se muestra la respuesta completa en la consola del navegador)</div>
                `;
                resultado.style.display = 'block';
                statusEl.textContent = 'Listo';
            }

            async function obtenerClimaPorCoords(lat, lon){
                statusEl.textContent = 'Consultando clima por coordenadas...';
                if(!OPENWEATHER_API_KEY){
                    const msg = 'OPENWEATHER_API_KEY no está configurada en el servidor';
                    log(msg);
                    alert(msg);
                    return;
                }
                try {
                    const url = `https://api.openweathermap.org/data/2.5/weather?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}&units=metric&lang=es&appid=${OPENWEATHER_API_KEY}`;
                    const resp = await fetch(url);
                    const data = await resp.json();
                    log('Respuesta OpenWeather (coords):', data);
                    if (data.cod && +data.cod !== 200) {
                        throw new Error(data.message || 'No se pudo obtener el clima');
                    }
                    renderClima(data);
                } catch (e) {
                    statusEl.textContent = 'Ocurrió un error al obtener el clima';
                    log('Error obteniendo clima (coords):', { message: e.message });
                    alert('No se pudo obtener el clima. Revisa la consola para más detalles.');
                }
            }

            async function obtenerClimaPorCiudad(q){
                statusEl.textContent = 'Buscando clima por ciudad...';
                if(!OPENWEATHER_API_KEY){
                    const msg = 'OPENWEATHER_API_KEY no está configurada en el servidor';
                    log(msg);
                    alert(msg);
                    return;
                }
                try {
                    const url = `https://api.openweathermap.org/data/2.5/weather?q=${encodeURIComponent(q)}&units=metric&lang=es&appid=${OPENWEATHER_API_KEY}`;
                    const resp = await fetch(url);
                    const data = await resp.json();
                    log('Respuesta OpenWeather (ciudad):', data);
                    if (data.cod && +data.cod !== 200) {
                        throw new Error(data.message || 'No se pudo obtener el clima');
                    }
                    renderClima(data, q);
                } catch (e) {
                    statusEl.textContent = 'Ocurrió un error al obtener el clima';
                    log('Error obteniendo clima (ciudad):', { message: e.message });
                    alert('No se pudo obtener el clima. Revisa la consola para más detalles.');
                }
            }

            function solicitarUbicacion(){
                if(!('geolocation' in navigator)){
                    statusEl.textContent = 'Tu navegador no soporta geolocalización';
                    log('Geolocalización no soportada');
                    return;
                }
                statusEl.textContent = 'Solicitando permiso de ubicación...';
                navigator.geolocation.getCurrentPosition(
                    pos => {
                        const { latitude, longitude } = pos.coords;
                        log('Ubicación obtenida:', { latitude, longitude });
                        statusEl.textContent = 'Ubicación obtenida. Obteniendo clima...';
                        obtenerClimaPorCoords(latitude, longitude);
                    },
                    err => {
                        let msg = 'Error al obtener la ubicación';
                        if (err.code === err.PERMISSION_DENIED) msg = 'Permiso de ubicación denegado';
                        else if (err.code === err.POSITION_UNAVAILABLE) msg = 'Ubicación no disponible';
                        else if (err.code === err.TIMEOUT) msg = 'Tiempo de espera agotado para la ubicación';
                        statusEl.textContent = msg;
                        log('Error geolocalización:', { code: err.code, message: err.message });
                        alert(msg + '. Puedes buscar por ciudad como alternativa.');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }

            function onBuscarCiudad(){
                const q = (inputCiudad.value || '').trim();
                if(!q){
                    statusEl.textContent = 'Ingresa una ciudad (ej: Buenos Aires, AR)';
                    inputCiudad.focus();
                    return;
                }
                obtenerClimaPorCiudad(q);
            }

            // Eventos
            btnUbic.addEventListener('click', solicitarUbicacion);
            btnBuscarCiudad.addEventListener('click', onBuscarCiudad);
            inputCiudad.addEventListener('keydown', (e) => { if(e.key === 'Enter') onBuscarCiudad(); });

            // Opcional: solicitar ubicación automáticamente
            // setTimeout(() => solicitarUbicacion(), 300);
        })();
    </script>
</main>
