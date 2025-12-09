<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?error=not_logged_in');
    exit;
}

$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
$allowed_roles = ['admin', 'moderator'];
if (!in_array($user_role, $allowed_roles)) {
    header('Location: ../index.php?error=access_denied');
    exit;
}

require_once '../config.php';

$user_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Администратор');
$is_admin = ($user_role === 'admin');
try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: ../login.php?error=user_not_found');
        exit;
    }
} catch (PDOException $e) {
    error_log("Ошибка БД в auth_check: " . $e->getMessage());
    // Не прерываем работу, продолжаем с данными из сессии
}
?>