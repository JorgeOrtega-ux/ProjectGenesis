<?php
// FILE: includes/sections/main/status-page.php
// (Versión CORREGIDA del archivo unificado)

// (Se asume que $basePath y $CURRENT_SECTION se cargan desde bootstrapper.php y router.php)

$icon = 'info';
$titleKey = 'page.404.title';
$descKey = 'page.404.description';
$buttonKey = 'page.status.backToLogin';
$buttonLink = htmlspecialchars($basePath) . '/login';

switch ($CURRENT_SECTION) {
    case 'maintenance':
        $icon = 'engineering';
        $titleKey = 'page.maintenance.title';
        $descKey = 'page.maintenance.description';
        $buttonKey = 'page.maintenance.adminLogin'; // Botón de login para admins
        break;
        
    case 'server-full':
        $icon = 'group_off';
        $titleKey = 'page.serverfull.title';
        $descKey = 'page.serverfull.description';
        $buttonKey = 'page.serverfull.backToLogin';
        break;

    case 'account-status-suspended':
        $icon = 'pause_circle';
        $titleKey = 'page.status.suspendedTitle';
        $descKey = 'page.status.suspendedDesc';
        $buttonKey = 'page.status.backToLogin';
        break;
        
    case 'account-status-deleted':
        $icon = 'remove_circle';
        $titleKey = 'page.status.deletedTitle';
        $descKey = 'page.status.deletedDesc';
        $buttonKey = 'page.status.backToLogin';
        break;
}
?>

<div class="section-content overflow-y <?php echo (strpos($CURRENT_SECTION, 'account-status-') === 0 || $CURRENT_SECTION === 'maintenance' || $CURRENT_SECTION === 'server-full') ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">
<div class="auth-container text-center">
        
        <div class="component-card__icon" style="background-color: transparent; width: 80px; height: 80px; margin: 0 auto 16px auto; border: none;">
             <span class="material-symbols-rounded" style="font-size: 80px; color: #6b7280;"><?php echo $icon; ?></span>
        </div>
        
        <h1 class="auth-title" data-i18n="<?php echo htmlspecialchars($titleKey); ?>"></h1>
        
        <p class="auth-verification-text mb-24" data-i18n="<?php echo htmlspecialchars($descKey); ?>"></p>
        
        <div class="auth-step-buttons">
            <a href="<?php echo $buttonLink; ?>" 
               class="auth-button" 
               data-i18n="<?php echo htmlspecialchars($buttonKey); ?>">
            </a>
        </div>
        
    </div>
</div>