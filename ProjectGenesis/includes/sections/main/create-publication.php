<?php
// FILE: includes/sections/main/create-publication.php

// Determina qué pestaña está activa basada en la sección actual
$isPollActive = ($CURRENT_SECTION === 'create-poll');
$isPostActive = !$isPollActive;
?>
<div class="section-content overflow-y <?php echo (strpos($CURRENT_SECTION, 'create-') === 0) ? 'active' : 'disabled'; ?>" data-section="<?php echo htmlspecialchars($CURRENT_SECTION); ?>">

    <div class="page-toolbar-container" id="create-post-toolbar-container">
        <div class="page-toolbar-floating">
            <div class="toolbar-action-default">
                <div class="page-toolbar-left">
                    <button type="button"
                        class="page-toolbar-button"
                        data-action="toggleSectionHome" 
                        data-tooltip="create_publication.backTooltip">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="component-wrapper" style="padding-top: 82px;">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="create_publication.title"></h1>
            <p class="component-page-description" data-i18n="create_publication.description"></p>
        </div>

        <?php outputCsrfInput(); ?>

        <!-- Contenedor principal para el formulario de creación -->
        <div class="component-card component-card--action" id="create-post-form" style="gap: 16px;">
        
            <!-- Toggle para Publicación / Encuesta -->
            <div class="component-toggle-tabs" id="post-type-toggle">
                <button type="button" class="component-toggle-tab <?php echo $isPostActive ? 'active' : ''; ?>" data-type="post">
                    <span class="material-symbols-rounded">post_add</span>
                    <span data-i18n="create_publication.post"></span>
                </button>
                <button type="button" class="component-toggle-tab <?php echo $isPollActive ? 'active' : ''; ?>" data-type="poll">
                    <span class="material-symbols-rounded">poll</span>
                    <span data-i18n="create_publication.poll"></span>
                </button>
            </div>

            <!-- Contenido de la Publicación (Visible para ambos) -->
            <div id="post-content-area" class="active" style="width: 100%;">
                <div class="component-input-group">
                    <textarea id="publication-text" class="component-input" rows="5" placeholder=" " style="height: 120px; resize: vertical; padding-top: 16px;"></textarea>
                    <label for="publication-text" data-i18n="create_publication.placeholder"></label>
                </div>
            </div>

            <!-- Contenido de la Encuesta (Oculto por defecto) -->
            <div id="poll-content-area" class="<?php echo $isPollActive ? 'active' : 'disabled'; ?>" style="width: 100%; display: <?php echo $isPollActive ? 'flex' : 'none'; ?>; flex-direction: column; gap: 8px;">
                <p class="component-card__description" style="text-align: center;">(Aquí irán las opciones de la encuesta...)</p>
                <!-- Aquí irían los inputs para las opciones de la encuesta -->
            </div>
            

            <!-- Acciones (Adjuntar y Publicar) -->
            <div class="component-card__actions" style="width: 100%; justify-content: space-between;">
                <button type="button" class="component-action-button component-action-button--secondary" id="attach-files-btn" title="Adjuntar archivos (próximamente)" disabled>
                    <span class="material-symbols-rounded">attach_file</span>
                </button>
                <button type="button" class="component-action-button component-action-button--primary" id="publish-post-btn" data-i18n="create_publication.publish" disabled>
                    Publicar
                </button>
            </div>

        </div>
    </div>
</div>