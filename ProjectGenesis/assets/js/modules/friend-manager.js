import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';
// --- ▼▼▼ ¡IMPORTACIÓN MODIFICADA! ▼▼▼ ---
import { callFriendApi as callApi } from '../services/api-service.js'; 

// --- ▼▼▼ ¡FUNCIÓN LOCAL callFriendApi ELIMINADA! ▼▼▼ ---
// (Ya no es necesaria, usamos la del servicio)


// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (RENDER) ▼▼▼ ---
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
        html += `
            <a class="menu-link" 
               href="${window.projectBasePath}/profile/${friend.username}"
               data-nav-js="true"
               title="${friend.username}">
                <div class="menu-link-icon">
                    <img src="${friend.profile_image_url}" alt="${friend.username}" class="menu-link-avatar">
                </div>
                <div class="menu-link-text">
                    <span>${friend.username}</span>
                </div>
            </a>
        `;
    });
    container.innerHTML = html;
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN (RENDER) ▲▲▲ ---


// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (INIT) ▼▼▼ ---
export async function initFriendList() {
    const container = document.getElementById('friend-list-container');
    if (!container) return; // No estamos en una página con la lista

    const formData = new FormData();
    formData.append('action', 'get-friends-list');

    try {
        // Usamos la API importada
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
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN (INIT) ▲▲▲ ---

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
    document.body.addEventListener('click', async (e) => {
        const button = e.target.closest('[data-action^="friend-"]');
        if (!button) return;

        e.preventDefault();
        const actionStr = button.dataset.action;
        const targetUserId = button.dataset.userId;
        
        if (!targetUserId) return;

        // Extraer la acción real (e.g., "send-request" de "friend-send-request")
        const apiAction = actionStr.replace('friend-', '');

        if (apiAction === 'remove' && !confirm(getTranslation('js.friends.confirmRemove') || '¿Seguro que quieres eliminar a este amigo?')) {
             return;
        }
        
        // Mapeo especial para 'remove' -> 'remove-friend' en la API si es necesario
        const finalApiAction = (apiAction === 'remove') ? 'remove-friend' : apiAction;

        toggleButtonLoading(button, true);

        const formData = new FormData();
        formData.append('action', finalApiAction);
        formData.append('target_user_id', targetUserId);

        // --- ▼▼▼ MODIFICACIÓN: Usar callApi (importada) ▼▼▼ ---
        const result = await callApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message), 'success');
            
            // --- ▼▼▼ ¡LÓGICA DE UI MODIFICADA! ▼▼▼ ---
            
            // 1. Actualizar botones del perfil (si estamos en uno)
            updateProfileActions(targetUserId, result.newStatus);
            
            // 2. Actualizar la lista de amigos (sidebar)
            initFriendList(); 
            
            // 3. Si el botón estaba en el panel de notificaciones, eliminar el item
            const notificationItem = button.closest('.notification-item');
            if (notificationItem) {
                notificationItem.remove();
                
                // Comprobar si la lista de notificaciones quedó vacía
                const listContainer = document.getElementById('notification-list-items');
                if (listContainer && listContainer.querySelectorAll('.notification-item').length === 0) {
                    const placeholder = document.getElementById('notification-placeholder');
                    if (placeholder) placeholder.style.display = 'flex';
                }
            }
            // --- ▲▲▲ ¡FIN DE LÓGICA DE UI MODIFICADA! ▲▲▲ ---

        } else {
            showAlert(getTranslation(result.message || 'js.friends.errorGeneric'), 'error');
            toggleButtonLoading(button, false);
        }
        // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
    });
}