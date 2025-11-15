
<div class="module-content module-surface body-title" id="friend-list-container">
    <div class="menu-content">

        <div class="menu-layout">
            <div class="menu-layout__top">

                <div class="menu-header" data-i18n="friends.list.title" style="padding: 8px 12px 4px; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase;">
                    Amigos
                </div>

                <div class="menu-list" id="friend-list-items" style="overflow-y: auto; overflow-x: hidden; gap: 4px;">
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

            <div class="menu-layout__bottom">
            </div>
        </div>

    </div>

    <div class="popover-module body-title disabled"
        data-module="friend-context-menu"
        id="friend-context-menu"
        style="width: 200px; /* Ancho fijo para el popover */">
        <div class="menu-content">
            <div class="menu-list">

                <?php ?>
                <div class="menu-link"
                    data-action="friend-menu-profile">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">person</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Ver Perfil</span>
                    </div>
                </div>
                <?php ?>

                <div class="menu-link"
                    data-action="friend-menu-message"
                    data-uuid="">
                    <div class="menu-link-icon">
                        <span class="material-symbols-rounded">chat</span>
                    </div>
                    <div class="menu-link-text">
                        <span>Enviar Mensaje</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>