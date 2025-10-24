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
        
        <form id="avatar-form" onsubmit="event.preventDefault();" novovite>
            
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
                        
                        <div class="settings-avatar-overlay">
                            <span class="material-symbols-rounded">photo_camera</span>
                        </div>
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

        <form id="username-form" onsubmit="event.preventDefault();" novalidate>
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="update-username">
            
            <div class="settings-card">
                
                <div class="settings-card-left" id="username-view-state" style="display: flex;">
                    <div class="settings-text-content">
                        <h2 class="settings-text-title">Nombre de usuario</h2>
                        <p class="settings-text-description" 
                           id="username-display-text" 
                           data-original-username="<?php echo htmlspecialchars($usernameForAlt); ?>">
                           <?php echo htmlspecialchars($usernameForAlt); ?>
                        </p>
                    </div>
                </div>
                <div class="settings-card-right" id="username-actions-view" style="display: flex;">
                    <button type="button" class="settings-button" id="username-edit-trigger">Editar</button>
                </div>

                <div class="settings-card-left" id="username-edit-state" style="display: none;">
                    <div class="settings-text-content" style="width: 100%;">
                        <h2 class="settings-text-title">Nombre de usuario</h2>
                        <input type="text" 
                               class="settings-username-input" 
                               id="username-input" 
                               name="username" 
                               value="<?php echo htmlspecialchars($usernameForAlt); ?>"
                               required
                               minlength="6">
                    </div>
                </div>
                <div class="settings-card-right-actions" id="username-actions-edit" style="display: none;">
                    <button type="button" class="settings-button" id="username-cancel-trigger">Cancelar</button>
                    <button type="submit" class="settings-button" id="username-save-trigger">Guardar</button>
                </div>

            </div>
        </form>

        <form id="email-form" onsubmit="event.preventDefault();" novalidate>
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="update-email">
            
            <div class="settings-card">
                
                <div class="settings-card-left" id="email-view-state" style="display: flex;">
                    <div class="settings-text-content">
                        <h2 class="settings-text-title">Correo Electrónico</h2>
                        <p class="settings-text-description" 
                           id="email-display-text" 
                           data-original-email="<?php echo htmlspecialchars($userEmail); ?>">
                           <?php echo htmlspecialchars($userEmail); ?>
                        </p>
                    </div>
                </div>
                <div class="settings-card-right" id="email-actions-view" style="display: flex;">
                    <button type="button" class="settings-button" id="email-edit-trigger">Editar</button>
                </div>

                <div class="settings-card-left" id="email-edit-state" style="display: none;">
                    <div class="settings-text-content" style="width: 100%;">
                        <h2 class="settings-text-title">Correo Electrónico</h2>
                        <input type="email" 
                               class="settings-username-input" 
                               id="email-input" 
                               name="email" 
                               value="<?php echo htmlspecialchars($userEmail); ?>"
                               required>
                    </div>
                </div>
                <div class="settings-card-right-actions" id="email-actions-edit" style="display: none;">
                    <button type="button" class="settings-button" id="email-cancel-trigger">Cancelar</button>
                    <button type="submit" class="settings-button" id="email-save-trigger">Guardar</button>
                </div>

            </div>
        </form>
        </div>
</div>