<div class="section-content <?php echo ($CURRENT_SECTION === 'register') ? 'active' : 'disabled'; ?>" data-section="register">
    <div class="auth-container">
        <h1 class="auth-title">Crea una cuenta</h1>
        <form class="auth-form" onsubmit="event.preventDefault();">
            
            <div class="auth-input-group">
                <input type="email" id="register-email" name="email" required placeholder=" ">
                <label for="register-email">Dirección de correo electrónico*</label>
            </div>

            <div class="auth-input-group">
                <input type="text" id="register-username" name="username" required placeholder=" ">
                <label for="register-username">Nombre de usuario*</label>
            </div>

            <div class="auth-input-group">
                <input type="password" id="register-password" name="password" required placeholder=" ">
                <label for="register-password">Contraseña*</label>
                <button type="button" class="auth-toggle-password" data-toggle="register-password">
                    <span class="material-symbols-rounded">visibility</span>
                </button>
            </div>

            <button type="submit" class="auth-button">Continuar</button>
        </form>
        <p class="auth-link">
            ¿Ya tienes una cuenta? <a href="/ProjectGenesis/login">Inicia sesión</a>
        </p>
    </div>
</div>