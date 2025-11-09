<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'users' => [], 'posts' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'js.settings.errorNoSession';
    echo json_encode($response);
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
        $response['message'] = 'js.api.errorSecurityRefresh';
        echo json_encode($response);
        exit;
    }

    $query = trim($_POST['q'] ?? '');

    if (empty($query)) {
        $response['success'] = true;
        echo json_encode($response);
        exit;
    }

    $searchParam = '%' . $query . '%';
    $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

    try {
        // 1. Buscar Usuarios
        $stmt_users = $pdo->prepare(
            "SELECT id, username, profile_image_url, role 
             FROM users 
             WHERE username LIKE ? 
             AND id != ? 
             AND account_status = 'active'
             LIMIT 5"
        );
        $stmt_users->execute([$searchParam, $userId]);
        $users = $stmt_users->fetchAll();

        foreach ($users as $user) {
            $avatar = $user['profile_image_url'] ?? $defaultAvatar;
            if (empty($avatar)) {
                 $avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&size=100&background=e0e0e0&color=ffffff";
            }
            $response['users'][] = [
                'username' => htmlspecialchars($user['username']),
                'avatar' => htmlspecialchars($avatar),
                'role' => htmlspecialchars($user['role'])
            ];
        }

        // --- ▼▼▼ INICIO DE SQL MODIFICADO (AÑADIDA LÓGICA DE AMIGOS) ▼▼▼ ---
        // 2. Buscar Publicaciones (solo de comunidades públicas o a las que pertenece)
        $stmt_posts = $pdo->prepare(
           "SELECT 
                p.id, 
                p.title,
                p.text_content, 
                u.username AS author_username
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
            LIMIT 5"
        );
        // ¡Ahora se pasan 6 parámetros!
        $stmt_posts->execute([$searchParam, $searchParam, $userId, $userId, $userId, $userId]);
        $posts = $stmt_posts->fetchAll();
        
        foreach ($posts as $post) {
             $response['posts'][] = [
                'id' => $post['id'],
                'title' => htmlspecialchars($post['title'] ?? ''), 
                'text' => htmlspecialchars(mb_substr($post['text_content'], 0, 100)), // Acortar texto
                'author' => htmlspecialchars($post['author_username'])
            ];
        }
        // --- ▲▲▲ FIN DE SQL MODIFICADO ▲▲▲ ---

        $response['success'] = true;

    } catch (PDOException $e) {
        logDatabaseError($e, 'search_handler');
        $response['message'] = 'js.api.errorDatabase';
    }
}

echo json_encode($response);
exit;
?>