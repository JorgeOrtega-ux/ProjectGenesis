/* ====================================== */
/* ========== URL-MANAGER.JS ============ */
/* ====================================== */
import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from './auth-manager.js';

const contentContainer = document.querySelector('.main-sections');

// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',

    // Nuevas rutas de Registro
    'toggleSectionRegisterStep1': 'register-step1',
    'toggleSectionRegisterStep2': 'register-step2',
    'toggleSectionRegisterStep3': 'register-step3',

    // Nuevas rutas de Reseteo
    'toggleSectionResetStep1': 'reset-step1',
    'toggleSectionResetStep2': 'reset-step2',
    'toggleSectionResetStep3': 'reset-step3',

    // Nuevas rutas de Configuración
    'toggleSectionSettingsProfile': 'settings-profile',
    'toggleSectionSettingsLogin': 'settings-login',
    'toggleSectionSettingsAccess': 'settings-accessibility',
    'toggleSectionSettingsDevices': 'settings-devices' // <-- AÑADIDO
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',

    // Nuevas rutas de Registro
    '/register': 'toggleSectionRegisterStep1',
    '/register/additional-data': 'toggleSectionRegisterStep2',
    '/register/verification-code': 'toggleSectionRegisterStep3',
    
    // Nuevas rutas de Reseteo
    '/reset-password': 'toggleSectionResetStep1',
    '/reset-password/verify-code': 'toggleSectionResetStep2',
    '/reset-password/new-password': 'toggleSectionResetStep3',

    // Rutas de Configuración
    '/settings/your-profile': 'toggleSectionSettingsProfile',
    '/settings/login-security': 'toggleSectionSettingsLogin',
    '/settings/accessibility': 'toggleSectionSettingsAccess',
    '/settings/device-sessions': 'toggleSectionSettingsDevices' // <-- AÑADIDO
};
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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

        // Después de cargar el contenido, buscar si hay un timer que iniciar.
        if (page === 'register-step3') {
            const link = document.getElementById('register-resend-code-link');
            if (link) {
                const cooldownSeconds = parseInt(link.dataset.cooldown || '0', 10);
                if (cooldownSeconds > 0) {
                    startResendTimer(link, cooldownSeconds);
                }
            }
        }
        
        // --- ▼▼▼ ¡NUEVO BLOQUE! Iniciar timer si cargamos el paso 2 de reseteo ▼▼▼ ---
        // (No necesitamos cooldown al cargar, porque el usuario acaba de llegar
        // del paso 1, así que la API acaba de generar el código.
        // Pero SÍ necesitamos que el botón de reenviar inicie un timer al hacer clic,
        // lo cual ya está manejado en auth-manager.js)
        // --- ▲▲▲ FIN DE BLOQUE NUEVO ▲▲▲ ---

    } catch (error) {
        console.error('Error al cargar la página:', error);
        contentContainer.innerHTML = '<h2>Error al cargar el contenido</h2>';
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
        loadPage('404');
        updateMenuState(null);
        return;
    }

    const page = routes[action];

    if (page) {
        loadPage(page);
        // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
        // Agrupar visualmente los menús
        let menuAction = action;
        if (action.startsWith('toggleSectionRegister')) menuAction = 'toggleSectionRegister';
        if (action.startsWith('toggleSectionReset')) menuAction = 'toggleSectionResetPassword'; 
        updateMenuState(menuAction);
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    } else {
        loadPage('404');
        updateMenuState(null);
    }
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
    // (Esta función sigue vacía por ahora)
}


export function initRouter() {

    document.body.addEventListener('click', e => {
        // --- ▼▼▼ ¡INICIO DE LA CORRECCIÓN! ▼▼▼ ---
        // Se añadió '.settings-button[data-action*="toggleSection"]' al selector
        const link = e.target.closest(
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[data-nav-js], .settings-button[data-action*="toggleSection"]'
        );
        // --- ▲▲▲ ¡FIN DE LA CORRECCIÓN! ▲▲▲ ---

        if (link) {
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