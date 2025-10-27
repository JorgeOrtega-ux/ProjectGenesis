<?php
// --- ▼▼▼ INICIO DE LÓGICA PHP PARA ESTA PÁGINA ▼▼▼ ---

// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
$sessions = [];
$currentSessionId = session_id(); 

try {
    $stmt = $pdo->prepare(
        "SELECT id, ip_address, device_type, browser_info, created_at 
         FROM user_metadata 
         WHERE user_id = ? 
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
    $browserText = ''; // Para texto que no es clave (ej. "Chrome")
    $osText = ''; // Para texto que no es clave (ej. "Windows 10/11")

    // Detectar OS
    if (preg_match('/windows nt 10/i', $userAgent)) { $osText = 'Windows 10/11'; $osKey = null; }
    elseif (preg_match('/windows/i', $userAgent)) { $osText = 'Windows'; $osKey = null; }
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) { $osText = 'macOS'; $osKey = null; }
    elseif (preg_match('/android/i', $userAgent)) { $osText = 'Android'; $osKey = null; }
    elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) { $osText = 'iOS'; $osKey = null; }
    elseif (preg_match('/linux/i', $userAgent)) { $osText = 'Linux'; $osKey = null; }

    // Detectar Navegador
    if (preg_match('/edg/i', $userAgent)) { $browserText = 'Edge'; $browserKey = null; }
    elseif (preg_match('/chrome/i', $userAgent)) { $browserText = 'Chrome'; $browserKey = null; }
    elseif (preg_match('/safari/i', $userAgent)) { $browserText = 'Safari'; $browserKey = null; }
    elseif (preg_match('/firefox/i', $userAgent)) { $browserText = 'Firefox'; $browserKey = null; }
    
    // Construir el HTML. 
    // Si la clave existe, usa <span>. Si no, usa el texto escapado.
    $browserHtml = $browserKey 
        ? '<span data-i18n="' . $browserKey . '"></span>' 
        : htmlspecialchars($browserText);
        
    $osHtml = $osKey 
        ? '<span data-i18n="' . $osKey . '"></span>' 
        : htmlspecialchars($osText);

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

<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-devices') ? 'active' : 'disabled'; ?>" data-section="settings-devices">
    <div class="settings-wrapper">

        <div class="settings-header-card">
            <h1 class="settings-title" data-i18n="settings.devices.title"></h1>
            <p class="settings-description" data-i18n="settings.devices.description"></p>
        </div>
        
        <div class="settings-card settings-card-column">
            <div class="settings-text-content">
                <h2 class="settings-text-title" data-i18n="settings.devices.invalidateTitle"></h2>
                <p class="settings-text-description" data-i18n="settings.devices.invalidateDesc"></p>
            </div>
            
            <div class="settings-card-bottom">
                <div class="settings-card-right-actions">
                    <button type="button" class="settings-button" id="logout-all-devices-trigger" data-i18n="settings.devices.invalidateButton"></button>
                </div>
            </div>
        </div>
        
        <div style="padding: 16px 8px 0px 8px;">
            <h2 class="settings-text-title" style="font-size: 18px;" data-i18n="settings.devices.activeSessionsTitle"></h2>
        </div>

        <?php if (empty($sessions)): ?>
            <div class="settings-card">
                <div class="settings-card-left">
                    <div class="settings-text-content">
                        <h2 class="settings-text-title" data-i18n="settings.devices.noSessionsTitle"></h2>
                        <p class="settings-text-description" data-i18n="settings.devices.noSessionsDesc"></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($sessions as $session): ?>
                <?php
                    $deviceIcon = ($session['device_type'] === 'Mobile') ? 'smartphone' : 'computer';
                    
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN DEL BUCLE ▼▼▼ ---
                    // Estas funciones ahora devuelven HTML, no texto plano
                    $deviceInfo = formatUserAgent($session['browser_info']);
                    $sessionDate = formatSessionDate($session['created_at']);
                ?>
                <div class="settings-card" data-session-card-id="<?php echo $session['id']; ?>">
                    <div class="settings-card-left">
                        <div class="settings-card-icon">
                            <span class="material-symbols-rounded"><?php echo $deviceIcon; ?></span>
                        </div>
                        <div class="settings-text-content">
                            
                            <h2 class="settings-text-title"><?php echo $deviceInfo; ?></h2> 
                            
                            <p class="settings-text-description">
                                <?php echo htmlspecialchars($session['ip_address']); ?> - 
                                <span data-i18n="settings.devices.lastAccess"></span> 
                                
                                <?php echo $sessionDate; ?> 
                            </p>
                        </div>
                    </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

    </div>

    <div class="settings-modal-overlay" id="logout-all-modal" style="display: none;">
        <button type="button" class="settings-modal-close-btn" id="logout-all-close">
            <span class="material-symbols-rounded">close</span>
        </button>
        <div class="settings-modal-content">
            <div class="auth-form">
                <fieldset class="auth-step active">
                    <h2 class="auth-title" data-i18n="settings.devices.modalTitle"></h2>
                    <p class="auth-verification-text" data-i18n="settings.devices.modalDesc"></p>
                    
                    <div class="auth-step-buttons">
                        <button type="button" class="auth-button-back" id="logout-all-cancel" style="flex: 1;" data-i18n="settings.devices.modalCancel"></button>
                        <button type="button" class="auth-button danger" id="logout-all-confirm" style="flex: 1; background-color: #c62828; border-color: #c62828;" data-i18n="settings.devices.modalConfirm"></button>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
    
    </div>