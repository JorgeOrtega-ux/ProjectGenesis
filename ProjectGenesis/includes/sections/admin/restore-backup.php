<?php
// FILE: includes/sections/admin/restore-backup.php

// Estas variables ($backupFileName, $backupFileSize, $backupFileDate)
// son cargadas y validadas por config/router.php

?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-restore-backup') ? 'active' : 'disabled'; ?>" data-section="admin-restore-backup">
    <div class="component-wrapper">

        <?php outputCsrfInput(); ?>
        <input type="hidden" id="restore-filename" value="<?php echo htmlspecialchars($backupFileName); ?>">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.restore.title"></h1>
            <p class="component-page-description" data-i18n="admin.restore.description"></p>
        </div>

        <div class="component-card component-card--action component-card--danger">
            <div class="component-card__content">
                <div class="component-card__text">
                    
                    <div class="component-warning-box" style="margin-bottom: 16px;">
                        <span class="material-symbols-rounded">error</span>
                        <p data-i18n="admin.restore.warningDesc"></p>
                    </div>

                    <h2 class="component-card__title" data-i18n="admin.restore.backupFile"></h2>
                    <p class="component-card__description" style="font-weight: 600; font-size: 16px; color: #1f2937; margin-bottom: 16px;"><?php echo htmlspecialchars($backupFileName); ?></p>
                    
                    <h2 class="component-card__title" data-i18n="admin.restore.backupDate"></h2>
                    <p class="component-card__description" style="margin-bottom: 16px;"><?php echo htmlspecialchars($backupFileDate); ?></p>

                    <h2 class="component-card__title" data-i18n="admin.restore.backupSize"></h2>
                    <p class="component-card__description"><?php echo htmlspecialchars($backupFileSize); ?></p>
                    
                </div>
            </div>
            
            <div class="component-card__actions">
                 <button type="button"
                   class="component-button"
                   data-action="toggleSectionAdminManageBackups"
                   data-i18n="admin.restore.cancelButton">
                </button>
                 <button type="button" class="component-button danger" id="admin-restore-confirm-btn" data-i18n="admin.restore.confirmButton"></button>
            </div>
        </div>

    </div>
</div>