<?php
// FILE: includes/sections/auth/account-status.php

// Esta variable $accountStatusType la define config/router.php ('deleted' o 'suspended')
$CURRENT_SECTION_CLASS = "account-status-{$accountStatusType}"; // e.g., 'account-status-deleted'

$titleKey = "page.status.{$accountStatusType}Title"; // e.g., 'page.status.deletedTitle'
$descKey = "page.status.{$accountStatusType}Desc";   // e.g., 'page.status.deletedDesc'

// Asegúrate de que $CURRENT_SECTION (definida en router.php) coincida con la clase actual
if ($CURRENT_SECTION !== $CURRENT_SECTION_CLASS) {
    // Si no coinciden (algo raro pasó), no mostrar nada o mostrar error
    echo "";
    return; 
}
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === $CURRENT_SECTION_CLASS) ? 'active' : 'disabled'; ?>" data-section="<?php echo $CURRENT_SECTION_CLASS; ?>">
    <div class="auth-container text-center">
        
        <h1 class="auth-title" data-i18n="<?php echo htmlspecialchars($titleKey); ?>"></h1>
        
        <p class="auth-verification-text mb-24" data-i18n="<?php echo htmlspecialchars($descKey); ?>"></p>
        
        <div class="auth-step-buttons">
            <a href="<?php echo htmlspecialchars($basePath); ?>/login" 
               class="auth-button" 
               data-i18n="page.status.backToLogin">
               </a>
        </div>
        
    </div>
</div>