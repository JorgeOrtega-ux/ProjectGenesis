// FILE: assets/js/app-init.js

import { initMainController } from './app/main-controller.js';
import { initRouter } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initCommunityManager } from './modules/community-manager.js';
import { initPublicationManager } from './modules/publication-manager.js';
import { initFriendManager, initFriendList } from './modules/friend-manager.js';
import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js'; 
import { callFriendApi } from './services/api-service.js';

const htmlEl = document.documentElement;
const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

window.lastKnownUserCount = null;

function applyTheme(theme) {
    if (theme === 'light') {
        htmlEl.classList.remove('dark-theme');
        htmlEl.classList.add('light-theme');
    } else if (theme === 'dark') {
        htmlEl.classList.remove('light-theme');
        htmlEl.classList.add('dark-theme');
    } else { 
        if (systemThemeQuery.matches) {
            htmlEl.classList.remove('light-theme');
            htmlEl.classList.add('dark-theme');
        } else {
            htmlEl.classList.remove('dark-theme');
            htmlEl.classList.add('light-theme');
        }
    }
}

window.applyCurrentTheme = applyTheme;

function initThemeManager() {
    applyTheme(window.userTheme || 'system');

    systemThemeQuery.addEventListener('change', (e) => {
        if ((window.userTheme || 'system') === 'system') {
            applyTheme('system');
        }
    });
}

// =============================================
// ============ LÓGICA DE NOTIFICACIONES =========
// =============================================
let hasLoadedNotifications = false;
let currentNotificationCount = 0;

/**
 * Actualiza el contador visual (badge) de notificaciones.
 * @param {number} count - El número total de notificaciones no leídas.
 */
function setNotificationCount(count) {
    currentNotificationCount = count;
    const badge = document.getElementById('notification-badge-count');
    if (!badge) return;

    badge.textContent = count;
    if (count > 0) {
        badge.classList.remove('disabled');
    } else {
        badge.classList.add('disabled');
    }
}

/**
 * Añade una notificación (HTML) al panel de notificaciones.
 * @param {object} request - Objeto con los datos de la solicitud (user_id, username, profile_image_url).
 */
function addNotificationToUI(request) {
    const listContainer = document.getElementById('notification-list-items');
    const placeholder = document.getElementById('notification-placeholder');
    if (!listContainer) return;

    if (placeholder) {
        placeholder.style.display = 'none';
    }
    
    if (document.querySelector(`.notification-item[data-user-id="${request.user_id}"]`)) {
        return;
    }

    const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    const avatar = request.profile_image_url || defaultAvatar;

    const notificationHtml = `
        <div class="notification-item" data-user-id="${request.user_id}">
            <div class="notification-avatar">
                <img src="${avatar}" alt="${request.username}">
            </div>
            <div class="notification-content">
                <div class="notification-text">
                    <strong>${request.username}</strong>
                    <span data-i18n="notifications.friendRequestText">quiere ser tu amigo.</span>
                </div>
                <div class="notification-actions">
                    <button type="button" class="notification-action-button notification-action-button--secondary" 
                            data-action="friend-decline-request" data-user-id="${request.user_id}">
                        <span data-i18n="friends.declineRequest">Rechazar</span>
                    </button>
                    <button type="button" class="notification-action-button notification-action-button--primary" 
                            data-action="friend-accept-request" data-user-id="${request.user_id}">
                        <span data-i18n="friends.acceptRequest">Aceptar</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    listContainer.insertAdjacentHTML('afterbegin', notificationHtml);
}

/**
 * Carga las solicitudes de amistad pendientes desde la API la primera vez que se abre el panel.
 */
async function loadPendingNotifications() {
    if (hasLoadedNotifications) {
        return; 
    }
    hasLoadedNotifications = true; 
    
    const listContainer = document.getElementById('notification-list-items');
    const placeholder = document.getElementById('notification-placeholder');
    if (!listContainer || !placeholder) return;

    placeholder.querySelector('.material-symbols-rounded').innerHTML = '<span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>';
    placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.loading');
    placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.loading');
    
    const formData = new FormData();
    formData.append('action', 'get-pending-requests'); 

    try {
        const result = await callFriendApi(formData);
        
        if (result.success && result.requests) {
            setNotificationCount(result.requests.length);
            
            if (result.requests.length === 0) {
                placeholder.style.display = 'flex'; // Asegurarse que se muestre si estaba oculto
                placeholder.querySelector('.material-symbols-rounded').innerHTML = 'notifications_off';
                placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.empty');
                placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.empty');
            } else {
                placeholder.style.display = 'none';
                listContainer.innerHTML = ''; 
                
                result.requests.forEach(request => {
                    addNotificationToUI(request);
                });
            }
        } else {
             placeholder.style.display = 'flex';
             placeholder.querySelector('.material-symbols-rounded').innerHTML = 'error';
             placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'js.api.errorServer');
             placeholder.querySelector('span[data-i18n]').textContent = getTranslation('js.api.errorServer');
        }
    } catch (e) {
        console.error("Error al cargar notificaciones:", e);
         placeholder.style.display = 'flex';
         placeholder.querySelector('.material-symbols-rounded').innerHTML = 'error';
         placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'js.api.errorConnection');
         placeholder.querySelector('span[data-i18n]').textContent = getTranslation('js.api.errorConnection');
    }
}

/**
 * Inicializa los listeners para el panel de notificaciones.
 */
function initNotificationManager() {
    const notificationButton = document.querySelector('[data-action="toggleModuleNotifications"]');
    if (notificationButton) {
        notificationButton.addEventListener('click', () => {
            if (!hasLoadedNotifications) {
                loadPendingNotifications();
            }
            setNotificationCount(0);
        }, { once: false });
    }
    
    document.body.addEventListener('click', (e) => {
        const targetButton = e.target.closest('[data-action="friend-accept-request"], [data-action="friend-decline-request"]');
        if (targetButton && targetButton.closest('.notification-item')) {
            const item = targetButton.closest('.notification-item');
            if (item) {
                item.style.opacity = '0.5'; 
                setTimeout(() => {
                    item.remove();
                    // Volver a mostrar el placeholder si la lista está vacía
                    const listContainer = document.getElementById('notification-list-items');
                    const placeholder = document.getElementById('notification-placeholder');
                    if (listContainer && placeholder && listContainer.children.length === 0) {
                        placeholder.style.display = 'flex';
                    }
                }, 1000);
            }
        }
    });
}
// =============================================
// ============ FIN DE NOTIFICACIONES ============
// =============================================


document.addEventListener('DOMContentLoaded', async function () { 
    
    window.showAlert = showAlert;

    await initI18nManager();

    initThemeManager();

    initMainController();
    
    initAuthManager();
    initSettingsManager();
    initAdminManager();
    initAdminEditUserManager();
    initAdminServerSettingsManager();
    initAdminBackupModule();
    initCommunityManager();
    initPublicationManager();
    initFriendManager(); 
    initNotificationManager();

    initRouter(); 
    
    initTooltipManager(); 

    if (window.isUserLoggedIn) {
        initFriendList();

        let ws;
        
        const wsHost = window.wsHost || '127.0.0.1';
        const wsUrl = `ws://${wsHost}:8765`;
        
        function connectWebSocket() {
            try {
                ws = new WebSocket(wsUrl);
                window.ws = ws;

                ws.onopen = () => {
                    console.log("[WS] Conectado al servidor en:", wsUrl);
                    
                    const authMessage = {
                        type: "auth",
                        user_id: window.userId || 0,
                        session_id: window.csrfToken || ""
                    };
                    ws.send(JSON.stringify(authMessage));
                };

                ws.onmessage = (event) => {
                    try {
                        const data = JSON.parse(event.data);
                        
                        if (data.type === 'user_count') {
                            window.lastKnownUserCount = data.count; 
                            const display = document.getElementById('concurrent-users-display');
                            if (display) {
                                display.textContent = data.count;
                                display.setAttribute('data-i18n', ''); 
                            }
                        } 
                        else if (data.type === 'force_logout') {
                            console.log("[WS] Recibida orden de desconexión forzada.");
                            window.showAlert(getTranslation('js.logout.forced') || 'Tu sesión ha caducado, por favor inicia sesión de nuevo.', 'info', 5000);
                            setTimeout(() => { window.location.reload(); }, 3000);
                        }
                        else if (data.type === 'account_status_update') {
                            const newStatus = data.status;
                            if (newStatus === 'suspended' || newStatus === 'deleted') {
                                const msgKey = (newStatus === 'suspended') ? 'js.auth.errorAccountSuspended' : 'js.auth.errorAccountDeleted';
                                window.showAlert(getTranslation(msgKey), 'error', 5000);
                                setTimeout(() => {
                                    window.location.href = `${window.projectBasePath}/account-status/${newStatus}`;
                                }, 3000);
                            }
                        }
                        else if (data.type === 'friend_request_received' && data.payload) {
                            console.log("[WS] Notificación de amistad recibida");
                            addNotificationToUI(data.payload);
                            
                            if (data.new_count !== undefined) {
                                setNotificationCount(data.new_count);
                            } else {
                                setNotificationCount(currentNotificationCount + 1);
                            }
                            showAlert(`🔔 ${data.payload.username} ${getTranslation('notifications.friendRequestAlert')}`, 'info');
                        }
                        // --- ▼▼▼ INICIO DE NUEVOS MANEJADORES ▼▼▼ ---
                        else if (data.type === 'friend_request_accepted' && data.payload) {
                            // Solicitud aceptada
                            console.log("[WS] Notificación de amistad aceptada");
                            setNotificationCount(currentNotificationCount + 1); // Incrementar badge
                            showAlert(`🎉 ${getTranslation('js.notifications.friendAccepted').replace('{username}', data.payload.username)}`, 'success');
                            initFriendList(); // Recargar lista de amigos
                        }
                        else if (data.type === 'new_like' && data.payload) {
                            // Nuevo Me Gusta
                            console.log("[WS] Notificación de nuevo 'Me Gusta'");
                            setNotificationCount(currentNotificationCount + 1); // Incrementar badge
                            showAlert(`❤️ ${getTranslation('js.notifications.newLike').replace('{username}', data.payload.username)}`, 'info');
                        }
                        else if (data.type === 'new_comment' && data.payload) {
                            // Nuevo Comentario
                            console.log("[WS] Notificación de nuevo comentario");
                            setNotificationCount(currentNotificationCount + 1); // Incrementar badge
                            showAlert(`💬 ${getTranslation('js.notifications.newComment').replace('{username}', data.payload.username)}`, 'info');
                        }
                        else if (data.type === 'new_reply' && data.payload) {
                            // Nueva Respuesta
                            console.log("[WS] Notificación de nueva respuesta");
                            setNotificationCount(currentNotificationCount + 1); // Incrementar badge
                            showAlert(`💬 ${getTranslation('js.notifications.newReply').replace('{username}', data.payload.username)}`, 'info');
                        }
                         else if (data.type === 'new_poll_vote' && data.payload) {
                            // Nuevo Voto (Solo toast, sin badge para no ser "ruidoso")
                            console.log("[WS] Notificación de nuevo voto");
                            showAlert(`📊 ${getTranslation('js.notifications.newPollVote').replace('{username}', data.payload.username)}`, 'info');
                        }
                        // --- ▲▲▲ FIN DE NUEVOS MANEJADORES ▲▲▲ ---
                    } catch (e) {
                        console.error("[WS] Error al parsear mensaje:", e);
                    }
                };
                
                ws.onclose = (event) => {
                    console.log("[WS] Desconectado del servidor de conteo.", event.reason);
                    const display = document.getElementById('concurrent-users-display');
                    if (display) {
                        display.textContent = '---';
                        display.setAttribute('data-i18n', ''); 
                    }
                };

                ws.onerror = (error) => {
                    console.error("[WS] Error de WebSocket:", error);
                };

            } catch (e) {
                console.error("[WS] No se pudo crear la conexión WebSocket:", e);
            }
        }

        connectWebSocket();

        window.addEventListener('beforeunload', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close(1000, "Navegación de usuario"); 
            }
        });
    }

});