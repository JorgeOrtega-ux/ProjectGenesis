<div class="section-content <?php echo ($CURRENT_SECTION === 'login') ? 'active' : 'disabled'; ?>" data-section="login">
    <div class="auth-container">
        <h1 class="auth-title">Iniciar sesión</h1>
        
        <form class="auth-form" id="login-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>

            <fieldset class="auth-step active" data-step="1">
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

                <p class="auth-link" style="text-align: right;">
                    <a href="/ProjectGenesis/reset-password">¿Olvidaste tu contraseña?</a>
                </p>
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step">Continuar</button>
                </div>
                <div class="auth-error-message" style="display: none;"></div>
            </fieldset>
            <fieldset class="auth-step" data-step="2" style="display: none;">
                <p class="auth-verification-text" style="margin-bottom: 16px;">
                    Se ha enviado un código de verificación a tu correo. 
                    Por favor, ingrésalo para continuar.
                </p>

                <div class="auth-input-group">
                    <input type="text" id="login-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="login-code">Código de Verificación*</label>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button-back" data-auth-action="prev-step">Atrás</button>
                    <button type="submit" class="auth-button">Verificar e Ingresar</button>
                </div>
                <div class="auth-error-message" style="display: none;"></div>
            </fieldset>
            
            </form>

        <p class="auth-link">
            ¿No tienes una cuenta? <a href="/ProjectGenesis/register">Crear cuenta</a>
        </p>
    </div>
</div>