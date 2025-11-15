import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { deactivateAllModules } from '../app/main-controller.js'; // Importar para cerrar popover

/**
 * Muestra u oculta un contenedor de detalles basado en una condición.
 * @param {HTMLElement} container El elemento contenedor a mostrar/ocultar.
 * @param {boolean} show `true` para mostrar, `false` para ocultar.
 */
function toggleDetailContainer(container, show) {
    if (container) {
        container.style.display = show ? 'block' : 'none';
    }
}

/**
 * Muestra u oculta la sección de restricciones completa.
 * @param {boolean} show `true` para mostrar, `false` para ocultar.
 */
function toggleRestrictionsSection(show) {
    const restrictionsSection = document.getElementById('admin-restrictions-section');
    if (restrictionsSection) {
        // Usamos flex porque el contenedor es un flex column
        restrictionsSection.style.display = show ? 'flex' : 'none';
    }
}

/**
 * Muestra un error en línea en la tarjeta de acciones.
 * @param {string} messageKey La clave de traducción i18n
 */
function showStatusError(messageKey) {
    // Apuntar al div de error específico en la tarjeta de "Guardar"
    const errorDiv = document.getElementById('admin-manage-status-error');
    if (!errorDiv) return;
    
    errorDiv.textContent = getTranslation(messageKey);
    errorDiv.style.display = 'block'; // Mostrarlo
}

/**
 * Oculta cualquier error en línea en las tarjetas.
 */
function hideStatusErrors() {
    const errorDiv = document.getElementById('admin-manage-status-error');
    if (errorDiv) {
        errorDiv.style.display = 'none'; // Ocultarlo
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

        // 1. Lógica para los radios "Permanente" / "Temporal" (Sin cambios)
        if (e.target.name.endsWith('_expiry_type')) {
            const isTemporary = e.target.value === 'temporary';
            const baseId = e.target.name.replace('_expiry_type', '');
            
            // Mostrar/ocultar el input de fecha
            toggleDetailContainer(
                document.getElementById(`${baseId}-expires-at-container`), 
                isTemporary
            );
        }
        
        // 2. Lógica para los toggles de restricción (Sin cambios)
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

        // --- INICIO: LÓGICA DEL NUEVO DROPDOWN DE ESTADO ---
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
        // --- FIN: LÓGICA DEL NUEVO DROPDOWN DE ESTADO ---


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
            
            // --- INICIO: CAMBIO EN LECTURA DE VALOR ---
            // Leer el valor desde el input oculto que actualiza el dropdown
            const generalStatus = document.getElementById('admin-manage-status-value')?.value || 'active';
            formData.append('general_status', generalStatus);
            // --- FIN: CAMBIO EN LECTURA DE VALOR ---

            if (generalStatus === 'suspended') {
                const expiryType = section.querySelector('input[name="status_expiry_type"]:checked')?.value || 'permanent';
                if (expiryType === 'temporary') {
                    const expiresAt = section.querySelector('#admin-status-expires-at')?.value || '';
                    formData.append('status_expires_at', expiresAt);
                }
            }
            
            if (generalStatus === 'active') {
                const restrictions = ['publish', 'comment', 'message', 'social'];
                
                restrictions.forEach(key => {
                    const toggle = section.querySelector(`#admin-restrict-${key}`);
                    if (toggle && toggle.checked) {
                        formData.append(`restrict_${key}`, 'true');
                        
                        const expiryType = section.querySelector(`input[name="${key}_expiry_type"]:checked`)?.value || 'permanent';
                        if (expiryType === 'temporary') {
                            const expiresAt = section.querySelector(`#admin-restrict-${key}-expires-at`)?.value || '';
                            formData.append(`restrict_${key}_expires_at`, expiresAt);
                        }
                    } else {
                        formData.append(`restrict_${key}`, 'false');
                    }
                });
            }
            
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