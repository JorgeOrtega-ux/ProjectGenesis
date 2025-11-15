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
        const selectedCard = document.querySelector('.card-item.selected');
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
        
        // --- BLOQUE ELIMINADO ---
        // El bloque 'else if (statusModule)' que estaba aquí ha sido eliminado.
        // --- FIN DE BLOQUE ELIMINADO ---
    }

    function setListLoadingState(isLoading) {
        const listContainer = document.querySelector('.card-list-container');
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
        const listContainer = document.querySelector('.card-list-container');
        if (!listContainer) return;

        if (totalUsers === 0 || users.length === 0) {
            const isSearching = currentSearch !== '';
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
                     data-user-status="${user.status}"
                     data-restrictions="${user.restrictions || ''}">
                    
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
        formData.append('p', currentPage);
        formData.append('q', currentSearch);
        formData.append('s', currentSort);
        formData.append('o', currentOrder);
        
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
    
    async function handleAdminAction(actionType, targetUserId, newValue, buttonEl) {
        if (!targetUserId) {
            showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
            return;
        }

        const menuLinks = buttonEl.closest('.menu-list').querySelectorAll('.menu-link');
        menuLinks.forEach(link => link.classList.add('disabled-interactive'));

        const formData = new FormData();
        formData.append('action', 'set-role');
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
            }
            // --- BLOQUE ELIMINADO ---
            // El 'else if (actionType === 'admin-set-status')' que estaba aquí ha sido eliminado.
            // --- FIN DE BLOQUE ELIMINADO ---
            
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
            
            if (selectedAdminLogFile === filename) {
                clearLogSelection();
            } else {
                const oldSelected = document.querySelector('.card-item.selected[data-log-filename]');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                logCard.classList.add('selected');
                selectedAdminLogFile = filename;
                enableLogSelectionActions();
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
            const clickedOnAnyCard = event.target.closest('.card-item');
            const clickedOnModule = event.target.closest('[data-module].active');

            if (!clickedOnModule && !button && !clickedOnAnyCard) {
                clearLogSelection();
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
                        console.error('Error al copiar (fallback):', err);
                    }
                } else {
                    navigator.clipboard.writeText(passInput.value)
                        .then(() => {
                            showAlert(getTranslation('js.admin.passwordCopied') || 'Contraseña copiada al portapapeles', 'success');
                        })
                        .catch(err => {
                            showAlert(getTranslation('js.admin.passwordCopyError') || 'Error al copiar la contraseña', 'error');
                            console.error('Error al copiar (clipboard API):', err);
                        });
                }
            }
            return;
        }
        
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
            
            const totalPages = parseInt(toolbar.dataset.totalPages, 10);
            let nextPage = (action === 'admin-page-next') ? currentPage + 1 : currentPage - 1;

            if (nextPage >= 1 && nextPage <= totalPages && nextPage !== currentPage) {
                currentPage = nextPage; 
                clearAdminUserSelection(); 
                fetchAndRenderUsers();  
            }
            return;
        }
        
        if (action === 'admin-toggle-search') {
            event.preventDefault();
            const searchButton = button;
            const isActive = searchButton.classList.contains('active');
            
            closeAllToolbarModes(); 

            if (!isActive) {
                openSearchMode();
            } else {
                const searchInput = document.getElementById('page-search-bar-container')?.querySelector('.page-search-input');
                if (searchInput) searchInput.value = '';
                
                if (currentSearch !== '') {
                    currentSearch = ''; 
                    currentPage = 1;
                    hideTooltip();
                    fetchAndRenderUsers(); 
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

        // --- INICIO DEL CÓDIGO AÑADIDO (VERSIÓN 2 - CORREGIDA) ---
        if (action === 'toggleSectionAdminManageStatus') {
            if (!selectedAdminUserId) {
                showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
                event.preventDefault(); 
                event.stopImmediatePropagation(); // <-- Prevenir que url-manager lo robe
                return;
            }
            
            // Prevenir que otros listeners (url-manager) tomen este clic
            event.preventDefault();
            event.stopImmediatePropagation();

            // Construye la URL con el ID
            const linkUrl = window.projectBasePath + '/admin/manage-status?id=' + selectedAdminUserId;
            
            // Simula la navegación de la misma manera que 'Editar Usuario'
            const link = document.createElement('a');
            link.href = linkUrl;
            link.setAttribute('data-nav-js', 'true'); 
            document.body.appendChild(link);
            link.click();
            link.remove();
            
            clearAdminUserSelection(); 
            deactivateAllModules(); 
            return; // Fin
        }
        // --- FIN DEL CÓDIGO AÑADIDO ---
        
        if (action === 'admin-set-filter') {
            event.preventDefault();
            hideTooltip();
            clearAdminUserSelection(); 

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
        
        // --- LÍNEA MODIFICADA ---
        if (action === 'admin-set-role') {
        // --- FIN DE MODIFICACIÓN ---
            event.preventDefault();
            hideTooltip();
            const newValue = button.dataset.value;
            handleAdminAction(action, selectedAdminUserId, newValue, button);
            return;
        }

        if (action === 'toggleModuleAdminExport') {
            event.stopPropagation();
            const module = document.querySelector('[data-module="moduleAdminExport"]');
            if (module) {
                const isOpening = module.classList.contains('disabled');
                if (isOpening) {
                    deactivateAllModules(module);
                }
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        if (action === 'admin-export-as') {
            event.preventDefault();
            const format = button.dataset.format;
            
            showAlert(`Iniciando exportación a ${format.toUpperCase()}...`, 'info');
            
            deactivateAllModules();

            console.log(`Solicitando exportación de usuarios como: ${format}`);
            
            
            
            
            return;
        }

        // --- BLOQUE ELIMINADO ---
        // El 'if (action === 'admin-save-status-btn')' que estaba aquí ha sido eliminado.
        // --- FIN DE BLOQUE ELIMINADO ---

        // --- BLOQUE ELIMINADO ---
        // El 'if (action === 'admin-status-cancel')' que estaba aquí ha sido eliminado.
        // --- FIN DE BLOQUE ELIMINADO ---

        // --- LÍNEA MODIFICADA ---
        if (action === 'toggleModuleAdminRole' || 
            action === 'toggleModuleAdminCreateRole') { 
        // --- FIN DE MODIFICACIÓN ---
            
            event.stopPropagation();
            let moduleName;
            
            if (action === 'toggleModuleAdminRole') {
                if (!selectedAdminUserId) return; 
                moduleName = 'moduleAdminRole';
                updateAdminModals();
            // --- BLOQUE ELIMINADO ---
            // El 'else if (action === 'toggleModuleAdminStatus')' que estaba aquí ha sido eliminado.
            // --- FIN DE BLOQUE ELIMINADO ---
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


    document.body.addEventListener('input', function(event) {
        const input = event.target.closest('#admin-create-user-form .component-input');
        if (input) {
            hideCreateUserError();
        }
        
        // --- BLOQUE ELIMINADO ---
        // El 'if (statusRadio)' que estaba aquí ha sido eliminado.
        // --- FIN DE BLOQUE ELIMINADO ---
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
            clearLogSelection(); 
        }
    });

    document.addEventListener('click', function (event) {
        const clickedOnModule = event.target.closest('[data-module].active');
        const clickedOnButton = event.target.closest('[data-action]');
        const clickedOnUserCard = event.target.closest('.card-item[data-user-id]');
        
        const clickedOnAnyCard = event.target.closest('.card-item');

        if (!clickedOnModule && !clickedOnButton && !clickedOnAnyCard) {
            clearAdminUserSelection();
            clearLogSelection(); 
        }
    });

    const userListContainer = document.querySelector('.card-list-container');
    if (userListContainer) {
        applyTranslations(userListContainer);
    }
}