import { getTranslation } from './i18n-manager.js';
import { handleNavigation } from './url-manager.js';

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
    
    // --- ▼▼▼ INICIO DE LÓGICA DE SELECCIÓN ADMIN (CORREGIDA) ▼▼▼ ---
    let selectedAdminUserId = null;

    function clearAdminUserSelection() {
        const selectedCard = document.querySelector('.user-card-item.selected');
        if (selectedCard) {
            selectedCard.classList.remove('selected');
        }
        
        // Simplemente quitamos la clase del contenedor principal
        const toolbarContainer = document.querySelector('.admin-toolbar-container');
        if (toolbarContainer) {
            toolbarContainer.classList.remove('selection-active');
        }
        
        selectedAdminUserId = null;
    }
    // --- ▲▲▲ FIN DE LÓGICA DE SELECCIÓN ADMIN (CORREGIDA) ▲▲▲ ---


    document.body.addEventListener('click', async function (event) {
        
        // --- ▼▼▼ INICIO DE LÓGICA DE CLIC EN TARJETA (CORREGIDA) ▼▼▼ ---
        const userCard = event.target.closest('.user-card-item[data-user-id]');
        if (userCard) {
            event.preventDefault();
            const userId = userCard.dataset.userId;
            const toolbarContainer = document.querySelector('.admin-toolbar-container');

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
                
                // 3. Actualizar el toolbar (solo con la clase)
                if (toolbarContainer) {
                    toolbarContainer.classList.add('selection-active');
                }
            }
            return;
        }
        // --- ▲▲▲ FIN DE LÓGICA DE CLIC EN TARJETA (CORREGIDA) ▲▲▲ ---
        
        
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
            const toolbar = button.closest('.admin-toolbar-floating[data-current-page]');
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
            const searchBar = document.getElementById('admin-search-bar');
            
            if (searchBar) {
                const isActive = searchButton.classList.contains('active');
                
                if (isActive) {
                    // CERRAR Y LIMPIAR BÚSQUEDA
                    searchButton.classList.remove('active');
                    searchBar.style.display = 'none';
                    const searchInput = searchBar.querySelector('.admin-search-input');
                    if (searchInput) searchInput.value = '';
                    
                    // Limpiar selección al cerrar búsqueda
                    clearAdminUserSelection();

                    const newUrl = new URL(window.location);
                    if (newUrl.searchParams.has('q')) {
                        newUrl.searchParams.delete('q');
                        newUrl.searchParams.set('p', '1');
                        history.pushState(null, '', newUrl);
                        handleNavigation(); 
                    }
                } else {
                    // ABRIR
                    searchButton.classList.add('active');
                    searchBar.style.display = 'flex';
                    searchBar.querySelector('.admin-search-input')?.focus();
                }
            }
            return;
        
        } else if (action === 'admin-clear-selection') {
            event.preventDefault();
            clearAdminUserSelection();
            return;
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
        const searchInput = event.target.closest('.admin-search-input');
        
        if (!searchInput || event.key !== 'Enter') {
            return;
        }
        
        event.preventDefault(); 
        
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