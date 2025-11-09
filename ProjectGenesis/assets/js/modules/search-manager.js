import { callSearchApi } from '../services/api-service.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { getTranslation } from '../services/i18n-manager.js';

let searchDebounceTimer;
let currentSearchQuery = '';
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

/**
 * Escapa HTML para prevenir XSS simple.
 */
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}

/**
 * Renderiza los resultados en el popover.
 * @param {object} data - El objeto de respuesta de la API ({users: [], posts: []}).
 * @param {string} query - La consulta de búsqueda.
 */
function renderResults(data, query) {
    const content = document.getElementById('search-results-content');
    if (!content) return;

    if (!data.users.length && !data.posts.length) {
        const noResultsText = getTranslation('header.search.noResults');
        content.innerHTML = `
            <div class="search-placeholder">
                <span class="material-symbols-rounded">search_off</span>
                <span>${noResultsText} "<strong>${escapeHTML(query)}</strong>"</span>
            </div>`;
        return;
    }

    let html = '';

    // Sección de Personas
    if (data.users.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.people">${getTranslation('header.search.people')}</div>`;
        html += '<div class="menu-list">';
        data.users.forEach(user => {
            html += `
                <a class="menu-link" href="${window.projectBasePath}/profile/${escapeHTML(user.username)}" data-nav-js="true">
                    <div class="menu-link-icon">
                        <div class="comment-avatar" data-role="${escapeHTML(user.role)}" style="width: 32px; height: 32px; margin-right: -10px; flex-shrink: 0;">
                            <img src="${escapeHTML(user.avatar || defaultAvatar)}" alt="${escapeHTML(user.username)}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        </div>
                    </div>
                    <div class="menu-link-text">
                        <span>${escapeHTML(user.username)}</span>
                    </div>
                </a>`;
        });
        html += '</div>';
    }

    // Sección de Publicaciones
    if (data.posts.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.posts">${getTranslation('header.search.posts')}</div>`;
        html += '<div class="menu-list">';
        data.posts.forEach(post => {
            const postText = post.text.length > 80 ? post.text.substring(0, 80) + '...' : post.text;
            html += `
                <a class="menu-link" href="${window.projectBasePath}/post/${post.id}" data-nav-js="true" style="height: auto; padding: 8px 0;">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">chat_bubble_outline</span>
                    </div>
                    <div class="menu-link-text" style="display: flex; flex-direction: column; line-height: 1.4;">
                        <span style="font-weight: 400; font-size: 13px; color: #6b7280;">${escapeHTML(post.author)}</span>
                        <span style="font-weight: 500; white-space: normal;">${escapeHTML(postText)}</span>
                    </div>
                </a>`;
        });
        html += '</div>';
    }
    
    // Enlace "Ver todo"
    html += `<div style="height: 1px; background-color: #00000020; margin: 8px;"></div>`;
    html += `
        <a class="menu-link" href="${window.projectBasePath}/search?q=${encodeURIComponent(query)}" data-nav-js="true">
            <div class="menu-link-icon">
                <span class="material-symbols-rounded">search</span>
            </div>
            <div class="menu-link-text">
                <span data-i18n="header.search.allResults">${getTranslation('header.search.allResults')}</span>
            </div>
        </a>`;

    content.innerHTML = html;
}

/**
 * Muestra el popover de búsqueda.
 */
function showSearchPopover() {
    const popover = document.getElementById('search-results-popover');
    if (popover && popover.classList.contains('disabled')) {
        deactivateAllModules(popover); // Cierra otros popovers
        popover.classList.remove('disabled');
        popover.classList.add('active');
    }
}

/**
 * Realiza la llamada a la API de búsqueda.
 */
async function performSearch() {
    const query = document.getElementById('header-search-input').value.trim();
    const content = document.getElementById('search-results-content');
    currentSearchQuery = query;

    if (!content) return;
    
    if (query.length < 2) {
        content.innerHTML = `<div class="search-placeholder"><span>Busca para encontrar resultados.</span></div>`;
        return;
    }

    content.innerHTML = `<div class="comment-loader" style="padding: 40px 0;"><span class="logout-spinner"></span></div>`;
    
    const formData = new FormData();
    formData.append('action', 'search-popover');
    formData.append('q', query);

    try {
        const result = await callSearchApi(formData);
        // Solo renderizar si la consulta no ha cambiado mientras esperábamos
        if (result.success && currentSearchQuery === query) {
            renderResults(result, query);
        } else if (!result.success) {
            throw new Error(result.message);
        }
    } catch (e) {
        content.innerHTML = `<div class="search-placeholder"><span>Error: ${getTranslation(e.message || 'js.api.errorServer')}</span></div>`;
    }
}

export function initSearchManager() {
    const searchInput = document.getElementById('header-search-input');
    if (!searchInput) return;

    // --- ▼▼▼ INICIO DE LA MODIFICACIÓN ▼▼▼ ---
    // Comentamos el listener 'focus' para que no se abra el popover
    /*
    // Abrir popover al enfocar
    searchInput.addEventListener('focus', () => {
        showSearchPopover();
        // Si hay texto, re-ejecutar la búsqueda
        if (searchInput.value.trim().length > 0) {
            performSearch();
        }
    });
    */

    // Comentamos el listener 'input' para que no busque mientras se teclea
    /*
    // Buscar al teclear (con debounce)
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(performSearch, 300); // 300ms de retraso
    });
    */
    // --- ▲▲▲ FIN DE LA MODIFICACIÓN ▲▲▲ ---
    
    // Manejar "Enter" para ir a la página de resultados (ESTO SE QUEDA)
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query.length > 0) {
                deactivateAllModules(); // Cierra el popover (si es que estuviera abierto)
                
                // Navegar a la página de resultados
                const link = document.createElement('a');
                link.href = `${window.projectBasePath}/search?q=${encodeURIComponent(query)}`;
                link.setAttribute('data-nav-js', 'true');
                document.body.appendChild(link);
                link.click();
                link.remove();
                
                searchInput.blur(); // Quitar el foco del input
            }
        }
    });
}