<?php
// --- ▼▼▼ INICIO DE NUEVO BLOQUE PHP ▼▼▼ ---

// $userTheme y $increaseMessageDuration son cargados por config/router.php

// 1. Definir los mapas de valores de BD a *CLAVES DE TRADUCCIÓN*
$themeMap = [
    'system' => 'settings.accessibility.themeSystem',
    'light' => 'settings.accessibility.themeLight',
    'dark' => 'settings.accessibility.themeDark'
];

// --- ¡NUEVO MAPA DE ICONOS AÑADIDO! ---
$themeIconMap = [
    'system' => 'desktop_windows',
    'light' => 'light_mode',
    'dark' => 'dark_mode'
];

// 2. Obtener la *CLAVE* actual para mostrar en el botón
$currentThemeKey = $themeMap[$userTheme] ?? 'settings.accessibility.themeSystem';

// --- ▲▲▲ FIN DE NUEVO BLOQUE PHP ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-accessibility') ? 'active' : 'disabled'; ?>" data-section="settings-accessibility">
    <div class="component-wrapper">
        
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.accessibility.title"></h1>
            <p class="component-page-description" data-i18n="settings.accessibility.description"></p>
        </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.accessibility.themeTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.accessibility.themeDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleThemeSelect">
                        <div class="trigger-select-icon">
                             <span class="material-symbols-rounded"><?php echo $themeIconMap[$userTheme] ?? 'desktop_windows'; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentThemeKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>
                    
                    <div class="popover-module popover-module--anchor-width body-title disabled" 
                         data-module="moduleThemeSelect"
                         data-preference-type="theme">
                    <div class="menu-content">
                            <div class="menu-list">
                                <?php 
                                foreach ($themeMap as $key => $textKey): 
                                    $isActive = ($key === $userTheme); 
                                    $iconName = $themeIconMap[$key] ?? 'desktop_windows';
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span data-i18n="<?php echo htmlspecialchars($textKey); ?>"></span>
                                        </div>
                                        <div class="menu-link-check-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.accessibility.durationTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.accessibility.durationDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox" 
                           id="toggle-message-duration"
                           data-preference-type="boolean"
                           data-field-name="increase_message_duration"
                           <?php echo ($increaseMessageDuration == 1) ? 'checked' : ''; ?>> 
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        </div>
</div>