<?php
// --- ▼▼▼ INICIO DE LÓGICA PHP PARA ESTA PÁGINA ▼▼▼ ---

// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
$sessions = [];
$currentSessionId = session_id(); // No lo usamos para la consulta, pero es útil saberlo

try {
    // 1. Obtener todas las sesiones de la tabla user_metadata
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
    // $sessions se quedará como un array vacío y el HTML mostrará un error
}

// 2. Funciones Helper para formatear los datos
// (Estas pueden moverse a un archivo 'utils.php' si se reúsan)

/**
 * Intenta parsear un User-Agent string para obtener Navegador y SO.
 * @param string $userAgent El string de $_SERVER['HTTP_USER_AGENT']
 * @return string
 */
function formatUserAgent($userAgent) {
    // Esta es una función MUY simplificada. 
    // Para producción, se recomienda usar una librería como 'whichbrowser/parser'
    
    // NOTA: Estas cadenas (Navegador Desconocido, en) se podrían traducir
    // pasando el array de traducciones a esta función, pero
    // por ahora se quedan estáticas en español.
    $browser = 'Navegador Desconocido';
    $os = 'SO Desconocido';

    // Detectar OS
    if (preg_match('/windows nt 10/i', $userAgent)) $os = 'Windows 10/11';
    elseif (preg_match('/windows/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';
    elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';

    // Detectar Navegador
    if (preg_match('/edg/i', $userAgent)) $browser = 'Edge';
    elseif (preg_match('/chrome/i', $userAgent)) $browser = 'Chrome';
    elseif (preg_match('/safari/i', $userAgent)) $browser = 'Safari';
    elseif (preg_match('/firefox/i', $userAgent)) $browser = 'Firefox';
    
    return "$browser en $os";
}

/**
 * Formatea una fecha/hora de la BD a un string legible.
 * @param string $dateTimeString
 * @return string
 */
function formatSessionDate($dateTimeString) {
    try {
        // NOTA: Estas cadenas (hace, año, etc.) también se podrían
        // traducir pasando el array de traducciones.
        $date = new DateTime($dateTimeString, new DateTimeZone('UTC'));
        // (Asumimos que el usuario quiere ver la hora en su zona horaria local)
        // (Pero sin su zona, solo podemos mostrar UTC o un 'hace X tiempo')
        
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $interval = $now->diff($date);

        if ($interval->y > 0) return 'hace ' . $interval->y . ' ' . ($interval->y == 1 ? 'año' : 'años');
        if ($interval->m > 0) return 'hace ' . $interval->m . ' ' . ($interval->m == 1 ? 'mes' : 'meses');
        if ($interval->d > 0) return 'hace ' . $interval->d . ' ' . ($interval->d == 1 ? 'día' : 'días');
        if ($interval->h > 0) return 'hace ' . $interval->h . ' ' . ($interval->h == 1 ? 'hora' : 'horas');
        if ($interval->i > 0) return 'hace ' . $interval->i . ' ' . ($interval->i == 1 ? 'minuto' : 'minutos');
        return 'hace unos segundos';

    } catch (Exception $e) {
        return 'fecha desconocida';
    }
}

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
                    $deviceInfo = formatUserAgent($session['browser_info']);
                    $sessionDate = formatSessionDate($session['created_at']);
                    $deviceInfoWithIp = $deviceInfo . ' (' . $session['ip_address'] . ')';
                ?>
                <div class="settings-card" data-session-card-id="<?php echo $session['id']; ?>">
                    <div class="settings-card-left">
                        <div class="settings-card-icon">
                            <span class="material-symbols-rounded"><?php echo $deviceIcon; ?></span>
                        </div>
                        <div class="settings-text-content">
                            <h2 class="settings-text-title"><?php echo htmlspecialchars($deviceInfo); ?></h2>
                            <p class="settings-text-description">
                                <?php echo htmlspecialchars($session['ip_address']); ?> - 
                                <span data-i18n="settings.devices.lastAccess"></span> <?php echo htmlspecialchars($sessionDate); ?>
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