<?php
// FILE: includes/sections/settings/privacy.php
// (NUEVO ARCHIVO)

// --- ▼▼▼ INICIO DE BLOQUE PHP (Copiado de your-profile.php) ▼▼▼ ---

// --- Lógica de Privacidad de Mensajes ---
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

$isFriendListPrivate = (int) ($_SESSION['is_friend_list_private'] ?? 1);
$isEmailPublic = (int) ($_SESSION['is_email_public'] ?? 0);

// --- ▲▲▲ FIN DE BLOQUE PHP ▲▲▲ ---
?>
<div class="section-content overflow-y <?php echo ($CURRENT_SECTION === 'settings-privacy') ? 'active' : 'disabled'; ?>" data-section="settings-privacy">
    <div class="component-wrapper">
        
        <div class="component-header-card">
            <h1 class="component-page-title" data-i18n="settings.privacyPage.title">Controles de Privacidad</h1>
            <p class="component-page-description" data-i18n="settings.privacyPage.description">Gestiona quién puede ver tu información y cómo pueden contactarte.</p>
        </div>

        <?php // --- ▼▼▼ INICIO DE TARJETAS PEGADAS ▼▼▼ --- ?>

        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Lista de amigos privada</h2>
                    <p class="component-card__description">Si está activado, solo tú podrás ver tu lista de amigos en tu perfil.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox"
                           id="toggle-friend-list-private"
                           data-preference-type="boolean"
                           data-field-name="is_friend_list_private"
                           <?php echo ($isFriendListPrivate == 1) ? 'checked' : ''; ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="component-card component-card--edit-mode">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title">Correo electrónico público</h2>
                    <p class="component-card__description">Si está activado, otros usuarios podrán ver tu correo en tu perfil.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <label class="component-toggle-switch">
                    <input type="checkbox"
                           id="toggle-email-public"
                           data-preference-type="boolean"
                           data-field-name="is_email_public"
                           <?php echo ($isEmailPublic == 1) ? 'checked' : ''; ?>>
                    <span class="component-toggle-slider"></span>
                </label>
            </div>
        </div>
        
        <div class="component-card component-card--column">
            <div class="component-card__content">
                <div class="component-card__text">
                    <h2 class="component-card__title" data-i18n="settings.profile.privacyTitle">Privacidad de Mensajes</h2>
                    <p class="component-card__description" data-i18n="settings.profile.privacyDesc">Elige quién puede enviarte mensajes directos.</p>
                </div>
            </div>
            <div class="component-card__actions">
                <div class="trigger-select-wrapper">
                    <div class="trigger-selector" data-action="toggleModulePrivacySelect">
                        <div class="trigger-select-icon">
                            <span class="material-symbols-rounded"><?php echo htmlspecialchars($currentMessagePrivacyIcon); ?></span>
                        </div>
                        <div class="trigger-select-text">
                            <span data-i18n="<?php echo htmlspecialchars($currentMessagePrivacyKey); ?>"></span>
                        </div>
                        <div class="trigger-select-arrow">
                            <span class="material-symbols-rounded">arrow_drop_down</span>
                        </div>
                    </div>

                    <div class="popover-module popover-module--anchor-width body-title disabled"
                         data-module="modulePrivacySelect"
                         data-preference-type="privacy">
                        <div class="menu-content">
                            <div class="menu-list">
                                <?php
                                foreach ($messagePrivacyMap as $key => $textKey):
                                    $isActive = ($key === $userMessagePrivacy);
                                    $iconName = $messagePrivacyIconMap[$key] ?? 'chat';
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

        <?php // --- ▲▲▲ FIN DE TARJETAS PEGADAS --- ?>

    </div>
</div>