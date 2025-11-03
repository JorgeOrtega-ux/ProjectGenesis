<?php
if (!isset($editUser) || !$editUser) {
    echo "Error: No se han podido cargar los datos del usuario.";
    return;
}
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-edit-user') ? 'active' : 'disabled'; ?>" data-section="admin-edit-user">
    <div class="component-wrapper">

        <input type="hidden" id="admin-edit-target-user-id" value="<?php echo htmlspecialchars($editUser['id']); ?>">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.edit.title">Editar Usuario</h1>
            <p class="component-page-description">
                <span data-i18n="admin.edit.description">Editando el perfil de</span>: <strong><?php echo htmlspecialchars($editUser['username']); ?></strong>
            </p>
        </div>

        <div class="component-card component-card--edit-mode" id="admin-avatar-section">
            <?php outputCsrfInput(); ?>
            <input type="file" class="visually-hidden" id="admin-avatar-upload-input" name="avatar" accept="image/png, image/jpeg, image/gif, image/webp">

            <div class="component-card__content">
                <div class="component-card__avatar" id="admin-avatar-preview-container" data-role="<?php echo htmlspecialchars($editUser['role']); ?>">
                    <img src="<?php echo htmlspecialchars($editUser['profile_image_url']); ?>"
                         alt="<?php echo htmlspecialchars($editUser['username']); ?>"
                         class="component-card__avatar-image"
                         id="admin-avatar-preview-image"
                         data-i18n-alt-prefix="header.profile.altPrefix">
                    <div class="component-card__avatar-overlay">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </div>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.avatarTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.profile.avatarDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div id="admin-avatar-actions-default" class="<?php echo $isDefaultAvatar ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button" id="admin-avatar-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                </div>
                <div id="admin-avatar-actions-custom" class="<?php echo !$isDefaultAvatar ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button danger" id="admin-avatar-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                    <button type="button" class="component-button" id="admin-avatar-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                </div>
                <div id="admin-avatar-actions-preview" class="disabled" style="gap: 12px;">
                <button type="button" class="component-button" id="admin-avatar-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="button" class="component-button" id="admin-avatar-save-trigger-btn" data-i18n="settings.profile.save"></button>
                </div>
            </div>
        </div>

        <div class="component-card component-card--edit-mode" id="admin-username-section">
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="admin-update-username">
            <div class="component-card__content active" id="admin-username-view-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.username"></h2>
                    <p class="component-card__description"
                       id="admin-username-display-text"
                       data-original-username="<?php echo htmlspecialchars($editUser['username']); ?>">
                       <?php echo htmlspecialchars($editUser['username']); ?>
                    </p>
                </div>
            </div>
            <div class="component-card__actions active" id="admin-username-actions-view">
            <button type="button" class="component-button" id="admin-username-edit-trigger" data-i18n="settings.profile.edit"></button>
            </div>
            <div class="component-card__content disabled" id="admin-username-edit-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.username"></h2>
                    <input type="text"
                           class="component-text-input"
                           id="admin-username-input"
                           name="username"
                           value="<?php echo htmlspecialchars($editUser['username']); ?>"
                           required
                           minlength="6"
                           maxlength="32">
                </div>
            </div>
            <div class="component-card__actions disabled" id="admin-username-actions-edit">
            <button type="button" class="component-button" id="admin-username-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-button" id="admin-username-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>

        <div class="component-card component-card--edit-mode" id="admin-email-section">
            <?php outputCsrfInput(); ?>
            <input type="hidden" name="action" value="admin-update-email">
            <div class="component-card__content active" id="admin-email-view-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.email"></h2>
                    <p class="component-card__description"
                       id="admin-email-display-text"
                       data-original-email="<?php echo htmlspecialchars($editUser['email']); ?>">
                       <?php echo htmlspecialchars($editUser['email']); ?>
                    </p>
                </div>
            </div>
            <div class="component-card__actions active" id="admin-email-actions-view">
            <button type="button" class="component-button" id="admin-email-edit-trigger" data-i18n="settings.profile.edit"></button>
            </div>
            <div class="component-card__content disabled" id="admin-email-edit-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.email"></h2>
                    <input type="email"
                           class="component-text-input"
                           id="admin-email-input"
                           name="email"
                           value="<?php echo htmlspecialchars($editUser['email']); ?>"
                           required
                           maxlength="255">
                </div>
            </div>
            <div class="component-card__actions disabled" id="admin-email-actions-edit">
            <button type="button" class="component-button" id="admin-email-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-button" id="admin-email-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>

        <div class="component-card component-card--action" id="admin-pass-step-2">
            <?php outputCsrfInput(); ?>
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">lock_reset</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.edit.passwordHashed"></h2>
                    <p class="component-card__description" data-i18n="admin.edit.passwordDesc"></p>
                </div>
            </div>

            <div class="component-input-group">
                <input type="text" id="admin-password-hash-display" class="component-input" value="<?php echo htmlspecialchars($editUser['password_hash']); ?>" readonly style="background-color: #f5f5fa; color: #6b7280; font-family: monospace; font-size: 14px;">
                <label for="admin-password-hash-display" data-i18n="admin.edit.passwordHashed"></label>
            </div>
            
            <div class="component-input-group">
                <input type="password" id="admin-password-update-new" name="new_password" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                <label for="admin-password-update-new" data-i18n="admin.edit.passwordNew"></label>
            </div>
            <div class="component-input-group">
                <input type="password" id="admin-password-update-confirm" name="confirm_password" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                <label for="admin-password-update-confirm" data-i18n="admin.edit.passwordConfirm"></label>
            </div>

            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--primary" id="admin-password-update-save" data-i18n="settings.login.savePassword"></button>
            </div>
        </div>

    </div>
</div>