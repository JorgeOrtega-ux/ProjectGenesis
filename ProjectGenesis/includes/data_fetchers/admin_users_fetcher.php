<?php
// FILE: includes/data_fetchers/admin_users_fetcher.php

/**
 * Obtiene los datos para la página de gestión de usuarios del admin.
 *
 * @param PDO $pdo La conexión a la base de datos.
 * @param array $getParams Los parámetros de $_GET (p, q, s, o).
 * @return array Un array con todos los datos para la vista.
 */
function getAdminUsersData($pdo, $getParams)
{
    $usersList = [];
    $defaultAvatar = "https://ui-avatars.com/api/?name=?&size=100&background=e0e0e0&color=ffffff";

    // 1. OBTENER PARÁMETROS DE URL
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

    $usersPerPage = 1; // 20 usuarios por página
    $totalUsers = 0;
    $totalPages = 1;

    try {
        // 2. Contar el total de usuarios (con filtro si existe)
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
            $totalPages = 1; // Si no hay usuarios, seguimos en la página 1
        }

        // 2. Asegurarse de que la página actual es válida
        if ($adminCurrentPage > $totalPages) {
            $adminCurrentPage = $totalPages;
        }

        // 3. Calcular el OFFSET
        $offset = ($adminCurrentPage - 1) * $usersPerPage;

        // 4. Obtener los usuarios para la página actual (con filtro si existe)
        $sqlSelect = "SELECT id, username, email, profile_image_url, role, created_at, account_status 
                      FROM users";
        if ($isSearching) {
            $sqlSelect .= " WHERE (username LIKE :query OR email LIKE :query)";
        }
        
        $sqlSelect .= " ORDER BY $sort_by_sql $sort_order_sql LIMIT :limit OFFSET :offset";

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
        // Los valores por defecto se mantendrán (listas vacías, página 1)
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
        'usersPerPage' => $usersPerPage // Aunque es estático, lo pasamos por consistencia
    ];
}
?>