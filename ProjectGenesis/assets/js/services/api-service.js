import { getTranslation } from './i18n-manager.js';

const API_ENDPOINTS = {
    AUTH: `${window.projectBasePath}/api/auth_handler.php`,
    SETTINGS: `${window.projectBasePath}/api/settings_handler.php`,
    ADMIN: `${window.projectBasePath}/api/admin_handler.php`,
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    GROUPS: `${window.projectBasePath}/api/groups_handler.php`
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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

// --- ▼▼▼ NUEVA FUNCIÓN AÑADIDA ▼▼▼ ---
async function callAdminApi(formData) {
    return _post(API_ENDPOINTS.ADMIN, formData);
}

/**
 * Llama al manejador de la API de grupos.
 * @param {FormData} formData Los datos del formulario a enviar.
 * @returns {Promise<object>} La respuesta JSON del servidor.
 */
async function callGroupsApi(formData) {
    return _post(API_ENDPOINTS.GROUPS, formData);
}

export { callAuthApi, callSettingsApi, callAdminApi, callGroupsApi }; // <-- MODIFICADO
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---