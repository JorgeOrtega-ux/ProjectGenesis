<div class="section-content <?php echo (strpos($CURRENT_SECTION, 'register-') === 0) ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">
    <div class="auth-container">
        <h1 class="auth-title">Crea una cuenta</h1>
        
        <form class="auth-form" id="register-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 1) ? 'active' : ''; ?>" data-step="1" <?php echo ($CURRENT_REGISTER_STEP != 1) ? 'style="display: none;"' : ''; ?>>
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
                <div class="auth-error-message" style="display: none;"></div>
            </fieldset>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 2) ? 'active' : ''; ?>" data-step="2" <?php echo ($CURRENT_REGISTER_STEP != 2) ? 'style="display: none;"' : ''; ?>>
                <div class="auth-input-group">
                    <input type="text" id="register-username" name="username" required placeholder=" ">
                    <label for="register-username">Nombre de usuario*</label>
                </div>

                <div class="auth-step-buttons">
                    <button type="button" class="auth-button" data-auth-action="next-step">Continuar</button>
                </div>
                <div class="auth-error-message" style="display: none;"></div>
            </fieldset>

            <fieldset class="auth-step <?php echo ($CURRENT_REGISTER_STEP == 3) ? 'active' : ''; ?>" data-step="3" <?php echo ($CURRENT_REGISTER_STEP != 3) ? 'style="display: none;"' : ''; ?>>
                
                <p class="auth-verification-text">
                    Te hemos enviado un código de verificación al correo
                    <strong><?php echo htmlspecialchars($_SESSION['registration_email'] ?? 'tu correo'); ?></strong>.
                    Por favor, ingrésalo para finalizar tu registro.
                </p>

                <div class="auth-input-group">
                    <input type="text" id="register-code" name="verification_code" required placeholder=" " maxlength="14">
                    <label for="register-code">Código de Verificación*</label>
                </div>
                
                <div class="auth-step-buttons">
                    <button type="submit" class="auth-button">Verificar y Crear Cuenta</button>
                </div>
                
                <div class="auth-error-message" style="display: none;"></div>
                
                <p class="auth-link" style="text-align: center;">
                    <a href="#" 
                       id="register-resend-code-link" 
                       data-auth-action="resend-code"
                       data-cooldown="<?php echo isset($initialCooldown) ? $initialCooldown : 0; ?>"
                       class="<?php echo (isset($initialCooldown) && $initialCooldown > 0) ? 'disabled-interactive' : ''; ?>"
                       style="<?php echo (isset($initialCooldown) && $initialCooldown > 0) ? 'opacity: 0.7; text-decoration: none;' : ''; ?>"
                    >
                       <?php // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ --- ?>
                       <?php 
                       $linkText = "Reenviar código de verificación";
                       if (isset($initialCooldown) && $initialCooldown > 0) {
                           // Ya está en cooldown al cargar, muestra el tiempo
                           // (JS lo actualizará dinámicamente)
                           echo htmlspecialchars($linkText . " (" . $initialCooldown . "s)");
                       } else {
                           // No hay cooldown, muestra el texto normal
                           echo htmlspecialchars($linkText);
                       }
                       ?>
                       <?php // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ --- ?>
                    </a>
                </p>
                </fieldset>
            
            </form>
        
        <?php if ($CURRENT_REGISTER_STEP == 1): ?>
        <p class="auth-link">
            ¿Ya tienes una cuenta? <a href="/ProjectGenesis/login">Inicia sesión</a>
        </p>
        <?php endif; ?>
    </div>
</div>