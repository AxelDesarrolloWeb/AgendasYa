<?php

namespace Controllers;

use Model\ClimaLectura;

class APIClimaLecturas
{
    private static function ensureSession()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    private static function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    // GET /api/clima/proveedor-actual?fecha=YYYY-MM-DD
    // Regla:
    // - Si fecha != hoy => open-meteo
    // - Si fecha == hoy => 'owm' si llamadas OWM de hoy < 1000; si no, 'open-meteo'
    public static function proveedorActual()
    {
        self::ensureSession();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $fecha = preg_replace('/[^0-9\-]/', '', $fecha);
        $hoy = date('Y-m-d');

        $provider = 'open-meteo';
        $owmCount = 0;
        $limit = 1000;

        if ($fecha === $hoy) {
            $owmCount = ClimaLectura::owmCallsToday();
            $provider = ($owmCount < $limit) ? 'owm' : 'open-meteo';
        }

        self::json([
            'ok' => true,
            'provider' => $provider,
            'owm_count_today' => $owmCount,
            'owm_daily_limit' => $limit,
            'date' => $fecha,
        ]);
    }

    // GET /api/clima/estado-actual?zona_id=123
    // Devuelve el slot actual (inicio/fin) y si existe lectura guardada para ese slot (cualquier provider)
    public static function estado()
    {
        self::ensureSession();
        $uid = $_SESSION['id'] ?? null;
        if (!$uid) {
            return self::json(['ok' => false, 'error' => 'No autenticado'], 401);
        }
        $zona_id = isset($_GET['zona_id']) ? (int)$_GET['zona_id'] : 0;
        if ($zona_id <= 0) {
            return self::json(['ok' => false, 'error' => 'zona_id inválida'], 400);
        }

        $slot_inicio = date('Y-m-d H:00:00');
        $slot_fin = date('Y-m-d H:59:59');

        // Buscar cualquier lectura de ese slot para la zona
        // Nota: evitamos acceder a propiedades protegidas ($db, $tabla) desde el controlador
        $query = "SELECT * FROM clima_lecturas WHERE zona_id={$zona_id} AND slot_inicio='{$slot_inicio}' ORDER BY id DESC LIMIT 1";
        $arr = ClimaLectura::consultarSQL($query);
        $lectura = array_shift($arr);

        $outLectura = null;
        if ($lectura) {
            $outLectura = [
                'id' => (int)$lectura->id,
                'provider' => $lectura->provider,
                'slot_inicio' => $lectura->slot_inicio,
                'slot_fin' => $lectura->slot_fin,
                'lat' => $lectura->lat,
                'lon' => $lectura->lon,
                'payload' => json_decode($lectura->payload, true),
                'created_at' => $lectura->created_at,
            ];
        }

        self::json([
            'ok' => true,
            'slot' => [ 'inicio' => $slot_inicio, 'fin' => $slot_fin ],
            'lectura' => $outLectura,
        ]);
    }

    // POST /api/clima/guardar
    // Body: JSON o application/x-www-form-urlencoded con
    //   zona_id, provider ('owm'|'open-meteo'), lat, lon, payload (JSON string o array)
    // Regla anti-límite: si provider='owm' y ya se llegó a 1000 y NO existe aún lectura para este slot,
    // rechaza con 429 para que el front reintente con open-meteo.
    public static function guardar()
    {
        self::ensureSession();
        $uid = $_SESSION['id'] ?? null;
        if (!$uid) {
            return self::json(['ok' => false, 'error' => 'No autenticado'], 401);
        }

        // Leer body JSON si corresponde
        $input = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);
            if (is_array($json)) $input = $json;
        }

        $zona_id = isset($input['zona_id']) ? (int)$input['zona_id'] : 0;
        $provider = isset($input['provider']) ? strtolower(trim($input['provider'])) : '';
        $lat = isset($input['lat']) ? (float)$input['lat'] : null;
        $lon = isset($input['lon']) ? (float)$input['lon'] : null;
        $payload = $input['payload'] ?? null;

        if ($zona_id <= 0 || !in_array($provider, ['owm','open-meteo'])) {
            return self::json(['ok' => false, 'error' => 'Parámetros inválidos'], 400);
        }
        if (!is_array($payload)) {
            // permitir string JSON
            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $payload = $decoded;
                }
            }
        }
        if (!is_array($payload)) {
            return self::json(['ok' => false, 'error' => 'payload inválido'], 400);
        }

        $slot_inicio = date('Y-m-d H:00:00');
        $slot_fin = date('Y-m-d H:59:59');
        $ahora = date('Y-m-d H:i:s');

        // Ver si ya existe una lectura para este slot+zona+provider
        $existente = ClimaLectura::findByZonaProviderSlot($zona_id, $provider, $slot_inicio);

        // Control de límite OWM cuando NO existe previa lectura
        if (!$existente && $provider === 'owm') {
            $owmCount = ClimaLectura::owmCallsToday();
            if ($owmCount >= 1000) {
                return self::json([
                    'ok' => false,
                    'error' => 'owm_limit_reached',
                    'mensaje' => 'Límite diario de OpenWeather alcanzado. Use Open-Meteo.',
                ], 429);
            }
        }

        if ($existente) {
            // actualizar
            $existente->lat = $lat;
            $existente->lon = $lon;
            $existente->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $existente->created_at = $ahora;
            $ok = $existente->actualizar();
            return self::json(['ok' => (bool)$ok, 'actualizado' => true, 'id' => $existente->id]);
        } else {
            // crear
            $reg = new ClimaLectura();
            $reg->usuario_id = $uid;
            $reg->zona_id = $zona_id;
            $reg->provider = $provider;
            $reg->slot_inicio = $slot_inicio;
            $reg->slot_fin = $slot_fin;
            $reg->lat = $lat;
            $reg->lon = $lon;
            $reg->payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $reg->created_at = $ahora;
            $res = $reg->guardar();
            return self::json(['ok' => (bool)$res['resultado'], 'id' => $res['id']]);
        }
    }
}
