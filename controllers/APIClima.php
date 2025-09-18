<?php

namespace Controllers;

class APIClima
{
    public static function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Validar lat y lon
        $lat = isset($_GET['lat']) ? filter_var($_GET['lat'], FILTER_VALIDATE_FLOAT) : null;
        $lon = isset($_GET['lon']) ? filter_var($_GET['lon'], FILTER_VALIDATE_FLOAT) : null;

        if ($lat === false || $lon === false || $lat === null || $lon === null) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Parámetros inválidos: se requieren lat y lon válidos'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Leer API Key desde .env (ver includes/app.php)
        $apiKey = $_ENV['OPENWEATHER_API_KEY']
            ?? $_SERVER['OPENWEATHER_API_KEY']
            ?? getenv('OPENWEATHER_API_KEY');

        if (!$apiKey) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Falta OPENWEATHER_API_KEY en includes/.env'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $units = isset($_GET['units']) && in_array($_GET['units'], ['standard', 'metric', 'imperial'])
            ? $_GET['units']
            : 'metric';
        $lang = isset($_GET['lang']) ? preg_replace('/[^a-zA-Z-]/', '', $_GET['lang']) : 'es';

        $url = sprintf(
            'https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&units=%s&lang=%s&appid=%s',
            $lat,
            $lon,
            $units,
            $lang,
            $apiKey
        );

        // Consumir OpenWeather con compatibilidad (cURL o file_get_contents)
        $response = null;
        $status = 0;
        $errDetail = null;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $errDetail = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true // para poder leer el cuerpo aunque sea 4xx/5xx
                ]
            ]);
            $response = @file_get_contents($url, false, $context);
            if (isset($http_response_header) && is_array($http_response_header) && count($http_response_header) > 0) {
                if (preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $m)) {
                    $status = (int)($m[1] ?? 0);
                }
            }
            if ($response === false) {
                $errDetail = 'file_get_contents falló o allow_url_fopen está deshabilitado';
            }
        }

        if ($response === false || $response === null) {
            http_response_code(502);
            echo json_encode([
                'ok' => false,
                'error' => 'Error de conexión con OpenWeather',
                'detalle' => $errDetail
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $data = json_decode($response, true);

        if ($status < 200 || $status >= 300) {
            http_response_code($status ?: 502);
            echo json_encode([
                'ok' => false,
                'error' => 'Respuesta no exitosa de OpenWeather',
                'status' => $status,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Responder directamente los datos de OpenWeather, agregando ok=true
        echo json_encode([
            'ok' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    }
}
