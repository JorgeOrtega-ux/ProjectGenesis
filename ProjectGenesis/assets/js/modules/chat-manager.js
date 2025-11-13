// FILE: assets/js/modules/chat-manager.js
// (NUEVO ARCHIVO)

import { callChatApi, callFriendApi } from '../services/api-service.js';
import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

let currentChatUserId = null;
let friendCache = []; // Almacena la lista de amigos para el filtrado
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
const myAvatar = window.profile_image_url || defaultAvatar; // Asumiendo que esto se carga en main-layout

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
 * Renderiza la lista de conversaciones en el panel izquierdo.
 */
function renderConversationList(conversations) {
    const listContainer = document.getElementById('chat-conversation-list');
    if (!listContainer) return;
    
    // Ocultar el loader
    const loader = document.getElementById('chat-list-loader');
    if (loader) loader.style.display = 'none';

    if (!conversations || conversations.length === 0) {
        const empty = document.getElementById('chat-list-empty');
        if (empty) empty.style.display = 'flex';
        return;
    }
    
    listContainer.innerHTML = ''; // Limpiar (quitar loader)
    let html = '';

    conversations.forEach(friend => {
        const avatar = friend.profile_image_url || defaultAvatar;
        const statusClass = friend.is_online ? 'online' : 'offline';
        const timestamp = friend.last_message_time ? formatTime(friend.last_message_time) : '';
        const snippet = friend.last_message ? escapeHTML(friend.last_message) : '...';
        const unreadCount = parseInt(friend.unread_count, 10);
        const unreadBadge = unreadCount > 0 ? `<span class="chat-item-unread-badge">${unreadCount}</span>` : '';
        
        html += `
            <div class="chat-conversation-item" data-user-id="${friend.friend_id}" data-username="${escapeHTML(friend.username)}" data-avatar="${escapeHTML(avatar)}" data-role="${escapeHTML(friend.role)}">
                <div class="chat-item-avatar" data-role="${escapeHTML(friend.role)}">
                    <img src="${escapeHTML(avatar)}" alt="${escapeHTML(friend.username)}">
                    <span class="chat-item-status ${statusClass}" id="chat-status-dot-${friend.friend_id}"></span>
                </div>
                <div class="chat-item-info">
                    <div class="chat-item-info-header">
                        <span class="chat-item-username">${escapeHTML(friend.username)}</span>
                        <span class="chat-item-timestamp">${timestamp}</span>
                    </div>
                    <div class="chat-item-snippet-wrapper">
                        <span class="chat-item-snippet">${snippet}</span>
                        ${unreadBadge}
                    </div>
                </div>
            </div>
        `;
    });
    listContainer.innerHTML = html;
}

/**
 * Carga la lista de amigos/conversaciones inicial.
 */
async function loadConversations() {
    // Primero, obtenemos el estado online de todos los amigos
    let onlineUserIds = {};
    try {
        const formData = new FormData();
        formData.append('action', 'get-friends-list'); // Reutilizamos el endpoint de friend_handler
        const friendResult = await callFriendApi(formData);
        if (friendResult.success) {
            friendResult.friends.forEach(friend => {
                if (friend.is_online) {
                    onlineUserIds[friend.friend_id] = true;
                }
            });
        }
    } catch (e) {
        console.error("Error al obtener estado online de amigos:", e);
    }

    // Segundo, obtenemos las conversaciones (con último mensaje)
    try {
        const formData = new FormData();
        formData.append('action', 'get-conversations');
        const result = await callChatApi(formData);

        if (result.success) {
            // Combinamos el estado online con los datos de la conversación
            result.conversations.forEach(convo => {
                convo.is_online = !!onlineUserIds[convo.friend_id];
            });
            friendCache = result.conversations; // Guardar en caché
            renderConversationList(friendCache);
        } else {
            // Manejar error de carga
            const listContainer = document.getElementById('chat-conversation-list');
            if (listContainer) listContainer.innerHTML = '<div class="chat-list-placeholder">Error al cargar.</div>';
        }
    } catch (e) {
        console.error("Error al cargar conversaciones:", e);
    }
}

/**
 * Filtra la lista de amigos en el panel izquierdo.
 */
function filterConversationList(query) {
    query = query.toLowerCase().trim();
    if (!query) {
        renderConversationList(friendCache);
        return;
    }
    const filtered = friendCache.filter(friend => 
        friend.username.toLowerCase().includes(query)
    );
    renderConversationList(filtered);
}

/**
 * Desplaza el contenedor de mensajes hasta el final.
 */
function scrollToBottom() {
    const msgList = document.getElementById('chat-message-list');
    if (msgList) {
        msgList.scrollTop = msgList.scrollHeight;
    }
}

/**
 * Renderiza las burbujas de chat en el panel derecho.
 */
function renderChatHistory(messages, receiverAvatar, receiverRole) {
    const msgList = document.getElementById('chat-message-list');
    if (!msgList) return;

    msgList.innerHTML = '';
    const myUserId = parseInt(window.userId, 10);
    
    messages.forEach(msg => {
        const isSent = parseInt(msg.sender_id, 10) === myUserId;
        const bubbleClass = isSent ? 'sent' : 'received';
        const avatar = isSent ? myAvatar : receiverAvatar;
        const role = isSent ? (window.userRole || 'user') : receiverRole;
        
        const bubbleHtml = `
            <div class="chat-bubble ${bubbleClass}">
                <div class="chat-bubble-avatar" data-role="${escapeHTML(role)}">
                    <img src="${escapeHTML(avatar)}" alt="Avatar">
                </div>
                <div class="chat-bubble-content">
                    ${escapeHTML(msg.message_text)}
                </div>
            </div>
        `;
        msgList.insertAdjacentHTML('beforeend', bubbleHtml);
    });
    
    scrollToBottom();
}

/**
 * Carga el historial de chat con un amigo específico.
 */
async function openChat(friendId, username, avatar, role, isOnline) {
    const placeholder = document.getElementById('chat-content-placeholder');
    const chatMain = document.getElementById('chat-content-main');
    if (!placeholder || !chatMain) return;

    // Actualizar UI
    placeholder.classList.remove('active');
    placeholder.classList.add('disabled');
    chatMain.classList.remove('disabled');
    chatMain.classList.add('active');
    
    document.getElementById('chat-header-avatar').src = avatar;
    document.getElementById('chat-header-username').textContent = username;
    const statusEl = document.getElementById('chat-header-status');
    statusEl.textContent = isOnline ? 'Online' : 'Offline';
    statusEl.className = isOnline ? 'chat-header-status online' : 'chat-header-status';
    
    document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder" id="chat-list-loader"><span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span></div>';
    document.getElementById('chat-message-input').disabled = true;
    document.getElementById('chat-send-button').disabled = true;
    document.getElementById('chat-receiver-id').value = friendId;

    currentChatUserId = parseInt(friendId, 10);
    
    // Marcar como activo en la lista
    document.querySelectorAll('.chat-conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`.chat-conversation-item[data-user-id="${friendId}"]`)?.classList.add('active');

    // Cargar historial
    const formData = new FormData();
    formData.append('action', 'get-chat-history');
    formData.append('target_user_id', friendId);
    
    try {
        const result = await callChatApi(formData);
        if (result.success) {
            renderChatHistory(result.messages, avatar, role);
            document.getElementById('chat-message-input').disabled = false;
        } else {
            document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder">Error al cargar mensajes.</div>';
        }
    } catch (e) {
        document.getElementById('chat-message-list').innerHTML = '<div class="chat-list-placeholder">Error de conexión.</div>';
    }
}

/**
 * Envía un mensaje de chat.
 */
async function sendMessage() {
    const input = document.getElementById('chat-message-input');
    const sendBtn = document.getElementById('chat-send-button');
    const receiverId = document.getElementById('chat-receiver-id').value;
    const messageText = input.value.trim();

    if (!messageText || !receiverId || sendBtn.disabled) return;
    
    sendBtn.disabled = true;
    input.disabled = true;

    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('receiver_id', receiverId);
    formData.append('message_text', messageText);

    try {
        const result = await callChatApi(formData);
        if (result.success && result.message_sent) {
            // Optimistic UI: renderizar el mensaje enviado
            const msgList = document.getElementById('chat-message-list');
            const bubbleHtml = `
                <div class="chat-bubble sent">
                    <div class="chat-bubble-avatar" data-role="${window.userRole || 'user'}">
                        <img src="${myAvatar}" alt="Avatar">
                    </div>
                    <div class="chat-bubble-content">
                        ${escapeHTML(result.message_sent.message_text)}
                    </div>
                </div>
            `;
            msgList.insertAdjacentHTML('beforeend', bubbleHtml);
            scrollToBottom();
            
            input.value = '';
            input.focus();
        } else {
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }
    } catch (e) {
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        sendBtn.disabled = true; // Se mantiene deshabilitado hasta que se escriba texto
        input.disabled = false;
    }
}

/**
 * Maneja un mensaje de chat entrante desde el WebSocket.
 */
export function handleChatMessageReceived(message) {
    if (!message || !message.sender_id) return;
    
    const senderId = parseInt(message.sender_id, 10);
    
    // Si el chat de esta persona está abierto, renderiza el mensaje
    if (senderId === currentChatUserId) {
        const msgList = document.getElementById('chat-message-list');
        const friendItem = document.querySelector(`.chat-conversation-item.active[data-user-id="${senderId}"]`);
        
        if (!msgList || !friendItem) return;

        const avatar = friendItem.dataset.avatar;
        const role = friendItem.dataset.role;

        const bubbleHtml = `
            <div class="chat-bubble received">
                <div class="chat-bubble-avatar" data-role="${escapeHTML(role)}">
                    <img src="${escapeHTML(avatar)}" alt="Avatar">
                </div>
                <div class="chat-bubble-content">
                    ${escapeHTML(message.message_text)}
                </div>
            </div>
        `;
        msgList.insertAdjacentHTML('beforeend', bubbleHtml);
        scrollToBottom();
        
        // TODO: Enviar un 'ack' al servidor para marcar como leído
        
    } else {
        // Si el chat no está abierto, actualizar la lista de conversaciones
        const friendItem = document.querySelector(`.chat-conversation-item[data-user-id="${senderId}"]`);
        if (friendItem) {
            // Actualizar snippet
            friendItem.querySelector('.chat-item-snippet').textContent = escapeHTML(message.message_text);
            // Actualizar hora
            friendItem.querySelector('.chat-item-timestamp').textContent = formatTime(message.created_at);
            // Actualizar badge de no leído
            let badge = friendItem.querySelector('.chat-item-unread-badge');
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'chat-item-unread-badge';
                friendItem.querySelector('.chat-item-snippet-wrapper').appendChild(badge);
            }
            const newCount = (parseInt(badge.textContent) || 0) + 1;
            badge.textContent = newCount;
            
            // Mover al principio de la lista
            friendItem.parentElement.prepend(friendItem);
        }
    }
}

/**
 * Inicializa todos los listeners para la página de chat.
 */
export function initChatManager() {
    
    // Listener para cargar la lista de conversaciones al entrar a la sección
    // Usamos un MutationObserver para detectar cuándo se carga la sección de 'messages'
    const sectionsContainer = document.querySelector('.main-sections');
    if (sectionsContainer) {
        const observer = new MutationObserver((mutations) => {
            for (let mutation of mutations) {
                if (mutation.type === 'childList') {
                    const messagesSection = document.querySelector('[data-section="messages"]');
                    if (messagesSection) {
                        loadConversations();
                        // Aplicar estado online desde el WebSocket si ya está disponible
                        document.dispatchEvent(new CustomEvent('request-friend-list-presence-update'));
                    } else {
                        currentChatUserId = null; // Resetea el chat activo al salir de la sección
                    }
                }
            }
        });
        observer.observe(sectionsContainer, { childList: true });
    }

    // Listeners de clics dentro de la sección de chat
    document.body.addEventListener('click', (e) => {
        const chatSection = e.target.closest('[data-section="messages"]');
        if (!chatSection) return;
        
        // Clic en un amigo de la lista
        const friendItem = e.target.closest('.chat-conversation-item');
        if (friendItem) {
            e.preventDefault();
            const friendId = friendItem.dataset.userId;
            const username = friendItem.dataset.username;
            const avatar = friendItem.dataset.avatar;
            const role = friendItem.dataset.role;
            const isOnline = friendItem.querySelector('.chat-item-status')?.classList.contains('online');
            
            openChat(friendId, username, avatar, role, isOnline);
            
            // (Móvil) Ocultar lista, mostrar chat
            document.getElementById('chat-layout-container')?.classList.add('show-chat');
            
            // Limpiar badge de no leídos
            friendItem.querySelector('.chat-item-unread-badge')?.remove();
            return;
        }
        
        // Clic en el botón "Atrás" (móvil)
        const backBtn = e.target.closest('#chat-back-button');
        if (backBtn) {
            e.preventDefault();
            document.getElementById('chat-layout-container')?.classList.remove('show-chat');
            currentChatUserId = null;
            // Recargar la lista de conversaciones para actualizar snippets/badges
            loadConversations();
            return;
        }
    });
    
    // Listener para el formulario de envío
    document.body.addEventListener('submit', (e) => {
        const chatForm = e.target.closest('#chat-message-input-form');
        if (chatForm) {
            e.preventDefault();
            sendMessage();
            return;
        }
    });

    // Listener para habilitar/deshabilitar el botón de envío
    document.body.addEventListener('input', (e) => {
        const chatInput = e.target.closest('#chat-message-input');
        if (chatInput) {
            const sendBtn = document.getElementById('chat-send-button');
            if (sendBtn) {
                sendBtn.disabled = chatInput.value.trim().length === 0;
            }
        }
        
        // Listener para el filtro de búsqueda
        const searchInput = e.target.closest('#chat-friend-search');
        if (searchInput) {
            filterConversationList(searchInput.value);
        }
    });
    
    // Listener para el evento de presencia (del friend-manager)
    document.addEventListener('user-presence-changed', (e) => {
        const { userId, status } = e.detail; // "online" u "offline"
        
        // Actualizar la lista de chat
        const chatItem = document.querySelector(`.chat-conversation-item[data-user-id="${userId}"]`);
        if (chatItem) {
            const dot = chatItem.querySelector('.chat-item-status');
            if (dot) {
                dot.classList.remove('online', 'offline');
                dot.classList.add(status); 
            }
        }
        
        // Actualizar el header del chat si está abierto
        if (parseInt(userId, 10) === currentChatUserId) {
            const statusEl = document.getElementById('chat-header-status');
            if (statusEl) {
                statusEl.textContent = status === 'online' ? 'Online' : 'Offline';
                statusEl.className = status === 'online' ? 'chat-header-status online' : 'chat-header-status';
            }
        }
    });
}