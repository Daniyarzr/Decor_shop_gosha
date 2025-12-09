<?php
session_start();
require_once '../config.php';

// Если корзина пуста, перенаправляем
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Если пользователь не авторизован, сохраняем данные в сессии и перенаправляем на вход
if (!isset($_SESSION['user_id'])) {
    $_SESSION['checkout_data'] = $_POST ?? [];
    header('Location: login.php?redirect=checkout');
    exit;
}

// Получаем данные корзины
$cart_items = [];
$total = 0;
$total_items = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $products_by_id = [];
        foreach ($products as $product) {
            $products_by_id[$product['id']] = $product;
        }
        
        foreach ($_SESSION['cart'] as $id => $qty) {
            if (isset($products_by_id[$id])) {
                $product = $products_by_id[$id];
                $item_total = $product['price'] * $qty;
                $cart_items[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'qty' => $qty,
                    'total' => $item_total
                ];
                $total += $item_total;
                $total_items += $qty;
            }
        }
    }
}

// Обработка оформления заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из формы
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cash');
    
    // Валидация
    $errors = [];
    if (empty($name)) $errors[] = 'Имя обязательно';
    if (empty($phone)) $errors[] = 'Телефон обязателен';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
    if (empty($city)) $errors[] = 'Город обязателен';
    if (empty($address)) $errors[] = 'Адрес обязателен';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Генерируем номер заказа
            $order_number = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Создаем запись заказа
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, status, 
                                   shipping_address, phone, email, customer_name, 
                                   city, comment, payment_method)
                VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $shipping_address = $city . ', ' . $address;
            $stmt->execute([
                $_SESSION['user_id'],
                $order_number,
                $total,
                $shipping_address,
                $phone,
                $email,
                $name,
                $city,
                $comment,
                $payment_method
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Сохраняем товары заказа
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, 
                                           product_price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['qty'],
                    $item['total']
                ]);
            }
            
            $pdo->commit();
            
            // Очищаем корзину
            $_SESSION['cart'] = [];
            
            // Удаляем данные оформления заказа из сессии, если они были
            if (isset($_SESSION['checkout_data'])) {
                unset($_SESSION['checkout_data']);
            }
            
            header('Location: order_success.php?order_id=' . $order_id);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Ошибка при оформлении заказа. Попробуйте позже.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Если есть сохраненные данные в сессии, подставляем их
if (isset($_SESSION['checkout_data'])) {
    $saved_data = $_SESSION['checkout_data'];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .order-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .order-summary h4 {
            margin-bottom: 15px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .order-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .error-message {
            color: #e74c3c;
            background: #ffecec;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="checkout-container">
        <h1>Оформление заказа</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" class="checkout-form">
            <div class="form-section">
                <h3>Контактная информация</h3>
                <div class="form-group">
                    <label>Имя *</label>
                    <input type="text" name="name" required 
                           value="<?= isset($saved_data['name']) ? htmlspecialchars($saved_data['name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" required 
                           value="<?= isset($saved_data['phone']) ? htmlspecialchars($saved_data['phone']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required 
                           value="<?= isset($saved_data['email']) ? htmlspecialchars($saved_data['email']) : (isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : '') ?>">
                </div>
            </div>
            
            <div class="form-section">
                <h3>Адрес доставки</h3>
                <div class="form-group">
                    <label>Город *</label>
                    <input type="text" name="city" required 
                           value="<?= isset($saved_data['city']) ? htmlspecialchars($saved_data['city']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Адрес (улица, дом, квартира) *</label>
                    <textarea name="address" rows="3" required><?= isset($saved_data['address']) ? htmlspecialchars($saved_data['address']) : '' ?></textarea>
                </div>
                <div class="form-group">
                    <label>Комментарий к заказу</label>
                    <textarea name="comment" rows="3"><?= isset($saved_data['comment']) ? htmlspecialchars($saved_data['comment']) : '' ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Способ оплаты</h3>
                <div class="form-group">
                    <select name="payment_method">
                        <option value="cash">Наличными при получении</option>
                        <option value="card">Банковской картой онлайн</option>
                        <option value="transfer">Банковский перевод</option>
                    </select>
                </div>
            </div>
            
            <div class="order-summary">
                <h4>Ваш заказ</h4>
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['qty'] ?></span>
                        <span><?= number_format($item['total'], 0, ',', ' ') ?> ₽</span>
                    </div>
                <?php endforeach; ?>
                <div class="order-item order-total">
                    <span>Итого:</span>
                    <span><?= number_format($total, 0, ',', ' ') ?> ₽</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-checkout">Подтвердить заказ</button>
        </form>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>