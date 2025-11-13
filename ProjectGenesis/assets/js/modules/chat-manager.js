// FILE: assets/js/modules/chat-manager.js
// (NUEVO ARCHIVO)

import { callApi } from '../services/api-service.js'; // Asumimos que api-service.js será actualizado
import { getTranslation, applyTranslations } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

// --- Variable de estado del módulo ---
let currentOpenChatUserId = null;
let isLoadingHistory = false;
let conversationCache = []; // Caché simple para la lista de amigos

/**
 * Llamada a la API de chat (asumimos que api-service.js se actualizará para incluir esto)
 * @param {FormData} formData - Datos del formulario a enviar.
 * @returns {Promise<object>} - Resultado de la API.
 */
async function callChatApi(formData) {
    const API_ENDPOINT_CHAT = `${window.projectBasePath}/api/chat_handler.php`;
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch(API_ENDPOINT_CHAT, {
            method: 'POST',
            body: formData,
        });
        if (!response.ok) {
            return { success: false, message: getTranslation('js.api.errorServer') };
        }
        return await response.json();
    } catch (error) {
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}

/**
 * Escapa HTML simple para evitar XSS en los mensajes.
 * @param {string} str - El texto a escapar.
 * @returns {string} - El texto escapado.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[m]));
}

/**
 * Formatea un timestamp de la BD (UTC) a un formato legible (ej. "10:30 AM" o "Ayer").
 * @param {string} dateString - El timestamp UTC de la BD.
 * @returns {string} - El tiempo formateado.
 */
function formatTimestamp(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString + 'Z'); // Asumir UTC
        const now = new Date();
        
        const isToday = date.toDateString() === now.toDateString();
        
        if (isToday) {
            // Formato de hora: 10:30 AM
            return date.toLocaleTimeString(navigator.language, {
                hour: 'numeric',
                minute: '2-digit'
            });
        }
        
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return getTranslation('chat.time.yesterday') || 'Ayer';
        }
        
        // Formato de fecha: 10/11/2025
        return date.toLocaleDateString(navigator.language, {
            day: 'numeric',
            month: 'numeric',
            year: 'numeric'
        });
    } catch (e) {
        console.error("Error al formatear fecha:", e);
        return dateString;
    }
}

/**
 * Desplaza el contenedor de mensajes hasta el final.
 */
function scrollToChatBottom() {
    const messageList = document.getElementById('chat-message-list');
    if (messageList) {
        messageList.scrollTop = messageList.scrollHeight;
    }
}

/**
 * Renderiza la lista de conversaciones en el panel izquierdo.
 * @param {Array} conversations - El array de amigos/conversaciones.
 */
function renderConversationsList(conversations) {
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    const emptyPlaceholder = document.getElementById('chat-list-empty');
    
    if (loader) loader.style.display = 'none';

    if (!conversations || conversations.length === 0) {
        if (emptyPlaceholder) emptyPlaceholder.style.display = 'flex';
        return;
    }
    
    if (emptyPlaceholder) emptyPlaceholder.style.display = 'none';
    
    let html = '';
    conversations.forEach(friend => {
        const statusClass = friend.is_online ? 'online' : 'offline';
        const activeClass = friend.friend_id == currentOpenChatUserId ? 'active' : '';
        
        html += `
            <a href="#" class="chat-conversation-item ${activeClass}" data-user-id="${friend.friend_id}" data-username="${escapeHTML(friend.username)}" data-avatar="${escapeHTML(friend.profile_image_url)}" data-status="${statusClass}">
                <div class="chat-item-avatar">
                    <img src="${escapeHTML(friend.profile_image_url)}" alt="${escapeHTML(friend.username)}">
                    <span class="chat-item-status ${statusClass}"></span>
                </div>
                <div class="chat-item-info">
                    <div class="chat-item-info-header">
                        <span class="chat-item-username">${escapeHTML(friend.username)}</span>
                        <span class="chat-item-timestamp">${formatTimestamp(friend.last_message_time)}</span>
                    </div>
                    <div class="chat-item-snippet-wrapper">
                        <span class="chat-item-snippet">${escapeHTML(friend.last_message_text)}</span>
                        ${friend.unread_count > 0 ? `<span class="chat-item-unread-badge">${friend.unread_count}</span>` : ''}
                    </div>
                </div>
            </a>
        `;
    });
    
    listContainer.innerHTML = html;
}

/**
 * Carga la lista de conversaciones desde la API.
 */
async function loadConversations() {
    const listContainer = document.getElementById('chat-conversation-list');
    const loader = document.getElementById('chat-list-loader');
    if (loader) loader.style.display = 'flex';

    const formData = new FormData();
    formData.append('action', 'get-conversations');
    
    const result = await callChatApi(formData);
    
    if (result.success) {
        conversationCache = result.conversations; // Guardar en caché
        renderConversationsList(conversationCache);
    } else {
        if (loader) loader.style.display = 'none';
        const emptyPlaceholder = document.getElementById('chat-list-empty');
        if (emptyPlaceholder) {
            emptyPlaceholder.style.display = 'flex';
            emptyPlaceholder.querySelector('span[data-i18n]').textContent = getTranslation(result.message || 'friends.list.error');
        }
    }
}

/**
 * Inserta una burbuja de mensaje en el chat activo.
 * @param {object} message - El objeto del mensaje de la BD.
 */
function appendMessageToChat(message) {
    const messageList = document.getElementById('chat-message-list');
    if (!messageList) return;
    
    const currentUserId = window.userId || 0;
    const isSent = message.sender_id == currentUserId;
    const type = isSent ? 'sent' : 'received';
    
    // Avatar (solo para recibidos, y lo tomamos del header del chat)
    let avatarImg = '';
    if (type === 'received') {
        const headerAvatar = document.getElementById('chat-header-avatar');
        avatarImg = headerAvatar ? headerAvatar.src : 'https://ui-avatars.com/api/?name=?';
    }

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${type}`;
    bubble.innerHTML = `
        ${type === 'received' ? `
            <div class="chat-bubble-avatar">
                <img src="${escapeHTML(avatarImg)}" alt="Avatar">
            </div>` : ''}
        <div class="chat-bubble-content">
            ${escapeHTML(message.message_text)}
        </div>
    `;
    
    messageList.appendChild(bubble);
    scrollToChatBottom();
}

/**
 * Renderiza el historial de chat completo en el panel derecho.
 * @param {Array} messages - Array de mensajes de la BD.
 */
function renderChatHistory(messages) {
    const messageList = document.getElementById('chat-message-list');
    if (!messageList) return;
    
    messageList.innerHTML = ''; // Limpiar historial anterior
    const currentUserId = window.userId || 0;
    
    const headerAvatar = document.getElementById('chat-header-avatar');
    const receivedAvatarImg = headerAvatar ? headerAvatar.src : 'https://ui-avatars.com/api/?name=?';

    messages.forEach(message => {
        const isSent = message.sender_id == currentUserId;
        const type = isSent ? 'sent' : 'received';

        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${type}`;
        bubble.innerHTML = `
            ${type === 'received' ? `
                <div class="chat-bubble-avatar">
                    <img src="${escapeHTML(receivedAvatarImg)}" alt="Avatar">
                </div>` : ''}
            <div class="chat-bubble-content">
                ${escapeHTML(message.message_text)}
            </div>
        `;
        messageList.appendChild(bubble);
    });
    
    // Scroll al fondo después de que el DOM se actualice
    setTimeout(scrollToChatBottom, 0);
}

/**
 * Carga el chat de un amigo específico.
 * @param {HTMLElement} friendItemElement - El elemento <a> del amigo en la lista.
 */
async function loadChatForFriend(friendItemElement) {
    if (isLoadingHistory) return;
    
    const targetUserId = friendItemElement.dataset.userId;
    if (currentOpenChatUserId == targetUserId) return; // Ya está abierto
    
    isLoadingHistory = true;
    currentOpenChatUserId = targetUserId;

    // Actualizar UI de la lista
    document.querySelectorAll('.chat-conversation-item.active').forEach(el => el.classList.remove('active'));
    friendItemElement.classList.add('active');

    // Ocultar placeholder, mostrar chat
    const placeholder = document.getElementById('chat-content-placeholder');
    const mainChat = document.getElementById('chat-content-main');
    const messageList = document.getElementById('chat-message-list');
    const messageInput = document.getElementById('chat-message-input');
    const sendButton = document.getElementById('chat-send-button');
    const receiverIdInput = document.getElementById('chat-receiver-id');

    if (placeholder) placeholder.classList.remove('active');
    if (mainChat) mainChat.classList.remove('disabled');
    if (messageList) messageList.innerHTML = `<div class="chat-list-placeholder"><span class="logout-spinner"></span></div>`; // Loader
    
    // Actualizar header del chat
    document.getElementById('chat-header-avatar').src = friendItemElement.dataset.avatar;
    document.getElementById('chat-header-username').textContent = friendItemElement.dataset.username;
    
    const statusEl = document.getElementById('chat-header-status');
    if (friendItemElement.dataset.status === 'online') {
        statusEl.textContent = 'En línea'; // TODO: i18n
        statusEl.className = 'chat-header-status online';
    } else {
        statusEl.textContent = 'Desconectado'; // TODO: i18n
        statusEl.className = 'chat-header-status';
    }

    // Preparar el formulario
    if (receiverIdInput) receiverIdInput.value = targetUserId;
    if (messageInput) messageInput.disabled = false;
    
    // Marcar como leído en la UI
    const unreadBadge = friendItemElement.querySelector('.chat-item-unread-badge');
    if (unreadBadge) {
        unreadBadge.remove();
        // (La API marcará como leído en la BD)
    }
    
    // Mostrar layout de chat en móvil
    document.getElementById('chat-layout-container').classList.add('show-chat');

    // Cargar historial
    const formData = new FormData();
    formData.append('action', 'get-chat-history');
    formData.append('target_user_id', targetUserId);
    
    const result = await callChatApi(formData);
    
    if (result.success) {
        renderChatHistory(result.messages);
    } else {
        showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        if (messageList) messageList.innerHTML = `<div class="chat-list-placeholder"><span>Error al cargar mensajes.</span></div>`;
    }

    isLoadingHistory = false;
    if (messageInput) messageInput.focus();
}

/**
 * Maneja el envío del formulario de chat.
 * @param {Event} event - El evento de submit.
 */
async function handleSendMessage(event) {
    event.preventDefault();
    const messageInput = document.getElementById('chat-message-input');
    const sendButton = document.getElementById('chat-send-button');
    const receiverIdInput = document.getElementById('chat-receiver-id');
    
    const messageText = messageInput.value.trim();
    const receiverId = receiverIdInput.value;

    if (!messageText || !receiverId || sendButton.disabled) {
        return;
    }
    
    messageInput.disabled = true;
    sendButton.disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('receiver_id', receiverId);
    formData.append('message_text', messageText);

    const result = await callChatApi(formData);
    
    if (result.success && result.newMessage) {
        messageInput.value = ''; // Limpiar input
        appendMessageToChat(result.newMessage); // Añadir mi propio mensaje
    } else {
        showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
    }
    
    messageInput.disabled = false;
    messageInput.focus();
}

/**
 * Maneja un mensaje de chat entrante desde el WebSocket.
 * @param {object} messageData - El objeto del mensaje.
 */
function displayIncomingMessage(messageData) {
    // 1. Comprobar si el chat de esta persona está abierto
    if (currentOpenChatUserId == messageData.sender_id) {
        // Sí, está abierto. Simplemente añade la burbuja.
        appendMessageToChat(messageData);
        
        // TODO: Enviar un 'pong' de "leído" al servidor
        
    } else {
        // No, está cerrado. Actualizar la lista de conversaciones.
        showAlert(`Nuevo mensaje de ${messageData.sender_id}`, 'info'); // TODO: i18n y nombre
        
        // Re-cargar la lista de conversaciones para que muestre el badge
        loadConversations();
    }
}

/**
 * Maneja los mensajes globales del WebSocket.
 * @param {MessageEvent} event - El evento del WebSocket.
 */
function handleWebSocketMessage(event) {
    try {
        const data = JSON.parse(event.data);
        
        if (data.type === 'new_chat_message' && data.message) {
            console.log("[ChatManager] Mensaje de chat recibido:", data.message);
            displayIncomingMessage(data.message);
        }
        
        // (app-init.js manejará los pings de notificaciones, presencia, etc.)
        
    } catch (e) {
        console.error("[ChatManager] Error al parsear WS message:", e);
    }
}

/**
 * Se llama CADA VEZ que la sección de mensajes se carga.
 */
export function initChatSection() {
    console.log("[ChatManager] Inicializando sección de Mensajes...");
    currentOpenChatUserId = null; // Resetear chat activo
    
    // Cargar la lista de amigos/conversaciones
    loadConversations();
    
    // Listener para la lista de conversaciones
    const convList = document.getElementById('chat-conversation-list');
    if (convList) {
        convList.onclick = (e) => {
            const friendItem = e.target.closest('.chat-conversation-item');
            if (friendItem) {
                e.preventDefault();
                loadChatForFriend(friendItem);
            }
        };
    }
    
    // Listener para el formulario de envío
    const chatForm = document.getElementById('chat-message-input-form');
    if (chatForm) {
        chatForm.onsubmit = handleSendMessage;
    }
    
    // Listener para el input de texto (habilitar/deshabilitar botón)
    const messageInput = document.getElementById('chat-message-input');
    const sendButton = document.getElementById('chat-send-button');
    if (messageInput && sendButton) {
        messageInput.oninput = () => {
            sendButton.disabled = messageInput.value.trim().length === 0;
        };
    }
    
    // Listener para el botón "Volver" en móvil
    const backButton = document.getElementById('chat-back-button');
    if (backButton) {
        backButton.onclick = () => {
            document.getElementById('chat-layout-container').classList.remove('show-chat');
            currentOpenChatUserId = null;
            // Limpiar selección en la lista
            document.querySelectorAll('.chat-conversation-item.active').forEach(el => el.classList.remove('active'));
            // Ocultar chat, mostrar placeholder
            const placeholder = document.getElementById('chat-content-placeholder');
            const mainChat = document.getElementById('chat-content-main');
            if (placeholder) placeholder.classList.add('active');
            if (mainChat) mainChat.classList.add('disabled');
        };
    }
}

/**
 * Se llama UNA VEZ cuando la app se carga (desde app-init.js).
 */
export function initChatManager() {
    // Asegurarse de que el listener de WebSocket esté listo
    if (window.ws) {
        console.log("[ChatManager] Adjuntando listener de mensajes al WebSocket global.");
        window.ws.addEventListener('message', handleWebSocketMessage);
    } else {
        // Si WS no está listo, reintentar en un segundo
        console.warn("[ChatManager] WebSocket (window.ws) no está listo. Reintentando en 1s...");
        setTimeout(initChatManager, 1000);
    }
}