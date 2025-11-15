// FILE: assets/js/app/url-manager.js
// (CORREGIDO - Error 'Cannot access 'page' before initialization')
// (MODIFICADO OTRA VEZ - Cambiado /messages/username a /messages/uuid)
// (CORREGIDO CON LÓGICA DE MENSAJERÍA DESHABILITADA EN CLICK Y POPSTATE)
// (CORREGIDO: EXCEPCIÓN PARA EVITAR NAVEGACIÓN AL CLICAR BOTÓN DE MENÚ)
// --- ▼▼▼ MODIFICACIÓN (ELIMINADA LÓGICA DE CHAT DE COMUNIDAD) ▼▼▼ ---

import { deactivateAllModules } from './main-controller.js';
import { startResendTimer } from '../modules/auth-manager.js';
import { applyTranslations, getTranslation } from '../services/i18n-manager.js';
import { hideTooltip } from '../services/tooltip-manager.js';
// --- ▼▼▼ IMPORTACIÓN MODIFICADA ▼▼▼ ---
import { loadSavedCommunity, loadCommentsForPost } from '../modules/community-manager.js';
import { initPublicationForm } from '../modules/publication-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
import { initFriendList } from '../modules/friend-manager.js';

const contentContainer = document.querySelector('.main-sections');
const pageLoader = document.getElementById('page-loader');

let loaderTimer = null;
let currentMenuType = null;

const routes = {
    'toggleSectionHome': 'home',
    'toggleSectionExplorer': 'explorer',
    'toggleSectionTrends': 'trends', // --- [HASTAGS] --- Nueva ruta
    'toggleSectionLogin': 'login',
    'toggleSectionMaintenance': 'maintenance',
    'toggleSectionServerFull': 'server-full',

    // --- ▼▼▼ RUTA ELIMINADA ▼▼▼ ---
    // 'toggleSectionJoinGroup': 'join-group', 
    // --- ▲▲▲ FIN RUTA ELIMINADA ▲▲▲ ---

    'toggleSectionCreatePublication': 'create-publication',
    'toggleSectionCreatePoll': 'create-poll',

    'toggleSectionPostView': 'post-view',

    'toggleSectionViewProfile': 'view-profile',

    'toggleSectionSearchResults': 'search-results',

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Ruta de Mensajes) ▼▼▼ ---
    'toggleSectionMessages': 'messages',
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    'toggleSectionRegisterStep1': 'register-step1',
    'toggleSectionRegisterStep2': 'register-step2',
    'toggleSectionRegisterStep3': 'register-step3',

    'toggleSectionResetStep1': 'reset-step1',
    'toggleSectionResetStep2': 'reset-step2',
    'toggleSectionResetStep3': 'reset-step3',

    'toggleSectionSettingsProfile': 'settings-profile',
    'toggleSectionSettingsLogin': 'settings-login',
    'toggleSectionSettingsAccess': 'accessibility',
    'toggleSectionSettingsDevices': 'settings-devices',

    'toggleSectionSettingsPassword': 'settings-change-password',
    'toggleSectionSettingsChangeEmail': 'settings-change-email',
    'toggleSectionSettingsToggle2fa': 'settings-toggle-2fa',
    'toggleSectionSettingsDeleteAccount': 'settings-delete-account',

    'toggleSectionAccountStatusDeleted': 'account-status-deleted',
    'toggleSectionAccountStatusSuspended': 'account-status-suspended',

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (RUTA AÑADIDA) ▼▼▼ ---
    'toggleSectionMessagingDisabled': 'messaging-disabled',
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    'toggleSectionAdminDashboard': 'admin-dashboard',
    'toggleSectionAdminManageUsers': 'admin-manage-users',
    'toggleSectionAdminCreateUser': 'admin-create-user',
    'toggleSectionAdminEditUser': 'admin-edit-user',
    'toggleSectionAdminServerSettings': 'admin-server-settings',
    'toggleSectionAdminManageBackups': 'admin-manage-backups',
    'toggleSectionAdminManageLogs': 'admin-manage-logs',

    'toggleSectionAdminManageCommunities': 'admin-manage-communities',
    'toggleSectionAdminEditCommunity': 'admin-edit-community',
};

const paths = {
    '/': 'toggleSectionHome',
    '/c/uuid-placeholder': 'toggleSectionHome',
    '/post/id-placeholder': 'toggleSectionPostView',
    '/profile/username-placeholder': 'toggleSectionViewProfile',

    '/search': 'toggleSectionSearchResults',

    '/trends': 'toggleSectionTrends', // --- [HASTAGS] --- Nueva ruta

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (RUTAS DE MENSAJES) ▼▼▼ ---
    '/messages': 'toggleSectionMessages',
    '/messages/uuid-placeholder': 'toggleSectionMessages', // <-- Placeholder actualizado
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (FIX) 1 DE 3 ▼▼▼ ---
    // (Placeholder de chat de comunidad eliminado)
    // --- ▲▲▲ FIN DE MODIFICACIÓN (FIX) 1 DE 3 ▲▲▲ ---
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    '/explorer': 'toggleSectionExplorer',
    '/login': 'toggleSectionLogin',
    '/maintenance': 'toggleSectionMaintenance',
    '/server-full': 'toggleSectionServerFull',

    // --- ▼▼▼ RUTA ELIMINADA ▼▼▼ ---
    // '/join-group': 'toggleSectionJoinGroup',
    // --- ▲▲▲ FIN RUTA ELIMINADA ▲▲▲ ---

    '/create-publication': 'toggleSectionCreatePublication',
    '/create-poll': 'toggleSectionCreatePoll',

    '/profile/username-placeholder/likes': 'toggleSectionViewProfile',
    '/profile/username-placeholder/bookmarks': 'toggleSectionViewProfile',
    '/profile/username-placeholder/info': 'toggleSectionViewProfile',
    '/profile/username-placeholder/amigos': 'toggleSectionViewProfile',
    '/profile/username-placeholder/fotos': 'toggleSectionViewProfile',

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

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (RUTA AÑADIDA) ▼▼▼ ---
    '/messaging-disabled': 'toggleSectionMessagingDisabled',
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    '/admin/dashboard': 'toggleSectionAdminDashboard',
    '/admin/manage-users': 'toggleSectionAdminManageUsers',
    '/admin/create-user': 'toggleSectionAdminCreateUser',
    '/admin/edit-user': 'toggleSectionAdminEditUser',
    '/admin/server-settings': 'toggleSectionAdminServerSettings',

    '/admin/manage-backups': 'toggleSectionAdminManageBackups',
    '/admin/manage-logs': 'admin-manage-logs',

    '/admin/manage-communities': 'toggleSectionAdminManageCommunities',
    '/admin/edit-community': 'toggleSectionAdminEditCommunity',
};

const basePath = window.projectBasePath || '/ProjectGenesis';


async function loadPage(page, action, fetchParams = null, isPartialLoad = false) {

    if (!contentContainer) return;

    // --- Lógica de Carga Parcial (Solo para pestañas de perfil) ---
    if (isPartialLoad) {
        const tabContainer = document.querySelector('.profile-content-container');
        if (!tabContainer) {
            // Si no encontramos el contenedor, forzamos una recarga completa
            loadPage(page, action, fetchParams, false);
            return;
        }

        // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO ▼▼▼ ---
        // Mostrar un loader spinner
        tabContainer.innerHTML = `
            <div class="page-loader active" style="position: relative; min-height: 285px; background-color: transparent;">
                <div class="spinner"></div>
            </div>
        `;
        // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▼▼▼ ---

        let queryString = '';
        if (fetchParams) {
            queryString = new URLSearchParams(fetchParams).toString().replace(/\+/g, '%20');
        }

        // Añadir el parámetro 'partial=true' para que el router sepa qué enviar
        const partialQuery = `partial=true${queryString ? `&${queryString}` : ''}`;
        const fetchUrl = `${basePath}/config/routing/router.php?page=${page}&${partialQuery}`;

        try {
            const response = await fetch(fetchUrl);
            const html = await response.text();

            // Inyectar el nuevo contenido de la pestaña
            tabContainer.innerHTML = html;
            applyTranslations(tabContainer);

            // --- ▼▼▼ INICIO DE BLOQUE ELIMINADO ▼▼▼ ---
            // La lógica de actualización de pestañas se movió a initRouter
            // --- ▲▲▲ FIN DE BLOQUE ELIMINADO ▼▼▼ ---

        } catch (error) {
            console.error('Error al cargar la pestaña:', error);
            tabContainer.innerHTML = `<h2>${getTranslation('js.url.errorLoad')}</h2>`;
        } finally {
            // Ya no es necesario
        }
        return; // Fin de la carga parcial
    }

    // --- Lógica de Carga Completa (la original) ---
    const headerTop = document.querySelector('.general-content-top');

    if (headerTop) {
        headerTop.classList.remove('shadow');
    }

    const friendListWrapper = document.getElementById('friend-list-wrapper');

    if (friendListWrapper) {

        // --- ▼▼▼ INICIO DE LA CORRECCIÓN ▼▼▼ ---
        // Se eliminó '|| page === 'trends'' de la condición
        if (page === 'home') {
            // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---

            if (!friendListWrapper.querySelector('#friend-list-container')) {
                try {
                    const friendListUrl = `${basePath}/includes/modules/module-friend-list.php`;
                    const response = await fetch(friendListUrl);

                    if (response.ok) {
                        friendListWrapper.innerHTML = await response.text();

                        const newFriendListModule = friendListWrapper.querySelector('#friend-list-container');
                        if (newFriendListModule) {
                            applyTranslations(newFriendListModule);
                            initFriendList();
                        }
                    } else {
                        throw new Error('Falló el fetch del módulo de amigos');
                    }
                } catch (err) {
                    console.error("Error al cargar dinámicamente la lista de amigos:", err);
                    friendListWrapper.innerHTML = '';
                }
            } else {
                const friendListItems = friendListWrapper.querySelector('#friend-list-items');
                if (friendListItems && friendListItems.querySelector('.logout-spinner')) {
                    initFriendList();
                }
            }
        } else {
            if (friendListWrapper.querySelector('#friend-list-container')) {
                friendListWrapper.innerHTML = '';
            }
        }
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

            newScrollableContent.addEventListener('scroll', function () {

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

            const commentsContainer = contentContainer.querySelector('.post-comments-container[data-post-id]');
            const commentInputContainer = contentContainer.querySelector('.post-comment-input-container[data-action="post-comment"]');

            if (commentsContainer && commentInputContainer) {
                commentsContainer.classList.add('active');
                commentInputContainer.classList.add('active');

                loadCommentsForPost(commentsContainer.dataset.postId);
            }
        }

        // --- ▼▼▼ INICIO DE BLOQUE AÑADIDO ▼▼▼ ---
        if (page === 'create-publication' || page === 'create-poll') {
            initPublicationForm(); // ¡¡LA LLAMADA QUE FALTABA!!
        }
        // --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---


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
    // --- ▼▼▼ INICIO DE LA CORRECCIÓN ▼▼▼ ---
    let page = null; // 1. Declarar 'page' aquí con 'let'
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▼▼▼ ---
    let fetchParams = null; // <-- AÑADIDO PARA LOS NUEVOS BLOQUES

    const communityUuidRegex = /^\/c\/([a-fA-F0-9\-]{36})$/i;
    const postViewRegex = /^\/post\/(\d+)$/i;

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Regex de Perfil y Mensajes) ▼▼▼ ---
    const profileRegex = /^\/profile\/([a-zA-Z0-9_]+)(?:\/(posts|likes|bookmarks|info|amigos|fotos))?$/i;
    const messagesRegex = /^\/messages\/([a-fA-F0-9\-]{36})$/i; // <-- MODIFICADO
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Regex de chat de comunidad eliminada) ▼▼▼ ---
    // const communityMessagesRegex = /^\/messages\/community\/([a-fA-F0-9\-]{36})$/i;
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---


    if (path === '/') {
        action = 'toggleSectionHome';
    } else if (communityUuidRegex.test(path)) {
        action = 'toggleSectionHome'; // <-- Corregido (ambos cargan 'home')
        page = 'home'; // <-- AÑADIDO
        const matches = path.match(communityUuidRegex);
        const uuid = matches[1];
        loadPage('home', action, { community_uuid: uuid });
        return;

    } else if (postViewRegex.test(path)) {
        action = 'toggleSectionPostView';
        page = 'post-view'; // <-- AÑADIDO
        const matches = path.match(postViewRegex);
        const postId = matches[1];
        loadPage('post-view', action, { post_id: postId });
        return;

    } else if (profileRegex.test(path)) {
        action = 'toggleSectionViewProfile';
        page = 'view-profile'; // <-- AÑADIDO
        const matches = path.match(profileRegex);
        const username = matches[1];
        const tab = matches[2] || 'posts';
        loadPage('view-profile', action, { username: username, tab: tab });
        return;

    // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (Manejo de /messages/uuid) ▼▼▼ ---
    } else if (messagesRegex.test(path)) {
        action = 'toggleSectionMessages';
        page = 'messages'; 
        
        // --- ▼▼▼ INICIO DEL BLOQUE DE SEGURIDAD AÑADIDO (EL FIX) ▼▼▼ ---
        const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
        const isMessagingEnabled = (window.isMessagingEnabled === true);

        if (isMessagingEnabled === false && !isPrivileged) {
            console.warn("handleNavigation (popstate): Navegación a 'messages/uuid' bloqueada.");
            const newPath = `${basePath}/messaging-disabled`;
            history.replaceState(null, '', newPath); 
            
            loadPage('messaging-disabled', 'toggleSectionMessagingDisabled', null, false);
            return; 
        }
        // --- ▲▲▲ FIN DEL BLOQUE DE SEGURIDAD AÑADIDO (EL FIX) ▲▲▲ ---
        
        const matches = path.match(messagesRegex);
        const userUuid = matches[1]; 
        loadPage(page, action, { user_uuid: userUuid }); 
        return;
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Bloque de chat de comunidad eliminado) ▼▼▼ ---
    // } else if (communityMessagesRegex.test(path)) { 
    //     ...
    // }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---

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

    // --- ▼▼▼ INICIO DE LA CORRECCIÓN ▼▼▼ ---
    // 3. Cambiar 'const' por una simple asignación
    page = routes[action];
    // --- ▲▲▲ FIN DE LA CORRECCIÓN ▼▼▼ ---

    if (page) {
        
        // --- ▼▼▼ INICIO DEL BLOQUE DE VERIFICACIÓN (PARA /messages) ▼▼▼ ---
        if (page === 'messages') {
            const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
            const isMessagingEnabled = (window.isMessagingEnabled === true); // Asumir true si no se define

            if (isMessagingEnabled === false && !isPrivileged) {
                
                console.log("handleNavigation (popstate): Navegación a 'messages' bloqueada.");
                
                const newPath = `${basePath}/messaging-disabled`;
                history.replaceState(null, '', newPath); 
                
                loadPage('messaging-disabled', 'toggleSectionMessagingDisabled', null, false);
                return; 
            }
        }
        // --- ▲▲▲ FIN DEL BLOQUE DE VERIFICACIÓN ▲▲▲ ---

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

    if (currentAction === 'toggleSectionAdminManageCommunities' || currentAction === 'toggleSectionAdminEditCommunity') {
        menuAction = 'toggleSectionAdminManageCommunities';
    }

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Agrupar todo bajo 'home' y 'messages') ▼▼▼ ---
    if (currentAction === 'toggleSectionJoinGroup' ||
        currentAction === 'toggleSectionCreatePublication' ||
        currentAction === 'toggleSectionCreatePoll' ||
        currentAction === 'toggleSectionPostView' ||
        currentAction === 'toggleSectionViewProfile' ||
        currentAction === 'toggleSectionSearchResults') {
        menuAction = 'toggleSectionHome'; // Todas estas acciones resaltan 'Home'
    }

    if (currentAction === 'toggleSectionMessages') {
        menuAction = 'toggleSectionMessages'; // La acción de mensajes se resalta a sí misma
    }
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (Añadir página de status) ▼▼▼ ---
    if (currentAction === 'toggleSectionMessagingDisabled') {
        menuAction = 'toggleSectionMessages'; // Resaltar "Mensajes" aunque esté deshabilitado
    }
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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

        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN (SELECTOR DE NAVEGACIÓN) ▼▼▼ ---
       const link = e.target.closest(
  '.menu-link[data-action*="toggleSection"], ' +
  'a[href*="/login"], a[href*="/register"], a[href*="/reset-password"], a[href*="/admin"], a[href*="/post/"], ' +
  'a[href*="/profile/"], a[href*="/search"], a[href*="/trends"], a[href*="/messages"], ' +
  '.component-button[data-action*="toggleSection"], ' +
  '.header-button[data-action*="toggleSection"], ' + // --- <<< LÍNEA AÑADIDA ---
  '.page-toolbar-button[data-action*="toggleSection"], a[href*="/maintenance"], a[href*="/admin/manage-backups"], ' +
  '.auth-button-back[data-action*="toggleSection"], .post-action-comment[data-action="toggleSectionPostView"], ' +
  '[data-module="moduleProfileMore"] a.menu-link[data-nav-js="true"], ' +
  'div.profile-nav-button[data-nav-js="true"],' +
  '[data-module="moduleSelect"] .menu-link[data-nav-js="true"],' +
  'a.post-hashtag-link[data-nav-js="true"],' +
  '[data-module="friend-context-menu"] [data-nav-js="true"],' + // Clic en "Ver Perfil"
  'a.chat-conversation-item[data-nav-js="true"]' // Clic en conversación
);

        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---

        if (link) {

            // ---
            // --- ▼▼▼ ESTA ES LA CORRECCIÓN PRINCIPAL ▼▼▼ ---
            // ---
            // Si el clic fue en un 'chat-conversation-item', 
            // pero *específicamente* en el botón de menú,
            // NO navegues. Deja que chat-manager.js lo maneje.
            if (link.matches('a.chat-conversation-item[data-nav-js="true"]') && 
                e.target.closest('[data-action="toggle-chat-context-menu"]')) {
                e.preventDefault(); // Previene la navegación
                return; // Detiene la ejecución de url-manager
            }
            // ---
            // --- ▲▲▲ FIN DE LA CORRECCIÓN ▲▲▲ ---
            // ---

            hideTooltip();

            if (link.classList.contains('component-button') && !link.hasAttribute('data-action') && !link.hasAttribute('data-nav-js')) {
                return;
            }

            e.preventDefault();

            let action, page, newPath;
            let fetchParams = null;

            let isPartialLoad = false;
            const currentProfileSection = document.querySelector('[data-section="view-profile"]');

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Detectar clic en div.profile-nav-button) ▼▼▼ ---
            if (currentProfileSection && (link.classList.contains('profile-nav-button') || link.closest('[data-module="moduleProfileMore"]'))) {
                isPartialLoad = true;
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            // --- ▼▼▼ INICIO DE NUEVO BLOQUE (ACTUALIZACIÓN INMEDIATA DE PESTAÑA) ▼▼▼ ---
            if (isPartialLoad) {
                const navBar = link.closest('.profile-nav-bar');
                const moreMenu = link.closest('[data-module="moduleProfileMore"]');

                if (navBar) {
                    // Si el clic fue en una pestaña principal
                    navBar.querySelectorAll('.profile-nav-button').forEach(btn => btn.classList.remove('active'));
                    const moreMenuPopover = navBar.querySelector('[data-module="moduleProfileMore"]');
                    if (moreMenuPopover) {
                        moreMenuPopover.querySelectorAll('a.menu-link').forEach(ml => ml.classList.remove('active'));
                    }
                    link.classList.add('active');
                } else if (moreMenu) {
                    // Si el clic fue en el menú "Más"
                    const mainNavBar = document.querySelector('.profile-nav-bar');
                    if (mainNavBar) {
                        mainNavBar.querySelectorAll('.profile-nav-button').forEach(btn => btn.classList.remove('active'));
                    }
                    moreMenu.querySelectorAll('a.menu-link').forEach(ml => ml.classList.remove('active'));
                    link.classList.add('active');
                }
            }
            // --- ▲▲▲ FIN DE NUEVO BLOQUE (ACTUALIZACIÓN INMEDIATA DE PESTAÑA) ▲▲▲ ---

            if (link.hasAttribute('data-action')) {
                action = link.getAttribute('data-action');

                // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (Manejar clic en "Enviar Mensaje") ▼▼▼ ---
                if (action === 'friend-menu-message' && link.dataset.uuid) { // <-- CAMBIADO: a uuid
                    const userUuid = link.dataset.uuid; // <-- CAMBIADO
                    page = 'messages';
                    action = 'toggleSectionMessages';
                    newPath = '/messages/' + userUuid; // <-- CAMBIADO
                    fetchParams = { user_uuid: userUuid }; // <-- CAMBIADO
                }
                // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---
                else if (action === 'toggleSectionPostView' && link.dataset.postId) {
                    page = 'post-view';
                    newPath = '/post/' + link.dataset.postId;
                    fetchParams = { post_id: link.dataset.postId };
                }
                else {
                    page = routes[action];
                    newPath = Object.keys(paths).find(key => paths[key] === action);
                }

            } else {

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Leer 'href' o 'data-href') ▼▼▼ ---
                const href = link.href || link.dataset.href;
                if (!href) return; // No es un enlace válido, salir
                const url = new URL(href, window.location.origin); // Usar window.location.origin como base
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

                newPath = url.pathname.replace(basePath, '') || '/';

                const postViewRegex = /^\/post\/(\d+)$/i;
                const communityUuidRegex = /^\/c\/([a-fA-F0-9\-]{36})$/i;

                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Nuevos Regex) ▼▼▼ ---
                const profileRegex = /^\/profile\/([a-zA-Z0-9_]+)(?:\/(posts|likes|bookmarks|info|amigos|fotos))?$/i;
                const messagesRegex = /^\/messages\/([a-fA-F0-9\-]{36})$/i; // <-- MODIFICADO
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Regex de chat de comunidad eliminada) ▼▼▼ ---
                // const communityMessagesRegex = /^\/messages\/community\/([a-fA-F0-9\-]{36})$/i;
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
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

                } else if (profileRegex.test(newPath)) {
                    action = 'toggleSectionViewProfile';
                    page = 'view-profile';
                    const matches = newPath.match(profileRegex);
                    fetchParams = { username: matches[1], tab: matches[2] || 'posts' };

                // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (Manejo de URL de Mensajes) ▼▼▼ ---
                } else if (messagesRegex.test(newPath)) {
                    action = 'toggleSectionMessages';
                    page = 'messages';
                    const matches = newPath.match(messagesRegex);
                    fetchParams = { user_uuid: matches[1] }; // <-- CAMBIADO
                // --- ▼▼▼ INICIO DE MODIFICACIÓN (Bloque de chat de comunidad eliminado) ▼▼▼ ---
                // } else if (communityMessagesRegex.test(newPath)) { 
                //    ...
                // }
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ ---

                } else {
                    if (newPath === '/settings') newPath = '/settings/your-profile';
                    if (newPath === '/admin') newPath = '/admin/dashboard';
                    action = paths[newPath];
                    page = routes[action];
                }
            }


            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Leer 'href' o 'data-href') ▼▼▼ ---
            const href = link.href || link.dataset.href;
            const url = href ? new URL(href, window.location.origin) : null;
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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
                if (link.tagName === 'A' && !link.hasAttribute('data-action')) {
                    window.location.href = link.href;
                }
                return;
            }

            // --- ▼▼▼ INICIO DEL NUEVO BLOQUE DE VERIFICACIÓN (LÓGICA DE CHAT DESHABILITADO) ▼▼▼ ---
            if (page === 'messages') {
                // Estas variables globales vienen de main-layout.php
                const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
                const isMessagingEnabled = (window.isMessagingEnabled === true); // Asumir true si no se define

                // Si la mensajería está APAGADA y el usuario NO es un admin
                if (isMessagingEnabled === false && !isPrivileged) {
                    
                    console.log("Navegación de chat bloqueada por el router JS.");
                    
                    // Redirigir al cliente a la página de "deshabilitado"
                    newPath = '/messaging-disabled';
                    action = 'toggleSectionMessagingDisabled'; 
                    page = 'messaging-disabled'; 
                    
                    fetchParams = null; // Limpiar cualquier parámetro (ej. /messages/uuid-...)
                    
                    const fullUrlPath = `${basePath}${newPath}`;
                    
                    // Comprobar si ya estamos allí
                    const currentFullUrl = window.location.pathname + window.location.search;
                    if (currentFullUrl !== fullUrlPath) {
                        history.pushState(null, '', fullUrlPath);
                        loadPage(page, action, fetchParams, false);
                    }
                    
                    deactivateAllModules();
                    return; // ¡Importante! Detener la navegación original a /messages
                }
            }
            // --- ▲▲▲ FIN DEL NUEVO BLOQUE DE VERIFICACIÓN ▲▲▲ ---

            const queryString = (url && url.search) ? url.search : '';
            let fullUrlPath;

            // --- ▼▼▼ INICIO DE MODIFICACIÓN (Añadir 'messages' a la lógica) ▼▼▼ ---
            if ((action === 'toggleSectionPostView' || action === 'toggleSectionViewProfile' || action === 'toggleSectionMessages') && newPath) {
                // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                fullUrlPath = `${basePath}${newPath}${queryString}`;
            } else {
                fullUrlPath = `${basePath}${newPath === '/' ? '/' : newPath}${queryString}`;
            }


            const currentFullUrl = window.location.pathname + window.location.search;
            if (currentFullUrl !== fullUrlPath) {
                history.pushState(null, '', fullUrlPath);

                loadPage(page, action, fetchParams, isPartialLoad);
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