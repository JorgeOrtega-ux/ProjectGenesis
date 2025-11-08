import { getTranslation } from '../services/i18n-manager.js';
import { showAlert } from '../services/alert-manager.js';

const API_ENDPOINT_FRIEND = `${window.projectBasePath}/api/friend_handler.php`;

async function callFriendApi(formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch(API_ENDPOINT_FRIEND, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) return { success: false, message: getTranslation('js.api.errorServer') };
        return await response.json();
    } catch (error) {
        return { success: false, message: getTranslation('js.api.errorConnection') };
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

        const result = await callFriendApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message), 'success');
            updateProfileActions(targetUserId, result.newStatus);
        } else {
            showAlert(getTranslation(result.message || 'js.friends.errorGeneric'), 'error');
            toggleButtonLoading(button, false);
        }
    });
}