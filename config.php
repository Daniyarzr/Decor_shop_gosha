<?php
// config.php
$host = '127.0.0.1';
$dbname = 'decor_shop';
$bdusername = 'mysql';
$bdpassword = 'mysql';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $bdusername, $bdpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("❌ Ошибка подключения к БД: " . $e->getMessage());
}

// Базовый URL вашего сайта
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/');

// Или для относительного пути от корня сервера
define('BASE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('BASE_URL_RELATIVE', '/');