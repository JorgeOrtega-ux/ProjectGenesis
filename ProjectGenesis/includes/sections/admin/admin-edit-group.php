<?php
// FILE: includes/sections/admin/admin-edit-group.php
// (CÓDIGO MODIFICADO PARA REUTILIZAR COMPONENTES)

// 1. Comprobar si $editGroup fue cargado por router.php
if (!isset($editGroup) || !$editGroup) {
    echo "Error: No se han podido cargar los datos del grupo.";
    return;
}

// 2. Definir los mapas para el selector de privacidad
$privacyMap = [
    'privado' => 'mygroups.card.privacyPrivado',
    'publico' => 'mygroups.card.privacyPublico'
];
$privacyIconMap = [
    'privado' => 'lock',
    'publico' => 'public'
];

// 3. Obtener valores actuales
$currentPrivacy = $editGroup['privacy'] ?? 'privado';
$currentPrivacyKey = $privacyMap[$currentPrivacy];
$currentPrivacyIcon = $privacyIconMap[$currentPrivacy];

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-edit-group') ? 'active' : 'disabled'; ?>" data-section="admin-edit-group">
    <div class="component-wrapper" id="admin-edit-group-form">

        <input type="hidden" id="admin-edit-target-group-id" value="<?php echo htmlspecialchars($editGroup['id']); ?>">
        <?php outputCsrfInput(); ?>
        
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.groups.editTitle">Editar Grupo</h1>
            <p class="component-page-description">
                <span data-i18n="admin.edit.description">Editando el perfil de</span>: <strong><?php echo htmlspecialchars($editGroup['name']); ?></strong>
            </p>
        </div>

        <div class="component-card component-card--edit-mode" id="admin-group-name-section">
            <input type="hidden" name="action" value="admin-update-group-name">
            
            <div class="component-card__content active" id="admin-group-name-view-state">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.groups.groupNameLabel">Nombre del Grupo</h2>
                    <p class="component-card__description"
                       id="admin-group-name-display-text"
                       data-original-value="<?php echo htmlspecialchars($editGroup['name']); ?>">
                       <?php echo htmlspecialchars($editGroup['name']); ?>
                    </p>
                </div>
            </div>
            <div class="component-card__actions active" id="admin-group-name-actions-view">
                <button type="button" class="component-button" id="admin-group-name-edit-trigger" data-action="admin-group-name-edit-trigger" data-i18n="settings.profile.edit">Editar</button>
            </div>

            <div class="component-card__content disabled" id="admin-group-name-edit-state">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.groups.groupNameLabel">Nombre del Grupo</h2>
                    <input type="text"
                           class="component-text-input"
                           id="admin-group-name-input"
                           name="name"
                           value="<?php echo htmlspecialchars($editGroup['name']); ?>"
                           required
                           maxlength="255">
                </div>
            </div>
            <div class="component-card__actions disabled" id="admin-group-name-actions-edit">
                <button type="button" class="component-button" id="admin-group-name-cancel-trigger" data-action="admin-group-name-cancel-trigger" data-i18n="settings.profile.cancel">Cancelar</button>
                <button type="button" class="component-button" id="admin-group-name-save-trigger-btn" data-action="admin-group-name-save-trigger-btn" data-i18n="settings.profile.save">Guardar</button>
            </div>
        </div>
        <div class="component-card component-card--column" id="admin-group-privacy-section">
            <input type="hidden" id="admin-group-privacy-input" name="privacy" value="<?php echo htmlspecialchars($currentPrivacy); ?>">
            
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.groups.privacyLabel">Privacidad</h2>
                    <p class="component-card__description" data-i18n="admin.groups.privacyDesc">Define si el grupo es público o privado.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleAdminEditGroupPrivacy">
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
                         data-module="moduleAdminEditGroupPrivacy"
                         data-preference-type="group-privacy"> <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($privacyMap as $key => $textKey):
                                    $isActive = ($key === $currentPrivacy);
                                    $iconName = $privacyIconMap[$key];
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
        <div class="component-card component-card--action" id="admin-access-key-section" style="gap: 16px;">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">key</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.groups.accessKeyLabel">Clave de Acceso</h2>
                    <input type="text"
                        class="component-text-input"
                        id="admin-edit-group-access-key"
                        name="access_key"
                        value="<?php echo htmlspecialchars($editGroup['access_key']); ?>"
                        placeholder="Clic en 'Generar' para crear una clave"
                        maxlength="12"
                        style="font-family: monospace; font-size: 16px; background-color: #f5f5fa;">
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button" class="component-button" id="admin-generate-group-code-btn" data-action="admin-generate-group-code" data-i18n="admin.groups.generateCode">Generar</button>
                <button type="button" class="component-button" id="admin-copy-group-code-btn" data-action="admin-copy-group-code" data-i18n="admin.groups.copyCode">Copiar</button>
            </div>
        </div> 
        <div class="component-card__error disabled" id="admin-edit-group-error" style="margin-bottom: 0;"></div>

        </div>
</div>