// FILE: assets/js/modules/admin-backup-module.js
// (Versión combinada de admin-backups-manager.js y admin-restore-backup-manager.js)

import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

// --- INICIO: Lógica de API (Compartida) ---
// Definimos el endpoint de la API de backups
const API_ENDPOINT_BACKUP = `${window.projectBasePath}/api/backup_handler.php`;

/**
 * Función _post interna para llamar a la API de backups.
 */
async function callBackupApi(formData) {
    const csrfToken = window.csrfToken || '';
    formData.append('csrf_token', csrfToken);

    try {
        const response = await fetch(API_ENDPOINT_BACKUP, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            console.error('Error de red o servidor:', response.statusText);
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
            console.error('Error al parsear JSON:', jsonError);
            const errorText = await responseClone.text();
            console.error('Respuesta del servidor (no-JSON):', errorText);
            return { success: false, message: getTranslation('js.api.errorServer') + ' (Respuesta inválida)' };
        }
    } catch (error) {
        console.error('Error en la llamada fetch (Red):', error);
        return { success: false, message: getTranslation('js.api.errorConnection') };
    }
}
// --- FIN: Lógica de API ---


export function initAdminBackupModule() {

    let selectedBackupFile = null;

    // --- Funciones de admin-backups-manager ---

    function enableSelectionActions() {
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.add('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    function disableSelectionActions() {
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.remove('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = true;
        });
    }

    function clearBackupSelection() {
        const selectedCard = document.querySelector('.card-item.selected[data-backup-filename]');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableSelectionActions();
        selectedBackupFile = null;
    }

    /**
     * Muestra/Oculta un spinner en un botón de la BARRA DE HERRAMIENTAS.
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
    
    /**
     * Muestra/Oculta un spinner en un botón de COMPONENTE (página de restaurar).
     */
    function toggleComponentButtonSpinner(button, text, isLoading) {
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

    /**
     * Restablece la barra de herramientas a su estado inicial.
     */
    function resetToolbarState(buttonEl = null) {
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) return;

        if (buttonEl) {
            toggleToolbarSpinner(buttonEl, false);
        }
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        // Volver a habilitar solo los botones de la vista 'default'
        toolbarContainer.querySelectorAll('.toolbar-action-default button').forEach(btn => btn.disabled = false);
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---
        
        clearBackupSelection();
    }
    
    /**
     * Formatea bytes a un tamaño legible (KB, MB, GB)
     */
    function formatBackupSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        const kb = bytes / 1024;
        if (kb < 1024) return kb.toFixed(2) + ' KB';
        const mb = kb / 1024;
        if (mb < 1024) return mb.toFixed(2) + ' MB';
        const gb = mb / 1024;
        return gb.toFixed(2) + ' GB';
    }
    
    /**
     * Formatea un timestamp de JS a d/m/Y H:i:s
     */
    function formatBackupDate(timestamp) {
        // PHP filemtime() devuelve timestamp en segundos, no milisegundos
        const date = new Date(timestamp * 1000); 
        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();
        const h = String(date.getHours()).padStart(2, '0');
        const i = String(date.getMinutes()).padStart(2, '0');
        const s = String(date.getSeconds()).padStart(2, '0');
        return `${d}/${m}/${y} ${h}:${i}:${s}`;
    }

    /**
     * Añade la tarjeta del nuevo backup a la lista
     */
    function renderNewBackup(backupData) {
        const listContainer = document.querySelector('.card-list-container');
        if (!listContainer) return;

        // Quitar el mensaje de "no hay copias" si existe
        const noBackupCard = listContainer.querySelector('.component-card');
        if (noBackupCard && !noBackupCard.hasAttribute('data-backup-filename')) {
            noBackupCard.remove();
        }

        const newCard = document.createElement('div');
        newCard.className = 'card-item';
        newCard.dataset.backupFilename = backupData.filename;
        newCard.style = "gap: 16px; padding: 16px;"; // Estilos inline como en el PHP

        newCard.innerHTML = `
            <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                <span class="material-symbols-rounded" style="font-size: 28px;">database</span>
            </div>
            <div class="card-item-details">
                <div class="card-detail-item card-detail-item--full" style="border: none; padding: 0; background: none;">
                    <span class="card-detail-value" style="font-size: 16px; font-weight: 600;">${backupData.filename}</span>
                </div>
                <div class="card-detail-item">
                    <span class="card-detail-label" data-i18n="admin.backups.labelDate"></span>
                    <span class="card-detail-value">${formatBackupDate(backupData.created_at)}</span>
                </div>
                <div class="card-detail-item">
                    <span class="card-detail-label" data-i18n="admin.backups.labelSize"></span>
                    <span class="card-detail-value">${formatBackupSize(backupData.size)}</span>
                </div>
            </div>
        `;
        
        listContainer.prepend(newCard);
        
        const newLabelDate = newCard.querySelector('[data-i18n="admin.backups.labelDate"]');
        if (newLabelDate) newLabelDate.textContent = getTranslation('admin.backups.labelDate');
        
        const newLabelSize = newCard.querySelector('[data-i18n="admin.backups.labelSize"]');
        if (newLabelSize) newLabelSize.textContent = getTranslation('admin.backups.labelSize');
    }

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    /**
     * Lógica simplificada para MANEJAR SOLO LA CREACIÓN de backups.
     * (La eliminación y restauración ahora tienen su propia lógica de UI).
     */
    async function handleBackupCreate(action, buttonEl) {
        
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) {
            console.error("Error: No se encontró 'backup-toolbar-container'.");
            return;
        }

        // Deshabilitar todos los botones durante la acción
        toolbarContainer.querySelectorAll('button').forEach(btn => btn.disabled = true); 
        if(buttonEl) toggleToolbarSpinner(buttonEl, true);
        
        const formData = new FormData();
        formData.append('action', action); // 'create-backup'

        try {
            const result = await callBackupApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'js.admin.backups.successGeneric'), 'success');
                
                resetToolbarState(buttonEl);

                if (action === 'create-backup' && result.newBackup) {
                    renderNewBackup(result.newBackup);
                }

            } else {
                showAlert(getTranslation(result.message || 'js.admin.backups.errorGeneric'), 'error');
                resetToolbarState(buttonEl);
            }
        } catch (error) {
            showAlert(getTranslation('js.api.errorServer'), 'error');
            resetToolbarState(buttonEl);
        }
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    // --- Listener de Clics Principal (Fusionado) ---
    document.body.addEventListener('click', async function (event) {
        
        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (PÁGINA DE ELIMINACIÓN) ▼▼▼ ---
        const deleteButton = event.target.closest('#admin-delete-confirm-btn');
        if (deleteButton) {
            event.preventDefault();
            const section = deleteButton.closest('[data-section="admin-backups-delete-confirm"]');
            if (!section) return;

            const filenameInput = section.querySelector('#delete-filename');
            const filename = filenameInput ? filenameInput.value : null;

            if (!filename) {
                showAlert(getTranslation('js.admin.restore.errorNoFile'), 'error'); // Reutilizamos clave
                return;
            }

            toggleComponentButtonSpinner(deleteButton, getTranslation('admin.delete.confirmButton'), true);

            const formData = new FormData();
            formData.append('action', 'delete-backup');
            formData.append('filename', filename);

            try {
                const result = await callBackupApi(formData);

                if (result.success) {
                    // Usar la nueva clave de traducción
                    showAlert(getTranslation('js.admin.backups.successDeleteRedirect'
                         || 'Copia eliminada. Serás redirigido.'), 'success');
                    
                    setTimeout(() => {
                        const link = document.createElement('a');
                        // Redirigir a la lista de backups
                        link.href = window.projectBasePath + '/admin/manage-backups';
                        link.setAttribute('data-nav-js', 'true');
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                    }, 2000);

                } else {
                    showAlert(getTranslation(result.message || 'js.admin.backups.errorGeneric'), 'error');
                    toggleComponentButtonSpinner(deleteButton, getTranslation('admin.delete.confirmButton'), false);
                }
            } catch (error) {
                showAlert(getTranslation('js.api.errorServer'), 'error');
                toggleComponentButtonSpinner(deleteButton, getTranslation('admin.delete.confirmButton'), false);
            }
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---


        // --- INICIO: Lógica de admin-restore-backup-manager ---
        const restoreButton = event.target.closest('#admin-restore-confirm-btn');
        if (restoreButton) {
            event.preventDefault();
            const section = restoreButton.closest('[data-section="admin-backups-restore-confirm"]');
            if (!section) return;

            const filenameInput = section.querySelector('#restore-filename');
            const filename = filenameInput ? filenameInput.value : null;

            if (!filename) {
                showAlert(getTranslation('js.admin.restore.errorNoFile'), 'error');
                return;
            }

            toggleComponentButtonSpinner(restoreButton, getTranslation('admin.restore.restoring'), true);

            const formData = new FormData();
            formData.append('action', 'restore-backup');
            formData.append('filename', filename);

            try {
                const result = await callBackupApi(formData);

                if (result.success) {
                    showAlert(getTranslation(result.message || 'admin.backups.successRestore'), 'success');
                    
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
                    toggleComponentButtonSpinner(restoreButton, getTranslation('admin.restore.confirmButton'), false);
                }
            } catch (error) {
                showAlert(getTranslation('js.api.errorServer'), 'error');
                toggleComponentButtonSpinner(restoreButton, getTranslation('admin.restore.confirmButton'), false);
            }
            return;
        }
        // --- FIN: Lógica de admin-restore-backup-manager ---


        // --- INICIO: Lógica de admin-backups-manager ---
        
        const backupSection = event.target.closest('[data-section="admin-backups"]');
        
        const backupCard = event.target.closest('.card-item[data-backup-filename]');
        if (backupCard && backupSection) {
            event.preventDefault();
            const filename = backupCard.dataset.backupFilename;
            
            if (selectedBackupFile === filename) {
                clearBackupSelection();
            } else {
                const oldSelected = document.querySelector('.card-item.selected[data-backup-filename]');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                backupCard.classList.add('selected');
                selectedBackupFile = filename;
                enableSelectionActions();
            }
            return;
        }

        const button = event.target.closest('button[data-action]');
        if (!button) {
            if (backupSection && !event.target.closest('[data-module].active')) {
                clearBackupSelection();
            }
            return;
        }
        
        if (!backupSection) {
             if (selectedBackupFile) {
                clearBackupSelection();
             }
             return;
        }
        
        const action = button.getAttribute('data-action');
        hideTooltip();

        switch (action) {
            case 'admin-backup-create':
                if (!confirm(getTranslation('admin.backups.confirmCreate'))) {
                    return;
                }
                // --- ▼▼▼ MODIFICACIÓN ▼▼▼ ---
                await handleBackupCreate('create-backup', button);
                // --- ▲▲▲ MODIFICACIÓN ▲▲▲ ---
                break;

            case 'admin-backup-restore':
                if (!selectedBackupFile) {
                    showAlert(getTranslation('admin.backups.errorNoSelection'), 'error');
                    return;
                }
                
                const linkUrlRestore = window.projectBasePath + '/admin/manage-backups?file=' + encodeURIComponent(selectedBackupFile);
                
                const linkRestore = document.createElement('a');
                linkRestore.href = linkUrlRestore;
                linkRestore.setAttribute('data-nav-js', 'true'); 
                document.body.appendChild(linkRestore);
                linkRestore.click();
                linkRestore.remove();
            
                clearBackupSelection(); 
                break;
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (CAMBIAR A NAVEGACIÓN) ▼▼▼ ---
            case 'admin-backup-delete':
                if (!selectedBackupFile) {
                    showAlert(getTranslation('admin.backups.errorNoSelection'), 'error');
                    return;
                }
                
                // Ya no hay confirm()
                
                // Navegar a la nueva página de confirmación
                const linkUrlDelete = window.projectBasePath + '/admin/manage-backups?delete=' + encodeURIComponent(selectedBackupFile);
                
                const linkDelete = document.createElement('a');
                linkDelete.href = linkUrlDelete;
                linkDelete.setAttribute('data-nav-js', 'true'); 
                document.body.appendChild(linkDelete);
                linkDelete.click();
                linkDelete.remove();

                clearBackupSelection();
                break;
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            // --- ▼▼▼ INICIO DE NUEVA LÓGICA ▼▼▼ ---
            case 'admin-backup-download':
                if (!selectedBackupFile) {
                    showAlert(getTranslation('admin.backups.errorNoSelection'), 'error');
                    return;
                }

                try {
                    const csrfToken = window.csrfToken || '';
                    
                    // Crear un formulario temporal para enviar la solicitud POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = API_ENDPOINT_BACKUP; // Endpoint definido al inicio del archivo
                    form.style.display = 'none';

                    // Añadir la acción
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'download-backup';
                    form.appendChild(actionInput);

                    // Añadir el token CSRF
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);

                    // Añadir el nombre del archivo
                    const fileInput = document.createElement('input');
                    fileInput.type = 'hidden';
                    fileInput.name = 'filename';
                    fileInput.value = selectedBackupFile;
                    form.appendChild(fileInput);

                    // Enviar el formulario
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                    
                    // Deseleccionar después de iniciar la descarga
                    clearBackupSelection();

                } catch (error) {
                    console.error('Error al crear el formulario de descarga:', error);
                    showAlert(getTranslation('admin.backups.errorDownload'), 'error');
                }
                break;
            // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---

            case 'admin-backup-clear-selection':
                clearBackupSelection();
                break;
        }
        // --- FIN: Lógica de admin-backups-manager ---
    });

    // --- Listeners Globales para deseleccionar (de admin-backups-manager) ---
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            clearBackupSelection();
        }
    });
}