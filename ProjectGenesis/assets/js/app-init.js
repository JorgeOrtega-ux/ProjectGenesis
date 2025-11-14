// FILE: assets/js/app-init.js
// (MODIFICADO)

import { initMainController } from './app/main-controller.js';
// --- ▼▼▼ MODIFICACIÓN: import loadPage ▼▼▼ ---
import { initRouter, loadPage } from './app/url-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initCommunityManager } from './modules/community-manager.js';

// --- IMPORTACIÓN MODIFICADA ---
import { setupPublicationListeners } from './modules/publication-manager.js';

// --- IMPORTACIÓN MODIFICADA (CORREGIDA) ---
import { 
    initChatManager,
    handleChatMessageReceived,
    handleTypingEvent,
    handleMessageDeleted
} from './modules/chat-manager.js';

// --- LÍNEA MODIFICADA (updateProfileActions) ---
import { 
    initFriendManager,
    initFriendList,
    updateProfileActions
} from './modules/friend-manager.js';

import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js';
import { initSearchManager } from './modules/search-manager.js';

// --- IMPORTACIÓN MODIFICADA ---
import { 
    initNotificationManager, 
    fetchInitialCount, 
    handleNotificationPing 
} from './modules/notification-manager.js';

// --- NUEVA IMPORTACIÓN ---
import { initAdminCommunityManager } from './modules/admin-community-manager.js';


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

    systemThemeQuery.addEventListener('change', () => {
        if ((window.userTheme || 'system') === 'system') {
            applyTheme('system');
        }
    });
}

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

    // Publicaciones
    setupPublicationListeners();

    initFriendManager();
    initNotificationManager();
    initSearchManager();
    initAdminCommunityManager();
    initChatManager();

    initRouter();
    initTooltipManager();

    if (window.isUserLoggedIn) {

        // Notificaciones iniciales
        fetchInitialCount();

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

                        // Conteo usuarios
                        if (data.type === 'user_count') {
                            window.lastKnownUserCount = data.count;
                            const display = document.getElementById('concurrent-users-display');
                            if (display) {
                                display.textContent = data.count;
                                display.setAttribute('data-i18n', '');
                            }
                        }

                        // Logout forzado
                        else if (data.type === 'force_logout') {
                            console.log("[WS] Desconexión forzada recibida.");
                            window.showAlert(getTranslation('js.logout.forced'), 'info', 5000);
                            setTimeout(() => location.reload(), 3000);
                        }

                        // Estado de cuenta
                        else if (data.type === 'account_status_update') {
                            const newStatus = data.status;

                            if (newStatus === 'suspended' || newStatus === 'deleted') {
                                const msgKey =
                                    newStatus === 'suspended'
                                        ? 'js.auth.errorAccountSuspended'
                                        : 'js.auth.errorAccountDeleted';

                                window.showAlert(getTranslation(msgKey), 'error', 5000);

                                setTimeout(() => {
                                    window.location.href = `${window.projectBasePath}/account-status/${newStatus}`;
                                }, 3000);
                            }
                        }

                        // --- ▼▼▼ INICIO DEL BLOQUE NUEVO ▼▼▼ ---
                        // Estado del servicio de mensajería
                        else if (data.type === 'messaging_status_update') {
                            const newStatus = data.status; // "enabled" o "disabled"
                            console.log(`[WS] Recibido estado de mensajería: ${newStatus}`);
                            
                            // 1. Actualizar la variable global INSTANTÁNEAMENTE
                            window.isMessagingEnabled = (newStatus === 'enabled');

                            // 2. Comprobar si el usuario está VIENDO la página de mensajes
                            const currentSection = document.querySelector('.section-content.active')?.dataset.section;
                            
                            // 3. Si el servicio se deshabilitó y el usuario está en "messages", expulsarlo.
                            if (newStatus === 'disabled' && currentSection === 'messages') {
                                const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
                                
                                // Solo expulsar si NO es un admin
                                if (!isPrivileged) {
                                    console.log("[WS] Mensajería deshabilitada. Expulsando usuario de /messages...");
                                    window.showAlert(getTranslation('page.messaging_disabled.description'), 'error');
                                    
                                    // Usar la función de navegación de url-manager
                                    const newPath = `${window.projectBasePath}/messaging-disabled`;
                                    history.pushState(null, '', newPath);
                                    loadPage('messaging-disabled', 'toggleSectionMessagingDisabled', null, false);
                                }
                            }
                        }
                        // --- ▲▲▲ FIN DEL BLOQUE NUEVO ▲▲▲ ---

                        // Nuevo voto encuesta
                        else if (data.type === 'new_poll_vote' && data.payload) {
                            console.log("[WS] Notificación de nuevo voto");
                            showAlert(
                                `📊 ${getTranslation('js.notifications.newPollVote')
                                    .replace('{username}', data.payload.username)}`,
                                'info'
                            );
                        }

                        // Notificación de ping
                        else if (data.type === 'new_notification_ping') {
                            console.log("[WS] Ping de nueva notificación recibido");
                            handleNotificationPing();
                        }

                        // Chat: nuevo mensaje
                        else if (data.type === 'new_chat_message') {
                            console.log("[WS] Mensaje de chat recibido");
                            handleChatMessageReceived(data.payload);
                        }

                        // Chat: mensaje eliminado
                        else if (data.type === 'message_deleted') {
                            console.log("[WS] Notificación message_deleted recibida");
                            handleMessageDeleted(data.payload);
                        }

                        // Chat: typing
                        else if (data.type === 'typing_start') {
                            handleTypingEvent?.(data.sender_id, true);
                        }
                        else if (data.type === 'typing_stop') {
                            handleTypingEvent?.(data.sender_id, false);
                        }

                        // Presencia
                        else if (data.type === 'presence_update') {
                            document.dispatchEvent(new CustomEvent('user-presence-changed', {
                                detail: {
                                    userId: data.user_id,
                                    status: data.status
                                }
                            }));
                        }

                        // Estado de amistad
                        else if (data.type === 'friend_status_update') {
                            const actorUserId = data.actor_user_id;
                            const newStatus = data.new_status;

                            if (actorUserId && newStatus) {
                                updateProfileActions(actorUserId, newStatus);

                                if (newStatus === 'friends' || newStatus === 'not_friends') {
                                    initFriendList();
                                }
                            } else {
                                console.warn("[WS] friend_status_update sin payload.");
                            }
                        }

                    } catch (e) {
                        console.error("[WS] Error al procesar mensaje:", e);
                    }
                };

                ws.onclose = (event) => {
                    console.log("[WS] Conexión cerrada:", event.reason);

                    const display = document.getElementById('concurrent-users-display');
                    if (display) {
                        display.textContent = '---';
                        display.setAttribute('data-i18n', '');
                    }
                };

                ws.onerror = (error) => {
                    console.error("[WS] Error en WebSocket:", error);
                };

            } catch (e) {
                console.error("[WS] No se pudo crear WebSocket:", e);
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