// FILE: assets/js/modules/chat-manager.js
// (MODIFICADO PARA ELIMINAR CHATS DE COMUNIDAD)
// (MODIFICADO PARA TEXTAREA AUTO-CRECIBLE CON LÍMITE)

import { callChatApi, callFriendApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
import { createPopper } from 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/esm/popper.min.js';
import { deactivateAllModules } from '../app/main-controller.js';

let currentChatUserId = null; // ID del amigo con el que se está chateando
let friendCache = []; // Caché solo para DMs
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

let selectedAttachments = [];
const MAX_CHAT_FILES = 4;

let isLoadingOlderMessages = false; 
let allMessagesLoaded = false;      
const CHAT_PAGE_SIZE = 30;          

let currentReplyMessageId = null; 
let typingTimer;
let isTyping = false;
let chatPopperInstance = null; 
let currentChatFilter = 'all'; // Estado del filtro: 'all', 'favorites', 'unread', 'archived'
let currentUnreadMessageCount = 0; 

let currentChatTargetId = null; // ID (de usuario)


/**
 * Intenta reproducir el sonido de notificación de chat.
 * Maneja los errores de autoplay del navegador.
 */
function playNotificationSound() {
    const audio = document.getElementById('chat-notification-sound');
    if (audio) {
        audio.currentTime = 0; 
        const playPromise = audio.play();
        
        if (playPromise !== undefined) {
            playPromise.catch(error => {
                // Error común: El usuario no ha interactuado con la página todavía.
            });
        }
    } else {
    }
}


/**
 * Escapa HTML simple para evitar XSS.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, (m) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[m]));
}

/**
 * Formatea la hora de un timestamp (ej: "10:30 AM")
 */
function formatTime(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString.includes('Z') ? dateString : dateString + 'Z');
        return date.toLocaleTimeString(window.userLanguage || 'es-ES', {
            hour: 'numeric',
            minute: '2-digit'
        });
    } catch (e) { return ''; }
}

/**
 * Convierte un timestamp UTC en un string legible "Activo hace X".
 * @param {string} dateTimeString - El timestamp UTC de la BD.
 * @returns {string} - El string formateado.
 */
function formatTimeAgo(dateTimeString) {
    if (!dateTimeString) {
        return getTranslation('chat.offline', 'Desconectado');
    }
    try {
        // Asegurarse de que la fecha se parsea como UTC
        const date = new Date(dateTimeString.includes('Z') ? dateTimeString : dateTimeString + 'Z');
        const now = new Date();
        const seconds = Math.round((now - date) / 1000);
        const minutes = Math.round(seconds / 60);
        const hours = Math.round(minutes / 60);
        const days = Math.round(hours / 24);
        const months = Math.round(days / 30.44); // Promedio de días por mes
        const years = Math.round(days / 365.25); // Cuenta años bisiestos

        if (isNaN(seconds)) {
            return getTranslation('chat.offline', 'Desconectado');
        }

        // Usamos las claves de i18n de la página de "device-sessions"
        if (seconds < 60) {
            return getTranslation('settings.devices.timeSecondsAgo', 'Activo hace unos segundos');
        }
        if (minutes < 60) {
            const key = (minutes === 1) ? 'settings.devices.timeMinute' : 'settings.devices.timeMinutes';
            return `${getTranslation('settings.devices.timeAgoPrefix', 'Activo hace')} ${minutes} ${getTranslation(key, 'minutos')}`;
        }
        if (hours < 24) {
            const key = (hours === 1) ? 'settings.devices.timeHour' : 'settings.devices.timeHours';
            return `${getTranslation('settings.devices.timeAgoPrefix', 'Activo hace')} ${hours} ${getTranslation(key, 'horas')}`;
        }
        if (days < 30) {
            const key = (days === 1) ? 'settings.devices.timeDay' : 'settings.devices.timeDays';
            return `${getTranslation('settings.devices.timeAgoPrefix', 'Activo hace')} ${days} ${getTranslation(key, 'días')}`;
        }
        if (months < 12) {
            const key = (months === 1) ? 'settings.devices.timeMonth' : 'settings.devices.timeMonths';
            return `${getTranslation('settings.devices.timeAgoPrefix', 'Activo hace')} ${months} ${getTranslation(key, 'meses')}`;
        }
        const key = (years === 1) ? 'settings.devices.timeYear' : 'settings.devices.timeYears';
        return `${getTranslation('settings.devices.timeAgoPrefix', 'Activo hace')} ${years} ${getTranslation(key, 'años')}`;
        
    } catch (e) {
        return getTranslation('chat.offline', 'Desconectado');
    }
}

/**
 * Actualiza el badge de mensajes no leídos en el header.
 * @param {number} count - El número total de mensajes no leídos.
 */
export function setUnreadMessageCount(count) {
    currentUnreadMessageCount = count;
    const badge = document.getElementById('message-badge-count');
    if (!badge) return;

    if (count > 99) {
        badge.textContent = '99+';
    } else {
        badge.textContent = count;
    }
    
    if (count > 0) {
        badge.classList.remove('disabled');
    } else {
        badge.classList.add('disabled');
    }
}

/**
 * Obtiene el conteo inicial de mensajes no leídos desde la API.
 */
export async function fetchInitialUnreadCount() {
    const formData = new FormData();
    formData.append('action', 'get-total-unread-count'); 
    const result = await callChatApi(formData);
    if (result.success && result.total_unread_count !== undefined) {
        setUnreadMessageCount(result.total_unread_count);
    } else {
    }
}


/**
 * Renderiza la lista de conversaciones en el panel izquierdo.
 * @param {Array} conversations - La lista de conversaciones (DMs).
 */
function renderConversationList(conversations) {
    
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    const emptyEl = document.getElementById('chat-list-empty'); 
    
    if (!listContainer || !loader || !emptyEl) {
        return;
    }
    
    loader.style.display = 'none';

    if (!conversations || conversations.length === 0) {
        
        // --- INICIO DE LA NUEVA LÓGICA DINÁMICA ---
        const emptyIcon = document.getElementById('chat-list-empty-icon');
        const emptyText = document.getElementById('chat-list-empty-text');
        const searchInput = document.getElementById('chat-friend-search');
        const query = searchInput ? searchInput.value.trim() : '';

        if (query.length > 0) {
            // Causa: Búsqueda activa
            emptyIcon.textContent = 'search_off';
            emptyText.textContent = getTranslation('chat.empty.search', 'No se encontraron chats...');
            emptyText.dataset.i18n = 'chat.empty.search';
        } else if (currentChatFilter !== 'all') {
            // Causa: Filtro de insignia activo
            switch (currentChatFilter) {
                case 'favorites':
                    emptyIcon.textContent = 'star_outline';
                    emptyText.textContent = getTranslation('chat.empty.favorites', 'No tienes chats en favoritos.');
                    emptyText.dataset.i18n = 'chat.empty.favorites';
                    break;
                case 'unread':
                    emptyIcon.textContent = 'mark_email_unread';
                    emptyText.textContent = getTranslation('chat.empty.unread', 'No tienes mensajes no leídos.');
                    emptyText.dataset.i18n = 'chat.empty.unread';
                    break;
                case 'archived':
                    emptyIcon.textContent = 'archive';
                    emptyText.textContent = getTranslation('chat.empty.archived', 'No tienes chats archivados.');
                    emptyText.dataset.i18n = 'chat.empty.archived';
                    break;
                // (Caso 'communities' eliminado)
                default:
                    // Fallback por si acaso
                    emptyIcon.textContent = 'chat';
                    emptyText.textContent = getTranslation('chat.empty.all', 'Inicia una conversación.');
                    emptyText.dataset.i18n = 'chat.empty.all';
                    break;
            }
        } else {
            // Causa: No hay chats de ningún tipo
            emptyIcon.textContent = 'chat';
            emptyText.textContent = getTranslation('chat.empty.all', 'Inicia una conversación.');
            emptyText.dataset.i18n = 'chat.empty.all';
        }
        // --- FIN DE LA NUEVA LÓGICA DINÁMICA ---

        emptyEl.style.display = 'flex'; 
        listContainer.innerHTML = ''; // Limpiar la lista (ya debería estarlo)
        return;
    }
    
    // Si llegamos aquí, SÍ hay conversaciones
    emptyEl.style.display = 'none';
    listContainer.innerHTML = ''; // Limpiar
    let html = '';

    conversations.forEach(convo => {
        
        const isPinned = convo.pinned_at ? 'true' : 'false';
        const isFavorite = convo.is_favorite ? 'true' : 'false';
        const isArchived = convo.is_archived ? 'true' : 'false';
        
        let timestamp = convo.last_message_time ? formatTime(convo.last_message_time) : '';
        const unreadCount = parseInt(convo.unread_count, 10);
        let unreadBadge = unreadCount > 0 ? `<span class="chat-item-unread-badge">${unreadCount}</span>` : '';

        let snippet;
        if (convo.last_message === '[Imagen]') {
            snippet = `<span data-i18n="chat.snippet.image">${getTranslation('chat.snippet.image', '[Imagen]')}</span>`;
        } else if (convo.last_message === 'Se eliminó este mensaje') {
            snippet = `<i data-i18n="chat.snippet.deleted">${getTranslation('chat.snippet.deleted', '[Mensaje eliminado]')}</i>`;
        } else if (convo.last_message) {
            snippet = escapeHTML(convo.last_message);
        } else {
            snippet = '...';
        }
        
        let indicatorsHtml = `
            <div class="chat-item-indicators">
                <span class="chat-item-indicator favorite" style="display: ${isFavorite === 'true' ? 'inline-block' : 'none'};">
                    <span class="material-symbols-rounded">star</span>
                </span>
                <span class="chat-item-indicator pinned" style="display: ${isPinned === 'true' ? 'inline-block' : 'none'};">
                    <span class="material-symbols-rounded">push_pin</span>
                </span>
            </div>
        `;

        // --- Lógica de DM (única lógica ahora) ---
        let name = convo.username;
        let avatar = convo.profile_image_url || defaultAvatar;
        let role = convo.role;
        let statusClass = convo.is_online ? 'online' : 'offline';
        let chatUrl = `${window.projectBasePath}/messages/${convo.uuid}`;
        let isBlockedClass = convo.is_blocked_globally ? 'is-blocked' : '';
        
        let dataAttributes = `
           data-type="dm"
           data-target-id="${convo.friend_id}" 
           data-username="${escapeHTML(name)}" 
           data-avatar="${escapeHTML(avatar)}" 
           data-role="${escapeHTML(role)}"
           data-uuid="${convo.uuid}"
           data-is-blocked-by-me="${convo.is_blocked_by_me}"
           data-is-blocked-globally="${convo.is_blocked_globally}"
           data-last-seen="${convo.last_seen || ''}"
        `;
        
        html += `
            <a class="chat-conversation-item ${isBlockedClass}" 
               href="${chatUrl}"
               data-nav-js="true"
               ${dataAttributes}
               data-is-favorite="${isFavorite}"
               data-pinned-at="${convo.pinned_at || ''}"
               data-is-archived="${isArchived}">
               
                <div class="chat-item-avatar" data-role="${escapeHTML(role)}">
                    <img src="${escapeHTML(avatar)}" alt="${escapeHTML(name)}">
                    <span class="chat-item-status ${statusClass}" id="chat-status-dot-${convo.friend_id}"></span>
                </div>
                <div class="chat-item-info">
                    <div class="chat-item-info-header">
                        <span class="chat-item-username">${escapeHTML(name)}</span>
                        <span class="chat-item-timestamp">${timestamp}</span>
                    </div>
                    <div class="chat-item-snippet-wrapper">
                        <span class="chat-item-snippet">${snippet}</span>
                        ${indicatorsHtml}
                        ${unreadBadge}
                    </div>
                </div>

                <div class="chat-item-actions">
                    <button type="button" class="chat-item-action-btn" data-action="toggle-chat-context-menu" title="Más opciones">
                        <span class="material-symbols-rounded">more_vert</span>
                    </button>
                </div>
            </a>
        `;
    });
    listContainer.innerHTML = html;
}

function filterConversationList(query) {
    
    query = query.toLowerCase().trim();
    
    let sourceCache = friendCache;
    let nameProperty = 'username';
    let renderType = 'dm';

    let filteredBySearch = [];
    if (!query) {
        filteredBySearch = [...sourceCache]; 
    } else {
        filteredBySearch = sourceCache.filter(item => 
            item[nameProperty].toLowerCase().includes(query)
        );
    }

    const showArchived = (currentChatFilter === 'archived');
    
    let conversationsToShow = filteredBySearch.filter(convo => {
        const isArchived = convo.is_archived === true;
        
        if (showArchived) {
            return isArchived;
        }
        
        if (isArchived) {
            return false;
        }
        
        if (currentChatFilter === 'favorites') {
            return convo.is_favorite === true;
        }
        if (currentChatFilter === 'unread') {
            return parseInt(convo.unread_count, 10) > 0;
        }
        
        // Si el filtro es 'all', muestra todo lo que no esté archivado
        return true; 
    });
    
    renderConversationList(conversationsToShow, renderType);
}

async function loadConversations(filter = 'all') {
    
    // Si el filtro es 'communities', simplemente cámbialo a 'all'.
    if (filter === 'communities') {
        filter = 'all';
    }
    
    currentChatFilter = filter;
    
    // Actualizar la UI de los filtros
    document.querySelectorAll('#chat-sidebar-filters .chat-filter-badge').forEach(badge => {
        badge.classList.toggle('active', badge.dataset.filter === filter);
    });
    
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    const emptyEl = document.getElementById('chat-list-empty');
    
    if (loader) loader.style.display = 'flex';
    if (emptyEl) emptyEl.style.display = 'none';
    if (listContainer) listContainer.innerHTML = '';
    
    let action = 'get-conversations'; // <-- Hardcoded
    
    try {
        const formData = new FormData();
        formData.append('action', action);
        const result = await callChatApi(formData);

        if (result.success) {
            
            let conversations = result.conversations;
            
            // Lógica de DMs (ahora incondicional)
            let onlineUserIds = {};
            try {
                const presenceFormData = new FormData();
                presenceFormData.append('action', 'get-friends-list');
                const presenceResult = await callFriendApi(presenceFormData);
                if (presenceResult.success) {
                    presenceResult.friends.forEach(friend => {
                        if (friend.is_online) {
                            onlineUserIds[friend.friend_id] = true;
                        }
                    });
                }
            } catch (e) {
            }
            conversations.forEach(convo => {
                convo.is_online = !!onlineUserIds[convo.friend_id];
            });
            friendCache = conversations; // Guardar en caché de DMs
            
            const searchInput = document.getElementById('chat-friend-search');
            const currentQuery = searchInput ? searchInput.value : '';
            
            filterConversationList(currentQuery); // <-- Esta función ahora usa el 'currentChatFilter'
            
        } else {
            if (listContainer) listContainer.innerHTML = '<div class="chat-list-placeholder">Error al cargar.</div>';
        }
    } catch (e) {
    }
}

function scrollToBottom() {
    const msgList = document.getElementById('chat-message-list');
    if (msgList) {
        setTimeout(() => {
            msgList.scrollTop = msgList.scrollHeight;
        }, 0);
    }
}

// --- ▼▼▼ INICIO DE MODIFICACIÓN JS ▼▼▼ ---
// 1. RE-AÑADIR la función helper de auto-crecimiento
/**
 * Ajusta la altura de un textarea automáticamente.
 * @param {HTMLTextAreaElement} el - El elemento textarea.
 */
function autoGrowTextarea(el) {
    if (!el) return;
    
    // La altura máxima está definida en el CSS (.chat-input-field)
    const maxHeight = parseInt(window.getComputedStyle(el).maxHeight, 10) || 120;
    
    el.style.height = 'auto'; // Resetea la altura para permitir que se encoja
    
    if (el.scrollHeight <= maxHeight) {
        // Crece hasta el max-height
        el.style.height = (el.scrollHeight) + 'px';
        el.style.overflowY = 'hidden'; // Oculta el scroll si no se necesita
    } else {
        // Si pasa el max-height, fija la altura y muestra el scroll
        el.style.height = maxHeight + 'px';
        el.style.overflowY = 'auto'; // Muestra el scroll
    }
}

function enableChatInput(allow, reason = null) {
    const input = document.getElementById('chat-message-input');
    const attachBtn = document.getElementById('chat-attach-button');
    const sendBtn = document.getElementById('chat-send-button');
    
    if (!input || !attachBtn || !sendBtn) return;

    if (allow) {
        input.disabled = false;
        attachBtn.disabled = false;
        input.placeholder = getTranslation('chat.messagePlaceholder', 'Escribe tu mensaje...');
        validateSendButton(); 
    } else {
        input.disabled = true;
        attachBtn.disabled = true;
        sendBtn.disabled = true; 
        input.value = ''; 
        
        const placeholderKey = reason || 'js.chat.errorPrivacyBlocked';
        input.placeholder = getTranslation(placeholderKey, 'No puedes enviar mensajes a este usuario.');
        
        selectedAttachments = [];
        const previewContainer = document.getElementById('chat-attachment-preview-container');
        const fileInput = document.getElementById('chat-attachment-input');
        if (previewContainer) previewContainer.innerHTML = '';
        if (fileInput) fileInput.value = '';
        hideReplyPreview();
    }
    
    // 2. AÑADIR la llamada a autoGrowTextarea
    autoGrowTextarea(input);
}
// --- ▲▲▲ FIN DE MODIFICACIÓN JS ▲▲▲ ---

function createMessageBubbleHtml(msg, isSent) {
    const myUserId = parseInt(window.userId, 10);
    const myAvatar = document.querySelector('.header-profile-image')?.src || defaultAvatar;
    const myRole = window.userRole || 'user';
    
    let avatar, role;
    const bubbleClass = isSent ? 'sent' : 'received';

    if (isSent) {
        avatar = myAvatar;
        role = myRole;
    } else {
        // Lógica de DM
        const friendItem = document.querySelector(`.chat-conversation-item[data-target-id="${msg.sender_id}"]`);
        if (friendItem) {
            avatar = friendItem.dataset.avatar;
            role = friendItem.dataset.role;
        } else {
            avatar = document.getElementById('chat-header-avatar').src;
            role = 'user'; // Fallback
        }
    }
    
    let actionsMenuHtml = '';
    if (msg.status !== 'deleted') {
         actionsMenuHtml = `
            <div class="chat-bubble-actions">
                <button type="button" class="chat-action-btn" data-action="msg-reply" title="Responder">
                    <span class="material-symbols-rounded">reply</span>
                </button>
                <button type="button" class="chat-action-btn" data-action="msg-copy" title="Copiar">
                    <span class="material-symbols-rounded">content_copy</span>
                </button>
                ${isSent ? `
                <button type="button" class="chat-action-btn chat-action-btn--danger" data-action="msg-delete" title="Eliminar mensaje">
                    <span class="material-symbols-rounded">delete</span>
                </button>
                ` : ''}
            </div>
        `;
    }

    let replyContextHtml = '';
    if (msg.reply_to_message_id && msg.status !== 'deleted') {
        const repliedUser = msg.replied_message_user || 'Usuario';
        let repliedText = msg.replied_message_text || '';
        
        if (repliedText === 'Se eliminó este mensaje') {
            repliedText = `<i>${escapeHTML(repliedText)}</i>`;
        } else {
            repliedText = escapeHTML(repliedText);
        }

        replyContextHtml = `
            <div class="chat-reply-context">
                <div class="chat-reply-context-user">${escapeHTML(repliedUser)}</div>
                <div class="chat-reply-context-text">${repliedText}</div>
            </div>
        `;
    }
    
    let textHtml = '';
    if (msg.status === 'deleted') {
        textHtml = `<div class="chat-bubble-content"><i>${escapeHTML(msg.message_text)}</i></div>`;
    } else {
        if (msg.message_text) {
            textHtml = `<div class="chat-bubble-content">${escapeHTML(msg.message_text)}</div>`;
        }
    }

    let attachmentsHtml = '';
    const attachments = (msg.attachment_urls && msg.status !== 'deleted') ? msg.attachment_urls.split(',') : [];
    
    if (attachments.length > 0) {
        let itemsHtml = '';
        attachments.forEach(url => {
            itemsHtml += `
                <div class="chat-attachment-item">
                    <img src="${escapeHTML(url)}" alt="Adjunto de chat" loading="lazy">
                </div>
            `;
        });
        
        attachmentsHtml = `
            <div class="chat-attachments-container" data-count="${attachments.length}">
                ${itemsHtml}
            </div>
        `;
    }

    // (senderNameHtml eliminado)

    const deletedClass = (msg.status === 'deleted') ? 'deleted' : '';
    const bubbleHtml = `
        <div class="chat-bubble ${bubbleClass} ${deletedClass}" data-message-id="${msg.id}" data-text-content="${escapeHTML(msg.message_text)}">
            <div class="chat-bubble-avatar" data-role="${escapeHTML(role)}">
                <img src="${escapeHTML(avatar)}" alt="Avatar">
            </div>
            <div class="chat-bubble-main-content">
                ${replyContextHtml}
                ${attachmentsHtml}
                ${textHtml}
            </div>
            ${actionsMenuHtml}
        </div>
    `;
    
    return bubbleHtml;
}

function renderChatHistory(messages) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    msgList.innerHTML = '';
    const myUserId = parseInt(window.userId, 10);
    
    messages.reverse();
    
    let bubblesHtml = '';
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        bubblesHtml += createMessageBubbleHtml(msg, isSent);
    });
    
    msgList.innerHTML = bubblesHtml;
    scrollToBottom();
}

function prependChatHistory(messages) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList || messages.length === 0) return;

    const myUserId = parseInt(window.userId, 10);
    
    const oldScrollHeight = msgList.scrollHeight;
    
    messages.reverse();
    
    let bubblesHtml = '';
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        bubblesHtml += createMessageBubbleHtml(msg, isSent);
    });
    
    msgList.insertAdjacentHTML('afterbegin', bubblesHtml);
    
    const newScrollHeight = msgList.scrollHeight;
    msgList.scrollTop = newScrollHeight - oldScrollHeight;
}

function showHistoryLoader(show) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    let loader = document.getElementById('chat-history-loader');
    if (show) {
        if (!loader) {
            loader = document.createElement('div');
            loader.id = 'chat-history-loader';
            loader.className = 'chat-list-placeholder';
            loader.innerHTML = `<span class="logout-spinner" style="width: 24px; height: 24px; border-width: 3px;"></span>`;
            msgList.prepend(loader);
        }
    } else {
        if (loader) {
            loader.remove();
        }
    }
}

async function loadChatHistory(targetId, beforeId = null) {
    const msgList = document.getElementById('chat-message-list');
    const isPaginating = beforeId !== null;

    if (isPaginating) {
        isLoadingOlderMessages = true;
        showHistoryLoader(true);
    } else {
        allMessagesLoaded = false;
        isLoadingOlderMessages = false;
        msgList.innerHTML = '<div class="chat-list-placeholder" id="chat-list-loader"><span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span></div>';
    }

    const formData = new FormData();
    formData.append('action', 'get-chat-history');
    formData.append('target_id', targetId); // <-- Modificado
    if (isPaginating) {
        formData.append('before_message_id', beforeId);
    }

    try {
        const result = await callChatApi(formData);
        
        if (isPaginating) showHistoryLoader(false);

        if (result.success) {
            if (result.messages.length < result.limit) {
                allMessagesLoaded = true;
            }

            if (isPaginating) {
                prependChatHistory(result.messages);
            } else {
                renderChatHistory(result.messages);
                
                if (result.can_send_message) {
                    enableChatInput(true); 
                } else {
                    enableChatInput(false, 'js.chat.errorBlocked'); 
                }
            }
            
            // Actualizar el conteo total si la API lo devuelve (sucede al abrir un chat)
            if (result.new_total_unread_count !== undefined) {
                setUnreadMessageCount(result.new_total_unread_count);
            }
            
        } else {
            if (!isPaginating) {
                msgList.innerHTML = `<div class="chat-list-placeholder">${getTranslation(result.message || 'js.api.errorServer')}</div>`;
                enableChatInput(false, result.message); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
        }
    } catch (e) {
        if (isPaginating) showHistoryLoader(false);
        if (!isPaginating) {
            msgList.innerHTML = '<div class="chat-list-placeholder">Error de conexión.</div>';
            enableChatInput(false, 'js.api.errorConnection'); 
        } else {
            showAlert(getTranslation('js.api.errorConnection'), 'error');
        }
    } finally {
        if (isPaginating) {
            isLoadingOlderMessages = false;
        }
    }
}


/**
 * Carga el historial de chat con un amigo.
 */
async function openChat(targetId, name, avatar, role, isOnline, lastSeen) {
    const placeholder = document.getElementById('chat-content-placeholder');
    const chatMain = document.getElementById('chat-content-main');
    if (!chatMain || !placeholder) return; 

    hideReplyPreview();

    placeholder.classList.remove('active');
    placeholder.classList.add('disabled');
    chatMain.classList.remove('disabled');
    chatMain.classList.add('active');
    
    // --- Lógica de Cabecera ---
    document.getElementById('chat-header-avatar').src = avatar;
    document.getElementById('chat-header-username').textContent = name;
    const statusEl = document.getElementById('chat-header-status');
    const avatarEl = document.getElementById('chat-header-avatar').closest('.chat-header-avatar');
    
    // --- Lógica de DM (única lógica ahora) ---
    avatarEl.style.borderRadius = '50%'; // Avatar redondo para DMs
    if (isOnline) {
        statusEl.textContent = getTranslation('chat.online', 'Online');
        statusEl.className = 'chat-header-status online active';
    } else {
        statusEl.textContent = formatTimeAgo(lastSeen);
        statusEl.className = 'chat-header-status active';
    }
    
    const typingEl = document.getElementById('chat-header-typing');
    if (typingEl) typingEl.classList.add('disabled');
    
    document.getElementById('chat-message-input').disabled = true;
    document.getElementById('chat-send-button').disabled = true;

    const input = document.getElementById('chat-message-input');
    if (input) {
        input.placeholder = getTranslation('chat.messagePlaceholder', 'Escribe un mensaje...');
    }

    document.getElementById('chat-attachment-preview-container').innerHTML = '';
    selectedAttachments = [];
    document.getElementById('chat-attachment-input').value = ''; 

    // --- Actualizar estado global ---
    currentChatTargetId = parseInt(targetId, 10);
    currentChatUserId = parseInt(targetId, 10); // Para "typing"
    
    // --- Actualizar UI de la lista ---
    document.querySelectorAll('.chat-conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    
    let selector = `.chat-conversation-item[data-type="dm"][data-target-id="${targetId}"]`;
        
    document.querySelector(selector)?.classList.add('active');

    // Cargar historial
    await loadChatHistory(targetId, null);
}

function validateSendButton() {
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    if (!input || !sendBtn) return;

    if (input.disabled) {
        sendBtn.disabled = true;
        return;
    }
    
    const hasText = input.value.trim().length > 0;
    const hasFiles = selectedAttachments.length > 0;
    
    sendBtn.disabled = !hasText && !hasFiles;
}

function createAttachmentPreview(file) {
    const container = document.getElementById('chat-attachment-preview-container');
    if (!container) return;

    const previewDiv = document.createElement('div');
    previewDiv.className = 'chat-attachment-preview-item';
    
    const reader = new FileReader();
    reader.onload = (e) => {
        previewDiv.innerHTML = `
            <img src="${e.target.result}" alt="${escapeHTML(file.name)}">
            <button type="button" class="chat-preview-remove-btn">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;
        
        previewDiv.querySelector('.chat-preview-remove-btn').addEventListener('click', () => {
            selectedAttachments = selectedAttachments.filter(f => f !== file);
            previewDiv.remove();
            document.getElementById('chat-attachment-input').value = '';
            validateSendButton();
        });
    };
    reader.readAsDataURL(file);
    
    container.appendChild(previewDiv);
}

function handleAttachmentChange(e) {
    const files = e.target.files;
    if (!files) return;

    const currentCount = selectedAttachments.length;
    const allowedNewCount = MAX_CHAT_FILES - currentCount;

    if (files.length > allowedNewCount) {
        showAlert(getTranslation('js.publication.errorFileCount', 'No puedes subir más de 4 archivos.').replace('4', MAX_CHAT_FILES), 'error');
    }

    const filesToProcess = Array.from(files).slice(0, allowedNewCount);

    const MAX_SIZE_MB = 5;
    const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    for (const file of filesToProcess) {
        if (!ALLOWED_TYPES.includes(file.type)) {
            showAlert(getTranslation('js.publication.errorFileType'), 'error');
            continue; 
        }
        
        if (file.size > MAX_SIZE_BYTES) {
            showAlert(getTranslation('js.publication.errorFileSize').replace('%size%', MAX_SIZE_MB), 'error');
            continue; 
        }

        selectedAttachments.push(file);
        createAttachmentPreview(file);
    }
    
    e.target.value = '';
    validateSendButton();
}

function showReplyPreview(messageId, username, text) {
    const container = document.getElementById('chat-reply-preview-container');
    if (!container) return;
    
    const input = document.getElementById('chat-message-input');
    if (input && input.disabled) {
        return;
    }

    const snippet = text.length > 100 ? text.substring(0, 100) + '...' : text;

    container.innerHTML = `
        <div class="chat-reply-preview-content">
            <div class="chat-reply-preview-user">${escapeHTML(username)}</div>
            <div class="chat-reply-preview-text">${escapeHTML(snippet)}</div>
        </div>
        <button type="button" class="chat-reply-preview-close" id="chat-reply-preview-close">
            <span class="material-symbols-rounded">close</span>
        </button>
    `;
    container.style.display = 'flex';
    currentReplyMessageId = messageId;
    
    document.getElementById('chat-reply-preview-close').addEventListener('click', hideReplyPreview);
    document.getElementById('chat-message-input')?.focus();
}

function hideReplyPreview() {
    const container = document.getElementById('chat-reply-preview-container');
    if (container) {
        container.style.display = 'none';
        container.innerHTML = '';
    }
    currentReplyMessageId = null;
}

async function sendMessage() {
    
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    const messageText = input.value.trim();

    if (!currentChatTargetId || sendBtn.disabled) {
        return;
    }
    if (!messageText && selectedAttachments.length === 0) {
        return;
    }
    
    sendBtn.disabled = true;
    input.disabled = true;
    document.getElementById('chat-attach-button').disabled = true;

    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('target_id', currentChatTargetId); // Modificado
    formData.append('message_text', messageText);
    
    if (currentReplyMessageId) {
        formData.append('reply_to_message_id', currentReplyMessageId);
    }
    
    for (const file of selectedAttachments) {
        formData.append('attachments[]', file, file.name);
    }

    try {
        const result = await callChatApi(formData);

        if (result.success && result.message_sent) {
            const bubbleHtml = createMessageBubbleHtml(result.message_sent, true);
            document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
            scrollToBottom();
            
            await loadConversations(currentChatFilter); // Recargar la lista de DMs
            
            // Re-seleccionar el item activo en la lista
            let selector = `.chat-conversation-item[data-type="dm"][data-target-id="${currentChatTargetId}"]`;
            
            const friendItem = document.querySelector(selector);
            if (friendItem) {
                document.querySelectorAll('.chat-conversation-item').forEach(item => item.classList.remove('active'));
                friendItem.classList.add('active');
            }
            
            input.value = '';
            selectedAttachments = [];
            document.getElementById('chat-attachment-preview-container').innerHTML = '';
            document.getElementById('chat-attachment-input').value = '';
            hideReplyPreview();
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN JS ▼▼▼ ---
            // 3. Resetear la altura del textarea después de enviar
            autoGrowTextarea(input); 
            // --- ▲▲▲ FIN DE MODIFICACIÓN JS ▲▲▲ ---

            enableChatInput(true);
            input.focus();
            
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            
            if (result.message === 'js.chat.errorBlocked' || 
                result.message === 'js.chat.errorPrivacyBlocked' || 
                result.message === 'js.chat.errorPrivacySenderBlocked' ||
                result.message === 'js.chat.errorPrivacyMutualBlocked') {
                
                enableChatInput(false, result.message); 
                
                if (result.message === 'js.chat.errorPrivacySenderBlocked') {
                    showAlert(getTranslation('js.chat.errorPrivacySenderBlocked'), 'error');
                } else if (result.message === 'js.chat.errorPrivacyMutualBlocked') {
                    showAlert(getTranslation('js.chat.errorPrivacyMutualBlocked'), 'error');
                }

            } else {
                enableChatInput(true);
            }
        }
    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
        
        enableChatInput(true);
        
    } finally {
    }
}

export function handleChatMessageReceived(message) {
    
    if (!message || !message.sender_id) {
        return;
    }

    let targetId = parseInt(message.sender_id, 10);
    let listToReload = 'all'; // Solo DMs
    
    // Recargar la lista correspondiente
    if (currentChatFilter === listToReload || (currentChatFilter !== 'communities' && listToReload === 'all')) {
         loadConversations(currentChatFilter);
    } else {
         friendCache = []; // Invalidar caché
    }
    
    // Comprobar si el chat está abierto
    if (targetId === currentChatTargetId) {
        const bubbleHtml = createMessageBubbleHtml(message, false);
        document.getElementById('chat-message-list').insertAdjacentHTML('beforeend', bubbleHtml);
        scrollToBottom();
    } else {
        setUnreadMessageCount(currentUnreadMessageCount + 1);
        
        const isOnMessagesPage = window.location.pathname.startsWith(window.projectBasePath + '/messages');
        
        if (!isOnMessagesPage) {
            playNotificationSound();
        } else {
        }
    }
}

function renderDeletedMessage(bubbleEl) {
    if (!bubbleEl) return;
    bubbleEl.classList.add('deleted');
    
    const mainContent = bubbleEl.querySelector('.chat-bubble-main-content');
    if (mainContent) {
        mainContent.innerHTML = `<div class="chat-bubble-content"><i>${getTranslation('js.chat.messageDeleted', 'Se eliminó este mensaje')}</i></div>`;
    }
    
    const actions = bubbleEl.querySelector('.chat-bubble-actions');
    if (actions) actions.remove();
}

export function handleMessageDeleted(payload) {
    
    if (!payload || !payload.message_id) return;
    
    const messageId = payload.message_id;
    const bubble = document.querySelector(`.chat-bubble[data-message-id="${messageId}"]`);
    
    if (bubble) {
        renderDeletedMessage(bubble);
    }
    
    loadConversations();
}

async function _executeChatContextMenuAction(action, userId) {
    if (!userId) return;

    const formData = new FormData();
    formData.append('target_user_id', userId);

    try {
        if (action === 'block-user') {
            if (!confirm(getTranslation('js.chat.confirmBlock', '¿Estás seguro de que quieres bloquear a este usuario? No podrán enviarse mensajes.'))) {
                return;
            }
            formData.append('action', 'block-user');
            const result = await callFriendApi(formData); 
            
            if (result.success) {
                showAlert(getTranslation(result.message || 'js.chat.userBlocked'), 'success');
                if (parseInt(userId, 10) === currentChatUserId) {
                    enableChatInput(false, 'js.chat.errorBlocked'); 
                }
                await loadConversations(); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }

        } else if (action === 'unblock-user') {
            if (!confirm(getTranslation('js.chat.confirmUnblock', '¿Desbloquear a este usuario?'))) {
                return;
            }
            formData.append('action', 'unblock-user');
            const result = await callFriendApi(formData); 
            
            if (result.success) {
                showAlert(getTranslation(result.message || 'js.chat.userUnblocked'), 'success');
                if (parseInt(userId, 10) === currentChatUserId) {
                    await loadChatHistory(userId, null);
                }
                await loadConversations(); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
            
        } else if (action === 'delete-chat') {
            if (!confirm(getTranslation('js.chat.confirmDeleteChat', '¿Eliminar historial? Esto solo eliminará tu copia de la conversación. La otra persona aún la verá.'))) {
                return;
            }
            formData.append('action', 'delete-chat');
            const result = await callChatApi(formData); 
            
            if (result.success) {
                showAlert(getTranslation(result.message || 'js.chat.chatDeleted'), 'success');
                
                if (parseInt(userId, 10) === currentChatUserId) {
                    const placeholder = document.getElementById('chat-content-placeholder');
                    const chatMain = document.getElementById('chat-content-main');
                    if (chatMain || placeholder) {
                        placeholder.classList.add('active');
                        placeholder.classList.remove('disabled');
                        chatMain.classList.add('disabled');
                        chatMain.classList.remove('active');
                    }
                    currentChatUserId = null;
                    
                    const newPath = `${window.projectBasePath}/messages`;
                    history.pushState(null, '', newPath);
                }
                await loadConversations(); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
        
        } else if (action === 'pin-chat' || action === 'unpin-chat') {
            
            formData.append('action', 'toggle-pin-chat');
            const result = await callChatApi(formData);
            
            if (result.success) {
                showAlert(getTranslation(result.message), 'success');
                await loadConversations(); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
        
        } else if (action === 'add-favorites' || action === 'remove-favorites') {
            
            formData.append('action', 'toggle-favorite');
            const result = await callChatApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message), 'success');
                const friendItem = document.querySelector(`.chat-conversation-item[data-target-id="${userId}"]`);
                if (friendItem) {
                    friendItem.dataset.isFavorite = result.new_is_favorite;
                    const favIcon = friendItem.querySelector('.chat-item-indicator.favorite');
                    if (favIcon) {
                        favIcon.style.display = result.new_is_favorite ? 'inline-block' : 'none';
                    }
                }
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
        
        } else if (action === 'archive-chat' || action === 'unarchive-chat') {
            
            formData.append('action', 'toggle-archive-chat');
            const result = await callChatApi(formData);
            
            if (result.success) {
                showAlert(getTranslation(result.message), 'success');
                await loadConversations(); 
            } else {
                showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
            }
        }

    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    }
}

export function handleTypingEvent(senderId, isTyping) {
    if (parseInt(senderId, 10) !== currentChatUserId) {
        return; 
    }
    const statusEl = document.getElementById('chat-header-status');
    const typingEl = document.getElementById('chat-header-typing');
    if (statusEl && typingEl) {
        if (isTyping) {
            statusEl.classList.remove('active');
            statusEl.classList.add('disabled');
            typingEl.classList.add('active');
            typingEl.classList.remove('disabled');
        } else {
            statusEl.classList.add('active');
            statusEl.classList.remove('disabled');
            typingEl.classList.remove('active');
            typingEl.classList.add('disabled');
        }
    }
}

/**
 * Inicializa todos los listeners para la página de chat.
 */
export function initChatManager() {
    
    
    const sectionsContainer = document.querySelector('.main-sections');
    if (sectionsContainer) {
        const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    const messagesSection = document.querySelector('[data-section="messages"]');
                    if (messagesSection) {
                        
                        loadConversations('all'); // Cargar DMs por defecto
                        document.dispatchEvent(new CustomEvent('request-friend-list-presence-update'));
                        
                        const chatMain = messagesSection.querySelector('#chat-content-main[data-autoload-chat="true"]');
                        if (chatMain) {
                            
                            const headerInfo = messagesSection.querySelector('#chat-header-info');
                            const avatarImg = messagesSection.querySelector('#chat-header-avatar');
                            const targetIdInput = messagesSection.querySelector('#chat-target-id');
                            
                            if (headerInfo && avatarImg && targetIdInput) {
                                const targetId = targetIdInput.value;
                                
                                document.getElementById('chat-layout-container')?.classList.add('show-chat');
                                chatMain.dataset.autoloadChat = 'false';
                                
                                // Actualizar estado global
                                currentChatTargetId = parseInt(targetId, 10);
                                currentChatUserId = currentChatTargetId;
                                
                                // Cargar historial
                                loadChatHistory(targetId, null);
                            }
                        }

                    } else {
                        currentChatUserId = null; 
                        currentChatTargetId = null; 
                    }
                }
            }
        });
        observer.observe(sectionsContainer, { childList: true });
    }

    document.body.addEventListener('click', async (e) => {
        
        // --- 4. Listener de clic en la lista de conversaciones ---
        const conversationItem = e.target.closest('a.chat-conversation-item[data-nav-js="true"]');
        if (conversationItem) {
            e.preventDefault(); 
            
            const targetId = conversationItem.dataset.targetId;
            const name = conversationItem.dataset.username;
            const avatar = conversationItem.dataset.avatar;
            const role = conversationItem.dataset.role;
            const isOnline = conversationItem.querySelector('.chat-item-status')?.classList.contains('online');
            const lastSeen = conversationItem.dataset.lastSeen || null;
            
            document.getElementById('chat-layout-container')?.classList.add('show-chat');
            
            openChat(targetId, name, avatar, role, isOnline, lastSeen);
            
            return;
        }

        const chatSection = e.target.closest('[data-section="messages"]');
        
        if (!chatSection) {
            if (chatPopperInstance) {
                chatPopperInstance.destroy();
                chatPopperInstance = null;
            }
            document.querySelector('.chat-item-actions.popover-active')?.classList.remove('popover-active');
            return; 
        }
        
        const filterBadge = e.target.closest('.chat-filter-badge[data-filter]');
        if (filterBadge) {
            e.preventDefault();
            const newFilter = filterBadge.dataset.filter;
            
            if (newFilter === 'communities') { // Filtro de comunidad ya no existe
                return;
            }
            
            if (newFilter === currentChatFilter) return; 
            
            
            // Llamar a loadConversations con el nuevo filtro
            loadConversations(newFilter); 
            
            return;
        }

        const contextBtn = e.target.closest('[data-action="toggle-chat-context-menu"]');
        if (contextBtn) {
            e.preventDefault();
            e.stopImmediatePropagation(); 

            const friendItem = contextBtn.closest('.chat-conversation-item');
            const popover = document.getElementById('chat-context-menu');
            const actionsContainer = contextBtn.closest('.chat-item-actions');

            if (!friendItem || !popover || !actionsContainer) return;

            if (chatPopperInstance) {
                chatPopperInstance.destroy();
                chatPopperInstance = null;
            }
            
            document.querySelectorAll('.chat-item-actions.popover-active').forEach(el => el.classList.remove('popover-active'));

            // --- Lógica de Popover (solo DM) ---
            const targetId = friendItem.dataset.targetId;
            const type = friendItem.dataset.type; // Siempre será 'dm'
            
            const isFavorite = friendItem.dataset.isFavorite === 'true';
            const isPinned = friendItem.dataset.pinnedAt.length > 0;
            const isArchived = friendItem.dataset.isArchived === 'true'; 
            
            popover.dataset.currentTargetId = targetId;
            popover.dataset.currentType = type;
            
            const blockBtn = popover.querySelector('[data-action="block-user"]');
            const unblockBtn = popover.querySelector('[data-action="unblock-user"]');
            const deleteBtn = popover.querySelector('[data-action="delete-chat"]');
            const profileBtn = popover.querySelector('[data-action="friend-menu-profile"]');

            // --- Lógica de DM (ahora incondicional) ---
            const isBlockedByMe = friendItem.dataset.isBlockedByMe === 'true';
            const isBlockedGlobally = friendItem.dataset.isBlockedGlobally === 'true';
            
            blockBtn.style.display = 'flex';
            unblockBtn.style.display = 'flex';
            deleteBtn.style.display = 'flex'; 
            profileBtn.style.display = 'flex';
            
            if (isBlockedByMe) {
                blockBtn.style.display = 'none';
                unblockBtn.style.display = 'flex';
            } else {
                blockBtn.style.display = 'flex';
                unblockBtn.style.display = 'none';
            }
            
            if (isBlockedGlobally && !isBlockedByMe) {
                blockBtn.style.display = 'none';
                unblockBtn.style.display = 'none';
            }
            
            profileBtn.href = `${window.projectBasePath}/profile/${friendItem.dataset.username}`;

            // --- (Fin Lógica de DM) ---

            const pinBtn = popover.querySelector('[data-action="pin-chat"]');
            const unpinBtn = popover.querySelector('[data-action="unpin-chat"]');
            const favBtn = popover.querySelector('[data-action="add-favorites"]');
            const unFavBtn = popover.querySelector('[data-action="remove-favorites"]');
            const archiveBtn = popover.querySelector('[data-action="archive-chat"]');
            const unarchiveBtn = popover.querySelector('[data-action="unarchive-chat"]');

            if (pinBtn && unpinBtn) {
                pinBtn.style.display = (isPinned || isArchived) ? 'none' : 'flex';
                unpinBtn.style.display = (isPinned && !isArchived) ? 'flex' : 'none';
            }
            if (favBtn && unFavBtn) {
                favBtn.style.display = isFavorite ? 'none' : 'flex';
                unFavBtn.style.display = isFavorite ? 'flex' : 'none';
            }
            if (archiveBtn && unarchiveBtn) { 
                archiveBtn.style.display = isArchived ? 'none' : 'flex';
                unarchiveBtn.style.display = isArchived ? 'flex' : 'none';
            }

            chatPopperInstance = createPopper(contextBtn, popover, {
                placement: 'left-start',
                modifiers: [{ name: 'offset', options: { offset: [0, 8] } }]
            });

            deactivateAllModules(popover); 
            popover.classList.toggle('disabled'); 
            popover.classList.toggle('active');
            
            if (popover.classList.contains('active')) {
                actionsContainer.classList.add('popover-active');
            } else {
                actionsContainer.classList.remove('popover-active');
            }
            return;
        }
        
        const popoverOption = e.target.closest('#chat-context-menu .menu-link');
        if (popoverOption) {
             e.preventDefault();
             e.stopPropagation();
             
             if (popoverOption.disabled) return; 
             
             const action = popoverOption.dataset.action;
             
             if (action === 'friend-menu-profile') {
                 deactivateAllModules();
                 if (chatPopperInstance) {
                     chatPopperInstance.destroy();
                     chatPopperInstance = null;
                 }
                 document.querySelector('.chat-item-actions.popover-active')?.classList.remove('popover-active');
                 return;
             }
             
             const popover = popoverOption.closest('#chat-context-menu');
             const targetId = popover.dataset.currentTargetId;
             
             deactivateAllModules();
             if (chatPopperInstance) {
                 chatPopperInstance.destroy();
                 chatPopperInstance = null;
             }
             document.querySelector('.chat-item-actions.popover-active')?.classList.remove('popover-active');
             
             _executeChatContextMenuAction(action, targetId);
             
             return;
        }

        const clickedOnPopover = e.target.closest('#chat-context-menu.active');
        if (!contextBtn && !clickedOnPopover) {
             if (chatPopperInstance) {
                chatPopperInstance.destroy();
                chatPopperInstance = null;
             }
             document.querySelector('.chat-item-actions.popover-active')?.classList.remove('popover-active');
        }
        
        const backBtn = e.target.closest('#chat-back-button');
        if (backBtn) {
            e.preventDefault();
            document.getElementById('chat-layout-container')?.classList.remove('show-chat');
            currentChatUserId = null;
            currentChatTargetId = null;
            loadConversations(currentChatFilter); // Recargar la lista actual
            return;
        }

        const attachBtn = e.target.closest('#chat-attach-button');
        if (attachBtn) {
            e.preventDefault();
            if (selectedAttachments.length >= MAX_CHAT_FILES) {
                showAlert(getTranslation('js.publication.errorFileCount', 'No puedes subir más de 4 archivos.').replace('4', MAX_CHAT_FILES), 'error');
                return;
            }
            document.getElementById('chat-attachment-input')?.click();
            return;
        }

        const actionBtn = e.target.closest('.chat-action-btn[data-action]');
        if (actionBtn) {
            const action = actionBtn.dataset.action;
            const messageBubble = actionBtn.closest('.chat-bubble');
            const messageId = messageBubble?.dataset.messageId;
            if (!messageId) return;

            if (action === 'msg-copy') {
                const textContent = messageBubble.dataset.textContent;
                if (textContent) {
                    try {
                        await navigator.clipboard.writeText(textContent);
                        showAlert(getTranslation('js.chat.copied', 'Mensaje copiado'), 'success');
                    } catch (err) {
                        showAlert(getTranslation('js.chat.copyError', 'Error al copiar'), 'error');
                    }
                }
            } 
            
            else if (action === 'msg-reply') {
                let username = 'Usuario';
                if (messageBubble.classList.contains('sent')) {
                    username = getTranslation('js.chat.replyToSelf', 'a ti mismo');
                } else {
                    // DM
                    username = document.getElementById('chat-header-username').textContent;
                }
                
                const textContent = messageBubble.dataset.textContent;
                showReplyPreview(messageId, username, textContent);
            } 
            
            else if (action === 'msg-delete') {
                if (!confirm(getTranslation('js.chat.confirmDelete', '¿Eliminar este mensaje? Esta acción no se puede deshacer.'))) {
                    return;
                }
                
                actionBtn.disabled = true;
                const formData = new FormData();
                formData.append('action', 'delete-message');
                formData.append('message_id', messageId);
                
                const result = await callChatApi(formData);
                if (result.success) {
                    showAlert(getTranslation('js.chat.successDeleted', 'Mensaje eliminado'), 'info');
                } else {
                    showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                    actionBtn.disabled = false;
                }
            } 
            
            else if (action === 'msg-info') {
                showAlert(`Info para msg ID: ${messageId} (no implementado)`, 'info');
            }
            return;
        }
    });
    
    document.body.addEventListener('submit', (e) => {
        const chatForm = e.target.closest('#chat-message-input-form');
        if (chatForm) {
            e.preventDefault();
            sendMessage();
            return;
        }
    });

    // --- ▼▼▼ INICIO DE MODIFICACIÓN JS ▼▼▼ ---
    // 3. Modificar el listener de 'input'
    document.body.addEventListener('input', (e) => {
        const chatInput = e.target.closest('#chat-message-input');
        if (chatInput) {
            validateSendButton();
            // 4. AÑADIR la llamada a autoGrowTextarea
            autoGrowTextarea(chatInput); 
            
            // Lógica de "typing" (solo para DMs)
            if (currentChatUserId && window.ws && window.ws.readyState === WebSocket.OPEN) {
                if (!isTyping) {
                    isTyping = true;
                    window.ws.send(JSON.stringify({
                        type: 'typing_start',
                        recipient_id: currentChatUserId
                    }));
                }
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => {
                    if (window.ws && window.ws.readyState === WebSocket.OPEN) {
                        window.ws.send(JSON.stringify({
                            type: 'typing_stop',
                            recipient_id: currentChatUserId
                        }));
                    }
                    isTyping = false;
                }, 2000); 
            }
        }
        
        const searchInput = e.target.closest('#chat-friend-search');
        if (searchInput) {
            filterConversationList(searchInput.value);
        }
    });

    // 5. Modificar el listener de 'keydown' para que NO llame a autoGrow
    document.body.addEventListener('keydown', (e) => {
        const chatInput = e.target.closest('#chat-message-input');
        if (chatInput && e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const sendBtn = document.getElementById('chat-send-button');
            if (sendBtn && !sendBtn.disabled) {
                sendMessage();
            }
        }
    });
    // --- ▲▲▲ FIN DE MODIFICACIÓN JS ▲▲▲ ---
    
    document.body.addEventListener('change', (e) => {
        const fileInput = e.target.closest('#chat-attachment-input');
        if (fileInput) {
            handleAttachmentChange(e);
        }
    });

    document.body.addEventListener('scroll', (e) => {
        const msgList = e.target.closest('#chat-message-list');
        if (!msgList) return;

        if (msgList.scrollTop === 0 && !isLoadingOlderMessages && !allMessagesLoaded) {
            
            const firstMessageEl = msgList.querySelector('.chat-bubble[data-message-id]');
            if (!firstMessageEl) return; 
            
            const beforeId = firstMessageEl.dataset.messageId;
            
            if (currentChatTargetId && beforeId) {
                loadChatHistory(currentChatTargetId, beforeId);
            }
        }
    }, true); 

    // --- 6. Añadir listener para 'user-presence-changed' ---
    document.addEventListener('user-presence-changed', (e) => {
        const { userId, status } = e.detail; 
        
        // 1. Actualizar la LISTA DE AMIGOS (el punto verde/gris)
        const friendItem = document.querySelector(`.friend-item[data-friend-id="${userId}"]`);
        if (friendItem) {
            const dot = friendItem.querySelector('.friend-status-dot');
            if (dot) {
                dot.classList.remove('online', 'offline');
                dot.classList.add(status); 
            }
        }

        // 2. Actualizar la PÁGINA DE PERFIL (si está abierta)
        const profileBadge = document.querySelector(`.profile-status-badge[data-user-id="${userId}"]`);
        if (profileBadge) {
            profileBadge.classList.remove('online', 'offline');
            profileBadge.classList.add(status);
            
            if (status === 'online') {
                profileBadge.innerHTML = `<span class="status-dot"></span>Activo ahora`;
            } else {
                // Actualizamos a un texto genérico "Offline"
                // Tu lógica de "hace 5 min" se ejecutará la próxima vez que cargues la página.
                profileBadge.innerHTML = `Activo hace un momento`; 
            }
        }
        
        // 3. Actualizar la CABECERA DE CHAT (si está abierta)
        if (parseInt(userId, 10) === currentChatTargetId) {
            const statusEl = document.getElementById('chat-header-status');
            const typingEl = document.getElementById('chat-header-typing');
            
            // Solo actualizar si "escribiendo..." no está activo
            if (statusEl && typingEl && !typingEl.classList.contains('active')) {
                if (status === 'online') {
                    statusEl.textContent = getTranslation('chat.online', 'Online');
                    statusEl.className = 'chat-header-status online active';
                } else {
                    // Acaba de desconectarse, mostramos "hace un momento"
                    statusEl.textContent = getTranslation('settings.devices.timeSecondsAgo', 'Activo hace unos segundos');
                    statusEl.className = 'chat-header-status active';
                }
            }
        }
    });
    
    const styleSheet = document.createElement("style");
    styleSheet.type = "text/css";
    styleSheet.innerText = `
        .chat-conversation-item.is-blocked {
            opacity: 0.6;
        }
        .chat-conversation-item.is-blocked:hover {
            opacity: 0.8;
            background-color: #f5f5fa; /* Mantener el hover normal */
        }
    `;
    document.head.appendChild(styleSheet);
    
    // (Promesa de communityIds eliminada)
}