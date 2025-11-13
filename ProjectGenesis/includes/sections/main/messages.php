<?php
// FILE: includes/sections/main/messages.php
// (NUEVO ARCHIVO)
global $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
?>
<style>
/* Estilos específicos para el chat */
.chat-layout-container {
    display: flex;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

/* --- Panel Izquierdo (Lista de Amigos) --- */
.chat-sidebar-left {
    width: 360px;
    height: 100%;
    border-right: 1px solid #00000020;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    background-color: #ffffff;
}

.chat-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid #00000020;
    flex-shrink: 0;
}

.chat-sidebar-header .component-page-title {
    font-size: 24px;
    text-align: left;
    margin-bottom: 16px;
}

.chat-sidebar-search {
    position: relative;
}

.chat-sidebar-search .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    pointer-events: none;
}

.chat-sidebar-search-input {
    width: 100%;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #00000020;
    background-color: #f5f5fa;
    padding: 0 12px 0 44px;
    font-size: 15px;
    font-family: "Roboto Condensed", sans-serif;
    font-weight: 500;
    color: #000;
    outline: none;
}
.chat-sidebar-search-input:focus {
    background-color: #ffffff;
    border-color: #000;
}

.chat-sidebar-list {
    flex-grow: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 8px;
}

.chat-conversation-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
    text-decoration: none;
    position: relative;
}
.chat-conversation-item:hover {
    background-color: #f5f5fa;
}
.chat-conversation-item.active {
    background-color: #f5f5fa;
}

.chat-item-avatar {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    position: relative;
}
.chat-item-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.chat-item-status {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    background-color: #ccc;
}
.chat-item-status.online {
    background-color: #28a745;
}

.chat-item-info {
    flex-grow: 1;
    min-width: 0;
}
.chat-item-info-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
}
.chat-item-username {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-item-timestamp {
    font-size: 12px;
    color: #6b7280;
    flex-shrink: 0;
}
.chat-item-snippet-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}
.chat-item-snippet {
    font-size: 14px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chat-item-unread-badge {
    background-color: #c62828;
    color: #ffffff;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 50px;
    flex-shrink: 0;
}
.chat-list-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    text-align: center;
    color: #6b7280;
    gap: 16px;
    flex-direction: column;
}

/* --- Panel Derecho (Chat Activo) --- */
.chat-content-right {
    flex-grow: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    background-color: #ffffff;
}
.chat-content-placeholder {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    gap: 16px;
}
.chat-content-placeholder .material-symbols-rounded {
    font-size: 64px;
}
.chat-content-placeholder span {
    font-size: 18px;
    font-weight: 500;
}
.chat-content-main {
    flex-grow: 1;
    overflow: auto;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.chat-content-main.disabled {
    display: none;
}
.chat-content-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #00000020;
    flex-shrink: 0;
}
.chat-header-avatar {
    width: 40px;
    height: 40px;
}
.chat-header-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.chat-header-info {
    flex-grow: 1;
}
.chat-header-username {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}
.chat-header-status {
    font-size: 13px;
    color: #6b7280;
}
.chat-header-status.online {
    color: #28a745;
}
.chat-message-list {
    flex-grow: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.chat-bubble {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 75%;
}
.chat-bubble-avatar {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    margin-top: auto;
}
.chat-bubble-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
.chat-bubble-content {
    background-color: #f5f5fa;
    border-radius: 12px;
    padding: 12px;
    font-size: 15px;
    line-height: 1.5;
    color: #1f2937;
    word-break: break-word;
}
.chat-bubble.sent {
    margin-left: auto;
    flex-direction: row-reverse;
}
.chat-bubble.sent .chat-bubble-content {
    background-color: #000;
    color: #ffffff;
}
.chat-bubble.sent .chat-bubble-avatar {
    display: none;
}
.chat-bubble.received {
    margin-right: auto;
}
.chat-message-input-form {
    padding: 16px;
    border-top: 1px solid #00000020;
    display: flex;
    align-items: center;
    gap: 12px;
    background-color: #ffffff;
    flex-shrink: 0;
}
.chat-input-field {
    flex-grow: 1;
    height: 44px;
    border: 1px solid #00000020;
    background-color: #f5f5fa;
    border-radius: 22px;
    padding: 0 16px;
    font-size: 15px;
    outline: none;
    transition: background-color 0.2s, border-color 0.2s;
}
.chat-input-field:focus {
    background-color: #ffffff;
    border-color: #000;
}
.chat-send-button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    background-color: #000;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
    flex-shrink: 0;
}
.chat-send-button:disabled {
    background-color: #f5f5fa;
    color: #adb5bd;
    cursor: not-allowed;
}
.chat-send-button:not(:disabled):hover {
    background-color: #333;
}
.chat-send-button .material-symbols-rounded {
    font-size: 24px;
}
.chat-attach-button {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 1px solid #00000020;
    background-color: #f5f5fa;
    color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s;
    flex-shrink: 0;
}
.chat-attach-button:hover {
    background-color: #e9ecef;
}

@media (max-width: 768px) {
    .chat-sidebar-left {
        width: 100%;
        position: absolute;
        z-index: 10;
        transition: transform 0.3s ease-in-out;
    }
    .chat-content-right {
        width: 100%;
        position: absolute;
        z-index: 9;
    }
    .chat-layout-container.show-chat .chat-sidebar-left {
        transform: translateX(-100%);
    }
    .chat-layout-container:not(.show-chat) .chat-content-right {
        transform: translateX(100%);
    }
    .chat-content-header {
        padding: 12px 8px 12px 16px;
    }
    .chat-back-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: none;
        background-color: transparent;
        border-radius: 50%;
        cursor: pointer;
    }
    .chat-back-button:hover {
        background-color: #f5f5fa;
    }
}
@media (min-width: 769px) {
    .chat-back-button {
        display: none;
    }
}
</style>

<div class="section-content <?php echo ($CURRENT_SECTION === 'messages') ? 'active' : 'disabled'; ?>" data-section="messages" style="overflow-y: hidden;">
    
    <div class="chat-layout-container" id="chat-layout-container">

        <div class="chat-sidebar-left" id="chat-sidebar-left">
            <div class="chat-sidebar-header">
                <h1 class="component-page-title" data-i18n="chat.title">Mensajes</h1>
                <div class="chat-sidebar-search">
                    <span class="material-symbols-rounded search-icon">search</span>
                    <input type="text" class="chat-sidebar-search-input" id="chat-friend-search" placeholder="Buscar amigos..." data-i18n-placeholder="chat.searchPlaceholder">
                </div>
            </div>
            
            <div class="chat-sidebar-list" id="chat-conversation-list">
                <div class="chat-list-placeholder" id="chat-list-loader">
                    <span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>
                    <span data-i18n="friends.list.loading">Cargando...</span>
                </div>
            </div>
        </div>

        <div class="chat-content-right" id="chat-content-right">

            <div class="chat-content-placeholder active" id="chat-content-placeholder">
                <span class="material-symbols-rounded">chat</span>
                <span data-i18n="chat.selectConversation">Selecciona una conversación para empezar</span>
            </div>

            <div class="chat-content-main disabled" id="chat-content-main">
                
                <div class="chat-content-header">
                    <button type="button" class="chat-back-button" id="chat-back-button">
                        <span class="material-symbols-rounded">arrow_back</span>
                    </button>
                    <div class="chat-header-avatar">
                        <img src="<?php echo $defaultAvatar; ?>" id="chat-header-avatar" alt="Avatar">
                    </div>
                    <div class="chat-header-info">
                        <div class="chat-header-username" id="chat-header-username">Nombre de Usuario</div>
                        <div class="chat-header-status" id="chat-header-status" data-i18n-offline="chat.offline">Offline</div>
                    </div>
                </div>

                <div class="chat-message-list" id="chat-message-list">
                    </div>

                <form class="chat-message-input-form" id="chat-message-input-form" action="#">
                    <?php outputCsrfInput(); ?>
                    <input type="hidden" id="chat-receiver-id" value="">
                    
                    <button type="button" class="chat-attach-button" id="chat-attach-button">
                         <span class="material-symbols-rounded">attach_file</span>
                    </button>
                    
                    <input type="text" class="chat-input-field" id="chat-message-input" placeholder="Escribe tu mensaje..." data-i18n-placeholder="chat.messagePlaceholder" autocomplete="off" disabled>
                    
                    <button type="submit" class="chat-send-button" id="chat-send-button" disabled>
                        <span class="material-symbols-rounded">send</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>