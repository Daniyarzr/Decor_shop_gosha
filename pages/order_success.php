<?php
session_start();
require_once '../config.php';

// Если нет ID заказа в GET, перенаправляем на главную
if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)$_GET['order_id'];

// Получаем информацию о заказе
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, 
               o.shipping_address, o.phone, o.payment_method
        FROM orders o
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
}

// Если заказ не найден или пользователь не авторизован
if (!$order) {
    $order = [
        'order_number' => '#' . rand(100000, 999999),
        'total_amount' => 0,
        'status' => 'pending'
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказ оформлен — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .success-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .success-message {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .order-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }
        
        .btn {
            padding: 12px 25px;
            background: #f25081;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #d93a6a;
        }
        
        .btn-continue {
            background: #6c757d;
        }
        
        .btn-continue:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="success-message">Заказ успешно оформлен!</h1>
        <p>Спасибо за ваш заказ. Мы свяжемся с вами в ближайшее время для подтверждения.</p>
        
        <div class="order-details">
            <p><strong>Номер заказа:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
            <p><strong>Статус:</strong> 
                <?php 
                $status_text = [
                    'pending' => 'Ожидает обработки',
                    'processing' => 'В обработке',
                    'completed' => 'Выполнен',
                    'cancelled' => 'Отменен'
                ];
                echo $status_text[$order['status']] ?? $order['status'];
                ?>
            </p>
            <p><strong>Сумма заказа:</strong> <?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</p>
            <p><strong>Ожидаемая дата доставки:</strong> 3-5 рабочих дней</p>
            <?php if (!empty($order['shipping_address'])): ?>
                <p><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
            <?php endif; ?>
        </div>
        
        <a href="../index.php" class="btn btn-continue">Вернуться на главную</a>
        <a href="catalog.php" class="btn btn-checkout">Продолжить покупки</a>
        <a href="profile.php#orders" class="btn btn-continue">Мои заказы</a>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>