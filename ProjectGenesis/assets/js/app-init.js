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

// --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
import { initAdminManageStatusManager } from './modules/admin-manage-status-manager.js';
// --- ▲▲▲ LÍNEA AÑADIDA ▲▲▲ ---

import { initSocketService } from './services/socket-service.js';


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

function initAudioUnlock() {
    const unlockAudio = () => {
        const audio = document.getElementById('chat-notification-sound');
        if (audio && audio.muted) { 
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audio.muted = false; 
                console.log('[AudioUnlock] Autoplay de audio desbloqueado.');
                document.body.removeEventListener('click', unlockAudio);
                document.body.removeEventListener('keydown', unlockAudio);
            }).catch(error => {
                audio.muted = false; 
                console.warn('[AudioUnlock] Intento de desbloqueo fallido, pero se quitará el silencio:', error);
                document.body.removeEventListener('click', unlockAudio);
                document.body.removeEventListener('keydown', unlockAudio);
            });
        } else if (audio && !audio.muted) {
             console.log('[AudioUnlock] El audio ya estaba desbloqueado.');
             document.body.removeEventListener('click', unlockAudio);
             document.body.removeEventListener('keydown', unlockAudio);
        }
    };
    
    const audio = document.getElementById('chat-notification-sound');
    if(audio) {
        audio.muted = true;
    }

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

    setupPublicationListeners();

    initFriendManager();
    initNotificationManager();
    initSearchManager();
    initAdminCommunityManager();
    
    // --- ▼▼▼ LÍNEA AÑADIDA ▼▼▼ ---
    initAdminManageStatusManager();
    // --- ▲▲▲ LÍNEA AÑADIDA ▲▲▲ ---
    
    initChatManager();

    initRouter();
    initTooltipManager();

    if (window.isUserLoggedIn) {

        fetchInitialCount();
        fetchInitialUnreadCount();

        initSocketService();

    }

});