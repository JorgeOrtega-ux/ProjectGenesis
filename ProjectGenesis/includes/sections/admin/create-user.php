<?php
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

        <form id="admin-create-user-form" novalidate onsubmit="event.preventDefault();">
            
            <?php outputCsrfInput(); ?>
            
            <input type="hidden" id="admin-create-role-input" name="role" value="<?php echo $defaultRole; ?>">

            <div class="component-card component-card--action" id="admin-create-card">
                
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

                <div class="component-input-group">
                    <input type="password" id="admin-create-password" name="password" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                    <label for="admin-create-password" data-i18n="admin.create.passwordLabel"></label>
                    <button type="button" class="auth-toggle-password" data-toggle="admin-create-password">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                    </div>
                
                <div class="component-input-group">
                    <input type="password" id="admin-create-password-confirm" name="password_confirm" class="component-input" required placeholder=" " minlength="8" maxlength="72">
                    <label for="admin-create-password-confirm" data-i18n="admin.create.confirmPasswordLabel"></label>
                    <button type="button" class="auth-toggle-password" data-toggle="admin-create-password-confirm">
                        <span class="material-symbols-rounded">visibility</span>
                    </button>
                </div>
                <div class="component-card__text" style="width: 100%; margin-top: 8px;">
                    <h2 class="component-card__title" data-i18n="admin.create.roleLabel"></h2>
                </div>
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
                </div> <div class="component-card__error" style="display: none; width: 100%; margin-top: 16px;"></div>

                <div class="component-card__actions" style="margin-top: 16px;">
                    <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionAdminManageUsers" data-i18n="admin.create.cancelButton"></button>
                    <button type="submit" class="component-action-button component-action-button--primary" id="admin-create-user-submit" data-i18n="admin.create.createButton"></button>
                </div>
            </div>
        </form>

    </div>
</div>