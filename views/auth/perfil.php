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

        <!-- Selector de ciudad -->
        <div class="formulario__campo">
            <label for="ciudad" class="formulario__label">Ciudad (1 por cuenta)</label>
            <select class="formulario__select" id="ciudad" name="ciudad_id">
                <option value="">- Seleccionar Ciudad -</option>
                <?php foreach ($ciudades as $ciudad) { ?>
                    <option value="<?php echo $ciudad->id; ?>" <?php echo ((string)($usuario->ciudad_id ?? '')) === (string)$ciudad->id ? 'selected' : '' ?>>
                        <?php echo $ciudad->nombre; ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <!-- Selector de zonas (multi-selección tipo horas) -->
        <div class="formulario__campo">
            <label class="formulario__label">Seleccionar Zonas</label>
            <ul id="zonas" class="horas zonas"></ul>
            <input type="hidden" name="zonas_ids" id="zonas_ids" value="<?php echo !empty($zonas_ids_usuario) ? implode(',', $zonas_ids_usuario) : ''; ?>">

            <div id="mensaje-zonas" class="formulario__mensaje"></div>
        </div>

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

<script>
    const zonasPorCiudad = <?php echo json_encode($zonasPorCiudad); ?>;
    const ciudadSelect = document.getElementById('ciudad');
    const zonasUl = document.getElementById('zonas');
    const zonasInput = document.getElementById('zonas_ids');
    const mensajeZonas = document.getElementById('mensaje-zonas');
    const zonasUsuario = zonasInput.value ? zonasInput.value.split(',') : [];

    function actualizarZonas() {
        const ciudadId = ciudadSelect.value;
        zonasUl.innerHTML = '';
        if (!ciudadId) {
            mensajeZonas.textContent = 'Selecciona una ciudad para mostrar sus zonas de logeo.';
            return;
        }
        mensajeZonas.textContent = '';
        if (zonasPorCiudad[ciudadId]) {
            zonasPorCiudad[ciudadId].forEach(zona => {
                const li = document.createElement('li');
                li.classList.add('horas__hora');
                li.textContent = zona.nombres;
                li.dataset.zonaId = zona.id;
                if (zonasUsuario.includes(zona.id.toString())) {
                    li.classList.add('horas__hora--seleccionada');
                }
                li.addEventListener('click', function() {
                    li.classList.toggle('horas__hora--seleccionada');
                    actualizarInputZonas();
                });
                zonasUl.appendChild(li);
            });
        }
        actualizarInputZonas();
    }

    function actualizarInputZonas() {
        const seleccionadas = [];
        zonasUl.querySelectorAll('.horas__hora--seleccionada').forEach(li => {
            seleccionadas.push(li.dataset.zonaId);
        });
        zonasInput.value = seleccionadas.join(',');
    }

    ciudadSelect.addEventListener('change', actualizarZonas);
    window.addEventListener('DOMContentLoaded', actualizarZonas);
</script>