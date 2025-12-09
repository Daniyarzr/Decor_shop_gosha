<?php
// get_promotion.php
require_once '../config.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($promotion);
}