<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-login') ? 'active' : 'disabled'; ?>" data-section="settings-login">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title">Inicio de Sesión y Seguridad</h1>
            <p class="settings-description">
                Gestiona tu contraseña, activa la verificación de dos pasos (2FA) y revisa tu historial de inicio de sesión.
            </p>
        </div>

        <div class="settings-card">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                     <span class="material-symbols-rounded">lock</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Contraseña</h2>
                    <p class="settings-text-description">Actualiza tu contraseña periódicamente para mantener tu cuenta segura.</p>
                </div>
            </div>
            
            <div class="settings-card-right">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button" id="password-edit-trigger">Actualizar</button>
                </div>
            </div>
        </div>
        </div>

    <div class="settings-modal-overlay" id="password-change-modal" style="display: none;">
        
        <button type="button" class="settings-modal-close-btn" id="password-verify-close">
            <span class="material-symbols-rounded">close</span>
        </button>

        <div class="settings-modal-content">

            <form class="auth-form" onsubmit="event.preventDefault();" novalidate>

                <fieldset class="auth-step active" data-step="1">
                    <h2 class="auth-title">Verifica tu identidad</h2>
                    <p class="auth-verification-text">
                        Para continuar, por favor ingresa tu contraseña actual.
                    </p>

                    <div class="auth-error-message" id="password-verify-error" style="display: none;"></div>

                    <div class="auth-input-group">
                        <input type="password" id="password-verify-current" name="current_password" required placeholder=" ">
                        <label for="password-verify-current">Contraseña actual*</label>
                    </div>

                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button" id="password-verify-continue">Continuar</button>
                    </div>
                </fieldset>
                
                <fieldset class="auth-step" data-step="2" style="display: none;">
                    <h2 class="auth-title">Crea una nueva contraseña</h2>
                    <p class="auth-verification-text">
                        Tu nueva contraseña debe tener al menos 8 caracteres.
                    </p>

                    <div class="auth-error-message" id="password-update-error"></div>

                    <div class="auth-input-group">
                        <input type="password" id="password-update-new" name="new_password" required placeholder=" ">
                        <label for="password-update-new">Nueva contraseña*</label>
                    </div>
                    
                    <div class="auth-input-group">
                        <input type="password" id="password-update-confirm" name="confirm_password" required placeholder=" ">
                        <label for="password-update-confirm">Confirmar nueva contraseña*</label>
                    </div>

                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button-back" id="password-update-back">Atrás</button>
                        <button type="button" class="auth-button" id="password-update-save">Guardar Contraseña</button>
                    </div>
                </fieldset>
            
            </form>
            </div>
    </div>
    </div>