import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from './auth-manager.js';
import { applyTranslations, getTranslation } from './i18n-manager.js';

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentIsSettings = null; 

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
    'toggleSectionAccountStatusSuspended': 'account-status-suspended'
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
    '/account-status/suspended': 'toggleSectionAccountStatusSuspended'
};

const basePath = window.projectBasePath || '/ProjectGenesis';


async function loadPage(page, isSettingsPage, action) {

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
                    
                    updateMenuState(action); 
                }
            })
            .catch(err => console.error('Error al cargar el menú lateral:', err));
    } else {
        updateMenuState(action);
    }


    try {
        const response = await fetch(`${basePath}/config/router.php?page=${page}`);
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

    const action = paths[path];

    if (!action) {
        const isSettings = path.startsWith('/settings');
        loadPage('404', isSettings, null); 
        return;
    }

    const page = routes[action];

    if (page) {
        const isSettings = path.startsWith('/settings');
        loadPage(page, isSettings, action); 
    } else {
        const isSettings = path.startsWith('/settings');
        loadPage('404', isSettings, null);
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
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], .settings-button[data-action*="toggleSection"]'
        );

        if (link) {
            if (link.classList.contains('settings-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) {
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
                
                loadPage(page, isGoingToSettings, action); 
            }

            deactivateAllModules();
        }
    });

    window.addEventListener('popstate', handleNavigation);

    const initialPath = window.location.pathname.replace(basePath, '') || '/';
    currentIsSettings = initialPath.startsWith('/settings') || initialPath.startsWith('/settings');

    handleNavigation();
}