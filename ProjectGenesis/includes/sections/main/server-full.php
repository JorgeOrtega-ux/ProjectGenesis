<?php
// FILE: includes/sections/main/server-full.php

// (Se asume que $basePath se carga desde bootstrapper.php)
// (CURRENT_SECTION es definido por router.php)
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'server-full') ? 'active' : 'disabled'; ?>" data-section="server-full">
    <div class="auth-container text-center">
        
        <div class="component-card__icon" style="background-color: transparent; width: 80px; height: 80px; margin: 0 auto 16px auto; border: none;">
             <span class="material-symbols-rounded" style="font-size: 80px; color: #6b7280;">group_off</span>
        </div>
        
        <h1 class="auth-title" data-i18n="page.serverfull.title">Servidor Lleno</h1>
        
        <p class="auth-verification-text mb-24" data-i18n="page.serverfull.description">Lo sentimos, el servidor ha alcanzado su capacidad máxima de usuarios. Por favor, inténtalo de nuevo en unos minutos.</p>
        
        <div class="auth-step-buttons">
            <a href="<?php echo htmlspecialchars($basePath); ?>/login" 
               class="auth-button" 
               data-i18n="page.serverfull.backToLogin">
               Volver a Inicio de Sesión
               </a>
        </div>
        
    </div>
</div>