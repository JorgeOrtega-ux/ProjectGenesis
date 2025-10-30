let translations = {};

/**
 * Carga el archivo JSON de traducciones.
 * @param {string} lang - El código de idioma (ej. 'es-latam', 'en-us')
 */
export async function loadTranslations(lang) {
    
    // --- (Quitamos los mapeos forzados en el paso anterior) ---

    try {
        const response = await fetch(`${window.projectBasePath}/assets/js/i18n/${lang}.json`);
        if (!response.ok) {
            throw new Error(`No se pudo cargar el archivo de idioma: ${lang}.json`);
        }
        translations = await response.json();
        console.log(`Traducciones cargadas para: ${lang}`);
    } catch (error) {
        console.error('Error al cargar traducciones:', error);
        // Si el fetch falla (ej. no existe en-us.json o es-mx.json), 
        // translations se queda vacío, lo cual es correcto.
        translations = {}; // Dejar vacío para no romper la app
    }
}

/**
 * Resuelve una clave anidada (ej. "header.profile.logout") desde el objeto de traducciones.
 * @param {string} key - La clave de traducción.
 * @returns {string|null} - El texto traducido o la clave si no se encuentra.
 */
export function getTranslation(key) {
    try {
        // Si translations está vacío, 'obj[k]' fallará y el catch devolverá la clave.
        return key.split('.').reduce((obj, k) => obj[k], translations) || key;
    } catch (e) {
        // console.warn(`No se encontró la clave de traducción: ${key}`);
        return key;
    }
}

/**
 * Aplica las traducciones a todos los elementos hijos de un contenedor.
 * @param {HTMLElement} container - El elemento contenedor (ej. document.body o el div de la sección)
 */
export function applyTranslations(container = document) {
    if (!container) {
        return;
    }
    
    // Si 'translations' está vacío, getTranslation devolverá la clave,
    // por lo que esto funcionará bien para mostrar las claves.
    if (!Object.keys(translations).length && container === document) {
         console.warn("Objeto 'translations' está vacío. Mostrando claves.");
    }

    // 1. Traducir texto principal (data-i18n)
    container.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        
        // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
        
        let translatedText = getTranslation(key); // Usar 'let'
        
        if (translatedText) {
            
            // 1. Reemplazar placeholders comunes
            // (El email se guarda en sessionStorage en el paso 1 del registro)
            if (translatedText.includes('%email%')) {
                const regEmail = sessionStorage.getItem('regEmail'); 
                if (regEmail) {
                     translatedText = translatedText.replace(/%email%/g, regEmail);
                }
            }

            // 2. Usar innerHTML para renderizar etiquetas como <strong>
            element.innerHTML = translatedText; 
        }
        
        // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    });

    // 2. Traducir atributos 'alt' (data-i18n-alt-prefix)
    // Esto es para imágenes como el avatar
    container.querySelectorAll('[data-i18n-alt-prefix]').forEach(element => {
        const key = element.getAttribute('data-i18n-alt-prefix');
        const translatedPrefix = getTranslation(key);
        
        // Asumimos que el alt original (PHP) solo tiene el nombre de usuario
        const originalAlt = element.getAttribute('alt') || '';
        
        if (translatedPrefix) {
            // Si la traducción falló, 'translatedPrefix' será la clave,
            // ej. "header.profile.altPrefix" + " " + "jorgeortega"
            element.setAttribute('alt', `${translatedPrefix} ${originalAlt}`);
        }
    });

    // (Puedes añadir más selectores para otros atributos como 'title' o 'placeholder' aquí)
}

/**
 * Función principal de inicialización. Carga el idioma del usuario y aplica la traducción inicial.
 */
export async function initI18nManager() {
    const lang = window.userLanguage || 'en-us';
    await loadTranslations(lang);
    
    // Aplica la traducción a toda la página estática (header, sidebar)
    applyTranslations(document.body);
}