<?php
// FILE: includes/sections/admin/create-user.php
// (CÓDIGO MODIFICADO - El Rol ahora es un componente separado)

$roleMap = [
    'user' => 'admin.users.roleUser',
    'moderator' => 'admin.users.roleModerator',
    'administrator' => 'admin.users.roleAdministrator'
];
$roleIconMap = [
    'user' => 'person',
    'moderator' => 'shield_person',
    'administrator' => 'admin_panel_settings'
];

$defaultRole = 'user';
$currentRoleKey = $roleMap[$defaultRole];
$currentRoleIcon = $roleIconMap[$defaultRole];

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-create-user') ? 'active' : 'disabled'; ?>" data-section="admin-create-user">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.create.title"></h1>
            <p class="component-page-description" data-i18n="admin.create.description"></p>
        </div>
        
        <?php outputCsrfInput(); ?>
            
        <input type="hidden" id="admin-create-role-input" name="role" value="<?php echo $defaultRole; ?>">

        <div class="component-card component-card--action" style="gap: 16px;">
            <div class="component-card__content" style="width: 100%;">
                <div class="component-card__text" style="width: 100%;">
                    <h2 class="component-card__title" style="margin-bottom: 16px;" data-i18n="admin.create.accountInfoTitle">Información de la Cuenta</h2>
                </div>
            </div>

            <div class="component-input-group">
                <input type="text" id="admin-create-username" name="username" class="component-input" required placeholder=" " minlength="6" maxlength="32">
                <label for="admin-create-username" data-i18n="admin.create.usernameLabel"></label>
                
                <button type="button" class="auth-toggle-password auth-generate-username" data-action="admin-generate-username" data-toggle="admin-create-username" title="Generar nombre de usuario aleatorio">
                    <span class="material-symbols-rounded">auto_fix_high</span>
                </button>
            </div>
            
            <div class="component-input-group">
                <input type="email" id="admin-create-email" name="email" class="component-input" required placeholder=" " maxlength="255">
                <label for="admin-create-email" data-i18n="admin.create.emailLabel"></label>
            </div>

            </div> 

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.create.roleLabel"></h2>
                    <p class="component-card__description" data-i18n="admin.create.roleDesc">Selecciona el nivel de permisos para el nuevo usuario.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper" style="width: 100%;">
                    <div class="trigger-selector" data-action="toggleModuleAdminCreateRole">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $currentRoleIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentRoleKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleAdminCreateRole">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($roleMap as $key => $textKey):
                                    $isActive = ($key === $defaultRole);
                                    $iconName = $roleIconMap[$key];
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>"
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
        </div>

        <div class="component-card component-card--action" id="admin-password-section" style="gap: 16px;">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">password</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.create.passwordLabel">Contraseña*</h2>
                    <input type="text"
                        class="component-text-input"
                        id="admin-create-password"
                        name="password"
                        value=""
                        placeholder="Clic en 'Generar' para crear una contraseña"
                        readonly 
                        style="background-color: #f5f5fa; cursor: default;">
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button" class="component-button" id="admin-generate-password-btn" data-action="admin-generate-password" data-i18n="admin.create.generatePass">Generar</button>
                <button type="button" class="component-button" id="admin-copy-password-btn" data-action="admin-copy-password" data-i18n="admin.create.copyPass">Copiar</button>
            </div>
        </div> 
        
        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">shield_lock</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.login.2fa">Verificación de dos pasos (2FA)</h2>
                    <p class="component-card__description" data-i18n="admin.create.2faDesc">Activa 2FA para esta cuenta al momento de crearla.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox"
                        id="admin-create-2fa"
                        name="is_2fa_enabled"
                        value="1">
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div> 
        
        <div class="component-card component-card--action" id="admin-create-card-actions">
            <div class="component-card__error disabled" style="width: 100%;"></div>
            
            <div class="component-card__actions" style="width: 100%;">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionAdminManageUsers" data-i18n="admin.create.cancelButton"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="admin-create-user-submit" data-action="admin-create-user-submit" data-i18n="admin.create.createButton"></button>
            </div>
        </div> 
    </div>
</div>