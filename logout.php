<?php
// logout.php

// Запускаем сессию
session_start();

// Уничтожаем все данные сессии
$_SESSION = [];

// Если используется кука сессии — удаляем её
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Завершаем сессию
session_destroy();

// Перенаправляем на главную
header('Location: index.php');
exit;