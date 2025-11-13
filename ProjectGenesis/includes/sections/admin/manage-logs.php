<?php
// FILE: includes/sections/admin/manage-logs.php

// --- INICIO DE LÓGICA PHP ---

// Asumimos que $pdo y $basePath están disponibles desde 'router.php'
$logDir = dirname(__DIR__, 3) . '/logs'; // Ir 3 niveles arriba (sections/admin/ -> includes/ -> ProjectGenesis/) y luego a /logs
$logFiles = [];
$viewingLog = null;
$logContent = null;
$errorMessage = null;

// --- Funciones Helper (copiadas de manage-backups.php) ---
if (!function_exists('formatLogSize')) {
    function formatLogSize($bytes) {
        if ($bytes < 1024) return $bytes . ' B';
        $kb = $bytes / 1024;
        if ($kb < 1024) return round($kb, 2) . ' KB';
        $mb = $kb / 1024;
        return round($mb, 2) . ' MB';
    }
}
if (!function_exists('formatLogDate')) {
    function formatLogDate($timestamp) {
         return date('d/m/Y H:i:s', $timestamp);
    }
}
// --- Fin Funciones Helper ---


if (isset($_GET['view']) && !empty($_GET['view'])) {
    // --- VISTA DE DETALLE DE LOG ---
    $viewingLog = basename($_GET['view']); // ¡Seguridad! Evitar Path Traversal
    $filePath = $logDir . '/' . $viewingLog;

    if (file_exists($filePath) && is_readable($filePath)) {
        $logContent = file_get_contents($filePath);
        if ($logContent === false) {
            $errorMessage = "No se pudo leer el archivo de log."; // (Añadir clave i18n)
        }
    } else {
        $errorMessage = "El archivo de log no existe o no se puede leer."; // (Añadir clave i18n)
    }

} else {
    // --- VISTA DE LISTA DE LOGS ---
    if (!is_dir($logDir) || !is_readable($logDir)) {
        $errorMessage = "El directorio de logs no existe o no se puede leer."; // (Añadir clave i18n)
    } else {
        $files = glob($logDir . '/*.log');
        if ($files === false) {
             $errorMessage = "Error al leer el directorio de logs."; // (Añadir clave i18n)
        } else {
            // Ordenar por fecha de modificación (más reciente primero)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });

            foreach ($files as $file) {
                $logFiles[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'modified_at' => filemtime($file)
                ];
            }
        }
    }
}
// --- FIN DE LÓGICA PHP ---
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-manage-logs') ? 'active' : 'disabled'; ?>" data-section="admin-logs">

    <?php if ($viewingLog): ?>
        
        <div class="page-toolbar-container" id="log-view-toolbar-container">
            <div class="page-toolbar-floating">
                <div class="toolbar-action-default">
                    <div class="page-toolbar-left">
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="toggleSectionAdminManageLogs"
                            data-tooltip="Volver a la lista de logs">
                            <span class="material-symbols-rounded">arrow_back</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="component-wrapper" style="padding-top: 82px;">
            <div class="component-header-card">
                <h1 class="component-page-title" data-i18n="admin.logs.viewTitle">Viendo Log</h1>
                <p class="component-page-description"><?php echo htmlspecialchars($viewingLog); ?></p>
            </div>

            <?php if ($errorMessage): ?>
                <div class="component-card component-card--danger">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">error</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="admin.logs.errorTitle">Error</h2>
                            <p class="component-card__description"><?php echo htmlspecialchars($errorMessage); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="component-card component-card--column" style="align-items: stretch;">
                    <div class="component-card__content" style="width: 100%;">
                        <textarea readonly style="width: 100%; height: 60vh; min-height: 400px; border: 1px solid #00000020; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 12px; line-height: 1.6; background-color: #f5f5fa; resize: vertical;"><?php echo htmlspecialchars($logContent); ?></textarea>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="page-toolbar-container" id="log-toolbar-container">
            <div class="page-toolbar-floating">
                
                <div class="toolbar-action-default">
                    </div>
                
                <div class="toolbar-action-selection">
                    <div class="page-toolbar-left">
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="admin-log-view"
                            data-tooltip="Ver Log" disabled>
                            <span class="material-symbols-rounded">visibility</span>
                        </button>
                        </div>

                    <div class="page-toolbar-right">
                        <button type="button"
                            class="page-toolbar-button"
                            data-action="admin-log-clear-selection"
                            data-tooltip="Quitar selección" disabled>
                            <span class="material-symbols-rounded">close</span>
                        </button>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="component-wrapper" style="padding-top: 82px;">

            <?php outputCsrfInput(); ?>

            <div class="component-header-card">
                <h1 class="component-page-title" data-i18n="admin.logs.title">Administrar Logs</h1>
                <p class="component-page-description" data-i18n="admin.logs.description">Revisa los logs de errores del sistema y la base de datos.</p>
            </div>

            <?php if ($errorMessage): ?>
                <div class="component-card component-card--danger">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">error</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="admin.logs.errorTitle">Error</h2>
                            <p class="component-card__description"><?php echo htmlspecialchars($errorMessage); ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif (empty($logFiles)): ?>
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">draft</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="admin.logs.noLogsTitle">No hay logs</h2>
                            <p class="component-card__description" data-i18n="admin.logs.noLogsDesc">No se encontraron archivos .log en el directorio.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card-list-container" id="log-list-container">
                    <?php foreach ($logFiles as $file): ?>
                        <div class="card-item" 
                             data-log-filename="<?php echo htmlspecialchars($file['filename']); ?>"
                             style="gap: 16px; padding: 16px;">
                        
                            <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                                <span class="material-symbols-rounded" style="font-size: 28px;">description</span>
                            </div>

                            <div class="card-item-details">
                                <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                                    <span class="card-detail-value" style="font-size: 16px; font-weight: 600;"><?php echo htmlspecialchars($file['filename']); ?></span>
                                </div>
                                <div class="card-detail-item">
                                    <span class="card-detail-label" data-i18n="admin.logs.labelDate">Última modif.</span>
                                    <span class="card-detail-value"><?php echo formatLogDate($file['modified_at']); ?></span>
                                </div>
                                <div class="card-detail-item">
                                    <span class="card-detail-label" data-i18n="admin.logs.labelSize">Tamaño</span>
                                    <span class="card-detail-value"><?php echo formatLogSize($file['size']); ?></span>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

    <?php endif; ?>
</div>