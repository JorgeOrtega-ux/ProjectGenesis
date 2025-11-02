<?php
// FILE: includes/sections/admin/server-settings.php

// (Se asume que config/router.php ya ha iniciado $pdo y la sesión)
// Las variables $maintenanceModeStatus y $allowRegistrationStatus son cargadas por config/router.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'admin-server-settings') ? 'active' : 'disabled'; ?>" data-section="admin-server-settings">
    <div class="component-wrapper">

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="admin.server.title"></h1>
            <p class="component-page-description" data-i18n="admin.server.description"></p>
        </div>

        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.maintenanceTitle"></h2>
                    <p class="component-card__description" data-i18n="admin.server.maintenanceDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-maintenance-mode"
                           <?php echo ($maintenanceModeStatus == 1) ? 'checked' : ''; ?>
                           <?php echo ($_SESSION['role'] !== 'founder') ? 'disabled' : ''; ?>
                           > 
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="admin.server.registrationTitle">Permitir nuevos registros</h2>
                    <p class="component-card__description" data-i18n="admin.server.registrationDesc">Si está inactivo, los nuevos usuarios no podrán registrarse. Se desactiva automáticamente con el modo mantenimiento.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-allow-registration"
                           <?php echo ($allowRegistrationStatus == 1) ? 'checked' : ''; ?>
                           <?php 
                           // Deshabilitado si no es fundador O si el modo mantenimiento está activo
                           echo ($_SESSION['role'] !== 'founder' || $maintenanceModeStatus == 1) ? 'disabled' : ''; 
                           ?>
                           > 
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        </div>
</div>