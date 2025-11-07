// RUTA: assets/js/app/url-manager.js
// (CÓDIGO COMPLETO CORREGIDO)

import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from '../modules/auth-manager.js';
import { applyTranslations, getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentMenuType = null; 

const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionJoinGroup': 'join-group',
    'toggleSectionMyGroups': 'my-groups',
    'toggleSectionLogin': 'login',
    'toggleSectionMaintenance': 'maintenance', 
    'toggleSectionServerFull': 'server-full', 

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
    'toggleSectionAdminServerSettings': 'admin-server-settings', 

    'toggleSectionAdminManageBackups': 'admin-manage-backups',
    'toggleSectionAdminManageLogs': 'admin-manage-logs', 
    'toggleSectionAdminManageGroups': 'admin-manage-groups',
    
    'toggleSectionAdminEditGroup': 'admin-edit-group',

    'toggleSectionHelpLegalNotice': 'help-legal-notice',
    'toggleSectionHelpPrivacyPolicy': 'help-privacy-policy',
    'toggleSectionHelpCookiesPolicy': 'help-cookies-policy',
    'toggleSectionHelpTermsConditions': 'help-terms-conditions',
    'toggleSectionHelpSendFeedback': 'help-send-feedback',
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/join-group': 'toggleSectionJoinGroup',
    '/my-groups': 'toggleSectionMyGroups',
    '/login': 'toggleSectionLogin',
    '/maintenance': 'toggleSectionMaintenance', 
    '/server-full': 'toggleSectionServerFull', 

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

    '/account-status/deleted': 'account-status-deleted',
    '/account-status/suspended': 'account-status-suspended',
    
    '/admin/dashboard': 'toggleSectionAdminDashboard',
    '/admin/manage-users': 'toggleSectionAdminManageUsers', 
    '/admin/create-user': 'toggleSectionAdminCreateUser', 
    '/admin/edit-user': 'toggleSectionAdminEditUser', 
    '/admin/server-settings': 'admin-server-settings', 

    '/admin/manage-backups': 'toggleSectionAdminManageBackups',
    '/admin/manage-logs': 'admin-manage-logs', 
    '/admin/manage-groups': 'toggleSectionAdminManageGroups',

    '/admin/edit-group': 'toggleSectionAdminEditGroup',

    '/help/legal-notice': 'toggleSectionHelpLegalNotice',
    '/help/privacy-policy': 'toggleSectionHelpPrivacyPolicy',
    '/help/cookies-policy': 'toggleSectionHelpCookiesPolicy',
    '/help/terms-conditions': 'toggleSectionHelpTermsConditions',
    '/help/send-feedback': 'toggleSectionHelpSendFeedback',
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
    const isHelpPage = page.startsWith('help-'); 
    
    let menuType = 'main';
    if (isSettingsPage) {
        menuType = 'settings';
    } else if (isAdminPage) {
        menuType = 'admin';
    } else if (isHelpPage) { 
        menuType = 'help';
    }

    if (currentMenuType === null || currentMenuType !== menuType) {
        currentMenuType = menuType; 
        
        fetch(`${basePath}/config/routing/menu_router.php?type=${menuType}`)
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
        
        const params = new URLSearchParams(fetchParams || {});
        
        if (params.toString() === '') {
             const browserQuery = window.location.search;
            if (browserQuery) {
                queryString = browserQuery.substring(1); 
            }
        } else {
            queryString = params.toString();
        }
        
        const fetchUrl = `${basePath}/config/routing/router.php?page=${page}${queryString ? `&${queryString}` : ''}`;

        const response = await fetch(fetchUrl);
        
        const html = await response.text();

        contentContainer.innerHTML = html;
        applyTranslations(contentContainer);

        
        // --- (Se mantiene la corrección de Zona Horaria) ---
        function formatLocalTime(utcTimestamp) {
            try {
                const date = new Date(utcTimestamp.replace(' ', 'T') + 'Z');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${hours}:${minutes}`;
            } catch (e) {
                return "--:--";
            }
        }

        contentContainer.querySelectorAll('[data-utc-timestamp]').forEach(el => {
            const utcString = el.dataset.utcTimestamp;
            if (utcString) {
                el.textContent = formatLocalTime(utcString);
            }
        });
        

        if (window.applyOnlineStatusToAllMembers) {
            window.applyOnlineStatusToAllMembers();
        }

        if (page === 'admin-server-settings') {
            if (window.lastKnownUserCount !== null) {
                const display = document.getElementById('concurrent-users-display');
                if (display) {
                    display.textContent = window.lastKnownUserCount;
                    display.setAttribute('data-i18n', '');
                }
            }
        }

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
        
        if (page === 'home' && window.wsSend) {
            const groupUuid = fetchParams ? fetchParams.uuid : null;
            
            window.wsSend({ type: 'join_group', group_uuid: groupUuid });
            
            document.body.dataset.currentGroupUuid = groupUuid || '';
        }
        
        
        // --- ▼▼▼ INICIO DE CORRECCIÓN DE SCROLL (LA SOLUCIÓN) ▼▼▼ ---
        // Después de que todo se cargue, si estamos en 'home',
        // forzamos el scroll al fondo del contenedor.
        if (page === 'home') {
            const chatHistory = contentContainer.querySelector('#chat-history-container');
            if (chatHistory) {
                // CORRECCIÓN: scrollHeight es el FONDO (mensajes nuevos).
                // 0 es el TOPE (mensajes antiguos).
                chatHistory.scrollTop = chatHistory.scrollHeight; // <-- ¡ESTA ES LA CORRECCIÓN!
            }
        }
        // --- ▲▲▲ FIN DE CORRECCIÓN DE SCROLL (LA SOLUCIÓN) ▲▲▲ ---
        
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

    let action = paths[path];
    let fetchParams = null; 

    if (!action) {
        const groupMatch = path.match(/^\/c\/([a-f0-9\-]{36})$/i);
        if (groupMatch) {
            action = 'toggleSectionHome'; 
            fetchParams = { uuid: groupMatch[1] }; 
        }
    }

    if (!action) {
        loadPage('404', null); 
        return;
    }

    const page = routes[action];

    if (page) {
        loadPage(page, action, fetchParams); 
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
    if (currentAction === 'toggleSectionAdminServerSettings') {
        menuAction = 'toggleSectionAdminServerSettings';
    }

    if (currentAction === 'toggleSectionAdminManageBackups') {
        menuAction = 'toggleSectionAdminManageBackups';
    }
    if (currentAction === 'toggleSectionAdminManageLogs') {
         menuAction = 'toggleSectionAdminDashboard'; 
    }
    
    if (currentAction === 'toggleSectionAdminManageGroups') {
        menuAction = 'toggleSectionAdminManageGroups';
    }
    
    if (currentAction === 'toggleSectionAdminEditGroup') {
        menuAction = 'toggleSectionAdminManageGroups'; // Mantener "Gestionar Grupos" activo
    }
    
    if (currentAction && currentAction.startsWith('toggleSectionHelp')) {
        menuAction = currentAction;
    }


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
            'a[data-nav-js="true"], .header-button[data-action*="toggleSection"], .menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/admin"], a[href*="/help"], a[href*="/my-groups"], .component-button[data-action*="toggleSection"], .component-action-button[data-action*="toggleSection"], .page-toolbar-button[data-action*="toggleSection"], a[href*="/maintenance"]'
        );

        if (link) {
            
            hideTooltip();

            // Evitar que botones de componentes sin data-action lancen la navegación
            if (link.classList.contains('component-button') && !link.dataset.action) {
                return;
            }
            if (link.classList.contains('component-action-button') && !link.dataset.action) { 
                return;
            }

            e.preventDefault();
            
            const groupItem = e.target.closest('.group-select-item');
            if (groupItem) {
                // Dejar que main-controller.js maneje la navegación del grupo
                return;
            }

            let action, page, newPath, fetchParams = null;

            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');

                if (action === 'toggleSectionAdminEditUser' || action === 'toggleSectionAdminEditGroup') {
                    e.stopImmediatePropagation();
                    return; 
                }
                
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
                
                const groupMatch = newPath.match(/^\/c\/([a-f0-9\-]{36})$/i);
                if (groupMatch) {
                    action = 'toggleSectionHome';
                    page = routes[action];
                    fetchParams = { uuid: groupMatch[1] };
                } else {
                    action = paths[newPath];
                    page = routes[action];
                }
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
                if(link.tagName === 'A' && !link.dataset.action) {
                    window.location.href = link.href;
                }
                return;
            }

            const fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}`;
            
            const currentFullUrl = window.location.pathname + window.location.search;
            if (currentFullUrl !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);
                loadPage(page, action, fetchParams); // <-- Pasar fetchParams
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
    } else if (initialPath.startsWith('/help')) { 
        initialMenuType = 'help';
    }
    currentMenuType = initialMenuType;

    handleNavigation();
}

export { loadPage };