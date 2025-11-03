<?php
// FILE: includes/sections/main/maintenance.php

// (Se asume que $basePath se carga desde router-guard.php o bootstrapper.php)
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'maintenance') ? 'active' : 'disabled'; ?>" data-section="maintenance">
    <div class="auth-container text-center">
        
        <div class="component-card__icon" style="background-color: transparent; width: 80px; height: 80px; margin: 0 auto 16px auto; border: none;">
             <span class="material-symbols-rounded" style="font-size: 80px; color: #6b7280;">engineering</span>
        </div>
        
        <h1 class="auth-title" data-i18n="page.maintenance.title"></h1>
        
        <p class="auth-verification-text mb-24" data-i18n="page.maintenance.description"></p>
        
        <div class="auth-step-buttons">
            <a href="<?php echo htmlspecialchars($basePath); ?>/login" 
               class="auth-button" 
               data-i18n="page.maintenance.adminLogin">
               </a>
        </div>
        
    </div>
</div>