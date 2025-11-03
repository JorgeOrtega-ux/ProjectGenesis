// FILE: assets/js/modules/admin-backups-manager.js
// (CÓDIGO MODIFICADO)

import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

// --- INICIO: Lógica de API (copiada de api-service.js para ser autocontenido) ---
// Definimos el nuevo endpoint de la API de backups
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


export function initAdminBackupsManager() {

    let selectedBackupFile = null;

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
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        const selectedCard = document.querySelector('.card-item.selected[data-backup-filename]');
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableSelectionActions();
        selectedBackupFile = null;
    }

    /**
     * Muestra/Oculta un spinner en un botón de la barra de herramientas.
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
     * Restablece la barra de herramientas a su estado inicial.
     */
    function resetToolbarState(buttonEl = null) {
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) return;

        if (buttonEl) {
            toggleToolbarSpinner(buttonEl, false);
        }
        
        toolbarContainer.querySelectorAll('.toolbar-action-default button').forEach(btn => btn.disabled = false);
        
        clearBackupSelection();
    }

    // --- ▼▼▼ INICIO DE NUEVAS FUNCIONES DE RENDERIZADO ▼▼▼ ---
    
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
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        const listContainer = document.querySelector('.card-list-container');
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (!listContainer) return;

        // Quitar el mensaje de "no hay copias" si existe
        const noBackupCard = listContainer.querySelector('.component-card');
        if (noBackupCard && !noBackupCard.hasAttribute('data-backup-filename')) {
            noBackupCard.remove();
        }

        const newCard = document.createElement('div');
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        newCard.className = 'card-item';
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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

    /**
     * Elimina la tarjeta del backup de la lista
     */
    function removeDeletedBackup(filename) {
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        const cardToRemove = document.querySelector(`.card-item[data-backup-filename="${filename}"]`);
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (cardToRemove) {
            cardToRemove.remove();
        }

        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        const listContainer = document.querySelector('.card-list-container');
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (listContainer && listContainer.children.length === 0) {
            listContainer.innerHTML = `
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">database</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="admin.backups.noBackupsTitle"></h2>
                            <p class="component-card__description" data-i18n="admin.backups.noBackupsDesc"></p>
                        </div>
                    </div>
                </div>`;
            const title = listContainer.querySelector('[data-i18n="admin.backups.noBackupsTitle"]');
            if (title) title.textContent = getTranslation('admin.backups.noBackupsTitle');
            const desc = listContainer.querySelector('[data-i18n="admin.backups.noBackupsDesc"]');
            if (desc) desc.textContent = getTranslation('admin.backups.noBackupsDesc');
        }
    }
    // --- ▲▲▲ FIN DE NUEVAS FUNCIONES DE RENDERIZADO ---


    /**
     * Maneja las acciones de la API de backup.
     */
    async function handleBackupAction(action, filename = null, buttonEl = null) {
        
        const toolbarContainer = document.getElementById('backup-toolbar-container');
        if (!toolbarContainer) {
            console.error("Error: No se encontró 'backup-toolbar-container'.");
            return;
        }

        toolbarContainer.querySelectorAll('button').forEach(btn => btn.disabled = true); 
        if(buttonEl) toggleToolbarSpinner(buttonEl, true);
        
        const formData = new FormData();
        formData.append('action', action);
        if (filename) {
            formData.append('filename', filename);
        }

        try {
            const result = await callBackupApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'js.admin.backups.successGeneric'), 'success');
                
                // --- ▼▼▼ CORRECCIÓN: Actualizar UI dinámicamente ▼▼▼ ---
                resetToolbarState(buttonEl);

                if (action === 'create-backup' && result.newBackup) {
                    renderNewBackup(result.newBackup);
                } else if (action === 'delete-backup' && result.deletedFilename) {
                    removeDeletedBackup(result.deletedFilename);
                } else if (action === 'restore-backup') {
                    // La restauración SÍ necesita recargar todo, por si acaso.
                    showAlert('Restauración completada. Recargando...', 'success');
                    setTimeout(() => location.reload(), 1500); 
                }
                // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

            } else {
                showAlert(getTranslation(result.message || 'js.admin.backups.errorGeneric'), 'error');
                resetToolbarState(buttonEl);
            }
        } catch (error) {
            showAlert(getTranslation('js.api.errorServer'), 'error');
            resetToolbarState(buttonEl);
        }
    }


    // --- Listener de Clics Principal ---
    document.body.addEventListener('click', async function (event) {
        
        // 1. Verificar si estamos en la sección de backups
        const section = event.target.closest('[data-section="admin-backups"]');
        if (!section) {
            if (selectedBackupFile) clearBackupSelection();
            return;
        }

        // 2. Manejar selección de item
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        const backupCard = event.target.closest('.card-item[data-backup-filename]');
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (backupCard) {
            event.preventDefault();
            const filename = backupCard.dataset.backupFilename;
            
            if (selectedBackupFile === filename) {
                clearBackupSelection();
            } else {
                // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
                const oldSelected = document.querySelector('.card-item.selected[data-backup-filename]');
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                backupCard.classList.add('selected');
                selectedBackupFile = filename;
                enableSelectionActions();
            }
            return;
        }

        // 3. Manejar botones de acción
        const button = event.target.closest('button[data-action]');
        if (!button) {
            if (!event.target.closest('[data-module].active')) {
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
                await handleBackupAction('create-backup', null, button);
                break;

            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            case 'admin-backup-restore':
                if (!selectedBackupFile) {
                    showAlert(getTranslation('admin.backups.errorNoSelection'), 'error');
                    return;
                }
                
                // Ya no mostramos confirm()
                // En su lugar, navegamos a la nueva página
                const linkUrl = window.projectBasePath + '/admin/restore-backup?file=' + encodeURIComponent(selectedBackupFile);
                
                const link = document.createElement('a');
                link.href = linkUrl;
                link.setAttribute('data-nav-js', 'true'); 
                // data-action no es necesario, la URL es suficiente
                document.body.appendChild(link);
                link.click();
                link.remove();
            
                clearBackupSelection(); 
                break;
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            case 'admin-backup-delete':
                if (!selectedBackupFile) {
                    showAlert(getTranslation('admin.backups.errorNoSelection'), 'error');
                    return;
                }
                 if (!confirm(getTranslation('admin.backups.confirmDelete', { filename: selectedBackupFile }))) {
                    return;
                }
                await handleBackupAction('delete-backup', selectedBackupFile, button);
                break;

            case 'admin-backup-clear-selection':
                clearBackupSelection();
                break;
        }
    });

    // --- Listeners Globales para deseleccionar ---
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            clearBackupSelection();
        }
    });
}