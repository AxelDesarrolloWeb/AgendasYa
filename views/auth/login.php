<main class="auth">
    <h2 class="auth__heading"><?php echo $titulo; ?></h2>
    <p class="auth__texto">Inicia Sesión en DevWebCamp</p>

     <?php 
        require_once __DIR__ . '/../templates/alertas.php';
    ?>
    <form class="formulario" method="POST" action="/login">
        <div class="formulario__campo">
            <label class="formulario__label" for="email">Email</label>
            <input 
            type="text"
            class="formulario__input"
            placeholder="Tu Email"
            id="email"
            name="email"
            />
        </div>

        <div class="formulario__campo">
            <label class="formulario__label" for="password">Password</label>
            <input 
            type="password"
            class="formulario__input"
            placeholder="Tu Password"
            id="password"
            name="password"
            />
        </div>

        <input type="submit" class="formulario__submit" value="Iniciar Sesión">
    </form>

    <div class="acciones">
        <a href="/registro" class="acciones__enlace">¿Aún no tienes una cuenta? Obtener una</a>
        <a href="/olvide" class="acciones__enlace">¿Olvidaste tu Password? Resetear Password</a>
    </div>
</main>