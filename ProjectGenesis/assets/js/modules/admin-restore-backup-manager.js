// FILE: assets/js/modules/admin-restore-backup-manager.js

import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';

// --- INICIO: Lógica de API (copiada de admin-backups-manager.js) ---
const API_ENDPOINT_BACKUP = `${window.projectBasePath}/api/backup_handler.php`;

async function callBackupApi(formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch(API_ENDPOINT_BACKUP, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            return { success: false, message: getTranslation('js.api.errorServer') };
        }

        const responseClone = response.clone();
        try {
            const result = await response.json();
            if (result.success === false && result.message && result.message.includes('Error de seguridad')) {
                window.showAlert(getTranslation('js.api.errorSecurity'), 'error');
                setTimeout(() => location.reload(), 2000);
            }
            return result;
        } catch (jsonError) {
            const errorText = await responseClone.text();
            return { success: false, message: getTranslation('js.api.errorServer') + ' (Respuesta inválida)' };
        }
    } catch (error) {
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}
// --- FIN: Lógica de API ---

/**
 * Muestra/Oculta un spinner en un botón de componente.
 */
function toggleButtonSpinner(button, text, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    if (isLoading) {
        button.dataset.originalText = button.textContent;
        const spinnerClass = 'logout-spinner'; 
        let spinnerStyle = 'width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;';
        
        if(button.classList.contains('danger')) {
             spinnerStyle += ' border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;';
        }

        button.innerHTML = `<span class="${spinnerClass}" style="${spinnerStyle}"></span>`;
    } else {
        button.innerHTML = button.dataset.originalText || text;
    }
}


export function initAdminRestoreBackupManager() {

    document.body.addEventListener('click', async function (event) {
        
        // 1. Verificar si estamos en la sección de restaurar backup
        const button = event.target.closest('#admin-restore-confirm-btn');
        if (!button) {
            return;
        }

        // 2. Prevenir acción y obtener datos
        event.preventDefault();
        const section = button.closest('[data-section="admin-restore-backup"]');
        if (!section) return;

        const filenameInput = section.querySelector('#restore-filename');
        const filename = filenameInput ? filenameInput.value : null;

        if (!filename) {
            showAlert(getTranslation('js.admin.restore.errorNoFile'), 'error');
            return;
        }

        // 3. Mostrar spinner
        toggleButtonSpinner(button, getTranslation('admin.restore.restoring'), true);

        // 4. Preparar y llamar a la API
        const formData = new FormData();
        formData.append('action', 'restore-backup');
        formData.append('filename', filename);

        try {
            const result = await callBackupApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'admin.backups.successRestore'), 'success');
                
                // Redirigir a la página de gestión de usuarios (o dashboard) tras el éxito
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/admin/manage-users';
                    link.setAttribute('data-nav-js', 'true');
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, 2000);

            } else {
                showAlert(getTranslation(result.message || 'js.admin.backups.errorGeneric'), 'error');
                toggleButtonSpinner(button, getTranslation('admin.restore.confirmButton'), false);
            }
        } catch (error) {
            showAlert(getTranslation('js.api.errorServer'), 'error');
            toggleButtonSpinner(button, getTranslation('admin.restore.confirmButton'), false);
        }
    });
}