import { getTranslation } from './i18n-manager.js';
import { handleNavigation } from './url-manager.js';
import { hideTooltip } from './tooltip-manager.js'; 
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
import { callAdminApi } from './api-service.js';
import { showAlert } from './alert-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

const deactivateAllModules = (exceptionModule = null) => {
    document.querySelectorAll('[data-module].active').forEach(activeModule => {
        if (activeModule !== exceptionModule) {
            activeModule.classList.add('disabled');
            activeModule.classList.remove('active');
        }
    });
};

function initMainController() {
    let allowMultipleActiveModules = false;
    let closeOnClickOutside = true;
    let closeOnEscape = true;
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    let selectedAdminUserId = null;
    let selectedAdminUserRole = null;
    let selectedAdminUserStatus = null;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    // --- ▼▼▼ NUEVA FUNCIÓN ▼▼▼ ---
    // Habilita los botones de acción y muestra la vista de selección
    function enableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;

        toolbarContainer.classList.add('selection-active');
        
        // Encontrar botones de selección y HABILITARLOS
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection');
        selectionButtons.forEach(btn => {
            btn.disabled = false;
        });
    }

    // --- ▼▼▼ NUEVA FUNCIÓN ▼▼▼ ---
    // Deshabilita los botones de acción y muestra la vista por defecto
    function disableSelectionActions() {
        const toolbarContainer = document.querySelector('.page-toolbar-container');
        if (!toolbarContainer) return;

        toolbarContainer.classList.remove('selection-active');
        
        // Encontrar botones de selección y DESHABILITARLOS
        const selectionButtons = toolbarContainer.querySelectorAll('.toolbar-action-selection');
        selectionButtons.forEach(btn => {
            btn.disabled = true;
        });
    }

    // --- ▼▼▼ FUNCIÓN MODIFICADA ▼▼▼ ---
    function clearAdminUserSelection() {
        const selectedCard = document.querySelector('.user-card-item.selected');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        
        // Llamar a la nueva función helper
        disableSelectionActions();
        
        selectedAdminUserId = null;
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        selectedAdminUserRole = null;
        selectedAdminUserStatus = null;
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }
    
    // --- ▼▼▼ NUEVA FUNCIÓN ▼▼▼ ---
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
    
    // --- ▼▼▼ NUEVA FUNCIÓN ▼▼▼ ---
    // Maneja la llamada a la API para cambiar rol o estado
    async function handleAdminAction(actionType, targetUserId, newValue, buttonEl) {
        if (!targetUserId) {
            showAlert(getTranslation('js.admin.errorNoSelection'), 'error');
            return;
        }

        // Deshabilitar todos los links en el menú actual para evitar doble clic
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
            // Limpiar selección y recargar la lista de usuarios
            clearAdminUserSelection();
            deactivateAllModules();
            handleNavigation(); // Esto recarga la sección
        } else {
            showAlert(getTranslation(result.message || 'js.auth.errorUnknown'), 'error');
            // Si falla, reactivar los links
            menuLinks.forEach(link => link.classList.remove('disabled-interactive'));
        }
    }


    document.body.addEventListener('click', async function (event) {
        
        // --- ▼▼▼ BLOQUE MODIFICADO ▼▼▼ ---
        const userCard = event.target.closest('.user-card-item[data-user-id]');
        if (userCard) {
            event.preventDefault();
            const userId = userCard.dataset.userId;

            if (selectedAdminUserId === userId) {
                // Ya estaba seleccionado, así que lo deseleccionamos
                clearAdminUserSelection();
            } else {
                // No estaba seleccionado o era otro
                // 1. Limpiar selección anterior (visual)
                const oldSelected = document.querySelector('.user-card-item.selected');
                if (oldSelected) {
                    oldSelected.classList.remove('selected');
                }
                
                // 2. Seleccionar el nuevo
                userCard.classList.add('selected');
                selectedAdminUserId = userId;
                selectedAdminUserRole = userCard.dataset.userRole; // <-- AÑADIDO
                selectedAdminUserStatus = userCard.dataset.userStatus; // <-- AÑADIDO
                
                // 3. Actualizar el toolbar (con la función helper)
                enableSelectionActions();
            }
            return;
        }
        // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---
        
        
        const button = event.target.closest('[data-action]');

        if (!button) {
            return;
        }

        const action = button.getAttribute('data-action');

        if (action === 'logout') {
            event.preventDefault();
            const logoutButton = button;

            if (logoutButton.classList.contains('disabled-interactive')) {
                return;
            }

            logoutButton.classList.add('disabled-interactive');

            const spinnerContainer = document.createElement('div');
            spinnerContainer.className = 'menu-link-icon';

            const spinner = document.createElement('div');
            spinner.className = 'logout-spinner';

            spinnerContainer.appendChild(spinner);
            logoutButton.appendChild(spinnerContainer);

            const checkNetwork = () => {
                return new Promise((resolve, reject) => {
                    setTimeout(() => {
                        if (navigator.onLine) {
                            console.log('Verificación: Conexión OK.');
                            resolve(true);
                        } else {
                            console.log('Verificación: Sin conexión.');
                            reject(new Error(getTranslation('js.main.errorNetwork')));
                        }
                    }, 800);
                });
            };

            const checkSession = () => {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        console.log('Verificación: Sesión activa (simulado).');
                        resolve(true);
                    }, 500);
                });
            };

            try {
                await checkSession();
                await checkNetwork();
                await new Promise(res => setTimeout(res, 1000));

                const token = window.csrfToken || '';
                const logoutUrl = (window.projectBasePath || '') + '/config/logout.php';

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = logoutUrl;
                form.style.display = 'none';

                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'csrf_token';
                tokenInput.value = token;

                form.appendChild(tokenInput);
                document.body.appendChild(form);
                form.submit();

            } catch (error) {
                alert(getTranslation('js.main.errorLogout') + (error.message || getTranslation('js.auth.errorUnknown')));
            } finally {
                spinnerContainer.remove();
                logoutButton.classList.remove('disabled-interactive');
            }
            return;
        
        } else if (action === 'admin-page-next' || action === 'admin-page-prev') {
            event.preventDefault();
            
            hideTooltip(); // <-- AÑADIDO: Ocultar tooltip antes de navegar
            
            const toolbar = button.closest('.page-toolbar-floating[data-current-page]');
            if (!toolbar) return;
            
            // Limpiar selección al navegar
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
        
        } else if (action === 'admin-toggle-search') {
            event.preventDefault();
            const searchButton = button;
            
            // --- INICIO DE CORRECCIÓN (BARRA DE BÚSQUEDA) ---
            
            // 1. Obtener la barra de búsqueda interna
            const searchBar = document.getElementById('page-search-bar');
            if (!searchBar) return;
            
            // 2. Obtener el contenedor flotante externo
            const searchBarContainer = searchBar.closest('.page-toolbar-floating');
            if (!searchBarContainer) return;
            
            // 3. Comprobar el estado
            const isActive = searchButton.classList.contains('active');
            
            if (isActive) {
                // CERRAR Y LIMPIAR BÚSQUEDA
                searchButton.classList.remove('active');
                searchBarContainer.style.display = 'none'; // <-- Ocultar el contenedor externo
                searchBar.style.display = 'none'; // <-- Asegurarse de ocultar también el interno

                const searchInput = searchBar.querySelector('.page-search-input');
                if (searchInput) searchInput.value = '';
                
                // Limpiar selección al cerrar búsqueda
                clearAdminUserSelection();

                const newUrl = new URL(window.location);
                if (newUrl.searchParams.has('q')) {
                    newUrl.searchParams.delete('q');
                    newUrl.searchParams.set('p', '1');
                    history.pushState(null, '', newUrl);
                    
                    hideTooltip(); // <-- AÑADIDO: Ocultar tooltip al navegar
                    
                    handleNavigation(); 
                }
            } else {
                // ABRIR
                searchButton.classList.add('active');
                searchBarContainer.style.display = 'flex'; // <-- Mostrar el contenedor externo
                searchBar.style.display = 'flex'; // <-- Mostrar la barra interna
                searchBar.querySelector('.page-search-input')?.focus();
            }
            
            // --- FIN DE CORRECCIÓN ---
            return;
        
        } else if (action === 'admin-clear-selection') {
            event.preventDefault();
            clearAdminUserSelection();
            return;
        
        // --- ▼▼▼ ¡BLOQUE DE FILTRO MODIFICADO! ▼▼▼ ---
        } else if (action === 'admin-set-filter') {
            event.preventDefault();
            hideTooltip();
            clearAdminUserSelection();
            deactivateAllModules(); // Cierra el popover de filtro

            const sort_by = button.dataset.sort;
            const sort_order = button.dataset.order;

            // Comprobar que los atributos existen (incluso si están vacíos)
            if (sort_by !== undefined && sort_order !== undefined) {
                const newUrl = new URL(window.location);
                
                // Si el valor está vacío, elimina el parámetro. Si no, lo establece.
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
                
                newUrl.searchParams.set('p', '1'); // Resetear a la página 1
                history.pushState(null, '', newUrl);
                handleNavigation();
            }
            return;
        // --- ▲▲▲ FIN DEL BLOQUE MODIFICADO ▲▲▲ ---
        
        // --- ▼▼▼ INICIO DE NUEVO BLOQUE ▼▼▼ ---
        } else if (action === 'admin-set-role' || action === 'admin-set-status') {
            event.preventDefault();
            hideTooltip();
            const newValue = button.dataset.value;
            // Llamar a la nueva función async
            handleAdminAction(action, selectedAdminUserId, newValue, button);
            return;
        // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---
        }


        if (action.startsWith('toggleSection')) {
            return;
        }

        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            return;
        }

        if (action.startsWith('toggle')) {
            event.stopPropagation();

            let moduleName = action.substring(6);
            moduleName = moduleName.charAt(0).toLowerCase() + moduleName.slice(1);

            // --- ▼▼▼ LÓGICA DE FILTRO Y ADMIN MODIFICADA ▼▼▼ ---
            if (action === 'toggleModulePageFilter') {
                moduleName = 'modulePageFilter';
            } else if (action === 'toggleModuleAdminRole') {
                moduleName = 'moduleAdminRole';
                updateAdminModals(); // Actualizar el estado antes de mostrar
            } else if (action === 'toggleModuleAdminStatus') {
                moduleName = 'moduleAdminStatus';
                updateAdminModals(); // Actualizar el estado antes de mostrar
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            const module = document.querySelector(`[data-module="${moduleName}"]`);

            if (module) {
                const isOpening = module.classList.contains('disabled');

                if (isOpening && !allowMultipleActiveModules) {
                    deactivateAllModules(module);
                }

                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
        }
    });

    if (closeOnClickOutside) {
        document.addEventListener('click', function (event) {
            const clickedOnModule = event.target.closest('[data-module].active');
            const clickedOnButton = event.target.closest('[data-action]');
            
            // No cerrar módulos si se hace clic en una tarjeta de usuario
            const clickedOnUserCard = event.target.closest('.user-card-item[data-user-id]');
            if (!clickedOnModule && !clickedOnButton && !clickedOnUserCard) {
                deactivateAllModules();
            }
        });
    }

    if (closeOnEscape) {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                deactivateAllModules();
                
                // Limpiar selección con Escape
                clearAdminUserSelection();
            }
        });
    }

    document.body.addEventListener('keydown', function(event) {
        const searchInput = event.target.closest('.page-search-input');
        
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        
        hideTooltip(); // <-- AÑADIDO: Ocultar tooltip al buscar
        
        // Limpiar selección al buscar
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
}

export { deactivateAllModules, initMainController };