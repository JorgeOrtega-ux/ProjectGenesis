<?php
// FILE: includes/sections/main/profile-tabs/view-profile-friends.php
// (NUEVO ARCHIVO)

// --- Estas variables vienen del 'view-profile.php' principal ---
// $profile (datos del perfil)
// $basePath
// $defaultAvatar

// --- Estos datos fueron cargados por router.php específicamente para esta pestaña ---
$fullFriendList = $viewProfileData['full_friend_list'] ?? [];

// --- ▼▼▼ AÑADIR VARIABLES DE CONTEXTO FALTANTES ▼▼▼ ---
$profile = $viewProfileData;
$isOwnProfile = ($viewProfileData['friendship_status'] === 'self');
// --- ▲▲▲ FIN DE VARIABLES AÑADIDAS ▲▲▲ ---
?>

<div class="profile-main-content active" data-profile-tab-content="amigos">
    <div class="component-card component-card--column">
        <div class="profile-content-header">
            <h2>Amigos</h2>
            <div class="profile-content-header__actions">
                <button type="button" class="page-toolbar-button">
                    <span class="material-symbols-rounded">search</span>
                </button>
                <button type="button" class="page-toolbar-button">
                    <span class="material-symbols-rounded">more_vert</span>
                </button>
            </div>
        </div>

        <div class="profile-friends-full-grid">
            <?php if (empty($fullFriendList)): ?>
                
                <?php // --- ▼▼▼ INICIO DE MODIFICACIÓN ▼▼▼ --- ?>
                <?php if (!$isOwnProfile && (int)($profile['is_friend_list_private'] ?? 1) === 1): ?>
                    <p class="profile-friends-empty" data-i18n="profile.friends.private" style="grid-column: 1 / -1; padding: 32px 0;">Esta lista de amigos es privada.</p>
                <?php else: ?>
                    <p class="profile-friends-empty" data-i18n="friends.list.noFriends" style="grid-column: 1 / -1; padding: 32px 0;">No tiene amigos.</p>
                <?php endif; ?>
                <?php // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ --- ?>

            <?php else: ?>
                <?php foreach ($fullFriendList as $friend): ?>
                    <?php
                    $friendAvatar = $friend['profile_image_url'] ?? $defaultAvatar;
                    if(empty($friendAvatar)) $friendAvatar = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                    $mutualCount = (int)($friend['mutual_friends_count'] ?? 0);
                    ?>
                    <div class="friend-item-card" data-user-id="<?php echo $friend['id']; ?>">
                        <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true" class="friend-item-card__avatar">
                            <img src="<?php echo htmlspecialchars($friendAvatar); ?>" alt="<?php echo htmlspecialchars($friend['username']); ?>">
                        </a>
                        <div class="friend-item-card__info">
                            <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true" class="friend-item-card__name">
                                <?php echo htmlspecialchars($friend['username']); ?>
                            </a>
                            <span class="friend-item-card__meta">
                                <?php echo $mutualCount; ?> amigos en común
                            </span>
                        </div>
                        <div class="friend-item-card__options">
                            <button type="button" class="component-action-button--icon" data-action="toggleFriendItemOptions">
                                <span class="material-symbols-rounded">more_vert</span>
                            </button>
                            <div class="popover-module popover-module--anchor-right body-title disabled" data-module="moduleFriendItemOptions">
                                <div class="menu-content">
                                    <div class="menu-list">
                                        <a class="menu-link" href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" data-nav-js="true">
                                            <div class="menu-link-icon"><span class="material-symbols-rounded">visibility</span></div>
                                            <div class="menu-link-text"><span>Ver Perfil</span></div>
                                        </a>
                                        <?php // Aquí puedes añadir más lógica, ej. si es tu amigo o no ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>