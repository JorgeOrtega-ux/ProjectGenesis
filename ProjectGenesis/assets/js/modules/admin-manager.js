// FILE: assets/js/modules/admin-manager.js
// (CÓDIGO MODIFICADO - Añadida lógica para edición de grupos en línea)

import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation, applyTranslations } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

export function initAdminManager() {

    let selectedAdminUserId = null;
    let selectedAdminUserRole = null;
    let selectedAdminUserStatus = null;
    
    let selectedAdminLogFile = null;

    // --- ▼▼▼ INICIO DE NUEVA LÓGICA (GRUPOS) ▼▼▼ ---
    let selectedAdminGroupId = null;
    // --- ▲▲▲ FIN DE NUEVA LÓGICA (GRUPOS) ▲▲▲ ---
    
    let userCurrentPage = 1;
    let userCurrentSearch = '';
    let userCurrentSort = '';
    let userCurrentOrder = '';

    let groupCurrentPage = 1;
    let groupCurrentSearch = '';

    const adminSection = document.querySelector('.section-content[data-section]');
    
    
    if (adminSection && adminSection.dataset.section === 'admin-manage-users') {
    
        const mainToolbar = document.querySelector('.page-toolbar-floating[data-current-page]');
        if (mainToolbar) {
            userCurrentPage = parseInt(mainToolbar.dataset.currentPage, 10) || 1;
        }
        const searchInput = document.querySelector('.page-search-input');
        if (searchInput) {
            userCurrentSearch = searchInput.value || '';
        }
        const activeFilter = document.querySelector('[data-module="modulePageFilter"] .menu-link.active');
        if (activeFilter) {
            userCurrentSort = activeFilter.dataset.sort || '';
            userCurrentOrder = activeFilter.dataset.order || '';
        }
    }
    
    
    if (adminSection && adminSection.dataset.section === 'admin-manage-groups') {
    
        const mainToolbar = document.querySelector('.page-toolbar-floating[data-current-page]');
        if (mainToolbar) {
            groupCurrentPage = parseInt(mainToolbar.dataset.currentPage, 10) || 1;
        }
        const searchInput = document.querySelector('.page-search-input');
        if (searchInput) {
            groupCurrentSearch = searchInput.value || '';
        }
    }


    function enableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.add('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    function disableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.remove('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = true;
        });
    }

    function clearAdminUserSelection() {
        const selectedCard = document.querySelector('.card-item.selected[data-user-id]'); // <-- Modificado
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableSelectionActions();
        selectedAdminUserId = null;
        selectedAdminUserRole = null;
        selectedAdminUserStatus = null;
        
        closeAllToolbarModes();
    }
    
    function enableLogSelectionActions() {
        const toolbarContainer = document.getElementById('log-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.add('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    function disableLogSelectionActions() {
        const toolbarContainer = document.getElementById('log-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.remove('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = true;
        });
    }

    function clearLogSelection() {
        const selectedCard = document.querySelector('.card-item.selected[data-log-filename]');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableLogSelectionActions();
        selectedAdminLogFile = null;
    }

    
    function enableGroupSelectionActions() {
        const toolbarContainer = document.getElementById('group-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.add('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    function disableGroupSelectionActions() {
        const toolbarContainer = document.getElementById('group-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.remove('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection button');
        selectionButtons.forEach(btn => {
            btn.disabled = true;
        });
    }

    function clearAdminGroupSelection() {
        const selectedCard = document.querySelector('.card-item.selected[data-group-id]');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableGroupSelectionActions();
        selectedAdminGroupId = null;
        
        closeAllToolbarModes();
    }
    
    
    function updateAdminModals() {
        const roleModule = document.querySelector('[data-module="moduleAdminRole"]');
        if (roleModule) {
            roleModule.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
                if (link.dataset.value === selectedAdminUserRole) {
                    link.classList.add('active');
                    if (icon) icon.innerHTML = '<span class="material-symbols-rounded">check</span>';
                }
            });
        }
        const statusModule = document.querySelector('[data-module="moduleAdminStatus"]');
        if (statusModule) {
            statusModule.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
                if (link.dataset.value === selectedAdminUserStatus) {
                    link.classList.add('active');
                    if (icon) icon.innerHTML = '<span class="material-symbols-rounded">check</span>';
                }
            });
        }
    }

    function setListLoadingState(isLoading) {
        const listContainer = document.querySelector('.card-list-container');
        if (listContainer) {
            listContainer.style.opacity = isLoading ? '0.5' : '1';
            listContainer.style.pointerEvents = isLoading ? 'none' : 'auto';
        }
    }

    function updatePaginationControls(currentPage, totalPages, totalItems) {
        const pageText = document.querySelector('.page-toolbar-page-text');
        const prevButton = document.querySelector('[data-action="admin-page-prev"]');
        const nextButton = document.querySelector('[data-action="admin-page-next"]');

        if (pageText) {
            pageText.textContent = (totalItems == 0) ? '--' : `${currentPage} / ${totalPages}`;
        }
        if (prevButton) {
            prevButton.disabled = (currentPage <= 1);
        }
        if (nextButton) {
            nextButton.disabled = (currentPage >= totalPages);
        }
        
        const mainToolbar = document.querySelector('.page-toolbar-floating[data-current-page]');
        if (mainToolbar) {
            mainToolbar.dataset.currentPage = currentPage;
            mainToolbar.dataset.totalPages = totalPages;
        }
    }

    function renderUserList(users, totalUsers) {
        const listContainer = document.querySelector('.card-list-container');
        if (!listContainer) return;

        if (totalUsers === 0 || users.length === 0) {
            const isSearching = userCurrentSearch !== '';
            const icon = isSearching ? 'person_search' : 'person_off';
            const titleKey = isSearching ? 'admin.users.noResultsTitle' : 'admin.users.noUsersTitle';
            const descKey = isSearching ? 'admin.users.noResultsDesc' : 'admin.users.noUsersDesc';
            
            listContainer.innerHTML = `
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">${icon}</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="${titleKey}"></h2>
                            <p class="component-card__description" data-i18n="${descKey}"></p>
                        </div>
                    </div>
                </div>`;
            applyTranslations(listContainer);
            return;
        }

        let userListHtml = '';
        users.forEach(user => {
            userListHtml += `
                <div class="card-item" 
                     data-user-id="${user.id}"
                     data-user-role="${user.role}"
                     data-user-status="${user.status}">
                    
                    <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="${user.role}">
                        <img src="${user.avatarUrl}" alt="${user.username}" class="component-card__avatar-image">
                    </div>

                    <div class="card-item-details">
                        <div class="card-detail-item card-detail-item--full">
                            <span class="card-detail-label" data-i18n="admin.users.labelUsername"></span>
                            <span class="card-detail-value">${user.username}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label" data-i18n="admin.users.labelRole"></span>
                            <span class="card-detail-value">${user.roleDisplay}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label" data-i18n="admin.users.labelCreated"></span>
                            <span class="card-detail-value">${user.createdAt}</span>
                        </div>
                        ${user.email ? `
                        <div class="card-detail-item card-detail-item--full">
                            <span class="card-detail-label" data-i18n="admin.users.labelEmail"></span>
                            <span class="card-detail-value">${user.email}</span>
                        </div>` : ''}
                        <div class="card-detail-item">
                            <span class="card-detail-label" data-i18n="admin.users.labelStatus"></span>
                            <span class="card-detail-value">${user.statusDisplay}</span>
                        </div>
                    </div>
                </div>`;
        });
        listContainer.innerHTML = userListHtml;
        applyTranslations(listContainer);
    }

    async function fetchAndRenderUsers() {
        setListLoadingState(true);

        const formData = new FormData();
        formData.append('action', 'get-users');
        formData.append('p', userCurrentPage);
        formData.append('q', userCurrentSearch);
        formData.append('s', userCurrentSort);
        formData.append('o', userCurrentOrder);
        
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            formData.append('csrf_token', csrfInput.value);
        }

        const result = await callAdminApi(formData);

        if (result.success) {
            renderUserList(result.users, result.totalUsers);
            updatePaginationControls(result.currentPage, result.totalPages, result.totalUsers);
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            const listContainer = document.querySelector('.card-list-container');
            if (listContainer) {
                listContainer.innerHTML = `<div class="component-card"><p>${getTranslation('js.api.errorServer')}</p></div>`;
            }
        }

        setListLoadingState(false);
    }
    
    function renderGroupList(groups, totalGroups) {
        const listContainer = document.querySelector('.card-list-container');
        if (!listContainer) return;

        if (totalGroups === 0 || groups.length === 0) {
            const isSearching = groupCurrentSearch !== '';
            const icon = isSearching ? 'search_off' : 'groups';
            const titleKey = isSearching ? 'admin.groups.noResultsTitle' : 'admin.groups.noGroupsTitle';
            const descKey = isSearching ? 'admin.groups.noResultsDesc' : 'admin.groups.noGroupsDesc';
            
            listContainer.innerHTML = `
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">${icon}</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title" data-i18n="${titleKey}"></h2>
                            <p class="component-card__description" data-i18n="${descKey}"></p>
                        </div>
                    </div>
                </div>`;
            applyTranslations(listContainer);
            return;
        }

        let groupListHtml = '';
        groups.forEach(group => {
            const privacyIcon = (group.privacy_raw === 'publico') ? 'public' : 'lock';
            
            groupListHtml += `
                <div class="card-item" 
                     data-group-id="${group.id}">
                    
                    <div class="component-card__icon" style="width: 50px; height: 50px; flex-shrink: 0; background-color: #f5f5fa;">
                         <span class="material-symbols-rounded" style="font-size: 28px;">${group.type === 'Universidad' ? 'school' : 'account_balance'}</span>
                    </div>

                    <div class="card-item-details">
                        <div class="card-detail-item card-detail-item--full">
                            <span class="card-detail-label">Nombre</span>
                            <span class="card-detail-value">${group.name}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;">key</span>
                            <span class="card-detail-value" style="font-family: monospace;">${group.access_key}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label">Miembros</span>
                            <span class="card-detail-value">${group.member_count}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label">Privacidad</span>
                            <span class="material-symbols-rounded" style="font-size: 16px; color: #6b7280;">${privacyIcon}</span>
                            <span class="card-detail-value">${group.privacy}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label">Tipo</span>
                            <span class="card-detail-value">${group.type}</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="card-detail-label">Creación</span>
                            <span class="card-detail-value">${group.createdAt}</span>
                        </div>
                    </div>
                </div>`;
        });
        listContainer.innerHTML = groupListHtml;
        
        applyTranslations(listContainer);
    }
    
    async function fetchAndRenderGroups() {
        setListLoadingState(true);

        const formData = new FormData();
        formData.append('action', 'get-groups');
        formData.append('p', groupCurrentPage);
        formData.append('q', groupCurrentSearch);
        
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            formData.append('csrf_token', csrfInput.value);
        }

        const result = await callAdminApi(formData);

        if (result.success) {
            renderGroupList(result.groups, result.totalGroups);
            updatePaginationControls(result.currentPage, result.totalPages, result.totalGroups);
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            const listContainer = document.querySelector('.card-list-container');
            if (listContainer) {
                listContainer.innerHTML = `<div class="component-card"><p>${getTranslation('js.api.errorServer')}</p></div>`;
            }
        }

        setListLoadingState(false);
    }

    
    async function handleAdminAction(actionType, targetUserId, newValue, buttonEl) {
        if (!targetUserId) {
            showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
            return;
        }

        const menuLinks = buttonEl.closest('.menu-list').querySelectorAll('.menu-link');
        menuLinks.forEach(link => link.classList.add('disabled-interactive'));

        const formData = new FormData();
        formData.append('action', actionType === 'admin-set-role' ? 'set-role' : 'set-status');
        formData.append('target_user_id', targetUserId);
        formData.append('new_value', newValue);
        
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) {
            formData.append('csrf_token', csrfInput.value);
        }

        const result = await callAdminApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.admin.successRole'), 'success');
            deactivateAllModules();

            
            const selectedCard = document.querySelector('.card-item.selected');
            
            if (actionType === 'admin-set-role') {
                selectedAdminUserRole = newValue;
                if (selectedCard) {
                    selectedCard.dataset.userRole = newValue;
                    
                    const newRoleText = buttonEl.querySelector('.menu-link-text span').textContent;
                    const labels = selectedCard.querySelectorAll('.card-detail-label');
                    labels.forEach(label => {
                        if (label.dataset.i18n === 'admin.users.labelRole') { 
                            const valueEl = label.nextElementSibling;
                            if (valueEl && valueEl.classList.contains('card-detail-value')) {
                                valueEl.textContent = newRoleText;
                            }
                        }
                    });
                    
                    const avatar = selectedCard.querySelector('.component-card__avatar');
                    if(avatar) avatar.dataset.role = newValue;
                }
            } else { 
                selectedAdminUserStatus = newValue;
                if (selectedCard) {
                    selectedCard.dataset.userStatus = newValue;
                    
                    const newStatusText = buttonEl.querySelector('.menu-link-text span').textContent;
                    const labels = selectedCard.querySelectorAll('.card-detail-label');
                    labels.forEach(label => {
                        if (label.dataset.i18n === 'admin.users.labelStatus') { 
                            const valueEl = label.nextElementSibling;
                            if (valueEl && valueEl.classList.contains('card-detail-value')) {
                                valueEl.textContent = newStatusText;
                            }
                        }
                    });
                }
            }
            
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));


        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));
        }
    }

    function showCreateUserError(messageKey, data = null) {
        const errorDiv = document.querySelector('#admin-create-card-actions .component-card__error');
        if (!errorDiv) return;

        let message = getTranslation(messageKey);
        if (data) {
            Object.keys(data).forEach(key => {
                message = message.replace(`%${key}%`, data[key]);
            });
        }
        
        errorDiv.textContent = message;
        errorDiv.classList.add('active'); 
        errorDiv.classList.remove('disabled');
    }
    
    function hideCreateUserError() {
        const errorDiv = document.querySelector('#admin-create-card-actions .component-card__error');
        if (errorDiv) {
            errorDiv.classList.remove('active'); 
            errorDiv.classList.add('disabled');
        }
    }
    
    
    // --- ▼▼▼ MODIFICACIÓN (Usar nuevo ID de error) ▼▼▼ ---
    function showEditGroupError(messageKey, data = null) {
        const errorDiv = document.querySelector('#admin-edit-group-error');
        if (!errorDiv) return;
    // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---

        let message = getTranslation(messageKey);
        if (data) {
            Object.keys(data).forEach(key => {
                message = message.replace(`%${key}%`, data[key]);
            });
        }
        
        errorDiv.textContent = message;
        errorDiv.classList.add('active'); 
        errorDiv.classList.remove('disabled');
    }

    // --- ▼▼▼ MODIFICACIÓN (Usar nuevo ID de error) ▼▼▼ ---
    function hideEditGroupError() {
        const errorDiv = document.querySelector('#admin-edit-group-error');
        if (errorDiv) {
            errorDiv.classList.remove('active'); 
            errorDiv.classList.add('disabled');
        }
    }
    // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---

    // --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (Guardado parcial de grupo) ▼▼▼ ---
    /**
     * Maneja una actualización de un solo campo para un grupo.
     * @param {string} field El campo de la BD (name, privacy, access_key).
     * @param {string} newValue El nuevo valor a guardar.
     * @param {HTMLElement} cardElement El .component-card que contiene el input/botón.
     * @param {HTMLElement} [buttonElement=null] El botón de guardado (para el spinner).
     */
    async function handleAdminGroupUpdate(field, newValue, cardElement, buttonElement = null) {
        hideEditGroupError(); // Ocultar error general

        const wrapper = cardElement.closest('.component-wrapper');
        const targetIdInput = wrapper.querySelector('#admin-edit-target-group-id');
        const csrfInput = wrapper.querySelector('input[name="csrf_token"]');
        
        if (!targetIdInput || !csrfInput) {
            console.error('No se encontró target_group_id o csrf_token');
            return;
        }

        const targetGroupId = targetIdInput.value;
        const csrfToken = csrfInput.value;
        
        // Mostrar spinner si es un botón
        if (buttonElement) {
            buttonElement.disabled = true;
            buttonElement.dataset.originalText = buttonElement.innerHTML;
            buttonElement.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
        } else {
            // Deshabilitar el trigger del dropdown
            cardElement.querySelector('.trigger-selector')?.classList.add('disabled-interactive');
        }
        
        const formData = new FormData();
        formData.append('action', 'admin-update-group');
        formData.append('target_group_id', targetGroupId);
        formData.append('field', field);
        formData.append('new_value', newValue);
        formData.append('csrf_token', csrfToken);

        const result = await callAdminApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'admin.groups.successUpdate'), 'success');

            // Lógica específica si el guardado fue de un botón (edit-in-place)
            if (buttonElement) {
                // Actualizar el texto estático
                const displayElement = cardElement.querySelector('#admin-group-name-display-text');
                if (displayElement) {
                    displayElement.textContent = newValue;
                    displayElement.dataset.originalValue = newValue;
                }
                // Volver al modo vista
                cardElement.querySelector('#admin-group-name-edit-state').classList.remove('active');
                cardElement.querySelector('#admin-group-name-edit-state').classList.add('disabled');
                cardElement.querySelector('#admin-group-name-actions-edit').classList.remove('active');
                cardElement.querySelector('#admin-group-name-actions-edit').classList.add('disabled');
                cardElement.querySelector('#admin-group-name-view-state').classList.add('active');
                cardElement.querySelector('#admin-group-name-view-state').classList.remove('disabled');
                cardElement.querySelector('#admin-group-name-actions-view').classList.add('active');
                cardElement.querySelector('#admin-group-name-actions-view').classList.remove('disabled');
            }
            
            // Lógica específica para el header
            if (field === 'name') {
                const headerTitle = document.querySelector('.component-header-card strong');
                if (headerTitle) {
                    headerTitle.textContent = newValue;
                }
            }
            
        } else {
            // Mostrar error
            showEditGroupError(result.message || 'js.auth.errorUnknown', result.data);
            
            // Revertir el input si es un 'edit-in-place'
            if (buttonElement) {
                const inputElement = cardElement.querySelector('#admin-group-name-input');
                const displayElement = cardElement.querySelector('#admin-group-name-display-text');
                if (inputElement && displayElement) {
                    inputElement.value = displayElement.dataset.originalValue;
                }
            }
            // NOTA: No revertimos el dropdown, el usuario puede volver a seleccionarlo.
        }

        // Restaurar botones/dropdowns
        if (buttonElement) {
            buttonElement.disabled = false;
            buttonElement.innerHTML = buttonElement.dataset.originalText;
        } else {
            cardElement.querySelector('.trigger-selector')?.classList.remove('disabled-interactive');
        }
    }
    // --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---

    function closeAllToolbarModes() {
        const searchBarContainer = document.getElementById('page-search-bar-container');
        if (searchBarContainer) {
            searchBarContainer.classList.remove('active');
            searchBarContainer.classList.add('disabled');
        }
        const searchButton = document.querySelector('[data-action="admin-toggle-search"]');
        if (searchButton) {
            searchButton.classList.remove('active');
        }

        deactivateAllModules(); 
        
        const filterButton = document.querySelector('[data-action="toggleModulePageFilter"]');
        if (filterButton) {
            filterButton.classList.remove('active');
        }
    }

    function openSearchMode() {
        const searchBarContainer = document.getElementById('page-search-bar-container');
        const searchButton = document.querySelector('[data-action="admin-toggle-search"]');
        
        if (searchBarContainer && searchButton) {
            searchButton.classList.add('active');
            searchBarContainer.classList.add('active');
            searchBarContainer.classList.remove('disabled');
            searchBarContainer.querySelector('.page-search-input')?.focus();
        }
    }

    function openFilterMode() {
        const module = document.querySelector(`[data-module="modulePageFilter"]`);
        const filterButton = document.querySelector('[data-action="toggleModulePageFilter"]');

        if (module && filterButton) {
            filterButton.classList.add('active');
            module.classList.add('active');
            module.classList.remove('disabled');
        }
    }


    document.body.addEventListener('click', async function (event) {
        
        const userCard = event.target.closest('.card-item[data-user-id]');
        if (userCard) {
            event.preventDefault();
            const userId = userCard.dataset.userId;
            
            clearAdminGroupSelection(); 
            
            if (selectedAdminUserId === userId) {
                clearAdminUserSelection(); 
            } else {
                const oldSelected = document.querySelector('.card-item.selected');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                userCard.classList.add('selected');
                selectedAdminUserId = userId;
                selectedAdminUserRole = userCard.dataset.userRole;
                selectedAdminUserStatus = userCard.dataset.userStatus;
                
                closeAllToolbarModes(); 
                enableSelectionActions(); 
            }
            return;
        }

        const logCard = event.target.closest('.card-item[data-log-filename]');
        if (logCard) {
            event.preventDefault();
            const filename = logCard.dataset.logFilename;
            
            clearAdminGroupSelection(); 
            clearAdminUserSelection(); 
            
            if (selectedAdminLogFile === filename) {
                clearLogSelection();
            } else {
                const oldSelected = document.querySelector('.card-item.selected');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                logCard.classList.add('selected');
                selectedAdminLogFile = filename;
                enableLogSelectionActions();
            }
            return;
        }

        
        const groupCard = event.target.closest('.card-item[data-group-id]');
        if (groupCard) {
            event.preventDefault();
            const groupId = groupCard.dataset.groupId;
            
            clearAdminUserSelection(); 
            clearLogSelection(); 
            
            if (selectedAdminGroupId === groupId) {
                clearAdminGroupSelection(); 
            } else {
                const oldSelected = document.querySelector('.card-item.selected');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                groupCard.classList.add('selected');
                selectedAdminGroupId = groupId;
                
                closeAllToolbarModes(); 
                enableGroupSelectionActions(); 
            }
            return;
        }
        

        const createRoleLink = event.target.closest('[data-module="moduleAdminCreateRole"] .menu-link');
        if (createRoleLink) {
            event.preventDefault();
            const newValue = createRoleLink.dataset.value;
            if (!newValue) return;

            const hiddenInput = document.getElementById('admin-create-role-input');
            if (hiddenInput) {
                hiddenInput.value = newValue;
            }

            const trigger = document.querySelector('[data-action="toggleModuleAdminCreateRole"]');
            const textEl = trigger.querySelector('.trigger-select-text span');
            const iconEl = trigger.querySelector('.trigger-select-icon span');
            
            const newTextKey = createRoleLink.querySelector('.menu-link-text span').dataset.i18n;
            const newIconName = createRoleLink.querySelector('.menu-link-icon span').textContent;

            if (textEl) textEl.setAttribute('data-i18n', newTextKey);
            if (textEl) textEl.textContent = getTranslation(newTextKey);
            if (iconEl) iconEl.textContent = newIconName;

            const menuList = createRoleLink.closest('.menu-list');
            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
            });
            createRoleLink.classList.add('active');
            const iconContainer = createRoleLink.querySelector('.menu-link-check-icon');
            if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';

            deactivateAllModules();
            return;
        }
        
        
        // --- ▼▼▼ MODIFICACIÓN (Lógica copiada de settings-manager.js y adaptada) ▼▼▼ ---
        const editGroupPrivacyLink = event.target.closest('[data-module="moduleAdminEditGroupPrivacy"] .menu-link');
        if (editGroupPrivacyLink) {
            event.preventDefault();
            
            const cardElement = editGroupPrivacyLink.closest('.component-card');
            if (!cardElement) return;

            // 1. Obtener elementos de la UI
            const menuList = editGroupPrivacyLink.closest('.menu-list');
            const trigger = cardElement.querySelector('[data-action="toggleModuleAdminEditGroupPrivacy"]');
            const textEl = trigger.querySelector('.trigger-select-text span');
            const iconEl = trigger.querySelector('.trigger-select-icon span');
            const hiddenInput = cardElement.querySelector('#admin-group-privacy-input');
            
            // 2. Obtener nuevos valores del link clickeado
            const newValue = editGroupPrivacyLink.dataset.value;
            const newTextKey = editGroupPrivacyLink.querySelector('.menu-link-text span').dataset.i18n;
            const newIconName = editGroupPrivacyLink.querySelector('.menu-link-icon span').textContent;
            
            if (editGroupPrivacyLink.classList.contains('active')) {
                deactivateAllModules();
                return; // No hacer nada si ya está activo
            }
            
            // 3. Actualizar la UI del trigger
            if (textEl) textEl.setAttribute('data-i18n', newTextKey);
            if (textEl) textEl.textContent = getTranslation(newTextKey);
            if (iconEl) iconEl.textContent = newIconName;
            if (hiddenInput) hiddenInput.value = newValue;

            // 4. Actualizar estado "active" en la lista
            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
            });
            editGroupPrivacyLink.classList.add('active');
            const iconContainer = editGroupPrivacyLink.querySelector('.menu-link-check-icon');
            if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';

            deactivateAllModules();

            // 5. Llamar a la API para guardar (auto-save)
            await handleAdminGroupUpdate('privacy', newValue, cardElement, null);
            
            return;
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        const button = event.target.closest('[data-action]');
        if (!button) {
            const clickedOnAnyCard = event.target.closest('.card-item');
            const clickedOnModule = event.target.closest('[data-module].active');

            if (!clickedOnModule && !button && !clickedOnAnyCard) {
                clearLogSelection();
                clearAdminUserSelection(); 
                clearAdminGroupSelection(); 
            }
            return;
        }
        const action = button.getAttribute('data-action');

        if (action === 'admin-generate-username') {
            
            event.preventDefault();
            const inputId = button.getAttribute('data-toggle');
            const input = document.getElementById(inputId);
            
            if (input) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const timestamp = `${year}${month}${day}_${hours}${minutes}${seconds}`;
                const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
                const suffix = chars[Math.floor(Math.random() * chars.length)] + chars[Math.floor(Math.random() * chars.length)];
                const newUsername = `user${timestamp}${suffix}`;
                const maxUserLength = window.maxUsernameLength || 32;
                input.value = newUsername.substring(0, maxUserLength);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                button.blur();
                hideCreateUserError(); 
            }
            return; 
        }

        if (action === 'admin-generate-password') {
            
            event.preventDefault();
            const passInput = document.getElementById('admin-create-password');
            const generateBtn = button;
            
            if (passInput && generateBtn && !generateBtn.disabled) {
                generateBtn.disabled = true;
                const originalBtnText = generateBtn.innerHTML;
                generateBtn.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;

                const formData = new FormData();
                formData.append('action', 'admin-generate-password');
                
                const result = await callAdminApi(formData);

                if (result.success && result.password) {
                    passInput.value = result.password;
                    hideCreateUserError();
                } else {
                    showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                }
                
                generateBtn.disabled = false;
                generateBtn.innerHTML = originalBtnText;
            }
            return;
        }
        
        if (action === 'admin-copy-password') {
            
            event.preventDefault();
            const passInput = document.getElementById('admin-create-password');
            if (passInput && passInput.value) {
                
                if (!navigator.clipboard) {
                    try {
                        passInput.focus();
                        passInput.select();
                        document.execCommand('copy');
                        passInput.blur();
                        showAlert(getTranslation('js.admin.passwordCopied') || 'Contraseña copiada al portapapeles', 'success');
                    } catch (err) {
                        showAlert(getTranslation('js.admin.passwordCopyError') || 'Error al copiar la contraseña', 'error');
                    }
                } else {
                    navigator.clipboard.writeText(passInput.value)
                        .then(() => {
                            showAlert(getTranslation('js.admin.passwordCopied') || 'Contraseña copiada al portapapeles', 'success');
                        })
                        .catch(err => {
                            showAlert(getTranslation('js.admin.passwordCopyError') || 'Error al copiar la contraseña', 'error');
                        });
                }
            }
            return;
        }
        
        
        if (action === 'admin-generate-group-code') {
            event.preventDefault();
            const codeInput = document.getElementById('admin-edit-group-access-key');
            const generateBtn = button;
            
            if (codeInput && generateBtn && !generateBtn.disabled) {
                generateBtn.disabled = true;
                const originalBtnText = generateBtn.innerHTML;
                
                generateBtn.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: inherit;"></span>`;

                const formData = new FormData();
                formData.append('action', 'admin-generate-group-code');
                
                
                const result = await callAdminApi(formData);

                if (result.success && result.code) {
                    codeInput.value = result.code;
                    
                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Auto-guardar al generar) ▼▼▼ ---
                    const cardElement = generateBtn.closest('.component-card');
                    if (cardElement) {
                        // Llamar a la función de guardado parcial, sin botón
                        await handleAdminGroupUpdate('access_key', result.code, cardElement, null);
                    }
                    hideEditGroupError();
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

                } else {
                    showAlert(getTranslation(result.message || 'js.api.errorServer'), 'error');
                }
                
                generateBtn.disabled = false;
                generateBtn.innerHTML = originalBtnText;
            }
            return;
        }

        if (action === 'admin-copy-group-code') {
            event.preventDefault();
            const codeInput = document.getElementById('admin-edit-group-access-key');
            if (codeInput && codeInput.value) {
                
                if (!navigator.clipboard) {
                    try {
                        codeInput.focus();
                        codeInput.select();
                        document.execCommand('copy');
                        codeInput.blur();
                        showAlert(getTranslation('js.admin.passwordCopied'), 'success');
                    } catch (err) {
                        showAlert(getTranslation('js.admin.passwordCopyError'), 'error');
                    }
                } else {
                    navigator.clipboard.writeText(codeInput.value)
                        .then(() => showAlert(getTranslation('js.admin.passwordCopied'), 'success'))
                        .catch(() => showAlert(getTranslation('js.admin.passwordCopyError'), 'error'));
                }
            }
            return;
        }
        
        // --- ▼▼▼ ACCIÓN ELIMINADA (admin-edit-group-submit) ▼▼▼ ---
        // if (action === 'admin-edit-group-submit') { ... }
        // --- ▲▲▲ FIN DE ACCIÓN ELIMINADA ▲▲▲ ---

        // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Guardar Nombre de Grupo) ▼▼▼ ---
        if (action === 'admin-group-name-edit-trigger') {
            event.preventDefault();
            const card = button.closest('.component-card');
            if (card) {
                hideEditGroupError(); // Ocultar error general
                card.querySelector('#admin-group-name-view-state').classList.remove('active');
                card.querySelector('#admin-group-name-view-state').classList.add('disabled');
                card.querySelector('#admin-group-name-actions-view').classList.remove('active');
                card.querySelector('#admin-group-name-actions-view').classList.add('disabled');
                card.querySelector('#admin-group-name-edit-state').classList.add('active');
                card.querySelector('#admin-group-name-edit-state').classList.remove('disabled');
                card.querySelector('#admin-group-name-actions-edit').classList.add('active');
                card.querySelector('#admin-group-name-actions-edit').classList.remove('disabled');
                card.querySelector('#admin-group-name-input')?.focus();
            }
            return;
        }

        if (action === 'admin-group-name-cancel-trigger') {
            event.preventDefault();
            const card = button.closest('.component-card');
            if (card) {
                hideEditGroupError(); // Ocultar error general
                const displayElement = card.querySelector('#admin-group-name-display-text');
                const inputElement = card.querySelector('#admin-group-name-input');
                if (displayElement && inputElement) {
                    inputElement.value = displayElement.dataset.originalValue;
                }
                card.querySelector('#admin-group-name-edit-state').classList.remove('active');
                card.querySelector('#admin-group-name-edit-state').classList.add('disabled');
                card.querySelector('#admin-group-name-actions-edit').classList.remove('active');
                card.querySelector('#admin-group-name-actions-edit').classList.add('disabled');
                card.querySelector('#admin-group-name-view-state').classList.add('active');
                card.querySelector('#admin-group-name-view-state').classList.remove('disabled');
                card.querySelector('#admin-group-name-actions-view').classList.add('active');
                card.querySelector('#admin-group-name-actions-view').classList.remove('disabled');
            }
            return;
        }

        if (action === 'admin-group-name-save-trigger-btn') {
            event.preventDefault();
            const card = button.closest('.component-card');
            const inputElement = card.querySelector('#admin-group-name-input');
            const newValue = inputElement ? inputElement.value : '';

            if (!newValue.trim()) {
                showEditGroupError('js.auth.errorCompleteAllFields');
                return;
            }
            
            // Llamar a la función de guardado parcial, pasando el botón
            await handleAdminGroupUpdate('name', newValue, card, button);
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
        
        if (action === 'admin-create-user-submit') {
            
            event.preventDefault();
            const button = event.target.closest('#admin-create-user-submit');
            
            hideCreateUserError();

            const usernameInput = document.getElementById('admin-create-username');
            const emailInput = document.getElementById('admin-create-email');
            const passwordInput = document.getElementById('admin-create-password');
            const roleInput = document.getElementById('admin-create-role-input');
            const is2faCheckbox = document.getElementById('admin-create-2fa');
            const csrfInput = document.querySelector('input[name="csrf_token"]');

            const username = usernameInput ? usernameInput.value : '';
            const email = emailInput ? emailInput.value : '';
            const password = passwordInput ? passwordInput.value : '';
            const role = roleInput ? roleInput.value : 'user';
            const is2fa = is2faCheckbox ? (is2faCheckbox.checked ? '1' : '0') : '0';
            const csrfToken = csrfInput ? csrfInput.value : '';

            const minUserLength = window.minUsernameLength || 6;
            const maxUserLength = window.maxUsernameLength || 32;
            const maxEmailLen = window.maxEmailLength || 255;

            if (!username || !email || !password) { 
                showCreateUserError('js.auth.errorCompleteAllFields'); return;
            }
            if (username.length < minUserLength || username.length > maxUserLength) {
                showCreateUserError('js.auth.errorUsernameLength', {min: minUserLength, max: maxUserLength}); return;
            }
            if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                showCreateUserError('js.auth.errorInvalidEmail'); return;
            }
            if (email.length > maxEmailLen) {
                showCreateUserError('js.auth.errorEmailLength'); return;
            }
            
            if (button) {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
            }

            const formData = new FormData();
            formData.append('action', 'create-user');
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('role', role);
            formData.append('is_2fa_enabled', is2fa);
            formData.append('csrf_token', csrfToken);

            const result = await callAdminApi(formData);

            if (result.success) {
                showAlert(getTranslation(result.message || 'admin.create.success'), 'success');
                
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = window.projectBasePath + '/admin/manage-users';
                    link.setAttribute('data-nav-js', 'true');
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }, 1500);

            } else {
                showCreateUserError(result.message || 'js.auth.errorUnknown', result.data);
                
                if (button) {
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalText || getTranslation('admin.create.createButton');
                }
            }
            return;
        }

        if (action === 'admin-page-next' || action === 'admin-page-prev') {
            
            event.preventDefault();
            hideTooltip();
            
            const toolbar = button.closest('.page-toolbar-floating[data-current-page]');
            if (!toolbar) return;
            const section = button.closest('.section-content');
            if (!section) return;

            const totalPages = parseInt(toolbar.dataset.totalPages, 10);
            
            if (section.dataset.section === 'admin-manage-users') {
                let nextPage = (action === 'admin-page-next') ? userCurrentPage + 1 : userCurrentPage - 1;
                if (nextPage >= 1 && nextPage <= totalPages && nextPage !== userCurrentPage) {
                    userCurrentPage = nextPage; 
                    clearAdminUserSelection(); 
                    fetchAndRenderUsers();  
                }
            } else if (section.dataset.section === 'admin-manage-groups') {
                let nextPage = (action === 'admin-page-next') ? groupCurrentPage + 1 : groupCurrentPage - 1;
                if (nextPage >= 1 && nextPage <= totalPages && nextPage !== groupCurrentPage) {
                    groupCurrentPage = nextPage;
                    clearAdminGroupSelection(); 
                    fetchAndRenderGroups();
                }
            }
            return;
        }
        
        if (action === 'admin-toggle-search') {
            
            event.preventDefault();
            const searchButton = button;
            const isActive = searchButton.classList.contains('active');
            const section = button.closest('.section-content');
            if (!section) return;

            closeAllToolbarModes(); 

            if (!isActive) {
                openSearchMode();
            } else {
                const searchInput = document.getElementById('page-search-bar-container')?.querySelector('.page-search-input');
                if (searchInput) searchInput.value = '';
                
                if (section.dataset.section === 'admin-manage-users') {
                    if (userCurrentSearch !== '') {
                        userCurrentSearch = ''; 
                        userCurrentPage = 1;
                        hideTooltip();
                        fetchAndRenderUsers(); 
                    }
                } else if (section.dataset.section === 'admin-manage-groups') {
                    if (groupCurrentSearch !== '') {
                        groupCurrentSearch = '';
                        groupCurrentPage = 1;
                        hideTooltip();
                        fetchAndRenderGroups();
                    }
                }
            }
            return;
        }
        
        if (action === 'toggleModulePageFilter') {
            
            event.stopPropagation(); 
            const filterButton = button;
            const isActive = filterButton.classList.contains('active');
            closeAllToolbarModes(); 
            if (!isActive) {
                openFilterMode();
            }
            return;
        }

        if (action === 'admin-clear-selection') {
            event.preventDefault();
            clearAdminUserSelection();
            return;
        }
        
        
        if (action === 'admin-group-clear-selection') {
            event.preventDefault();
            clearAdminGroupSelection();
            return;
        }
        

        if (action === 'admin-log-clear-selection') {
            
            event.preventDefault();
            clearLogSelection();
            return;
        }
        
        if (action === 'admin-log-view') {
            
            if (!selectedAdminLogFile) {
                showAlert(getTranslation('js.admin.logs.errorNoSelection') || "Por favor, selecciona un archivo de log primero.", 'error');
                event.preventDefault(); 
                event.stopImmediatePropagation();
                return;
            }
            
            const linkUrl = window.projectBasePath + '/admin/manage-logs?view=' + encodeURIComponent(selectedAdminLogFile);
            
            const link = document.createElement('a');
            link.href = linkUrl;
            link.setAttribute('data-nav-js', 'true'); 
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            clearLogSelection(); 
            return;
        }

        if (action === 'toggleSectionAdminEditUser') {
            
            if (!selectedAdminUserId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
                event.preventDefault(); 
                event.stopImmediatePropagation();
                return;
            }
            
            const linkUrl = window.projectBasePath + '/admin/edit-user?id=' + selectedAdminUserId;
            
            if (button.tagName === 'A') {
                button.href = linkUrl;
            } else {
                const link = document.createElement('a');
                link.href = linkUrl;
                link.setAttribute('data-nav-js', 'true'); 
                document.body.appendChild(link);
                link.click();
                link.remove();
            }
            
            clearAdminUserSelection(); 
            deactivateAllModules(); 
            return;
        }
        
        
        if (action === 'toggleSectionAdminEditGroup') {
            if (!selectedAdminGroupId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error'); 
                event.preventDefault(); 
                event.stopImmediatePropagation();
                return;
            }
            
            
            const linkUrl = window.projectBasePath + '/admin/edit-group?id=' + selectedAdminGroupId;
            
            const link = document.createElement('a');
            link.href = linkUrl;
            link.setAttribute('data-nav-js', 'true'); 
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            clearAdminGroupSelection(); 
            deactivateAllModules(); 
            return;
        }
        
        
        if (action === 'admin-set-filter') {
            
            event.preventDefault();
            hideTooltip();
            clearAdminUserSelection(); 

            const newSort = button.dataset.sort;
            const newOrder = button.dataset.order;

            if (userCurrentSort === newSort && userCurrentOrder === newOrder) {
                return; 
            }

            if (newSort !== undefined && newOrder !== undefined) {
                userCurrentSort = newSort;
                userCurrentOrder = newOrder;
                userCurrentPage = 1;
                
                const menuList = button.closest('.menu-list');
                if (menuList) {
                    menuList.querySelectorAll('.menu-link').forEach(link => {
                        link.classList.remove('active');
                        const icon = link.querySelector('.menu-link-check-icon');
                        if (icon) icon.innerHTML = '';
                    });
                    button.classList.add('active');
                    const icon = button.querySelector('.menu-link-check-icon');
                    if (icon) icon.innerHTML = '<span class="material-symbols-rounded">check</span>';
                }
                
                fetchAndRenderUsers(); 
            }
            return;
        }
        
        if (action === 'admin-set-role' || action === 'admin-set-status') {
            
            event.preventDefault();
            hideTooltip();
            const newValue = button.dataset.value;
            handleAdminAction(action, selectedAdminUserId, newValue, button);
            return;
        }

        if (action === 'toggleModuleAdminRole' || 
            action === 'toggleModuleAdminStatus' ||
            
            action === 'toggleModuleAdminCreateRole' ||
            action === 'toggleModuleAdminEditGroupPrivacy') { 
            
            
            event.stopPropagation();
            let moduleName;
            
            if (action === 'toggleModuleAdminRole') {
                if (!selectedAdminUserId) return; 
                moduleName = 'moduleAdminRole';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminStatus'){ 
                if (!selectedAdminUserId) return; 
                moduleName = 'moduleAdminStatus';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminCreateRole') { 
                moduleName = 'moduleAdminCreateRole';
            
            } else if (action === 'toggleModuleAdminEditGroupPrivacy') {
                moduleName = 'moduleAdminEditGroupPrivacy';
                
            }
            
            
            const module = document.querySelector(`[data-module="${moduleName}"]`);
            if (module) {
                const isOpening = module.classList.contains('disabled');
                if (isOpening) {
                    deactivateAllModules(module);
                }
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
        }
    });

    document.body.addEventListener('input', function(event) {
        const input = event.target.closest('#admin-create-user-form .component-input');
        if (input) {
            hideCreateUserError();
        }
        
        
        // --- ▼▼▼ MODIFICACIÓN (Selector de input) ▼▼▼ ---
        const groupInput = event.target.closest('#admin-edit-group-form .component-input, #admin-edit-group-form .component-text-input');
        if (groupInput) {
            hideEditGroupError();
            // --- ▼▼▼ INICIO DE NUEVA LÓGICA (Ocultar error al escribir) ▼▼▼ ---
            // Ocultar error en línea específico del componente
            const card = groupInput.closest('.component-card');
            if (card) {
                const errorDiv = card.nextElementSibling;
                if (errorDiv && errorDiv.classList.contains('component-card__error')) {
                    errorDiv.classList.remove('active');
                    errorDiv.classList.add('disabled');
                }
            }
            // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    });

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Auto-guardar Access Key en 'blur') ▼▼▼ ---
    document.body.addEventListener('blur', async function(event) {
        const accessKeyInput = event.target.closest('#admin-edit-group-access-key');
        if (accessKeyInput) {
            const cardElement = accessKeyInput.closest('.component-card');
            const newValue = accessKeyInput.value.trim().toUpperCase().replace(/-/g, '');
            
            // Formatear el valor en el input
            let formatted = '';
            for (let i = 0; i < newValue.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formatted += '-';
                }
                formatted += newValue[i];
            }
            accessKeyInput.value = formatted;
            
            // Si el valor (sin guiones) tiene 12 caracteres, intentar guardarlo
            if (newValue.length === 12) {
                await handleAdminGroupUpdate('access_key', newValue, cardElement, null);
            } else if (newValue.length > 0) {
                // Si no tiene 12, mostrar un error
                showEditGroupError('admin.groups.errorKeyLength');
            }
        }
    }, true); // Usar 'true' para capturar el evento 'blur'
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    document.body.addEventListener('keydown', function(event) {
        
        const searchInput = event.target.closest('.page-search-input');
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        hideTooltip();
        
        const newQuery = searchInput.value;
        const section = searchInput.closest('.section-content');
        if (!section) return;

        if (section.dataset.section === 'admin-manage-users') {
            if (userCurrentSearch === newQuery) return; 
            userCurrentSearch = newQuery;
            userCurrentPage = 1; 
            fetchAndRenderUsers(); 
        } else if (section.dataset.section === 'admin-manage-groups') {
            if (groupCurrentSearch === newQuery) return;
            groupCurrentSearch = newQuery;
            groupCurrentPage = 1;
            fetchAndRenderGroups();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            clearAdminUserSelection();
            clearLogSelection();
            clearAdminGroupSelection(); 
        }
    });

    document.addEventListener('click', function (event) {
        const clickedOnModule = event.target.closest('[data-module].active');
        const clickedOnButton = event.target.closest('[data-action]');
        const clickedOnAnyCard = event.target.closest('.card-item');

        if (!clickedOnModule && !clickedOnButton && !clickedOnAnyCard) {
            clearAdminUserSelection();
            clearLogSelection();
            clearAdminGroupSelection(); 
        }
    });

    const userListContainer = document.querySelector('.card-list-container');
    if (userListContainer) {
        applyTranslations(userListContainer);
    }
}