<?php

include '../config.php'; 

$submittedToken = $_POST['csrf_token'] ?? '';

if (!validateCsrfToken($submittedToken)) {
    die('logout.invalidSession: Your session has expired or is invalid.');
}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare(
            "UPDATE users SET last_seen = NOW() WHERE id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        logDatabaseError($e, 'logout action');
    }
}


$_SESSION = [];

session_destroy();

header('Location: ' . $basePath . '/login');
exit;
?>