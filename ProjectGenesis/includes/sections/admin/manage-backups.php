<?php
// FILE: includes/sections/admin/manage-backups.php

// --- INICIO DE LÓGICA PHP ---
// Define la ruta al directorio de backups (fuera del directorio web público)
$backupDir = dirname(__DIR__, 3) . '/backups'; // Sube 3 niveles (includes/sections/admin -> projectgenesis/backups)
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
    // Obtener todos los archivos .sql, ordenados por fecha (más nuevos primero)
    $files = glob($backupDir . '/*.sql');
    if ($files === false) {
         $hasError = true;
         $errorMessage = 'admin.backups.errorDirRead';
    } else {
        // Ordenar archivos por fecha de modificación (más nuevos primero)
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

/**
 * Formatea bytes a un tamaño legible (KB, MB, GB)
 */
function formatBackupSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $kb = $bytes / 1024;
    if ($kb < 1024) return round($kb, 2) . ' KB';
    $mb = $kb / 1024;
    if ($mb < 1024) return round($mb, 2) . ' MB';
    $gb = $mb / 1024;
    return round($gb, 2) . ' GB';
}
// --- FIN DE LÓGICA PHP ---
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