<?php
// FILE: config/routing/router.php
// (MODIFICADO - Añadida lógica para pre-cargar chat y corrección de error de log)

include '../config.php';

include_once __DIR__ . '/../../includes/data_fetchers/profile_data_fetcher.php';
include_once __DIR__ . '/../../includes/data_fetchers/home_feed_fetcher.php';
include_once __DIR__ . '/../../includes/data_fetchers/admin_users_fetcher.php';

$isPartialLoad = isset($_GET['partial']) && $_GET['partial'] === 'true';

function showRegistrationError($basePath, $messageKey, $detailsKey)
{
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(400);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';

    echo '<title data-i18n="page.error.title">Error en el registro</title></head>';

    echo '<body style="background-color: #f5f5fa;">';

    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';

    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';

    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';

    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/register" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToRegister">Volver al inicio del registro</a>';

    echo '</div></div>';
    echo '</body></html>';
}


function showResetError($basePath, $messageKey, $detailsKey)
{
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code(400);

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=deVice-width, initial-scale=1.0">';
    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded">';
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/assets/css/styles.css">';
    echo '<title data-i18n="page.error.titleReset">Error en la recuperación</title></head>';
    echo '<body style="background-color: #f5f5fa;">';
    echo '<div class="section-content active" style="align-items: center; justify-content: center; height: 100vh;">';
    echo '<div class="auth-container" style="max-width: 460px;">';
    echo '<h1 class="auth-title" style="font-size: 36px; margin-bottom: 16px;" data-i18n="page.error.oopsTitle">¡Uy! Faltan datos.</h1>';
    echo '<div class="auth-error-message" style="display: block; background-color: #ffffff; border: 1px solid #00000020; color: #1f2937; margin-bottom: 24px; text-align: left; padding: 16px;">';
    echo '<strong style="display: block; font-size: 16px; margin-bottom: 8px; color: #000;" data-i18n="' . htmlspecialchars($messageKey, ENT_QUOTES, 'UTF-8') . '"></strong>';
    echo '<p style="font-size: 14px; margin: 0; color: #6b7280; line-height: 1.5;" data-i18n="' . htmlspecialchars($detailsKey, ENT_QUOTES, 'UTF-8') . '"></p>';
    echo '</div>';
    echo '<a href="' . htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') . '/reset-password" class="auth-button" style="text-decoration: none; text-align: center; line-height: 52px; display: block; width: 100%;" data-i18n="page.error.backToReset">Volver al inicio de la recuperación</a>';
    echo '</div></div>';
    echo '</body></html>';
}

function formatBackupSize($bytes)
{
    if ($bytes < 1024) return $bytes . ' B';
    $kb = $bytes / 1024;
    if ($kb < 1024) return round($kb, 2) . ' KB';
    $mb = $kb / 1024;
    if ($mb < 1024) return round($mb, 2) . ' MB';
    $gb = $mb / 1024;
    return round($gb, 2) . ' GB';
}
function formatBackupDate($timestamp)
{
    return date('d/m/Y H:i:s', $timestamp);
}

$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page;

$allowedPages = [
    'home' => '../../includes/sections/main/home.php',
    'explorer' => '../../includes/sections/main/explorer.php',
    'login' => '../../includes/sections/auth/login.php',
    '404' => '../../includes/sections/main/404.php',
    'db-error' => '../../includes/sections/main/db-error.php',

    'maintenance' => '../../includes/sections/main/status-page.php',
    'server-full' => '../../includes/sections/main/status-page.php',
    'account-status-deleted' => '../../includes/sections/main/status-page.php',
    'account-status-suspended' => '../../includes/sections/main/status-page.php',

    // --- ▼▼▼ RUTA ELIMINADA ▼▼▼ ---
    // 'join-group' => '../../includes/sections/main/join-group.php',
    // --- ▲▲▲ FIN RUTA ELIMINADA ▲▲▲ ---
    
    'create-publication' => '../../includes/sections/main/create-publication.php',
    'create-poll' => '../../includes/sections/main/create-publication.php',

    'post-view' => '../../includes/sections/main/view-post.php',
    'view-profile' => '../../includes/sections/main/view-profile.php',

    'search-results' => '../../includes/sections/main/search-results.php',

    'trends' => '../../includes/sections/main/trends.php',

    // --- ▼▼▼ INICIO DE MODIFICACIÓN (RUTAS DE MENSAJES) ▼▼▼ ---
    'messages' => '../../includes/sections/main/messages.php',
    'messages/username-placeholder' => '../../includes/sections/main/messages.php',
    // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---
    
    'register-step1' => '../../includes/sections/auth/register.php',
    'register-step2' => '../../includes/sections/auth/register.php',
    'register-step3' => '../../includes/sections/auth/register.php',

    'reset-step1' => '../../includes/sections/auth/reset-password.php',
    'reset-step2' => '../../includes/sections/auth/reset-password.php',
    'reset-step3' => '../../includes/sections/auth/reset-password.php',

    'settings-profile' => '../../includes/sections/settings/your-profile.php',
    'settings-login' => '../../includes/sections/settings/login-security.php',
    'settings-accessibility' => '../../includes/sections/settings/accessibility.php',
    'settings-devices' => '../../includes/sections/settings/device-sessions.php',

    'settings-change-password' => '../../includes/sections/settings/actions/change-password.php',
    'settings-change-email' => '../../includes/sections/settings/actions/change-email.php',
    'settings-toggle-2fa' => '../../includes/sections/settings/actions/toggle-2fa.php',
    'settings-delete-account' => '../../includes/sections/settings/actions/delete-account.php',

    'admin-dashboard' => '../../includes/sections/admin/dashboard.php',
    'admin-manage-users' => '../../includes/sections/admin/manage-users.php',
    'admin-create-user' => '../../includes/sections/admin/create-user.php',
    'admin-edit-user' => '../../includes/sections/admin/admin-edit-user.php',
    'admin-server-settings' => '../../includes/sections/admin/server-settings.php',
    'admin-manage-backups' => '../../includes/sections/admin/manage-backups.php',
    'admin-manage-logs' => '../../includes/sections/admin/manage-logs.php',

    'admin-manage-communities' => '../../includes/sections/admin/manage-communities.php',
    'admin-edit-community' => '../../includes/sections/admin/edit-community.php',
];



if (array_key_exists($page, $allowedPages)) {

    $CURRENT_REGISTER_STEP = 1;
    $initialCooldown = 0;

    if ($page === 'register-step1') {
        $CURRENT_REGISTER_STEP = 1;
        unset($_SESSION['registration_step']);
        unset($_SESSION['registration_email']);
        echo '<script>sessionStorage.removeItem("regEmail"); sessionStorage.removeItem("regPass");</script>';
    } elseif ($page === 'register-step2') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2) {
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep1');
            exit;
        }
        $CURRENT_REGISTER_STEP = 2;
    } elseif ($page === 'register-step3') {
        if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 3) {
            showRegistrationError($basePath, 'page.error.400title', 'page.error.regStep2');
            exit;
        }
        $CURRENT_REGISTER_STEP = 3;

        if (isset($_SESSION['registration_email'])) {
            try {
                $email = $_SESSION['registration_email'];
                $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = 'registration' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = (int) ($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - register-step3-cooldown');
                $initialCooldown = 0;
            }
        }
    }

    $CURRENT_RESET_STEP = 1;

    if ($page === 'reset-step1') {
        $CURRENT_RESET_STEP = 1;
        unset($_SESSION['reset_step']);
        unset($_SESSION['reset_email']);
        echo '<script>sessionStorage.removeItem("resetEmail"); sessionStorage.removeItem("resetCode");</script>';
    } elseif ($page === 'reset-step2') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 2) {
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep1');
            exit;
        }
        $CURRENT_RESET_STEP = 2;

        if (isset($_SESSION['reset_email'])) {
            try {
                $email = $_SESSION['reset_email'];
                $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = 'password_reset' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $codeData = $stmt->fetch();

                if ($codeData) {
                    $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                    $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
                    $cooldownConstant = (int) ($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);

                    if ($secondsPassed < $cooldownConstant) {
                        $initialCooldown = $cooldownConstant - $secondsPassed;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - reset-step2-cooldown');
                $initialCooldown = 0;
            }
        }
    } elseif ($page === 'reset-step3') {
        if (!isset($_SESSION['reset_step']) || $_SESSION['reset_step'] < 3) {
            showResetError($basePath, 'page.error.400title', 'page.error.resetStep2');
            exit;
        }
        $CURRENT_RESET_STEP = 3;
    }


    if ($page === 'settings-profile') {
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
        $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || strpos($profileImageUrl, 'user-' . $_SESSION['user_id'] . '.png') !== false;
        $usernameForAlt = $_SESSION['username'] ?? 'Usuario';
        $userRole = $_SESSION['role'] ?? 'user';
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        $userLanguage = $_SESSION['language'] ?? 'en-us';
        $userUsageType = $_SESSION['usage_type'] ?? 'personal';
        $openLinksInNewTab = (int) ($_SESSION['open_links_in_new_tab'] ?? 1);
        $isFriendListPrivate = (int) ($_SESSION['is_friend_list_private'] ?? 1);
        $isEmailPublic = (int) ($_SESSION['is_email_public'] ?? 0);

        $userEmployment = $_SESSION['employment'] ?? 'none';
        $userEducation = $_SESSION['education'] ?? 'none';
    } elseif ($page === 'settings-login') {
        try {
            $stmt_user = $pdo->prepare("SELECT is_2fa_enabled, created_at FROM users WHERE id = ?");
            $stmt_user->execute([$_SESSION['user_id']]);
            $userData = $stmt_user->fetch();
            $is2faEnabled = $userData ? (int) $userData['is_2fa_enabled'] : 0;
            $accountCreatedDate = $userData ? $userData['created_at'] : null;

            $stmt_pass_log = $pdo->prepare("SELECT changed_at FROM user_audit_logs WHERE user_id = ? AND change_type = 'password' ORDER BY changed_at DESC LIMIT 1");
            $stmt_pass_log->execute([$_SESSION['user_id']]);
            $lastLog = $stmt_pass_log->fetch();

            if ($lastLog) {
                $lastPasswordUpdateText = 'Última actualización: ' . (new DateTime($lastLog['changed_at']))->format('d/m/Y H:i');
            } else {
                $lastPasswordUpdateText = 'settings.login.lastPassUpdateNever';
            }

            $accountCreationDateText = '';
            if ($accountCreatedDate) {
                $accountCreationDateText = 'Cuenta creada el ' . (new DateTime($accountCreatedDate))->format('d/m/Y');
            }
            $deleteAccountDescText = 'settings.login.deleteAccountDesc';
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-login');
            $is2faEnabled = 0;
            $lastPasswordUpdateText = 'settings.login.lastPassUpdateError';
            $deleteAccountDescText = 'settings.login.deleteAccountDesc';
            $accountCreationDateText = '';
        }
    } elseif ($page === 'settings-accessibility') {
        $userTheme = $_SESSION['theme'] ?? 'system';
        $increaseMessageDuration = (int) ($_SESSION['increase_message_duration'] ?? 0);
    } elseif ($page === 'settings-change-email') {
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        $initialEmailCooldown = 0;
        $cooldownConstant = (int) ($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);
        $identifier = $_SESSION['user_id'];
        $codeType = 'email_change';

        try {
            $stmt = $pdo->prepare("SELECT created_at FROM verification_codes WHERE identifier = ? AND code_type = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$identifier, $codeType]);
            $codeData = $stmt->fetch();

            $secondsPassed = -1;

            if ($codeData) {
                $lastCodeTime = new DateTime($codeData['created_at'], new DateTimeZone('UTC'));
                $currentTime = new DateTime('now', new DateTimeZone('UTC'));
                $secondsPassed = $currentTime->getTimestamp() - $lastCodeTime->getTimestamp();
            }

            if (!$codeData || $secondsPassed === -1 || $secondsPassed >= $cooldownConstant) {
                $stmt_delete = $pdo->prepare("DELETE FROM verification_codes WHERE identifier = ? AND code_type = ?");
                $stmt_delete->execute([$identifier, $codeType]);

                $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $code = '';
                $max = strlen($chars) - 1;
                for ($i = 0; $i < 12; $i++) {
                    $code .= $chars[random_int(0, $max)];
                }
                $verificationCode = substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
                $verificationCode = str_replace('-', '', $verificationCode);

                $stmt_insert = $pdo->prepare(
                    "INSERT INTO verification_codes (identifier, code_type, code) 
                         VALUES (?, ?, ?)"
                );
                $stmt_insert->execute([$identifier, $codeType, $verificationCode]);

                $initialEmailCooldown = $cooldownConstant;
            } else {
                $initialEmailCooldown = $cooldownConstant - $secondsPassed;
            }
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-change-email-cooldown');
            $initialEmailCooldown = 0;
        }
    } elseif ($page === 'settings-toggle-2fa') {
        try {
            $stmt_2fa = $pdo->prepare("SELECT is_2fa_enabled FROM users WHERE id = ?");
            $stmt_2fa->execute([$_SESSION['user_id']]);
            $is2faEnabled = (int) $stmt_2fa->fetchColumn();
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - settings-toggle-2fa');
            $is2faEnabled = 0;
        }
    } elseif ($page === 'settings-delete-account') {
        $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
        $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
        $profileImageUrl = $_SESSION['profile_image_url'] ?? $defaultAvatar;
        if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
    } elseif ($page === 'join-group') {
        // Esta página ya no se usa, pero se mantiene la lógica por si acaso.
        try {
            $stmt_public = $pdo->prepare("SELECT id, name FROM communities WHERE privacy = 'public' ORDER BY name ASC");
            $stmt_public->execute();
            $publicCommunities = $stmt_public->fetchAll();

            $stmt_joined = $pdo->prepare("SELECT community_id FROM user_communities WHERE user_id = ?");
            $stmt_joined->execute([$_SESSION['user_id']]);
            $joinedCommunityIds = $stmt_joined->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - join-group');
            $publicCommunities = [];
            $joinedCommunityIds = [];
        }
    } elseif ($page === 'create-publication' || $page === 'create-poll') {
        $userCommunitiesForPost = [];
        try {
            $stmt_c = $pdo->prepare("SELECT c.id, c.name FROM communities c JOIN user_communities uc ON c.id = uc.community_id WHERE uc.user_id = ? ORDER BY c.name ASC");
            $stmt_c->execute([$_SESSION['user_id']]);
            $userCommunitiesForPost = $stmt_c->fetchAll();
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - create-post-communities');
        }
    } elseif ($page === 'home') {

        $communityUuid = $_GET['community_uuid'] ?? null;
        $currentUserId = $_SESSION['user_id'];

        $homeFeedData = getHomeFeedData($pdo, $currentUserId, $communityUuid);

        extract($homeFeedData);
    } elseif ($page === 'post-view') {
        $viewPostData = null;
        $postId = (int) ($_GET['post_id'] ?? 0);
        $userId = $_SESSION['user_id'];

        if ($postId === 0) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } else {
            try {

                $sql_post =
                    "SELECT 
                           p.*, 
                           u.username, 
                           u.profile_image_url, 
                           u.role,
                           p.title,
                           p.post_status, 
                           p.privacy_level, 
                           c.name AS community_name,
                           (SELECT GROUP_CONCAT(pf.public_url SEPARATOR ',') 
                            FROM publication_attachments pa
                            JOIN publication_files pf ON pa.file_id = pf.id
                            WHERE pa.publication_id = p.id
                            ORDER BY pa.sort_order ASC
                           ) AS attachments,
                           (SELECT COUNT(pv.id) FROM poll_votes pv WHERE pv.publication_id = p.id) AS total_votes,
                           (SELECT pv.poll_option_id FROM poll_votes pv WHERE pv.publication_id = p.id AND pv.user_id = ?) AS user_voted_option_id,
                           (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id) AS like_count,
                           (SELECT COUNT(*) FROM publication_likes pl WHERE pl.publication_id = p.id AND pl.user_id = ?) AS user_has_liked,
                           (SELECT COUNT(*) FROM publication_bookmarks pb WHERE pb.publication_id = p.id AND pb.user_id = ?) AS user_has_bookmarked,
                           (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count,
                           (SELECT GROUP_CONCAT(h.tag SEPARATOR ',') 
                            FROM publication_hashtags ph
                            JOIN hashtags h ON ph.hashtag_id = h.id
                            WHERE ph.publication_id = p.id
                           ) AS hashtags
                         FROM community_publications p
                         JOIN users u ON p.user_id = u.id
                         LEFT JOIN communities c ON p.community_id = c.id
                         WHERE p.id = ?";

                $stmt_post = $pdo->prepare($sql_post);
                $stmt_post->execute([$userId, $userId, $userId, $postId]);

                $viewPostData = $stmt_post->fetch();

                if (!$viewPostData) {
                    $page = '404';
                    $CURRENT_SECTION = '404';
                } else {
                    if ($viewPostData['post_type'] === 'poll') {
                        $stmt_options = $pdo->prepare(
                            "SELECT 
                                 po.id, 
                                 po.option_text, 
                                 COUNT(pv.id) AS vote_count
                             FROM poll_options po
                             LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                             WHERE po.publication_id = ?
                             GROUP BY po.id, po.option_text 
                             ORDER BY po.id ASC"
                        );
                        $stmt_options->execute([$postId]);
                        $options = $stmt_options->fetchAll();
                        $viewPostData['poll_options'] = $options;
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - post-view');
                $page = '404';
                $CURRENT_SECTION = '404';
            }
        }
    } elseif ($page === 'view-profile') {

        $targetUsername = $_GET['username'] ?? '';
        $currentTab = $_GET['tab'] ?? 'posts';
        $currentUserId = $_SESSION['user_id'];

        $viewProfileData = getProfileData($pdo, $targetUsername, $currentTab, $currentUserId);

        if ($viewProfileData === null) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } else {
            $currentTab = $viewProfileData['current_tab'];
        }

        if ($isPartialLoad && $page !== '404') {

            $tabBasePath = '../../includes/sections/main/profile-tabs/';
            $tabFile = '';

            switch ($currentTab) {
                case 'info':
                    $tabFile = $tabBasePath . 'view-profile-information.php';
                    break;
                case 'amigos':
                    $tabFile = $tabBasePath . 'view-profile-friends.php';
                    break;
                case 'fotos':
                    $tabFile = $tabBasePath . 'view-profile-photos.php';
                    break;
                case 'likes':
                case 'bookmarks':
                case 'posts':
                default:
                    $tabFile = $tabBasePath . 'view-profile-posts.php';
                    break;
            }

            if (file_exists($tabFile)) {
                include $tabFile;
            } else {
                echo '<div class="component-card"><p>Error: No se pudo cargar el contenido de la pestaña.</p></div>';
            }
            exit;
        }
    } elseif ($page === 'search-results') {
        $searchQuery = $_GET['q'] ?? '';
        $userResults = [];
        $postResults = [];
        $communityResults = [];

        $isHashtagSearch = (strpos($searchQuery, '#') === 0);
        $searchTag = '';

        if (!empty($searchQuery)) {
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
            $currentUserId = $_SESSION['user_id'];

            try {
                if ($isHashtagSearch) {
                    $searchTag = mb_strtolower(trim($searchQuery, '# '));
                    $searchTag = preg_replace('/[^a-z0-9áéíóúñ_-]/u', '', $searchTag);

                    if (!empty($searchTag)) {
                        $stmt_posts = $pdo->prepare(
                            "SELECT 
                               p.id, p.title, p.text_content, p.created_at,
                               u.username, u.profile_image_url, u.role
                             FROM community_publications p
                             JOIN users u ON p.user_id = u.id
                             LEFT JOIN communities c ON p.community_id = c.id
                             JOIN publication_hashtags ph ON p.id = ph.publication_id
                             JOIN hashtags h ON ph.hashtag_id = h.id
                             WHERE 
                               h.tag = :search_tag 
                             AND p.post_status = 'active'
                             AND (
                                 p.privacy_level = 'public'
                                 OR (p.privacy_level = 'friends' AND (
                                     p.user_id = :current_user_id 
                                     OR p.user_id IN (
                                         (SELECT user_id_2 FROM friendships WHERE user_id_1 = :current_user_id AND status = 'accepted')
                                         UNION
                                         (SELECT user_id_1 FROM friendships WHERE user_id_2 = :current_user_id AND status = 'accepted')
                                     )
                                 ))
                             )
                             AND (
                                p.community_id IS NULL -- Posts de perfil
                                OR c.privacy = 'public' -- O grupos públicos
                                OR p.community_id IN (SELECT community_id FROM user_communities WHERE user_id = :current_user_id) -- O grupos de los que soy miembro
                             )
                             ORDER BY p.created_at DESC
                             LIMIT 20"
                        );
                        $stmt_posts->execute([
                            ':search_tag' => $searchTag,
                            ':current_user_id' => $currentUserId
                        ]);
                        $postResults = $stmt_posts->fetchAll();
                    }
                } else {
                    $searchParam = '%' . $searchQuery . '%';

                    $stmt_users = $pdo->prepare(
                        "SELECT id, username, profile_image_url, role 
                           FROM users 
                           WHERE username LIKE ? 
                           AND account_status = 'active'
                           LIMIT 20"
                    );
                    $stmt_users->execute([$searchParam]);
                    $users = $stmt_users->fetchAll();

                    foreach ($users as $user) {
                        $avatar = $user['profile_image_url'] ?? $defaultAvatar;
                        if (empty($avatar)) {
                            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
                        }
                        $userResults[] = [
                            'username' => htmlspecialchars($user['username']),
                            'avatarUrl' => htmlspecialchars($avatar),
                            'role' => htmlspecialchars($user['role'])
                        ];
                    }

                    $stmt_posts = $pdo->prepare(
                        "SELECT 
                               p.id, 
                               p.title, 
                               p.text_content, 
                               p.created_at,
                               u.username,
                               u.profile_image_url,
                               u.role,
                               p.privacy_level,
                               c.name AS community_name
                           FROM community_publications p
                           JOIN users u ON p.user_id = u.id
                           LEFT JOIN communities c ON p.community_id = c.id
                           WHERE 
                               (p.text_content LIKE ? OR p.title LIKE ?) 
                           AND 
                               (
                                   p.community_id IS NULL -- Posts de perfil
                                   OR 
                                   c.privacy = 'public' -- O grupos públicos
                                   OR 
                                   p.community_id IN (SELECT community_id FROM user_communities WHERE user_id = ?) -- O grupos de los que soy miembro
                               )
                           AND p.post_status = 'active'
                           AND (
                               p.privacy_level = 'public'
                               OR (p.privacy_level = 'friends' AND (
                                   p.user_id = ? 
                                   OR p.user_id IN (
                                       (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted')
                                       UNION
                                       (SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted')
                                   )
                               ))
                           )
                           ORDER BY p.created_at DESC
                           LIMIT 20"
                    );
                    $stmt_posts->execute([$searchParam, $searchParam, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
                    $postResults = $stmt_posts->fetchAll();

                    $stmt_comm = $pdo->prepare(
                        "SELECT id, uuid, name, icon_url, 
                          (SELECT COUNT(*) FROM user_communities uc WHERE uc.community_id = c.id) as member_count
                           FROM communities c
                           WHERE name LIKE ? 
                           AND privacy = 'public'
                           ORDER BY member_count DESC
                           LIMIT 10"
                    );
                    $stmt_comm->execute([$searchParam]);
                    $communities = $stmt_comm->fetchAll();

                    foreach ($communities as $community) {
                        $icon = $community['icon_url'] ?? $defaultAvatar;
                        if (empty($icon)) {
                            $icon = "https://ui-avatars.com/api/?name=" . urlencode($community['name']) . "&size=100&background=e0e0e0&color=ffffff";
                        }
                        $communityResults[] = [
                            'id' => $community['id'],
                            'uuid' => $community['uuid'],
                            'name' => htmlspecialchars($community['name']),
                            'icon_url' => htmlspecialchars($icon),
                            'member_count' => (int) $community['member_count']
                        ];
                    }
                }
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - search-results');
            }
        }
    } elseif ($page === 'trends') {
        $trendingHashtags = [];
        try {
            $stmt_trends = $pdo->prepare(
                "SELECT tag, use_count 
                 FROM hashtags 
                 ORDER BY use_count DESC, tag ASC
                 LIMIT 10"
            );
            $stmt_trends->execute();
            $trendingHashtags = $stmt_trends->fetchAll();
        } catch (PDOException $e) {
            logDatabaseError($e, 'router - trends');
            $trendingHashtags = [];
        }
    // --- ▼▼▼ INICIO DE NUEVO BLOQUE (LÓGICA DE PRE-CARGA DE CHAT) ▼▼▼ ---
    } elseif ($page === 'messages') {
        
        $preloadedChatUser = null;
        $targetUsername = $_GET['username'] ?? null;
        $currentUserId = $_SESSION['user_id'];

        if ($targetUsername) {
            try {
                // 1. Buscar al usuario
                $stmt_user = $pdo->prepare("SELECT id, username, profile_image_url, role, last_seen FROM users WHERE username = ? AND account_status = 'active'");
                $stmt_user->execute([$targetUsername]);
                $targetUser = $stmt_user->fetch();

                if ($targetUser) {
                    $targetUserId = $targetUser['id'];

                    // 2. Verificar que sean amigos (¡importante para seguridad!)
                    $userId1 = min($currentUserId, $targetUserId);
                    $userId2 = max($currentUserId, $targetUserId);
                    
                    $stmt_friend = $pdo->prepare("SELECT status FROM friendships WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'accepted'");
                    $stmt_friend->execute([$userId1, $userId2]);
                    $friendship = $stmt_friend->fetch();

                    if ($friendship) {
                        // 3. Son amigos, pre-cargar los datos
                        $preloadedChatUser = $targetUser;
                    } else {
                        // No son amigos, redirigir a 404
                        $page = '404';
                        $CURRENT_SECTION = '404';
                    }
                } else {
                    // Usuario no encontrado, redirigir a 404
                    $page = '404';
                    $CURRENT_SECTION = '404';
                }

            } catch (PDOException $e) {
                logDatabaseError($e, 'router - messages - preload');
                $preloadedChatUser = null; // Fallback a la vista de lista normal
            }
        }
        
    // --- ▲▲▲ FIN DE NUEVO BLOQUE ▲▲▲ ---
    } elseif ($page === 'admin-manage-users') {

        $adminUsersData = getAdminUsersData($pdo, $_GET);

        extract($adminUsersData);
    } elseif ($page === 'admin-edit-user') {
        $targetUserId = (int) ($_GET['id'] ?? 0);
        if ($targetUserId === 0) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } else {
            try {
                $stmt_user = $pdo->prepare("SELECT id, username, email, password, profile_image_url, role FROM users WHERE id = ?");
                $stmt_user->execute([$targetUserId]);
                $editUser = $stmt_user->fetch();

                if (!$editUser) {
                    $page = '404';
                    $CURRENT_SECTION = '404';
                }

                $adminRole = $_SESSION['role'] ?? 'user';
                if ($editUser['role'] === 'founder' && $adminRole !== 'founder') {
                    $page = '404';
                    $CURRENT_SECTION = '404';
                }

                $editUser['password_hash'] = $editUser['password'];
                unset($editUser['password']);

                $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
                $profileImageUrl = $editUser['profile_image_url'] ?? $defaultAvatar;
                if (empty($profileImageUrl)) $profileImageUrl = $defaultAvatar;
                $isDefaultAvatar = strpos($profileImageUrl, 'ui-avatars.com') !== false || strpos($profileImageUrl, 'user-' . $editUser['id'] . '.png') !== false;
            } catch (PDOException $e) {
                logDatabaseError($e, 'router - admin-edit-user');
                $page = '404';
                $CURRENT_SECTION = '404';
            }
        }
    } elseif ($page === 'admin-server-settings') {
        $maintenanceModeStatus = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
        $allowRegistrationStatus = $GLOBALS['site_settings']['allow_new_registrations'] ?? '1';
    
    // --- ▼▼▼ INICIO DE CORRECCIÓN (Eliminar 'elseif ($isAdminPage)') ▼▼▼ ---
    // } elseif ($isAdminPage) { 
    //     // Esta línea [704] causaba el error en php_errors.log porque $isAdminPage
    //     // no se define en todos los flujos (ej. carga parcial).
    //     // Ya no es necesaria, la lógica de admin se maneja en los 'elseif' de arriba.
    }
    // --- ▲▲▲ FIN DE CORRECCIÓN ▲▲▲ ---


    include $allowedPages[$page];
} else {
    http_response_code(404);
    $CURRENT_SECTION = '404';
    include $allowedPages['404'];
}