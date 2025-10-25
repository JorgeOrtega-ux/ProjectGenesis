/* ====================================== */
/* ========== URL-MANAGER.JS ============ */
/* ====================================== */
import { deactivateAllModules } from './main-controller.js';

const contentContainer = document.querySelector('.main-sections');

// --- ▼▼▼ MODIFICACIÓN: ACTUALIZAR RUTAS DE REGISTRO ▼▼▼ ---
const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',
    // 'toggleSectionRegister': 'register', // <-- Eliminado
    'toggleSectionResetPassword': 'reset-password',

    // Nuevas rutas de Registro
    'toggleSectionRegisterStep1': 'register-step1',
    'toggleSectionRegisterStep2': 'register-step2',
    'toggleSectionRegisterStep3': 'register-step3',

    // Nuevas rutas de Configuración
    'toggleSectionSettingsProfile': 'settings-profile',
    'toggleSectionSettingsLogin': 'settings-login',
    'toggleSectionSettingsAccess': 'settings-accessibility'
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    // '/register': 'toggleSectionRegister', // <-- Eliminado
    '/reset-password': 'toggleSectionResetPassword',

    // Nuevas rutas de Registro
    '/register': 'toggleSectionRegisterStep1',
    '/register/additional-data': 'toggleSectionRegisterStep2',
    '/register/verification-code': 'toggleSectionRegisterStep3',

    // --- ▼▼▼ MODIFICACIÓN: LÍNEA AMBIGUA ELIMINADA ▼▼▼ ---
    // '/settings': 'toggleSectionSettingsProfile', // <-- Esta línea causaba el bug
    '/settings/your-profile': 'toggleSectionSettingsProfile',
    '/settings/login-security': 'toggleSectionSettingsLogin',
    '/settings/accessibility': 'toggleSectionSettingsAccess'
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
};
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

const basePath = window.projectBasePath || '/ProjectGenesis';

async function loadPage(page) {

    if (!contentContainer) return;

    const isSettingsPage = page.startsWith('settings-');
    updateGlobalMenuVisibility(isSettingsPage);

    contentContainer.innerHTML = '';

    try {
        const response = await fetch(`${basePath}/config/router.php?page=${page}`);
        const html = await response.text();

        contentContainer.innerHTML = html;

    } catch (error) {
        console.error('Error al cargar la página:', error);
        contentContainer.innerHTML = '<h2>Error al cargar el contenido</h2>';
    }
}

// --- ▼▼▼ MODIFICACIÓN: EXPORTAR ESTA FUNCIÓN ▼▼▼ ---
export function handleNavigation() {
// --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    let path = window.location.pathname.replace(basePath, '');
    if (path === '' || path === '/') path = '/';

    if (path === '/settings') {
        path = '/settings/your-profile';
        history.replaceState(null, '', `${basePath}${path}`);
    }

    const action = paths[path];

    if (!action) {
        loadPage('404');
        updateMenuState(null);
        return;
    }

    const page = routes[action];

    // --- ▼▼▼ MODIFICACIÓN: Actualizar active para todos los pasos de registro ▼▼▼ ---
    if (page) {
        loadPage(page);
        // Hacemos que cualquier página 'register-...' ponga 'register' como activo en el menú (si existiera)
        const menuAction = action.startsWith('toggleSectionRegister') ? 'toggleSectionRegister' : action;
        updateMenuState(menuAction);
    } else {
        loadPage('404');
        updateMenuState(null);
    }
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
}

function updateMenuState(currentAction) {
    document.querySelectorAll('.module-surface .menu-link').forEach(link => {
        const linkAction = link.getAttribute('data-action');

        if (linkAction === currentAction) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

async function updateGlobalMenuVisibility(isSettings) {
    // (Esta función sigue vacía por ahora, pero la lógica de recarga
    // de página completa al cambiar de contexto previene problemas)
}


export function initRouter() {

    document.body.addEventListener('click', e => {
        // --- ▼▼▼ MODIFICACIÓN: AÑADIR a[href*="/register"] ▼▼▼ ---
        // Esto capturará los nuevos botones <a> de "Atrás"
        const link = e.target.closest(
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[data-nav-js]'
        );
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

        if (link) {
            e.preventDefault();

            let action, page, newPath;

            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');
                page = routes[action];
                // --- ▼▼▼ MODIFICACIÓN: AHORA ENCONTRARÁ LA URL CORRECTA ▼▼▼ ---
                newPath = Object.keys(paths).find(key => paths[key] === action);
                // (Ahora 'newPath' será '/settings/your-profile' correctamente)
                // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
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
                
                const isCurrentlySettings = window.location.pathname.startsWith(`${basePath}/settings`);
                const isGoingToSettings = newPath.startsWith('/settings');

                if (isCurrentlySettings !== isGoingToSettings) {
                    window.location.href = fullUrlPath;
                    return;
                }

                history.pushState(null, '', fullUrlPath);
                loadPage(page);
                updateMenuState(action);
            }

            deactivateAllModules();
        }
    });

    window.addEventListener('popstate', handleNavigation);

    handleNavigation();
}