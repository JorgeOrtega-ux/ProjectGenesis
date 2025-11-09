// FILE: assets/js/app/main-controller.js
// (MODIFICADO CON LOGS Y CORRECCIÓN DE CONFLICTO)

import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js'; 

const deactivateAllModules = (exceptionModule = null) => {
    // console.log('[MainController] Desactivando todos los módulos', exceptionModule ? `excepto ${exceptionModule.dataset.module}` : '');
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
    
    console.log('[MainController] Inicializando controlador principal...');

    document.body.addEventListener('click', async function (event) {
        
        const button = event.target.closest('[data-action]');

        if (button) {
            hideTooltip();
        }

        if (!button) {
            // console.log('[MainController] Clic en el body, sin data-action.');
            return;
        }

        const action = button.getAttribute('data-action');
        console.log(`[MainController] Clic detectado en: ${action}`);

        if (action === 'logout') {
            event.preventDefault();
            console.log('[MainController] Acción de Logout iniciada...');
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
                            console.log('[MainController] Verificación de red: Conexión OK.');
                            resolve(true);
                        } else {
                            console.log('[MainController] Verificación de red: Sin conexión.');
                            reject(new Error(getTranslation('js.main.errorNetwork')));
                        }
                    }, 800);
                });
            };

            const checkSession = () => {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        console.log('[MainController] Verificación de sesión: Activa (simulado).');
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
                console.log('[MainController] Enviando formulario de Logout...');
                form.submit();

            } catch (error) {
                alert(getTranslation('js.main.errorLogout') + (error.message || getTranslation('js.auth.errorUnknown')));
            } finally {
                spinnerContainer.remove();
                logoutButton.classList.remove('disabled-interactive');
            }
            return;
        

        } else if (action.startsWith('toggleSection')) {
            console.log('[MainController] Acción de navegación (toggleSection). Omitiendo.');
            return;
        }

        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            console.log('[MainController] Clic en item de un popover-selector. Omitiendo.');
            return;
        }

        if (action.startsWith('toggle')) {
            
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
            // Le decimos al controlador principal que ignore la campana de notificaciones.
            // Su propio manager (notification-manager) se encargará de ella.
            const managedActions = [
                'toggleModulePageFilter',
                'toggleModuleAdminRole',
                'toggleModuleAdminStatus',
                'toggleModuleAdminCreateRole',
                'toggleModuleCommunitySelect', 
                'toggleModuleSelectGroup',
                'toggleModuleSearch',
                'toggleModuleSearchFilter',
                'toggleModuleNotifications' // <-- ¡ESTA LÍNEA ES LA CORRECCIÓN!
            ];
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

            if (managedActions.includes(action)) {
                console.log(`[MainController] Acción ${action} es manejada por su propio módulo. Omitiendo.`);
                return; 
            }

            console.log(`[MainController] Propagación detenida para: ${action}`);
            event.stopPropagation(); // <-- Este es el causante del bug original

            let moduleName = action.substring(6);
            moduleName = moduleName.charAt(0).toLowerCase() + moduleName.slice(1);

            console.log(`[MainController] Buscando módulo: [data-module="${moduleName}"]`);
            const module = document.querySelector(`[data-module="${moduleName}"]`);

            if (module) {
                const isOpening = module.classList.contains('disabled');
                console.log(`[MainController] Módulo encontrado. ${isOpening ? 'Abriendo' : 'Cerrando'}.`);

                if (isOpening && !allowMultipleActiveModules) {
                    deactivateAllModules(module);
                }

                module.classList.toggle('disabled');
                module.classList.toggle('active');
            } else {
                console.warn(`[MainController] No se encontró el módulo para la acción: ${action}`);
            }
        }
    });

    if (closeOnClickOutside) {
        document.addEventListener('click', function (event) {
            const clickedOnModule = event.target.closest('[data-module].active');
            const clickedOnButton = event.target.closest('[data-action]');
            const clickedOnCardItem = event.target.closest('.card-item');
            const clickedOnSearchInput = event.target.closest('#header-search-input'); 
            
            if (!clickedOnModule && !clickedOnButton && !clickedOnCardItem && !clickedOnSearchInput) { 
                // console.log('[MainController] Clic fuera de todo. Cerrando popovers.');
                deactivateAllModules();
            }
        });
    }

    if (closeOnEscape) {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                console.log('[MainController] Tecla ESCAPE presionada. Cerrando popovers.');
                deactivateAllModules();
            }
        });
    }

}

export { deactivateAllModules, initMainController };