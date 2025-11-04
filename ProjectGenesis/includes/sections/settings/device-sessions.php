<?php
// --- ▼▼▼ INICIO DE LÓGICA PHP PARA ESTA PÁGINA ▼▼▼ ---

// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
$sessions = [];
$currentSessionId = session_id(); 
// --- ▼▼▼ LÍNEA AÑADIDA (Paso 5) ▼▼▼ ---
$currentMetadataId = $_SESSION['metadata_id'] ?? 0;
// --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

try {
    $stmt = $pdo->prepare(
        "SELECT id, ip_address, device_type, browser_info, created_at 
         FROM user_metadata 
         WHERE user_id = ? 
         AND is_active = 1 -- <-- ¡MODIFICACIÓN! Solo mostrar sesiones activas
         ORDER BY created_at DESC"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $sessions = $stmt->fetchAll();

} catch (PDOException $e) {
    logDatabaseError($e, 'router - settings-devices');
    // $sessions se quedará como un array vacío
}

// 2. Funciones Helper para formatear los datos

/**
 * Intenta parsear un User-Agent string para obtener Navegador y SO.
 * @param string $userAgent El string de $_SERVER['HTTP_USER_AGENT']
 * @return string
 */
// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA ▼▼▼ ---
function formatUserAgent($userAgent) {
    
    $browserKey = 'settings.devices.unknownBrowser';
    $osKey = 'settings.devices.unknownOS';
    
    // --- Quitamos $browserText y $osText ---

    // Detectar OS
    if (preg_match('/windows nt 10/i', $userAgent)) { $osKey = 'settings.devices.osWindows11'; }
    elseif (preg_match('/windows/i', $userAgent)) { $osKey = 'settings.devices.osWindows'; }
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) { $osKey = 'settings.devices.osMacOS'; }
    elseif (preg_match('/android/i', $userAgent)) { $osKey = 'settings.devices.osAndroid'; }
    elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) { $osKey = 'settings.devices.osIOS'; }
    elseif (preg_match('/linux/i', $userAgent)) { $osKey = 'settings.devices.osLinux'; }

    // Detectar Navegador
    if (preg_match('/edg/i', $userAgent)) { $browserKey = 'settings.devices.browserEdge'; }
    elseif (preg_match('/chrome/i', $userAgent)) { $browserKey = 'settings.devices.browserChrome'; }
    elseif (preg_match('/safari/i', $userAgent)) { $browserKey = 'settings.devices.browserSafari'; }
    elseif (preg_match('/firefox/i', $userAgent)) { $browserKey = 'settings.devices.browserFirefox'; }
    
    // Construir el HTML. 
    // Ahora SIEMPRE usamos claves.
    $browserHtml = '<span data-i18n="' . $browserKey . '"></span>';
    $osHtml = '<span data-i18n="' . $osKey . '"></span>';

    // Devolver el string HTML listo para ser interpretado por i18n-manager.js
    return $browserHtml . ' <span data-i18n="settings.devices.browserOsSeparator"></span> ' . $osHtml;
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA ▲▲▲ ---


/**
 * Formatea una fecha/hora de la BD a un string legible.
 * @param string $dateTimeString
 * @return string
 */
// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA ▼▼▼ ---
function formatSessionDate($dateTimeString) {
    try {
        $date = new DateTime($dateTimeString, new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $interval = $now->diff($date);

        // Devolvemos HTML con los `data-i18n` correctos
        if ($interval->y > 0) {
            $key = ($interval->y == 1) ? 'settings.devices.timeYear' : 'settings.devices.timeYears';
            return '<span data-i18n="settings.devices.timeAgoPrefix"></span> ' . $interval->y . ' <span data-i18n="' . $key . '"></span>';
        }
        if ($interval->m > 0) {
            $key = ($interval->m == 1) ? 'settings.devices.timeMonth' : 'settings.devices.timeMonths';
            return '<span data-i18n="settings.devices.timeAgoPrefix"></span> ' . $interval->m . ' <span data-i18n="' . $key . '"></span>';
        }
        if ($interval->d > 0) {
            $key = ($interval->d == 1) ? 'settings.devices.timeDay' : 'settings.devices.timeDays';
            return '<span data-i18n="settings.devices.timeAgoPrefix"></span> ' . $interval->d . ' <span data-i18n="' . $key . '"></span>';
        }
        if ($interval->h > 0) {
            $key = ($interval->h == 1) ? 'settings.devices.timeHour' : 'settings.devices.timeHours';
            return '<span data-i18n="settings.devices.timeAgoPrefix"></span> ' . $interval->h . ' <span data-i18n="' . $key . '"></span>';
        }
        if ($interval->i > 0) {
            $key = ($interval->i == 1) ? 'settings.devices.timeMinute' : 'settings.devices.timeMinutes';
            return '<span data-i18n="settings.devices.timeAgoPrefix"></span> ' . $interval->i . ' <span data-i18n="' . $key . '"></span>';
        }
        
        return '<span data-i18n="settings.devices.timeSecondsAgo"></span>';

    } catch (Exception $e) {
        return '<span data-i18n="settings.devices.timeUnknown"></span>';
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA ▲▲▲ ---

// --- ▲▲▲ FIN DE LÓGICA PHP ▲▲▲ ---
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-devices') ? 'active' : 'disabled'; ?>" data-section="settings-devices">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.devices.title"></h1>
            <p class="component-page-description" data-i18n="settings.devices.description"></p>
        </div>
        
        <div class="component-card component-card--action"> <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.devices.invalidateTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.devices.invalidateDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <button type="button" class="component-button" id="logout-all-devices-trigger" data-i18n="settings.devices.invalidateButton"></button>
            </div>
        </div>

        <div class="component-card" style="padding: 16px 24px;">
            <div class="component-card__content" style="gap: 12px;">
                <div class="component-card__icon" style="width: 40px; height: 40px; border-radius: 50%;">
                    <span class="material-symbols-rounded" style="font-size: 20px;">devices</span>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" style="font-size: 18px; font-weight: 600;" data-i18n="settings.devices.activeSessionsTitle"></h2>
                </div>
            </div>
        </div>
        <?php if (empty($sessions)): ?>
            <div class="component-card">
                <div class="component-card__content">
                    <div class="component-card__text">
                        <h2 class="component-card__title" data-i18n="settings.devices.noSessionsTitle"></h2>
                        <p class="component-card__description" data-i18n="settings.devices.noSessionsDesc"></p>
                    </div>
                </div>
                </div>
            <?php else: ?>
            <?php foreach ($sessions as $session): ?>
                <?php
                    $deviceIcon = ($session['device_type'] === 'Mobile') ? 'smartphone' : 'computer';
                    $deviceInfo = formatUserAgent($session['browser_info']);
                    $sessionDate = formatSessionDate($session['created_at']);
                    // --- ▼▼▼ LÍNEA AÑADIDA (Paso 5) ▼▼▼ ---
                    $isCurrentSession = ($session['id'] == $currentMetadataId);
                    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---
                ?>
                <div class="component-card" data-session-card-id="<?php echo $session['id']; ?>">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded"><?php echo $deviceIcon; ?></span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title"><?php echo $deviceInfo; ?></h2> 
                            <p class="component-card__description">
                                <?php echo htmlspecialchars($session['ip_address']); ?> - 
                                <span data-i18n="settings.devices.lastAccess"></span> 
                                <?php echo $sessionDate; ?> 
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($isCurrentSession): ?>
                        <span class="session-badge-current" data-i18n="settings.devices.currentSession">Esta sesión</span>
                    <?php else: ?>
                        <div class="component-card__actions">
                            <button type="button" 
                                    class="component-button danger" 
                                    data-action="logout-individual-session" 
                                    data-session-id="<?php echo $session['id']; ?>"
                                    data-i18n="settings.devices.logoutButton">
                                Cerrar sesión
                            </button>
                        </div>
                    <?php endif; ?>
                    </div>
                <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <div class="modal-overlay" id="logout-all-modal">
        <div class="modal-content">
            <div class="modal__header">
                <h2 class="modal__title" data-i18n="settings.devices.modalTitle"></h2>
            </div>
            
            <div class="modal__body">
                <p class="modal__description" data-i18n="settings.devices.modalDesc"></p>
            </div>
            <div class="modal__footer modal__footer--small-buttons">
                <button type="button" class="modal__button-small modal__button-small--secondary" id="logout-all-cancel" data-i18n="settings.devices.modalCancel"></button>
                <button type="button" class="modal__button-small modal__button-small--danger" id="logout-all-confirm" data-i18n="settings.devices.modalConfirm"></button>
            </div>
            
        </div>
    </div>
</div>