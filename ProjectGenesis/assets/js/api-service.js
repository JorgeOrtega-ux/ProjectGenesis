/* ====================================== */
/* ========= API-SERVICE.JS ============= */
/* ====================================== */

// 1. Definir los endpoints de la API en un solo lugar
const API_ENDPOINTS = {
    AUTH: `${window.projectBasePath}/api/auth_handler.php`,
    SETTINGS: `${window.projectBasePath}/api/settings_handler.php`
};

/**
 * Función privada principal para realizar la solicitud POST.
 * Maneja el token CSRF, la llamada fetch, los errores de red y el parseo de JSON.
 * @param {string} url - El endpoint de la API al que llamar.
 * @param {FormData} formData - El objeto FormData que ya contiene la 'action' y otros datos.
 * @returns {Promise<object>} - La respuesta JSON del servidor o un objeto de error estándar.
 */
async function _post(url, formData) {
    // 2. Obtener y adjuntar automáticamente el token CSRF
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        // 3. Realizar la llamada fetch
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        // 4. Manejar respuestas no-ok (ej. 404, 500)
        if (!response.ok) {
            console.error('Error de red o servidor:', response.statusText);
            return { success: false, message: 'Error en la respuesta del servidor.' };
        }

        // 5. Parsear la respuesta JSON
        const result = await response.json();

        // 6. Manejar errores de seguridad (ej. token CSRF inválido)
        // Esto recargará la página para obtener un nuevo token.
        if (result.success === false && result.message && result.message.includes('Error de seguridad')) {
            window.showAlert('Error de seguridad. Recargando la página...', 'error');
            setTimeout(() => location.reload(), 2000);
        }

        return result;

    } catch (error) {
        // 7. Manejar errores de red (ej. sin conexión)
        console.error('Error en la llamada fetch:', error);
        return { success: false, message: 'No se pudo conectar con el servidor.' };
    }
}

/**
 * Llama al endpoint de autenticación.
 * @param {FormData} formData - FormData con la 'action' (ej. 'login-check', 'register-verify').
 * @returns {Promise<object>} - La respuesta de la API.
 */
export async function callAuthApi(formData) {
    return _post(API_ENDPOINTS.AUTH, formData);
}

/**
 * Llama al endpoint de configuración.
 * @param {FormData} formData - FormData con la 'action' (ej. 'upload-avatar', 'update-username').
 * @returns {Promise<object>} - La respuesta de la API.
 */
export async function callSettingsApi(formData) {
    return _post(API_ENDPOINTS.SETTINGS, formData);
}