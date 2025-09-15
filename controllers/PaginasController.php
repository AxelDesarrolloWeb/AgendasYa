<?php

namespace Controllers;

use Model\Dia;
use Model\Hora;
use MVC\Router;
use Model\Evento;
use Model\Ponente;
use Model\Categoria;

error_reporting(E_ALL ^ E_DEPRECATED);
class PaginasController
{
    public static function index(Router $router)
    {
        $eventos = Evento::ordenar('hora_id', 'ASC');
        $alertas = [];

        $eventos_formateados = [];
        foreach($eventos as $evento) {
            $evento->categoria = Categoria::find($evento->categoria_id);
            $evento->dia = Dia::find($evento->dia_id);
            $evento->hora = Hora::find($evento->hora_id);
            $evento->ponente = Ponente::find($evento->ponente_id);
            
            if($evento->dia_id === "1" && $evento->categoria_id === "1") {
                $eventos_formateados['conferencias_v'][] = $evento;
            }

            if($evento->dia_id === "2" && $evento->categoria_id === "1") {
                $eventos_formateados['conferencias_s'][] = $evento;
            }

            if($evento->dia_id === "1" && $evento->categoria_id === "2") {
                $eventos_formateados['workshops_v'][] = $evento;
            }

            if($evento->dia_id === "2" && $evento->categoria_id === "2") {
                $eventos_formateados['workshops_s'][] = $evento;
            }
        }

        // Obtenre el total de cda blloque
        $ponentes_total = Ponente::total();
        $conferencias_total = Evento::total('categoria_id',1);
        $workshops_total = Evento::total('categoria_id',2);

        // Obtener todos los ponentes_total
        $ponentes = Ponente::all();

        $router->render('paginas/index', [
            'titulo' => 'Inicio',
            'eventos' => $eventos_formateados,
            'ponentes_total' => $ponentes_total,
            'conferencias_total' => $conferencias_total,
            'workshops_total' => $workshops_total,
            'ponentes' => $ponentes
        ]);
    }

    public static function sobre_nosotros(Router $router)
    {

        $router->render('paginas/sobre-nosotros', [
            'titulo' => 'Sobre AgendasYa'
        ]);
    }

    public static function planes(Router $router)
    {

        $router->render('paginas/planes', [
            'titulo' => 'Planes AgendasYa'
        ]);
    }

    public static function publicaciones(Router $router) {

        $eventos = Evento::ordenar('hora_id', 'ASC');

        $eventos_formateados = [];
        foreach($eventos as $evento) {
            $evento->categoria = Categoria::find($evento->categoria_id);
            $evento->dia = Dia::find($evento->dia_id);
            $evento->hora = Hora::find($evento->hora_id);
            $evento->ponente = Ponente::find($evento->ponente_id);
            
            if($evento->dia_id === "1" && $evento->categoria_id === "1") {
                $eventos_formateados['conferencias_v'][] = $evento;
            }

            if($evento->dia_id === "2" && $evento->categoria_id === "1") {
                $eventos_formateados['conferencias_s'][] = $evento;
            }

            if($evento->dia_id === "1" && $evento->categoria_id === "2") {
                $eventos_formateados['workshops_v'][] = $evento;
            }

            if($evento->dia_id === "2" && $evento->categoria_id === "2") {
                $eventos_formateados['workshops_s'][] = $evento;
            }
        }


        $router->render('paginas/conferencias', [
            'titulo' => 'Comentarios & Puntuaciones',
            'eventos' => $eventos_formateados
        ]);
    }

    public static function mapa(Router $router)
    {

        $router->render('paginas/mapa', [
            'titulo' => 'Mapa de Logeo'
        ]);
    }
   

    public static function agendaya(Router $router)
    {

        $router->render('paginas/agendaya', [
            'titulo' => 'Agenda Ya!'
        ]);
    }

    public static function error(Router $router)
    {

        $router->render('paginas/error', [
            'titulo' => 'Error, Página No Encontrada'
        ]);
    }
}
