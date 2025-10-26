<div class="module-content module-surface body-title disabled" data-module="moduleSurface">
    <div class="menu-content">
        <div class="menu-list">
            
            <?php 
            // La variable $isSettingsPage viene de index.php
            if (isset($isSettingsPage) && $isSettingsPage): 
            ?>
                <div class="menu-link" data-action="toggleSectionHome">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Volver a Inicio</span>
                    </div>
                </div>
                
                <div class="menu-link" data-action="toggleSectionSettingsProfile">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">account_circle</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Tu Perfil</span>
                    </div>
                </div>

                <div class="menu-link" data-action="toggleSectionSettingsLogin">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">security</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Inicio de Sesión y Seguridad</span>
                    </div>
                </div>

                <div class="menu-link" data-action="toggleSectionSettingsDevices">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">devices</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Sesiones de Dispositivos</span>
                    </div>
                </div>
                <div class="menu-link" data-action="toggleSectionSettingsAccess">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">accessibility</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Accesibilidad</span>
                    </div>
                </div>
                <?php else: ?>

                <div class="menu-link active" data-action="toggleSectionHome">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">home</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Pagina principal</span>
                    </div>
                </div>
                
                <div class="menu-link" data-action="toggleSectionExplorer">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">groups</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Explorar comunidades</span>
                    </div>
                </div>
                <?php endif; ?>

        </div>
    </div>
</div>