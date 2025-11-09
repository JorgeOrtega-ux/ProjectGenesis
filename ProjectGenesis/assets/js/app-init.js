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
import { callNotificationApi } from './services/api-service.js';
import { initSearchManager } from './modules/search-manager.js';

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

// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (formatTimeAgo) ▼▼▼ ---
/**
 * Formatea una fecha UTC a un string relativo (ej. "hace 5m", "Ayer", "7 Nov").
 * @param {string} dateString - El string de fecha UTC (ej. "2025-11-08 20:56:47")
 * @returns {string} - El string de tiempo formateado.
 */
function formatTimeAgo(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString.includes('Z') ? dateString : dateString + 'Z'); // Asegurar que se parsee como UTC
        const now = new Date();
        const seconds = Math.round((now - date) / 1000);
        
        const minutes = Math.round(seconds / 60);
        const hours = Math.round(minutes / 60);
        const days = Math.round(hours / 24);

        if (seconds < 60) {
            return 'Ahora';
        } else if (minutes < 60) {
            return `hace ${minutes}m`;
        } else if (hours < 24) {
            return `hace ${hours}h`;
        } else if (days === 1) {
            return 'Ayer';
        } else {
            // Formato para fechas más antiguas (ej. "7 Nov")
            return date.toLocaleDateString(window.userLanguage.split('-')[0] || 'es', {
                month: 'short',
                day: 'numeric'
            });
        }
    } catch (e) {
        console.error("Error al formatear fecha:", e);
        return dateString;
    }
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO DE NUEVA FUNCIÓN (getRelativeDateGroup) ▼▼▼ ---
/**
 * Devuelve la clave del grupo de fecha (Hoy, Ayer, etc.) para una fecha dada.
 * @param {Date} date - El objeto Date de la notificación.
 * @returns {string} - El string del grupo ("Hoy", "Ayer", "7 de noviembre de 2025").
 */
function getRelativeDateGroup(date) {
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const lang = window.userLanguage.split('-')[0] || 'es';

    if (date.toDateString() === today.toDateString()) {
        return "Hoy";
    }
    if (date.toDateString() === yesterday.toDateString()) {
        return "Ayer";
    }
    // Formato para grupos más antiguos (ej. "viernes, 7 de noviembre de 2025")
    return date.toLocaleDateString(lang, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}
// --- ▲▲▲ FIN DE NUEVA FUNCIÓN ▲▲▲ ---

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

// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (addNotificationToUI) ▼▼▼ ---
/**
 * Genera el HTML para una notificación.
 * @param {object} notification - Objeto con los datos de la notificación
 * @returns {string} - El string HTML del item de notificación.
 */
function addNotificationToUI(notification) {
    const avatar = notification.actor_avatar || "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
    let notificationHtml = '';
    let textKey = '';
    let href = '#'; // Enlace por defecto

    // --- 1. Generar el string de tiempo relativo ---
    const timeAgo = formatTimeAgo(notification.created_at);

    switch (notification.type) {
        case 'friend_request':
            textKey = 'notifications.friendRequestText';
            href = `${window.projectBasePath}/profile/${notification.actor_username}`;
            notificationHtml = `
                <div class="notification-item" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <a href="${href}" data-nav-js="true" class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </a>
                    <div class="notification-content">
                        <div class="notification-text">
                            <a href="${href}" data-nav-js="true" style="text-decoration: none; color: inherit;">
                                <strong>${notification.actor_username}</strong>
                            </a>
                            <span data-i18n="${textKey}">quiere ser tu amigo.</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo}</div>
                        <div class="notification-actions">
                            <button type="button" class="notification-action-button notification-action-button--secondary" 
                                    data-action="friend-decline-request" data-user-id="${notification.actor_user_id}">
                                <span data-i18n="friends.declineRequest">Rechazar</span>
                            </button>
                            <button type="button" class="notification-action-button notification-action-button--primary" 
                                    data-action="friend-accept-request" data-user-id="${notification.actor_user_id}">
                                <span data-i18n="friends.acceptRequest">Aceptar</span>
                            </button>
                        </div>
                    </div>
                </div>`;
            break;
        
        case 'friend_accept':
            textKey = 'js.notifications.friendAccepted';
            href = `${window.projectBasePath}/profile/${notification.actor_username}`;
            notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo}</div>
                        </div>
                </a>`;
            break;

        case 'like':
            textKey = 'js.notifications.newLike';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
            notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo}</div>
                        </div>
                </a>`;
            break;
            
        case 'comment':
            textKey = 'js.notifications.newComment';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
             notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo}</div>
                        </div>
                </a>`;
            break;

        case 'reply':
            textKey = 'js.notifications.newReply';
            href = `${window.projectBasePath}/post/${notification.reference_id}`;
             notificationHtml = `
                <a href="${href}" data-nav-js="true" class="notification-item" data-id="${notification.id}" data-user-id="${notification.actor_user_id}">
                    <div class="notification-avatar">
                        <img src="${avatar}" alt="${notification.actor_username}">
                    </div>
                    <div class="notification-content">
                        <div class="notification-text">
                            <span data-i18n="${textKey}">${getTranslation(textKey).replace('{username}', notification.actor_username)}</span>
                        </div>
                        <div class="notification-timestamp">${timeAgo}</div>
                        </div>
                </a>`;
            break;
    }
    
    if (notificationHtml) {
        if (notification.is_read == 1) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = notificationHtml.trim();
            const item = tempDiv.firstChild;
            item.style.opacity = '0.6'; 
            notificationHtml = item.outerHTML;
        }
    }
    
    // --- 2. Devolver el HTML (ya no lo inserta aquí) ---
    return notificationHtml;
}
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (addNotificationToUI) ▲▲▲ ---


// --- ▼▼▼ INICIO DE FUNCIÓN MODIFICADA (loadAllNotifications) ▼▼▼ ---
/**
 * Carga TODAS las notificaciones (amistad, likes, etc.) desde la BD.
 */
async function loadAllNotifications() {
    if (hasLoadedNotifications) {
        return; 
    }
    hasLoadedNotifications = true; 
    
    const listContainer = document.getElementById('notification-list-items');
    const placeholder = document.getElementById('notification-placeholder');
    if (!listContainer || !placeholder) return;

    placeholder.style.display = 'flex';
    placeholder.querySelector('.material-symbols-rounded').innerHTML = '<span class="logout-spinner" style="width: 32px; height: 32px; border-width: 3px;"></span>';
    placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.loading');
    placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.loading');
    listContainer.innerHTML = ''; // Limpiar lista
    
    const formData = new FormData();
    formData.append('action', 'get-notifications'); 

    try {
        const result = await callNotificationApi(formData);
        
        if (result.success && result.notifications) {
            setNotificationCount(result.unread_count || 0);
            
            if (result.notifications.length === 0) {
                placeholder.style.display = 'flex';
                placeholder.querySelector('.material-symbols-rounded').innerHTML = 'notifications_off';
                placeholder.querySelector('span[data-i18n]').setAttribute('data-i18n', 'notifications.empty');
                placeholder.querySelector('span[data-i18n]').textContent = getTranslation('notifications.empty');
            } else {
                placeholder.style.display = 'none';
                
                // --- 1. Lógica de agrupación ---
                let lastDateGroup = null;
                
                result.notifications.forEach(notification => {
                    const notificationDate = new Date(notification.created_at + 'Z');
                    const currentGroup = getRelativeDateGroup(notificationDate);
                    
                    // --- 2. Insertar cabecera de grupo si es nueva ---
                    if (currentGroup !== lastDateGroup) {
                        const dividerHtml = `<div class="notification-date-divider">${currentGroup}</div>`;
                        listContainer.insertAdjacentHTML('beforeend', dividerHtml);
                        lastDateGroup = currentGroup;
                    }

                    // --- 3. Generar e insertar el item ---
                    const notificationHtml = addNotificationToUI(notification);
                    if (notificationHtml) {
                        listContainer.insertAdjacentHTML('beforeend', notificationHtml);
                    }
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
// --- ▲▲▲ FIN DE FUNCIÓN MODIFICADA (loadAllNotifications) ▲▲▲ ---

/**
 * Inicializa los listeners para el panel de notificaciones.
 */
function initNotificationManager() {
    const notificationButton = document.querySelector('[data-action="toggleModuleNotifications"]');
    if (notificationButton) {
        notificationButton.addEventListener('click', () => {
            if (!hasLoadedNotifications) {
                loadAllNotifications();
            }
            const formData = new FormData();
            formData.append('action', 'mark-all-read');
            callNotificationApi(formData);
            
            setNotificationCount(0);

            document.querySelectorAll('#notification-list-items .notification-item').forEach(item => {
                item.style.opacity = '0.6';
            });
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
    initSearchManager();

    initRouter(); 
    
    initTooltipManager(); 

    if (window.isUserLoggedIn) {
        // --- ▼▼▼ LÍNEA ELIMINADA ▼▼▼ ---
        // initFriendList(); 
        // --- ▲▲▲ FIN DE LÍNEA ELIMINADA ▲▲▲ ---

        (async () => {
            const formData = new FormData();
            formData.append('action', 'get-notifications'); 
            const result = await callNotificationApi(formData);
            if (result.success && result.unread_count !== undefined) {
                setNotificationCount(result.unread_count);
            }
        })();


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
                        
                         else if (data.type === 'new_poll_vote' && data.payload) {
                            console.log("[WS] Notificación de nuevo voto");
                            showAlert(`📊 ${getTranslation('js.notifications.newPollVote').replace('{username}', data.payload.username)}`, 'info');
                        }
                        
                        else if (data.type === 'new_notification_ping') {
                            console.log("[WS] Ping de nueva notificación recibido");
                            setNotificationCount(currentNotificationCount + 1);
                            hasLoadedNotifications = false;
                        }

                        else if (data.type === 'presence_update') {
                            console.log(`[WS] Actualización de estado: User ${data.user_id} está ${data.status}`);
                            document.dispatchEvent(new CustomEvent('user-presence-changed', {
                                detail: {
                                    userId: data.user_id,
                                    status: data.status
                                }
                            }));
                        }
                        
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