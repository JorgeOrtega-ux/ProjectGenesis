<?php
// FILE: includes/sections/admin/admin-edit-group.php (NUEVO ARCHIVO)

// 1. Comprobar si $editGroup fue cargado por router.php
if (!isset($editGroup) || !$editGroup) {
    echo "Error: No se han podido cargar los datos del grupo.";
    // En una implementación real, el router ya debería haber mostrado un 404
    // pero esto es unaDoble comprobación.
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
        <input type="hidden" id="admin-edit-group-privacy-input" name="privacy" value="<?php echo htmlspecialchars($currentPrivacy); ?>">
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.groups.editTitle">Editar Grupo</h1>
            <p class="component-page-description">
                <span data-i18n="admin.edit.description">Editando el perfil de</span>: <strong><?php echo htmlspecialchars($editGroup['name']); ?></strong>
            </p>
        </div>

        <div class="component-card component-card--action" style="gap: 16px;">
            <div class="component-card__content" style="width: 100%;">
                <div class="component-card__text" style="width: 100%;">
                    <h2 class="component-card__title" style="margin-bottom: 16px;" data-i18n="admin.groups.groupNameLabel">Nombre del Grupo</h2>
                </div>
            </div>
            <div class="component-input-group">
                <input type="text" id="admin-edit-group-name" name="name" class="component-input" required placeholder=" " maxlength="255" value="<?php echo htmlspecialchars($editGroup['name']); ?>">
                <label for="admin-edit-group-name" data-i18n="admin.groups.groupNameLabel">Nombre del Grupo</label>
            </div>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.groups.privacyLabel">Privacidad</h2>
                    <p class="component-card__description" data-i18n="admin.groups.privacyDesc">Define si el grupo es público o privado.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper" style="width: 100%;">
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
                         data-module="moduleAdminEditGroupPrivacy">
                        <div class="menu-content">
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

        <div class="component-card component-card--action" id="admin-edit-group-card-actions">
            <div class="component-card__error disabled" style="width: 100%;"></div>
            
            <div class="component-card__actions" style="width: 100%;">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionAdminManageGroups" data-i18n="admin.create.cancelButton"></button>
                <button type="button" class="component-action-button component-action-button--primary" id="admin-edit-group-submit" data-action="admin-edit-group-submit" data-i18n="admin.groups.saveChanges">Guardar Cambios</button>
            </div>
        </div> 

    </div>
</div>