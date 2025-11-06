<?php
// FILE: includes/sections/main/home.php
// (Contenido MODIFICADO para añadir la nueva barra de herramientas)
?>

<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'home') ? 'active' : 'disabled'; ?>" data-section="home">

    <!-- ▼▼▼ INICIO DE LA TOOLBAR AÑADIDA ▼▼▼ -->
    <div class="page-toolbar-container" id="home-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <!-- Botón para abrir el modal -->
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleGroupSelectModal"
                        data-tooltip="toolbar.buttons.selectGroup">
                        <span class="material-symbols-rounded">groups</span>
                    </button>
                    
                    <!-- Div para mostrar el grupo seleccionado -->
                    <div class="toolbar-group-display" id="selected-group-display">
                        <span class="material-symbols-rounded">label</span>
                        <span class="toolbar-group-text" data-i18n="toolbar.noGroupSelected">Ningún grupo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ▲▲▲ FIN DE LA TOOLBAR AÑADIDA ▲▲▲ -->

    <div class="component-wrapper" style="padding-top: 82px;"> <!-- Añadido padding-top para la toolbar -->

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="home.empty.title">Página Principal</h1>
            <p class="component-page-description" data-i18n="home.empty.description">
                Esta es tu página principal. El contenido se añadirá próximamente.
            </p>
        </div>

    </div>
</div>