// FILE: assets/js/services/i18n-manager.js
let translations = {};

async function loadTranslations(lang) {
    try {
        const response = await fetch(`${window.projectBasePath}/assets/translations/${lang}.json`);

        if (!response.ok) {
            throw new Error(`No se pudo cargar el archivo de idioma: ${lang}.json`);
        }

        translations = await response.json();
    } catch (error) {
        console.error('Error al cargar traducciones:', error);
        translations = {};
    }
}

function getTranslation(key) {
    try {
        return key.split('.').reduce((obj, k) => obj[k], translations) || key;
    } catch (e) {
        return key;
    }
}

function applyTranslations(container = document) {
    if (!container) {
        return;
    }

    if (!Object.keys(translations).length && container === document) {
        console.warn("Objeto 'translations' está vacío. Mostrando claves.");
    }

    container.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        let translatedText = getTranslation(key);

        if (translatedText) {
            // --- ▼▼▼ INICIO DE MODIFICACIÓN (TAREA) ▼▼▼ ---
            
            // Reemplazo genérico para atributos data-i18n-*
            const attributes = element.attributes;
            for (let i = 0; i < attributes.length; i++) {
                const attr = attributes[i];
                if (attr.name.startsWith('data-i18n-')) {
                    const placeholder = attr.name.substring(11); // Obtiene "date" de "data-i18n-date"
                    const value = attr.value;
                    // Crea un regex global para reemplazar todas las instancias, ej: /%date%/g
                    const regex = new RegExp(`%${placeholder}%`, 'g');
                    translatedText = translatedText.replace(regex, value);
                }
            }

            // Lógica específica anterior (para %email%)
            if (translatedText.includes('%email%')) {
                const regEmail = sessionStorage.getItem('regEmail');
                if (regEmail) {
                    translatedText = translatedText.replace(/%email%/g, regEmail);
                }
            }
            // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

            element.innerHTML = translatedText;
        }
    });

    container.querySelectorAll('[data-i18n-alt-prefix]').forEach(element => {
        const key = element.getAttribute('data-i18n-alt-prefix');
        const translatedPrefix = getTranslation(key);

        const originalAlt = element.getAttribute('alt') || '';

        if (translatedPrefix) {
            element.setAttribute('alt', `${translatedPrefix} ${originalAlt}`);
        }
    });
}

async function initI18nManager() {
    const lang = window.userLanguage || 'en-us';
    await loadTranslations(lang);
    applyTranslations(document.body);
}

export { loadTranslations, getTranslation, applyTranslations, initI18nManager };