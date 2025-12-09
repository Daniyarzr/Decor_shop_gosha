<?php
session_start();
require_once '../config.php';

// Если пользователь не авторизован - перенаправляем на вход
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем информацию о пользователе
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Пользователь не найден (удален из БД)
    session_destroy();
    header('Location: login.php');
    exit;
}

// Получаем историю заказов пользователя
$orders_stmt = $pdo->prepare("
    SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, 
           o.shipping_address, o.phone, o.payment_method
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll();

// Получаем детали заказов
$order_items_by_order = [];
if (!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    
    $items_stmt = $pdo->prepare("
        SELECT oi.order_id, oi.product_name, oi.product_price, oi.quantity, oi.subtotal
        FROM order_items oi
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id DESC
    ");
    $items_stmt->execute($order_ids);
    $order_items = $items_stmt->fetchAll();
    
    // Группируем товары по заказам
    foreach ($order_items as $item) {
        $order_items_by_order[$item['order_id']][] = $item;
    }
}

// Обработка обновления профиля
$message = '';
$message_type = ''; // success или error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Проверка обязательных полей
        if (empty($username) || empty($email)) {
            $message = 'Логин и email обязательны для заполнения.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Некорректный email.';
            $message_type = 'error';
        } else {
            // Проверяем, не занят ли email другим пользователем
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->execute([$email, $user_id]);
            if ($check_stmt->fetch()) {
                $message = 'Этот email уже используется другим пользователем.';
                $message_type = 'error';
            } else {
                // Если введен новый пароль - проверяем текущий
                if (!empty($new_password)) {
                    // Получаем текущий пароль пользователя
                    $password_stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                    $password_stmt->execute([$user_id]);
                    $db_password = $password_stmt->fetchColumn();
                    
                    if (empty($current_password) || !password_verify($current_password, $db_password)) {
                        $message = 'Неверный текущий пароль.';
                        $message_type = 'error';
                    } elseif ($new_password !== $confirm_password) {
                        $message = 'Новые пароли не совпадают.';
                        $message_type = 'error';
                    } elseif (strlen($new_password) < 6) {
                        $message = 'Новый пароль должен содержать минимум 6 символов.';
                        $message_type = 'error';
                    } else {
                        // Обновляем пароль
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                        if ($update_stmt->execute([$username, $email, $hashed_password, $user_id])) {
                            $_SESSION['username'] = $username;
                            $message = 'Профиль и пароль успешно обновлены!';
                            $message_type = 'success';
                        } else {
                            $message = 'Ошибка при обновлении профиля.';
                            $message_type = 'error';
                        }
                    }
                } else {
                    // Обновляем только логин и email
                    $update_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    if ($update_stmt->execute([$username, $email, $user_id])) {
                        $_SESSION['username'] = $username;
                        $message = 'Профиль успешно обновлен!';
                        $message_type = 'success';
                    } else {
                        $message = 'Ошибка при обновлении профиля.';
                        $message_type = 'error';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f25081;
        }
        
        .profile-title {
            font-size: 32px;
            color: #333;
            margin: 0;
        }
        
        .profile-welcome {
            font-size: 16px;
            color: #666;
        }
        
        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }
        
        .profile-sidebar {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .profile-menu li {
            margin-bottom: 10px;
        }
        
        .profile-menu a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .profile-menu a:hover {
            background: #f9f9f9;
            color: #f25081;
        }
        
        .profile-menu a.active {
            background: #f25081;
            color: white;
        }
        
        .profile-main {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #444;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #f25081;
            box-shadow: 0 0 0 2px rgba(242, 80, 129, 0.1);
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
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #d93a6a;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .message {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Стили для истории заказов */
        .orders-list {
            margin-top: 20px;
        }
        
        .order-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-number {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .order-date {
            color: #666;
            font-size: 14px;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-details {
            margin-bottom: 15px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #f25081;
            margin-top: 10px;
        }
        
        .order-address {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-size: 16px;
        }
        
        .no-orders p {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="profile-container">
        <div class="profile-header">
            <div>
                <h1 class="profile-title">Личный кабинет</h1>
                <p class="profile-welcome">Добро пожаловать, <?= htmlspecialchars($user['username']) ?>!</p>
            </div>
            <div>
                <span style="color: #666; font-size: 14px;">
                    Дата регистрации: <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                </span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-content">
            <!-- Боковое меню -->
            <aside class="profile-sidebar">
                <ul class="profile-menu">
                    <li><a href="#profile" class="active">Мой профиль</a></li>
                    <li><a href="#orders">История заказов</a></li>
                    <li><a href="logout.php">Выход</a></li>
                </ul>
            </aside>
            
            <!-- Основной контент -->
            <main class="profile-main">
                <!-- Вкладка профиля -->
                <div id="profile">
                    <h2 class="section-title">Мой профиль</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Логин</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <h3 class="section-title">Смена пароля</h3>
                        <p style="color: #666; margin-bottom: 20px; font-size: 14px;">
                            Оставьте эти поля пустыми, если не хотите менять пароль.
                        </p>
                        
                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите новый пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn">Сохранить изменения</button>
                    </form>
                </div>
                
                <!-- Вкладка истории заказов -->
                <div id="orders" style="display: none;">
                    <h2 class="section-title">История заказов</h2>
                    
                    <?php if (empty($orders)): ?>
                        <div class="no-orders">
                            <p>У вас пока нет заказов.</p>
                            <a href="catalog.php" class="btn">Перейти в каталог</a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div>
                                            <div class="order-number">Заказ #<?= htmlspecialchars($order['order_number']) ?></div>
                                            <div class="order-date"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                                        </div>
                                        <div>
                                            <span class="order-status status-<?= htmlspecialchars($order['status']) ?>">
                                                <?php 
                                                $status_text = [
                                                    'pending' => 'Ожидает обработки',
                                                    'processing' => 'В обработке',
                                                    'completed' => 'Выполнен',
                                                    'cancelled' => 'Отменен'
                                                ];
                                                echo $status_text[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-details">
                                        <?php if (isset($order_items_by_order[$order['id']])): ?>
                                            <?php foreach ($order_items_by_order[$order['id']] as $item): ?>
                                                <div class="order-item">
                                                    <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                                                    <span><?= number_format($item['subtotal'], 0, ',', ' ') ?> ₽</span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="order-total">
                                        Итого: <?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽
                                    </div>
                                    
                                    <?php if (!empty($order['shipping_address']) || !empty($order['phone'])): ?>
                                        <div class="order-address">
                                            <?php if (!empty($order['shipping_address'])): ?>
                                                <div><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($order['phone'])): ?>
                                                <div><strong>Телефон:</strong> <?= htmlspecialchars($order['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($order['payment_method'])): ?>
                                                <div><strong>Способ оплаты:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Переключение между вкладками
        document.querySelectorAll('.profile-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Убираем активный класс у всех ссылок
                document.querySelectorAll('.profile-menu a').forEach(a => {
                    a.classList.remove('active');
                });
                
                // Добавляем активный класс текущей ссылке
                this.classList.add('active');
                
                // Скрываем все вкладки
                document.querySelectorAll('.profile-main > div').forEach(div => {
                    div.style.display = 'none';
                });
                
                // Показываем выбранную вкладку
                const targetId = this.getAttribute('href').substring(1);
                document.getElementById(targetId).style.display = 'block';
            });
        });
    </script>
</body>
</html>