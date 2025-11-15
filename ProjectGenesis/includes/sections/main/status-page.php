<?php
// FILE: includes/sections/main/status-page.php
// (Versión CORREGIDA del archivo unificado)

// (Se asume que $basePath y $CURRENT_SECTION se cargan desde bootstrapper.php y router.php)

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
        $descKey = 'page.status.suspendedDesc';
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
        $descKey = 'page.status.restrictedDesc'; // Nueva clave de traducción
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
        
        <p class="auth-verification-text mb-24" data-i18n="<?php echo htmlspecialchars($descKey); ?>"></p>
        
        <?php 
        // El bloque de botones ha sido eliminado
        ?>
        
    </div>
</div>