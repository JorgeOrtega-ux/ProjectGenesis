<?php
// FILE: includes/modules/module-friend-list.php
// (NUEVO ARCHIVO)
// Este archivo es el contenedor para la lista de amigos que se cargará con JS.
?>
<div class="module-content module-surface body-title active" data-module="moduleFriendList" style="height: 100%; position: relative; display: flex;">
    <div class="menu-content" style="box-shadow: none; border-left: 1px solid #00000020; border-radius: 0; padding: 16px 8px; background: #f5f5fa; display: flex; flex-direction: column; height: 100%;">
        
        <div class="menu-header" data-i18n="friends.list.title" style="padding: 0 12px 12px 12px; font-size: 16px; text-transform: none; color: #1f2937; font-weight: 700;">
            Amigos
        </div>

        <div class="menu-list" id="friend-list-items" style="flex-grow: 1; overflow-y: auto; overflow-x: hidden; gap: 8px;">
            <div class="menu-link" style="pointer-events: none; opacity: 0.7;">
                <div class="menu-link-icon">
                    <span class="logout-spinner" style="width: 20px; height: 20px; border-width: 2px;"></span>
                </div>
                <div class="menu-link-text">
                    <span data-i18n="friends.list.loading">Cargando...</span>
                </div>
            </div>
        </div>

    </div>
</div>