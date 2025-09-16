<?php

namespace Model;

class Zonas extends ActiveRecord
{
    protected static $tabla = 'zonas';
    protected static $columnasDB = ['id', 'nombres', 'ciudad_id'];

    public $id;
    public $nombres;
    public $ciudad_id;


}
