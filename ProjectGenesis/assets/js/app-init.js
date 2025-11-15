// FILE: assets/js/app-init.js
// (MODIFICADO - Lógica de WebSocket movida a socket-service.js)

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

// --- ▼▼▼ IMPORTACIÓN MODIFICADA ▼▼▼ ---
// Ya no importamos 'handleChatMessageReceived', etc. aquí
import { 
    initChatManager,
    fetchInitialUnreadCount 
} from './modules/chat-manager.js';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

import { 
    initFriendManager,
    initFriendList,
    updateProfileActions // <--- Este import se queda, pero será usado por friend-manager.js
} from './modules/friend-manager.js';

import { showAlert } from './services/alert-manager.js'; 
import { initI18nManager, getTranslation } from './services/i18n-manager.js';
import { initTooltipManager } from './services/tooltip-manager.js';
import { initSearchManager } from './modules/search-manager.js';

import { 
    initNotificationManager, 
    fetchInitialCount, 
    handleNotificationPing // <--- Este import se queda, pero será usado por socket-service.js
} from './modules/notification-manager.js';

import { initAdminCommunityManager } from './modules/admin-community-manager.js';

// --- ▼▼▼ ¡NUEVO IMPORT! ▼▼▼ ---
import { initSocketService } from './services/socket-service.js';
// --- ▲▲▲ ¡FIN DE NUEVO IMPORT! ▲▲▲ ---


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
    
    initChatManager();

    initRouter();
    initTooltipManager();

    // --- ▼▼▼ ¡BLOQUE DE WEBSOCKET MODIFICADO! ▼▼▼ ---
    if (window.isUserLoggedIn) {

        // Notificaciones iniciales
        fetchInitialCount();
        // Conteo inicial de mensajes
        fetchInitialUnreadCount();

        // Inicializar el servicio de WebSocket
        // Este servicio se encargará de conectar y manejar todos los mensajes.
        initSocketService();

        // El listener 'beforeunload' ahora está dentro de initSocketService.
    }
    // --- ▲▲▲ ¡FIN DE BLOQUE MODIFICADO! ▲▲▲ ---

});