// FILE: assets/js/app/main-controller.js
// (CÓDIGO MODIFICADO PARA USAR PUSHSTATE Y CARGAR PÁGINA)

import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js'; 
import { handleNavigation } from '../app/url-manager.js'; // <-- ¡AÑADIDO!

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
    
    document.body.addEventListener('click', async function (event) {
        
        // 1. Comprobar si se hizo clic en un item de grupo (esto debe ir primero)
        const groupItem = event.target.closest('.group-select-item');
        if (groupItem) { 
            event.preventDefault();
            
            // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA ▼▼▼ ---
            
            // 1. Obtener datos del ítem clickeado
            const groupName = groupItem.dataset.groupName;
            const groupUuid = groupItem.dataset.groupUuid; // <-- ¡NUEVO!
            const groupI18nKey = groupItem.dataset.i18nKey;

            // 2. Determinar la nueva URL
            let newUrl;
            if (groupUuid) {
                // URL para un grupo específico
                newUrl = window.projectBasePath + '/c/' + groupUuid;
            } else {
                // URL para "Ningún grupo" (la raíz)
                newUrl = window.projectBasePath + '/';
            }

            // 3. Cerrar el popover
            deactivateAllModules();
            
            // 4. Comprobar si la URL ya es la actual (evitar recarga innecesaria)
            const currentPath = window.location.pathname;
            if (currentPath === newUrl) {
                return; // Ya estamos en esta URL
            }

            // 5. Actualizar la URL en el navegador
            history.pushState(null, '', newUrl);

            // 6. Llamar al router para que cargue el contenido de la nueva URL
            // (Esto recargará la sección 'home' con los datos correctos)
            handleNavigation(); 
            
            return; // Terminar
            // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---
        }
        

        // El resto de la lógica de clics para [data-action] va después
        const button = event.target.closest('[data-action]');

        if (button) {
            hideTooltip();
        }

        if (!button) {
            // Si no es un botón de acción ni un groupItem, salir
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
                
                const logoutUrl = (window.projectBasePath || '') + '/config/actions/logout.php';

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
        

        } else if (action.startsWith('toggleSection')) {
            return;
        }

        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            return;
        }

        if (action.startsWith('toggle')) {
            
            if (action === 'toggleModulePageFilter' || 
                action === 'toggleModuleAdminRole' || 
               action === 'toggleModuleAdminStatus' ||
                action === 'toggleModuleAdminCreateRole') { 
                return; 
            }
            
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
            const clickedOnGroupItem = event.target.closest('.group-select-item');
            const clickedOnCardItem = event.target.closest('.card-item');

            if (!clickedOnModule && !clickedOnButton && !clickedOnCardItem && !clickedOnGroupItem) {
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

}

export { deactivateAllModules, initMainController };