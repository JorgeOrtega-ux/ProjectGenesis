// FILE: assets/js/modules/admin-server-settings-manager.js

import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';

/**
 * Obtiene el token CSRF de la página.
 * @returns {string} El token CSRF.
 */
function getCsrfTokenFromPage() {
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    return csrfInput ? csrfInput.value : (window.csrfToken || '');
}

/**
 * Inicializa los listeners para la página de configuración del servidor.
 */
export function initAdminServerSettingsManager() {

    document.body.addEventListener('change', async (e) => {
        // Salir si no es el toggle de mantenimiento
        if (e.target.id !== 'toggle-maintenance-mode') {
            return;
        }

        // Salir si no estamos en la sección correcta (para evitar efectos secundarios)
        const section = e.target.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) {
            return;
        }

        const toggle = e.target;
        const newValue = toggle.checked ? '1' : '0';

        // Deshabilitar el toggle mientras se procesa
        toggle.disabled = true;

        const formData = new FormData();
        formData.append('action', 'update-maintenance-mode');
        formData.append('new_value', newValue);
        formData.append('csrf_token', getCsrfTokenFromPage());

        try {
            const result = await callAdminApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'js.admin.maintenanceSuccess'), 'success');
            } else {
                // Si falla, revertir el estado visual del toggle
                showAlert(getTranslation(result.message || 'js.admin.maintenanceError'), 'error');
                toggle.checked = !toggle.checked;
            }

        } catch (error) {
            // Error de red o similar
            showAlert(getTranslation('js.api.errorServer'), 'error');
            toggle.checked = !toggle.checked;
        } finally {
            // Volver a habilitar el toggle
            toggle.disabled = false;
        }
    });
}