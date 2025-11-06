import { getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js'; 

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
    
    // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA (ELIMINAR VARIABLES DEL MODAL) ▼▼▼ ---
    // const groupSelectModal = document.getElementById('group-select-modal'); // <-- ELIMINADO
    // const groupSelectBtn = document.getElementById('select-group-btn'); // <-- ELIMINADO
    const groupDisplay = document.getElementById('selected-group-display');
    const groupDisplayText = groupDisplay ? groupDisplay.querySelector('.toolbar-group-text') : null;
    // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---

    document.body.addEventListener('click', async function (event) {
        
        
        const button = event.target.closest('[data-action]');

        if (button) {
            hideTooltip();
        }

        if (!button) {
            // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA (ELIMINAR IF) ▼▼▼ ---
            // if (groupSelectModal && event.target === groupSelectModal) { ... } // <-- ELIMINADO
            // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---
            return;
        }

        const action = button.getAttribute('data-action');

        // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA (ELIMINAR ACCIONES DEL MODAL) ▼▼▼ ---
        /*
        if (action === 'toggleGroupSelectModal') {
            // ... LÓGICA ELIMINADA ...
        }

        if (action === 'closeGroupSelectModal') {
            // ... LÓGICA ELIMINADA ...
        }
        */
        // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---
        
        // --- ▼▼▼ INICIO DE LÓGICA MODIFICADA (SELECTOR DE ITEM) ▼▼▼ ---
        // Listener para los items dentro del NUEVO popover de grupos
        const groupItem = event.target.closest('.group-select-item');
        if (groupItem) { // <-- Se quita la comprobación de groupSelectModal
            event.preventDefault();
            
            // --- NUEVA LÓGICA ---
            const groupName = groupItem.dataset.groupName; // Será "Grupo A" o undefined
            const groupI18nKey = groupItem.dataset.i18nKey; // Será "toolbar.noGroupSelected" o undefined
            // --- FIN NUEVA LÓGICA ---
            
            if (groupDisplay && groupDisplayText) {
                
                // --- LÓGICA REVISADA ---
                if (groupI18nKey) {
                    // Es el item "Ningún grupo"
                    const translatedText = getTranslation(groupI18nKey); // Obtener traducción JS
                    groupDisplayText.textContent = translatedText; // Poner el texto traducido
                    groupDisplayText.setAttribute('data-i18n', groupI18nKey); // Restaurar el atributo i18n
                    groupDisplay.classList.remove('active');
                } else {
                    // Es un item de grupo real
                    groupDisplayText.textContent = groupName;
                    groupDisplayText.removeAttribute('data-i18n'); // Quitar i18n para que no se sobrescriba
                    groupDisplay.classList.add('active');
                }
                // --- FIN LÓGICA REVISADA ---
            }
            
            // Cerrar el popover (que es un módulo estándar)
            deactivateAllModules();
            return;
        }
        // --- ▲▲▲ FIN DE LÓGICA MODIFICADA ▲▲▲ ---


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
             // --- ▼▼▼ LÓGICA MODIFICADA (ELIMINAR IF) ▼▼▼ ---
            // if (groupSelectModal) { ... } // <-- ELIMINADO
            // --- ▲▲▲ FIN LÓGICA MODIFICADA ▲▲▲ ---
            return;
        }

        const isSelectorLink = event.target.closest('[data-module="moduleTriggerSelect"] .menu-link');
        if (isSelectorLink) {
            return;
        }

        if (action.startsWith('toggle')) {
            
            // --- ▼▼▼ INICIO DE LÓGICA CORREGIDA ▼▼▼ ---
            // ESTA ES LA LÓGICA QUE FALTABA
            if (action === 'toggleModulePageFilter' || 
                action === 'toggleModuleAdminRole' || 
               action === 'toggleModuleAdminStatus' ||
                action === 'toggleModuleAdminCreateRole') { 
                // Estas acciones se manejan en admin-manager.js, así que las ignoramos aquí
                return; 
            }
            
            // Si la acción es 'toggleModuleGroupSelect', NO se ignora y continúa.
            // --- ▲▲▲ FIN DE LÓGICA CORREGIDA ▲▲▲ ---

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
            
            const clickedOnCardItem = event.target.closest('.card-item');

            // --- ▼▼▼ MODIFICACIÓN: ELIMINAR CHECK DE MODAL ▼▼▼ ---
            if (!clickedOnModule && !clickedOnButton && !clickedOnCardItem) {
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                deactivateAllModules();
            }
        });
    }

    if (closeOnEscape) {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                deactivateAllModules();
                // --- ▼▼▼ LÓGICA MODIFICADA (ELIMINAR IF) ▼▼▼ ---
                // if (groupSelectModal) { ... } // <-- ELIMINADO
                // --- ▲▲▲ FIN LÓGICA MODIFICADA ▲▲▲ ---
            }
        });
    }

}

export { deactivateAllModules, initMainController };