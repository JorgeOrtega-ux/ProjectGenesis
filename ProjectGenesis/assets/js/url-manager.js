/* ====================================== */
/* ========== URL-MANAGER.JS ============ */
/* ====================================== */
import { deactivateAllModules } from './main-controller.js';

const contentContainer = document.querySelector('.main-sections');

const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',
    'toggleSectionRegister': 'register',
    'toggleSectionResetPassword': 'reset-password', // <-- AÑADIDO
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    '/register': 'toggleSectionRegister',
    '/reset-password': 'toggleSectionResetPassword', // <-- AÑADIDO
};

// Usar la variable global definida en index.php
const basePath = window.projectBasePath || '/ProjectGenesis'; 

async function loadPage(page) {

    if (!contentContainer) return;

    contentContainer.innerHTML = '';

    try {
        // Modificación: Usar basePath en el fetch
        const response = await fetch(`${basePath}/router/router.php?page=${page}`);
        const html = await response.text();
        
        contentContainer.innerHTML = html;

    } catch (error) {
        console.error('Error al cargar la página:', error);
        contentContainer.innerHTML = '<h2>Error al cargar el contenido</h2>';
    }
}

function handleNavigation() {

    let path = window.location.pathname.replace(basePath, '');
    if (path === '' || path === '/') path = '/'; 

    const action = paths[path];

    if (!action) {
        loadPage('404');          
        updateMenuState(null);  
        return;                   
    }
    
    const page = routes[action]; 

    if (page) {
        loadPage(page);
        updateMenuState(action);
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

export function initRouter() {
    
    document.body.addEventListener('click', e => {
        // --- MODIFICADO: Añadido "a[href*='/reset-password']" ---
        const link = e.target.closest('.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"]');
        
        if (link) {
            e.preventDefault(); 
            
            let action, page, newPath;

            if (link.hasAttribute('data-action')) {
                // Es un link del menú
                action = link.getAttribute('data-action');
                page = routes[action];
                newPath = Object.keys(paths).find(key => paths[key] === action);
            } else {
                // Es un link de auth (<a>)
                const url = new URL(link.href);
                newPath = url.pathname.replace(basePath, '') || '/';
                action = paths[newPath];
                page = routes[action];
            }

            if (!page) return; 

            const fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}`;

            if (window.location.pathname !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);
                loadPage(page);
                updateMenuState(action);
            }
            
            deactivateAllModules();
        }
    });
    
    // --- MODIFICACIÓN: ELIMINAR ESTE BLOQUE ---
    // La lógica del 'auth-toggle-password' se ha movido a auth-manager.js
    /*
    document.body.addEventListener('click', e => {
        const toggleBtn = e.target.closest('.auth-toggle-password');
        // ... (todo el bloque eliminado) ...
    });
    */
    // --- FIN DE LA MODIFICACIÓN ---

    window.addEventListener('popstate', handleNavigation);

    handleNavigation();
}