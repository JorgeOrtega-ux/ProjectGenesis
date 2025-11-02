import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from '../modules/auth-manager.js';
import { applyTranslations, getTranslation } from '../i18n-manager.js';

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
// --- ▼▼▼ MODIFICACIÓN: Renombrar variable ▼▼▼ ---
let currentMenuType = null; 
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

const routes = {
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
    
    'toggleSectionSettingsPassword': 'settings-change-password',
    'toggleSectionSettingsChangeEmail': 'settings-change-email',
    'toggleSectionSettingsToggle2fa': 'settings-toggle-2fa',
    'toggleSectionSettingsDeleteAccount': 'settings-delete-account',

    'toggleSectionAccountStatusDeleted': 'account-status-deleted',
    'toggleSectionAccountStatusSuspended': 'account-status-suspended',
    
    // --- ▼▼▼ RUTAS DE ADMIN MODIFICADAS ▼▼▼ ---
    'toggleSectionAdminDashboard': 'admin-dashboard',
    'toggleSectionAdminManageUsers': 'admin-manage-users', // <--- RUTA MODIFICADA
    // --- ▲▲▲ FIN DE RUTAS DE ADMIN ▲▲▲ ---
};

const paths = {
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
    
    '/settings/change-password': 'toggleSectionSettingsPassword',
    '/settings/change-email': 'toggleSectionSettingsChangeEmail',
    '/settings/toggle-2fa': 'toggleSectionSettingsToggle2fa',
    '/settings/delete-account': 'toggleSectionSettingsDeleteAccount',

    '/account-status/deleted': 'toggleSectionAccountStatusDeleted',
    '/account-status/suspended': 'toggleSectionAccountStatusSuspended',
    
    // --- ▼▼▼ PATHS DE ADMIN MODIFICADOS ▼▼▼ ---
    '/admin/dashboard': 'toggleSectionAdminDashboard',
    '/admin/manage-users': 'toggleSectionAdminManageUsers', // <--- PATH MODIFICADO
    // --- ▲▲▲ FIN DE PATHS DE ADMIN ▲▲▲ ---
};

const basePath = window.projectBasePath || '/ProjectGenesis';


// --- ▼▼▼ MODIFICACIÓN: Lógica de loadPage actualizada ▼▼▼ ---
async function loadPage(page, action) {

    if (!contentContainer) return;

    
    const headerTop = document.querySelector('.general-content-top');
    
    if (headerTop) {
        headerTop.classList.remove('shadow');
    }


    contentContainer.innerHTML = ''; 
    if (loaderTimer) {
        clearTimeout(loaderTimer);
    }
    loaderTimer = setTimeout(() => {
        if (pageLoader) {
            pageLoader.classList.add('active');
        }
    }, 200);

    
    // Determinar qué tipo de página es (main, settings, o admin)
    const isSettingsPage = page.startsWith('settings-');
    const isAdminPage = page.startsWith('admin-');
    
    let menuType = 'main';
    if (isSettingsPage) {
        menuType = 'settings';
    } else if (isAdminPage) {
        menuType = 'admin';
    }

    // Cargar el menú lateral correcto si el tipo de menú cambió
    if (currentMenuType === null || currentMenuType !== menuType) {
        currentMenuType = menuType; 
        
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
                    
                    updateMenuState(action); 
                }
            })
            .catch(err => console.error('Error al cargar el menú lateral:', err));
    } else {
        updateMenuState(action);
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    try {
        // --- ▼▼▼ MODIFICACIÓN: Añadir query string al fetch ▼▼▼ ---
        const queryString = window.location.search;
        const fetchUrl = `${basePath}/config/router.php?page=${page}${queryString ? queryString.replace('?', '&') : ''}`;
        const response = await fetch(fetchUrl);
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        
        const html = await response.text();

        contentContainer.innerHTML = html;
        applyTranslations(contentContainer);

        let link;
        if (page === 'register-step3') {
            link = document.getElementById('register-resend-code-link');
        } else if (page === 'reset-step2') {
            link = document.getElementById('reset-resend-code-link');
        } else if (page === 'settings-change-email') { 
            link = document.getElementById('email-verify-resend');
        }

        if (link) {
            const cooldownSeconds = parseInt(link.dataset.cooldown || '0', 10);
            if (cooldownSeconds > 0) {
                startResendTimer(link, cooldownSeconds);
            }
        }

        
        
        const headerTopForListener = document.querySelector('.general-content-top');
        
        const newScrollableContent = contentContainer.querySelector('.section-content.overflow-y');

        if (newScrollableContent && headerTopForListener) {
            
            newScrollableContent.addEventListener('scroll', function() {
                
                if (this.scrollTop > 0) {
                    headerTopForListener.classList.add('shadow');
                } else {
                    headerTopForListener.classList.remove('shadow');
                }
            });
        }
        
    } catch (error) {
        console.error('Error al cargar la página:', error);
        contentContainer.innerHTML = `<h2>${getTranslation('js.url.errorLoad')}</h2>`;
    } finally {
        if (loaderTimer) {
            clearTimeout(loaderTimer);
            loaderTimer = null;
        }
        if (pageLoader) {
            pageLoader.classList.remove('active');
        }
    }
}

export function handleNavigation() {

    let path = window.location.pathname.replace(basePath, '');
    if (path === '' || path === '/') path = '/';

    if (path === '/settings') {
        path = '/settings/your-profile';
        history.replaceState(null, '', `${basePath}${path}`);
    }
    
    // --- ▼▼▼ NUEVA REGLA DE REDIRECCIÓN ▼▼▼ ---
    if (path === '/admin') {
        path = '/admin/dashboard';
        history.replaceState(null, '', `${basePath}${path}`);
    }
    // --- ▲▲▲ FIN DE NUEVA REGLA ▲▲▲ ---

    const action = paths[path];

    if (!action) {
        // --- ▼▼▼ MODIFICACIÓN: Simplificado ▼▼▼ ---
        loadPage('404', null); 
        return;
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }

    const page = routes[action];

    if (page) {
        // --- ▼▼▼ MODIFICACIÓN: Simplificado ▼▼▼ ---
        loadPage(page, action); 
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    } else {
        // --- ▼▼▼ MODIFICACIÓN: Simplificado ▼▼▼ ---
        loadPage('404', null);
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    }
}

function updateMenuState(currentAction) {
    
    let menuAction = currentAction;
    if (currentAction && currentAction.startsWith('toggleSectionRegister')) menuAction = 'toggleSectionRegister';
    if (currentAction && currentAction.startsWith('toggleSectionReset')) menuAction = 'toggleSectionResetPassword';

    if (currentAction === 'toggleSectionSettingsPassword' || 
        currentAction === 'toggleSectionSettingsToggle2fa' ||
        currentAction === 'toggleSectionSettingsDeleteAccount') {
        menuAction = 'toggleSectionSettingsLogin';
    }
    if (currentAction === 'toggleSectionSettingsChangeEmail') {
        menuAction = 'toggleSectionSettingsProfile';
    }
    
    // --- ▼▼▼ LÓGICA DE ADMIN MODIFICADA ▼▼▼ ---
    if (currentAction === 'toggleSectionAdminManageUsers') { // <--- ACCIÓN MODIFICADA
        menuAction = 'toggleSectionAdminManageUsers'; // <--- ACCIÓN MODIFICADA
    }
    // --- ▲▲▲ FIN DE LÓGICA DE ADMIN ▲▲▲ ---


    document.querySelectorAll('.module-surface .menu-link').forEach(link => {
        const linkAction = link.getAttribute('data-action');

        if (linkAction === menuAction) { 
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}


export function initRouter() {

    document.body.addEventListener('click', e => {
        // --- ▼▼▼ MODIFICACIÓN: Añadir selector de admin ▼▼▼ ---
        const link = e.target.closest(
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/admin"], .component-button[data-action*="toggleSection"]'
        );
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        if (link) {
            if (link.classList.contains('component-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) { // MODIFICADO
                return;
            }


            e.preventDefault();

            let action, page, newPath;

            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');
                page = routes[action];
                newPath = Object.keys(paths).find(key => paths[key] === action);
            } else {
                const url = new URL(link.href);
                newPath = url.pathname.replace(basePath, '') || '/';
                
                if (newPath === '/settings') {
                    newPath = '/settings/your-profile';
                }
                
                // --- ▼▼▼ NUEVA REGLA ▼▼▼ ---
                if (newPath === '/admin') {
                    newPath = '/admin/dashboard';
                }
                // --- ▲▲▲ FIN DE NUEVA REGLA ▲▲▲ ---
                
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
                
                // --- ▼▼▼ MODIFICACIÓN: Simplificado ▼▼▼ ---
                history.pushState(null, '', fullUrlPath);
                
                loadPage(page, action); 
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            }

            deactivateAllModules();
        }
    });

    window.addEventListener('popstate', handleNavigation);

    // --- ▼▼▼ MODIFICACIÓN: Lógica de Carga Inicial ▼▼▼ ---
    const initialPath = window.location.pathname.replace(basePath, '') || '/';
    let initialMenuType = 'main';
    if (initialPath.startsWith('/settings')) {
        initialMenuType = 'settings';
    } else if (initialPath.startsWith('/admin')) {
        initialMenuType = 'admin';
    }
    currentMenuType = initialMenuType;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    handleNavigation();
}