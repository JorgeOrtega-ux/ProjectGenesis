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

    const actionButtons = document.querySelectorAll('[data-action]');

    actionButtons.forEach(button => {
        button.addEventListener('click', async function (event) {
            const action = this.getAttribute('data-action');

            // --- MODIFICACIÓN: LÓGICA DE LOGOUT 100% DINÁMICA ---
            if (action === 'logout') {
                event.preventDefault();
                const logoutButton = this; 
                
                if (logoutButton.classList.contains('loading')) {
                    return;
                }

                // 1. Añadir clase de carga (para evitar doble-click)
                logoutButton.classList.add('loading');
                
                // 2. Crear el CONTENEDOR del spinner
                const spinnerContainer = document.createElement('div');
                spinnerContainer.className = 'menu-link-icon'; // El 2do contenedor
                
                // 3. Crear el SPINNER
                const spinner = document.createElement('div');
                spinner.className = 'logout-spinner';
                
                // 4. Añadirlos al DOM
                spinnerContainer.appendChild(spinner);
                logoutButton.appendChild(spinnerContainer);
                
                // (El icono original 'logout' no se toca y permanece visible)

                // 5. Definir verificaciones (simuladas)
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

                // 6. Ejecutar verificaciones
                try {
                    await checkSession();
                    await checkNetwork();
                    await new Promise(res => setTimeout(res, 1000)); 

                    window.location.href = (window.projectBasePath || '') + '/logout.php';
                    
                } catch (error) {
                    alert(`Error al cerrar sesión: ${error.message || 'Error desconocido'}`);
                
                } finally {
                    // 7. Limpiar siempre (al final o si hay error)
                    
                    // Eliminar el CONTENEDOR del spinner (que se lleva el spinner)
                    spinnerContainer.remove(); 
                    
                    // Quitar la clase de carga
                    logoutButton.classList.remove('loading');
                }
                return;
            }
            // --- FIN DE LA MODIFICACIÓN ---
            
            if (action.startsWith('toggleSection')) {
                return; 
            }

            event.stopPropagation();
            
            if (action.startsWith('toggle')) {
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
    });

    if (closeOnClickOutside) {
        document.addEventListener('click', function (event) {
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