import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../services/alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
// --- ▼▼▼ MODIFICACIÓN DE IMPORTS (loadPage eliminado) ▼▼▼ ---
// import { loadPage } from '../app/url-manager.js'; // <-- ELIMINADO
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
import { hideTooltip } from '../services/tooltip-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

export function initAdminManager() {

    let selectedAdminUserId = null;
    let selectedAdminUserRole = null;
    let selectedAdminUserStatus = null;
    
    // --- ▼▼▼ VARIABLES DE ESTADO (Ahora leen el estado inicial del DOM) ▼▼▼ ---
    let currentPage = 1;
    let currentSearch = '';
    let currentSort = '';
    let currentOrder = '';

    // Leer el estado inicial renderizado por PHP
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
    // --- ▲▲▲ FIN DE VARIABLES DE ESTADO ▲▲▲ ---


    function enableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.add('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    function disableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;
        toolbarContainer.classList.remove('selection-active');
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection');
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

    // --- ▼▼▼ ¡NUEVAS FUNCIONES DE RENDERIZADO! ▼▼▼ ---

    /**
     * Muestra/Oculta el estado de carga de la lista
     * @param {boolean} isLoading 
     */
    function setListLoadingState(isLoading) {
        const listContainer = document.querySelector('.user-list-container');
        if (listContainer) {
            listContainer.style.opacity = isLoading ? '0.5' : '1';
            listContainer.style.pointerEvents = isLoading ? 'none' : 'auto';
        }
    }

    /**
     * Actualiza el texto de paginación y el estado de los botones
     * @param {number} currentPage 
     * @param {number} totalPages 
     */
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
        
        // Actualizar el data-attribute en el toolbar para futuras referencias
        const mainToolbar = document.querySelector('.page-toolbar-floating[data-current-page]');
        if (mainToolbar) {
            mainToolbar.dataset.currentPage = currentPage;
            mainToolbar.dataset.totalPages = totalPages;
        }
    }

    /**
     * Renderiza la lista de usuarios o el mensaje de "sin resultados"
     * @param {Array} users - El array de usuarios de la API
     * @param {number} totalUsers - El conteo total de usuarios
     */
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

    /**
     * Función principal que llama a la API y actualiza el DOM
     */
    async function fetchAndRenderUsers() {
        clearAdminUserSelection();
        setListLoadingState(true);

        const formData = new FormData();
        formData.append('action', 'get-users');
        formData.append('p', currentPage);
        formData.append('q', currentSearch);
        formData.append('s', currentSort);
        formData.append('o', currentOrder);
        // El token CSRF se añade automáticamente en callAdminApi

        const result = await callAdminApi(formData);

        if (result.success) {
            renderUserList(result.users, result.totalUsers);
            updatePaginationControls(result.currentPage, result.totalPages, result.totalUsers);
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            // Opcional: mostrar un estado de error en la lista
            listContainer.innerHTML = `<div class="component-card"><p>${getTranslation('js.api.errorServer')}</p></div>`;
        }

        setListLoadingState(false);
    }

    // --- ▲▲▲ FIN NUEVAS FUNCIONES DE RENDERIZADO ▲▲▲ ---
    
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
            // --- ▼▼▼ MODIFICACIÓN (Llamar a fetchAndRenderUsers en lugar de loadPage) ▼▼▼ ---
            fetchAndRenderUsers();
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));
        }
    }

    // --- ▼▼▼ ¡NUEVA FUNCIÓN! ▼▼▼ ---
    /**
     * Muestra u oculta un error inline en el formulario de creación
     * @param {string} messageKey - Clave de traducción del mensaje
     * @param {Object} data - Datos para reemplazar en la clave (ej. %length%)
     */
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
    
    // --- ▼▼▼ ¡NUEVA FUNCIÓN! ▼▼▼ ---
    /**
     * Oculta el error inline en el formulario de creación
     */
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

        // --- ▼▼▼ ¡NUEVA LÓGICA! ▼▼▼ ---
        // Manejar clic en el selector de rol en la PÁGINA DE CREACIÓN
        const createRoleLink = event.target.closest('[data-module="moduleAdminCreateRole"] .menu-link');
        if (createRoleLink) {
            event.preventDefault();
            const newValue = createRoleLink.dataset.value;
            if (!newValue) return;

            // 1. Actualizar el input oculto
            const hiddenInput = document.getElementById('admin-create-role-input');
            if (hiddenInput) {
                hiddenInput.value = newValue;
            }

            // 2. Actualizar el botón trigger
            const trigger = document.querySelector('[data-action="toggleModuleAdminCreateRole"]');
            const textEl = trigger.querySelector('.trigger-select-text span');
            const iconEl = trigger.querySelector('.trigger-select-icon span');
            
            const newTextKey = createRoleLink.querySelector('.menu-link-text span').dataset.i18n;
            const newIconName = createRoleLink.querySelector('.menu-link-icon span').textContent;

            if (textEl) textEl.setAttribute('data-i18n', newTextKey);
            if (textEl) textEl.textContent = getTranslation(newTextKey);
            if (iconEl) iconEl.textContent = newIconName;

            // 3. Actualizar el estado 'active' en el popover
            const menuList = createRoleLink.closest('.menu-list');
            menuList.querySelectorAll('.menu-link').forEach(link => {
                link.classList.remove('active');
                const icon = link.querySelector('.menu-link-check-icon');
                if (icon) icon.innerHTML = '';
            });
            createRoleLink.classList.add('active');
            const iconContainer = createRoleLink.querySelector('.menu-link-check-icon');
            if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';

            // 4. Cerrar el popover
            deactivateAllModules();
            return;
        }
        // --- ▲▲▲ FIN DE NUEVA LÓGICA ▲▲▲ ---

        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }
        const action = button.getAttribute('data-action');

        if (action === 'admin-page-next' || action === 'admin-page-prev') {
            event.preventDefault();
            hideTooltip();
            
            const toolbar = button.closest('.page-toolbar-floating[data-current-page]');
            if (!toolbar) return;
            
            // --- ▼▼▼ LÓGICA DE PAGINACIÓN MODIFICADA (Llama a fetchAndRenderUsers) ▼▼▼ ---
            const totalPages = parseInt(toolbar.dataset.totalPages, 10);
            let nextPage = (action === 'admin-page-next') ? currentPage + 1 : currentPage - 1;

            if (nextPage >= 1 && nextPage <= totalPages && nextPage !== currentPage) {
                currentPage = nextPage; // Actualiza el estado de JS
                fetchAndRenderUsers();  // Llama a la API
            }
            // --- ▲▲▲ FIN DE LÓGICA DE PAGINACIÓN ▲▲▲ ---
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
                
                // --- ▼▼▼ LÓGICA DE BÚSQUEDA MODIFICADA (Llama a fetchAndRenderUsers) ▼▼▼ ---
                if (currentSearch !== '') {
                    currentSearch = ''; // Actualiza el estado de JS
                    currentPage = 1;
                    hideTooltip();
                    fetchAndRenderUsers(); // Llama a la API
                }
                // --- ▲▲▲ FIN DE LÓGICA DE BÚSQUEDA ▲▲▲ ---
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
        
        if (action === 'admin-set-filter') {
            event.preventDefault();
            hideTooltip();
            deactivateAllModules(); 

            // --- ▼▼▼ LÓGICA DE FILTRO MODIFICADA (Llama a fetchAndRenderUsers) ▼▼▼ ---
            const newSort = button.dataset.sort;
            const newOrder = button.dataset.order;

            if (currentSort === newSort && currentOrder === newOrder) {
                return; // No hacer nada si el filtro ya está activo
            }

            if (newSort !== undefined && newOrder !== undefined) {
                // Actualiza el estado de JS
                currentSort = newSort;
                currentOrder = newOrder;
                currentPage = 1;
                
                // Actualizar la UI del popover
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
                
                fetchAndRenderUsers(); // Llama a la API
            }
            // --- ▲▲▲ FIN DE LÓGICA DE FILTRO ▲▲▲ ---
            return;
        }
        
        if (action === 'admin-set-role' || action === 'admin-set-status') {
            event.preventDefault();
            hideTooltip();
            const newValue = button.dataset.value;
            handleAdminAction(action, selectedAdminUserId, newValue, button);
            return;
        }

        // --- ▼▼▼ LÓGICA DE TOGGLE MODIFICADA ▼▼▼ ---
        if (action === 'toggleModulePageFilter' || 
            action === 'toggleModuleAdminRole' || 
            action === 'toggleModuleAdminStatus' ||
            action === 'toggleModuleAdminCreateRole') { // <-- ¡NUEVO!
            
            event.stopPropagation();
            let moduleName;
            
            if (action === 'toggleModulePageFilter') {
                moduleName = 'modulePageFilter';
            } else if (action === 'toggleModuleAdminRole') {
                if (!selectedAdminUserId) return; // No abrir si no hay usuario
                moduleName = 'moduleAdminRole';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminStatus'){ 
                if (!selectedAdminUserId) return; // No abrir si no hay usuario
                moduleName = 'moduleAdminStatus';
                updateAdminModals();
            } else if (action === 'toggleModuleAdminCreateRole') { // <-- ¡NUEVO!
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
            // --- ▲▲▲ FIN DE LÓGICA DE TOGGLE ▲▲▲ ---
        }
    });

    // --- ▼▼▼ ¡NUEVO LISTENER! ▼▼▼ ---
    // Manejar el envío del formulario de creación de usuario
    document.body.addEventListener('submit', async function(event) {
        if (event.target.id !== 'admin-create-user-form') {
            return;
        }
        event.preventDefault();
        
        const form = event.target;
        const button = form.querySelector('#admin-create-user-submit');
        
        // Ocultar error anterior
        hideCreateUserError();

        // --- Validación básica del lado del cliente ---
        const username = form.querySelector('#admin-create-username').value;
        const email = form.querySelector('#admin-create-email').value;
        const password = form.querySelector('#admin-create-password').value;
        const allowedDomains = /@(gmail\.com|outlook\.com|hotmail\.com|yahoo\.com|icloud\.com)$/i;

        if (!username || !email || !password) {
            showCreateUserError('js.auth.errorCompleteAllFields'); return;
        }
        if (username.length < 6 || username.length > 32) {
            showCreateUserError('js.auth.errorUsernameLength', {min: 6, max: 32}); return;
        }
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showCreateUserError('js.auth.errorInvalidEmail'); return;
        }
        if (!allowedDomains.test(email)) {
            showCreateUserError('js.auth.errorEmailDomain'); return;
        }
        if (password.length < 8 || password.length > 72) {
            showCreateUserError('js.auth.errorPasswordLength', {min: 8, max: 72}); return;
        }
        // --- Fin Validación ---

        // Mostrar spinner en el botón
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
            
            // Redirigir a la página de gestión de usuarios
            setTimeout(() => {
                const link = document.createElement('a');
                link.href = window.projectBasePath + '/admin/manage-users';
                link.setAttribute('data-nav-js', 'true');
                document.body.appendChild(link);
                link.click();
                link.remove();
            }, 1500);

        } else {
            // Mostrar error de la API
            showCreateUserError(result.message || 'js.auth.errorUnknown', result.data);
            
            // Restaurar botón
            if (button) {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || getTranslation('admin.create.createButton');
            }
        }
    });

    // Ocultar error de creación al escribir en cualquier input
    document.body.addEventListener('input', function(event) {
        const input = event.target.closest('#admin-create-user-form .component-input');
        if (input) {
            hideCreateUserError();
        }
    });
    // --- ▲▲▲ FIN DE NUEVO LISTENER ▲▲▲ ---

    document.body.addEventListener('keydown', function(event) {
        const searchInput = event.target.closest('.page-search-input');
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        hideTooltip();
        
        // --- ▼▼▼ LÓGICA DE BÚSQUEDA MODIFICADA (Llama a fetchAndRenderUsers) ▼▼▼ ---
        const newQuery = searchInput.value;
        
        if (currentSearch === newQuery) return; // No buscar lo mismo
        
        currentSearch = newQuery;
        currentPage = 1; 
        
        fetchAndRenderUsers(); // Llama a la API
        // --- ▲▲▲ FIN DE LÓGICA DE BÚSQUEDA ▲▲▲ ---
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