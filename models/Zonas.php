<?php

namespace Model;

class Zonas extends ActiveRecord
{
    protected static $tabla = 'zonas';
    // Añadir columnas para coordenadas (asegúrate de agregarlas en la BD)
    protected static $columnasDB = ['id', 'nombres', 'ciudad_id', 'lat', 'lon'];

    public $id;
    public $nombres;
    public $ciudad_id;
    public $lat;   // DECIMAL(10,7) recomendado
    public $lon;   // DECIMAL(10,7) recomendado
}