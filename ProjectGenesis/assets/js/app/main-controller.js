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
    
    // --- ▼▼▼ INICIO DE LÓGICA AÑADIDA PARA MODAL DE GRUPOS ▼▼▼ ---
    const groupSelectModal = document.getElementById('group-select-modal');
    const groupSelectBtn = document.getElementById('select-group-btn');
    const groupDisplay = document.getElementById('selected-group-display');
    const groupDisplayText = groupDisplay ? groupDisplay.querySelector('.toolbar-group-text') : null;
    // --- ▲▲▲ FIN DE LÓGICA AÑADIDA ▲▲▲ ---

    document.body.addEventListener('click', async function (event) {
        
        
        const button = event.target.closest('[data-action]');

        if (button) {
            hideTooltip();
        }

        if (!button) {
            // --- ▼▼▼ INICIO DE LÓGICA AÑADIDA (CERRAR MODAL AL CLICAR FUERA) ▼▼▼ ---
            // Si se hace clic fuera de un botón y es en el overlay del modal de grupos
            if (groupSelectModal && event.target === groupSelectModal) {
                groupSelectModal.classList.add('disabled');
                groupSelectModal.classList.remove('active');
            }
            // --- ▲▲▲ FIN DE LÓGICA AÑADIDA ▲▲▲ ---
            return;
        }

        const action = button.getAttribute('data-action');

        // --- ▼▼▼ INICIO DE LÓGICA AÑADIDA PARA MODAL DE GRUPOS ▼▼▼ ---
        if (action === 'toggleGroupSelectModal') {
            event.preventDefault();
            if (groupSelectModal) {
                groupSelectModal.classList.remove('disabled');
                groupSelectModal.classList.add('active');
            }
            return; 
        }

        if (action === 'closeGroupSelectModal') {
            event.preventDefault();
            if (groupSelectModal) {
                groupSelectModal.classList.add('disabled');
                groupSelectModal.classList.remove('active');
            }
            return;
        }
        
        // Listener para los items dentro del modal de grupos
        const groupItem = event.target.closest('.group-modal-item');
        if (groupItem && groupSelectModal) {
            event.preventDefault();
            const groupName = groupItem.dataset.groupName;
            
            if (groupDisplay && groupDisplayText) {
                groupDisplayText.textContent = groupName;
                groupDisplayText.removeAttribute('data-i18n'); // Quitar i18n para que no se sobrescriba
                groupDisplay.classList.add('active');
            }
            
            // Cerrar el modal
            groupSelectModal.classList.add('disabled');
            groupSelectModal.classList.remove('active');
            
            // Cerrar el menú lateral (si estuviera abierto en móvil)
            deactivateAllModules();
            return;
        }
        // --- ▲▲▲ FIN DE LÓGICA AÑADIDA ▲▲▲ ---


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
             // --- ▼▼▼ LÓGICA AÑADIDA (CERRAR MODAL AL NAVEGAR) ▼▼▼ ---
            if (groupSelectModal) {
                groupSelectModal.classList.add('disabled');
                groupSelectModal.classList.remove('active');
            }
            // --- ▲▲▲ FIN DE LÓGICA AÑADIDA ▲▲▲ ---
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
            
            const clickedOnCardItem = event.target.closest('.card-item');

            // --- ▼▼▼ MODIFICACIÓN: NO CERRAR SI SE HACE CLIC EN EL MODAL DE GRUPO ▼▼▼ ---
            const clickedOnGroupModal = event.target.closest('#group-select-modal');
            if (!clickedOnModule && !clickedOnButton && !clickedOnCardItem && !clickedOnGroupModal) {
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                deactivateAllModules();
            }
        });
    }

    if (closeOnEscape) {
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                deactivateAllModules();
                // --- ▼▼▼ LÓGICA AÑADIDA (CERRAR MODAL CON ESCAPE) ▼▼▼ ---
                if (groupSelectModal) {
                    groupSelectModal.classList.add('disabled');
                    groupSelectModal.classList.remove('active');
                }
                // --- ▲▲▲ FIN DE LÓGICA AÑADIDA ▲▲▲ ---
            }
        });
    }

}

export { deactivateAllModules, initMainController };