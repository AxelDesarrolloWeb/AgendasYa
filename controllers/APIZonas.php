<?php

namespace Controllers;

use Model\UsuarioZona;
use Model\Zonas;

class APIZonas
{
    public static function misZonas()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!isset($_SESSION)) {
            session_start();
        }
        $uid = $_SESSION['id'] ?? null;
        if (!$uid) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'error' => 'No autenticado'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Obtener asignaciones usuario-zona
        $relaciones = UsuarioZona::whereArray(['usuario_id' => $uid]) ?? [];
        $zonas = [];
        foreach ($relaciones as $rel) {
            // $rel es instancia de UsuarioZona con ->zona_id
            $z = Zonas::find($rel->zona_id);
            if ($z) {
                $zonas[] = [
                    'id' => (int)$z->id,
                    'nombres' => $z->nombres,
                    'ciudad_id' => isset($z->ciudad_id) ? (int)$z->ciudad_id : null,
                    'lat' => isset($z->lat) ? (float)$z->lat : null,
                    'lon' => isset($z->lon) ? (float)$z->lon : null,
                ];
            }
        }

        echo json_encode([
            'ok' => true,
            'zonas' => $zonas
        ], JSON_UNESCAPED_UNICODE);
    }
}
