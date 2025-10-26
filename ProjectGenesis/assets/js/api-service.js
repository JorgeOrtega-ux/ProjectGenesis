import { getTranslation } from './i18n-manager.js';

const API_ENDPOINTS = {
    AUTH: `${window.projectBasePath}/api/auth_handler.php`,
    SETTINGS: `${window.projectBasePath}/api/settings_handler.php`
};

async function _post(url, formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            console.error('Error de red o servidor:', response.statusText);
            return { success: false, message: getTranslation('js.api.errorServer') };
        }

        const result = await response.json();

        if (result.success === false && result.message && result.message.includes('Error de seguridad')) {
            window.showAlert(getTranslation('js.api.errorSecurity'), 'error');
            setTimeout(() => location.reload(), 2000);
        }

        return result;

    } catch (error) {
        console.error('Error en la llamada fetch:', error);
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}

export async function callAuthApi(formData) {
    return _post(API_ENDPOINTS.AUTH, formData);
}

export async function callSettingsApi(formData) {
    return _post(API_ENDPOINTS.SETTINGS, formData);
}