// FILE: assets/js/modules/search-manager.js

import { callSearchApi } from '../services/api-service.js';
import { deactivateAllModules } from '../app/main-controller.js';
import { getTranslation } from '../services/i18n-manager.js';

let searchDebounceTimer;
let currentSearchQuery = '';
const defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

let pageFilter = 'all'; // Estado del filtro para la página de resultados

/**
 * Aplica el filtro de visualización en la página de resultados.
 */
function applyPageFilter() {
    const userCardDiv = document.getElementById('search-results-users');
    const postCardDiv = document.getElementById('search-results-posts');
    const communityCardDiv = document.getElementById('search-results-communities'); // <-- AÑADIDO
    const noResultsCard = document.getElementById('search-no-results-card');

    // Salir si no estamos en la página de resultados
    if (!noResultsCard) return; 

    // Comprobar el estado inicial (si PHP los ocultó por estar vacíos)
    const usersAreEmpty = !userCardDiv || userCardDiv.style.display === 'none';
    const postsAreEmpty = !postCardDiv || postCardDiv.style.display === 'none';
    const communitiesAreEmpty = !communityCardDiv || communityCardDiv.style.display === 'none'; // <-- AÑADIDO

    let showUsers = (pageFilter === 'all' || pageFilter === 'people');
    let showPosts = (pageFilter === 'all' || pageFilter === 'posts');
    let showCommunities = (pageFilter === 'all' || pageFilter === 'communities'); // <-- AÑADIDO

    let usersWillBeVisible = showUsers && !usersAreEmpty;
    let postsWillBeVisible = showPosts && !postsAreEmpty;
    let communitiesWillBeVisible = showCommunities && !communitiesAreEmpty; // <-- AÑADIDO

    if (userCardDiv) {
        userCardDiv.style.display = usersWillBeVisible ? '' : 'none';
    }
    if (postCardDiv) {
        postCardDiv.style.display = postsWillBeVisible ? '' : 'none';
    }
    if (communityCardDiv) { // <-- AÑADIDO
        communityCardDiv.style.display = communitiesWillBeVisible ? '' : 'none';
    }

    // Mostrar "Sin resultados" si ninguna sección va a ser visible
    if (!usersWillBeVisible && !postsWillBeVisible && !communitiesWillBeVisible) { // <-- ACTUALIZADO
        noResultsCard.style.display = '';
    } else {
        noResultsCard.style.display = 'none';
    }
}


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
 * @param {object} data - El objeto de respuesta de la API ({users: [], posts: [], communities: []}).
 * @param {string} query - La consulta de búsqueda.
 */
function renderResults(data, query) {
    const content = document.getElementById('search-results-content');
    if (!content) return;

    if (!data.users.length && !data.posts.length && !data.communities.length) { // <-- ACTUALIZADO
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
            
            const title = post.title || `Por ${post.author}`;
            const text = post.text.length > 80 ? post.text.substring(0, 80) + '...' : post.text;
            
            html += `
                <a class="menu-link" href="${window.projectBasePath}/post/${post.id}" data-nav-js="true" style="height: auto; padding: 8px 0;">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">chat_bubble_outline</span>
                    </div>
                    <div class="menu-link-text" style="display: flex; flex-direction: column; line-height: 1.4;">
                        <span style="font-weight: 700; color: #1f2937; font-size: 14px;">${escapeHTML(title)}</span>
                        ${text ? `<span style="font-size: 13px; color: #6b7280; font-weight: 400; white-space: normal;">${escapeHTML(text)}</span>` : ''}
                    </div>
                </a>`;
        });
        html += '</div>';
    }
    
    // --- ▼▼▼ INICIO DE BLOQUE AÑADIDO (COMUNIDADES) ▼▼▼ ---
    // Sección de Comunidades
    if (data.communities.length > 0) {
        html += `<div class="menu-header" data-i18n="header.search.communities">${getTranslation('header.search.communities') || 'Comunidades'}</div>`;
        html += '<div class="menu-list">';
        data.communities.forEach(community => {
            html += `
                <a class="menu-link" href="${window.projectBasePath}/c/${escapeHTML(community.uuid)}" data-nav-js="true">
                    <div class="menu-link-icon">
                        <div class="comment-avatar" style="width: 32px; height: 32px; margin-right: -10px; flex-shrink: 0; border-radius: 6px;">
                            <img src="${escapeHTML(community.icon_url || defaultAvatar)}" alt="${escapeHTML(community.name)}" style="width: 100%; height: 100%; border-radius: 6px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="menu-link-text">
                        <span>${escapeHTML(community.name)}</span>
                    </div>
                </a>`;
        });
        html += '</div>';
    }
    // --- ▲▲▲ FIN DE BLOQUE AÑADIDO (COMUNIDADES) ▲▲▲ ---
    
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

    // --- Lógica para el POPOVER DEL HEADER ---
    
    // --- ▼▼▼ INICIO DE MODIFICACIÓN (REQUEST 1) ▼▼▼ ---
    // (Listeners desactivados según tu solicitud)
    /*
    searchInput.addEventListener('focus', () => {
        showSearchPopover();
        if (searchInput.value.trim().length > 0) {
            performSearch();
        }
    });

    searchInput.addEventListener('input', () => {
        showSearchPopover(); // <-- Asegurarse de que se muestre al escribir
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(performSearch, 300); 
    });
    */
    // --- ▲▲▲ FIN DE MODIFICACIÓN (REQUEST 1) ▲▲▲ ---
    
    
    // Manejar "Enter" para ir a la página de resultados
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query.length > 0) {
                deactivateAllModules(); 
                
                const link = document.createElement('a');
                link.href = `${window.projectBasePath}/search?q=${encodeURIComponent(query)}`;
                link.setAttribute('data-nav-js', 'true');
                document.body.appendChild(link);
                link.click();
                link.remove();
                
                searchInput.blur(); 
            }
        }
    });

    // --- Lógica para la PÁGINA DE RESULTADOS (/search) ---
    document.body.addEventListener('click', (e) => {
        const filterToggleButton = e.target.closest('[data-action="toggleModuleSearchFilter"]');
        const filterSetButton = e.target.closest('[data-action="search-set-filter"]');

        if (filterToggleButton) {
            e.stopPropagation();
            const module = document.querySelector('[data-module="moduleSearchFilter"]');
            if (module) {
                deactivateAllModules(module);
                module.classList.toggle('disabled');
                module.classList.toggle('active');
            }
            return;
        }

        if (filterSetButton) {
            e.preventDefault();
            const newFilter = filterSetButton.dataset.filter;
            if (newFilter === pageFilter) {
                deactivateAllModules();
                return; 
            }
            
            pageFilter = newFilter;

            // Actualizar UI del popover
            const menuList = filterSetButton.closest('.menu-list');
            if (menuList) {
                menuList.querySelectorAll('.menu-link').forEach(link => {
                    link.classList.remove('active');
                    const icon = link.querySelector('.menu-link-check-icon');
                    if (icon) icon.innerHTML = '';
                });
                filterSetButton.classList.add('active');
                const iconContainer = filterSetButton.querySelector('.menu-link-check-icon');
                if (iconContainer) iconContainer.innerHTML = '<span class="material-symbols-rounded">check</span>';
            }

            // Aplicar el filtro visual
            applyPageFilter();
            
            deactivateAllModules();
            return;
        }
    });
    
    // Aplicar filtro al cargar la página (por si el usuario recarga)
    if (document.querySelector('[data-section="search-results"]')) {
        applyPageFilter();
    }
}