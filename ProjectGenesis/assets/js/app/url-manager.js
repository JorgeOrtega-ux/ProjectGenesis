// RUTA: assets/js/app/url-manager.js
// (CÓDIGO MODIFICADO)

import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from '../modules/auth-manager.js';
import { applyTranslations, getTranslation } from '../services/i18n-manager.js';
// --- ▼▼▼ INICIO DE LA CORRECCIÓN (IMPORTAR) ▼▼▼ ---
import { hideTooltip } from '../services/tooltip-manager.js';
// --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentMenuType = null; 

const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',
    'toggleSectionMaintenance': 'maintenance', // <-- ¡NUEVA LÍNEA!
    'toggleSectionServerFull': 'server-full', // <--- AÑADE ESTA LÍNEA

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
    
    'toggleSectionAdminDashboard': 'admin-dashboard',
    'toggleSectionAdminManageUsers': 'admin-manage-users', 
    'toggleSectionAdminCreateUser': 'admin-create-user', 
    'toggleSectionAdminEditUser': 'admin-edit-user', 
    'toggleSectionAdminServerSettings': 'admin-server-settings', // <-- ¡NUEVA LÍNEA!

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    'toggleSectionAdminManageBackups': 'admin-manage-backups',
    // 'toggleSectionAdminRestoreBackup': 'admin-restore-backup', // <-- ¡ELIMINADA!
    'toggleSectionAdminManageLogs': 'admin-manage-logs', // <-- ¡NUEVA LÍNEA!
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // --- ▼▼▼ INICIO DE NUEVAS RUTAS (HELP) ▼▼▼ ---
    'toggleSectionHelpLegalNotice': 'help-legal-notice',
    'toggleSectionHelpPrivacyPolicy': 'help-privacy-policy',
    'toggleSectionHelpCookiesPolicy': 'help-cookies-policy',
    'toggleSectionHelpTermsConditions': 'help-terms-conditions',
    'toggleSectionHelpSendFeedback': 'help-send-feedback',
    // --- ▲▲▲ FIN DE NUEVAS RUTAS (HELP) ▲▲▲ ---
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    '/maintenance': 'toggleSectionMaintenance', // <-- ¡NUEVA LÍNEA!
    '/server-full': 'toggleSectionServerFull', // <--- AÑADE ESTA LÍNEA

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
    
    '/admin/dashboard': 'toggleSectionAdminDashboard',
    '/admin/manage-users': 'toggleSectionAdminManageUsers', 
    '/admin/create-user': 'toggleSectionAdminCreateUser', 
    '/admin/edit-user': 'toggleSectionAdminEditUser', 
    '/admin/server-settings': 'toggleSectionAdminServerSettings', // <-- ¡NUEVA LÍNEA!

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    '/admin/manage-backups': 'toggleSectionAdminManageBackups',
    // '/admin/restore-backup': 'toggleSectionAdminRestoreBackup', // <-- ¡ELIMINADA!
    '/admin/manage-logs': 'toggleSectionAdminManageLogs', // <-- ¡NUEVA LÍNEA!
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // --- ▼▼▼ INICIO DE NUEVAS RUTAS (HELP) ▼▼▼ ---
    '/help/legal-notice': 'toggleSectionHelpLegalNotice',
    '/help/privacy-policy': 'toggleSectionHelpPrivacyPolicy',
    '/help/cookies-policy': 'toggleSectionHelpCookiesPolicy',
    '/help/terms-conditions': 'toggleSectionHelpTermsConditions',
    '/help/send-feedback': 'toggleSectionHelpSendFeedback',
    // --- ▲▲▲ FIN DE NUEVAS RUTAS (HELP) ▲▲▲ ---
};

const basePath = window.projectBasePath || '/ProjectGenesis';


async function loadPage(page, action, fetchParams = null) {

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

    
    const isSettingsPage = page.startsWith('settings-');
    const isAdminPage = page.startsWith('admin-');
    const isHelpPage = page.startsWith('help-'); // <-- ¡NUEVA LÍNEA!
    
    let menuType = 'main';
    if (isSettingsPage) {
        menuType = 'settings';
    } else if (isAdminPage) {
        menuType = 'admin';
    } else if (isHelpPage) { // <-- ¡NUEVO BLOQUE!
        menuType = 'help';
    }

    if (currentMenuType === null || currentMenuType !== menuType) {
        currentMenuType = menuType; 
        
        // ▼▼▼ CAMBIO AQUÍ ▼▼▼
        fetch(`${basePath}/config/routing/menu_router.php?type=${menuType}`)
        // ▲▲▲ FIN DEL CAMBIO ▲▲▲
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


    try {
        let queryString = '';

        if (fetchParams) {
            queryString = new URLSearchParams(fetchParams).toString().replace(/\+/g, '%20');
        
        } else {
            const browserQuery = window.location.search;
            if (browserQuery) {
                queryString = browserQuery.substring(1); 
            }
        }
        
        // ▼▼▼ CAMBIO AQUÍ ▼▼▼
        const fetchUrl = `${basePath}/config/routing/router.php?page=${page}${queryString ? `&${queryString}` : ''}`;
        // ▲▲▲ FIN DEL CAMBIO ▲▲▲

        const response = await fetch(fetchUrl);
        
        const html = await response.text();

        contentContainer.innerHTML = html;
        applyTranslations(contentContainer);

        // --- ▼▼▼ INICIO DE MODIFICACIÓN (FIX CONTEO) ▼▼▼ ---
        // Después de cargar el HTML y las traducciones,
        // aplicar el conteo de usuarios que pudo llegar antes.
        if (page === 'admin-server-settings') {
            if (window.lastKnownUserCount !== null) {
                const display = document.getElementById('concurrent-users-display');
                if (display) {
                    display.textContent = window.lastKnownUserCount;
                    display.setAttribute('data-i18n', '');
                }
            }
        }
        // --- ▲▲▲ FIN DE MODIFICACIÓN (FIX CONTEO) ▲▲▲ ---

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
        history.replaceState(null, '', `${basePath}/settings/your-profile`);
        path = '/settings/your-profile';
    }
    
    if (path === '/admin') {
        history.replaceState(null, '', `${basePath}/admin/dashboard`);
        path = '/admin/dashboard';
    }

    const action = paths[path];

    if (!action) {
        loadPage('404', null); 
        return;
    }

    const page = routes[action];

    if (page) {
        loadPage(page, action); 
    } else {
        loadPage('404', null);
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
    
    if (currentAction === 'toggleSectionAdminManageUsers') { 
        menuAction = 'toggleSectionAdminManageUsers'; 
    }
    if (currentAction === 'toggleSectionAdminCreateUser') {
        menuAction = 'toggleSectionAdminCreateUser';
    }
    if (currentAction === 'toggleSectionAdminEditUser') {
        menuAction = 'toggleSectionAdminManageUsers';
    }
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (MODO MANTENIMIENTO) ▼▼▼ ---
    if (currentAction === 'toggleSectionAdminServerSettings') {
        menuAction = 'toggleSectionAdminServerSettings';
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
    if (currentAction === 'toggleSectionAdminManageBackups') {
        menuAction = 'toggleSectionAdminManageBackups';
    }
    // La regla para 'toggleSectionAdminRestoreBackup' se ha eliminado
    
    // --- ¡NUEVA LÍNEA! ---
    // No necesitamos una acción de menú, pero si quisiéramos resaltarla:
    if (currentAction === 'toggleSectionAdminManageLogs') {
         menuAction = 'toggleSectionAdminDashboard'; // Resalta 'Dashboard'
    }
    // --- FIN NUEVA LÍNEA ---
    
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▼▼▼ ---

    // --- ▼▼▼ INICIO DE NUEVO BLOQUE (HELP) ▼▼▼ ---
    if (currentAction && currentAction.startsWith('toggleSectionHelp')) {
        menuAction = currentAction;
    }
    // --- ▲▲▲ FIN DE NUEVO BLOQUE (HELP) ▲▲▲ ---


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
      const link = e.target.closest(
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (AÑADIR /help) ▼▼▼ ---
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/admin"], a[href*="/help"], .component-button[data-action*="toggleSection"], .page-toolbar-button[data-action*="toggleSection"], a[href*="/maintenance"], a[href*="/admin/manage-backups"]'
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
        );

        if (link) {
            
            // --- ▼▼▼ INICIO DE LA CORRECCIÓN (LLAMAR) ▼▼▼ ---
            // Ocultar cualquier tooltip abierto tan pronto como se haga clic en un enlace de navegación.
            hideTooltip();
            // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

            if (link.classList.contains('component-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) { 
                return;
            }


            e.preventDefault();

            let action, page, newPath;

            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');

                // --- ▼▼▼ ¡ESTA ES LA CORRECCIÓN! ▼▼▼ ---
                if (action === 'toggleSectionAdminEditUser') {
                    // Esta acción es especial y la maneja 'admin-manager.js'
                    // porque necesita el ID del usuario seleccionado.
                    // Detenemos este listener para que el otro pueda actuar.
                    e.stopImmediatePropagation();
                    return; 
                }
                // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
                
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (ELIMINADO) ▼▼▼ ---
                // La regla 'toggleSectionAdminRestoreBackup' se ha eliminado
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


                page = routes[action];
                newPath = Object.keys(paths).find(key => paths[key] === action);
            } else {
                const url = new URL(link.href);
                newPath = url.pathname.replace(basePath, '') || '/';
                
                if (newPath === '/settings') {
                    newPath = '/settings/your-profile';
                }
                
                if (newPath === '/admin') {
                    newPath = '/admin/dashboard';
                }
                
                action = paths[newPath];
                page = routes[action];
            }
            
            const url = link.href ? new URL(link.href) : null;
            if (link.hasAttribute('data-nav-js') && url && url.search) {
                 if (newPath.includes('?')) {
                    newPath += "&" + url.search.substring(1);
                } else {
                    newPath += url.search;
                }
            }

            if (!page) {
                if(link.tagName === 'A' && !link.TAgName('data-action')) {
                    window.location.href = link.href;
                }
                return;
            }

            const fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}`;
            
            const currentFullUrl = window.location.pathname + window.location.search;
            if (currentFullUrl !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);
                loadPage(page, action); 
            }

            deactivateAllModules();
        }
    });

    window.addEventListener('popstate', handleNavigation);

    const initialPath = window.location.pathname.replace(basePath, '') || '/';
    let initialMenuType = 'main';
    if (initialPath.startsWith('/settings')) {
        initialMenuType = 'settings';
    } else if (initialPath.startsWith('/admin')) {
        initialMenuType = 'admin';
    } else if (initialPath.startsWith('/help')) { // <-- ¡NUEVA LÍNEA!
        initialMenuType = 'help';
    }
    currentMenuType = initialMenuType;

    handleNavigation();
}

export { loadPage };