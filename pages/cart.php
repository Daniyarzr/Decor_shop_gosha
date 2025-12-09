<?php
session_start();
require_once '../config.php';

// Обработка GET-запроса для получения количества товаров
if (isset($_GET['get_count'])) {
    $count = 0;
    if (!empty($_SESSION['cart'])) {
        $count = array_sum($_SESSION['cart']);
    }
    echo json_encode(['count' => $count]);
    exit;
}

// Обработка GET-запроса для получения деталей корзины (для пересчета на клиенте)
if (isset($_GET['get_cart_details'])) {
    $cart_details = [];
    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        if (!empty($ids)) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $products_by_id = [];
            foreach ($products as $product) {
                $products_by_id[$product['id']] = $product;
            }
            
            foreach ($_SESSION['cart'] as $id => $qty) {
                if (isset($products_by_id[$id])) {
                    $product = $products_by_id[$id];
                    $cart_details[] = [
                        'id' => $id,
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'qty' => $qty,
                        'image' => $product['image']
                    ];
                }
            }
        }
    }
    echo json_encode($cart_details);
    exit;
}

// Обработка AJAX запросов для работы с корзиной
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $response = ['status' => 'error', 'message' => 'Unknown action'];
        
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['product_id'])) {
                    $id = (int)$_POST['product_id'];
                    if (!isset($_SESSION['cart'][$id])) {
                        $_SESSION['cart'][$id] = 1;
                    } else {
                        $_SESSION['cart'][$id]++;
                    }
                    $response = ['status' => 'success', 'count' => array_sum($_SESSION['cart'])];
                }
                break;
                
            case 'update':
                if (isset($_POST['product_id'], $_POST['quantity'])) {
                    $id = (int)$_POST['product_id'];
                    $quantity = (int)$_POST['quantity'];
                    if ($quantity <= 0) {
                        unset($_SESSION['cart'][$id]);
                    } else {
                        $_SESSION['cart'][$id] = $quantity;
                    }
                    $response = ['status' => 'success', 'count' => array_sum($_SESSION['cart'])];
                }
                break;
                
            case 'remove':
                if (isset($_POST['product_id'])) {
                    $id = (int)$_POST['product_id'];
                    unset($_SESSION['cart'][$id]);
                    $response = ['status' => 'success', 'count' => array_sum($_SESSION['cart'])];
                }
                break;
                
            case 'clear':
                $_SESSION['cart'] = [];
                $response = ['status' => 'success', 'count' => 0];
                break;
        }
        
        echo json_encode($response);
        exit;
    }
}

// Получение данных корзины для отображения
$cart_items = [];
$total = 0;
$total_items = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Преобразуем в массив с id в качестве ключа
        $products_by_id = [];
        foreach ($products as $product) {
            $products_by_id[$product['id']] = $product;
        }
        
        foreach ($_SESSION['cart'] as $id => $qty) {
            if (isset($products_by_id[$id])) {
                $product = $products_by_id[$id];
                $item_total = $product['price'] * $qty;
                $cart_items[] = [
                    'id' => $id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'qty' => $qty,
                    'image' => $product['image'],
                    'total' => $item_total
                ];
                $total += $item_total;
                $total_items += $qty;
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
    <title>Корзина — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Общие стили */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Стили для корзины */
        .cart-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f25081;
        }
        
        .cart-title {
            font-size: 32px;
            color: #333;
            margin: 0;
        }
        
        .cart-summary {
            font-size: 18px;
            color: #666;
            font-weight: 500;
        }
        
        /* Стили для товаров */
        .cart-items {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 2fr 1fr 1fr 1fr 50px;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            gap: 15px;
            transition: background-color 0.2s;
        }
        
        .cart-item:hover {
            background-color: #f9f9f9;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item-image {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-item-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .cart-item-name {
            font-size: 18px;
            color: #333;
            font-weight: 500;
        }
        
        .cart-item-price {
            font-size: 18px;
            color: #333;
            text-align: center;
            font-weight: 500;
        }
        
        /* Стили для количества */
        .cart-item-quantity {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .quantity-btn:hover {
            background: #f5f5f5;
            border-color: #f25081;
            color: #f25081;
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px 5px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: #f25081;
            box-shadow: 0 0 0 2px rgba(242, 80, 129, 0.1);
        }
        
        .quantity-input:disabled {
            background-color: #f9f9f9;
        }
        
        .cart-item-total {
            font-size: 18px;
            font-weight: bold;
            color: #f25081;
            text-align: center;
        }
        
        /* Кнопка удаления */
        .remove-btn {
            color: #ff6b6b;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-btn:hover {
            color: #ff4444;
            background: #ffeaea;
        }
        
        .remove-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Корзина пуста */
        .cart-empty {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
        }
        
        .cart-empty h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        
        .cart-empty p {
            margin-bottom: 30px;
            color: #777;
        }
        
        /* Итоговая сумма */
        .cart-total {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 18px;
            color: #555;
        }
        
        .total-row.grand-total {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            border-top: 2px solid #eee;
            padding-top: 20px;
            margin-top: 10px;
        }
        
        /* Кнопки действий */
        .cart-actions {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            min-width: 180px;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-continue {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-continue:hover:not(:disabled) {
            background: #e5e5e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-clear {
            background: #ff4444;
            color: white;
        }
        
        .btn-clear:hover:not(:disabled) {
            background: #cc0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 68, 68, 0.2);
        }
        
        .btn-checkout {
            background: #f25081;
            color: white;
            flex-grow: 1;
            font-size: 18px;
        }
        
        .btn-checkout:hover:not(:disabled) {
            background: #d93a6a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(242, 80, 129, 0.3);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        /* Стили для счетчика в шапке */
        .cart-count {
            background: #f25081;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        /* Адаптивность */
        @media (max-width: 992px) {
            .cart-item {
                grid-template-columns: 80px 1fr;
                grid-template-rows: auto auto auto;
                gap: 10px;
            }
            
            .cart-item-image {
                grid-row: 1 / 4;
            }
            
            .cart-item-name {
                grid-column: 2;
                grid-row: 1;
            }
            
            .cart-item-price {
                grid-column: 2;
                grid-row: 2;
                text-align: left;
            }
            
            .cart-item-quantity {
                grid-column: 2;
                grid-row: 3;
                justify-content: flex-start;
            }
            
            .cart-item-total {
                grid-column: 3;
                grid-row: 1 / 4;
                align-self: center;
            }
            
            .remove-btn {
                grid-column: 3;
                grid-row: 1 / 4;
                align-self: center;
                justify-self: end;
            }
        }
        
        @media (max-width: 576px) {
            .cart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .cart-item {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Загрузка */
        .loading {
            position: relative;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f25081;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Всплывающие уведомления */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 25px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        .notification.error {
            background: #f44336;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Включаем header из той же папки
    include './header.php'; 
    ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1 class="cart-title">Корзина</h1>
            <div class="cart-summary">
                <?php if ($total_items > 0): ?>
                    <i class="fas fa-shopping-cart"></i> 
                    Товаров: <strong id="summary-count"><?= $total_items ?></strong> шт. 
                    на сумму <strong id="summary-total"><?= number_format($total, 0, ',', ' ') ?> ₽</strong>
                <?php else: ?>
                    Корзина пуста
                <?php endif; ?>
            </div>
        </div>

        <div class="cart-items" id="cart-items-container">
            <?php if (empty($cart_items)): ?>
                <div class="cart-empty">
                    <h3>Ваша корзина пуста</h3>
                    <p>Добавьте товары из каталога, чтобы продолжить покупки</p>
                    <a href="catalog.php" class="btn btn-continue">
                        <i class="fas fa-arrow-left"></i> Перейти в каталог
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="cart-item-<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                        <div class="cart-item-image">
                            <?php if (!empty($item['image'])): ?>
                                <img src="../assets/img/<?= htmlspecialchars($item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                     onerror="this.src='../assets/img/no-image.jpg'">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #999;">
                                    Нет фото
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-name">
                            <?= htmlspecialchars($item['name']) ?>
                        </div>
                        <div class="cart-item-price" id="price-<?= $item['id'] ?>">
                            <?= number_format($item['price'], 0, ',', ' ') ?> ₽
                        </div>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn minus" data-id="<?= $item['id'] ?>" title="Уменьшить">
                                -
                            </button>
                            <input type="number" 
                                   class="quantity-input" 
                                   value="<?= $item['qty'] ?>" 
                                   min="1" 
                                   max="99" 
                                   data-id="<?= $item['id'] ?>"
                                   title="Количество">
                            <button class="quantity-btn plus" data-id="<?= $item['id'] ?>" title="Увеличить">
                                +
                            </button>
                        </div>
                        <div class="cart-item-total" id="total-<?= $item['id'] ?>">
                            <?= number_format($item['total'], 0, ',', ' ') ?> ₽
                        </div>
                        <div>
                            <button class="remove-btn" data-id="<?= $item['id'] ?>" title="Удалить товар">
                                ×
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($cart_items)): ?>
            <div class="cart-total" id="cart-total-container">
                <div class="total-row">
                    <span>Товаров:</span>
                    <span id="total-items"><?= $total_items ?> шт.</span>
                </div>
                <div class="total-row">
                    <span>Сумма товаров:</span>
                    <span id="subtotal"><?= number_format($total, 0, ',', ' ') ?> ₽</span>
                </div>
                <div class="total-row">
                    <span>Доставка:</span>
                    <span>Бесплатно</span>
                </div>
                <div class="total-row grand-total">
                    <span>Итого к оплате:</span>
                    <span id="grand-total"><?= number_format($total, 0, ',', ' ') ?> ₽</span>
                </div>
            </div>

            <div class="cart-actions">
                <a href="catalog.php" class="btn btn-continue">
                    <i class="fas fa-arrow-left"></i> Продолжить покупки
                </a>
                <button class="btn btn-clear" id="clear-cart">
                    <i class="fas fa-trash"></i> Очистить корзину
                </button>
                <a href="checkout.php" class="btn btn-checkout">
                    <i class="fas fa-lock"></i> Оформить заказ
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php include './footer.php'; ?>

    <script>
        // Форматирование цены
        function formatPrice(price) {
            return price.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }
        
        // Показ уведомления
        function showNotification(message, isError = false) {
            // Удаляем старое уведомление
            const oldNotification = document.querySelector('.notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            // Создаем новое
            const notification = document.createElement('div');
            notification.className = `notification ${isError ? 'error' : ''}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Автоматическое удаление через 3 секунды
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Обновление счетчика в шапке
        function updateCartCount(count) {
            const cartCounts = document.querySelectorAll('.cart-count');
            cartCounts.forEach(element => {
                if (count > 0) {
                    element.textContent = count;
                    element.style.display = 'inline-flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }
        
        // Пересчет итогов корзины
        function recalculateCart() {
            let totalItems = 0;
            let subtotal = 0;
            
            // Собираем данные со всех товаров
            document.querySelectorAll('.cart-item').forEach(item => {
                const id = item.dataset.id;
                const quantityInput = item.querySelector('.quantity-input');
                const priceElement = item.querySelector('.cart-item-price');
                const totalElement = item.querySelector('.cart-item-total');
                
                if (quantityInput && priceElement && totalElement) {
                    const quantity = parseInt(quantityInput.value) || 0;
                    const priceText = priceElement.textContent.replace(/[^\d,]/g, '').replace(',', '.');
                    const price = parseFloat(priceText) || 0;
                    const itemTotal = price * quantity;
                    
                    // Обновляем сумму товара
                    totalElement.textContent = formatPrice(itemTotal) + ' ₽';
                    
                    totalItems += quantity;
                    subtotal += itemTotal;
                }
            });
            
            // Обновляем итоги
            const totalItemsElement = document.getElementById('total-items');
            const subtotalElement = document.getElementById('subtotal');
            const grandTotalElement = document.getElementById('grand-total');
            const summaryCountElement = document.getElementById('summary-count');
            const summaryTotalElement = document.getElementById('summary-total');
            
            if (totalItemsElement) totalItemsElement.textContent = totalItems + ' шт.';
            if (subtotalElement) subtotalElement.textContent = formatPrice(subtotal) + ' ₽';
            if (grandTotalElement) grandTotalElement.textContent = formatPrice(subtotal) + ' ₽';
            if (summaryCountElement) summaryCountElement.textContent = totalItems;
            if (summaryTotalElement) summaryTotalElement.textContent = formatPrice(subtotal) + ' ₽';
            
            // Обновляем счетчик в шапке
            updateCartCount(totalItems);
            
            return { totalItems, subtotal };
        }
        
        // Обновление количества товара
        async function updateQuantity(productId, quantity) {
            const cartItem = document.getElementById('cart-item-' + productId);
            if (!cartItem) return;
            
            const quantityInput = cartItem.querySelector('.quantity-input');
            const minusBtn = cartItem.querySelector('.minus');
            const plusBtn = cartItem.querySelector('.plus');
            const removeBtn = cartItem.querySelector('.remove-btn');
            
            // Блокируем элементы во время запроса
            quantityInput.disabled = true;
            minusBtn.disabled = true;
            plusBtn.disabled = true;
            removeBtn.disabled = true;
            cartItem.classList.add('loading');
            
            try {
                const response = await fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update&product_id=' + productId + '&quantity=' + quantity
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Обновляем количество в инпуте
                    quantityInput.value = quantity;
                    
                    // Пересчитываем корзину
                    recalculateCart();
                    
                    // Если количество 0, удаляем товар из DOM
                    if (quantity <= 0) {
                        setTimeout(() => {
                            cartItem.style.opacity = '0';
                            cartItem.style.transform = 'translateX(100px)';
                            setTimeout(() => {
                                cartItem.remove();
                                
                                // Проверяем, пуста ли корзина
                                const remainingItems = document.querySelectorAll('.cart-item').length;
                                if (remainingItems === 0) {
                                    showEmptyCartMessage();
                                }
                            }, 300);
                        }, 100);
                    }
                    
                    showNotification('Количество обновлено');
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при обновлении количества', true);
                // Откатываем значение в инпуте
                const currentQty = cartItem.querySelector('.quantity-input').dataset.currentQty || 1;
                quantityInput.value = currentQty;
            } finally {
                // Разблокируем элементы
                quantityInput.disabled = false;
                minusBtn.disabled = false;
                plusBtn.disabled = false;
                removeBtn.disabled = false;
                cartItem.classList.remove('loading');
            }
        }
        
        // Удаление товара
        async function removeItem(productId) {
            if (!confirm('Удалить товар из корзины?')) return;
            
            const cartItem = document.getElementById('cart-item-' + productId);
            if (!cartItem) return;
            
            const quantityInput = cartItem.querySelector('.quantity-input');
            const minusBtn = cartItem.querySelector('.minus');
            const plusBtn = cartItem.querySelector('.plus');
            const removeBtn = cartItem.querySelector('.remove-btn');
            
            // Блокируем элементы
            quantityInput.disabled = true;
            minusBtn.disabled = true;
            plusBtn.disabled = true;
            removeBtn.disabled = true;
            cartItem.classList.add('loading');
            
            try {
                const response = await fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=remove&product_id=' + productId
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Анимация удаления
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(100px)';
                    
                    setTimeout(() => {
                        cartItem.remove();
                        
                        // Обновляем счетчик
                        updateCartCount(data.count);
                        
                        // Пересчитываем корзину
                        recalculateCart();
                        
                        // Проверяем, пуста ли корзина
                        const remainingItems = document.querySelectorAll('.cart-item').length;
                        if (remainingItems === 0) {
                            showEmptyCartMessage();
                        }
                        
                        showNotification('Товар удален из корзины');
                    }, 300);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при удалении товара', true);
                // Разблокируем элементы
                quantityInput.disabled = false;
                minusBtn.disabled = false;
                plusBtn.disabled = false;
                removeBtn.disabled = false;
                cartItem.classList.remove('loading');
            }
        }
        
        // Показать сообщение о пустой корзине
        function showEmptyCartMessage() {
            const cartItemsContainer = document.getElementById('cart-items-container');
            const cartTotalContainer = document.getElementById('cart-total-container');
            const cartActions = document.querySelector('.cart-actions');
            
            if (cartItemsContainer && !cartItemsContainer.querySelector('.cart-empty')) {
                cartItemsContainer.innerHTML = `
                    <div class="cart-empty">
                        <h3>Ваша корзина пуста</h3>
                        <p>Добавьте товары из каталога, чтобы продолжить покупки</p>
                        <a href="catalog.php" class="btn btn-continue">
                            <i class="fas fa-arrow-left"></i> Перейти в каталог
                        </a>
                    </div>
                `;
                
                if (cartTotalContainer) cartTotalContainer.style.display = 'none';
                if (cartActions) cartActions.style.display = 'none';
                
                // Обновляем заголовок
                const cartSummary = document.querySelector('.cart-summary');
                if (cartSummary) {
                    cartSummary.innerHTML = 'Корзина пуста';
                }
            }
        }
        
        // Очистка корзины
        async function clearCart() {
            if (!confirm('Вы уверены, что хотите очистить всю корзину?')) return;
            
            const clearBtn = document.getElementById('clear-cart');
            const checkoutBtn = document.querySelector('.btn-checkout');
            
            if (clearBtn) clearBtn.disabled = true;
            if (checkoutBtn) checkoutBtn.style.pointerEvents = 'none';
            
            try {
                const response = await fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear'
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Анимация очистки
                    document.querySelectorAll('.cart-item').forEach(item => {
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(100px)';
                    });
                    
                    setTimeout(() => {
                        showEmptyCartMessage();
                        updateCartCount(0);
                        showNotification('Корзина очищена');
                    }, 500);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка при очистке корзины', true);
            } finally {
                if (clearBtn) clearBtn.disabled = false;
                if (checkoutBtn) checkoutBtn.style.pointerEvents = 'auto';
            }
        }
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            // Сохраняем начальные значения количества
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.dataset.currentQty = input.value;
            });
            
            // Обработчики для кнопок увеличения
            document.querySelectorAll('.quantity-btn.plus').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const input = this.parentNode.querySelector('.quantity-input');
                    let quantity = parseInt(input.value) || 1;
                    
                    if (quantity < 99) {
                        quantity++;
                        updateQuantity(productId, quantity);
                    }
                });
            });
            
            // Обработчики для кнопок уменьшения
            document.querySelectorAll('.quantity-btn.minus').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    const input = this.parentNode.querySelector('.quantity-input');
                    let quantity = parseInt(input.value) || 1;
                    
                    if (quantity > 1) {
                        quantity--;
                        updateQuantity(productId, quantity);
                    } else {
                        // Если количество станет 0, удаляем товар
                        removeItem(productId);
                    }
                });
            });
            
            // Обработчики изменения инпута
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.dataset.id;
                    let quantity = parseInt(this.value) || 1;
                    
                    // Валидация
                    if (isNaN(quantity) || quantity < 1) {
                        quantity = 1;
                        this.value = 1;
                    }
                    if (quantity > 99) {
                        quantity = 99;
                        this.value = 99;
                    }
                    
                    // Сохраняем текущее значение для отката при ошибке
                    this.dataset.currentQty = quantity;
                    
                    updateQuantity(productId, quantity);
                });
                
                // Сохраняем значение при фокусе для возможного отката
                input.addEventListener('focus', function() {
                    this.dataset.oldValue = this.value;
                });
                
                // Откат при нажатии Esc
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        this.value = this.dataset.oldValue || this.dataset.currentQty || 1;
                        this.blur();
                    }
                });
            });
            
            // Обработчики удаления
            document.querySelectorAll('.remove-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.id;
                    removeItem(productId);
                });
            });
            
            // Обработчик очистки корзины
            const clearBtn = document.getElementById('clear-cart');
            if (clearBtn) {
                clearBtn.addEventListener('click', clearCart);
            }
            
            // Защита от потери данных
            let cartModified = false;
            
            document.querySelectorAll('.quantity-btn, .quantity-input').forEach(element => {
                element.addEventListener('input', () => {
                    cartModified = true;
                });
                
                element.addEventListener('change', () => {
                    cartModified = true;
                });
            });
            
            window.addEventListener('beforeunload', (e) => {
                if (cartModified) {
                    e.preventDefault();
                    e.returnValue = 'У вас есть несохраненные изменения в корзине. Вы уверены, что хотите уйти?';
                }
            });
            
            // Сброс флага при успешном сохранении
            document.addEventListener('cartUpdated', () => {
                cartModified = false;
            });
        });
        
        // Добавляем иконки Font Awesome
        const faLink = document.createElement('link');
        faLink.rel = 'stylesheet';
        faLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
        document.head.appendChild(faLink);
    </script>
</body>
</html>