import { getTranslation } from './i18n-manager.js';

const API_ENDPOINTS = {
    AUTH: `${window.projectBasePath}/api/auth_handler.php`,
    SETTINGS: `${window.projectBasePath}/api/settings_handler.php`,
    ADMIN: `${window.projectBasePath}/api/admin_handler.php`,
    COMMUNITY: `${window.projectBasePath}/api/community_handler.php`,
    PUBLICATION: `${window.projectBasePath}/api/publication_handler.php`,
    FRIEND: `${window.projectBasePath}/api/friend_handler.php`,
    NOTIFICATION: `${window.projectBasePath}/api/notification_handler.php`,
    SEARCH: `${window.projectBasePath}/api/search_handler.php`,
    CHAT: `${window.projectBasePath}/api/chat_handler.php`
};

async function _post(url, formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    let response;

    try {
        response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            console.error('Error de red o servidor:', response.statusText);
            return { success: false, message: getTranslation('js.api.errorServer') };
        }

        const responseClone = response.clone();

        try {
            const result = await response.json();

            if (result.success === false && result.message && result.message.includes('Error de seguridad')) {
                window.showAlert(getTranslation('js.api.errorSecurity'), 'error');
                setTimeout(() => location.reload(), 2000);
            }

            return result;

        } catch (jsonError) {
            console.error('Error al parsear JSON:', jsonError);
            const errorText = await responseClone.text();
            console.error('Respuesta del servidor (no-JSON):', errorText);
            return { success: false, message: getTranslation('js.api.errorServer') + ' (Respuesta inválida)' };
        }

    } catch (error) {
        console.error('Error en la llamada fetch (Red):', error);
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}

async function callAuthApi(formData) {
    return _post(API_ENDPOINTS.AUTH, formData);
}

async function callSettingsApi(formData) {
    return _post(API_ENDPOINTS.SETTINGS, formData);
}

async function callAdminApi(formData) {
    return _post(API_ENDPOINTS.ADMIN, formData);
}

async function callCommunityApi(formData) {
    return _post(API_ENDPOINTS.COMMUNITY, formData);
}

async function callPublicationApi(formData) {
    return _post(API_ENDPOINTS.PUBLICATION, formData);
}

async function callFriendApi(formData) {
    return _post(API_ENDPOINTS.FRIEND, formData);
}

async function callNotificationApi(formData) {
    return _post(API_ENDPOINTS.NOTIFICATION, formData);
}

// --- ▼▼▼ FUNCIÓN AÑADIDA ▼▼▼ ---
async function callSearchApi(formData) {
    return _post(API_ENDPOINTS.SEARCH, formData);
}

async function callChatApi(formData) {
    return _post(API_ENDPOINTS.CHAT, formData);
}
// --- ▲▲▲ FIN DE FUNCIÓN AÑADIDA ▲▲▲ ---


export { 
    callAuthApi, 
    callSettingsApi, 
    callAdminApi, 
    callCommunityApi, 
    callPublicationApi, 
    callChatApi,
    callFriendApi,
    callNotificationApi,
    callSearchApi // <-- EXPORTACIÓN AÑADIDA
};