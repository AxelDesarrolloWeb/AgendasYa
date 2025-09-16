<?php

namespace Model;

class Ciudades extends ActiveRecord
{
    protected static $tabla = 'ciudades';
    protected static $columnasDB = ['id', 'nombre'];

    public $id;
    public $nombre;


}
