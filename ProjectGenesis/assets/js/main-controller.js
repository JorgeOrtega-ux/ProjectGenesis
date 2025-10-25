/* ====================================== */
/* ======== MAIN-CONTROLLER.JS ========== */
/* ====================================== */
export const deactivateAllModules = (exceptionModule = null) => {
    document.querySelectorAll('[data-module].active').forEach(activeModule => {
        if (activeModule !== exceptionModule) {
            activeModule.classList.add('disabled');
            activeModule.classList.remove('active');
        }
    });
};

export function initMainController() {
    let allowMultipleActiveModules = false;
    let closeOnClickOutside = true;
    let closeOnEscape = true;

    // --- ▼▼▼ MODIFICACIÓN: Usar event delegation en document.body ▼▼▼ ---
    // Esto escucha clics en CUALQUIER [data-action], incluso los cargados después.
    document.body.addEventListener('click', async function (event) {
        
        // Encontrar el [data-action] más cercano al que se hizo clic
        const button = event.target.closest('[data-action]');
        
        // Si no se hizo clic en un [data-action], no hacer nada
        if (!button) {
            return;
        }

        const action = button.getAttribute('data-action');

        // --- Lógica de Logout ---
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
                            reject(new Error('No hay conexión a internet.'));
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
                
                window.location.href = `${logoutUrl}?csrf_token=${encodeURIComponent(token)}`;
                
            } catch (error) {
                alert(`Error al cerrar sesión: ${error.message || 'Error desconocido'}`);
            
            } finally {

                spinnerContainer.remove(); 
                
                logoutButton.classList.remove('disabled-interactive');
            }
            return;
        }
        
        // --- Ignorar acciones de navegación (manejadas por url-manager.js) ---
        if (action.startsWith('toggleSection')) {
            return; 
        }

        // --- Ignorar clics en los links del selector (manejados por settings-manager.js) ---
        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            return;
        }

        // --- Lógica para Toggles de Módulos (ej. toggleModuleSelect, toggleModuleTriggerSelect) ---
        if (action.startsWith('toggle')) {
            event.stopPropagation(); // Prevenir que el 'click outside' lo cierre
            
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
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


    if (closeOnClickOutside) {
        document.addEventListener('click', function (event) {
            // Esta lógica SÍ debe estar separada.
            // Primero, el listener de arriba (delegado) abre el módulo.
            // Este listener (en document) cierra módulos si se hace clic fuera.
            const clickedOnModule = event.target.closest('[data-module].active');
            const clickedOnButton = event.target.closest('[data-action]');

            if (!clickedOnModule && !clickedOnButton) {
                deactivateAllModules();
            }
        });
    }

    if (closeOnEscape) {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                deactivateAllModules();
            }
        });
    }
    
    const scrollableContent = document.querySelector('.general-content-scrolleable');
    const headerTop = document.querySelector('.general-content-top');

    if (scrollableContent && headerTop) {
        scrollableContent.addEventListener('scroll', function() {
            
            if (this.scrollTop > 0) {
                headerTop.classList.add('shadow');
            } else {
                
                headerTop.classList.remove('shadow');
            }
        });
    }
}