// FILE: assets/js/modules/friend-manager.js
// (MODIFICADO - Añadido menú contextual al hacer clic en amigo)

import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
import { callFriendApi as callApi } from '../services/api-service.js';
// --- ▼▼▼ INICIO DE IMPORTACIONES AÑADIDAS ▼▼▼ ---
import { createPopper } from 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/esm/popper.min.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { handleNavigation } from '../app/url-manager.js';
// --- ▲▲▲ FIN DE IMPORTACIONES AÑADIDAS ▲▲▲ ---

let popperInstance = null; // Instancia para el popover de contexto

// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (RENDER) ▼▼▼ ---
function renderFriendList(friends) {
    const container = document.getElementById('friend-list-items');
    if (!container) return;

    if (friends.length === 0) {
        container.innerHTML = `
            <div class="menu-link" style="pointer-events: none; opacity: 0.7;">
                <div class="menu-link-icon">
                    <span class="material-symbols-rounded">person_off</span>
                </div>
                <div class="menu-link-text">
                    <span data-i18n="friends.list.noFriends">No tienes amigos.</span>
                </div>
            </div>`;
        return;
    }

    let html = '';
    friends.forEach(friend => {
        const statusClass = friend.is_online ? 'online' : 'offline';
        const profileUrl = `${window.projectBasePath}/profile/${friend.username}`;
        
        // --- INICIO DE LA MODIFICACIÓN ---
        // Se cambia <a> por <div>
        // Se quita href y data-nav-js
        // Se añade data-action="toggle-friend-context-menu"
        // Se añaden data-username y data-profile-url para el popover
        html += `
            <div class="menu-link friend-item" 
               data-action="toggle-friend-context-menu"
               data-friend-id="${friend.friend_id}"
               data-username="${friend.username}"
               data-profile-url="${profileUrl}"
               title="${friend.username}">
               
                <div class="menu-link-icon">
                    <div class="comment-avatar" data-role="${friend.role}" style="width: 32px; height: 32px; margin-right: -10px; flex-shrink: 0;">
                        <img src="${friend.profile_image_url}" alt="${friend.username}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <span class="friend-status-dot ${statusClass}"></span>
                    </div>
                </div>
                
                <div class="menu-link-text">
                    <span>${friend.username}</span>
                </div>
            </div>
        `;
        // --- FIN DE LA MODIFICACIÓN ---
    });
    container.innerHTML = html;
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (RENDER) ▼▼▼ ---


export async function initFriendList() {
    const container = document.getElementById('friend-list-container');
    if (!container) return; 

    const formData = new FormData();
    formData.append('action', 'get-friends-list');

    try {
        const result = await callApi(formData); 
        if (result.success) {
            renderFriendList(result.friends);
        } else {
            const listContainer = document.getElementById('friend-list-items');
            if (listContainer) {
                 listContainer.innerHTML = `
                    <div class="menu-link" style="pointer-events: none; opacity: 0.7;">
                        <div class="menu-link-icon">
                            <span class="material-symbols-rounded">error</span>
                        </div>
                        <div class="menu-link-text">
                            <span data-i18n="friends.list.error">Error al cargar.</span>
                        </div>
                    </div>`;
            }
        }
    } catch (e) {
        console.error("Error al cargar lista de amigos:", e);
    }
}

function updateProfileActions(userId, newStatus) {
    const actionsContainer = document.querySelector(`.profile-actions[data-user-id="${userId}"]`);
    if (!actionsContainer) return;

    let newHtml = '';

    switch (newStatus) {
        case 'not_friends':
            newHtml = `
                <button type="button" class="component-button component-button--primary" data-action="friend-send-request" data-user-id="${userId}">
                    <span class="material-symbols-rounded">person_add</span>
                    <span data-i18n="friends.sendRequest">${getTranslation('friends.sendRequest')}</span>
                </button>
            `;
            break;
        case 'pending_sent':
            newHtml = `
                <button type="button" class="component-button" data-action="friend-cancel-request" data-user-id="${userId}">
                    <span class="material-symbols-rounded">close</span>
                    <span data-i18n="friends.cancelRequest">${getTranslation('friends.cancelRequest')}</span>
                </button>
            `;
            break;
        case 'pending_received':
            newHtml = `
                <button type="button" class="component-button component-button--primary" data-action="friend-accept-request" data-user-id="${userId}">
                    <span class="material-symbols-rounded">check</span>
                    <span data-i18n="friends.acceptRequest">${getTranslation('friends.acceptRequest')}</span>
                </button>
                <button type="button" class="component-button" data-action="friend-decline-request" data-user-id="${userId}">
                    <span class="material-symbols-rounded">close</span>
                    <span data-i18n="friends.declineRequest">${getTranslation('friends.declineRequest')}</span>
                </button>
            `;
            break;
        case 'friends':
            newHtml = `
                <button type="button" class="component-button" data-action="friend-remove" data-user-id="${userId}">
                    <span class="material-symbols-rounded">person_remove</span>
                    <span data-i18n="friends.removeFriend">${getTranslation('friends.removeFriend')}</span>
                </button>
            `;
            break;
    }

    actionsContainer.innerHTML = newHtml;
}

function toggleButtonLoading(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalContent = button.innerHTML;
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;
    } else {
        button.innerHTML = button.dataset.originalContent || '';
    }
}

export function initFriendManager() {
    
    // --- ▼▼▼ INICIO DE NUEVO LISTENER (ACTUALIZACIÓN EN TIEMPO REAL) ▼▼▼ ---
    document.addEventListener('user-presence-changed', (e) => {
        const { userId, status } = e.detail; // "online" u "offline"
        
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
    });
    // --- ▲▲▲ FIN DE NUEVO LISTENER ▲▲▲ ---

    document.body.addEventListener('click', async (e) => {
        
        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Menú contextual de amigo) ▼▼▼ ---
        const friendItem = e.target.closest('[data-action="toggle-friend-context-menu"]');
        if (friendItem) {
            e.preventDefault();
            e.stopPropagation();

            if (popperInstance) {
                popperInstance.destroy();
                popperInstance = null;
            }

            const popover = document.getElementById('friend-context-menu');
            if (!popover) return;
            
            // Rellenar los datos en el popover
            const profileUrl = friendItem.dataset.profileUrl;
            const username = friendItem.dataset.username;
            
            const profileLink = popover.querySelector('[data-action="friend-menu-profile"]');
            const messageLink = popover.querySelector('[data-action="friend-menu-message"]');
            
            if (profileLink) profileLink.href = profileUrl;
            if (messageLink) messageLink.dataset.username = username;

            // Posicionar y mostrar el popover
            popperInstance = createPopper(friendItem, popover, {
                placement: 'left-start',
                modifiers: [{ name: 'offset', options: { offset: [0, 8] } }]
            });

            deactivateAllModules(popover);
            popover.classList.remove('disabled');
            popover.classList.add('active');
            
            return;
        }
        
        // Clic en "Enviar Mensaje"
        const messageButton = e.target.closest('[data-action="friend-menu-message"]');
        if (messageButton) {
            e.preventDefault();
            const username = messageButton.dataset.username;
            if (!username) return;

            // Construir la nueva URL y navegar
            const newPath = `${window.projectBasePath}/messages/${username}`;
            history.pushState(null, '', newPath);
            handleNavigation(); // Dejar que el url-manager maneje la carga
            
            deactivateAllModules();
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---


        const button = e.target.closest('[data-action^="friend-"]');
        if (!button) return;

        // Prevenir que el clic en "enviar mensaje" o "ver perfil" se propague
        if (button.dataset.action === 'friend-menu-profile' || button.dataset.action === 'friend-menu-message') {
            return;
        }

        e.preventDefault();
        const actionStr = button.dataset.action;
        const targetUserId = button.dataset.userId;
        
        if (!targetUserId) return;

        const apiAction = actionStr.replace('friend-', '');

        if (apiAction === 'remove' && !confirm(getTranslation('js.friends.confirmRemove') || '¿Seguro que quieres eliminar a este amigo?')) {
             return;
        }
        
        const finalApiAction = (apiAction === 'remove') ? 'remove-friend' : apiAction;

        toggleButtonLoading(button, true);

        const formData = new FormData();
        formData.append('action', finalApiAction);
        formData.append('target_user_id', targetUserId);

        const result = await callApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message), 'success');
            updateProfileActions(targetUserId, result.newStatus);
            initFriendList(); 
            
            const notificationItem = button.closest('.notification-item');
            if (notificationItem) {
                notificationItem.remove();
                const listContainer = document.getElementById('notification-list-items');
                const placeholder = document.getElementById('notification-placeholder');
                if (listContainer && placeholder && listContainer.children.length === 0) {
                    placeholder.style.display = 'flex';
                }
            }

        } else {
            showAlert(getTranslation(result.message || 'js.friends.errorGeneric'), 'error');
            toggleButtonLoading(button, false);
        }
    });
}