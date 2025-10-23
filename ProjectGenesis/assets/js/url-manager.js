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
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    '/register': 'toggleSectionRegister',
};

const basePath = '/ProjectGenesis';

async function loadPage(page) {

    if (!contentContainer) return;

    contentContainer.innerHTML = '';

    try {
        const response = await fetch(`${basePath}/router.php?page=${page}`);
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
        const link = e.target.closest('.menu-link[data-action*="toggleSection"]');
        
        if (link) {
            e.preventDefault(); 
            
            const action = link.getAttribute('data-action');
            const page = routes[action];

            if (!page) return; 

            const newPath = Object.keys(paths).find(key => paths[key] === action);
            const fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}`;

            if (window.location.pathname !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);
                loadPage(page);
                updateMenuState(action);
            }
            
            deactivateAllModules();
        }
    });
    
    document.body.addEventListener('click', e => {
        const toggleBtn = e.target.closest('.auth-toggle-password');

        if (toggleBtn) {
            const inputId = toggleBtn.getAttribute('data-toggle');
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('.material-symbols-rounded');

            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.textContent = 'visibility_off';
                } else {
                    input.type = 'password';
                    icon.textContent = 'visibility';
                }
            }
        }
    });

    window.addEventListener('popstate', handleNavigation);

    handleNavigation();
}