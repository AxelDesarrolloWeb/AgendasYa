<?php

namespace Model;


class UsuarioZona extends ActiveRecord {
    protected static $tabla = 'usuario_zona';
    protected static $columnasDB = ['id', 'usuario_id', 'zona_id'];

    public static function eliminarPorUsuario($usuario_id) {
        $query = "DELETE FROM usuario_zona WHERE usuario_id = $usuario_id";
        self::$db->query($query);
    }
}