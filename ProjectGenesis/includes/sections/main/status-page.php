<?php
// FILE: includes/sections/main/status-page.php
// (Versión CORREGIDA del archivo unificado)

// (Se asume que $basePath y $CURRENT_SECTION se cargan desde bootstrapper.php y router.php)

// --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA) ▼▼▼ ---
$formattedExpiryDate = null;
$dataAttributes = '';

// Función helper para formatear la fecha (local a este archivo)
if (!function_exists('formatExpiryDate')) {
    /**
     * Formatea un timestamp UTC a un string legible en español.
     * @param string $dateString El timestamp UTC de la BD.
     * @return string|null
     */
    function formatExpiryDate($dateString) {
        if (empty($dateString)) return null;
        try {
            $date = new DateTime($dateString, new DateTimeZone('UTC'));
            // Intentar establecer la localización en español
            $locale = setlocale(LC_TIME, 'es_ES.UTF-8', 'Spanish_Spain.1252', 'es_MX.UTF-8', 'es_ES', 'es');

            if ($locale && class_exists('IntlDateFormatter')) {
                // Opción 1: Usar IntlDateFormatter (preferido)
                $formatter = new IntlDateFormatter(
                    'es_ES', // Usar el locale
                    IntlDateFormatter::LONG, 
                    IntlDateFormatter::SHORT,
                    'UTC', // La fecha de entrada es UTC
                    IntlDateFormatter::GREGORIAN
                );
                // El formato de Intl es 'd \'de\' MMMM \'de\' y, HH:mm z'
                return $formatter->format($date);
            } else {
                // Opción 2: Fallback manual (no traduce meses)
                return $date->format('Y-m-d H:i') . ' UTC';
            }

        } catch (Exception $e) {
            logDatabaseError($e, 'status-page - formatExpiryDate');
            return null;
        }
    }
}

if ($CURRENT_SECTION === 'account-status-suspended' && isset($_SESSION['suspension_expires_at'])) {
    $formattedExpiryDate = formatExpiryDate($_SESSION['suspension_expires_at']);
    unset($_SESSION['suspension_expires_at']); // Limpiar
} elseif ($CURRENT_SECTION === 'messaging-restricted' && isset($_SESSION['restriction_expires_at'])) {
    $formattedExpiryDate = formatExpiryDate($_SESSION['restriction_expires_at']);
    unset($_SESSION['restriction_expires_at']); // Limpiar
}
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


$icon = 'info';
$titleKey = 'page.404.title';
$descKey = 'page.404.description';

switch ($CURRENT_SECTION) {
    case 'maintenance':
        $icon = 'engineering';
        $titleKey = 'page.maintenance.title';
        $descKey = 'page.maintenance.description';
        break;
        
    case 'server-full':
        $icon = 'group_off';
        $titleKey = 'page.serverfull.title';
        $descKey = 'page.serverfull.description';
        break;

    case 'account-status-suspended':
        $icon = 'pause_circle';
        $titleKey = 'page.status.suspendedTitle';
        // --- ▼▼▼ MODIFICACIÓN (TAREA) ▼▼▼ ---
        if ($formattedExpiryDate) {
            $descKey = 'page.status.suspendedDescTemporary';
            $dataAttributes = ' data-i18n-date="' . htmlspecialchars($formattedExpiryDate, ENT_QUOTES) . '"';
        } else {
            $descKey = 'page.status.suspendedDesc';
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        break;
        
    case 'account-status-deleted':
        $icon = 'remove_circle';
        $titleKey = 'page.status.deletedTitle';
        $descKey = 'page.status.deletedDesc';
        break;
    
    case 'messaging-disabled':
        $icon = 'chat_error';
        $titleKey = 'page.messaging_disabled.title'; 
        $descKey = 'page.messaging_disabled.description';
        break;
        
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    case 'messaging-restricted':
        $icon = 'voice_over_off'; // Icono que implica "silenciado" o "restringido"
        $titleKey = 'page.status.restrictedTitle'; // Nueva clave de traducción
        // --- ▼▼▼ MODIFICACIÓN (TAREA) ▼▼▼ ---
        if ($formattedExpiryDate) {
            $descKey = 'page.status.restrictedDescTemporary';
            $dataAttributes = ' data-i18n-date="' . htmlspecialchars($formattedExpiryDate, ENT_QUOTES) . '"';
        } else {
            $descKey = 'page.status.restrictedDesc';
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        break;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
?>

<div class="section-content overflow-y <?php echo (strpos($CURRENT_SECTION, 'account-status-') === 0 || $CURRENT_SECTION === 'maintenance' || $CURRENT_SECTION === 'server-full' || $CURRENT_SECTION === 'messaging-disabled' || $CURRENT_SECTION === 'messaging-restricted') ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">
<div class="auth-container text-center">
        
        <div class="component-card__icon">
             <span class="material-symbols-rounded"><?php echo $icon; ?></span>
        </div>
        
        <h1 class="auth-title" data-i18n="<?php echo htmlspecialchars($titleKey); ?>"></h1>
        
        <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA) ▼▼▼ --- ?>
        <p class="auth-verification-text mb-24" data-i18n="<?php echo htmlspecialchars($descKey); ?>"<?php echo $dataAttributes; ?>></p>
        <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>
        
        <?php 
        // El bloque de botones ha sido eliminado
        ?>
        
    </div>
</div>