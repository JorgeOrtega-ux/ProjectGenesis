<?php
// --- ▼▼▼ INICIO DE NUEVO BLOQUE PHP ▼▼▼ ---

// $userTheme es cargado por config/router.php

$themeMap = [
    'system' => 'Sincronizar con el sistema',
    'light' => 'Tema claro',
    'dark' => 'Tema oscuro'
];

$currentThemeText = $themeMap[$userTheme] ?? 'Sincronizar con el sistema';

// --- ▲▲▲ FIN DE NUEVO BLOQUE PHP ▲▲▲ ---
?>
<div class="section-content <?php echo ($CURRENT_SECTION === 'settings-accessibility') ? 'active' : 'disabled'; ?>" data-section="settings-accessibility">
    <div class="settings-wrapper">
        
        <div class="settings-header-card">
            <h1 class="settings-title">Accesibilidad</h1>
            <p class="settings-description">
                Ajusta las configuraciones de visualización, como el tamaño del texto o el contraste de colores.
            </p>
        </div>

        <div class="settings-card settings-card-trigger-column">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Tema</h2>
                    <p class="settings-text-description">
                        Elige cómo quieres que se vea la interfaz.
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <div class="trigger-select-wrapper">
                    
                    <div class="trigger-selector" 
                         data-action="toggleModuleThemeSelect">
                        
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded">brightness_medium</span>
                        </div>
                        <div class="trigger-select-text">
                            <span><?php echo htmlspecialchars($currentThemeText); ?></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="module-content module-trigger-select body-title disabled" 
                         data-module="moduleThemeSelect"
                         data-preference-type="theme">
                        
                        <div class="menu-content">
                            <div class="menu-list">

                                <?php foreach ($themeMap as $key => $text): ?>
                                    <?php $isActive = ($key === $userTheme); ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        
                                        <div class="menu-link-icon">
                                            <?php if ($isActive): ?>
                                                <span class="material-symbols-rounded">check</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="menu-link-text">
                                            <span><?php echo htmlspecialchars($text); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="settings-card settings-card-align-bottom">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Contraste alto de colores</h2>
                    <p class="settings-text-description">
                        Se mantiene un mayor contraste entre el texto y el fondo, incluidos los fondos con degradados.
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <label class="settings-toggle-switch">
                    <input type="checkbox"> 
                    <span class="settings-toggle-slider"></span>
                </label>

            </div>
        </div>
        <div class="settings-card settings-card-align-bottom">
            <div class="settings-card-left">
                <div class="settings-text-content">
                    <h2 class="settings-text-title">Aumenta el tiempo de permanencia de un mensaje en la pantalla.</h2>
                    <p class="settings-text-description">
                        Los mensajes permanecerán más tiempo en pantalla antes de desaparecer.
                    </p>
                </div>
            </div>

            <div class="settings-card-right">
                
                <label class="settings-toggle-switch">
                    <input type="checkbox"> 
                    <span class="settings-toggle-slider"></span>
                </label>

            </div>
        </div>
        </div>
</div>