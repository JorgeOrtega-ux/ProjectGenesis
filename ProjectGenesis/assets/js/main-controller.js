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
        button.addEventListener('click', function (event) {
            const action = this.getAttribute('data-action');

            // --- MODIFICACIÓN: AÑADIR CASO DE LOGOUT ---
            if (action === 'logout') {
                // Usar la variable global de index.php
                window.location.href = (window.projectBasePath || '') + '/logout.php';
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