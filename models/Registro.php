<?php 

namespace Model;
error_reporting(E_ALL ^ E_DEPRECATED);
class Registro extends ActiveRecord {
    protected static $tabla = 'registros';
    protected static $columnasDB = ['id', 'paquete_id', 'token', 'usuario_id', 'pago_id', 'regalo_id'];


    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->paquete_id = $args['paquete_id'] ?? '';
        $this->token = $args['token'] ?? '';
        $this->usuario_id = $args['usuario_id'] ?? '';
        $this->pago_id = $args['pago_id'] ?? '';
        $this->regalo_id = $args['regalo_id'] ?? 1;
    }

}