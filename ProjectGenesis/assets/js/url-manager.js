import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from './auth-manager.js';
import { applyTranslations, getTranslation } from './i18n-manager.js';

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentIsSettings = null; 

const routes = {
    // ... (sin cambios)
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',

    'toggleSectionRegisterStep1': 'register-step1',
    'toggleSectionRegisterStep2': 'register-step2',
    'toggleSectionRegisterStep3': 'register-step3',

    'toggleSectionResetStep1': 'reset-step1',
    'toggleSectionResetStep2': 'reset-step2',
    'toggleSectionResetStep3': 'reset-step3',

    'toggleSectionSettingsProfile': 'settings-profile',
    'toggleSectionSettingsLogin': 'settings-login',
    'toggleSectionSettingsAccess': 'settings-accessibility',
    'toggleSectionSettingsDevices': 'settings-devices',
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    'toggleSectionSettingsPassword': 'settings-change-password',
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    'toggleSectionAccountStatusDeleted': 'account-status-deleted',
    'toggleSectionAccountStatusSuspended': 'account-status-suspended'
};

const paths = {
    // ... (sin cambios)
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',

    '/register': 'toggleSectionRegisterStep1',
    '/register/additional-data': 'toggleSectionRegisterStep2',
    '/register/verification-code': 'toggleSectionRegisterStep3',
    
    '/reset-password': 'toggleSectionResetStep1',
    '/reset-password/verify-code': 'toggleSectionResetStep2',
    '/reset-password/new-password': 'toggleSectionResetStep3',

    '/settings/your-profile': 'toggleSectionSettingsProfile',
    '/settings/login-security': 'toggleSectionSettingsLogin',
    '/settings/accessibility': 'toggleSectionSettingsAccess',
    '/settings/device-sessions': 'toggleSectionSettingsDevices',
    
    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    '/settings/change-password': 'toggleSectionSettingsPassword',
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    '/account-status/deleted': 'toggleSectionAccountStatusDeleted',
    '/account-status/suspended': 'toggleSectionAccountStatusSuspended'
};

const basePath = window.projectBasePath || '/ProjectGenesis';


// --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---

// 1. Modificar la firma para aceptar 'action'
async function loadPage(page, isSettingsPage, action) {

    if (!contentContainer) return;

    // --- Lógica del Loader (sin cambios) ---
    contentContainer.innerHTML = ''; 
    if (loaderTimer) {
        clearTimeout(loaderTimer);
    }
    loaderTimer = setTimeout(() => {
        if (pageLoader) {
            pageLoader.classList.add('active');
        }
    }, 200);

    
    // --- Lógica de Carga de Menú ---
    if (currentIsSettings === null || currentIsSettings !== isSettingsPage) {
        currentIsSettings = isSettingsPage; 
        const menuType = isSettingsPage ? 'settings' : 'main';
        
        fetch(`${basePath}/config/menu_router.php?type=${menuType}`)
            .then(res => res.text())
            .then(menuHtml => {
                const oldMenu = document.querySelector('[data-module="moduleSurface"]');
                if (oldMenu) {
                    oldMenu.outerHTML = menuHtml;
                    const newMenu = document.querySelector('[data-module="moduleSurface"]');
                    if (newMenu) {
                        applyTranslations(newMenu);
                    }
                    
                    // 2. LLAMAR A updateMenuState DESPUÉS de que el nuevo menú se inyectó
                    updateMenuState(action); 
                }
            })
            .catch(err => console.error('Error al cargar el menú lateral:', err));
    } else {
        // 3. LLAMAR A updateMenuState si el menú NO cambió (ej. settings -> settings)
        updateMenuState(action);
    }
    // --- FIN DE LA LÓGICA DE MENÚ ---


    // --- Lógica de Carga de Página (con modificación) ---
    try {
        const response = await fetch(`${basePath}/config/router.php?page=${page}`);
        const html = await response.text();

        contentContainer.innerHTML = html;
        applyTranslations(contentContainer);

        // --- INICIO DE BLOQUE MODIFICADO ---
        // Ahora revisa tanto 'register-step3' como 'reset-step2'
        if (page === 'register-step3' || page === 'reset-step2') {
            let link;
            if (page === 'register-step3') {
                link = document.getElementById('register-resend-code-link');
            } else if (page === 'reset-step2') {
                link = document.getElementById('reset-resend-code-link');
            }

            if (link) {
                const cooldownSeconds = parseInt(link.dataset.cooldown || '0', 10);
                if (cooldownSeconds > 0) {
                    startResendTimer(link, cooldownSeconds);
                }
            }
        }
        // --- FIN DE BLOQUE MODIFICADO ---
        
    } catch (error) {
        console.error('Error al cargar la página:', error);
        contentContainer.innerHTML = `<h2>${getTranslation('js.url.errorLoad')}</h2>`;
    } finally {
        // --- Lógica de limpieza del Loader (sin cambios) ---
        if (loaderTimer) {
            clearTimeout(loaderTimer);
            loaderTimer = null;
        }
        if (pageLoader) {
            pageLoader.classList.remove('active');
        }
    }
}
// --- ▲▲▲ FIN DE LA MODIFICACIÓN (FUNCIÓN loadPage) ▲▲▲ ---

export function handleNavigation() {

    let path = window.location.pathname.replace(basePath, '');
    if (path === '' || path === '/') path = '/';

    if (path === '/settings') {
        path = '/settings/your-profile';
        history.replaceState(null, '', `${basePath}${path}`);
    }

    const action = paths[path];

    if (!action) {
        const isSettings = path.startsWith('/settings');
        // 4. Pasar 'action' (null en este caso) a loadPage
        loadPage('404', isSettings, null); 
        // 5. Eliminar la llamada de aquí
        // updateMenuState(null); 
        return;
    }

    const page = routes[action];

    if (page) {
        const isSettings = path.startsWith('/settings');
        // 6. Pasar 'action' a loadPage
        loadPage(page, isSettings, action); 
        
        // 7. Eliminar la llamada de aquí
        // let menuAction = action;
        // if (action.startsWith('toggleSectionRegister')) menuAction = 'toggleSectionRegister';
        // if (action.startsWith('toggleSectionReset')) menuAction = 'toggleSectionResetPassword'; 
        // updateMenuState(menuAction);
    } else {
        const isSettings = path.startsWith('/settings');
        // 8. Pasar 'action' (null en este caso) a loadPage
        loadPage('404', isSettings, null);
        // 9. Eliminar la llamada de aquí
        // updateMenuState(null);
    }
}

function updateMenuState(currentAction) {
    
    // 10. (Lógica de alias para register/reset)
    // Esto lo podemos mover aquí para que funcione correctamente
    let menuAction = currentAction;
    if (currentAction && currentAction.startsWith('toggleSectionRegister')) menuAction = 'toggleSectionRegister';
    if (currentAction && currentAction.startsWith('toggleSectionReset')) menuAction = 'toggleSectionResetPassword';

    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    // Si estamos en la página de cambiar contraseña, resaltar "Login y Seguridad"
    if (currentAction === 'toggleSectionSettingsPassword') {
        menuAction = 'toggleSectionSettingsLogin';
    }
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


    document.querySelectorAll('.module-surface .menu-link').forEach(link => {
        const linkAction = link.getAttribute('data-action');

        if (linkAction === menuAction) { // Usar la variable 'menuAction'
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}


export function initRouter() {

    document.body.addEventListener('click', e => {
        const link = e.target.closest(
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (Añadido 'a[href*="/settings/"]') ▼▼▼ ---
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/settings/"], a[data-nav-js], .settings-button[data-action*="toggleSection"]'
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
        );

        if (link) {
            // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (Evitar que los botones de settings recarguen la página) ▼▼▼ ---
            // Si es un botón de settings que no sea un data-action, no hacer nada
            if (link.classList.contains('settings-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) {
                return;
            }
            // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---


            e.preventDefault();

            let action, page, newPath;

            if (link.hasAttribute('data-action')) {
                // ... (sin cambios)
                action = link.getAttribute('data-action');
                page = routes[action];
                newPath = Object.keys(paths).find(key => paths[key] === action);
            } else {
                // ... (sin cambios)
                const url = new URL(link.href);
                newPath = url.pathname.replace(basePath, '') || '/';
                
                if (newPath === '/settings') {
                    newPath = '/settings/your-profile';
                }
                
                action = paths[newPath];
                page = routes[action];
            }

            if (!page) {
                if(link.tagName === 'A' && !link.hasAttribute('data-action')) {
                    window.location.href = link.href;
                }
                return;
            }

            const fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}`;

            if (window.location.pathname !== fullUrlPath) {
                
                const isGoingToSettings = newPath.startsWith('/settings');

                history.pushState(null, '', fullUrlPath);
                
                // 11. Pasar 'action' a loadPage
                loadPage(page, isGoingToSettings, action); 
                
                // 12. Eliminar la llamada de aquí
                // updateMenuState(action);
            }

            deactivateAllModules();
        }
    });

    window.addEventListener('popstate', handleNavigation);

    const initialPath = window.location.pathname.replace(basePath, '') || '/';
    currentIsSettings = initialPath.startsWith('/settings') || initialPath.startsWith('/settings');

    handleNavigation();
}