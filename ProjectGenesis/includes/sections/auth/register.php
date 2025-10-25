<div class="section-content <?php echo (strpos($CURRENT_SECTION, 'register-') === 0) ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">
    <div class="auth-container">
        <h1 class="auth-title">Crea una cuenta</h1>
        
        <form class="auth-form" id="register-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>
            <div class="auth-error-message" id="register-error" style="display: none;"></div>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 1) ? 'active' : ''; ?>" data-step="1" <?php echo ($CURRENT_REGISTER_STEP != 1) ? 'style="display: none;"' : ''; ?>>
                <p class="auth-step-indicator">Paso 1 de 3: Tu cuenta</p>
                <div class="auth-input-group">
                    <input type="email" id="register-email" name="email" required placeholder=" ">
                    <label for="register-email">Dirección de correo electrónico*</label>
                </div>

                <div class="auth-input-group">
                    <input type="password" id="register-password" name="password" required placeholder=" ">
                    <label for="register-password">Contraseña*</label>
                    <button type="button" class="auth-toggle-password" data-toggle="register-password">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step">Continuar</button>
                </div>
            </fieldset>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 2) ? 'active' : ''; ?>" data-step="2" <?php echo ($CURRENT_REGISTER_STEP != 2) ? 'style="display: none;"' : ''; ?>>
                <p class="auth-step-indicator">Paso 2 de 3: Tu perfil</p>
                <div class="auth-input-group">
                    <input type="text" id="register-username" name="username" required placeholder=" ">
                    <label for="register-username">Nombre de usuario*</label>
                </div>

                <div class="auth-step-buttons">
                    <a href="<?php echo $basePath; ?>/register" class="auth-button-back" style="text-align: center; text-decoration: none; padding: 0 20px; line-height: 52px;">Atrás</a>
                    <button type="button" class="auth-button" data-auth-action="next-step">Continuar</button>
                </div>
            </fieldset>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 3) ? 'active' : ''; ?>" data-step="3" <?php echo ($CURRENT_REGISTER_STEP != 3) ? 'style="display: none;"' : ''; ?>>
                <p class="auth-step-indicator">Paso 3 de 3: Verificación</p>
                
                <p class="auth-verification-text">
                    Te hemos enviado (simulado) un código de verificación. 
                    Por favor, ingrésalo para finalizar tu registro.
                </p>

                <div class="auth-input-group">
                    <input type="text" id="register-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="register-code">Código de Verificación*</label>
                </div>
                
                <div class="auth-step-buttons">
                    <a href="<?php echo $basePath; ?>/register/additional-data" class="auth-button-back" style="text-align: center; text-decoration: none; padding: 0 20px; line-height: 52px;">Atrás</a>
                    <button type="submit" class="auth-button">Verificar y Crear Cuenta</button>
                </div>
            </fieldset>
            
        </form>
        
        <p class="auth-link">
            ¿Ya tienes una cuenta? <a href="/ProjectGenesis/login">Inicia sesión</a>
        </p>
    </div>
</div>