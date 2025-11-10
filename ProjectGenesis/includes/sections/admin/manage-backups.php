<?php
// FILE: includes/sections/admin/manage-backups.php
// (CÓDIGO UNIFICADO Y MODIFICADO)

// --- ▼▼▼ INICIO DE LA MODIFICACIÓN (NUEVA LÓGICA DE 3 VISTAS) ▼▼▼ ---

// 1. Comprobar si estamos en modo "confirmar eliminación"
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    
    $backupFileName = basename($_GET['delete']); // Sanitización
    $backupDir = dirname(__DIR__, 3) . '/backups';
    $filePath = $backupDir . '/' . $backupFileName;

    if (empty($backupFileName) || !file_exists($filePath) || !is_readable($filePath)) {
        // Si el archivo no es válido, mostrar un 404
        include dirname(__DIR__, 2) . '/main/404.php';
    
    } else {
        // Si el archivo es válido, obtener sus datos para la vista
        $backupFileSize = formatBackupSize(filesize($filePath));
        $backupFileDate = formatBackupDate(filemtime($filePath));
        
        // --- INICIO DE HTML (NUEVA VISTA DE ELIMINACIÓN) ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-backups') ? 'active' : 'disabled'; ?>" data-section="admin-backups-delete-confirm">
    <div class="component-wrapper">

        <?php outputCsrfInput(); ?>
        <input type="hidden" id="delete-filename" value="<?php echo htmlspecialchars($backupFileName); ?>">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.delete.title">Confirmar Eliminación</h1>
            <p class="component-page-description" data-i18n="admin.delete.description">Estás a punto de eliminar un archivo de copia de seguridad.</p>
        </div>

        <div class="component-card component-card--action component-card--danger">
            <div class="component-card__content">
                <div class="component-card__text">
                    
                    <div class="component-warning-box" style="margin-bottom: 16px;">
                        <span class="material-symbols-rounded">error</span>
                        <p data-i18n="admin.delete.warningDesc">¡ACCIÓN IRREVERSIBLE! El archivo de copia de seguridad se eliminará permanentemente del servidor. Esta acción no se puede deshacer. (Esto no afecta a la base de datos actual).</p>
                    </div>

                    <h2 class="component-card__title" data-i18n="admin.delete.backupFile"></h2>
                    <p class="component-card__description" style="font-weight: 600; font-size: 16px; color: #1f2937; margin-bottom: 16px;"><?php echo htmlspecialchars($backupFileName); ?></p>
                    
                    <h2 class="component-card__title" data-i18n="admin.delete.backupDate"></h2>
                    <p class="component-card__description" style="margin-bottom: 16px;"><?php echo htmlspecialchars($backupFileDate); ?></p>

                    <h2 class="component-card__title" data-i18n="admin.delete.backupSize"></h2>
                    <p class="component-card__description"><?php echo htmlspecialchars($backupFileSize); ?></p>
                    
                </div>
            </div>
            
            <div class="component-card__actions">
                 <button type="button"
                   class="component-button"
                   data-action="toggleSectionAdminManageBackups"
                   data-i18n="admin.delete.cancelButton">
                </button>
                 <button type="button" class="component-button danger" id="admin-delete-confirm-btn" data-i18n="admin.delete.confirmButton"></button>
            </div>
        </div>

    </div>
</div>
<?php
    } // Fin del 'else' (archivo válido)

} 
// 2. Comprobar si estamos en modo "confirmar restauración"
elseif (isset($_GET['file']) && !empty($_GET['file'])) {
    
    $backupFileName = basename($_GET['file']); // Sanitización
    $backupDir = dirname(__DIR__, 3) . '/backups';
    $filePath = $backupDir . '/' . $backupFileName;

    if (empty($backupFileName) || !file_exists($filePath) || !is_readable($filePath)) {
        include dirname(__DIR__, 2) . '/main/404.php';
    
    } else {
        $backupFileSize = formatBackupSize(filesize($filePath));
        $backupFileDate = formatBackupDate(filemtime($filePath));
        
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-backups') ? 'active' : 'disabled'; ?>" data-section="admin-backups-restore-confirm">
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
<?php
    } // Fin del 'else' (archivo válido)

} 
// 3. Modo lista (default)
else {
    
    $backupDir = dirname(__DIR__, 3) . '/backups';
    $backupFiles = [];
    $hasError = false;
    $errorMessage = '';

    if (!is_dir($backupDir)) {
        if (!@mkdir($backupDir, 0755, true)) {
            $hasError = true;
            $errorMessage = 'admin.backups.errorDirCreate';
            logDatabaseError(new Exception("No se pudo crear el directorio de backups en: " . $backupDir), 'manage-backups');
        }
    }

    if (!$hasError && !is_writable($backupDir)) {
         $hasError = true;
         $errorMessage = 'admin.backups.errorDirWrite';
         logDatabaseError(new Exception("El directorio de backups no tiene permisos de escritura: " . $backupDir), 'manage-backups');
    }

    if (!$hasError) {
        $files = glob($backupDir . '/*.sql');
        if ($files === false) {
             $hasError = true;
             $errorMessage = 'admin.backups.errorDirRead';
        } else {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $backupFiles[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'created_at' => filemtime($file)
                ];
            }
        }
    }
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-backups') ? 'active' : 'disabled'; ?>" data-section="admin-backups">

    <div class="page-toolbar-container" id="backup-toolbar-container">

        <div class="page-toolbar-floating">

            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-backup-create"
                        data-tooltip="admin.backups.createTooltip"
                        <?php echo ($_SESSION['role'] !== 'founder' || $hasError) ? 'disabled' : ''; ?>
                        >
                        <span class="material-symbols-rounded">add_to_drive</span>
                    </button>
                </div>
            </div>
            
            <div class="toolbar-action-selection">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-backup-restore"
                        data-tooltip="admin.backups.restoreTooltip" disabled>
                        <span class="material-symbols-rounded">restore</span>
                    </button>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-backup-download"
                        data-tooltip="admin.backups.downloadTooltip" disabled>
                        <span class="material-symbols-rounded">download</span>
                    </button>
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-backup-delete"
                        data-tooltip="admin.backups.deleteTooltip" disabled>
                        <span class="material-symbols-rounded">delete</span>
                    </button>
                </div>

                <div class="page-toolbar-right">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="admin-backup-clear-selection"
                        data-tooltip="admin.backups.clearSelection" disabled>
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    
    <div class="component-wrapper">

        <?php outputCsrfInput(); ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.backups.title"></h1>
            <p class="component-page-description" data-i18n="admin.backups.description"></p>
        </div>

        <?php if ($hasError): ?>
            <div class="component-card component-card--danger">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">error</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="admin.backups.errorDirTitle"></h2>
                        <p class="component-card__description" data-i18n="<?php echo $errorMessage; ?>"></p>
                    </div>
                </div>
            </div>
        <?php elseif (empty($backupFiles)): ?>
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__icon">
                        <span class="material-symbols-rounded">database</span>
                    </div>
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="admin.backups.noBackupsTitle"></h2>
                        <p class="component-card__description" data-i18n="admin.backups.noBackupsDesc"></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card-list-container" id="backup-list-container">
                <?php foreach ($backupFiles as $file): ?>
                    <div class="card-item" 
                         data-backup-filename="<?php echo htmlspecialchars($file['filename']); ?>"
                         style="gap: 16px; padding: 16px;">
                    
                        <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                            <span class="material-symbols-rounded" style="font-size: 28px;">database</span>
                        </div>

                        <div class="card-item-details">

                            <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                                <span class="card-detail-value" style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($file['filename']); ?></span>
                            </div>

                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.backups.labelDate"></span>
                                <span class="card-detail-value"><?php echo date('d/m/Y H:i:s', $file['created_at']); ?></span>
                            </div>
                            <div class="card-detail-item">
                                <span class="card-detail-label" data-i18n="admin.backups.labelSize"></span>
                                <span class="card-detail-value"><?php echo formatBackupSize($file['size']); ?></span>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php
} // --- FIN DE LA MODIFICACIÓN (cierre del 'else' principal) ---
?>