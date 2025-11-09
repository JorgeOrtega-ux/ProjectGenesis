<?php
// FILE: config/routing/router.php
// (CÓDIGO CORREGIDO - Lógica de redirección duplicada eliminada)

include '../config.php'; 

function showRegistrationError($basePath, $messageKey, $detailsKey) {
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


function showResetError($basePath, $messageKey, $detailsKey) {
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

function formatBackupSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    $kb = $bytes / 1024;
    if ($kb < 1024) return round($kb, 2) . ' KB';
    $mb = $kb / 1024;
    if ($mb < 1024) return round($mb, 2) . ' MB';
    $gb = $mb / 1024;
    return round($gb, 2) . ' GB';
}
function formatBackupDate($timestamp) {
     return date('d/m/Y H:i:s', $timestamp);
}


$page = $_GET['page'] ?? 'home';

$CURRENT_SECTION = $page; 

$allowedPages = [
    'home'     => '../../includes/sections/main/home.php',
    'explorer' => '../../includes/sections/main/explorer.php',
    'login'    => '../../includes/sections/auth/login.php',
    '404'      => '../../includes/sections/main/404.php', 
    'db-error' => '../../includes/sections/main/db-error.php', 
    
    'maintenance' => '../../includes/sections/main/status-page.php',
    'server-full' => '../../includes/sections/main/status-page.php',
    'account-status-deleted'   => '../../includes/sections/main/status-page.php',
    'account-status-suspended' => '../../includes/sections/main/status-page.php',

    'join-group' => '../../includes/sections/main/join-group.php',
    'create-publication' => '../../includes/sections/main/create-publication.php',
    'create-poll' => '../../includes/sections/main/create-publication.php',
    
    'post-view' => '../../includes/sections/main/view-post.php',
    'view-profile' => '../../includes/sections/main/view-profile.php',
    
    'search-results' => '../../includes/sections/main/search-results.php', // <-- LÍNEA AÑADIDA

    'register-step1' => '../../includes/sections/auth/register.php',
    'register-step2' => '../../includes/sections/auth/register.php',
    'register-step3' => '../../includes/sections/auth/register.php',

    'reset-step1' => '../../includes/sections/auth/reset-password.php',
    'reset-step2' => '../../includes/sections/auth/reset-password.php',
    'reset-step3' => '../../includes/sections/auth/reset-password.php',

    'settings-profile'       => '../../includes/sections/settings/your-profile.php',
    'settings-login'         => '../../includes/sections/settings/login-security.php',
    'settings-accessibility' => '../../includes/sections/settings/accessibility.php',
    'settings-devices'       => '../../includes/sections/settings/device-sessions.php', 
    
    'settings-change-password' => '../../includes/sections/settings/actions/change-password.php',
    'settings-change-email'    => '../../includes/sections/settings/actions/change-email.php',
    'settings-toggle-2fa'      => '../../includes/sections/settings/actions/toggle-2fa.php',
    'settings-delete-account'  => '../../includes/sections/settings/actions/delete-account.php',
    
    'admin-dashboard'          => '../../includes/sections/admin/dashboard.php',
    'admin-manage-users'       => '../../includes/sections/admin/manage-users.php',
    'admin-create-user'        => '../../includes/sections/admin/create-user.php',
    'admin-edit-user'          => '../../includes/sections/admin/admin-edit-user.php',
    'admin-server-settings'    => '../../includes/sections/admin/server-settings.php',
    'admin-manage-backups'     => '../../includes/sections/admin/manage-backups.php',
    'admin-manage-logs'        => '../../includes/sections/admin/manage-logs.php',
    // --- ▼▼▼ LÍNEAS AÑADIDAS ▼▼▼ ---
    'admin-manage-communities' => '../../includes/sections/admin/manage-communities.php',
    'admin-edit-community'     => '../../includes/sections/admin/edit-community.php',
];


// ##########################################################################
// ## INICIO DE BLOQUE DE LÓGICA DE CARGA DE DATOS (ESTO SE QUEDA)
// ##########################################################################

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
                    $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60); 

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
                    $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);

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
        $openLinksInNewTab = (int)($_SESSION['open_links_in_new_tab'] ?? 1); 

    } elseif ($page === 'settings-login') {
        try {
            $stmt_user = $pdo->prepare("SELECT is_2fa_enabled, created_at FROM users WHERE id = ?");
            $stmt_user->execute([$_SESSION['user_id']]);
            $userData = $stmt_user->fetch();
            $is2faEnabled = $userData ? (int)$userData['is_2fa_enabled'] : 0;
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
        $increaseMessageDuration = (int)($_SESSION['increase_message_duration'] ?? 0);
    
    } elseif ($page === 'settings-change-email') {
         $userEmail = $_SESSION['email'] ?? 'correo@ejemplo.com';
         $initialEmailCooldown = 0;
         $cooldownConstant = (int)($GLOBALS['site_settings']['code_resend_cooldown_seconds'] ?? 60);
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
                for ($i = 0; $i < 12; $i++) { $code .= $chars[random_int(0, $max)]; }
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
            $is2faEnabled = (int)$stmt_2fa->fetchColumn(); 
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

    } elseif ($page === 'post-view') {
        $viewPostData = null; 
        $postId = (int)($_GET['post_id'] ?? 0);
        $userId = $_SESSION['user_id']; 

        if ($postId === 0) {
            $page = '404';
            $CURRENT_SECTION = '404';
        } else {
            try {
                // --- ▼▼▼ INICIO DE SQL MODIFICADO (Añadido title, status, privacy) ▼▼▼ ---
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
                        (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count
                     FROM community_publications p
                     JOIN users u ON p.user_id = u.id
                     LEFT JOIN communities c ON p.community_id = c.id
                     WHERE p.id = ?"; 
                
                $stmt_post = $pdo->prepare($sql_post);
                $stmt_post->execute([$userId, $userId, $userId, $postId]);
                // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---
                
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
        $viewProfileData = null;
        $targetUsername = $_GET['username'] ?? '';
        $currentTab = $_GET['tab'] ?? 'posts'; 
        $currentUserId = $_SESSION['user_id'];
        $isOwnProfile = false; 

        if (empty($targetUsername)) {
             $page = '404';
             $CURRENT_SECTION = '404';
        } else {
             try {
                 // 1. Obtener datos del perfil
                 $stmt_profile = $pdo->prepare(
                    "SELECT id, username, profile_image_url, role, created_at, is_online, last_seen 
                     FROM users 
                     WHERE username = ?"
                 );
                 $stmt_profile->execute([$targetUsername]);
                 $userProfile = $stmt_profile->fetch();

                 if (!$userProfile) {
                     $page = '404';
                     $CURRENT_SECTION = '404';
                 } else {
                     $viewProfileData = $userProfile;
                     $targetUserId = $userProfile['id'];
                     $isOwnProfile = ($targetUserId == $currentUserId);
                     
                     $viewProfileData['current_tab'] = $currentTab;

                     $friendshipStatus = 'not_friends'; 

                     if (!$isOwnProfile) {
                         $userId1 = min($currentUserId, $targetUserId);
                         $userId2 = max($currentUserId, $targetUserId);

                         $stmt_friend = $pdo->prepare("SELECT status, action_user_id FROM friendships WHERE user_id_1 = ? AND user_id_2 = ?");
                         $stmt_friend->execute([$userId1, $userId2]);
                         $friendship = $stmt_friend->fetch();

                         if ($friendship) {
                             if ($friendship['status'] === 'accepted') {
                                 $friendshipStatus = 'friends';
                             } elseif ($friendship['status'] === 'pending') {
                                 if ($friendship['action_user_id'] == $currentUserId) {
                                     $friendshipStatus = 'pending_sent'; 
                                 } else {
                                     $friendshipStatus = 'pending_received'; 
                                 }
                             }
                         }
                     }
                     
                     $viewProfileData['friendship_status'] = $friendshipStatus;

                     // 2. Obtener publicaciones del usuario (LÓGICA MODIFICADA)
                     
                     // --- ▼▼▼ INICIO DE SQL MODIFICADO (Añadido title, status, privacy) ▼▼▼ ---
                     $sql_select_base = 
                        "SELECT 
                            p.*, 
                            u.username, 
                            u.profile_image_url,
                            u.role,
                            p.title,
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
                            (SELECT COUNT(*) FROM publication_comments pc WHERE pc.publication_id = p.id) AS comment_count";

                     $sql_from_base = 
                        " FROM community_publications p
                         JOIN users u ON p.user_id = u.id
                         LEFT JOIN communities c ON p.community_id = c.id";

                     $sql_join_where_clause = "";
                     $params = [$currentUserId, $currentUserId, $currentUserId]; 
                     
                     if ($isOwnProfile && $currentTab === 'likes') {
                         $sql_join_where_clause = " JOIN publication_likes pl ON p.id = pl.publication_id WHERE pl.user_id = ? AND p.post_status = 'active' ";
                         $params[] = $targetUserId; 
                     
                     } elseif ($isOwnProfile && $currentTab === 'bookmarks') {
                         $sql_join_where_clause = " JOIN publication_bookmarks pb ON p.id = pb.publication_id WHERE pb.user_id = ? AND p.post_status = 'active' ";
                         $params[] = $targetUserId; 
                     
                     } else {
                         // --- ▼▼▼ INICIO DE LÓGICA DE PERFIL (CORREGIDA) ▼▼▼ ---
                         $privacyClause = "";
                         if (!$isOwnProfile) { 
                             if ($friendshipStatus === 'friends') {
                                 // Si somos amigos, veo 'public' y 'friends'
                                 $privacyClause = "AND (p.privacy_level = 'public' OR p.privacy_level = 'friends')";
                             } else {
                                 // Si no somos amigos, solo veo 'public'
                                 $privacyClause = "AND p.privacy_level = 'public'";
                             }
                             
                             // Adicionalmente, solo mostrar posts de comunidades públicas si no es mi perfil
                             $privacyClause .= " AND (c.privacy = 'public' OR p.community_id IS NULL)"; 
                         } else {
                             // Si es mi perfil, veo todo (público, amigos, privado)
                             // No se añade cláusula de privacidad
                         }
                         $sql_join_where_clause = " WHERE p.user_id = ? AND p.post_status = 'active' $privacyClause ";
                         $params[] = $targetUserId;
                         // --- ▲▲▲ FIN DE LÓGICA DE PERFIL (CORREGIDA) ▲▲▲ ---
                     }
                     // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---

                     $sql_order = " ORDER BY p.created_at DESC LIMIT 50";
                     $sql_posts = $sql_select_base . $sql_from_base . $sql_join_where_clause . $sql_order;

                    $stmt_posts = $pdo->prepare($sql_posts);
                    $stmt_posts->execute($params);
                    $publications = $stmt_posts->fetchAll();
                    
                    if (!empty($publications)) {
                        $pollIds = [];
                        foreach ($publications as $key => $post) {
                            if ($post['post_type'] === 'poll') {
                                $pollIds[] = $post['id'];
                            }
                        }
                        if (!empty($pollIds)) {
                            $placeholders = implode(',', array_fill(0, count($pollIds), '?'));
                            $stmt_options = $pdo->prepare(
                               "SELECT 
                                    po.publication_id, po.id, po.option_text, COUNT(pv.id) AS vote_count
                                FROM poll_options po
                                LEFT JOIN poll_votes pv ON po.id = pv.poll_option_id
                                WHERE po.publication_id IN ($placeholders)
                                GROUP BY po.publication_id, po.id, po.option_text 
                                ORDER BY po.id ASC"
                            );
                            $stmt_options->execute($pollIds);
                            $options = $stmt_options->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
                            foreach ($publications as $key => $post) {
                                if (isset($options[$post['id']])) {
                                    $publications[$key]['poll_options'] = $options[$post['id']];
                                } else {
                                    $publications[$key]['poll_options'] = [];
                                }
                            }
                        }
                    }
                    $viewProfileData['publications'] = $publications;
                 }
             } catch (PDOException $e) {
                 logDatabaseError($e, 'router - view-profile');
                 $page = '404';
                 $CURRENT_SECTION = '404';
             }
        }
    
    // --- ▼▼▼ INICIO DE NUEVA LÓGICA DE BÚSQUEDA (MODIFICADA) ▼▼▼ ---
    } elseif ($page === 'search-results') {
        $searchQuery = $_GET['q'] ?? '';
        $userResults = [];
        $postResults = [];
        
        if (!empty($searchQuery)) {
            $searchParam = '%' . $searchQuery . '%';
            $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";
            $currentUserId = $_SESSION['user_id'];

            try {
                // 1. Buscar Usuarios
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

                // 2. Buscar Publicaciones
                // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDA LÓGICA DE AMIGOS) ▼▼▼ ---
                $stmt_posts = $pdo->prepare(
                   "SELECT 
                        p.id, 
                        p.title, 
                        p.text_content, 
                        p.created_at,
                        u.username,
                        u.profile_image_url,
                        u.role
                    FROM community_publications p
                    JOIN users u ON p.user_id = u.id
                    LEFT JOIN communities c ON p.community_id = c.id
                    WHERE 
                        (p.text_content LIKE ? OR p.title LIKE ?) 
                    AND 
                        (c.privacy = 'public' OR p.community_id IN (
                            SELECT community_id FROM user_communities WHERE user_id = ?
                        ))
                    AND p.post_status = 'active'
                    AND (
                        p.privacy_level = 'public' -- Ver posts públicos
                        OR (p.privacy_level = 'friends' AND ( -- O ver posts de amigos si:
                            p.user_id = ? -- 1. Yo soy el autor
                            OR p.user_id IN ( -- 2. El autor es mi amigo
                                (SELECT user_id_2 FROM friendships WHERE user_id_1 = ? AND status = 'accepted')
                                UNION
                                (SELECT user_id_1 FROM friendships WHERE user_id_2 = ? AND status = 'accepted')
                            )
                        ))
                    )
                    ORDER BY p.created_at DESC
                    LIMIT 20"
                );
                // ¡Ahora se pasan 6 parámetros!
                $stmt_posts->execute([$searchParam, $searchParam, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
                $postResults = $stmt_posts->fetchAll();
                // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---

            } catch (PDOException $e) {
                logDatabaseError($e, 'router - search-results');
            }
        }
    // --- ▲▲▲ FIN DE NUEVA LÓGICA DE BÚSQUEDA ▲▲▲ ---

    } elseif ($page === 'admin-manage-users') {
        $adminCurrentPage = (int)($_GET['p'] ?? 1);
        if ($adminCurrentPage < 1) $adminCurrentPage = 1;
    }
    elseif ($page === 'admin-edit-user') {
        $targetUserId = (int)($_GET['id'] ?? 0);
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
    }
    elseif ($page === 'admin-server-settings') {
        $maintenanceModeStatus = $GLOBALS['site_settings']['maintenance_mode'] ?? '0';
        $allowRegistrationStatus = $GLOBALS['site_settings']['allow_new_registrations'] ?? '1';
    }
    elseif ($isAdminPage) {
        // Bloque vacío intencional para lógica futura de admin
    }
    
    
    include $allowedPages[$page];

} else {
    http_response_code(404);
    $CURRENT_SECTION = '404'; 
    include $allowedPages['404']; 
}
?>