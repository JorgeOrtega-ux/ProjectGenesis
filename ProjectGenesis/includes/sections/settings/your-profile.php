<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-profile') ? 'active' : 'disabled'; ?>" data-section="settings-profile">
    <div class="settings-wrapper">
        
        <div class="settings-header-card">
            <h1 class="settings-title">Tu Perfil</h1>
            <p class="settings-description">
                Aquí podrás editar tu información de perfil, cambiar tu avatar y nombre de usuario.
            </p>
        </div>

        <?php
        // ¡Este bloque de lógica se ha ido!
        ?>
        
        <form id="avatar-form" onsubmit="event.preventDefault();" novalidate>
            
            <?php outputCsrfInput(); ?>
            
            <input type="file" id="avatar-upload-input" name="avatar" class="visually-hidden" accept="image/png, image/jpeg, image/gif, image/webp">
            <div class="settings-card-avatar-error" id="avatar-error" style="display: none;"></div>

            <div class="settings-card">
                <div class="settings-card-left">
                    <div class="settings-avatar" data-role="<?php echo htmlspecialchars($userRole); ?>" id="avatar-preview-container">
                        <img src="<?php echo htmlspecialchars($profileImageUrl); ?>" 
                             alt="Avatar de <?php echo htmlspecialchars($usernameForAlt); ?>"
                             class="settings-avatar-image"
                             id="avatar-preview-image">
                    </div>
                    <div class="settings-text-content">
                        <h2 class="settings-text-title">Foto de perfil</h2>
                        <p class="settings-text-description">Esto ayudará a tus compañeros a reconocerte.</p>
                    </div>
                </div>
                
                <div class="settings-card-right">
                    
                    <div class="settings-card-right-actions" id="avatar-actions-default" <?php echo $isDefaultAvatar ? '' : 'style="display: none;"'; ?>>
                        <button type="button" class="settings-button" id="avatar-upload-trigger">Subir foto</button>
                    </div>

                    <div class="settings-card-right-actions" id="avatar-actions-custom" <?php echo !$isDefaultAvatar ? '' : 'style="display: none;"'; ?>>
                        <button type="button" class="settings-button danger" id="avatar-remove-trigger">Eliminar foto</button>
                        <button type="button" class="settings-button" id="avatar-change-trigger">Cambiar foto</button>
                    </div>

                    <div class="settings-card-right-actions" id="avatar-actions-preview" style="display: none;">
                        <button type="button" class="settings-button" id="avatar-cancel-trigger">Cancelar</button>
                        <button type="submit" class="settings-button" id="avatar-save-trigger">Guardar</button>
                    </div>

                </div>
            </div>
        </form>
        </div>
</div>