import config from '../config/config.jsx';

async function obtenerDatosClima(lat, lon) {
  try {
    const respuesta = await fetch(`${config.urlBase}?q=${lat},${lon}&appid=${config.ApiKey}${config.lang}${config.units}`);
    const datos = await respuesta.json();
    // Procesar los datos de la API
    return datos;
  } catch (error) {
    console.error(error);
    return null;
  }
}