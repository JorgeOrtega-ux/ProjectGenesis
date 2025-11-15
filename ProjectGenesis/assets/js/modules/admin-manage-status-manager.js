import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

/**
 * Muestra u oculta un contenedor de detalles basado en una condición.
 * @param {HTMLElement} container El elemento contenedor a mostrar/ocultar.
 * @param {boolean} show `true` para mostrar, `false` para ocultar.
 */
function toggleDetailContainer(container, show) {
    if (container) {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (style -> class) ▼▼▼ ---
        if (show) {
            container.classList.add('active');
            container.classList.remove('disabled');
        } else {
            container.classList.add('disabled');
            container.classList.remove('active');
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }
}

/**
 * Muestra u oculta la sección de restricciones completa.
 * @param {boolean} show `true` para mostrar, `false` para ocultar.
 */
function toggleRestrictionsSection(show) {
    const restrictionsSection = document.getElementById('admin-restrictions-section');
    if (restrictionsSection) {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (style -> class) ▼▼▼ ---
        if (show) {
            restrictionsSection.classList.add('active');
            restrictionsSection.classList.remove('disabled');
        } else {
            restrictionsSection.classList.add('disabled');
            restrictionsSection.classList.remove('active');
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }
}

/**
 * Muestra un error en línea en la tarjeta de acciones.
 * @param {string} messageKey La clave de traducción i18n
 */
function showStatusError(messageKey) {
    const errorDiv = document.getElementById('admin-manage-status-error');
    if (!errorDiv) return;
    
    errorDiv.textContent = getTranslation(messageKey);
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (style -> class) ▼▼▼ ---
    errorDiv.classList.add('active');
    errorDiv.classList.remove('disabled');
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
}

/**
 * Oculta cualquier error en línea en las tarjetas.
 */
function hideStatusErrors() {
    const errorDiv = document.getElementById('admin-manage-status-error');
    if (errorDiv) {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (style -> class) ▼▼▼ ---
        errorDiv.classList.add('disabled');
        errorDiv.classList.remove('active');
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }
}

/**
 * Muestra/oculta el spinner en un botón.
 * @param {HTMLElement} button El elemento del botón.
 * @param {boolean} isLoading `true` para mostrar spinner, `false` para restaurar.
 */
function toggleSpinner(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff;"></span>`;
    } else {
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }
}

/**
 * Inicializa los listeners para la página de gestión de estado.
 */
export function initAdminManageStatusManager() {
    
    // --- LISTENER DE CAMBIOS (PARA RADIOS y TOGGLES) ---
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section="admin-manage-status"]');
        if (!section) return;

        // --- (Listener de radio/select eliminado, movido a 'click') ---
        
        // 2. Lógica para los toggles de restricción
        if (e.target.classList.contains('admin-restriction-toggle')) {
            const isChecked = e.target.checked;
            const baseId = e.target.id;
            
            // Mostrar/ocultar los detalles de la restricción
            toggleDetailContainer(
                document.getElementById(`${baseId}-details`),
                isChecked
            );
        }
    });

    // --- LISTENER DE CLICS (PARA BOTONES Y EL NUEVO DROPDOWN) ---
    document.body.addEventListener('click', async (e) => {
        const section = e.target.closest('[data-section="admin-manage-status"]');
        if (!section) return;

        // --- INICIO: LÓGICA DEL NUEVO STEPPER DE DÍAS ---
        const stepperButton = e.target.closest('.component-stepper button[data-step-action]');
        if (stepperButton) {
            e.preventDefault();
            e.stopPropagation(); // Evitar que el clic se propague
            const wrapper = stepperButton.closest('.component-stepper');
            if (!wrapper || wrapper.classList.contains('disabled-interactive')) return;
            
            const stepAction = stepperButton.dataset.stepAction;
            const valueDisplay = wrapper.querySelector('.stepper-value');
            const min = parseInt(wrapper.dataset.min, 10);
            const max = parseInt(wrapper.dataset.max, 10);
            
            const step1 = parseInt(wrapper.dataset.step1 || '1', 10);
            const step10 = parseInt(wrapper.dataset.step10 || '10');

            let currentValue = parseInt(wrapper.dataset.currentValue, 10);
            let newValue = currentValue;
            let stepAmount = 0;

            switch (stepAction) {
                case 'increment-1': stepAmount = step1; break;
                case 'increment-10': stepAmount = step10; break;
                case 'decrement-1': stepAmount = -step1; break;
                case 'decrement-10': stepAmount = -step10; break;
            }
            
            newValue = currentValue + stepAmount;
            
            if (!isNaN(min) && newValue < min) newValue = min;
            if (!isNaN(max) && newValue > max) newValue = max;
            
            if (newValue === currentValue) return;

            if (valueDisplay) valueDisplay.textContent = newValue;
            wrapper.dataset.currentValue = newValue;

            // Actualizar estado de botones
            wrapper.querySelector('[data-step-action="decrement-10"]').disabled = newValue < min + step10;
            wrapper.querySelector('[data-step-action="decrement-1"]').disabled = newValue <= min;
            wrapper.querySelector('[data-step-action="increment-1"]').disabled = newValue >= max;
            wrapper.querySelector('[data-step-action="increment-10"]').disabled = newValue > max - step10;
            
            return; // Importante para que no siga al 'saveBtn'
        }
        // --- FIN: LÓGICA DEL STEPPER ---

        // --- Lógica del Dropdown de Estado ---
        const statusTrigger = e.target.closest('[data-action="toggleModuleAdminStatusSelect"]');
        if (statusTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleAdminStatusSelect"]');
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        const statusLink = e.target.closest('[data-module="moduleAdminStatusSelect"] .menu-link');
        if (statusLink) {
            e.preventDefault();
            e.stopPropagation();
            
            const newValue = statusLink.dataset.value;
            const hiddenInput = document.getElementById('admin-manage-status-value');
            if (hiddenInput) {
                hiddenInput.value = newValue;
            }

            // Actualizar el botón trigger
            const trigger = document.querySelector('[data-action="toggleModuleAdminStatusSelect"]');
            const newTextKey = statusLink.querySelector('.menu-link-text span').dataset.i18n;
            const newIconName = statusLink.querySelector('.menu-link-icon span').textContent;
            
            if (trigger) {
                trigger.querySelector('.trigger-select-icon span').textContent = newIconName;
                const textEl = trigger.querySelector('.trigger-select-text span');
                textEl.textContent = getTranslation(newTextKey);
                textEl.dataset.i18n = newTextKey;
            }

            // Actualizar clases 'active' en el popover
            statusLink.closest('.menu-list').querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                link.querySelector('.menu-link-check-icon').innerHTML = '';
            });
            statusLink.classList.add('active');
            statusLink.querySelector('.menu-link-check-icon').innerHTML = '<span class="material-symbols-rounded">check</span>';

            // Mostrar/ocultar las secciones dependientes
            toggleDetailContainer(document.getElementById('admin-status-suspension-details'), newValue === 'suspended');
            toggleRestrictionsSection(newValue === 'active');

            deactivateAllModules();
            return;
        }
        // --- FIN: LÓGICA DEL DROPDOWN DE ESTADO ---
        
        // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE CLIC (POPOVERS DE EXPIRACIÓN) ▼▼▼ ---
        const expiryTrigger = e.target.closest('[data-action^="toggleModule-"][data-action$="-expiry"]');
        if (expiryTrigger) {
            e.preventDefault();
            e.stopPropagation();
            const moduleName = expiryTrigger.dataset.action.replace('toggleModule-', 'module-');
            const module = document.querySelector(`[data-module="${moduleName}"]`);
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        const expiryLink = e.target.closest('[data-module$="-expiry"] .menu-link');
        if (expiryLink) {
            e.preventDefault();
            e.stopPropagation();
            
            const newValue = expiryLink.dataset.value; // 'permanent' o 'temporary'
            const module = expiryLink.closest('[data-module$="-expiry"]');
            const moduleName = module.dataset.module; // ej: 'module-status-expiry'
            
            // Encontrar el ID base (ej: 'status' o 'publish')
            const baseId = moduleName.replace('module-', '').replace('-expiry', ''); // 'status' o 'publish'

            // Actualizar el botón trigger
            const trigger = document.querySelector(`[data-action="toggleModule-${baseId}-expiry"]`);
            const newTextKey = expiryLink.querySelector('.menu-link-text span').dataset.i18n;
            const newIconName = expiryLink.querySelector('.menu-link-icon span').textContent;
            
            if (trigger) {
                trigger.querySelector('.trigger-select-icon span').textContent = newIconName;
                const textEl = trigger.querySelector('.trigger-select-text span');
                textEl.textContent = getTranslation(newTextKey);
                textEl.dataset.i18n = newTextKey;
            }

            // Actualizar 'active' en el popover
            expiryLink.closest('.menu-list').querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                link.querySelector('.menu-link-check-icon').innerHTML = '';
            });
            expiryLink.classList.add('active');
            expiryLink.querySelector('.menu-link-check-icon').innerHTML = '<span class="material-symbols-rounded">check</span>';

            // Mostrar/ocultar el stepper de días
            const stepperContainerId = (baseId === 'status') 
                ? `admin-status-expires-at-container` 
                : `admin-restrict-${baseId}-expires-at-container`;
            
            toggleDetailContainer(
                document.getElementById(stepperContainerId),
                newValue === 'temporary'
            );

            deactivateAllModules();
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA DE CLIC ---


        // 4. Lógica para el botón Guardar (Modificada)
        const saveBtn = e.target.closest('#admin-save-status-btn');
        if (saveBtn) {
            
            hideStatusErrors();
            toggleSpinner(saveBtn, true);

            const targetUserId = document.getElementById('admin-manage-status-user-id')?.value;
            if (!targetUserId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
                toggleSpinner(saveBtn, false);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'admin-update-restrictions');
            formData.append('target_user_id', targetUserId);
            
            // --- Leer el valor desde el input oculto ---
            const generalStatus = document.getElementById('admin-manage-status-value')?.value || 'active';
            formData.append('general_status', generalStatus);

            // --- Recopilar datos de expiración de estado ---
            if (generalStatus === 'suspended') {
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Leer Popover) ▼▼▼ ---
                const expiryType = section.querySelector('[data-module="module-status-expiry"] .menu-link.active')?.dataset.value || 'permanent';
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                if (expiryType === 'temporary') {
                    const stepper = section.querySelector('#admin-status-expires-stepper');
                    const days = stepper ? stepper.dataset.currentValue : '1';
                    formData.append('status_expires_in_days', days);
                }
            }
            
            const restrictions = ['publish', 'comment', 'message', 'social'];
            
            restrictions.forEach(key => {
                const toggle = section.querySelector(`#admin-restrict-${key}`);
                if (toggle && toggle.checked) {
                    formData.append(`restrict_${key}`, 'true');
                    
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Leer Popover) ▼▼▼ ---
                    const expiryType = section.querySelector(`[data-module="module-${key}-expiry"] .menu-link.active`)?.dataset.value || 'permanent';
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    
                    if (expiryType === 'temporary') {
                        const stepper = section.querySelector(`#admin-restrict-${key}-expires-stepper`);
                        const days = stepper ? stepper.dataset.currentValue : '1';
                        formData.append(`restrict_${key}_expires_in_days`, days);
                    }
                } else {
                    formData.append(`restrict_${key}`, 'false');
                }
            });
            
            
            // --- Llamada a la API (Sin cambios) ---
            try {
                const result = await callAdminApi(formData);
                
                if (result.success) {
                    showAlert(getTranslation(result.message || 'js.admin.successStatus'), 'success');
                    // Disparar una navegación de vuelta a la lista de usuarios
                    const backButton = document.querySelector('[data-action="toggleSectionAdminManageUsers"]');
                    if (backButton) {
                        backButton.click();
                    }
                } else {
                    showStatusError(result.message || 'js.api.errorServer');
                }
                
            } catch (error) {
                console.error('Error al guardar estado:', error);
                showStatusError('js.api.errorConnection');
            } finally {
                toggleSpinner(saveBtn, false);
            }
        }
    });
}