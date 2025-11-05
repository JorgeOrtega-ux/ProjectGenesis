<?php
// FILE: includes/sections/main/join-group.php
// (Se asume que esta página se llama 'join-group' en el router)
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'join-group') ? 'active' : 'disabled'; ?>" data-section="join-group">
    <div class="component-wrapper">

        <div class="component-header-card">
            <!-- (Añadir a es-mx.json) groups.join.title -->
            <h1 class="component-page-title" data-i18n="groups.join.title">Unirse a un Grupo</h1>
            <!-- (Añadir a es-mx.json) groups.join.description -->
            <p class="component-page-description" data-i18n="groups.join.description">Ingresa un código de invitación para unirte a un grupo existente.</p>
        </div>

        <?php
        // Incluir el input CSRF
        outputCsrfInput();
        ?>

        <div class="component-card component-card--action" id="join-group-card" style="gap: 16px;">
            <div class="component-card__content">
                <div class="component-card__icon">
                    <span class="material-symbols-rounded">group_add</span>
                </div>
                <div class="component-card__text">
                    <!-- (Añadir a es-mx.json) groups.join.accessCodeTitle -->
                    <h2 class="component-card__title" data-i18n="groups.join.accessCodeTitle">Código de Invitación</h2>
                    <p class="component-card__description" data-i18n="groups.join.accessCodeDesc">Pídele el código al administrador del grupo.</p>
                </div>
            </div>
            
            <div class="component-input-group">
                <!-- (Añadir a es-mx.json) groups.join.accessCodeLabel -->
                <input type="text" id="join-group-access-code" name="access_code" class="component-input" required placeholder=" " maxlength="20">
                <label for="join-group-access-code" data-i18n="groups.join.accessCodeLabel">Código de acceso</label>
            </div>

            <!-- Div para mostrar errores de JS -->
            <div class="component-card__error disabled" id="join-group-error"></div>

            <div class="component-card__actions">
                <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionHome" data-i18n="settings.profile.cancel"></button>
                <!-- (Añadir a es-mx.json) groups.join.button -->
                <button type="button" class="component-action-button component-action-button--primary" id="join-group-submit-btn" data-i18n="groups.join.button">Unirme al grupo</button>
            </div>
        </div>
    </div>
</div>