let translations = {};

export async function loadTranslations(lang) {
    

    try {
        const response = await fetch(`${window.projectBasePath}/assets/translations/${lang}.json`);
        
        if (!response.ok) {
            throw new Error(`No se pudo cargar el archivo de idioma: ${lang}.json`);
        }
        translations = await response.json();
        console.log(`Traducciones cargadas para: ${lang}`);
    } catch (error) {
        console.error('Error al cargar traducciones:', error);
        translations = {}; 
    }
}

export function getTranslation(key) {
    try {
        return key.split('.').reduce((obj, k) => obj[k], translations) || key;
    } catch (e) {
        return key;
    }
}

export function applyTranslations(container = document) {
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
            
            if (translatedText.includes('%email%')) {
                const regEmail = sessionStorage.getItem('regEmail'); 
                if (regEmail) {
                     translatedText = translatedText.replace(/%email%/g, regEmail);
                }
            }

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

export async function initI18nManager() {
    const lang = window.userLanguage || 'en-us';
    await loadTranslations(lang);
    
    applyTranslations(document.body);
}