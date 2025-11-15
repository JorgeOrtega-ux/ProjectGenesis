<?php
// FILE: includes/sections/settings/your-profile.php
?>
<?php
// --- ▼▼▼ INICIO DE BLOQUE PHP MODIFICADO ▼▼▼ ---

// Estas variables son cargadas por config/router.php
$usageMap = [
    'personal' => 'settings.profile.usagePersonal',
    'student' => 'settings.profile.usageStudent',
    'teacher' => 'settings.profile.usageTeacher',
    'small_business' => 'settings.profile.usageSmallBusiness',
    'large_company' => 'settings.profile.usageLargeCompany'
];

$usageIconMap = [
    'personal' => 'person',
    'student' => 'school',
    'teacher' => 'history_edu',
    'small_business' => 'storefront',
    'large_company' => 'business'
];

$languageMap = [
    'es-latam' => 'settings.profile.langEsLatam',
    'es-mx' => 'settings.profile.langEsMx',
    'en-us' => 'settings.profile.langEnUs',
    'fr-fr' => 'settings.profile.langFrFr'
];

// --- [NUEVO] Lógica de Privacidad de Mensajes ---
$messagePrivacyMap = [
    'all' => 'settings.profile.privacyAll',
    'friends' => 'settings.profile.privacyFriends',
    'none' => 'settings.profile.privacyNone'
];

$messagePrivacyIconMap = [
    'all' => 'chat',
    'friends' => 'people',
    'none' => 'chat_error'
];

// Asumimos que $userMessagePrivacy es cargado por el router, con 'all' como default
$userMessagePrivacy = $_SESSION['message_privacy_level'] ?? 'all';
$currentMessagePrivacyKey = $messagePrivacyMap[$userMessagePrivacy] ?? 'settings.profile.privacyAll';
$currentMessagePrivacyIcon = $messagePrivacyIconMap[$userMessagePrivacy] ?? 'chat';
// --- [FIN] Lógica de Privacidad de Mensajes ---


$currentUsageKey = $usageMap[$userUsageType] ?? 'settings.profile.usagePersonal';
$currentLanguageKey = $languageMap[$userLanguage] ?? 'settings.profile.langEnUs';

$isFriendListPrivate = (int) ($_SESSION['is_friend_list_private'] ?? 1);
$isEmailPublic = (int) ($_SESSION['is_email_public'] ?? 0);

// --- [INICIO] Lógica de Empleo y Formación ---
$userEmployment = $_SESSION['employment'] ?? 'none';
$userEducation = $_SESSION['education'] ?? 'none';

// Opciones de Empleo
$employmentMap = [
    'none' => 'Sin empleo',
    'student' => 'Estudiante',
    'tech' => 'Tecnología / Desarrollo de Software',
    'health' => 'Salud / Medicina',
    'education' => 'Educación / Docencia',
    'industry' => 'Industria / Manufactura',
    'commerce' => 'Comercio / Ventas',
    'admin' => 'Administración / Oficina',
    'other' => 'Otro'
];
$employmentIconMap = [
    'none' => 'work_off',
    'student' => 'school',
    'tech' => 'computer',
    'health' => 'medical_services',
    'education' => 'history_edu',
    'industry' => 'factory',
    'commerce' => 'storefront',
    'admin' => 'business_center',
    'other' => 'work'
];

// --- [NUEVO] Opciones de Formación Agrupadas ---
$educationGroups = [
    'General' => [
        'none' => 'Sin formación',
    ],
    'Valle Hermoso' => [
        'icn_valle_hermoso' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN) - Valle Hermoso',
        'uda_zaragoza_vh' => 'Universidad del Atlántico - Campus Valle Hermoso (Zaragoza)',
        'uda_juarez_vh' => 'Universidad del Atlántico - Campus Valle Hermoso (Juárez)',
        'unm_valle_hermoso' => 'Universidad del Noreste de México - Unidad Valle Hermoso',
        'uat_valle_hermoso' => 'Universidad Autónoma de Tamaulipas (UAT) - Unidad Valle Hermoso',
    ],
    'Matamoros' => [
        'icn_matamoros' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN) - Matamoros',
        'uih_matamoros' => 'Universidad de Integración Humanista - Matamoros',
        'fmisc_matamoros' => 'Facultad de Medicina e Ingeniería en Sistemas Computacionales - Matamoros',
        'cin_matamoros' => 'Centro Universitario del Noreste (CIN) - Matamoros',
        'iom_matamoros' => 'Instituto Odontológico de Matamoros (IOM)',
        'uamm_matamoros' => 'Unidad Académica Multidisciplinaria Matamoros (UAMM)',
        'uane_matamoros' => 'Universidad Americana del Noreste - Campus Matamoros',
        'ut_matamoros' => 'Universidad Tamaulipeca - Campus Matamoros',
        'itm_matamoros' => 'Instituto Tecnológico de Matamoros',
        'upn_matamoros' => 'Universidad Pedagógica Nacional (UPN) - Matamoros',
        'uda_cardenas_matamoros' => 'Universidad del Atlántico - Campus Pedro Cárdenas',
        'icest_matamoros' => 'Inst. de Ciencias y Est. Sup. de Tamps. (ICEST) - Matamoros',
        'uane_constituyentes_matamoros' => 'Universidad Americanista del Noreste (UANE) - Campus Matamoros',
        'uda_villar_matamoros' => 'Universidad del Atlántico - Campus Lauro Villar',
        'uda_laguneta_matamoros' => 'Universidad del Atlántico - Campus Laguneta',
        'unm_matamoros' => 'Universidad del Noreste de México - Unidad Matamoros',
        'normal_mainero_matamoros' => 'Escuela Normal Lic. J. Guadalupe Mainero - Matamoros',
        'lpca_matamoros' => 'Liceo Profesional de Comercio y Administración - Matamoros',
        'utm_matamoros' => 'Universidad Tecnológica de Matamoros (UTM)',
    ],
    'Otros' => [
        'other' => 'Otra',
    ]
];

// --- [NUEVO] Crear mapas planos para iconos y texto actual ---
$educationIconMap = [];
$educationMap = []; // Mapa plano para buscar el texto de la opción seleccionada
foreach ($educationGroups as $groupOptions) {
    foreach ($groupOptions as $key => $text) {
        $educationIconMap[$key] = 'school'; // Icono por defecto
        $educationMap[$key] = $text; // Añadir al mapa plano
    }
}
// --- ▼▼▼ LÍNEA MODIFICADA ▼▼▼ ---
$educationIconMap['none'] = 'work_off'; // Cambiado de 'school_off' a 'work_off'
// --- ▲▲▲ LÍNEA MODIFICADA ▲▲▲ ---
$educationIconMap['other'] = 'domain';

// --- Obtener claves actuales ---
$currentEmploymentKey = $employmentMap[$userEmployment] ?? $employmentMap['none'];
$currentEmploymentIcon = $employmentIconMap[$userEmployment] ?? $employmentIconMap['none'];

$currentEducationKey = $educationMap[$userEducation] ?? $educationMap['none']; // Usa el nuevo mapa plano
$currentEducationIcon = $educationIconMap[$userEducation] ?? $educationIconMap['none'];
// --- [FIN] Lógica de Empleo y Formación ---

// --- ▲▲▲ FIN DE BLOQUE PHP MODIFICADO ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-profile') ? 'active' : 'disabled'; ?>" data-section="settings-profile">
    <div class="component-wrapper">

        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.profile.title"></h1>
            <p class="component-page-description" data-i18n="settings.profile.description"></p>
        </div>

        <div class="component-card component-card--edit-mode" id="avatar-section">

            <?php outputCsrfInput(); ?> <input type="file" class="visually-hidden" id="avatar-upload-input" name="avatar" accept="image/png, image/jpeg, image/gif, image/webp">

            <div class="component-card__content">
                <div class="component-card__avatar" id="avatar-preview-container" data-role="<?php echo htmlspecialchars($userRole); ?>">
                    <img src="<?php echo htmlspecialchars($profileImageUrl); ?>"
                         alt="<?php echo htmlspecialchars($usernameForAlt); ?>"
                         class="component-card__avatar-image"
                         id="avatar-preview-image"
                         data-i18n-alt-prefix="header.profile.altPrefix">

                    <div class="component-card__avatar-overlay">
                        <span class="material-symbols-rounded">photo_camera</span>
                    </div>
                </div>
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.avatarTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.profile.avatarDesc"></p>
                </div>
            </div>

            <div class="component-card__actions">
                
                <div id="avatar-actions-default" class="<?php echo $isDefaultAvatar ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                <button type="button" class="component-button" id="avatar-upload-trigger" data-i18n="settings.profile.uploadPhoto"></button>
                </div>

                <div id="avatar-actions-custom" class="<?php echo !$isDefaultAvatar ? 'active' : 'disabled'; ?>" style="gap: 12px;">
                <button type="button" class="component-button danger" id="avatar-remove-trigger" data-i18n="settings.profile.removePhoto"></button>
                    <button type="button" class="component-button" id="avatar-change-trigger" data-i18n="settings.profile.changePhoto"></button>
                </div>

                <div id="avatar-actions-preview" class="disabled" style="gap: 12px;">
                <button type="button" class="component-button" id="avatar-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                    <button type="button" class="component-button" id="avatar-save-trigger-btn" data-i18n="settings.profile.save"></button>
                </div>
            </div>
        </div>
        <div class="component-card component-card--edit-mode" id="username-section">
            <?php outputCsrfInput(); ?> <input type="hidden" name="action" value="update-username"> 
            
            <div class="component-card__content active" id="username-view-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.username"></h2>
                    <p class="component-card__description"
                       id="username-display-text"
                       data-original-username="<?php echo htmlspecialchars($usernameForAlt); ?>">
                       <?php echo htmlspecialchars($usernameForAlt); ?>
                    </p>
                </div>
            </div>
            <div class="component-card__actions active" id="username-actions-view">
            <button type="button" class="component-button" id="username-edit-trigger" data-i18n="settings.profile.edit"></button>
            </div>

            <div class="component-card__content disabled" id="username-edit-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.username"></h2>
                    <input type="text"
                           class="component-text-input"
                           id="username-input"
                           name="username"
                           value="<?php echo htmlspecialchars($usernameForAlt); ?>"
                           required
                           minlength="6"
                           maxlength="32">
                </div>
            </div>
            <div class="component-card__actions disabled" id="username-actions-edit">
            <button type="button" class="component-button" id="username-cancel-trigger" data-i18n="settings.profile.cancel"></button>
                <button type="button" class="component-button" id="username-save-trigger-btn" data-i18n="settings.profile.save"></button>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode" id="email-section">
            <?php outputCsrfInput(); ?> 
            <div class="component-card__content active" id="email-view-state">
            <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.email"></h2>
                    <p class="component-card__description"
                       id="email-display-text"
                       data-original-email="<?php echo htmlspecialchars($userEmail); ?>">
                       <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                </div>
            </div>
            <div class="component-card__actions active" id="email-actions-view">
            <button type="button"
                   class="component-button"
                   data-action="toggleSectionSettingsChangeEmail"
                   data-i18n="settings.profile.edit">
                </button>
                </div>

            </div>

        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.employmentTitle">Empleo</h2>
                    <p class="component-card__description" data-i18n="settings.profile.employmentDesc">Selecciona tu sector laboral actual.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleEmploymentSelect">
                        <div class="trigger-select-icon">
                             <span class="material-symbols-rounded"><?php echo $currentEmploymentIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span>
                                <?php echo htmlspecialchars($currentEmploymentKey); ?>
                            </span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>
                    
                    <div class="popover-module popover-module--anchor-width body-title disabled" 
                         data-module="moduleEmploymentSelect"
                         data-preference-type="employment">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php 
                                foreach ($employmentMap as $key => $text): 
                                    $isActive = ($key === $userEmployment); 
                                    $iconName = $employmentIconMap[$key];
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                        </div>
                                        <div class="menu-link-text">
                                            <span><?php echo htmlspecialchars($text); ?></span>
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
        
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.educationTitle">Formación</h2>
                    <p class="component-card__description" data-i18n="settings.profile.educationDesc">Selecciona tu institución educativa.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleEducationSelect">
                        <div class="trigger-select-icon">
                             <span class="material-symbols-rounded"><?php echo $currentEducationIcon; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span>
                                <?php echo htmlspecialchars($currentEducationKey); ?>
                            </span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>
                    
                    <div class="popover-module popover-module--anchor-width body-title disabled" 
                         data-module="moduleEducationSelect"
                         data-preference-type="education">
                        <div class="menu-content" style="max-height: 300px; overflow-y: auto;"> <div class="menu-list">
                                <?php 
                                // --- [NUEVO] Bucle anidado para grupos ---
                                foreach ($educationGroups as $groupName => $groupOptions):
                                ?>
                                    
                                    <?php 
                                    // No mostrar encabezado para el grupo "General" (que solo tiene 'Sin formación')
                                    if ($groupName !== 'General'): 
                                    ?>
                                        <div class="menu-header">
                                            <div class="menu-header-title"><?php echo htmlspecialchars($groupName); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <?php 
                                    foreach ($groupOptions as $key => $text):
                                        $isActive = ($key === $userEducation); 
                                        $iconName = $educationIconMap[$key];
                                    ?>
                                        <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>" 
                                             data-value="<?php echo htmlspecialchars($key); ?>">
                                            <div class="menu-link-icon">
                                                <span class="material-symbols-rounded"><?php echo $iconName; ?></span>
                                            </div>
                                            <div class="menu-link-text">
                                                <span><?php echo htmlspecialchars($text); ?></span>
                                            </div>
                                            <div class="menu-link-check-icon">
                                                <?php if ($isActive): ?>
                                                    <span class="material-symbols-rounded">check</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php 
                                    endforeach; // Fin del bucle de opciones
                                    ?>
                                <?php 
                                endforeach; // Fin del bucle de grupos
                                // --- [FIN] Bucle anidado ---
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.usageTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.profile.usageDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleUsageSelect">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo $usageIconMap[$userUsageType] ?? 'person'; ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentUsageKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleUsageSelect"
                         data-preference-type="usage">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($usageMap as $key => $textKey):
                                    $isActive = ($key === $userUsageType);
                                    $iconName = $usageIconMap[$key] ?? 'person';
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
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.langTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.profile.langDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModuleLanguageSelect">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded">language</span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentLanguageKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="moduleLanguageSelect"
                         data-preference-type="language">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($languageMap as $key => $textKey):
                                    $isActive = ($key === $userLanguage);
                                ?>
                                    <div class="menu-link <?php echo $isActive ? 'active' : ''; ?>"
                                         data-value="<?php echo htmlspecialchars($key); ?>">
                                        <div class="menu-link-icon">
                                            <span class="material-symbols-rounded">language</span>
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
                    <h2 class="component-card__title" data-i18n="settings.profile.newTabTitle"></h2>
                    <p class="component-card__description" data-i18n="settings.profile.newTabDesc"></p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox"
                           id="toggle-new-tab"
                           data-preference-type="boolean"
                           data-field-name="open_links_in_new_tab"
                           <?php echo ($openLinksInNewTab == 1) ? 'checked' : ''; ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <?php // --- BLOQUES DE PRIVACIDAD ELIMINADOS DE AQUÍ --- ?>
        
    </div>
</div>