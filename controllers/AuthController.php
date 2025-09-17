<?php
// ...
namespace Controllers;

require_once __DIR__ . '/../includes/funciones.php';

use MVC\Router;
use Model\Zonas;
use Classes\Email;
use Model\Usuario;
use Model\Ciudades;
use Model\UsuarioZona;

$carpeta_imagenes = CARPETA_IMAGENES;
class AuthController
{
    public static function login(Router $router)
    {

        $alertas = [];


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario = new Usuario($_POST);

            $alertas = $usuario->validarLogin();

            if (empty($alertas)) {
                // Verificar quel el usuario exista
                $usuario = Usuario::where('email', $usuario->email);
                if (!$usuario || !$usuario->confirmado) {
                    Usuario::setAlerta('error', 'El Usuario No Existe o no esta confirmado');
                } else {
                    // El Usuario existe
                    if (password_verify($_POST['password'], $usuario->password)) {

                        // Iniciar la sesión
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['apellido'] = $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['admin'] = $usuario->admin ?? null;

                        // Redirección 
                        if ($usuario->admin) {
                            header('Location: /admin/dashboard');
                        } else {
                            header('Location: /finalizar-registro');
                        }
                    } else {
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();

        // Render a la vista 
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    public static function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();
            $_SESSION = [];
            header('Location: /');
        }
    }

    public static function registro(Router $router)
    {
        $alertas = [];
        $usuario = new Usuario;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $usuario->sincronizar($_POST);

            $alertas = $usuario->validar_cuenta();

            if (empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);

                $permitidos = ['image/jpeg', 'image/png'];
                if (in_array($_FILES['imagen']['type'], $permitidos)) {
                    // Procesar imagen
                    // Procesa la imagen si se subió
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name']) {
                        $carpeta_imagenes = __DIR__ . '/../public/imagenes/';
                        if (!is_dir($carpeta_imagenes)) {
                            mkdir($carpeta_imagenes, 0777, true);
                        }
                        $nombreImagen = md5(uniqid(rand(), true)) . ".jpg";
                        move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta_imagenes . $nombreImagen);
                        $usuario->imagen = $nombreImagen;
                    }
                }

                if ($existeUsuario) {
                    Usuario::setAlerta('error', 'El Usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    // Hashear el password
                    $usuario->hashPassword();

                    // Eliminar password2
                    unset($usuario->password2);

                    // Generar el Token
                    $usuario->crearToken();

                    // Crear un nuevo usuario
                    $resultado =  $usuario->guardar();

                    // Enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();


                    if ($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }

        // Render a la vista
        $router->render('auth/registro', [
            'titulo' => 'Crea tu cuenta en AgendasYa',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }


    public static function perfil(Router $router)
    {
        session_start();
        if (!isset($_SESSION['id'])) {
            header('Location: /login');
            exit;
        }

        $usuario = Usuario::find($_SESSION['id']);
        $alertas = [];

        $ciudades = Ciudades::all();
        $zonas = Zonas::all();
        // Agrupa zonas por ciudad
        $zonasPorCiudad = [];
        foreach ($zonas as $zona) {
            $zonasPorCiudad[$zona->ciudad_id][] = $zona;
        }

        // ...existing code...
        $zonas_ids = isset($_POST['zonas_ids']) ? explode(',', $_POST['zonas_ids']) : [];
        $ciudad_id = $_POST['ciudad_id'] ?? null;

        // Validar que todas las zonas existen y pertenecen a la ciudad seleccionada
        $zonas_validas = [];
        if ($ciudad_id && !empty($zonas_ids)) {
            foreach ($zonas_ids as $zona_id) {
                $zona = Zonas::find($zona_id);
                if ($zona && $zona->ciudad_id == $ciudad_id) {
                    $zonas_validas[] = $zona_id;
                }
            }
            if (count($zonas_validas) !== count($zonas_ids)) {
                $alertas['error'][] = 'Una o más zonas seleccionadas no corresponden a la ciudad elegida.';
            }
        }
        // Elimina zonas anteriores
        UsuarioZona::eliminarPorUsuario($usuario->id);

        // Guarda nuevas zonas
        foreach ($zonas_validas as $zona_id) {
            $usuarioZona = new UsuarioZona();
            $usuarioZona->usuario_id = $usuario->id;
            $usuarioZona->zona_id = $zona_id;
            $usuarioZona->guardar();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sincroniza los datos del usuario
            $usuario->sincronizar($_POST);

            $permitidos = ['image/jpeg', 'image/png'];
            if (in_array($_FILES['imagen']['type'], $permitidos)) {
                // Procesar imagen
                // Procesa la imagen si se subió
                if (isset($_FILES['imagen']) && $_FILES['imagen']['tmp_name']) {
                    $carpeta_imagenes = __DIR__ . '/../public/imagenes/';
                    if (!is_dir($carpeta_imagenes)) {
                        mkdir($carpeta_imagenes, 0777, true);
                    }
                    $nombreImagen = md5(uniqid(rand(), true)) . ".jpg";
                    move_uploaded_file($_FILES['imagen']['tmp_name'], $carpeta_imagenes . $nombreImagen);
                    $usuario->imagen = $nombreImagen;
                }
            }


            $alertas = $usuario->validar_cuenta();

            if (empty($alertas)) {
                if (!empty($usuario->password)) {
                    // Solo hashea si el password es nuevo y no está hasheado
                    if (strlen($usuario->password) < 60) {
                        $usuario->password = password_hash($usuario->password, PASSWORD_BCRYPT);
                    }
                }
                $usuario->guardar();
                $alertas['exito'][] = 'Perfil actualizado correctamente';
            }
        }


        $zonas_ids = isset($_POST['zonas_ids']) ? explode(',', $_POST['zonas_ids']) : [];
        // Valida que las zonas existan y pertenezcan a la ciudad seleccionada

        $router->render('auth/perfil', [
            'titulo' => 'Mi Perfil',
            'usuario' => $usuario,
            'zonas' => $zonas,
            'ciudades' => $ciudades,
            'zonasPorCiudad' => $zonasPorCiudad,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
                // Buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);

                if ($usuario && $usuario->confirmado) {

                    // Generar un nuevo token
                    $usuario->crearToken();

                    unset($usuario->password2);

                    // Actualizar el usuario
                    $usuario->guardar();

                    // Enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();


                    // Imprimir la alerta
                    // Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');

                    $alertas['exito'][] = 'Hemos enviado las instrucciones a tu email';
                } else {

                    // Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');

                    $alertas['error'][] = 'El Usuario no existe o no esta confirmado';
                }
            }
        }


        // Muestra la vista
        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi Password',
            'alertas' => $alertas
        ]);
    }

    public static function reestablecer(Router $router)
    {

        $token = s($_GET['token']);

        $token_valido = true;

        if (!$token) header('Location: /');

        // Identificar el usuario con este token
        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido, intenta de nuevo');
            $token_valido = false;
        }


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Añadir el nuevo password
            $usuario->sincronizar($_POST);

            // Validar el password
            $alertas = $usuario->validarPassword();

            if (empty($alertas)) {
                // Hashear el nuevo password
                $usuario->hashPassword();

                // Eliminar el Token
                $usuario->token = null;

                // Guardar el usuario en la BD
                $resultado = $usuario->guardar();

                // Redireccionar
                if ($resultado) {
                    header('Location: /login');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        // Muestra la vista
        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer Password',
            'alertas' => $alertas,
            'token_valido' => $token_valido
        ]);
    }

    public static function mensaje(Router $router)
    {

        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }

    public static function confirmar(Router $router)
    {

        $token = s($_GET['token']);

        if (!$token) header('Location: /');

        // Encontrar al usuario con este token
        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            // No se encontró un usuario con ese token
            Usuario::setAlerta('error', 'Token No Válido, la cuenta no se confirmó');
        } else {
            // Confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token = '';
            unset($usuario->password2);

            // Guardar en la BD
            $usuario->guardar();

            Usuario::setAlerta('exito', 'Cuenta Comprobada éxitosamente');
        }



        $router->render('auth/confirmar', [
            'titulo' => 'Confirma tu cuenta DevWebcamp',
            'alertas' => Usuario::getAlertas()
        ]);
    }
}
