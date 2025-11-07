<?php
// FILE: includes/sections/main/home.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">
    
    <div class="page-toolbar-container" id="home-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                
                <div class="page-toolbar-left">
                    
                    <!-- --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- -->
                    <div id="current-group-display" class="page-toolbar-group-display">
                        <!-- El JS pondrá aquí el nombre del grupo -->
                    </div>
                    
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="home-select-group"
                        data-tooltip="home.toolbar.selectGroup">
                        <span class="material-symbols-rounded">group</span>
                    </button>
                    <!-- --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- -->

                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionJoinGroup" 
                        data-tooltip="home.toolbar.joinGroup">
                    <span class="material-symbols-rounded">group_add</span>
                    </button>
                    
                </div>
                
                </div>

            </div>
            
        <!-- --- ▼▼▼ INICIO DE POPOVER AÑADIDO ▼▼▼ --- -->
        <div class="popover-module popover-module--anchor-left body-title disabled" data-module="moduleSelectGroup" style="top: calc(100% + 8px); left: 8px; width: 300px;">
            <div class="menu-content">
                <div class="menu-header" data-i18n="home.popover.title">Mis Grupos</div>
                <div class="menu-list" id="my-groups-list">
                    <div class="menu-link" data-i18n="home.popover.loading">Cargando...</div>
                </div>
            </div>
        </div>
        <!-- --- ▲▲▲ FIN DE POPOVER AÑADIDO ▲▲▲ --- -->
    </div>

    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="home.main.title"></h1>
            <p class="component-page-description" data-i18n="home.main.description"></p>
        </div>

        <div class="component-card">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Contenido de Ejemplo</h2>
                    <p class="component-card__description">
                        Aquí iría el contenido del grupo seleccionado...
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>