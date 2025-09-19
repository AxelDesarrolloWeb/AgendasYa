<?php

namespace Model;

class ClimaLectura extends ActiveRecord
{
    protected static $tabla = 'clima_lecturas';
    protected static $columnasDB = [
        'id',
        'usuario_id',
        'zona_id',
        'provider', // 'owm' | 'open-meteo'
        'slot_inicio', // DATETIME
        'slot_fin',    // DATETIME
        'lat',         // DECIMAL(10,7)
        'lon',         // DECIMAL(10,7)
        'payload',     // TEXT/JSON
        'created_at'   // DATETIME
    ];

    public $id;
    public $usuario_id;
    public $zona_id;
    public $provider;
    public $slot_inicio;
    public $slot_fin;
    public $lat;
    public $lon;
    public $payload;
    public $created_at;

    public static function owmCallsToday(): int
    {
        $today = date('Y-m-d');
        $query = "SELECT COUNT(*) AS c FROM " . static::$tabla . " WHERE provider='owm' AND DATE(created_at) = '{$today}'";
        $res = self::$db->query($query);
        if ($res) {
            $row = $res->fetch_assoc();
            return (int)($row['c'] ?? 0);
        }
        return 0;
    }

    public static function findByZonaProviderSlot($zona_id, $provider, $slot_inicio)
    {
        $zona_id = (int)$zona_id;
        $provider = self::$db->escape_string($provider);
        $slot_inicio = self::$db->escape_string($slot_inicio);
        $query = "SELECT * FROM " . static::$tabla . " WHERE zona_id={$zona_id} AND provider='{$provider}' AND slot_inicio='{$slot_inicio}' LIMIT 1";
        $arr = self::consultarSQL($query);
        return array_shift($arr);
    }
}
