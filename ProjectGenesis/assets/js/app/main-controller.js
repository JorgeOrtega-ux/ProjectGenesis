// FILE: assets/js/app/main-controller.js
// (MODIFICADO - Eliminado el bloque 'toggleSection' que bloqueaba la navegación)
// (MODIFICADO OTRA VEZ - Añadida 'toggle-chat-context-menu' a las acciones gestionadas)

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
                            resolve(true);
                        } else {
                            reject(new Error(getTranslation('js.main.errorNetwork')));
                        }
                    }, 800);
                });
            };

            const checkSession = () => {
                return new Promise((resolve) => {
                    setTimeout(() => {
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
        
        
        // --- ▼▼▼ BLOQUE PROBLEMÁTICO ELIMINADO ▼▼▼ ---
        // } else if (action.startsWith('toggleSection')) {
        //     console.log('[MainController] Acción de navegación (toggleSection). Omitiendo.');
        //     return;
        // }
        // --- ▲▲▲ BLOQUE PROBLEMÁTICO ELIMINADO ▲▲▲ ---
        

        } else if (action.startsWith('toggleSection')) {
            return;
        }

        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            return;
        }

        if (action.startsWith('toggle')) {
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            const managedActions = [
                'toggleModulePageFilter',
                'toggleModuleAdminRole',
                'toggleModuleAdminStatus',
                'toggleModuleAdminCreateRole',
                'toggleModuleAdminCommunityPrivacy',
                'toggleModuleAdminCommunityType',
                'toggleModuleCommunitySelect', 
                'toggleModuleSelectGroup',
                'toggleModuleSearch',
                'toggleModuleSearchFilter',
                'toggleModuleNotifications',
                
                'toggle-post-options',   
                'toggle-post-privacy',   
                'toggle-comments',       
                'toggle-post-text',
                
                'toggleModuleProfileMore',
                'toggleFriendItemOptions',
                
                'toggleModuleAdminExport',
                'toggle-chat-context-menu' // <-- ¡ACCIÓN AÑADIDA A LA LISTA DE EXCEPCIONES!
            ];
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            if (managedActions.includes(action)) {
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
            } else {
            }
        }
    });

    if (closeOnClickOutside) {
        document.addEventListener('click', function (event) {
            const clickedOnModule = event.target.closest('[data-module].active');
            const clickedOnButton = event.target.closest('[data-action]');
            const clickedOnCardItem = event.target.closest('.card-item');
            const clickedOnSearchInput = event.target.closest('#header-search-input'); 
            
            // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
            // Añadir el botón de contexto de chat a las excepciones de "click-outside"
            const clickedOnChatMenuButton = event.target.closest('[data-action="toggle-chat-context-menu"]');
            
            if (!clickedOnModule && !clickedOnButton && !clickedOnCardItem && !clickedOnSearchInput && !clickedOnChatMenuButton) { 
                deactivateAllModules();
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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