<?php
// FILE: includes/sections/help/send-feedback.php
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'help-send-feedback') ? 'active' : 'disabled'; ?>" data-section="help-send-feedback">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="help.feedback.title">Enviar Comentarios</h1>
            <p class="component-page-description" data-i18n="help.feedback.description">¿Tienes algún problema o sugerencia? Háznoslo saber.</p>
        </div>
        
        <div class="component-card component-card--action" style="gap: 16px;">
            <?php outputCsrfInput(); ?>
            <div class="component-input-group">
                <input type="text" id="feedback-subject" name="subject" class="component-input" required placeholder=" " maxlength="100">
                <label for="feedback-subject" data-i18n="help.feedback.subject">Asunto</label>
            </div>

            <div class="component-input-group">
                <textarea id="feedback-message" name="message" class="component-input" required placeholder=" " style="height: 150px; resize: vertical; padding-top: 20px;"></textarea>
                <label for="feedback-message" data-i18n="help.feedback.message">Tu mensaje</label>
            </div>
            
            <div class="component-card__error disabled" id="feedback-error-message"></div>

            <div class="component-card__actions">
                 <button type="button" class="component-action-button component-action-button--secondary" data-action="toggleSectionHome" data-i18n="settings.profile.cancel"></button>
                 <button type="button" class="component-action-button component-action-button--primary" id="send-feedback-btn" data-i18n="help.feedback.send">Enviar</button>
            </div>
        </div>

    </div>
</div>