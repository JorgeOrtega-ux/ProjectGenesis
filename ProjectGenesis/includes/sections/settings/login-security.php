<?php
// FILE: jorgeortega-ux/projectgenesis/ProjectGenesis-98418948306e47bc505f1797114031c3351b5e33/ProjectGenesis/includes/sections/settings/login-security.php
?>
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

                    <p class="settings-text-description">
                        <?php
                        // Esta variable $lastPasswordUpdateText se define en config/router.php
                        echo htmlspecialchars($lastPasswordUpdateText);
                        ?>
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button" id="password-edit-trigger">Actualizar</button>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                    <span class="material-symbols-rounded">shield_lock</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Verificación de dos pasos (2FA)</h2>
                    <p class="settings-text-description" id="tfa-status-text">
                        <?php
                        // $is2faEnabled viene de config/router.php
                        echo $is2faEnabled ? 'La autenticación de dos pasos está activa.' : 'Añade una capa extra de seguridad a tu cuenta.';
                        ?>
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                <div class="settings-card-right-actions">
                    <button type="button" 
                            class="settings-button <?php echo $is2faEnabled ? 'danger' : ''; ?>" 
                            id="tfa-toggle-button"
                            data-is-enabled="<?php echo $is2faEnabled ? '1' : '0'; ?>">
                        <?php echo $is2faEnabled ? 'Deshabilitar' : 'Habilitar'; ?>
                    </button>
                    </div>
            </div>
        </div>

        <div class="settings-card settings-card-column">
            <div class="settings-card-left">
                <div class="settings-card-icon">
                    <span class="material-symbols-rounded">devices</span>
                </div>
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Sesiones de dispositivos</h2>
                    <p class="settings-text-description">
                        Revisa y administra los dispositivos donde has iniciado sesión.
                    </p>
                </div>
            </div>
            
            <div class="settings-card-bottom">
                <div class="settings-card-right-actions">
                    <button type="button" 
                            class="settings-button" 
                            data-action="toggleSectionSettingsDevices"> 
                            Administrar dispositivos
                    </button>
                </div>
            </div>
        </div>
        <div class="settings-card settings-card-column settings-card-danger">
            
            <div class="settings-text-content">
                <h2 class="settings-text-title">Eliminar tu cuenta</h2>
                <p class="settings-text-description">
                    Si eliminas tu cuenta, ya no podrás acceder a ninguno de tus diseños ni iniciar sesión.
                </p>
            </div>
            
            <div class="settings-card-bottom">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button danger">Eliminar cuenta</button>
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
                        <input type="password" id="password-update-new" name="new_password" required placeholder=" " minlength="8" maxlength="72">
                        <label for="password-update-new">Nueva contraseña*</label>
                    </div>

                    <div class="auth-input-group">
                        <input type="password" id="password-update-confirm" name="confirm_password" required placeholder=" " minlength="8" maxlength="72">
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

    <div class="settings-modal-overlay" id="tfa-verify-modal" style="display: none;">
        <button type="button" class="settings-modal-close-btn" id="tfa-verify-close">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div class="settings-modal-content">
            <form class="auth-form" onsubmit="event.preventDefault();" novalidate>
                <fieldset class="auth-step active">
                    <h2 class="auth-title" id="tfa-modal-title">Verifica tu identidad</h2>
                    <p class_id="tfa-modal-text">
                        Para continuar, por favor ingresa tu contraseña actual.
                    </p>
                    <div class="auth-error-message" id="tfa-verify-error" style="display: none;"></div>
                    <div class="auth-input-group">
                        <input type="password" id="tfa-verify-password" name="current_password" required placeholder=" ">
                        <label for="tfa-verify-password">Contraseña actual*</label>
                    </div>
                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button" id="tfa-verify-continue">Confirmar</button>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>

    </div>
</div>