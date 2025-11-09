// FILE: assets/js/app/url-manager.js

import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from '../modules/auth-manager.js';
// --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ ---
import { applyTranslations, getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';
import { loadSavedCommunity, loadCommentsForPost } from '../modules/community-manager.js';
import { initFriendList } from '../modules/friend-manager.js'; // <-- IMPORTADO
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentMenuType = null; 

const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionLogin': 'login',
    'toggleSectionMaintenance': 'maintenance', 
    'toggleSectionServerFull': 'server-full', 

    'toggleSectionJoinGroup': 'join-group',
    'toggleSectionCreatePublication': 'create-publication', 
    'toggleSectionCreatePoll': 'create-poll', 
    
    'toggleSectionPostView': 'post-view', 

    'toggleSectionViewProfile': 'view-profile', // <-- RUTA DE PERFIL (MODIFICADA)

    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    'toggleSectionSearchResults': 'search-results',
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

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
    
    // --- ▼▼▼ LÍNEAS AÑADIDAS ▼▼▼ ---
    'toggleSectionAdminManageCommunities': 'admin-manage-communities',
    'toggleSectionAdminEditCommunity': 'admin-edit-community',
    // --- ▲▲▲ FIN LÍNEAS AÑADIDAS ▲▲▲ ---
};

const paths = {
    '/': 'toggleSectionHome',
    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    '/maintenance': 'toggleSectionMaintenance', 
    '/server-full': 'toggleSectionServerFull', 

    '/join-group': 'toggleSectionJoinGroup',
    
    // --- ▼▼▼ INICIO DE LA CORRECCIÓN (BUG .../undefined) ▼▼▼ ---
    // Los valores aquí deben coincidir con las *claves* del objeto 'routes'
    '/create-publication': 'toggleSectionCreatePublication', 
    '/create-poll': 'toggleSectionCreatePoll', 
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
    
    '/post': 'toggleSectionPostView', 

    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    '/search': 'toggleSectionSearchResults',
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Rutas de Perfil) ▼▼▼ ---
    '/profile': 'toggleSectionViewProfile', // Genérico (obsoleto, pero por si acaso)
    '/profile/username-placeholder': 'toggleSectionViewProfile', 
    '/profile/username-placeholder/likes': 'toggleSectionViewProfile',
    '/profile/username-placeholder/bookmarks': 'toggleSectionViewProfile',
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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
    '/admin/server-settings': 'toggleSectionAdminServerSettings', 

    '/admin/manage-backups': 'toggleSectionAdminManageBackups',
    '/admin/manage-logs': 'toggleSectionAdminManageLogs',
    
    // --- ▼▼▼ LÍNEAS AÑADIDAS ▼▼▼ ---
    '/admin/manage-communities': 'toggleSectionAdminManageCommunities',
    '/admin/edit-community': 'toggleSectionAdminEditCommunity',
    // --- ▲▲▲ FIN LÍNEAS AÑADIDAS ▲▲▲ ---
};

const basePath = window.projectBasePath || '/ProjectGenesis';


async function loadPage(page, action, fetchParams = null) {

    if (!contentContainer) return;

    
    const headerTop = document.querySelector('.general-content-top');
    
    if (headerTop) {
        headerTop.classList.remove('shadow');
    }

    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (LÓGICA DE CARGA/ELIMINACIÓN DE AMIGOS) ▼▼▼ ---
    const friendListWrapper = document.getElementById('friend-list-wrapper');
    
    if (friendListWrapper) {
        if (page === 'home') {
            // Es 'home'. ¿Ya está el módulo en el DOM?
            if (!friendListWrapper.querySelector('#friend-list-container')) {
                // No está. Hay que cargarlo (porque recargamos en otra pág y navegamos a home).
                try {
                    const friendListUrl = `${basePath}/includes/modules/module-friend-list.php`;
                    const response = await fetch(friendListUrl);
                    
                    if (response.ok) {
                        friendListWrapper.innerHTML = await response.text();
                        
                        // Ahora que el HTML existe, lo poblamos
                        const newFriendListModule = friendListWrapper.querySelector('#friend-list-container');
                        if (newFriendListModule) {
                            applyTranslations(newFriendListModule);
                            initFriendList(); // Popula los datos
                        }
                    } else {
                        throw new Error('Falló el fetch del módulo de amigos');
                    }
                } catch (err) {
                    console.error("Error al cargar dinámicamente la lista de amigos:", err);
                    friendListWrapper.innerHTML = ''; // Limpiar en caso de error
                }
            } else {
                // El módulo ya estaba cargado (probablemente por el servidor en la carga inicial de home).
                // Nos aseguramos de que esté poblado.
                
                // Comprobamos si ya tiene items o si solo tiene el placeholder de carga
                const friendListItems = friendListWrapper.querySelector('#friend-list-items');
                if (friendListItems && friendListItems.querySelector('.logout-spinner')) {
                     initFriendList(); // Popula los datos
                }
            }
        } else {
            // NO es 'home'. Eliminamos el módulo si existe.
            if (friendListWrapper.querySelector('#friend-list-container')) {
                friendListWrapper.innerHTML = ''; // Elimina el módulo del DOM
            }
        }
    }
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

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
    
    let menuType = 'main';
    if (isSettingsPage) {
        menuType = 'settings';
    } else if (isAdminPage) {
        menuType = 'admin';
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

        if (fetchParams) {
            queryString = new URLSearchParams(fetchParams).toString().replace(/\+/g, '%20');
        
        } else {
            const browserQuery = window.location.search;
            if (browserQuery) {
                queryString = browserQuery.substring(1); 
            }
        }
        
        const fetchUrl = `${basePath}/config/routing/router.php?page=${page}${queryString ? `&${queryString}` : ''}`;

        const response = await fetch(fetchUrl);
        
        const html = await response.text();

        contentContainer.innerHTML = html;
        applyTranslations(contentContainer);

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
        
        if (page === 'home') {
            loadSavedCommunity();
        }
        
        if (page === 'post-view') {
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Mostrar comentarios en vista de post) ▼▼▼ ---
            
            // Encontrar ambos contenedores
            const commentsContainer = contentContainer.querySelector('.post-comments-container[data-post-id]');
            const commentInputContainer = contentContainer.querySelector('.post-comment-input-container[data-action="post-comment"]');
            
            if (commentsContainer && commentInputContainer) {
                // 1. Mostrarlos añadiendo la clase 'active'
                commentsContainer.classList.add('active');
                commentInputContainer.classList.add('active');
                
                // 2. Cargar los comentarios
                loadCommentsForPost(commentsContainer.dataset.postId);
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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

    let action = null;
    const communityUuidRegex = /^\/c\/([a-fA-F0-9\-]{36})$/i;
    const postViewRegex = /^\/post\/(\d+)$/i; 
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Nueva Regex de Perfil) ▼▼▼ ---
    const profileRegex = /^\/profile\/([a-zA-Z0-9_]+)(?:\/(likes|bookmarks))?$/i;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    if (path === '/') {
        action = 'toggleSectionHome';
    } else if (communityUuidRegex.test(path)) {
        const matches = path.match(communityUuidRegex);
        const uuid = matches[1];
        loadPage('home', action, { community_uuid: uuid });
        return; 
        
    } else if (postViewRegex.test(path)) { 
        action = 'toggleSectionPostView';
        const matches = path.match(postViewRegex);
        const postId = matches[1];
        loadPage('post-view', action, { post_id: postId }); 
        return;

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Lógica de Perfil) ▼▼▼ ---
    } else if (profileRegex.test(path)) {
        action = 'toggleSectionViewProfile';
        const matches = path.match(profileRegex);
        const username = matches[1];
        const tab = matches[2] || 'posts'; // 'posts', 'likes', o 'bookmarks'
        loadPage('view-profile', action, { username: username, tab: tab });
        return;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    } else {
        if (path === '/settings') {
            path = '/settings/your-profile';
            history.replaceState(null, '', `${basePath}${path}`);
        }
        
        if (path === '/admin') {
            path = '/admin/dashboard';
            history.replaceState(null, '', `${basePath}${path}`);
        }
        
        action = paths[path];
    }

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
    if (currentAction === 'toggleSectionAdminServerSettings') {
        menuAction = 'toggleSectionAdminServerSettings';
    }

    if (currentAction === 'toggleSectionAdminManageBackups') {
        menuAction = 'toggleSectionAdminManageBackups';
    }
    
    if (currentAction === 'toggleSectionAdminManageLogs') {
         menuAction = 'toggleSectionAdminDashboard'; 
    }
    
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    if (currentAction === 'toggleSectionAdminManageCommunities' || currentAction === 'toggleSectionAdminEditCommunity') {
        menuAction = 'toggleSectionAdminManageCommunities';
    }
    // --- ▲▲▲ FIN LÍNEA AÑADIDA ▲▲▲ ---

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Añadido ViewProfile y SearchResults) ▼▼▼ ---
    if (currentAction === 'toggleSectionJoinGroup' || 
        currentAction === 'toggleSectionCreatePublication' || 
        currentAction === 'toggleSectionCreatePoll' ||
        currentAction === 'toggleSectionPostView' ||
        currentAction === 'toggleSectionViewProfile' ||
        currentAction === 'toggleSectionSearchResults') { // <-- LÍNEA AÑADIDA
        menuAction = 'toggleSectionHome';
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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
      const communityLink = e.target.closest('[data-module="moduleSelectGroup"] .menu-link[data-community-id]');
      if (communityLink) {
          return;
      }
      
      // --- ▼▼▼ INICIO DE MODIFICACIÓN (Selector actualizado) ▼▼▼ ---
      const link = e.target.closest(
            '.menu-link[data-action*="toggleSection"], a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/admin"], a[href*="/post/"], a[href*="/profile/"], a[href*="/search"], .component-button[data-action*="toggleSection"], .page-toolbar-button[data-action*="toggleSection"], a[href*="/maintenance"], a[href*="/admin/manage-backups"], .auth-button-back[data-action*="toggleSection"], .post-action-comment[data-action="toggleSectionPostView"], .profile-tab a[data-nav-js="true"]'
        ); // <-- Añadido a[href*="/admin"]
      // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        if (link) {
            
            hideTooltip();

            if (link.classList.contains('component-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) { 
                return;
            }

            e.preventDefault();

            let action, page, newPath;
            let fetchParams = null; 


            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');

                // --- ▼▼▼ MODIFICACIÓN: Excluir 'edit-user' y 'edit-community' de aquí ▼▼▼ ---
                if (action === 'toggleSectionAdminEditUser' || action === 'toggleSectionAdminEditCommunity') {
                    e.stopImmediatePropagation();
                    return; 
                }
                // --- ▲▲▲ FIN MODIFICACIÓN ▲▲▲ ---
                
                if (action === 'toggleSectionPostView' && link.dataset.postId) {
                    page = 'post-view';
                    newPath = '/post/' + link.dataset.postId;
                    fetchParams = { post_id: link.dataset.postId };
                } 
                else { 
                    page = routes[action];
                    newPath = Object.keys(paths).find(key => paths[key] === action);
                }

            } else {
                
                const url = new URL(link.href); 
                
                newPath = url.pathname.replace(basePath, '') || '/';
                
                const postViewRegex = /^\/post\/(\d+)$/i;
                const communityUuidRegex = /^\/c\/([a-fA-F0-9\-]{36})$/i;
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Regex de Perfil) ▼▼▼ ---
                const profileRegex = /^\/profile\/([a-zA-Z0-9_]+)(?:\/(likes|bookmarks))?$/i;
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

                if (postViewRegex.test(newPath)) {
                    action = 'toggleSectionPostView';
                    page = 'post-view';
                    const matches = newPath.match(postViewRegex);
                    fetchParams = { post_id: matches[1] }; 
                
                } else if (communityUuidRegex.test(newPath)) {
                    action = 'toggleSectionHome';
                    page = 'home';
                    const matches = newPath.match(communityUuidRegex);
                    fetchParams = { community_uuid: matches[1] };

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Lógica de Perfil) ▼▼▼ ---
                } else if (profileRegex.test(newPath)) {
                    action = 'toggleSectionViewProfile';
                    page = 'view-profile';
                    const matches = newPath.match(profileRegex);
                    fetchParams = { username: matches[1], tab: matches[2] || 'posts' };
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

                } else { 
                    if (newPath === '/settings') newPath = '/settings/your-profile';
                    if (newPath === '/admin') newPath = '/admin/dashboard';
                    action = paths[newPath];
                    page = routes[action];
                }
            }
            
            
            const url = link.href ? new URL(link.href) : null; 
            
            if (link.hasAttribute('data-nav-js') && url && url.search) {
                 if (!fetchParams) fetchParams = {};
                 const searchParams = new URLSearchParams(url.search);
                 searchParams.forEach((value, key) => {
                    if (!fetchParams.hasOwnProperty(key)) { 
                        fetchParams[key] = value;
                    }
                 });
            }

            if (!page) {
                if(link.tagName === 'A' && !link.hasAttribute('data-action')) {
                    window.location.href = link.href;
                }
                return;
            }

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (fullUrlPath) ▼▼▼ ---
            const queryString = (url && url.search) ? url.search : '';
            let fullUrlPath;
            
            // --- Modificado para usar el newPath construido por la regex ---
            if ((action === 'toggleSectionPostView' || action === 'toggleSectionViewProfile') && newPath) {
                fullUrlPath = `${basePath}${newPath}`;
            } else {
                fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}${queryString}`;
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
            
            
            const currentFullUrl = window.location.pathname + window.location.search;
            if (currentFullUrl !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);
                
                loadPage(page, action, fetchParams); 
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
    }
    currentMenuType = initialMenuType;

    handleNavigation();
}

export { loadPage };