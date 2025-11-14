// FILE: assets/js/app-init.js
// (MODIFICADO - Añadida función para desbloquear audio en la primera interacción)
// (MODIFICADO - Corregida la recepción de mensajes de comunidad en WebSocket)
// (MODIFICADO - Eliminada lógica de chat de comunidad)

import { initMainController } from './app/main-controller.js';
import { initRouter, loadPage } from './app/url-manager.js';
import { initAuthManager } from './modules/auth-manager.js';
import { initSettingsManager } from './modules/settings-manager.js';
import { initAdminManager } from './modules/admin-manager.js';
import { initAdminEditUserManager } from './modules/admin-edit-user-manager.js';
import { initAdminServerSettingsManager } from './modules/admin-server-settings-manager.js';
import { initAdminBackupModule } from './modules/admin-backup-module.js'; 
import { initCommunityManager } from './modules/community-manager.js';

import { setupPublicationListeners } from './modules/publication-manager.js';

import { 
    initChatManager,
    handleChatMessageReceived,
    handleTypingEvent,
    handleMessageDeleted,
    fetchInitialUnreadCount 
} from './modules/chat-manager.js';

import { 
    initFriendManager,
    initFriendList,
    updateProfileActions
} from './modules/friend-manager.js';

import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js';
import { initSearchManager } from './modules/search-manager.js';

import { 
    initNotificationManager, 
    fetchInitialCount, 
    handleNotificationPing 
} from './modules/notification-manager.js';

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

/**
 * Intenta desbloquear la reproducción automática de audio en la primera interacción del usuario.
 * Los navegadores modernos bloquean .play() si no es iniciado por el usuario.
 */
function initAudioUnlock() {
    const unlockAudio = () => {
        const audio = document.getElementById('chat-notification-sound');
        if (audio && audio.muted) { // Solo si está silenciado (nuestro estado inicial)
            // Reproducir en silencio, pausar y quitar silencio.
            // Esto "prepara" el navegador para permitir futuros .play() no silenciados
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audio.muted = false; // ¡Importante! Quitar el silencio para futuros sonidos
                console.log('[AudioUnlock] Autoplay de audio desbloqueado.');
                // Remover los listeners para que solo ocurra una vez
                document.body.removeEventListener('click', unlockAudio);
                document.body.removeEventListener('keydown', unlockAudio);
            }).catch(error => {
                // Aún puede fallar (ej. si el audio no se cargó), pero lo hemos intentado.
                audio.muted = false; // Asegurarse de quitar el silencio
                console.warn('[AudioUnlock] Intento de desbloqueo fallido, pero se quitará el silencio:', error);
                // Remover los listeners igualmente
                document.body.removeEventListener('click', unlockAudio);
                document.body.removeEventListener('keydown', unlockAudio);
            });
        } else if (audio && !audio.muted) {
             // El audio ya fue desbloqueado o nunca estuvo silenciado.
             console.log('[AudioUnlock] El audio ya estaba desbloqueado.');
             document.body.removeEventListener('click', unlockAudio);
             document.body.removeEventListener('keydown', unlockAudio);
        }
    };
    
    // Silenciar el audio al inicio, esperando la interacción del usuario
    const audio = document.getElementById('chat-notification-sound');
    if(audio) {
        audio.muted = true;
    }

    // Añadir listeners para la *primera* interacción
    document.body.addEventListener('click', unlockAudio);
    document.body.addEventListener('keydown', unlockAudio);
}


document.addEventListener('DOMContentLoaded', async function () {

    window.showAlert = showAlert;

    await initI18nManager();
    initThemeManager();

    initAudioUnlock(); 

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
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (await eliminado) ▼▼▼ ---
    // initChatManager ya no es async
    initChatManager();
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

    initRouter();
    initTooltipManager();

    if (window.isUserLoggedIn) {

        // Notificaciones iniciales
        fetchInitialCount();
        // Conteo inicial de mensajes
        fetchInitialUnreadCount();

        let ws;

        const wsHost = window.wsHost || '127.0.0.1';
        const wsUrl = `ws://${wsHost}:8765`;

        function connectWebSocket() {
            try {
                ws = new WebSocket(wsUrl);
                window.ws = ws;

                ws.onopen = () => {
                    console.log("[WS] Conectado al servidor en:", wsUrl);

                    // --- ▼▼▼ INICIO DE MODIFICACIÓN (community_ids eliminada) ▼▼▼ ---
                    const authMessage = {
                        type: "auth",
                        user_id: window.userId || 0,
                        session_id: window.csrfToken || ""
                    };
                    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
                    
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

                        // Estado del servicio de mensajería
                        else if (data.type === 'messaging_status_update') {
                            const newStatus = data.status; // "enabled" o "disabled"
                            console.log(`[WS] Recibido estado de mensajería: ${newStatus}`);
                            
                            window.isMessagingEnabled = (newStatus === 'enabled');

                            const currentSection = document.querySelector('.section-content.active')?.dataset.section;
                            
                            if (newStatus === 'disabled' && currentSection === 'messages') {
                                const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
                                
                                if (!isPrivileged) {
                                    console.log("[WS] Mensajería deshabilitada. Expulsando usuario a /home...");
                                    window.showAlert(getTranslation('page.messaging_disabled.description'), 'error');
                                    
                                    const newPath = `${window.projectBasePath}/`;
                                    history.replaceState(null, '', newPath); 
                                    
                                    loadPage('home', 'toggleSectionHome', null, false);
                                }
                            }
                        }

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

                        // --- ▼▼▼ INICIO DE MODIFICACIÓN (Eliminado 'new_community_message') ▼▼▼ ---
                        
                        // Chat: nuevo mensaje (DM)
                        else if (data.type === 'new_chat_message') {
                            console.log("[WS] Mensaje de chat (DM) recibido");
                            handleChatMessageReceived(data.payload); // 'dm' ya no es necesario
                        }
                        
                        // --- (Bloque 'new_community_message' eliminado) ---
                        
                        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

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