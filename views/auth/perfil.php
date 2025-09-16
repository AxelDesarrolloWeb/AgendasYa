<main class="auth">
    <h2 class="auth__heading"><?php echo $titulo; ?></h2>
    <p class="auth__texto">Tu Cuenta en AgendasYa</p>

    <?php
    require_once __DIR__ . '/../templates/alertas.php';
    ?>

    <form class="formulario" method="POST" action="/actualizar" enctype="multipart/form-data">
        <div class="formulario__campo">
            <label class="formulario__label" for="nombre">Nombre</label>
            <input
                type="text"
                class="formulario__input"
                placeholder="Tu nombre"
                id="nombre"
                name="nombre"
                value="<?php echo $usuario->nombre ?>" />
        </div>
        <div class="formulario__campo">
            <label class="formulario__label" for="apellido">Apellido</label>
            <input
                type="text"
                class="formulario__input"
                placeholder="Tu apellido"
                id="apellido"
                name="apellido"
                value="<?php echo $usuario->apellido ?>" />
        </div>

        <div class="formulario__campo">
            <label class="formulario__label" for="email">Email</label>
            <input
                type="text"
                class="formulario__input"
                placeholder="Tu email"
                id="email"
                name="email"
                value="<?php echo $usuario->email ?>" />
        </div>

        <div class="formulario__campo">
            <label class="formulario__label" for="imagen">Imagen del perfil:</label>
            <input class="formulario__input" type="file" id="imagen" accept="image/jpeg, image/png" name="imagen">

            <?php if ($usuario->imagen) { ?>
                <img src="/imagenes/<?php echo $usuario->imagen ?>" class="imagen-small">
            <?php } ?>
        </div>

        <div class="formulario__campo">
            <label for="cuidad" class="formulario__label">Ciudad (1 por cuenta)</label>
            <select
                class="formulario__select"
                id="cuidad"
                name="cuidad_id">
                <option value="">- Seleccionar Ciudad -</option>
                <?php foreach ($ciudades as $ciudad) { ?>
                    <option <?php echo ($usuario->ciudad_id === $ciudad->id) ? 'selected' : '' ?> value="<?php echo $ciudad->id; ?>"><?php echo $ciudad->nombre; ?></option>
                <?php } ?>
            </select>
        </div>


        <!-- Imprimir las zonas de logeo con su respectiva ciudad aquí o un link a su página de ejemplo. -->

        <div id="zonas" class="formulario__campo">
            <label class="formulario__label">Seleccionar Zonas</label>

            <ul id="zonas" class="zonas">
                <?php foreach ($zonas as $zona) { ?>
                    <li data-zona-id="<?php echo $zona->id; ?>" class="zonas__zona zonas__zona--deshabilitada"><?php echo $zona->nombres; ?></li>
                <?php } ?>
            </ul>

            <input type="hidden" name="zona_id" value="<?php echo $evento->zona_id; ?>">
        </div>

        <!-- <div class="formulario__campo">
            <label for="zonas_input" class="formulario__label">Zonas de Logeo (separadas por coma)</label>
            <input
                type="text"
                class="formulario__input"
                id="zonas_input"
                placeholder="Añade tus zonas de logeo">

            <div id="zonas" class="formulario__listado"></div>
            <input type="hidden" name="zonas" value="<php echo $usuario->zonas ?? ''; ?>">
        </div> -->

        <div class="formulario__campo">
            <label class="formulario__label" for="password">Password</label>
            <input
                type="password"
                class="formulario__input"
                placeholder="Tu Password"
                id="password"
                name="password" />
        </div>

        <div class="formulario__campo">
            <label class="formulario__label" for="password2">Repetir password</label>
            <input
                type="password"
                class="formulario__input"
                placeholder="Tu password"
                id="password2"
                name="password2" />
        </div>

        <input type="submit" class="formulario__submit" value="Actualizar Perfil">
    </form>

    <!-- <div class="acciones">
        <a href="/login" class="acciones__enlace">¿Ya tienes una cuenta? Iniciar Sesión</a>
        <a href="/olvide" class="acciones__enlace">¿Olvidaste tu Password? Resetear Password</a>
    </div> -->
</main>