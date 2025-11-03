import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

export function initAdminManager() {

    let selectedAdminUserId = null;
    let selectedAdminUserRole = null;
    let selectedAdminUserStatus = null;
    
    let currentPage = 1;
    let currentSearch = '';
    let currentSort = '';
    let currentOrder = '';

    const mainToolbar = document.querySelector('.page-toolbar-floating[data-current-page]');
    if (mainToolbar) {
        currentPage = parseInt(mainToolbar.dataset.currentPage, 10) || 1;
    }
    const searchInput = document.querySelector('.page-search-input');
    if (searchInput) {
        currentSearch = searchInput.value || '';
    }
    const activeFilter = document.querySelector('[data-module="modulePageFilter"] .menu-link.active');
    if (activeFilter) {
        currentSort = activeFilter.dataset.sort || '';
        currentOrder = activeFilter.dataset.order || '';
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
        const selectedCard = document.querySelector('.user-card-item.selected');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        disableSelectionActions();
        selectedAdminUserId = null;
        selectedAdminUserRole = null;
        selectedAdminUserStatus = null;
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
        const listContainer = document.querySelector('.user-list-container');
        if (listContainer) {
            listContainer.style.opacity = isLoading ? '0.5' : '1';
            listContainer.style.pointerEvents = isLoading ? 'none' : 'auto';
        }
    }

    function updatePaginationControls(currentPage, totalPages, totalUsers) {
        const pageText = document.querySelector('.page-toolbar-page-text');
        const prevButton = document.querySelector('[data-action="admin-page-prev"]');
        const nextButton = document.querySelector('[data-action="admin-page-next"]');

        if (pageText) {
            pageText.textContent = (totalUsers == 0) ? '--' : `${currentPage} / ${totalPages}`;
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
        const listContainer = document.querySelector('.user-list-container');
        if (!listContainer) return;

        if (totalUsers === 0 || users.length === 0) {
            const isSearching = currentSearch !== '';
            const icon = isSearching ? 'person_search' : 'person_off';
            const title = isSearching ? 'Sin resultados' : 'No se encontraron usuarios';
            const desc = isSearching ? 'No hay usuarios que coincidan con tu término de búsqueda.' : 'No hay usuarios registrados en esta página o hubo un error al cargarlos.';
            
            listContainer.innerHTML = `
                <div class="component-card">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">${icon}</span>
                        </div>
                        <div class="component-card__text">
                            <h2 class="component-card__title">${title}</h2>
                            <p class="component-card__description">${desc}</p>
                        </div>
                    </div>
                </div>`;
            return;
        }

        let userListHtml = '';
        users.forEach(user => {
            userListHtml += `
                <div class="user-card-item" 
                     data-user-id="${user.id}"
                     data-user-role="${user.role}"
                     data-user-status="${user.status}">
                    
                    <div class="component-card__avatar" style="width: 50px; height: 50px; flex-shrink: 0;" data-role="${user.role}">
                        <img src="${user.avatarUrl}" alt="${user.username}" class="component-card__avatar-image">
                    </div>

                    <div class="user-card-details">
                        <div class="user-card-detail-item user-card-detail-item--full">
                            <span class="user-card-detail-label">Nombre del usuario</span>
                            <span class="user-card-detail-value">${user.username}</span>
                        </div>
                        <div class="user-card-detail-item">
                            <span class="user-card-detail-label">Rol</span>
                            <span class="user-card-detail-value">${user.roleDisplay}</span>
                        </div>
                        <div class="user-card-detail-item">
                            <span class="user-card-detail-label">Fecha de creación</span>
                            <span class="user-card-detail-value">${user.createdAt}</span>
                        </div>
                        ${user.email ? `
                        <div class="user-card-detail-item user-card-detail-item--full">
                            <span class="user-card-detail-label">Email</span>
                            <span class="user-card-detail-value">${user.email}</span>
                        </div>` : ''}
                        <div class="user-card-detail-item">
                            <span class="user-card-detail-label">Estado de la cuenta</span>
                            <span class="user-card-detail-value">${user.statusDisplay}</span>
                        </div>
                    </div>
                </div>`;
        });
        listContainer.innerHTML = userListHtml;
    }

    async function fetchAndRenderUsers() {
        clearAdminUserSelection();
        setListLoadingState(true);

        const formData = new FormData();
        formData.append('action', 'get-users');
        formData.append('p', currentPage);
        formData.append('q', currentSearch);
        formData.append('s', currentSort);
        formData.append('o', currentOrder);

        const result = await callAdminApi(formData);

        if (result.success) {
            renderUserList(result.users, result.totalUsers);
            updatePaginationControls(result.currentPage, result.totalPages, result.totalUsers);
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            listContainer.innerHTML = `<div class="component-card"><p>${getTranslation('js.api.errorServer')}</p></div>`;
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

        const result = await callAdminApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.admin.successRole'), 'success');
            deactivateAllModules();
            fetchAndRenderUsers();
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));
        }
    }

    function showCreateUserError(messageKey, data = null) {
        const errorDiv = document.querySelector('#admin-create-card .component-card__error');
        if (!errorDiv) return;

        let message = getTranslation(messageKey);
        if (data) {
            Object.keys(data).forEach(key => {
                message = message.replace(`%${key}%`, data[key]);
            });
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
    
    function hideCreateUserError() {
        const errorDiv = document.querySelector('#admin-create-card .component-card__error');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    document.body.addEventListener('click', async function (event) {
        
        const userCard = event.target.closest('.user-card-item[data-user-id]');
        if (userCard) {
            event.preventDefault();
            const userId = userCard.dataset.userId;
            if (selectedAdminUserId === userId) {
                clearAdminUserSelection();
            } else {
                const oldSelected = document.querySelector('.user-card-item.selected');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                userCard.classList.add('selected');
                selectedAdminUserId = userId;
                selectedAdminUserRole = userCard.dataset.userRole;
                selectedAdminUserStatus = userCard.dataset.userStatus;
                enableSelectionActions();
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

        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }
        const action = button.getAttribute('data-action');

        // === INICIO DE MODIFICACIÓN ===
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
                
                // Formato: userYYYYMMDD_HHMMSS
                const timestamp = `${year}${month}${day}_${hours}${minutes}${seconds}`;
                
                // Sufijo: XX (dos caracteres alfanuméricos)
                const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
                const suffix = chars[Math.floor(Math.random() * chars.length)] + chars[Math.floor(Math.random() * chars.length)];
                
                const newUsername = `user${timestamp}${suffix}`;
                
                // Asegurarse de que no exceda el maxlength
                input.value = newUsername.substring(0, 32);
                
                // Forzar que la etiqueta flotante se active
                input.dispatchEvent(new Event('input', { bubbles: true }));
                
                // Opcional: quitar el foco del botón
                button.blur();
            }
            return; // Importante para no continuar
        }
        // === FIN DE MODIFICACIÓN ===

        if (action === 'admin-page-next' || action === 'admin-page-prev') {
            event.preventDefault();
            hideTooltip();
            
            const toolbar = button.closest('.page-toolbar-floating[data-current-page]');
            if (!toolbar) return;
            
            const totalPages = parseInt(toolbar.dataset.totalPages, 10);
            let nextPage = (action === 'admin-page-next') ? currentPage + 1 : currentPage - 1;

            if (nextPage >= 1 && nextPage <= totalPages && nextPage !== currentPage) {
                currentPage = nextPage; 
                fetchAndRenderUsers();  
            }
            return;
        }
        
        if (action === 'admin-toggle-search') {
            event.preventDefault();
            const searchButton = button;
            const searchBar = document.getElementById('page-search-bar');
            if (!searchBar) return;
            const searchBarContainer = searchBar.closest('.page-toolbar-floating');
            if (!searchBarContainer) return;
            
            const isActive = searchButton.classList.contains('active');
            
            if (isActive) {
                searchButton.classList.remove('active');
                searchBarContainer.style.display = 'none';
                searchBar.style.display = 'none';
                const searchInput = searchBar.querySelector('.page-search-input');
                if (searchInput) searchInput.value = '';
                
                if (currentSearch !== '') {
                    currentSearch = ''; 
                    currentPage = 1;
                    hideTooltip();
                    fetchAndRenderUsers(); 
                }
            } else {
                searchButton.classList.add('active');
                searchBarContainer.style.display = 'flex';
                searchBar.style.display = 'flex';
                searchBar.querySelector('.page-search-input')?.focus();
            }
            return;
        }
        
        if (action === 'admin-clear-selection') {
            event.preventDefault();
            clearAdminUserSelection();
            return;
        }

        if (action === 'toggleSectionAdminEditUser') {
            // event.preventDefault(); <-- No es necesario, url-manager lo hace
            // event.stopImmediatePropagation(); <-- Eliminado

            if (!selectedAdminUserId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
                return;
            }
            
            const link = document.createElement('a');
            link.href = window.projectBasePath + '/admin/edit-user?id=' + selectedAdminUserId;
            link.setAttribute('data-nav-js', 'true'); 
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            clearAdminUserSelection(); 
            deactivateAllModules(); 
            return;
        }
        
        if (action === 'admin-set-filter') {
            event.preventDefault();
            hideTooltip();
            deactivateAllModules(); 

            const newSort = button.dataset.sort;
            const newOrder = button.dataset.order;

            if (currentSort === newSort && currentOrder === newOrder) {
                return; 
            }

            if (newSort !== undefined && newOrder !== undefined) {
                currentSort = newSort;
                currentOrder = newOrder;
                currentPage = 1;
                
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

        if (action === 'toggleModulePageFilter' || 
            action === 'toggleModuleAdminRole' || 
            action === 'toggleModuleAdminStatus' ||
            action === 'toggleModuleAdminCreateRole') { 
            
            event.stopPropagation();
            let moduleName;
            
            if (action === 'toggleModulePageFilter') {
                moduleName = 'modulePageFilter';
            } else if (action === 'toggleModuleAdminRole') {
                if (!selectedAdminUserId) return; 
                moduleName = 'moduleAdminRole';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminStatus'){ 
                if (!selectedAdminUserId) return; 
                moduleName = 'moduleAdminStatus';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminCreateRole') { 
                moduleName = 'moduleAdminCreateRole';
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

    document.body.addEventListener('submit', async function(event) {
        if (event.target.id !== 'admin-create-user-form') {
            return;
        }
        event.preventDefault();
        
        const form = event.target;
        const button = form.querySelector('#admin-create-user-submit');
        
        hideCreateUserError();

        const username = form.querySelector('#admin-create-username').value;
        const email = form.querySelector('#admin-create-email').value;
        const password = form.querySelector('#admin-create-password').value;
        const passwordConfirm = form.querySelector('#admin-create-password-confirm').value; 
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS) ▼▼▼ ---
        // const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i; // <-- ELIMINADO
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        if (!username || !email || !password || !passwordConfirm) { 
            showCreateUserError('js.auth.errorCompleteAllFields'); return;
        }
        if (username.length < 6 || username.length > 32) {
            showCreateUserError('js.auth.errorUsernameLength', {min: 6, max: 32}); return;
        }
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showCreateUserError('js.auth.errorInvalidEmail'); return;
        }
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (DOMINIOS) ▼▼▼ ---
        /*
        if (!allowedDomains.test(email)) { // <-- ELIMINADO
            showCreateUserError('js.auth.errorEmailDomain'); return;
        }
        */
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        
        // --- ▼▼▼ INICIO DE MODIFICACIÓN (PASS GLOBAL) ▼▼▼ ---
        const minPassLength = window.minPasswordLength || 8;
        if (password.length < minPassLength || password.length > 72) {
            showCreateUserError('js.auth.errorPasswordLength', {min: minPassLength, max: 72}); return;
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        if (password !== passwordConfirm) {
            showCreateUserError('js.auth.errorPasswordMismatch'); return;
        }

        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = `<span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px; margin: 0 auto; border-top-color: #ffffff; border-left-color: #ffffff20; border-bottom-color: #ffffff20; border-right-color: #ffffff20;"></span>`;
        }

        const formData = new FormData(form);
        formData.append('action', 'create-user');

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
    });

    document.body.addEventListener('input', function(event) {
        const input = event.target.closest('#admin-create-user-form .component-input');
        if (input) {
            hideCreateUserError();
        }
    });

    document.body.addEventListener('keydown', function(event) {
        const searchInput = event.target.closest('.page-search-input');
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        hideTooltip();
        
        const newQuery = searchInput.value;
        
        if (currentSearch === newQuery) return; 
        
        currentSearch = newQuery;
        currentPage = 1; 
        
        fetchAndRenderUsers(); 
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            clearAdminUserSelection();
        }
    });

    document.addEventListener('click', function (event) {
        const clickedOnModule = event.target.closest('[data-module].active');
        const clickedOnButton = event.target.closest('[data-action]');
        const clickedOnUserCard = event.target.closest('.user-card-item[data-user-id]');
        
        if (!clickedOnModule && !clickedOnButton && !clickedOnUserCard) {
            clearAdminUserSelection();
        }
    });
}