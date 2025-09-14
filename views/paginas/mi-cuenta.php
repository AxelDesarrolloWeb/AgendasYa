<main class="agendasya">
    <h2 class="agendasya__heading"><?php echo $titulo; ?></h2>
    <p class="agendasya__descripcion">Bienvenidx a tu cuenta</p>

    <main class="contenedor seccion">
    <h1>Contacto</h1>

    <?php
    if ($mensaje) { ?>
        <p class='alerta exito'> <?php echo $mensaje; ?> </p>;
    <?php } ?>


    <picture>
        <source srcset="build/img/destacada3.webp" type="image/webp">
        <source srcset="build/img/destacada3.jpg" type="image/jpeg">
        <img loading="lazy" src="build/img/destacada3.jpg" alt="Imagen de contacto">
    </picture>

    <h2>Llene el formulario de contacto</h2>

    <form class="formulario" action="/contacto" method="POST">
        <fieldset>
            <legend>Informacion personal</legend>
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" placeholder="Tu nombre" name="contacto[nombre]" required>

            <label for="mensaje">Mensaje:</label>
            <textarea id="mensaje" placeholder="Tu mensaje" name="contacto[mensaje]" required></textarea>
        </fieldset>
        <fieldset>
            <legend>Información sobre la propiedad</legend>
            <label for="opciones">Vende o compra:</label>
            <select id="opciones" name="contacto[tipo]" required>
                <option value="" disabled selected>-- Seleccione --</option>
                <option value="venta">Venta</option>
                <option value="compra">Compra</option>
            </select>

            <label for="presupuesto">Presupuesto:</label>
            <input type="number" id="presupuesto" placeholder="Tu precio o presupuesto" name="contacto[presupuesto]" required>
        </fieldset>
        <fieldset>
            <legend>Contacto</legend>
            <p>Como desea ser contactado</p>

            <div class="forma-contacto">
                <label for="contacto-telefono">Teléfono:</label>
                <input type="radio" value="telefono" id="contactar-telefono" name="contacto[contacto]" required>

                <label for="contacto-email">Email:</label>
                <input type="radio" value="email" id="contactar-email" name="contacto[contacto]" required>
            </div>

            <div id="contacto"></div>

        </fieldset>
        <input type="submit" value="Enviar" class="boton-verde">
    </form>
</main>
</main>