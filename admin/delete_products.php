<?php
require_once 'check_admin.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die('Неверный ID.');
}

// Удаляем изображение
$stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
$stmt->execute([$id]);
$image = $stmt->fetchColumn();

if ($image && file_exists('../assets/img/' . $image)) {
    unlink('../assets/img/' . $image);
}

// Удаляем товар
$stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

header('Location: products.php?deleted=1');
exit;