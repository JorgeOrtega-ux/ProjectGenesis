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
        
        const toggle = e.target;
        const toggleId = toggle.id;

        // Salir si no es uno de los toggles de esta página
        if (toggleId !== 'toggle-maintenance-mode' && toggleId !== 'toggle-allow-registration') {
            return;
        }

        // Salir si no estamos en la sección correcta
        const section = toggle.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) {
            return;
        }

        const newValue = toggle.checked ? '1' : '0';
        let action = '';
        let formData = new FormData();
        
        formData.append('new_value', newValue);
        formData.append('csrf_token', getCsrfTokenFromPage());

        // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA ▼▼▼ ---

        if (toggleId === 'toggle-maintenance-mode') {
            action = 'update-maintenance-mode';
            formData.append('action', action);

        } else if (toggleId === 'toggle-allow-registration') {
            action = 'update-registration-mode';
            formData.append('action', action);
        }
        
        // Deshabilitar el toggle mientras se procesa
        toggle.disabled = true;

        try {
            const result = await callAdminApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'js.admin.maintenanceSuccess'), 'success');

                // Lógica de vinculación: si el modo mantenimiento se activó,
                // actualizar el toggle de registro.
                if (action === 'update-maintenance-mode') {
                    const regToggle = document.getElementById('toggle-allow-registration');
                    if (regToggle) {
                        if (result.newValue === '1') {
                            // Mantenimiento ON -> Forzar registro OFF y deshabilitado
                            regToggle.checked = false;
                            regToggle.disabled = true;
                        } else {
                            // Mantenimiento OFF -> Solo rehabilitar, no cambiar valor
                            regToggle.disabled = false;
                        }
                    }
                }

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
            // Volver a habilitar el toggle (a menos que sea el de registro y mant. esté on)
            if (toggleId === 'toggle-maintenance-mode') {
                toggle.disabled = false;
            } else if (toggleId === 'toggle-allow-registration') {
                // No re-habilitar si el modo mantenimiento está activo
                const maintenanceToggle = document.getElementById('toggle-maintenance-mode');
                if (!maintenanceToggle || !maintenanceToggle.checked) {
                    toggle.disabled = false;
                }
            }
        }
        // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---
    });
}