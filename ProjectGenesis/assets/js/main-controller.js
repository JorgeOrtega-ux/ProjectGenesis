import { getTranslation } from './i18n-manager.js';

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

    document.body.addEventListener('click', async function (event) {
        
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
    
    
    
}