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
 * Manejador genérico para actualizar una configuración del sitio.
 * @param {HTMLElement} inputElement - El elemento input (checkbox o number) que disparó el evento.
 * @param {string} action - La acción de la API a llamar (ej. 'update-maintenance-mode').
 * @param {string} newValue - El nuevo valor a enviar.
 */
async function handleSettingUpdate(inputElement, action, newValue) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('new_value', newValue);
    formData.append('csrf_token', getCsrfTokenFromPage());

    // Deshabilitar el input mientras se procesa
    inputElement.disabled = true;

    try {
        const result = await callAdminApi(formData);

        if (result.success) {
            // Usar un mensaje genérico de éxito para todas las configuraciones
            showAlert(getTranslation(result.message || 'js.admin.settingUpdateSuccess'), 'success');

            // --- Lógica de Vinculación (Casos Especiales) ---
            
            // 1. Si se actualizó el modo mantenimiento
            if (action === 'update-maintenance-mode') {
                const regToggle = document.getElementById('toggle-allow-registration');
                if (regToggle) {
                    if (newValue === '1') {
                        // Mantenimiento ON -> Forzar registro OFF y deshabilitado
                        regToggle.checked = false;
                        regToggle.disabled = true;
                    } else {
                        // Mantenimiento OFF -> Solo rehabilitar, no cambiar valor
                        regToggle.disabled = false;
                    }
                }
            }
            
            // 2. Si se actualizó el toggle de registro (y falló por mantenimiento)
            // (La API maneja esto, pero el JS de 'result.success' no se ejecutaría)

        } else {
            // Si falla, revertir el estado visual del input
            showAlert(getTranslation(result.message || 'js.admin.settingUpdateError'), 'error');
            if (inputElement.type === 'checkbox') {
                inputElement.checked = !inputElement.checked;
            }
            // (Para 'number', revertir al valor original sería más complejo,
            // por ahora solo mostramos el error).
        }

    } catch (error) {
        // Error de red o similar
        showAlert(getTranslation('js.api.errorServer'), 'error');
        if (inputElement.type === 'checkbox') {
            inputElement.checked = !inputElement.checked;
        }
    } finally {
        // Volver a habilitar el input (a menos que sea el de registro y mant. esté on)
        if (inputElement.id === 'toggle-allow-registration') {
            const maintenanceToggle = document.getElementById('toggle-maintenance-mode');
            if (!maintenanceToggle || !maintenanceToggle.checked) {
                inputElement.disabled = false;
            }
        } else {
            inputElement.disabled = false;
        }
    }
}

/**
 * Inicializa los listeners para la página de configuración del servidor.
 */
export function initAdminServerSettingsManager() {

    // Usar 'change' funciona tanto para toggles como para inputs numéricos (se dispara al perder el foco)
    document.body.addEventListener('change', async (e) => {
        
        const input = e.target;
        const action = input.dataset.action;

        // Salir si el input no tiene data-action
        if (!action) {
            return;
        }

        // Salir si no estamos en la sección correcta
        const section = input.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) {
            return;
        }

        let newValue = '';

        if (input.type === 'checkbox') {
            // Es un Toggle
            newValue = input.checked ? '1' : '0';
        } else if (input.type === 'number') {
            // Es un campo numérico
            newValue = input.value;
            // Validación simple
            if (newValue === '' || parseFloat(newValue) < 0) {
                showAlert(getTranslation('js.auth.errorCompleteFields'), 'error');
                // (Opcional: revertir al valor anterior si lo teníamos guardado)
                return;
            }
        } else {
            // No es un input que nos interese
            return;
        }

        // Llamar al manejador genérico
        await handleSettingUpdate(input, action, newValue);
    });
}