<?php

function getAdminUsersData($pdo, $getParams)
{
    $usersList = [];
    $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

    $adminCurrentPage = (int)($getParams['p'] ?? 1);
    if ($adminCurrentPage < 1) $adminCurrentPage = 1;

    $searchQuery = trim($getParams['q'] ?? '');
    $isSearching = !empty($searchQuery);

    $sort_by_param = trim($getParams['s'] ?? '');
    $sort_order_param = trim($getParams['o'] ?? '');

    $allowed_sort = ['created_at', 'username', 'email'];
    $allowed_order = ['ASC', 'DESC'];

    if (!in_array($sort_by_param, $allowed_sort)) {
        $sort_by_param = '';
    }
    if (!in_array($sort_order_param, $allowed_order)) {
        $sort_order_param = '';
    }

    $sort_by_sql = ($sort_by_param === '') ? 'created_at' : $sort_by_param;
    $sort_order_sql = ($sort_order_param === '') ? 'DESC' : $sort_order_param;

    $usersPerPage = 1; 
    $totalUsers = 0;
    $totalPages = 1;

    try {
        $sqlCount = "SELECT COUNT(*) FROM users";
        if ($isSearching) {
            $sqlCount .= " WHERE (username LIKE :query OR email LIKE :query)";
        }

        $totalUsersStmt = $pdo->prepare($sqlCount);

        if ($isSearching) {
            $searchParam = '%' . $searchQuery . '%';
            $totalUsersStmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
        }

        $totalUsersStmt->execute();
        $totalUsers = (int)$totalUsersStmt->fetchColumn();

        if ($totalUsers > 0) {
            $totalPages = (int)ceil($totalUsers / $usersPerPage);
        } else {
            $totalPages = 1; 
        }

        if ($adminCurrentPage > $totalPages) {
            $adminCurrentPage = $totalPages;
        }

        $offset = ($adminCurrentPage - 1) * $usersPerPage;

        // --- ▼▼▼ INICIO DE MODIFICACIÓN (SQL) ▼▼▼ ---
        $sqlSelect = "SELECT 
                        u.id, u.username, u.email, u.profile_image_url, 
                        u.role, u.created_at, u.account_status,
                        GROUP_CONCAT(r.restriction_type) AS restrictions
                      FROM users u
                      LEFT JOIN user_restrictions r ON u.id = r.user_id AND (r.expires_at IS NULL OR r.expires_at > NOW())";
        
        if ($isSearching) {
            $sqlSelect .= " WHERE (u.username LIKE :query OR u.email LIKE :query)";
        }
        
        $sqlSelect .= " GROUP BY u.id"; // <-- Agrupado por usuario
        $sqlSelect .= " ORDER BY $sort_by_sql $sort_order_sql LIMIT :limit OFFSET :offset";
        // --- ▲▲▲ FIN DE MODIFICACIÓN ▲▲▲ ---

        $stmt = $pdo->prepare($sqlSelect);

        if ($isSearching) {
            $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $usersPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $usersList = $stmt->fetchAll();

    } catch (PDOException $e) {
        logDatabaseError($e, 'admin_users_fetcher - getAdminUsersData');
    }

    return [
        'usersList' => $usersList,
        'defaultAvatar' => $defaultAvatar,
        'adminCurrentPage' => $adminCurrentPage,
        'searchQuery' => $searchQuery,
        'isSearching' => $isSearching,
        'sort_by_param' => $sort_by_param,
        'sort_order_param' => $sort_order_param,
        'totalUsers' => $totalUsers,
        'totalPages' => $totalPages,
        'usersPerPage' => $usersPerPage 
    ];
}
?>