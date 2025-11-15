<?php
// FILE: includes/sections/admin/edit-community.php (NUEVO)

global $pdo, $basePath;

$isCreating = true;
$editCommunity = null;
$pageTitleKey = 'admin.communities.create.title';
$pageDescKey = 'admin.communities.create.description';

$defaultIcon = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$defaultBanner = $basePath . '/assets/images/default_banner.png'; // Asumiendo que tienes un banner por defecto

$communityId = (int)($_GET['id'] ?? 0);

if ($communityId > 0) {
    try {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (SQL) ▼▼▼ ---
        $stmt = $pdo->prepare("SELECT * FROM communities WHERE id = ?");
        // --- ▲▲▲ FIN DE MODIFICACIÓN (SQL) ▲▲▲ ---
        $stmt->execute([$communityId]);
        $editCommunity = $stmt->fetch();

        if ($editCommunity) {
            $isCreating = false;
            $pageTitleKey = 'admin.communities.edit.title';
            $pageDescKey = 'admin.communities.edit.description';
        } else {
            // ID no encontrado, volver a modo creación
            $communityId = 0;
        }
    } catch (PDOException $e) {
        logDatabaseError($e, 'admin - edit-community - load');
        // Fallback a modo creación
        $communityId = 0;
    }
}

// --- Preparar variables para el formulario ---
$comm_id = $editCommunity['id'] ?? 0;
$comm_name = $editCommunity['name'] ?? '';
$comm_type = $editCommunity['community_type'] ?? 'municipio'; 
$comm_privacy = $editCommunity['privacy'] ?? 'public';
$comm_code = $editCommunity['access_code'] ?? '';

// --- ▼▼▼ INICIO DE MODIFICACIÓN (NUEVA VARIABLE) ▼▼▼ ---
$comm_max_members_display = $editCommunity['max_members'] ?? 0; // 0 = sin límite en la UI
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

$comm_icon_url = $editCommunity['icon_url'] ?? $defaultIcon;
if(empty($comm_icon_url)) $comm_icon_url = $defaultIcon;
$isDefaultIcon = strpos($comm_icon_url, 'ui-avatars.com') !== false || strpos($comm_icon_url, '/assets/uploads/communities_default/') !== false;

$comm_banner_url = $editCommunity['banner_url'] ?? $defaultBanner;
$isDefaultBanner = (empty($comm_banner_url) || $comm_banner_url === $defaultBanner);

// Mapas para el selector de privacidad
$privacyMap = [
    'public' => 'admin.communities.privacyPublic',
    'private' => 'admin.communities.privacyPrivate'
];
$privacyIconMap = [
    'public' => 'public',
    'private' => 'lock'
];
$currentPrivacyKey = $privacyMap[$comm_privacy];
$currentPrivacyIcon = $privacyIconMap[$comm_privacy];

// Mapas para el selector de TIPO
$typeMap = [
    'municipio' => 'admin.communities.type.municipio',
    'universidad' => 'admin.communities.type.universidad'
];
$typeIconMap = [
    'municipio' => 'account_balance',
    'universidad' => 'school'
];
$currentTypeKey = $typeMap[$comm_type];
$currentTypeIcon = $typeIconMap[$comm_type];

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-edit-community') ? 'active' : 'disabled'; ?>" data-section="admin-edit-community">
    <div class="component-wrapper">

        <input type="hidden" id="admin-edit-target-community-id" value="<?php echo $comm_id; ?>">
        <input type="hidden" id="admin-edit-is-creating" value="<?php echo $isCreating ? '1' : '0'; ?>">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="<?php echo $pageTitleKey; ?>"></h1>
            <p class="component-page-description">
                <span data-i18n="<?php echo $pageDescKey; ?>"></span>
                <?php if (!$isCreating): ?>
                    <strong><?php echo htmlspecialchars($comm_name); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <div class="component-card component-card--edit-mode" id="admin-community-icon-section">
            <?php outputCsrfInput(); ?>
            <input type="file" class="visually-hidden" id="admin-icon-upload-input" name="image" accept="image/png, image/jpeg, image/gif, image/webp">

            <div class="component-card__content">
                <div class="component-card__avatar" id="admin-icon-preview-container" style="border-radius: 8px;">
                    <img src="<?php echo htmlspecialchars($comm_icon_url); ?>"
                         alt="Icono de la comunidad"
                         class="component-card__avatar-image"
                         id="admin-icon-preview-image"
                         style="border-radius: 8px;">
                    <div class="component-card__avatar-overlay" style="border-radius: 8px;">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </div>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.communities.edit.iconTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.communities.edit.iconDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div id="admin-icon-actions-default" class="<?php echo $isDefaultIcon ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button" id="admin-icon-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                </div>
                <div id="admin-icon-actions-custom" class="<?php echo !$isDefaultIcon ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button danger" id="admin-icon-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                    <button type="button" class="component-button" id="admin-icon-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                </div>
                <div id="admin-icon-actions-preview" class="disabled" style="gap: 12px;">
                    <button type="button" class="component-button" id="admin-icon-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="button" class="component-button" id="admin-icon-save-trigger-btn" data-i18n="settings.profile.save"></button>
                </div>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode" id="admin-community-banner-section">
            <?php outputCsrfInput(); ?>
            <input type="file" class="visually-hidden" id="admin-banner-upload-input" name="image" accept="image/png, image/jpeg, image/gif, image/webp">

            <div class="component-card__content">
                 <div class="component-card__avatar" id="admin-banner-preview-container" style="border-radius: 8px; width: 120px; height: 60px; background-color: #f0f0f0;">
                    <img src="<?php echo htmlspecialchars($comm_banner_url); ?>"
                         alt="Banner de la comunidad"
                         class="component-card__avatar-image"
                         id="admin-banner-preview-image"
                         style="border-radius: 8px;">
                    <div class="component-card__avatar-overlay" style="border-radius: 8px;">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </div>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.communities.edit.bannerTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.communities.edit.bannerDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div id="admin-banner-actions-default" class="<?php echo $isDefaultBanner ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button" id="admin-banner-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                </div>
                <div id="admin-banner-actions-custom" class="<?php echo !$isDefaultBanner ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                    <button type="button" class="component-button danger" id="admin-banner-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                    <button type="button" class="component-button" id="admin-banner-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                </div>
                <div id="admin-banner-actions-preview" class="disabled" style="gap: 12px;">
                    <button type="button" class="component-button" id="admin-banner-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="button" class="component-button" id="admin-banner-save-trigger-btn" data-i18n="settings.profile.save"></button>
                </div>
            </div>
        </div>

        <div class="component-card component-card--action" id="admin-community-details-section" style="gap: 16px;">
            <?php outputCsrfInput(); ?>
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.communities.edit.detailsTitle">Detalles de la Comunidad</h2>
                </div>
            </div>
            
            <div class="component-input-group">
                <input type="text" id="admin-community-name" class="component-input" required placeholder=" " maxlength="100" value="<?php echo htmlspecialchars($comm_name); ?>">
                <label for="admin-community-name" data-i18n="admin.communities.edit.nameLabel"></label>
            </div>
            
            <div class="component-card__content" style="width: 100%; flex-direction: column; align-items: flex-start; gap: 8px; padding-top: 8px;">
                <label class="component-card__description" data-i18n="admin.communities.edit.typeTitle">Tipo de Comunidad</label>
                <div class="trigger-select-wrapper" style="width: 100%;">
                    <div class="trigger-selector" data-action="toggleModuleAdminCommunityType">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $currentTypeIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentTypeKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleAdminCommunityType">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($typeMap as $key => $textKey):
                                    $isActive = ($key === $comm_type);
                                    $iconName = $typeIconMap[$key];
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>"
                                         data-value="<?php echo htmlspecialchars($key); ?>"
                                         data-icon="<?php echo htmlspecialchars($iconName); ?>"
                                         data-text-key="<?php echo htmlspecialchars($textKey); ?>">
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
            
            <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (NUEVO STEPPER) ▼▼▼ --- ?>
            <div class="component-card__content" style="width: 100%; flex-direction: column; align-items: flex-start; gap: 8px; padding-top: 8px;">
                <label class="component-card__description" data-i18n="admin.communities.edit.maxMembersLabel">Límite de Miembros (0 = sin límite)</label>
                
                <div class="component-stepper component-stepper--multi"
                    id="admin-community-max-members"
                    style="width: 100%;"
                    data-current-value="<?php echo htmlspecialchars($comm_max_members_display); ?>"
                    data-min="0"
                    data-max="100000"
                    data-step-1="10"
                    data-step-10="100"
                    <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>>
                    <button type="button" class="stepper-button" data-step-action="decrement-10" <?php echo ($_SESSION['role'] !== 'founder' || $comm_max_members_display <= 99) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_left</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="decrement-1" <?php echo ($_SESSION['role'] !== 'founder' || $comm_max_members_display <= 0) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_left</span>
                    </button>
                    <div class="stepper-value">
                        <?php echo htmlspecialchars($comm_max_members_display); ?>
                    </div>
                    <button type="button" class="stepper-button" data-step-action="increment-1" <?php echo ($_SESSION['role'] !== 'founder' || $comm_max_members_display >= 100000) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </button>
                    <button type="button" class="stepper-button" data-step-action="increment-10" <?php echo ($_SESSION['role'] !== 'founder' || $comm_max_members_display >= 99901) ? 'disabled' : ''; ?>>
                        <span class="material-symbols-rounded">keyboard_double_arrow_right</span>
                    </button>
                </div>
            </div>
            <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
            
            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--primary" id="admin-community-details-save-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>

        <div class="component-card component-card--action" id="admin-community-privacy-section" style="gap: 16px;">
            <?php outputCsrfInput(); ?>
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.communities.edit.privacyTitle">Privacidad y Acceso</h2>
                </div>
            </div>

            <div class="component-card__content" style="width: 100%; flex-direction: column; align-items: flex-start; gap: 8px;">
                <label class="component-card__description" data-i18n="admin.communities.edit.privacyLabel">Tipo de Comunidad</label>
                <div class="trigger-select-wrapper" style="width: 100%;">
                    <div class="trigger-selector" data-action="toggleModuleAdminCommunityPrivacy">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $currentPrivacyIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentPrivacyKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleAdminCommunityPrivacy">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($privacyMap as $key => $textKey):
                                    $isActive = ($key === $comm_privacy);
                                    $iconName = $privacyIconMap[$key];
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>"
                                         data-value="<?php echo htmlspecialchars($key); ?>"
                                         data-icon="<?php echo htmlspecialchars($iconName); ?>"
                                         data-text-key="<?php echo htmlspecialchars($textKey); ?>">
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
            
            <div class="component-input-group" id="admin-access-code-group" style="<?php echo ($comm_privacy === 'public') ? 'display: none;' : ''; ?>">
                <input type="text" id="admin-community-code" class="component-input" placeholder=" " maxlength="50" value="<?php echo htmlspecialchars($comm_code); ?>">
                <label for="admin-community-code" data-i18n="admin.communities.edit.codeLabel"></label>
                
                <button type="button" class="auth-toggle-password" data-action="admin-generate-code" title="Generar código aleatorio">
                    <span class="material-symbols-rounded">auto_fix_high</span>
                </button>
            </div>
            
            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--primary" id="admin-community-privacy-save-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>

        <?php if (!$isCreating): ?>
        <div class="component-card component-card--action component-card--danger">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">delete_forever</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.communities.edit.dangerTitle">Zona Peligrosa</h2>
                    <p class="component-card__description" data-i18n="admin.communities.edit.dangerDesc">Eliminar esta comunidad es una acción irreversible.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button" class="component-button danger" id="admin-community-delete-btn" data-i18n="admin.communities.edit.deleteButton"></button>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>