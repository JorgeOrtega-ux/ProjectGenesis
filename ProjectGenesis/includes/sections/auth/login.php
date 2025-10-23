<div class="section-content <?php echo ($CURRENT_SECTION === 'login') ? 'active' : 'disabled'; ?>" data-section="login">
    <div class="auth-container">
        <h1 class="auth-title">Iniciar sesión</h1>
        
        <form class="auth-form" id="login-form" onsubmit="event.preventDefault();" novalidate>
            
            <div class="auth-error-message" id="login-error" style="display: none;"></div>

            <div class="auth-input-group">
                <input type="email" id="login-email" name="email" required placeholder=" ">
                <label for="login-email">Dirección de correo electrónico*</label>
            </div>

            <div class="auth-input-group">
                <input type="password" id="login-password" name="password" required placeholder=" ">
                <label for="login-password">Contraseña*</label>
                <button type="button" class="auth-toggle-password" data-toggle="login-password">
                    <span class="material-symbols-rounded">visibility</span>
                </button>
            </div>

            <button type="submit" class="auth-button">Continuar</button>
        </form>
        <p class="auth-link">
            ¿No tienes una cuenta? <a href="/ProjectGenesis/register">Crea una</a>
        </p>
    </div>
</div>