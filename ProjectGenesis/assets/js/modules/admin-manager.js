import { callAdminApi } from '../services/api-service.js';
import { showAlert } from '../alert-manager.js';
import { getTranslation } from '../services/i18n-manager.js';
import { handleNavigation } from '../app/url-manager.js';
import { hideTooltip } from '../tooltip-manager.js';
import { deactivateAllModules } from '../app/main-controller.js';

export function initAdminManager() {

    let selectedAdminUserId = null;
    let selectedAdminUserRole = null;
    let selectedAdminUserStatus = null;

    // Habilita los botones de acción y muestra la vista de selección
    function enableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;

        toolbarContainer.classList.add('selection-active');
        
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    // Deshabilita los botones de acción y muestra la vista por defecto
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
    
    // Actualiza los popovers de admin con el estado actual del usuario seleccionado
    function updateAdminModals() {
        // Actualizar popover de Rol
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
        
        // Actualizar popover de Estado
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
    
    // Maneja la llamada a la API para cambiar rol o estado
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
        // El token CSRF se añade automáticamente en callAdminApi

        const result = await callAdminApi(formData);

        if (result.success) {
            showAlert(getTranslation(result.message || 'js.admin.successRole'), 'success');
            clearAdminUserSelection();
            deactivateAllModules();
            handleNavigation(); // Esto recarga la sección
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));
        }
    }

    // --- Listener principal para acciones de admin ---
    document.body.addEventListener('click', async function (event) {
        
        // 1. Selección de tarjeta de usuario
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

        // 2. Clic en botones de acción
        const button = event.target.closest('[data-action]');
        if (!button) {
            return;
        }
        const action = button.getAttribute('data-action');

        // 3. Paginación
        if (action === 'admin-page-next' || action === 'admin-page-prev') {
            event.preventDefault();
            hideTooltip();
            
            const toolbar = button.closest('.page-toolbar-floating[data-current-page]');
            if (!toolbar) return;
            
            clearAdminUserSelection();

            let currentPage = parseInt(toolbar.dataset.currentPage, 10);
            const totalPages = parseInt(toolbar.dataset.totalPages, 10);
            let nextPage = (action === 'admin-page-next') ? currentPage + 1 : currentPage - 1;

            if (nextPage >= 1 && nextPage <= totalPages) {
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('p', nextPage);
                history.pushState(null, '', newUrl);
                handleNavigation();
            }
            return;
        }
        
        // 4. Búsqueda (toggle)
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
                
                clearAdminUserSelection();

                const newUrl = new URL(window.location);
                if (newUrl.searchParams.has('q')) {
                    newUrl.searchParams.delete('q');
                    newUrl.searchParams.set('p', '1');
                    history.pushState(null, '', newUrl);
                    hideTooltip();
                    handleNavigation(); 
                }
            } else {
                searchButton.classList.add('active');
                searchBarContainer.style.display = 'flex';
                searchBar.style.display = 'flex';
                searchBar.querySelector('.page-search-input')?.focus();
            }
            return;
        }
        
        // 5. Limpiar selección
        if (action === 'admin-clear-selection') {
            event.preventDefault();
            clearAdminUserSelection();
            return;
        }
        
        // 6. Aplicar filtro
        if (action === 'admin-set-filter') {
            event.preventDefault();
            hideTooltip();
            clearAdminUserSelection();
            deactivateAllModules(); // Cierra el popover de filtro

            const sort_by = button.dataset.sort;
            const sort_order = button.dataset.order;

            if (sort_by !== undefined && sort_order !== undefined) {
                const newUrl = new URL(window.location);
                
                if (sort_by === '') {
                    newUrl.searchParams.delete('s');
                } else {
                    newUrl.searchParams.set('s', sort_by);
                }

                if (sort_order === '') {
                    newUrl.searchParams.delete('o');
                } else {
                    newUrl.searchParams.set('o', sort_order);
                }
                
                newUrl.searchParams.set('p', '1');
                history.pushState(null, '', newUrl);
                handleNavigation();
            }
            return;
        }
        
        // 7. Aplicar cambio de rol/estado
        if (action === 'admin-set-role' || action === 'admin-set-status') {
            event.preventDefault();
            hideTooltip();
            const newValue = button.dataset.value;
            handleAdminAction(action, selectedAdminUserId, newValue, button);
            return;
        }

        // 8. Abrir popovers de admin
        if (action === 'toggleModulePageFilter' || action === 'toggleModuleAdminRole' || action === 'toggleModuleAdminStatus') {
            event.stopPropagation();
            let moduleName;

            if (action === 'toggleModulePageFilter') {
                moduleName = 'modulePageFilter';
            } else if (action === 'toggleModuleAdminRole') {
                moduleName = 'moduleAdminRole';
                updateAdminModals();
            } else { // toggleModuleAdminStatus
                moduleName = 'moduleAdminStatus';
                updateAdminModals();
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

    // --- Listeners de Teclado ---

    // Listener para la tecla 'Enter' en la búsqueda
    document.body.addEventListener('keydown', function(event) {
        const searchInput = event.target.closest('.page-search-input');
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        hideTooltip();
        clearAdminUserSelection();
        
        const newQuery = searchInput.value;
        const newUrl = new URL(window.location);
        
        if (newQuery.trim()) {
            newUrl.searchParams.set('q', newQuery);
        } else {
            newUrl.searchParams.delete('q');
        }
        
        newUrl.searchParams.set('p', '1'); 
        
        history.pushState(null, '', newUrl);
        handleNavigation();
    });

    // Listener para la tecla 'Escape'
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            clearAdminUserSelection();
        }
    });

    // Listener para clics "fuera" de elementos interactivos
    document.addEventListener('click', function (event) {
        const clickedOnModule = event.target.closest('[data-module].active');
        const clickedOnButton = event.target.closest('[data-action]');
        const clickedOnUserCard = event.target.closest('.user-card-item[data-user-id]');
        
        // Si se hace clic fuera de un módulo, un botón o una tarjeta de usuario, limpiar selección
        if (!clickedOnModule && !clickedOnButton && !clickedOnUserCard) {
            clearAdminUserSelection();
        }
    });
}