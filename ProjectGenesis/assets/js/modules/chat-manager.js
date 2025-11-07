// ARCHIVO: assets/js/modules/chat-manager.js
// (Versión modificada para enviar y renderizar mensajes)

import { callChatApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

// Almacenará los archivos seleccionados.
const attachedFiles = new Map();
let isSending = false; // Evitar envíos duplicados

/**
 * Limpia el input de chat, borra los archivos adjuntos y elimina las vistas previas.
 */
function clearChatInput() {
    const textInput = document.getElementById('chat-input-text-area');
    if (textInput) {
        textInput.innerHTML = '';
    }

    const previewContainer = document.getElementById('chat-preview-container');
    if (previewContainer) {
        previewContainer.innerHTML = ''; // Borra todos los items
        removePreviewContainer(previewContainer); // Elimina el contenedor
    }
    
    attachedFiles.clear();
}

/**
 * Elimina el contenedor de preview y quita la clase del wrapper.
 */
function removePreviewContainer(previewContainer) {
    const inputWrapper = document.getElementById('chat-input-wrapper');
    if (inputWrapper) {
        inputWrapper.classList.remove('has-previews');
    }
    if (previewContainer && previewContainer.parentNode) {
        previewContainer.parentNode.removeChild(previewContainer);
    }
}

/**
 * Elimina una vista previa específica y su archivo.
 */
function removePreview(fileId) {
    attachedFiles.delete(fileId);

    const previewContainer = document.getElementById('chat-preview-container');
    if (!previewContainer) return;

    const previewItem = previewContainer.querySelector(`.chat-preview-item[data-file-id="${fileId}"]`);
    if (previewItem) {
        previewItem.remove();
    }

    if (previewContainer.children.length === 0) {
        removePreviewContainer(previewContainer);
    }
}

/**
 * Crea la vista previa de la imagen y la añade al DOM.
 */
function createPreview(file, fileId, previewContainer, inputWrapper) {
    const reader = new FileReader();
    
    reader.onload = (e) => {
        const dataUrl = e.target.result;

        const previewItem = document.createElement('div');
        previewItem.className = 'chat-preview-item';
        previewItem.dataset.fileId = fileId;

        previewItem.innerHTML = `
            <img src="${dataUrl}" alt="${file.name}" class="chat-preview-image">
            <button type="button" class="chat-preview-remove">
                <span class="material-symbols-rounded">close</span>
            </button>
        `;

        previewItem.querySelector('.chat-preview-remove').addEventListener('click', () => {
            removePreview(fileId);
        });

        previewContainer.appendChild(previewItem);
        inputWrapper.classList.add('has-previews');
    };

    reader.readAsDataURL(file);
}

/**
 * Formatea un timestamp (ej. "2025-11-06 20:43:00") a "HH:MM".
 */
function formatMessageTime(timestamp) {
    try {
        // Asumimos que el timestamp es UTC (o la hora del servidor)
        const date = new Date(timestamp.replace(' ', 'T') + 'Z');
        // Convertir a la hora local del navegador
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    } catch (e) {
        console.error("Error formateando fecha de chat:", e);
        return "--:--";
    }
}


/**
 * Renderiza un nuevo mensaje en la UI del chat.
 * Esta función es llamada por app-init.js cuando llega un mensaje de WS.
 * @param {object} msgData El objeto del mensaje (de la API/WS).
 */
export function renderIncomingMessage(msgData) {
    const chatHistory = document.getElementById('chat-history-container');
    if (!chatHistory) return;

    // 1. Determinar si es mi propio mensaje
    const isOwnMessage = msgData.user_id === window.userId;

    // 2. Crear el elemento de la burbuja
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    if (isOwnMessage) {
        bubble.classList.add('is-own');
    }
    
    // 3. Obtener avatar (usar un placeholder si no existe)
    const avatarUrl = msgData.profile_image_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(msgData.username)}&size=100&background=e0e0e0&color=ffffff`;
    
    // 4. Formatear contenido (Texto vs Imagen)
    let contentHtml = '';
    if (msgData.message_type === 'text') {
        contentHtml = `<div class="chat-bubble-text">${msgData.content}</div>`;
    } else if (msgData.message_type === 'image') {
        contentHtml = `<div class="chat-bubble-image">
            <img src="${msgData.content}" alt="Imagen adjunta" loading="lazy">
        </div>`;
    }

    // 5. Formatear hora
    const time = formatMessageTime(msgData.created_at);

    // 6. Ensamblar el HTML
    bubble.innerHTML = `
        <div class="chat-bubble-avatar">
            <img src="${avatarUrl}" alt="${msgData.username}">
        </div>
        <div class="chat-bubble-content">
            <div class="chat-bubble-header">
                <span class="chat-bubble-username">${msgData.username}</span>
            </div>
            ${contentHtml}
            <div class="chat-bubble-footer">
                <span class="chat-bubble-time">${time}</span>
            </div>
        </div>
    `;

    // 7. Añadir al DOM y hacer scroll
    chatHistory.appendChild(bubble);
    chatHistory.scrollTop = chatHistory.scrollHeight;
}

/**
 * Maneja el envío del formulario de chat.
 */
async function handleSendMessage() {
    if (isSending) return;

    const textInput = document.getElementById('chat-input-text-area');
    const sendButton = document.getElementById('chat-send-button');
    const currentGroupUuid = document.body.dataset.currentGroupUuid || '';
    const csrfToken = window.csrfToken || '';

    if (!currentGroupUuid) {
        showAlert('No hay un grupo seleccionado.', 'error'); // (Clave i18n: 'chat.error.noGroup')
        return;
    }

    const messageText = textInput ? textInput.innerText.trim() : ''; // .innerText para contenteditable
    const hasFiles = attachedFiles.size > 0;

    if (!messageText && !hasFiles) {
        return; // No enviar nada si está vacío
    }

    isSending = true;
    if(sendButton) sendButton.disabled = true;
    hideTooltip();

    // 1. Preparar FormData
    const formData = new FormData();
    formData.append('action', 'send-message');
    formData.append('csrf_token', csrfToken);
    formData.append('group_uuid', currentGroupUuid);
    formData.append('message_text', messageText);

    // 2. Adjuntar archivos
    attachedFiles.forEach((file) => {
        formData.append('images[]', file, file.name);
    });

    try {
        // 3. Llamar a la API de Chat
        const result = await callChatApi(formData);

        if (result.success) {
            // 4. Éxito: Limpiar el input.
            // El mensaje se renderizará cuando vuelva por el WebSocket.
            clearChatInput();
        } else {
            // 5. Error: Mostrar alerta
            showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
        }

    } catch (error) {
        console.error("Error al enviar mensaje:", error);
        showAlert(getTranslation('js.api.errorConnection'), 'error');
    } finally {
        isSending = false;
        if(sendButton) sendButton.disabled = false;
    }
}

/**
 * Inicializa los listeners para el input de chat.
 */
export function initChatManager() {
    
    // --- Delegación de eventos para botones ---
    document.body.addEventListener('click', (event) => {
        const target = event.target;
        
        // 1. Botón de adjuntar (+)
        const attachButton = target.closest('#chat-attach-button');
        if (attachButton) {
            const fileInput = document.getElementById('chat-file-input');
            if (fileInput) {
                fileInput.click(); 
            }
            return;
        }
        
        // 2. Botón de enviar (>)
        const sendButton = target.closest('#chat-send-button');
        if (sendButton) {
            handleSendMessage();
            return;
        }
    });

    // --- Listener para el input de archivos ---
    document.body.addEventListener('change', (event) => {
        const fileInput = event.target.closest('#chat-file-input');
        
        if (fileInput) {
            const files = fileInput.files;
            if (!files) return;

            const inputWrapper = document.getElementById('chat-input-wrapper');
            if (!inputWrapper) {
                console.error("No se encontró #chat-input-wrapper.");
                return;
            }

            let previewContainer = document.getElementById('chat-preview-container');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.className = 'chat-input__previews';
                previewContainer.id = 'chat-preview-container';
                
                const textArea = inputWrapper.querySelector('.chat-input__text-area');
                if (textArea) {
                    inputWrapper.insertBefore(previewContainer, textArea);
                } else {
                    inputWrapper.prepend(previewContainer); 
                }
            }

            for (const file of files) {
                // (Validación simple de tipo)
                if (file.type.startsWith('image/')) {
                    const fileId = `file-${Date.now()}-${Math.random()}`;
                    attachedFiles.set(fileId, file);
                    createPreview(file, fileId, previewContainer, inputWrapper);
                } else {
                    showAlert('Solo se pueden adjuntar imágenes.', 'error'); // (i18n: 'chat.error.onlyImages')
                }
            }

            fileInput.value = ''; // Limpiar el input
        }
    });

    // --- Listener para presionar Enter en el área de texto ---
    document.body.addEventListener('keydown', (event) => {
        const textInput = event.target.closest('#chat-input-text-area');
        if (textInput && event.key === 'Enter') {
            if (!event.shiftKey) { // Si no se presiona Shift + Enter
                event.preventDefault(); // Prevenir el salto de línea
                handleSendMessage();
            }
            // Si se presiona Shift + Enter, el comportamiento por defecto (salto de línea) ocurre.
        }
    });
}