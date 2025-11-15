import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';

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
        restrictionsSection.style.display = show ? 'block' : 'none';
    }
}

/**
 * Muestra un error en línea debajo de un componente de tarjeta.
 * @param {string} cardId El ID del component-card
 * @param {string} messageKey La clave de traducción i18n
 */
function showStatusError(cardId, messageKey) {
    const cardElement = document.getElementById(cardId);
    if (!cardElement) return;
    
    // Ocultar errores previos
    const oldError = cardElement.querySelector('.component-card__error');
    if (oldError) oldError.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'component-card__error active';
    errorDiv.style.width = '100%';
    errorDiv.style.marginTop = '16px';
    errorDiv.textContent = getTranslation(messageKey);
    cardElement.appendChild(errorDiv);
}

/**
 * Oculta cualquier error en línea en las tarjetas.
 */
function hideStatusErrors() {
    document.querySelectorAll('[data-section="admin-manage-status"] .component-card__error').forEach(el => {
        el.remove();
    });
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
    
    document.body.addEventListener('change', (e) => {
        const section = e.target.closest('[data-section="admin-manage-status"]');
        if (!section) return;

        // 1. Lógica para el Estado General (Activo/Suspendido/Eliminado)
        if (e.target.name === 'general_status') {
            const isSuspended = e.target.value === 'suspended';
            const isActive = e.target.value === 'active';
            
            // Mostrar/ocultar detalles de expiración de suspensión
            toggleDetailContainer(
                document.getElementById('admin-status-suspension-details'), 
                isSuspended
            );
            
            // Mostrar/ocultar toda la sección de restricciones
            toggleRestrictionsSection(isActive);
        }

        // 2. Lógica para los radios "Permanente" / "Temporal"
        if (e.target.name.endsWith('_expiry_type')) {
            const isTemporary = e.target.value === 'temporary';
            const baseId = e.target.name.replace('_expiry_type', '');
            
            // Mostrar/ocultar el input de fecha
            toggleDetailContainer(
                document.getElementById(`${baseId}-expires-at-container`), 
                isTemporary
            );
        }
        
        // 3. Lógica para los toggles de restricción
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

    // 4. Lógica para el botón Guardar
    document.body.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#admin-save-status-btn');
        if (!saveBtn) return;
        
        const section = saveBtn.closest('[data-section="admin-manage-status"]');
        if (!section) return;
        
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
        
        // --- Recolectar Estado General ---
        const generalStatus = section.querySelector('input[name="general_status"]:checked')?.value || 'active';
        formData.append('general_status', generalStatus);

        if (generalStatus === 'suspended') {
            const expiryType = section.querySelector('input[name="status_expiry_type"]:checked')?.value || 'permanent';
            if (expiryType === 'temporary') {
                const expiresAt = section.querySelector('#admin-status-expires-at')?.value || '';
                formData.append('status_expires_at', expiresAt);
            }
        }
        
        // --- Recolectar Restricciones (solo si el estado es 'active') ---
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
        
        // --- Llamada a la API ---
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
                showStatusError('admin-restrictions-section', result.message || 'js.api.errorServer');
            }
            
        } catch (error) {
            console.error('Error al guardar estado:', error);
            showStatusError('admin-restrictions-section', 'js.api.errorConnection');
        } finally {
            toggleSpinner(saveBtn, false);
        }
    });
}