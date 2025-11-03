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
 * Oculta errores inline en una tarjeta de componente.
 * @param {HTMLElement} cardElement - La tarjeta (.component-card) que contiene el error.
 */
function hideInlineError(cardElement) {
    if (!cardElement) return;
    const nextElement = cardElement.nextElementSibling;
    if (nextElement && nextElement.classList.contains('component-card__error')) {
        nextElement.remove();
    }
}

/**
 * Muestra un error inline debajo de una tarjeta de componente.
 * @param {HTMLElement} cardElement - La tarjeta (.component-card) bajo la cual mostrar el error.
 * @param {string} messageKey - La clave de traducción i18n para el mensaje.
 * @param {object|null} data - Datos para reemplazar en la clave de traducción.
 */
function showInlineError(cardElement, messageKey, data = null) {
    if (!cardElement) return;
    hideInlineError(cardElement); 
    const errorDiv = document.createElement('div');
    errorDiv.className = 'component-card__error';
    let message = getTranslation(messageKey);
    if (data) {
        Object.keys(data).forEach(key => {
            message = message.replace(`%${key}%`, data[key]);
        });
    }
    errorDiv.textContent = message;
    // Insertar después de la tarjeta, no dentro
    cardElement.parentNode.insertBefore(errorDiv, cardElement.nextSibling);
}

/**
 * Manejador genérico para actualizar una configuración del sitio.
 * @param {HTMLElement} element - El elemento (input, label, o div.component-stepper) que disparó el evento.
 * @param {string} action - La acción de la API a llamar (ej. 'update-maintenance-mode').
 * @param {string} newValue - El nuevo valor a enviar.
 */
async function handleSettingUpdate(element, action, newValue) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('new_value', newValue);
    formData.append('csrf_token', getCsrfTokenFromPage());

    // Deshabilitar el elemento (sea input, label o div)
    element.classList.add('disabled-interactive');

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
                        // Aplicar clase disabled al <label> padre si existe
                        regToggle.closest('.component-toggle-switch')?.classList.add('disabled-interactive');
                    } else {
                        // Mantenimiento OFF -> Solo rehabilitar, no cambiar valor
                        regToggle.disabled = false;
                        regToggle.closest('.component-toggle-switch')?.classList.remove('disabled-interactive');
                    }
                }
            }
            
            // 2. Si es un stepper, actualizamos su valor base
            if (element.classList.contains('component-stepper')) {
                element.dataset.currentValue = newValue;
            }

        } else {
            // Si falla, revertir el estado visual del input
            showAlert(getTranslation(result.message || 'js.admin.settingUpdateError'), 'error');
            
            if (element.type === 'checkbox') {
                element.checked = !element.checked;
            } else if (element.classList.contains('component-stepper')) {
                // Revertir el stepper al valor original guardado
                const originalValue = element.dataset.currentValue;
                const valueDisplay = element.querySelector('.stepper-value');
                if (valueDisplay) valueDisplay.textContent = originalValue;
                
                // Re-evaluar estado de botones min/max
                const min = parseInt(element.dataset.min, 10);
                const max = parseInt(element.dataset.max, 10);
                element.querySelector('[data-step-action="decrement"]').disabled = originalValue <= min;
                element.querySelector('[data-step-action="increment"]').disabled = originalValue >= max;
            }
        }

    } catch (error) {
        // Error de red o similar
        showAlert(getTranslation('js.api.errorServer'), 'error');
        if (element.type === 'checkbox') {
            element.checked = !element.checked;
        } else if (element.classList.contains('component-stepper')) {
            const originalValue = element.dataset.currentValue;
            const valueDisplay = element.querySelector('.stepper-value');
            if (valueDisplay) valueDisplay.textContent = originalValue;
        }
    } finally {
        // Volver a habilitar el input (a menos que sea el de registro y mant. esté on)
        if (element.id === 'toggle-allow-registration') {
            const maintenanceToggle = document.getElementById('toggle-maintenance-mode');
            if (!maintenanceToggle || !maintenanceToggle.checked) {
                element.classList.remove('disabled-interactive');
            }
        } else {
            element.classList.remove('disabled-interactive');
        }
    }
}

/**
 * Inicializa los listeners para la página de configuración del servidor.
 */
export function initAdminServerSettingsManager() {

    // 1. LISTENER DE CLICS (Para Steppers Y Botones de Dominio)
    document.body.addEventListener('click', async (e) => {
        const button = e.target.closest('button[data-step-action], button[data-action]');
        if (!button) return;
        
        const section = button.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        const action = button.dataset.action;
        const stepAction = button.dataset.stepAction;

        // --- LÓGICA DEL STEPPER ---
        if (stepAction) {
            const wrapper = button.closest('.component-stepper');
            if (!wrapper || wrapper.classList.contains('disabled-interactive')) return;

            const stepperAction = wrapper.dataset.action;
            if (!stepperAction) return;

            const valueDisplay = wrapper.querySelector('.stepper-value');
            const min = parseInt(wrapper.dataset.min, 10);
            const max = parseInt(wrapper.dataset.max, 10);
            const step = parseInt(wrapper.dataset.step, 10) || 1;
            let currentValue = parseInt(wrapper.dataset.currentValue, 10);

            let newValue = currentValue;

            if (stepAction === 'increment') {
                newValue = currentValue + step;
            } else if (stepAction === 'decrement') {
                newValue = currentValue - step;
            }

            // Validar
            if (!isNaN(min) && newValue < min) newValue = min;
            if (!isNaN(max) && newValue > max) newValue = max;
            
            // Si el valor no cambió (ya estaba en min/max), no hacer nada
            if (newValue === currentValue) return;

            // Actualizar UI
            if (valueDisplay) valueDisplay.textContent = newValue;
            
            // Actualizar botones disabled
            wrapper.querySelector('[data-step-action="decrement"]').disabled = newValue <= min;
            wrapper.querySelector('[data-step-action="increment"]').disabled = newValue >= max;
            
            // Llamar al manejador genérico (que ahora guarda el valor)
            await handleSettingUpdate(wrapper, stepperAction, newValue.toString());
            return;
        }

        // --- LÓGICA DE LOS BOTONES DE DOMINIO ---
        if (action) {
            const domainCard = button.closest('#admin-domain-card');
            if (!domainCard) return;

            const viewState = domainCard.querySelector('#domain-view-state');
            const addState = domainCard.querySelector('#domain-add-state');
            hideInlineError(domainCard); // Ocultar errores previos

            if (action === 'admin-domain-show-add') {
                if (viewState) viewState.style.display = 'none';
                if (addState) addState.style.display = 'block';
                domainCard.querySelector('#setting-new-domain-input')?.focus();
            }

            else if (action === 'admin-domain-cancel-add') {
                if (viewState) viewState.style.display = 'block';
                if (addState) addState.style.display = 'none';
                const input = domainCard.querySelector('#setting-new-domain-input');
                if (input) input.value = '';
            }

            else if (action === 'admin-domain-save-add') {
                const input = domainCard.querySelector('#setting-new-domain-input');
                const newDomain = input ? input.value.trim().toLowerCase() : '';

                if (!newDomain) {
                    showInlineError(domainCard, 'js.admin.domainEmpty');
                    return;
                }
                // Validación simple de formato
                if (!newDomain.match(/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i)) {
                    showInlineError(domainCard, 'js.admin.domainInvalid');
                    return;
                }

                button.classList.add('disabled-interactive'); // Deshabilitar botón
                
                const formData = new FormData();
                formData.append('action', 'admin-add-domain');
                formData.append('new_domain', newDomain);
                formData.append('csrf_token', getCsrfTokenFromPage());

                const result = await callAdminApi(formData);
                if (result.success) {
                    showAlert(getTranslation('js.admin.domainAdded'), 'success');
                    // Añadir dinámicamente
                    const list = domainCard.querySelector('.domain-card-list');
                    if (list) {
                        // Quitar el mensaje de "lista vacía" si existe
                        const emptyMsg = list.querySelector('p.component-card__description');
                        if (emptyMsg) emptyMsg.remove();

                        const newCardItem = document.createElement('div');
                        newCardItem.className = 'domain-card-item';
                        newCardItem.dataset.domain = result.domain;
                        newCardItem.innerHTML = `
                            <span class="material-symbols-rounded">language</span>
                            <span class="domain-card-text">${result.domain}</span>
                            <button type="button" class="domain-card-delete" data-action="admin-domain-delete" data-domain="${result.domain}" data-tooltip="admin.server.deleteDomainTooltip">
                                <span class="material-symbols-rounded">delete</span>
                            </button>
                        `;
                        list.appendChild(newCardItem);
                    }
                    // Volver a la vista
                    if (viewState) viewState.style.display = 'block';
                    if (addState) addState.style.display = 'none';
                    if (input) input.value = '';

                } else {
                    showInlineError(domainCard, result.message || 'js.admin.domainAddError');
                }
                button.classList.remove('disabled-interactive');
            }

            else if (action === 'admin-domain-delete') {
                const domainToRemove = button.dataset.domain;
                if (!domainToRemove) return;

                if (!confirm(`¿Estás seguro de que deseas eliminar el dominio "${domainToRemove}"?`)) {
                    return;
                }

                button.classList.add('disabled-interactive');

                const formData = new FormData();
                formData.append('action', 'admin-remove-domain');
                formData.append('domain_to_remove', domainToRemove);
                formData.append('csrf_token', getCsrfTokenFromPage());

                const result = await callAdminApi(formData);
                if (result.success) {
                    showAlert(getTranslation('js.admin.domainRemoved'), 'success');
                    // Eliminar dinámicamente
                    button.closest('.domain-card-item')?.remove();
                } else {
                    showAlert(getTranslation(result.message || 'js.admin.domainRemoveError'), 'error');
                }
                // El botón se elimina, no necesita ser reactivado
            }
        }
    });

    // 2. LISTENER DE CAMBIOS (Solo para Toggles y Textarea)
    document.body.addEventListener('change', async (e) => {
        
        const input = e.target;
        
        // Si es un stepper o un botón de dominio, los listeners de 'click' lo manejan
        if (input.closest('.component-stepper') || input.closest('#admin-domain-card')) return;

        const action = input.dataset.action;
        if (!action) return;

        // Salir si no estamos en la sección correcta
        const section = input.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        let newValue = '';

        if (input.type === 'checkbox') {
            // Es un Toggle
            newValue = input.checked ? '1' : '0';
        } else {
            // No es un input que nos interese en ESTE listener
            return;
        }

        // Llamar al manejador genérico
        await handleSettingUpdate(input, action, newValue);
    });
    
    // 3. LISTENER DE 'BLUR' (Para el Textarea de dominios)
    document.body.addEventListener('blur', async (e) => {
        const input = e.target;
        
        // Solo nos interesa el textarea de dominios
        if (input.id !== 'setting-allowed-email-domains') return;
        
        const action = input.dataset.action;
        if (!action) return;

        const section = input.closest('.section-content[data-section="admin-server-settings"]');
        if (!section) return;

        // Guardar el valor del textarea
        let newValue = input.value;
        
        // Llamar al manejador genérico
        await handleSettingUpdate(input, action, newValue);

    }, true); // Usar captura para 'blur'
}