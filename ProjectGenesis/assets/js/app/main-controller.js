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
    
    // --- ▼▼▼ ¡CORRECCIÓN! (ELIMINADO DE AQUÍ) ▼▼▼ ---
    // NO podemos cachear estas variables aquí, porque #selected-group-display
    // no existe hasta que 'home.php' se carga dinámicamente.
    // const groupDisplay = document.getElementById('selected-group-display'); // <-- ELIMINADO
    // const groupDisplayText = groupDisplay ? groupDisplay.querySelector('.toolbar-group-text') : null; // <-- ELIMINADO
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

    document.body.addEventListener('click', async function (event) {
        
        // --- ▼▼▼ ¡LÓGICA CORREGIDA Y MEJORADA! ▼▼▼ ---
        // 1. Comprobar si se hizo clic en un item de grupo (esto debe ir primero)
        const groupItem = event.target.closest('.group-select-item');
        if (groupItem) { 
            event.preventDefault();
            
            // 2. Buscar los elementos de la toolbar AHORA, en el momento del clic.
            const groupDisplay = document.getElementById('selected-group-display');
            const groupDisplayText = groupDisplay ? groupDisplay.querySelector('.toolbar-group-text') : null;
            
            // 3. Obtener los datos del item clickeado
            const groupName = groupItem.dataset.groupName; 
            const groupI18nKey = groupItem.dataset.i18nKey; 
            
            // 4. Actualizar la barra de herramientas (el "box")
            if (groupDisplay && groupDisplayText) {
                if (groupI18nKey) {
                    // Caso: "Ningún grupo"
                    const translatedText = getTranslation(groupI18nKey);
                    groupDisplayText.textContent = translatedText;
                    groupDisplayText.setAttribute('data-i18n', groupI18nKey);
                    groupDisplay.classList.remove('active');
                } else {
                    // Caso: Un grupo real
                    groupDisplayText.textContent = groupName;
                    groupDisplayText.removeAttribute('data-i18n');
                    groupDisplay.classList.add('active');
                }
            }

            // 5. Actualizar el estado "active" dentro del popover (para el check)
            const menuList = groupItem.closest('.menu-list');
            if (menuList) {
                // Quitar 'active' y 'check' de todos
                menuList.querySelectorAll('.menu-link.group-select-item').forEach(link => {
                    link.classList.remove('active');
                    const icon = link.querySelector('.menu-link-check-icon');
                    if (icon) icon.innerHTML = '';
                });

                // Poner 'active' y 'check' solo en el clickeado
                groupItem.classList.add('active');
                const iconContainer = groupItem.querySelector('.menu-link-check-icon');
                if (iconContainer) {
                    iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';
                }
            }
            
            // 6. Cerrar el popover
            deactivateAllModules();
            return; // Terminar
        }
        // --- ▲▲▲ ¡FIN DE LA LÓGICA CORREGIDA! ▲▲▲ ---
        

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