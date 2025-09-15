<header class="header">
    <div class="header__contenedor">
        <nav class="header__navegacion">

            <?php
            // ...existing code...
            if (function_exists('is_auth') && is_auth()) {
                // Obtén el usuario logeado (ajusta según tu sistema de sesiones)
                $usuario = \Model\Usuario::find($_SESSION['id']);
                $imagenPerfil = $usuario && $usuario->imagen ? '/imagenes/' . $usuario->imagen : '/build/img/mi-cuenta.jpeg';
            }
            ?>
            <!-- ...existing code... -->
            <?php if (function_exists('is_auth') && is_auth()) { ?>
                <a href="<?php echo (function_exists('is_admin') && is_admin()) ? '/admin/dashboard' : '/finalizar-registro'; ?>" class="header__enlace">Administrar</a>
                <form method="POST" action="/logout" class="header__form">
                    <input type="submit" value="Cerrar Sesión" class="header__submit">
                </form>
                <a href="/perfil">
                    <img src="<?php echo $imagenPerfil; ?>" alt="Perfil" class="header__enlace--cuenta" style="width:48px;height:48px;object-fit:cover;border-radius:50%;">
                </a>
            <?php } else { ?>
                <a href="/registro" class="header__enlace">Registro</a>
                <a href="/login" class="header__enlace">Iniciar Sesión</a>

            <?php } ?>
        </nav>

        <div class="header__contenido">
            <a href="/">
                <h1 class="header__logo">
                    &#60;AgendasYa />
                </h1>
            </a>

            <p class="header__texto">Pago Seguro</p>
            <p class="header__texto header__texto--modalidad">Anual - Mensual</p>

            <a href="/registro" class="header__boton">Comprar Plan</a>
        </div>
    </div>
</header>
<div class="barra">
    <div class="barra__contenido">
        <a href="/">
            <h2 class="barra__logo">
                &#60;AgendasYa />
            </h2>
        </a>
        <nav class="navegacion">
            <a href="/planes" class="navegacion__enlace <?php echo pagina_actual('/planes') ? 'navegacion__enlace--actual' : ''; ?>">Planes</a>
            <a href="/registro" class="navegacion__enlace <?php echo pagina_actual('/registro') ? 'navegacion__enlace--actual' : ''; ?>">Comprar Plan</a>
            <a href="/agendaya" class="navegacion__enlace <?php echo pagina_actual('/agendaya') ? 'navegacion__enlace--actual' : ''; ?>">Agenda Ya!</a>
            <a href="/publicaciones" class="navegacion__enlace <?php echo pagina_actual('/publicaciones') ? 'navegacion__enlace--actual' : ''; ?>">Publicaciones</a>
            <a href="/sobre-nosotros" class="navegacion__enlace <?php echo function_exists('pagina_actual') && pagina_actual('/sobre-nosotros') ? 'navegacion__enlace--actual' : ''; ?>">Sobre Nosotros</a>
            <a href="https://www.google.com/maps/d/u/0/viewer?ll=-34.63198345969997%2C-58.56577521582031&z=10&mid=1kAJoB2oAwZ4G-VOgZhWzLHg6zqk_VwI" target="_blank" class="navegacion__enlace <?php echo pagina_actual('/mapa') ? 'navegacion__enlace--actual' : ''; ?>">Mapa Logeo</a>
        </nav>
    </div>
</div>