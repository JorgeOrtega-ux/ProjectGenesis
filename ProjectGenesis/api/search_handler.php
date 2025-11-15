<?php

include '../config/config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'users' => [], 'posts' => [], 'communities' => []]; 

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
            LIMIT 5"
        );
        $stmt_posts->execute([$searchParam, $searchParam, $userId, $userId, $userId, $userId]);
        $posts = $stmt_posts->fetchAll();
        
        foreach ($posts as $post) {
             $response['posts'][] = [
                'id' => $post['id'],
                'title' => htmlspecialchars($post['title'] ?? ''), 
                'text' => htmlspecialchars(mb_substr($post['text_content'], 0, 100)), 
                'author' => htmlspecialchars($post['author_username'])
            ];
        }

        $stmt_comm = $pdo->prepare(
            "SELECT id, uuid, name, icon_url 
             FROM communities 
             WHERE name LIKE ? 
             AND privacy = 'public'
             LIMIT 3"
        );
        $stmt_comm->execute([$searchParam]);
        $communities = $stmt_comm->fetchAll();

        foreach ($communities as $community) {
            $icon = $community['icon_url'] ?? $defaultAvatar;
            if (empty($icon)) {
                 $icon = "https://ui-avatars.com/api/?name=" . urlencode($community['name']) . "&size=100&background=e0e0e0&color=ffffff";
            }
            $response['communities'][] = [
                'id' => $community['id'],
                'uuid' => $community['uuid'],
                'name' => htmlspecialchars($community['name']),
                'icon_url' => htmlspecialchars($icon)
            ];
        }

        $response['success'] = true;

    } catch (PDOException $e) {
        logDatabaseError($e, 'search_handler');
        $response['message'] = 'js.api.errorDatabase';
    }
}

echo json_encode($response);
exit;
?>