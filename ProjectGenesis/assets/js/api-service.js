// assets/js/api-service.js

import { getTranslation } from './i18n-manager.js';

const API_ENDPOINTS = {
    AUTH: `${window.projectBasePath}/api/auth_handler.php`,
    SETTINGS: `${window.projectBasePath}/api/settings_handler.php`
};

async function _post(url, formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    let response; // --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
    
    try {
        response = await fetch(url, { // --- Asignar a 'response'
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            console.error('Error de red o servidor:', response.statusText);
            return { success: false, message: getTranslation('js.api.errorServer') };
        }

        // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO ▼▼▼ ---
        // Intentar clonar la respuesta para leerla como texto si falla el JSON
        const responseClone = response.clone();
        
        try {
            const result = await response.json(); // Intentar parsear JSON

            if (result.success === false && result.message && result.message.includes('Error de seguridad')) {
                window.showAlert(getTranslation('js.api.errorSecurity'), 'error');
                setTimeout(() => location.reload(), 2000);
            }
            
            return result;

        } catch (jsonError) {
            // ¡FALLÓ EL JSON! Leer la respuesta como texto para ver el error de PHP
            console.error('Error al parsear JSON:', jsonError);
            const errorText = await responseClone.text();
            console.error('Respuesta del servidor (no-JSON):', errorText);
            // Devolver un error más específico
            return { success: false, message: getTranslation('js.api.errorServer') + ' (Respuesta inválida)' };
        }
        // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---


    } catch (error) { // Esto ahora solo captura errores de red (ej. sin internet)
        console.error('Error en la llamada fetch (Red):', error);
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}

export async function callAuthApi(formData) {
    return _post(API_ENDPOINTS.AUTH, formData);
}

export async function callSettingsApi(formData) {
    return _post(API_ENDPOINTS.SETTINGS, formData);
}