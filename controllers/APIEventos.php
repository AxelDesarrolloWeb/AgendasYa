<?php

namespace Controllers;

use Model\EventoHorario;

class APIEventos {

    public static function index() {
    $dia_id = $_GET['dia_id'] ?? '';
    $categoria_id = $_GET['categoria_id'] ?? '';
    $exclude_evento_id = $_GET['exclude_evento_id'] ?? '';

    $dia_id = filter_var($dia_id, FILTER_VALIDATE_INT);
    $categoria_id = filter_var($categoria_id, FILTER_VALIDATE_INT);

    if(!$dia_id || !$categoria_id) {
        echo json_encode([]);
        return;
    }

    // Consultar la base de datos
    $eventos = EventoHorario::whereArray(['dia_id' => $dia_id, 'categoria_id' => $categoria_id]) ?? [];

    // Excluir evento actual si se especifica
    if ($exclude_evento_id) {
        $exclude_evento_id = filter_var($exclude_evento_id, FILTER_VALIDATE_INT);
        $eventos = array_filter($eventos, function($evento) use ($exclude_evento_id) {
            return $evento->id !== $exclude_evento_id;
        });
    }

    echo json_encode($eventos);
}
}