<?php
// FILE: includes/sections/main/messages.php
// (MODIFICADO - Ahora acepta un usuario pre-cargado desde el router)
// (MODIFICADO OTRA VEZ - Para manejar $chatErrorType)
// --- ▼▼▼ MODIFICACIÓN (ELIMINADO BOTÓN DE FILTRO "COMUNIDADES") ▼▼▼ ---
// --- ▼▼▼ MODIFICACIÓN (AÑADIDO POPOVER DE MENSAJE) ▼▼▼ ---
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;

// --- ▼▼▼ INICIO DE FUNCIÓN HELPER AÑADIDA (LÓGICA DE 'view-profile.php') ▼▼▼ ---
/**
 * Genera el string "Activo hace X"
 * @param string $dateTimeString El timestamp UTC de la BD
 * @return string
 */
function getChatTimeAgo($dateTimeString)
{
    if (empty($dateTimeString)) {
        return 'Desconectado'; // Fallback
    }
    try {
        $lastSeenTime = new DateTime($dateTimeString, new DateTimeZone('UTC'));
        $currentTime = new DateTime('now', new DateTimeZone('UTC'));
        $interval = $currentTime->diff($lastSeenTime);

        $timeAgo = '';
        if ($interval->y > 0) {
            $timeAgo = ($interval->y == 1) ? '1 año' : $interval->y . ' años';
        } elseif ($interval->m > 0) {
            $timeAgo = ($interval->m == 1) ? '1 mes' : $interval->m . ' meses';
        } elseif ($interval->d > 0) {
            $timeAgo = ($interval->d == 1) ? '1 día' : $interval->d . ' días';
        } elseif ($interval->h > 0) {
            $timeAgo = ($interval->h == 1) ? '1 h' : $interval->h . ' h';
        } elseif ($interval->i > 0) {
            $timeAgo = ($interval->i == 1) ? '1 min' : $interval->i . ' min';
        } else {
            $timeAgo = 'unos segundos';
        }

        // Devolver el texto traducible (el JS se encargará de las claves i18n si esto falla)
        return ($timeAgo === 'unos segundos') ? 'Activo hace unos momentos' : "Activo hace $timeAgo";
    } catch (Exception $e) {
        return 'Desconectado'; // Fallback en caso de error
    }
}
// --- ▲▲▲ FIN DE FUNCIÓN HELPER AÑADIDA ▲▲▲ ---
?>

<?php
// --- ▼▼▼ INICIO DE NUEVA LÓGICA DE PRE-CARGA Y ERROR ▼▼▼ ---

// $preloadedChatUser es inyectado por router.php si la URL es /messages/uuid
// $chatErrorType es inyectado por router.php si la privacidad lo bloquea
$hasPreloadedUser = isset($preloadedChatUser) && $preloadedChatUser;
$hasChatError = isset($chatErrorType) && $chatErrorType; // <-- NUEVO

// Ocultar la barra lateral y mostrar el panel derecho si hay un chat O un error
$chatSidebarClass = ($hasPreloadedUser || $hasChatError) ? 'disabled' : 'active'; // <-- MODIFICADO
// Ocultar el placeholder por defecto si hay un chat O un error
$chatContentPlaceholderClass = ($hasPreloadedUser || $hasChatError) ? 'disabled' : 'active'; // <-- MODIFICADO

// Mostrar el chat principal solo si hay un usuario pre-cargado (sin error)
$chatContentMainClass = $hasPreloadedUser ? 'active' : 'disabled';

// Forzar la vista de chat en móvil si hay un chat O un error
$chatLayoutClass = ($hasPreloadedUser || $hasChatError) ? 'show-chat' : ''; // <-- MODIFICADO

$preloadedReceiverId = '';
$preloadedAvatar = $defaultAvatar;
$preloadedUsername = '...';
$preloadedStatusText = 'Offline';
$preloadedStatusClass = 'offline';
$preloadedChatType = 'dm'; // <-- TIPO DE CHAT POR DEFECTO

// Esta lógica solo se ejecuta si el chat se cargó con éxito
if ($hasPreloadedUser) {
    $preloadedReceiverId = htmlspecialchars($preloadedChatUser['id']);
    $preloadedAvatar = htmlspecialchars($preloadedChatUser['profile_image_url'] ?? $defaultAvatar);
    if (empty($preloadedAvatar)) $preloadedAvatar = "https://ui-avatars.com/api/?name=" . urlencode($preloadedChatUser['username']) . "&size=100&background=e0e0e0&color=ffffff";
    $preloadedUsername = htmlspecialchars($preloadedChatUser['username']);

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (LÓGICA DE ROL/ESTADO) ▼▼▼ ---
    if ($preloadedChatUser['role'] === 'community') {
        $preloadedStatusText = 'Chat Grupal'; // O una clave i18n
        $preloadedStatusClass = 'active'; // Visible, pero sin "online"
    } elseif ($is_actually_online) { // $is_actually_online solo se define para DMs
        $preloadedStatusText = 'Online';
        $preloadedStatusClass = 'online active';
    } else {
        $preloadedStatusText = getChatTimeAgo($preloadedChatUser['last_seen']);
        $preloadedStatusClass = 'active'; // 'active' (visible), pero sin clase 'online'
    }

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASAR TIPO DE CHAT) ▼▼▼ ---
    $preloadedChatType = $chatType ?? 'dm'; // $chatType es definido en router.php
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}
// --- ▲▲▲ FIN DE NUEVA LÓGICA DE PRE-CARGA Y ERROR ▲▲▲ ---
?>

<div class="section-content <?php echo ($CURRENT_SECTION === 'messages') ? 'active' : 'disabled'; ?>" data-section="messages" style="overflow-y: hidden;">

    <div class="chat-layout-container <?php echo $chatLayoutClass; ?>" id="chat-layout-container">

        <div class="chat-sidebar-left <?php echo $chatSidebarClass; ?>" id="chat-sidebar-left">
            <div class="chat-sidebar-header">

                <div class="chat-sidebar-search">
                    <span class="material-symbols-rounded search-icon">search</span>

                    <input type="text" class="chat-sidebar-search-input" id="chat-friend-search" placeholder="Buscar conversación..." data-i18n-placeholder="chat.searchPlaceholder">
                </div>
            </div>

            <div class="chat-sidebar-filters" id="chat-sidebar-filters">
                <button type="button" class="chat-filter-badge active" data-filter="all" data-i18n="chat.filter.all">Todo</button>

                <button type="button" class="chat-filter-badge" data-filter="favorites" data-i18n="chat.filter.favorites">Favoritos</button>
                <button type="button" class="chat-filter-badge" data-filter="unread" data-i18n="chat.filter.unread">No leídos</button>
                <button type="button" class="chat-filter-badge" data-filter="archived" data-i18n="chat.filter.archived">Archivados</button>
            </div>
            <div class="chat-sidebar-list">

                <div class="chat-list-placeholder" id="chat-list-loader">
                    <span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>
                </div>

                <div class="chat-list-placeholder" id="chat-list-empty" style="display: none;">
                    <span class="material-symbols-rounded" id="chat-list-empty-icon">chat</span>
                    <span id="chat-list-empty-text" data-i18n="chat.empty.all">Inicia una conversación.</span>
                </div>
                <div id="chat-conversation-list">
                </div>

            </div>
        </div>

        <div class="chat-content-right" id="chat-content-right">

            <div class="chat-content-placeholder <?php echo $chatContentPlaceholderClass; ?>" id="chat-content-placeholder">
                <span class="material-symbols-rounded">chat</span>
                <span data-i18n="chat.selectConversation">Selecciona una conversación para empezar</span>
            </div>

            <?php if ($hasChatError): ?>
                <div class="chat-content-placeholder active" id="chat-content-error-placeholder">
                    <span class="material-symbols-rounded">lock</span>
                    <?php if ($chatErrorType === 'friends_only'): ?>
                        <span data-i18n="chat.error.friendsOnly">Este usuario solo acepta mensajes de amigos.</span>
                    <?php elseif ($chatErrorType === 'none'): ?>
                        <span data-i18n="chat.error.none">Este usuario no acepta mensajes privados.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="chat-content-main <?php echo $chatContentMainClass; ?>"
                id="chat-content-main"
                data-autoload-chat="<?php echo $hasPreloadedUser ? 'true' : 'false'; ?>">

                <div class="chat-content-header">
                    <button type="button" class="chat-back-button" id="chat-back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="chat-header-avatar" style="<?php echo ($preloadedChatUser['role'] === 'community') ? 'border-radius: 8px;' : ''; ?>">
                        <img src="<?php echo $preloadedAvatar; ?>" id="chat-header-avatar" alt="Avatar" style="<?php echo ($preloadedChatUser['role'] === 'community') ? 'border-radius: 8px;' : ''; ?>">
                    </div>
                    <div class="chat-header-info" id="chat-header-info">
                        <div class="chat-header-username" id="chat-header-username"><?php echo $preloadedUsername; ?></div>
                        <div class="chat-header-status <?php echo $preloadedStatusClass; ?>" id="chat-header-status" data-i18n-offline="chat.offline"><?php echo $preloadedStatusText; ?></div>

                        <div class="chat-header-status-typing disabled" id="chat-header-typing">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <?php // Se eliminó el <span> de texto 
                            ?>
                        </div>
                    </div>
                </div>

                <div class="chat-message-list overflow-y" id="chat-message-list">
                </div>

                <form class="chat-message-input-form" id="chat-message-input-form" action="#">
                    <?php outputCsrfInput(); ?>
                    <input type="hidden" id="chat-target-id" value="<?php echo $preloadedReceiverId; ?>">

                    <input type="hidden" id="chat-type" value="<?php echo $preloadedChatType; ?>">
                    <input type="file" id="chat-attachment-input" class="visually-hidden"
                        accept="image/png, image/jpeg, image/gif, image/webp"
                        multiple>

                    <div class="chat-reply-preview-container" id="chat-reply-preview-container" style="display: none;"></div>

                    <div class="chat-input-pill-wrapper">

                        <div class="chat-attachment-preview-container" id="chat-attachment-preview-container">
                        </div>

                        <div class="chat-input-main-column disabled">
                            <textarea
                                class="chat-input-field"
                                id="chat-message-input-expanded" placeholder="Escribe tu mensaje..."
                                data-i18n-placeholder="chat.messagePlaceholder"
                                autocomplete="off"
                                rows="1"
                                <?php echo ($hasPreloadedUser && !$hasChatError) ? '' : 'disabled'; ?>></textarea>
                        </div>

                        <div class="chat-input-main-row active">
                            <button type="button" class="chat-attach-button" id="chat-attach-button">
                                <span class="material-symbols-rounded">add_photo_alternate</span>
                            </button>
                            <textarea
                                class="chat-input-field"
                                id="chat-message-input-inline" placeholder="Escribe tu mensaje..."
                                data-i18n-placeholder="chat.messagePlaceholder"
                                autocomplete="off"
                                rows="1"
                                <?php echo ($hasPreloadedUser && !$hasChatError) ? '' : 'disabled'; ?>></textarea>
                            <button type="submit" class="chat-send-button" id="chat-send-button" disabled>
                                <span class="material-symbols-rounded">send</span>
                            </button>
                        </div>
                        
                        </div>
                </form>
            </div>
        </div>

        <div class="popover-module body-title disabled"
            data-module="moduleChatContext"
            id="chat-context-menu">
            <div class="menu-content">
                <div class="menu-list">

                    <div class="menu-link" data-action="pin-chat">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">push_pin</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.pinChat">Fijar chat</span></div>
                    </div>
                    <div class="menu-link" data-action="unpin-chat" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded" style="font-variation-settings: 'FILL' 0;">push_pin</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unpinChat">Desfijar chat</span></div>
                    </div>

                    <div class="menu-link" data-action="add-favorites">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">star_outline</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.addFavorite">Añadir a favoritos</span></div>
                    </div>
                    <div class="menu-link" data-action="remove-favorites" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded" style="color: #206BD3;">star</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.removeFavorite" style="color: #206BD3;">Quitar de favoritos</span></div>
                    </div>

                    <div class="menu-link" data-action="archive-chat">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">archive</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.archiveChat">Archivar chat</span></div>
                    </div>
                    <div class="menu-link" data-action="unarchive-chat" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">unarchive</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unarchiveChat">Desarchivar chat</span></div>
                    </div>

                    <?php // --- ▼▼▼ BLOQUE ELIMINADO (Separador y Ver Perfil) ▼▼▼ --- 
                    ?>
                    <?php /*
                    <div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>

                    <a class="menu-link"
                        data-action="friend-menu-profile"
                        data-nav-js="true"
                        href="#">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">person</span>
                        </div>
                        <div class="menu-link-text">
                            <span>Ver Perfil</span>
                        </div>
                    </a>
                    */ ?>
                    <?php // --- ▲▲▲ FIN BLOQUE ELIMINADO ▲▲▲ --- 
                    ?>

                    <div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>

                    <div class="menu-link" data-action="block-user">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">block</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.blockUser">Bloquear</span></div>
                    </div>
                    <div class="menu-link" data-action="unblock-user" style="display: none;">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">lock_open</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.unblockUser">Desbloquear</span></div>
                    </div>

                    <div class="menu-link" data-action="delete-chat">
                        <div class="menu-link-icon" style="color: #c62828;"><span class="material-symbols-rounded">delete</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.deleteChat" style="color: #c62828;">Eliminar chat</span></div>
                    </div>
                </div>
            </div>
        </div>
        <?php // --- ▲▲▲ FIN DE MODIFICACIÓN (Menú Contextual) ▲▲▲ 
        ?>

        <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN (POPOVER DE MENSAJE AÑADIDO) ▼▼▼ 
        ?>
        <div class="popover-module body-title disabled"
            data-module="moduleMessageContext"
            id="message-context-menu">
            <div class="menu-content">
                <div class="menu-list">

                    <div class="menu-link" data-action="msg-reply">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">reply</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.reply">Responder</span></div>
                    </div>

                    <div class="menu-link" data-action="msg-copy">
                        <div class="menu-link-icon"><span class="material-symbols-rounded">content_copy</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.copy">Copiar texto</span></div>
                    </div>

                    <div style="height: 1px; background-color: #00000020; margin: 4px 8px;"></div>

                    <div class="menu-link" data-action="msg-delete" style="display: none;"> <?php // El JS lo mostrará si es 'sent' 
                                                                                            ?>
                        <div class="menu-link-icon"><span class="material-symbols-rounded">delete</span></div>
                        <div class="menu-link-text"><span data-i18n="chat.context.delete">Eliminar mensaje</span></div>
                    </div>

                </div>
            </div>
        </div>
        <?php // --- ▲▲▲ FIN DE MODIFICACIÓN (POPOVER DE MENSAJE AÑADIDO) ▲▲▲ 
        ?>


    </div>
</div>