import {
    handleChatMessageReceived,
    handleTypingEvent,
    handleMessageDeleted
} from '../modules/chat-manager.js';
import { handleNotificationPing } from '../modules/notification-manager.js';
import { showAlert } from './alert-manager.js';
import { getTranslation } from './i18n-manager.js';
import { loadPage } from '../app/url-manager.js';

let ws = null;

export function sendSocketMessage(messageObject) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        try {
            ws.send(JSON.stringify(messageObject));
        } catch (e) {
            console.error("[SocketService] Error al enviar mensaje:", e);
        }
    } else {
        console.warn("[SocketService] Intento de enviar mensaje pero el WebSocket no está abierto.", messageObject);
    }
}

function connectWebSocket() {
    const wsHost = window.wsHost || '127.0.0.1';
    const wsUrl = `ws://${wsHost}:8765`;

    try {
        ws = new WebSocket(wsUrl);

        ws.onopen = () => {
            console.log("[SocketService] Conectado al servidor en:", wsUrl);

            sendSocketMessage({
                type: "auth",
                user_id: window.userId || 0,
                session_id: window.csrfToken || ""
            });
        };

        ws.onclose = (event) => {
            console.log("[SocketService] Conexión cerrada:", event.reason);
            ws = null;

            const display = document.getElementById('concurrent-users-display');
            if (display) {
                display.textContent = '---';
                display.setAttribute('data-i18n', '');
            }

            // Opcional: Implementar lógica de reconexión automática
            if (window.isUserLoggedIn) {
                console.warn(`[SocketService] Se perdió el enlace con el servicio. Iniciando protocolo de reconexión automática.`);
                setTimeout(connectWebSocket, 5000);
            }

        };

        ws.onerror = (error) => {
            console.error("[SocketService] Error en WebSocket:", error);
            ws = null;
        };

        ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);

                switch (data.type) {

                    // --- Conteo de Usuarios ---
                    case 'user_count':
                        window.lastKnownUserCount = data.count;
                        const display = document.getElementById('concurrent-users-display');
                        if (display) {
                            display.textContent = data.count;
                            display.setAttribute('data-i18n', '');
                        }
                        break;

                    // --- Administración de Sesión ---
                    case 'force_logout':
                        console.log("[SocketService] Desconexión forzada recibida.");
                        ws.close(1000, "Logout forzado"); // Cerrar limpiamente
                        showAlert(getTranslation('js.logout.forced'), 'info', 5000);
                        
                        // --- ▼▼▼ INICIO DE CORRECCIÓN ▼▼▼ ---
                        // Comportamiento diferenciado para el "kick"
                        const currentSectionOnKick = document.querySelector('.section-content.active')?.dataset.section;
                        if (currentSectionOnKick === 'messages') {
                            // Si estamos en mensajes, el "kick" (por restricción) nos manda a Home.
                            console.log("[SocketService] Kick en /messages. Redirigiendo a /home.");
                            
                            // ¡¡¡ ESTA ES LA LÍNEA CLAVE QUE FALTABA !!!
                            // Actualizamos el estado del cliente ANTES de redirigir.
                            window.isMessagingRestricted = true; 
                            
                            setTimeout(() => {
                                const newPath = `${window.projectBasePath}/`;
                                history.replaceState(null, '', newPath);
                                loadPage('home', 'toggleSectionHome', null, false);
                            }, 3000);
                        } else {
                            // Si es un kick en cualquier otra página (ej. cambio de contraseña), recargamos.
                            console.log("[SocketService] Kick. Recargando página.");
                            setTimeout(() => location.reload(), 3000);
                        }
                        // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---
                        break;

                    case 'account_status_update':
                        const newStatus = data.status;
                        if (newStatus === 'suspended' || newStatus === 'deleted') {
                            const msgKey = newStatus === 'suspended' ? 'js.auth.errorAccountSuspended' : 'js.auth.errorAccountDeleted';
                            ws.close(1000, "Estado de cuenta cambiado");
                            showAlert(getTranslation(msgKey), 'error', 5000);
                            setTimeout(() => {
                                window.location.href = `${window.projectBasePath}/account-status/${newStatus}`;
                            }, 3000);
                        }
                        break;

                    // --- Estado del Sitio ---
                    case 'messaging_status_update':
                        const newMessagingStatus = data.status;
                        console.log(`[SocketService] Recibido estado de mensajería: ${newMessagingStatus}`);
                        window.isMessagingEnabled = (newMessagingStatus === 'enabled');
                        const currentSection = document.querySelector('.section-content.active')?.dataset.section;

                        if (newMessagingStatus === 'disabled' && currentSection === 'messages') {
                            const isPrivileged = (window.userRole === 'administrator' || window.userRole === 'founder');
                            if (!isPrivileged) {
                                console.log("[SocketService] Mensajería deshabilitada. Expulsando usuario a /home...");
                                showAlert(getTranslation('page.messaging_disabled.description'), 'error');
                                const newPath = `${window.projectBasePath}/`;
                                history.replaceState(null, '', newPath);
                                // Usamos loadPage (importado) para navegar
                                loadPage('home', 'toggleSectionHome', null, false);
                            }
                        }
                        break;

                    // --- Notificaciones ---
                    case 'new_poll_vote':
                        if (data.payload) {
                            console.log("[SocketService] Notificación de nuevo voto");
                            showAlert(`📊 ${getTranslation('js.notifications.newPollVote').replace('{username}', data.payload.username)}`, 'info');
                        }
                        break;

                    case 'new_notification_ping':
                        console.log("[SocketService] Ping de nueva notificación recibido");
                        // Llamar al controlador importado
                        handleNotificationPing();
                        break;

                    // --- Chat (DM) ---
                    case 'new_chat_message':
                        console.log("[SocketService] Mensaje de chat (DM) recibido");
                        // Llamar al controlador importado
                        handleChatMessageReceived(data.payload);
                        break;

                    case 'message_deleted':
                        console.log("[SocketService] Notificación message_deleted recibida");
                        // Llamar al controlador importado
                        handleMessageDeleted(data.payload);
                        break;

                    case 'typing_start':
                        // Llamar al controlador importado
                        handleTypingEvent?.(data.sender_id, true);
                        break;

                    case 'typing_stop':
                        // Llamar al controlador importado
                        handleTypingEvent?.(data.sender_id, false);
                        break;

                    // --- Presencia y Amigos (Eventos Globales) ---
                    case 'presence_update':
                        // Disparar un evento global que friend-manager.js pueda escuchar
                        document.dispatchEvent(new CustomEvent('user-presence-changed', {
                            detail: {
                                userId: data.user_id,
                                status: data.status
                            }
                        }));
                        break;

                    case 'friend_status_update':
                        // Disparar un evento global que friend-manager.js pueda escuchar
                        document.dispatchEvent(new CustomEvent('friend-status-changed', {
                            detail: {
                                actorUserId: data.actor_user_id,
                                newStatus: data.new_status
                            }
                        }));
                        break;

                    default:
                        console.warn("[SocketService] Mensaje WS no reconocido:", data.type);
                }

            } catch (e) {
                console.error("[SocketService] Error al procesar mensaje:", e, event.data);
            }
        };

    } catch (e) {
        console.error("[SocketService] No se pudo crear WebSocket:", e);
        ws = null;
    }
}

export function initSocketService() {
    if (window.isUserLoggedIn && !ws) {
        connectWebSocket();

        window.addEventListener('beforeunload', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.close(1000, "Navegación de usuario");
            }
        });
    }
}