<div class="section-content <?php echo ($CURRENT_SECTION === 'reset-password') ? 'active' : 'disabled'; ?>" data-section="reset-password">
    <div class="auth-container">
        <h1 class="auth-title">Recuperar Contraseña</h1>
        
        <form class="auth-form" id="reset-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>
            <div class="auth-error-message" id="reset-error" style="display: none;"></div>

            <fieldset class="auth-step active" data-step="1">
                <p class="auth-step-indicator">Paso 1 de 3: Verifica tu cuenta</p>
                 <p class="auth-verification-text" style="margin-bottom: 16px;">
                    Ingresa tu correo electrónico y te enviaremos (simulado) un código de recuperación.
                </p>
                <div class="auth-input-group">
                    <input type="email" id="reset-email" name="email" required placeholder=" ">
                    <label for="reset-email">Dirección de correo electrónico*</label>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step">Enviar Código</button>
                </div>
            </fieldset>

            <fieldset class="auth-step" data-step="2" style="display: none;">
                <p class="auth-step-indicator">Paso 2 de 3: Código de Verificación</p>
                <p class="auth-verification-text" style="margin-bottom: 16px;">
                    Revisa tu bandeja de entrada e ingresa el código.
                </p>
                <div class="auth-input-group">
                    <input type="text" id="reset-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="reset-code">Código de Verificación*</label>
                </div>

                <div class="auth-step-buttons">
                    <button type="button" class="auth-button-back" data-auth-action="prev-step">Atrás</button>
                    <button type="button" class="auth-button" data-auth-action="next-step">Verificar</button>
                </div>
            </fieldset>

            <fieldset class="auth-step" data-step="3" style="display: none;">
                <p class="auth-step-indicator">Paso 3 de 3: Nueva Contraseña</p>
                <p class="auth-verification-text" style="margin-bottom: 16px;">
                    Ingresa tu nueva contraseña. Debe tener al menos 8 caracteres.
                </p>

                <div class="auth-input-group">
                    <input type="password" id="reset-password" name="password" required placeholder=" ">
                    <label for="reset-password">Nueva Contraseña*</label>
                    <button type="button" class="auth-toggle-password" data-toggle="reset-password">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>

                 <div class="auth-input-group">
                    <input type="password" id="reset-password-confirm" name="password_confirm" required placeholder=" ">
                    <label for="reset-password-confirm">Confirmar Contraseña*</label>
                    <button type="button" class="auth-toggle-password" data-toggle="reset-password-confirm">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button-back" data-auth-action="prev-step">Atrás</button>
                    <button type="submit" class="auth-button">Guardar y Continuar</button>
                </div>
            </fieldset>
            
        </form>
        
        <p class="auth-link">
            ¿Recordaste tu contraseña? <a href="/ProjectGenesis/login">Inicia sesión</a>
        </p>
    </div>
</div>