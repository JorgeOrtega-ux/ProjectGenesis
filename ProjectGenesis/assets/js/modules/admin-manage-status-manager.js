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
        if (show) {
            container.classList.add('active');
            container.classList.remove('disabled');
        } else {
            container.classList.add('disabled');
            container.classList.remove('active');
        }
    }
}

/**
 * Muestra u oculta la sección de restricciones completa.
 * @param {boolean} show `true` para mostrar, `false` para ocultar.
 */
function toggleRestrictionsSection(show) {
    const restrictionsSection = document.getElementById('admin-restrictions-section');
    if (restrictionsSection) {
        if (show) {
            restrictionsSection.classList.add('active');
            restrictionsSection.classList.remove('disabled');
        } else {
            restrictionsSection.classList.add('disabled');
            restrictionsSection.classList.remove('active');
        }
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
    errorDiv.classList.add('active');
    errorDiv.classList.remove('disabled');
}

/**
 * Oculta cualquier error en línea en las tarjetas.
 */
function hideStatusErrors() {
    const errorDiv = document.getElementById('admin-manage-status-error');
    if (errorDiv) {
        errorDiv.classList.add('disabled');
        errorDiv.classList.remove('active');
    }
}

/**
 * Muestra/oculta el spinner en un botón.
 * @param {HTMLElement} button El elemento del botón.
 * @param {boolean} isLoading `true` para mostrar spinner, `false` para restaurar.
 */
// --- ▼▼▼ FUNCIÓN REEMPLAZADA ▼▼▼ ---
/**
 * Muestra/oculta el spinner en un BOTÓN DE BARRA DE HERRAMIENTAS (icono).
 * @param {HTMLElement} button El elemento del botón.
 * @param {boolean} isLoading `true` para mostrar spinner, `false` para restaurar.
 */
function toggleToolbarSpinner(button, isLoading) {
    if (!button) return;
    const iconSpan = button.querySelector('.material-symbols-rounded');
    if (!iconSpan) return;

    if (isLoading) {
        button.disabled = true;
        button.dataset.originalIcon = iconSpan.textContent;
        iconSpan.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;
    } else {
        button.disabled = false;
        if (button.dataset.originalIcon) {
            iconSpan.innerHTML = button.dataset.originalIcon;
        }
    }
}
// --- ▲▲▲ FUNCIÓN REEMPLAZADA ▲▲▲ ---

/**
 * Inicializa los listeners para la página de gestión de estado.
 */
export function initAdminManageStatusManager() {
    
    // --- LISTENER DE CAMBIOS (PARA RADIOS y TOGGLES) ---
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section="admin-manage-status"]');
        if (!section) return;

        // 2. Lógica para los toggles de restricción
        if (e.target.classList.contains('admin-restriction-toggle')) {
            // Ya no hay sub-secciones que mostrar/ocultar
        }
    });

    // --- LISTENER DE CLICS (PARA BOTONES Y EL NUEVO DROPDOWN) ---
    document.body.addEventListener('click', async (e) => {
        const section = e.target.closest('[data-section="admin-manage-status"]');
        if (!section) return;

        // --- Lógica de Stepper ELIMINADA ---

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
            // --- Lógica de 'admin-status-suspension-details' ELIMINADA ---
            toggleRestrictionsSection(newValue === 'active');

            deactivateAllModules();
            return;
        }
        // --- FIN: LÓGICA DEL DROPDOWN DE ESTADO ---
        
        // --- Lógica de Popovers de Expiración ELIMINADA ---

        // 4. Lógica para el botón Guardar (Modificada)
        const saveBtn = e.target.closest('#admin-save-status-btn');
        if (saveBtn) {
            
            hideStatusErrors();
            // --- ▼▼▼ LLAMADA A FUNCIÓN MODIFICADA ▼▼▼ ---
            toggleToolbarSpinner(saveBtn, true);
            // --- ▲▲▲ LLAMADA A FUNCIÓN MODIFICADA ▲▲▲ ---

            const targetUserId = document.getElementById('admin-manage-status-user-id')?.value;
            if (!targetUserId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
                // --- ▼▼▼ LLAMADA A FUNCIÓN MODIFICADA ▼▼▼ ---
                toggleToolbarSpinner(saveBtn, false);
                // --- ▲▲▲ LLAMADA A FUNCIÓN MODIFICADA ▲▲▲ ---
                return;
            }

            const formData = new FormData();
            formData.append('action', 'admin-update-restrictions');
            formData.append('target_user_id', targetUserId);
            
            // --- Leer el valor desde el input oculto ---
            const generalStatus = document.getElementById('admin-manage-status-value')?.value || 'active';
            formData.append('general_status', generalStatus);

            // --- Recopilar datos de expiración de estado ELIMINADO ---
            
            const restrictions = ['publish', 'comment', 'message', 'social'];
            
            restrictions.forEach(key => {
                const toggle = section.querySelector(`#admin-restrict-${key}`);
                if (toggle && toggle.checked) {
                    formData.append(`restrict_${key}`, 'true');
                    // --- Lógica de expiración de restricción ELIMINADA ---
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
                // --- ▼▼▼ LLAMADA A FUNCIÓN MODIFICADA ▼▼▼ ---
                toggleToolbarSpinner(saveBtn, false);
                // --- ▲▲▲ LLAMADA A FUNCIÓN MODIFICADA ▲▲▲ ---
            }
        }
    });
}