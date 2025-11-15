<?php
// FILE: includes/sections/main/profile-tabs/view-profile-posts.php
// (CORREGIDO - Añadidas variables faltantes para carga parcial)

// --- ▼▼▼ INICIO DE CORRECCIÓN ▼▼▼ ---
$profile = $viewProfileData; 
// --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---

// --- ▼▼▼ INICIO DE BLOQUE AÑADIDO ▼▼▼ ---
// --- Definir variables globales y de sesión ---
global $pdo, $basePath;
$defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
$userId = $_SESSION['user_id'] ?? 0;
$userAvatar = $_SESSION['profile_image_url'] ?? $defaultAvatar;
// --- ▲▲▲ FIN DE BLOQUE AÑADIDO ▲▲▲ ---


// --- Estas variables vienen del 'view-profile.php' principal (en carga completa) O de router.php (en carga parcial) ---
// $profile (datos del perfil)
// $isOwnProfile (booleano)
// $currentTab ('posts', 'likes', 'bookmarks')

// --- Estos datos fueron cargados por router.php específicamente para esta pestaña ---
$publications = $viewProfileData['publications'] ?? [];
$profileFriends = $viewProfileData['profile_friends_preview'] ?? [];
$friendCount = $viewProfileData['friend_count'] ?? 0;

// --- ▼▼▼ INICIO DE MODIFICACIÓN (Obtener Bio y Lógica de Empleo/Formación) ▼▼▼ ---
$profileBio = $profile['bio'] ?? null;
$hasBio = !empty($profileBio);

// (Usamos los mismos mapas definidos en your-profile.php para mostrar el texto amigable)
$employmentMap = [
    'none' => 'Sin empleo',
    'student' => 'Estudiante',
    'tech' => 'Tecnología / Desarrollo de Software',
    'health' => 'Salud / Medicina',
    'education' => 'Educación / Docencia',
    'industry' => 'Industria / Manufactura',
    'commerce' => 'Comercio / Ventas',
    'admin' => 'Administración / Oficina',
    'other' => 'Otro'
];
$employmentIconMap = [
    'none' => 'work_off',
    'student' => 'school',
    'tech' => 'computer',
    'health' => 'medical_services',
    'education' => 'history_edu',
    'industry' => 'factory',
    'commerce' => 'storefront',
    'admin' => 'business_center',
    'other' => 'work'
];
$educationMap = [
    'none' => 'Sin formación',
    'icn_valle_hermoso' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN) – Campus Valle Hermoso',
    'uda_zaragoza_vh' => 'Universidad del Atlántico – Campus Valle Hermoso (Zaragoza)',
    'uda_juarez_vh' => 'Universidad del Atlántico – Campus Valle Hermoso (Juárez)',
    'unm_valle_hermoso' => 'Universidad del Noreste de México – Unidad Valle Hermoso',
    'uat_valle_hermoso' => 'Universidad Autónoma de Tamaulipas (UAT) – Unidad Académica Multidisciplinaria Valle Hermoso',
    'icn_matamoros' => 'Universidad de Ingenierías y Ciencias del Noreste (ICN)',
    'uih_matamoros' => 'Universidad de Integración Humanista',
    'fmisc_matamoros' => 'Facultad de Medicina e Ingeniería en Sistemas Computacionales Matamoros',
    'cin_matamoros' => 'Centro Universitario del Noreste (CIN)',
    'iom_matamoros' => 'Instituto Odontológico de Matamoros (IOM)',
    'uamm_matamoros' => 'Unidad Académica Multidisciplinaria Matamoros (UAMM)',
    'uane_americana_matamoros' => 'Universidad Americana del Noreste, Campus Matamoros',
    'uane_americanista_matamoros' => 'Universidad Americanista del Noreste (UANE), Campus Matamoros',
    'ut_matamoros' => 'Universidad Tamaulipeca, Campus Matamoros',
    'itm_matamoros' => 'Instituto Tecnológico de Matamoros',
    'upn_matamoros' => 'Universidad Pedagógica Nacional (UPN)',
    'uda_cardenas_matamoros' => 'Universidad del Atlántico, Campus Pedro Cárdenas',
    'uda_villar_matamoros' => 'Universidad del Atlántico, Campus Lauro Villar',
    'uda_logrono_matamoros' => 'Universidad del Atlántico, Campus Logroño',
    'unm_matamoros' => 'Universidad del Noreste de México, Unidad Matamoros',
    'normal_mainero_matamoros' => 'Escuela Normal Lic. J. Guadalupe Mainero',
    'lpca_matamoros' => 'Liceo Profesional de Comercio y Administración',
    'utm_matamoros' => 'Universidad Tecnológica de Matamoros (UTM)',
    'other' => 'Otra'
];

// Obtener los valores guardados
$employmentKey = $profile['employment'] ?? 'none';
$educationKey = $profile['education'] ?? 'none';

// Buscar el texto legible. Si no se encuentra, usar el default 'none'.
$employmentText = $employmentMap[$employmentKey] ?? $employmentMap['none'];
$educationText = $educationMap[$educationKey] ?? $educationMap['none'];

// Variables para la lógica de "mostrar o placeholder"
$hasEmployment = ($employmentKey !== 'none');
$hasEducation = ($educationKey !== 'none');

// Texto para mostrar (el valor real o el placeholder)
$employmentDisplay = $hasEmployment ? $employmentText : 'Sin empleo establecido';
$educationDisplay = $hasEducation ? $educationText : 'Sin formación establecida';
// --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
?>

<?php // --- [HASTAGS] <style> ELIMINADO --- ?>

<div class="profile-main-content active" data-profile-tab-content="posts">
                
    <div class="profile-left-column">
    
        <div class="component-card component-card--column" id="profile-bio-card">
            <?php outputCsrfInput(); // CSRF para el formulario de bio ?>
            
            <div class="profile-bio-header">
                <h2 class="component-card__title">Detalles</h2>
                <?php if ($isOwnProfile): // Solo mostrar botón de editar/agregar si es mi perfil ?>
                    <button type="button" 
                            class="profile-bio-add-btn" 
                            id="profile-bio-edit-trigger"
                            style="<?php echo $hasBio ? '' : 'display: none;'; // Ocultar si no hay bio (se muestra el de abajo) ?>">
                        Editar
                    </button>
                <?php endif; ?>
            </div>

            <div id="profile-bio-view-state">
                <?php if ($hasBio): ?>
                    <div class="profile-bio-content">
                        <?php echo htmlspecialchars($profileBio); ?>
                    </div>
                <?php elseif ($isOwnProfile): ?>
                    <div class="profile-bio-placeholder" style="cursor: pointer;" id="profile-bio-add-trigger">
                        <button type="button" class="profile-bio-add-btn">
                            Agregar presentación
                        </button>
                    </div>
                <?php else: ?>
                    <div class="profile-bio-placeholder">
                        <span class="material-symbols-rounded">person</span>
                        <span>Sin presentación.</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($isOwnProfile): ?>
            <form id="profile-bio-edit-form">
                <textarea id="profile-bio-textarea" 
                          placeholder="Describe tu perfil" 
                          maxlength="500"><?php echo htmlspecialchars($profileBio); ?></textarea>
                <div id="profile-bio-edit-actions">
                    <button type="button" class="component-button" id="profile-bio-cancel-btn">
                        Cancelar
                    </button>
                    <button type="button" class="component-button component-button--primary" id="profile-bio-save-btn">
                        Guardar
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <?php // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO (Placeholder Académico) ▼▼▼ --- ?>
            <div style="border-top: 1px solid #00000015; margin: 12px 8px 0 8px;"></div>
            
            <div class="profile-bio-placeholder" style="padding: 12px 8px 0 8px; margin-top: 0;">
                <span class="material-symbols-rounded"><?php echo $hasEmployment ? 'work' : 'work_off'; ?></span>
                <span><?php echo htmlspecialchars($employmentDisplay); ?></span>
            </div>
            
            <div class="profile-bio-placeholder" style="padding: 12px 8px 0 8px; margin-top: 0;">
                
<span class="material-symbols-rounded"><?php echo $hasEducation ? 'school' : 'work_off'; ?></span>
                <span><?php echo htmlspecialchars($educationDisplay); ?></span>
            </div>
            <?php // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ --- ?>
        </div>
        <div class="component-card component-card--column" id="profile-friends-preview-card">
            
            <?php // --- (Bloque de Amigos sin cambios) --- ?>
            <div class="profile-friends-header">
                <h2 class="component-card__title" data-i18n="friends.list.title">Amigos</h2>
                <?php
                if ($isOwnProfile || (int)($profile['is_friend_list_private'] ?? 1) === 0): 
                ?>
                    <span class="profile-friends-count"><?php echo $friendCount; ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (empty($profileFriends)): ?>
                
                <?php
                if (!$isOwnProfile && (int)($profile['is_friend_list_private'] ?? 1) === 1):
                ?>
                    <p class="profile-friends-empty" data-i18n="profile.friends.private">Esta lista de amigos es privada.</p>
                <?php else: ?>
                    <p class="profile-friends-empty" data-i18n="friends.list.noFriends">No tiene amigos.</p>
                <?php endif; ?>

            <?php else: ?>
                <div class="profile-friends-grid">
                    <?php foreach ($profileFriends as $friend): ?>
                        <?php
                        $friendAvatar = $friend['profile_image_url'] ?? $defaultAvatar;
                        if(empty($friendAvatar)) $friendAvatar = "https://ui-avatars.com/api/?name=" . urlencode($friend['username']) . "&size=100&background=e0e0e0&color=ffffff";
                        ?>
                        <a href="<?php echo $basePath . '/profile/' . htmlspecialchars($friend['username']); ?>" 
                           data-nav-js="true" 
                           class="friend-preview-item"
                           title="<?php echo htmlspecialchars($friend['username']); ?>">
                           
                            <div class="comment-avatar" data-role="<?php echo htmlspecialchars($friend['role']); ?>" style="width: 100%; height: auto; padding-top: 100%; position: relative; border-radius: 8px;">
                                <img src="<?php echo htmlspecialchars($friendAvatar); ?>" 
                                     alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                     style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                            </div>
                            <span class="friend-preview-name"><?php echo htmlspecialchars($friend['username']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php // --- (Fin Bloque de Amigos) --- ?>
        </div>
    </div>

    <div class="profile-right-column">
        
        <?php // --- (Formulario de Post en Perfil sin cambios) --- ?>
        <?php if ($isOwnProfile && $currentTab === 'posts'): ?>
            <form class="component-card post-comment-input-container" data-action="profile-post-submit" style="padding: 16px;">
                <?php outputCsrfInput(); ?>
                <input type="hidden" name="action" value="create-post">
                <input type="hidden" name="community_id" value=""> <input type="hidden" name="privacy_level" value="public"> <div class="post-comment-avatar" data-role="<?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?>">
                    <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="Tu avatar">
                </div>
                <input type="text" 
                       class="post-comment-input" 
                       name="text_content" 
                       placeholder="¿En qué estás pensando, <?php echo htmlspecialchars($_SESSION['username']); ?>?" 
                       required 
                       autocomplete="off"
                       maxlength="1000"> <?php // Límite de la BD ?>
                <button type="submit" class="post-comment-submit-btn" disabled>
                    <span class="material-symbols-rounded">send</span>
                </button>
            </form>
        <?php endif; ?>
        <?php // --- (Fin Formulario de Post) --- ?>


        <div class="card-list-container" id="profile-posts-list"> <?php // <-- ID AÑADIDO ?>
            
            <?php if (empty($publications)): ?>
                <div class="component-card component-card--column">
                    <div class="component-card__content">
                        <div class="component-card__icon">
                            <span class="material-symbols-rounded">
                                <?php
                                switch ($currentTab) {
                                    case 'likes': echo 'favorite'; break;
                                    case 'bookmarks': echo 'bookmark'; break;
                                    default: echo 'feed'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <div class="component-card__text">
                            <?php if ($currentTab === 'likes'): ?>
                                <h2 class="component-card__title" data-i18n="profile.noLikes.title"></h2>
                                <p class="component-card__description" data-i18n="profile.noLikes.desc"></p>
                            <?php elseif ($currentTab === 'bookmarks'): ?>
                                <h2 class="component-card__title" data-i18n="profile.noBookmarks.title"></h2>
                                <p class="component-card__description" data-i18n="profile.noBookmarks.desc"></p>
                            <?php else: ?>
                                <h2 class="component-card__title" data-i18n="profile.noPosts.title"></h2>
                                <?php if ($isOwnProfile): ?>
                                    <p class="component-card__description" data-i18n="profile.noPosts.descSelf"></p>
                                <?php else: ?>
                                    <p class="component-card__description" data-i18n="profile.noPosts.descOther"><?php echo htmlspecialchars($profile['username']); ?> aún no ha publicado nada.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($publications as $post): ?>
                    <?php
                    // --- (Lógica de renderizado de post sin cambios) ---
                    $postAvatar = $post['profile_image_url'] ?? $defaultAvatar;
                    if (empty($postAvatar)) $postAvatar = $defaultAvatar;
                    $postRole = $post['role'] ?? 'user';
                    $attachments = [];
                    if (!empty($post['attachments'])) {
                        $attachments = explode(',', $post['attachments']);
                    }
                    $attachmentCount = count($attachments);
                    $isPoll = $post['post_type'] === 'poll';
                    $hasVoted = $post['user_voted_option_id'] !== null;
                    $totalVotes = (int)($post['total_votes'] ?? 0);
                    $pollOptions = $post['poll_options'] ?? [];
                    $likeCount = (int)($post['like_count'] ?? 0);
                    $userHasLiked = (int)($post['user_has_liked'] ?? 0) > 0;
                    $commentCount = (int)($post['comment_count'] ?? 0);
                    $userHasBookmarked = (int)($post['user_has_bookmarked'] ?? 0) > 0;
                    $privacyLevel = $post['privacy_level'] ?? 'public';
                    $privacyIcon = 'public';
                    $privacyTooltipKey = 'post.privacy.public';
                    if ($privacyLevel === 'friends') {
                        $privacyIcon = 'group';
                        $privacyTooltipKey = 'post.privacy.friends';
                    } elseif ($privacyLevel === 'private') {
                        $privacyIcon = 'lock';
                        $privacyTooltipKey = 'post.privacy.private';
                    }
                    // $isOwner se hereda de la variable $profile
                    $isOwner = ($post['user_id'] == $userId);
                    // --- [HASTAGS] Lógica de $hashtags ELIMINADA ---
                    ?>
                    
                    <div class="component-card component-card--post component-card--column" 
                         data-post-id="<?php echo $post['id']; ?>"
                         data-privacy="<?php echo htmlspecialchars($privacyLevel); ?>">
                    <div class="post-card-header">
                            <div class="component-card__content">
                                <div class="component-card__avatar" data-role="<?php echo htmlspecialchars($postRole); ?>">
                                    <img src="<?php echo htmlspecialchars($postAvatar); ?>" alt="<?php echo htmlspecialchars($post['username']); ?>" class="component-card__avatar-image">
                                </div>
                                
                                <?php // --- ▼▼▼ INICIO DE BLOQUE MODIFICADO ▼▼▼ --- ?>
                                <div class="component-card__text">
                                    <h2 class="component-card__title"><?php echo htmlspecialchars($post['username']); ?></h2>
                                    
                                    <div class="profile-meta" style="padding: 0; margin-top: 4px; gap: 8px;">
                                        <div class="profile-meta-badge">
                                            <span><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></span>
                                        </div>
                                        
                                        <div class="profile-meta-badge" data-tooltip="<?php echo $privacyTooltipKey; ?>">
                                            <span data-i18n="<?php echo $privacyTooltipKey; ?>"></span>
                                        </div>
                                        
                                        <?php if (isset($post['community_name']) && $post['community_name']): ?>
                                            <div class="profile-meta-badge">
                                                <span class="material-symbols-rounded">group</span>
                                                <span style="font-weight: 600;"><?php echo htmlspecialchars($post['community_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                </div>
                                <?php // --- ▲▲▲ FIN DE BLOQUE MODIFICADO ▲▲▲ --- ?>

                            </div>
                            
                            <?php if ($isOwner): ?>
                            <div class="post-card-options">
                                <button type="button" 
                                        class="component-action-button--icon" 
                                        data-action="toggle-post-options"
                                        data-post-id="<?php echo $post['id']; ?>"
                                        data-tooltip="Más opciones">
                                    <span class="material-symbols-rounded">more_vert</span>
                                </button>
                                
                                <div class="popover-module body-title disabled" data-module="modulePostOptions">
                                    <div class="menu-content">
                                        <div class="menu-list">
                                            <div class="menu-link" data-action="toggle-post-privacy">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded">visibility</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span data-i18n="post.options.changePrivacy"></span>
                                                </div>
                                            </div>
                                            <div class="menu-link" data-action="post-delete">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded" style="color: #c62828;">delete</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span data-i18n="post.options.delete" style="color: #c62828;"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="popover-module body-title disabled" data-module="modulePostPrivacy">
                                    <div class="menu-content">
                                        <div class="menu-list">
                                            <div class="menu-header" data-i18n="post.options.privacyTitle"></div>
                                            
                                            <div class="menu-link" data-action="post-set-privacy" data-value="public">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded">public</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span data-i18n="post.options.privacyPublic"></span>
                                                </div>
                                                <div class="menu-link-check-icon"></div>
                                            </div>
                                            
                                            <div class="menu-link" data-action="post-set-privacy" data-value="friends">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded">group</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span data-i18n="post.options.privacyFriends"></span>
                                                </div>
                                                <div class="menu-link-check-icon"></div>
                                            </div>
                                            
                                            <div class="menu-link" data-action="post-set-privacy" data-value="private">
                                                <div class="menu-link-icon">
                                                    <span class="material-symbols-rounded">lock</span>
                                                </div>
                                                <div class="menu-link-text">
                                                    <span data-i18n="post.options.privacyPrivate"></span>
                                                </div>
                                                <div class="menu-link-check-icon"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                </div>
                            <?php endif; ?>
                            </div>

                        <?php if (!empty($post['title']) && !$isPoll): ?>
                            <div class="post-card-content" style="padding-bottom: 0;">
                                <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($post['text_content'])): ?>
                            <div class="post-card-content" <?php if (!empty($post['title'])) echo 'style="padding-top: 8px;"'; ?>>
                                <?php if ($isPoll): ?>
                                    <h3 class="poll-question"><?php echo htmlspecialchars($post['text_content']); ?></h3>
                                <?php else: ?>
                                    <div>
                                        <?php 
                                        echo truncatePostText($post['text_content'], $post['id'], $basePath, 500); 
                                        ?>
                                    </div>
                                <?php endif; ?>
                                </div>
                        <?php endif; ?>
                        
                        <?php // --- [HASTAGS] Bloque de renderizado ELIMINADO --- ?>

                        <?php if ($isPoll && !empty($pollOptions)): ?>
                            <div class="poll-container" id="poll-<?php echo $post['id']; ?>" data-poll-id="<?php echo $post['id']; ?>">
                                <?php if ($hasVoted): ?>
                                    <div class="poll-results">
                                        <?php foreach ($pollOptions as $option): 
                                            $voteCount = (int)$option['vote_count'];
                                            $percentage = ($totalVotes > 0) ? round(($voteCount / $totalVotes) * 100) : 0;
                                            $isUserVote = ($option['id'] == $post['user_voted_option_id']);
                                        ?>
                                            <div class="poll-option-result <?php echo $isUserVote ? 'voted-by-user' : ''; ?>">
                                                <div class="poll-option-bar" style="width: <?php echo $percentage; ?>%;"></div>
                                                <div class="poll-option-text">
                                                    <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                    <?php if ($isUserVote): ?>
                                                        <span class="material-symbols-rounded poll-user-vote-icon">check_circle</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="poll-option-percent"><?php echo $percentage; ?>%</div>
                                            </div>
                                        <?php endforeach; ?>
                                        <p class="poll-total-votes" data-i18n="home.poll.totalVotes" data-count="<?php echo $totalVotes; ?>"><?php echo $totalVotes; ?> votos</p>
                                    </div>
                                <?php else: ?>
                                    <form class="poll-form" data-action="submit-poll-vote">
                                        <input type="hidden" name="publication_id" value="<?php echo $post['id']; ?>">
                                        <?php foreach ($pollOptions as $option): ?>
                                            <label class="poll-option-vote">
                                                <input type="radio" name="poll_option_id" value="<?php echo $option['id']; ?>" required>
                                                <span class="poll-option-radio"></span>
                                                <span class="poll-option-text"><?php echo htmlspecialchars($option['option_text']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                        <div class="poll-form-actions">
                                            <button type="submit" class="component-action-button component-action-button--primary" data-i18n="home.poll.voteButton">Votar</button>
                                            <p class="poll-total-votes" data-i18n="home.poll.totalVotes" data-count="<?php echo $totalVotes; ?>"><?php echo $totalVotes; ?> votos</p>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$isPoll && $attachmentCount > 0): ?>
                        <div class="post-attachments-container" data-count="<?php echo $attachmentCount; ?>">
                            <?php foreach ($attachments as $imgUrl): ?>
                                <div class="post-attachment-item">
                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Adjunto de publicación" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-actions-container">
                            <div class="post-actions-left">
                                <button type="button" 
                                        class="component-action-button--icon post-action-like <?php echo $userHasLiked ? 'active' : ''; ?>" 
                                        data-tooltip="home.actions.like"
                                        data-action="like-toggle"
                                        data-post-id="<?php echo $post['id']; ?>">
                            <span class="material-symbols-rounded"><?php echo $userHasLiked ? 'favorite' : 'favorite_border'; ?></span>
                            <span class="action-text"><?php echo $likeCount; ?></span>
                                </button>
                                
                                <button type="button"
                                   class="component-action-button--icon post-action-comment" 
                                   data-tooltip="home.actions.comment"
                                   data-action="toggleSectionPostView"
                                   data-post-id="<?php echo $post['id']; ?>">
                                    <span class="material-symbols-rounded">chat_bubble_outline</span>
                                    <span class="action-text"><?php echo $commentCount; ?></span>
                                </button>
                                <button type="button" class="component-action-button--icon" data-tooltip="home.actions.share">
                                    <span class="material-symbols-rounded">send</span>
                                </button>
                            </div>
                            <div class="post-actions-right">
                                <button type="button" 
                                        class="component-action-button--icon post-action-bookmark <?php echo $userHasBookmarked ? 'active' : ''; ?>" 
                                        data-tooltip="home.actions.save"
                                        data-action="bookmark-toggle"
                                        data-post-id="<?php echo $post['id']; ?>">
                                    <span class="material-symbols-rounded"><?php echo $userHasBookmarked ? 'bookmark' : 'bookmark_border'; ?></span>
                                </button>
                                </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</div>