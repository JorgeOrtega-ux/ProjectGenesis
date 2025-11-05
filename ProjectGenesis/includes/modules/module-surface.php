<?php
// FILE: jorgeortega-ux/projectgenesis/ProjectGenesis-98418948306e47bc505f1797114031c3351b5e33/ProjectGenesis/includes/modules/module-surface.php
?>
<div class="module-content module-surface body-title disabled" data-module="moduleSurface">
    <div class="menu-content">
        
        <div class="menu-layout">
            <div class="menu-layout__top">
                <div class="menu-list">
                    
                    <?php 
                    // La variable $isSettingsPage y $isAdminPage vienen de config/menu_router.php
                    if (isset($isSettingsPage) && $isSettingsPage): 
                    ?>
                        <div class="menu-link" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">arrow_back</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.backToHome"></span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionSettingsProfile">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">account_circle</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.yourProfile"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionSettingsLogin">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">security</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.loginSecurity"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionSettingsAccess">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">accessibility</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.settings.accessibility"></span>
                            </div>
                        </div>
                        
                    <?php 
                    elseif (isset($isAdminPage) && $isAdminPage): 
                    ?>
                        <div class="menu-link" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">arrow_back</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.backToHome"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionAdminDashboard">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">dashboard</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.dashboard"></span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionAdminManageUsers">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">manage_accounts</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.manageUsers"></span>
                            </div>
                        </div>
                        
                    <?php 
                    elseif (isset($isHelpPage) && $isHelpPage): 
                    ?>
                        <div class="menu-link" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">arrow_back</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.backToHome">Volver a Inicio</span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionHelpSendFeedback">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">feedback</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.sendFeedback">Enviar comentarios</span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionHelpLegalNotice">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">gavel</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.legalNotice">Aviso legal</span>
                            </div>
                        </div>

                        <div class="menu-link" data-action="toggleSectionHelpPrivacyPolicy">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">policy</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.privacyPolicy">Política de privacidad</span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionHelpCookiesPolicy">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">cookie</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.cookiesPolicy">Política de cookies</span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionHelpTermsConditions">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">description</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.help.termsConditions">Términos y condiciones</span>
                            </div>
                        </div>
                    <?php 
                    else: 
                    ?>
                        <div class="menu-link active" data-action="toggleSectionHome">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">home</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.main.home"></span>
                            </div>
                        </div>
                        
                        <div class="menu-link" data-action="toggleSectionExplorer">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">groups</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.main.explore"></span>
                            </div>
                        </div>
                    <?php 
                    endif; 
                    ?>
                </div>
            </div>

            <div class="menu-layout__bottom">
                <div class="menu-list">
                    <?php 
                    // Solo mostrar este bloque si es la página de admin
                    if (isset($isAdminPage) && $isAdminPage): 
                    ?>
                        <?php // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (ORDEN) ▼▼▼ --- ?>
                        <?php if ($_SESSION['role'] === 'founder'): ?>
                            <div class="menu-link" data-action="toggleSectionAdminManageBackups">
                                <div class="menu-link-icon">
                                    <span class="material-symbols-rounded">backup</span>
                                </div>
                                <div class="menu-link-text">
                                    <span data-i18n="sidebar.admin.manageBackups">Gestionar Copias</span> 
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="menu-link" data-action="toggleSectionAdminServerSettings">
                            <div class="menu-link-icon">
                                <span class="material-symbols-rounded">dns</span>
                            </div>
                            <div class="menu-link-text">
                                <span data-i18n="sidebar.admin.serverSettings">Config. del Servidor</span> 
                            </div>
                        </div>
                        <?php // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ --- ?>

                    <?php 
                    endif; 
                    ?>
                </div>
            </div>
        </div>
        </div>
</div>