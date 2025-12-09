<?php
// admin/auth_check.php
session_start();

// 1. Начинаем сессию если не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. ВРЕМЕННО: отладка - что в сессии?
error_log("DEBUG: Сессия содержит: " . print_r($_SESSION, true));

// 3. Проверяем авторизацию (убедитесь что ключ правильный)
if (!isset($_SESSION['user_id'])) {
    error_log("DEBUG: Нет user_id в сессии, редирект на логин");
    header('Location: ../login.php?error=not_logged_in');
    exit;
}

// 4. Проверяем роль (ключ может быть 'role', а не 'user_role')
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
error_log("DEBUG: Роль пользователя: " . $user_role);

// 5. Разрешаем доступ только админам и модераторам
$allowed_roles = ['admin', 'moderator'];
if (!in_array($user_role, $allowed_roles)) {
    error_log("DEBUG: Роль $user_role не разрешена, редирект на главную");
    header('Location: ../index.php?error=access_denied');
    exit;
}

// 6. Подключаем конфигурацию БД
require_once '../config.php';

// 7. Данные пользователя для отображения
$user_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Администратор');

// 8. Определяем права
$is_admin = ($user_role === 'admin');

// 9. Проверяем, что пользователь все еще существует в БД
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