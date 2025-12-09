<?php
session_start();
require_once '../config.php';

// Получаем фильтры из GET-параметров
$selected_categories = [];
$selected_promotions = [];
$min_price = null;
$max_price = null;
$has_discount = false;

// Обработка категорий
if (isset($_GET['category'])) {
    if (is_array($_GET['category'])) {
        $selected_categories = array_filter(array_map('trim', $_GET['category']));
    } else {
        $cat = trim($_GET['category']);
        if (!empty($cat)) {
            $selected_categories = [$cat];
        }
    }
}

// Обработка акций
if (isset($_GET['promotion'])) {
    if (is_array($_GET['promotion'])) {
        $selected_promotions = array_filter(array_map('intval', $_GET['promotion']), function($v) { return $v > 0; });
    } else {
        $promo_id = (int)$_GET['promotion'];
        if ($promo_id > 0) {
            $selected_promotions = [$promo_id];
        }
    }
}

// Обработка цены
if (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
    $min_price = (float)$_GET['min_price'];
}
if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
    $max_price = (float)$_GET['max_price'];
}

// Обработка флага "только со скидкой"
if (isset($_GET['has_discount']) && ($_GET['has_discount'] === '1' || $_GET['has_discount'] === 'on')) {
    $has_discount = true;
}

// Получаем все активные акции для фильтра
$current_date = date('Y-m-d');
$promotions_stmt = $pdo->prepare("
    SELECT id, title, discount_value, discount_type 
    FROM promotions 
    WHERE is_active = 1 AND end_date >= ?
    ORDER BY discount_value DESC
");
$promotions_stmt->execute([$current_date]);
$all_promotions = $promotions_stmt->fetchAll();

// Получаем ID товаров по акциям для фильтрации
$product_ids_by_promotion = [];
if (!empty($selected_promotions)) {
    foreach ($selected_promotions as $promo_id) {
        $stmt = $pdo->prepare("SELECT product_ids FROM promotions WHERE id = ?");
        $stmt->execute([$promo_id]);
        $result = $stmt->fetch();
        if ($result && !empty($result['product_ids'])) {
            $ids = array_filter(explode(',', $result['product_ids']), 'is_numeric');
            $product_ids_by_promotion = array_merge($product_ids_by_promotion, $ids);
        }
    }
    $product_ids_by_promotion = array_unique($product_ids_by_promotion);
}

// Получаем ID всех товаров по акциям (для фильтра "Только товары со скидкой")
$all_promotion_product_ids = [];
if ($has_discount) {
    $stmt = $pdo->prepare("SELECT GROUP_CONCAT(product_ids) as all_ids FROM promotions WHERE is_active = 1 AND end_date >= ?");
    $stmt->execute([$current_date]);
    $result = $stmt->fetch();
    if ($result && !empty($result['all_ids'])) {
        $all_ids = explode(',', $result['all_ids']);
        $all_promotion_product_ids = array_filter($all_ids, 'is_numeric');
        $all_promotion_product_ids = array_unique($all_promotion_product_ids);
    }
}

// Базовый запрос товаров
$sql = "SELECT p.* FROM products p WHERE 1=1";
$params = [];

// Фильтр по категориям
if (!empty($selected_categories)) {
    $placeholders = str_repeat('?,', count($selected_categories) - 1) . '?';
    $sql .= " AND p.category IN ($placeholders)";
    $params = array_merge($params, $selected_categories);
}

// Фильтр по акциям и "только со скидкой" - объединяем через OR, если выбраны оба
$promotion_product_ids = [];
if (!empty($product_ids_by_promotion)) {
    $promotion_product_ids = array_merge($promotion_product_ids, $product_ids_by_promotion);
}
if ($has_discount && !empty($all_promotion_product_ids)) {
    $promotion_product_ids = array_merge($promotion_product_ids, $all_promotion_product_ids);
}
$promotion_product_ids = array_unique($promotion_product_ids);

// Применяем фильтр по акциям
if (!empty($promotion_product_ids)) {
    $placeholders = str_repeat('?,', count($promotion_product_ids) - 1) . '?';
    $sql .= " AND p.id IN ($placeholders)";
    $params = array_merge($params, $promotion_product_ids);
}

// Фильтр по цене
if ($min_price !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

// Сортировка: сначала товары по акциям, потом остальные
// Для этого нужно получить ID товаров по акциям
$promotion_product_ids_stmt = $pdo->prepare("
    SELECT GROUP_CONCAT(DISTINCT product_ids) as all_promo_ids 
    FROM promotions 
    WHERE is_active = 1 AND end_date >= ?
");
$promotion_product_ids_stmt->execute([$current_date]);
$promo_result = $promotion_product_ids_stmt->fetch();

$promo_product_ids = [];
if ($promo_result && !empty($promo_result['all_promo_ids'])) {
    $all_ids = explode(',', $promo_result['all_promo_ids']);
    $promo_product_ids = array_filter($all_ids, 'is_numeric');
    $promo_product_ids = array_unique($promo_product_ids);
}

if (!empty($promo_product_ids)) {
    $promo_ids_str = implode(',', $promo_product_ids);
    $sql .= " ORDER BY FIELD(p.id, $promo_ids_str) DESC, p.id DESC";
} else {
    $sql .= " ORDER BY p.id DESC";
}

// Выполняем запрос
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Теперь получаем информацию об акциях для каждого товара
$product_promotions = [];
if (!empty($products)) {
    $product_ids = array_column($products, 'id');
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $promo_info_stmt = $pdo->prepare("
        SELECT pr.id as promotion_id, pr.title, pr.discount_value, pr.discount_type, 
               pr.product_ids, p.id as product_id
        FROM promotions pr
        CROSS JOIN products p
        WHERE pr.is_active = 1 AND pr.end_date >= ? 
        AND p.id IN ($placeholders)
        AND FIND_IN_SET(p.id, pr.product_ids)
    ");
    
    $promo_params = array_merge([$current_date], $product_ids);
    $promo_info_stmt->execute($promo_params);
    $promo_info_results = $promo_info_stmt->fetchAll();
    
    // Группируем акции по товарам
    foreach ($promo_info_results as $row) {
        $product_id = $row['product_id'];
        if (!isset($product_promotions[$product_id])) {
            $product_promotions[$product_id] = [];
        }
        $product_promotions[$product_id][] = [
            'id' => $row['promotion_id'],
            'title' => $row['title'],
            'discount_value' => (float)$row['discount_value'],
            'discount_type' => $row['discount_type']
        ];
    }
}

// Рассчитываем цены со скидкой для каждого товара
foreach ($products as &$product) {
    $product_id = $product['id'];
    
    if (isset($product_promotions[$product_id]) && !empty($product_promotions[$product_id])) {
        $product['promotion_info'] = $product_promotions[$product_id];
        
        $max_discount = 0;
        $final_price = $product['price'];
        
        foreach ($product_promotions[$product_id] as $promo) {
            $discount_value = $promo['discount_value'];
            $discount_type = $promo['discount_type'];
            
            if ($discount_type === 'percentage') {
                $discounted_price = $product['price'] * (1 - $discount_value / 100);
                $discount_amount = $product['price'] - $discounted_price;
            } elseif ($discount_type === 'fixed') {
                $discounted_price = max(0, $product['price'] - $discount_value);
                $discount_amount = $discount_value;
            } else {
                $discounted_price = $product['price'];
                $discount_amount = 0;
            }
            
            if ($discounted_price < $final_price) {
                $final_price = $discounted_price;
                $max_discount = $discount_amount;
            }
        }
        
        $product['discounted_price'] = $final_price;
        $product['has_discount'] = $final_price < $product['price'];
        $product['discount_percentage'] = $max_discount > 0 
            ? round(($max_discount / $product['price']) * 100) 
            : 0;
    } else {
        $product['promotion_info'] = [];
        $product['discounted_price'] = $product['price'];
        $product['has_discount'] = false;
        $product['discount_percentage'] = 0;
    }
}
unset($product);

// Получим уникальные категории
$categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$all_categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/catalog.css">
    <style>
        /* Стили для уведомления */
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
        
        .cart-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .cart-notification.hiding {
            animation: slideOut 0.3s ease-out forwards;
        }
        
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
            margin-left: 5px;
            font-weight: bold;
        }
        
        /* Стили для акций в карточках товаров */
        .product-badges {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .discount-badge {
            background: linear-gradient(135deg, #f25081, #ff6b9d);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(242, 80, 129, 0.3);
            white-space: nowrap;
        }
        
        .promotion-badge {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(76, 175, 80, 0.3);
            white-space: nowrap;
        }
        
        .product-card {
            position: relative;
        }
        
        .product-image {
            position: relative;
        }
        
        .price-container {
            margin: 10px 0;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
            margin-right: 8px;
        }
        
        .discounted-price {
            color: #f25081;
            font-weight: bold;
            font-size: 18px;
        }
        
        .regular-price {
            color: #333;
            font-weight: bold;
            font-size: 18px;
        }
        
        .promotion-info {
            background: #f9f9f9;
            border-radius: 5px;
            padding: 8px;
            margin-top: 8px;
            font-size: 12px;
            color: #666;
        }
        
        .promotion-info a {
            color: #f25081;
            text-decoration: none;
            font-weight: bold;
        }
        
        .promotion-info a:hover {
            text-decoration: underline;
        }
        
        /* Стили для фильтра акций */
        .filter-promotions {
            margin-bottom: 20px;
        }
        
        .filter-promotions label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            cursor: pointer;
        }
        
        .promotion-discount {
            background: #f25081;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: auto;
        }
        
        .filter-has-discount {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .filter-has-discount label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            cursor: pointer;
        }
        
        .filter-has-discount input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        .selected-filters {
            background: #f5f5f5;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .selected-filters-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .selected-filter-item {
            display: inline-block;
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 3px;
            margin: 2px;
            font-size: 12px;
        }
        
        .clear-all-filters {
            color: #f25081;
            text-decoration: none;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .clear-all-filters:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <?php include './header.php'; ?>

    <div class="container">

        <div class="page-header">
            <h1>Каталог товаров</h1>
            <p>Все товары в одном месте</p>
        </div>

        <!-- Показать выбранные фильтры -->
        <?php if (!empty($selected_promotions) || $has_discount || !empty($selected_categories) || $min_price !== null || $max_price !== null): ?>
            <div class="selected-filters">
                <div class="selected-filters-title">Выбранные фильтры:</div>
                
                <?php if (!empty($selected_promotions)): ?>
                    <?php foreach ($all_promotions as $promo): ?>
                        <?php if (in_array($promo['id'], $selected_promotions)): ?>
                            <span class="selected-filter-item">
                                Акция: <?= htmlspecialchars($promo['title']) ?>
                                <?php if ($promo['discount_type'] === 'percentage'): ?>
                                    (-<?= $promo['discount_value'] ?>%)
                                <?php elseif ($promo['discount_type'] === 'fixed'): ?>
                                    (-<?= $promo['discount_value'] ?> ₽)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($has_discount): ?>
                    <span class="selected-filter-item">Только со скидкой</span>
                <?php endif; ?>
                
                <?php if (!empty($selected_categories)): ?>
                    <?php foreach ($selected_categories as $cat): ?>
                        <span class="selected-filter-item">Категория: <?= htmlspecialchars($cat) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($min_price !== null): ?>
                    <span class="selected-filter-item">Цена от: <?= number_format($min_price, 0, ',', ' ') ?> ₽</span>
                <?php endif; ?>
                
                <?php if ($max_price !== null): ?>
                    <span class="selected-filter-item">Цена до: <?= number_format($max_price, 0, ',', ' ') ?> ₽</span>
                <?php endif; ?>
                
                <a href="catalog.php" class="clear-all-filters">Сбросить все фильтры</a>
            </div>
        <?php endif; ?>

        <div class="catalog-layout">

            <!-- ФИЛЬТР -->
            <aside class="filter-sidebar">
                <h3>Фильтры</h3>

                <form method="GET" id="filter-form">
                    <!-- Сохраняем все остальные GET-параметры -->
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!in_array($key, ['category', 'promotion', 'min_price', 'max_price', 'has_discount'])): ?>
                            <?php if (!is_array($value)): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <!-- Акции -->
                    <?php if (!empty($all_promotions)): ?>
                        <div class="filter-section filter-promotions">
                            <h4>Акции:</h4>
                            <div class="filter-promotion-list">
                                <?php foreach ($all_promotions as $promotion): ?>
                                    <label>
                                        <input type="checkbox" name="promotion[]" value="<?= $promotion['id'] ?>"
                                            <?= in_array($promotion['id'], $selected_promotions) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($promotion['title']) ?></span>
                                        <?php if ($promotion['discount_type'] === 'percentage'): ?>
                                            <span class="promotion-discount">-<?= $promotion['discount_value'] ?>%</span>
                                        <?php elseif ($promotion['discount_type'] === 'fixed'): ?>
                                            <span class="promotion-discount">-<?= $promotion['discount_value'] ?> ₽</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Только товары со скидкой -->
                    <div class="filter-section filter-has-discount">
                        <label>
                            <input type="checkbox" name="has_discount" value="1"
                                <?= $has_discount ? 'checked' : '' ?>>
                            Только товары со скидкой
                        </label>
                    </div>

                    <!-- Категории -->
                    <div class="filter-section">
                        <h4>Категория:</h4>
                        <div class="filter-category">
                            <?php foreach ($all_categories as $cat): ?>
                                <label>
                                    <input type="checkbox" name="category[]" value="<?= htmlspecialchars($cat) ?>"
                                        <?= in_array($cat, $selected_categories) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Цена -->
                    <div class="filter-section">
                        <h4>Цена:</h4>
                        <div class="price-filter">
                            <input type="number" name="min_price" placeholder="От" value="<?= htmlspecialchars($min_price ?? '') ?>">
                            <span>—</span>
                            <input type="number" name="max_price" placeholder="До" value="<?= htmlspecialchars($max_price ?? '') ?>">
                        </div>
                        <button type="submit" class="apply-btn">Применить</button>
                        <?php if (!empty($_GET)): ?>
                            <a href="catalog.php" class="reset-link">Сбросить</a>
                        <?php endif; ?>
                    </div>
                </form>
            </aside>

            <!-- Центральный спейсер -->
            <div class="spacer"></div>

            <!-- ТОВАРЫ -->
            <main class="products-main">
                <div class="products-info">
                    <div class="products-count">
                        Найдено товаров: <?= count($products) ?>
                    </div>
                    <?php if (!empty($selected_promotions) || $has_discount): ?>
                        <div class="active-promotions-info">
                            <i class="fas fa-fire" style="color: #f25081;"></i>
                            <?php if ($has_discount): ?>
                                Показаны товары со скидками
                                <?php if (!empty($selected_promotions)): ?>
                                    по выбранным акциям
                                <?php endif; ?>
                            <?php elseif (!empty($selected_promotions)): ?>
                                Показаны товары по выбранным акциям
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <i class="fas fa-search" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                            <h3>Товары не найдены</h3>
                            <p>Попробуйте изменить параметры фильтрации</p>
                            <a href="catalog.php" class="btn-promotion" style="padding: 10px 20px; font-size: 14px;">
                                <i class="fas fa-times"></i> Сбросить фильтры
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="no-image">Нет изображения</div>
                                    <?php endif; ?>
                                    
                                    <!-- Бейджи акций и скидок -->
                                    <div class="product-badges">
                                        <?php if ($product['has_discount']): ?>
                                            <div class="discount-badge">-<?= $product['discount_percentage'] ?>%</div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($product['promotion_info'])): ?>
                                            <?php foreach ($product['promotion_info'] as $promo): ?>
                                                <div class="promotion-badge">
                                                    <?php 
                                                    if ($promo['discount_type'] === 'percentage') {
                                                        echo "-{$promo['discount_value']}%";
                                                    } elseif ($promo['discount_type'] === 'fixed') {
                                                        echo "-{$promo['discount_value']} ₽";
                                                    } else {
                                                        echo "Акция";
                                                    }
                                                    ?>
                                                </div>
                                                <?php break; // Показываем только первую акцию на бейдже ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <?php if (!empty($product['category'])): ?>
                                        <p class="product-category"><?= htmlspecialchars($product['category']) ?></p>
                                    <?php endif; ?>
                                    <div class="rating">★★★★☆ <span>(12)</span></div>
                                    
                                    <div class="price-container">
                                        <?php if ($product['has_discount']): ?>
                                            <span class="original-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
                                            <span class="discounted-price"><?= number_format($product['discounted_price'], 0, ',', ' ') ?> ₽</span>
                                        <?php else: ?>
                                            <span class="regular-price"><?= number_format($product['price'], 0, ',', ' ') ?> ₽</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Информация об акциях -->
                                    <?php if (!empty($product['promotion_info'])): ?>
                                        <div class="promotion-info">
                                            <i class="fas fa-gift" style="color: #f25081;"></i>
                                            <?php 
                                            $promo_count = count($product['promotion_info']);
                                            if ($promo_count == 1) {
                                                $promo = $product['promotion_info'][0];
                                                echo "В акции: <a href='promotions.php#promotion-{$promo['id']}'>{$promo['title']}</a>";
                                            } else {
                                                echo "Участвует в {$promo_count} акциях";
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <button class="add-to-cart" data-id="<?= $product['id'] ?>">
                                        <i class="fas fa-shopping-cart"></i> В корзину
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>

        </div>

    </div>

    <?php include './footer.php'; ?>

    <script>
        // Функция показа уведомления
        function showNotification(message) {
            // Удаляем старое уведомление, если есть
            const oldNotification = document.querySelector('.cart-notification');
            if (oldNotification) {
                oldNotification.remove();
            }
            
            // Создаем новое уведомление
            const notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <svg style="width: 20px; height: 20px; fill: white;" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Удаляем уведомление через 3 секунды
            setTimeout(() => {
                notification.classList.add('hiding');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Функция обновления счетчика корзины
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
            
            // Также обновляем количество в иконке корзины в header.php
            const cartLinks = document.querySelectorAll('a[href*="cart.php"]');
            cartLinks.forEach(link => {
                let countElement = link.querySelector('.cart-count');
                if (!countElement && count > 0) {
                    countElement = document.createElement('span');
                    countElement.className = 'cart-count';
                    link.appendChild(countElement);
                }
                if (countElement) {
                    if (count > 0) {
                        countElement.textContent = count;
                        countElement.style.display = 'inline-flex';
                    } else {
                        countElement.style.display = 'none';
                    }
                }
            });
        }

        // Обработчик кликов по кнопкам "В корзину"
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                if (!productId) {
                    console.error('Product ID not found');
                    return;
                }
                
                // Показываем состояние загрузки
                const originalText = this.textContent;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Добавляем...';
                this.disabled = true;
                
                // Отправляем запрос
                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        showNotification('Товар добавлен в корзину!');
                        updateCartCount(data.count);
                        
                        // Визуальная обратная связь
                        this.style.backgroundColor = '#4CAF50';
                        this.innerHTML = '<i class="fas fa-check"></i> ✓ Добавлено';
                        setTimeout(() => {
                            this.innerHTML = '<i class="fas fa-shopping-cart"></i> В корзину';
                            this.style.backgroundColor = '';
                            this.disabled = false;
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Ошибка при добавлении товара');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });

        // Инициализация счетчика корзины при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Запрашиваем текущее количество товаров в корзине
            fetch('cart.php?get_count=true')
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        updateCartCount(data.count);
                    }
                })
                .catch(error => {
                    console.error('Error loading cart count:', error);
                });
            
            // Добавляем возможность выбора акций по клику на текст
            document.querySelectorAll('.filter-promotion-list label').forEach(label => {
                label.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                    }
                });
            });
            
            // Добавляем возможность выбора "Только товары со скидкой" по клику на текст
            const hasDiscountLabel = document.querySelector('.filter-has-discount label');
            if (hasDiscountLabel) {
                hasDiscountLabel.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                    }
                });
            }
            
            // Автоматическая отправка формы при изменении фильтров (кроме полей цены)
            const filterForm = document.getElementById('filter-form');
            if (filterForm) {
                // Обработчики для чекбоксов
                filterForm.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // Не отправляем сразу, даем пользователю возможность выбрать несколько
                        // Отправка будет по кнопке "Применить"
                    });
                });
                
                // Обработчики для полей категорий
                filterForm.querySelectorAll('input[name="category[]"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // Можно добавить автоотправку или оставить ручную через кнопку
                    });
                });
                
                // Автоотправка при изменении цены (с небольшой задержкой)
                let priceTimeout;
                filterForm.querySelectorAll('input[name="min_price"], input[name="max_price"]').forEach(input => {
                    input.addEventListener('input', function() {
                        clearTimeout(priceTimeout);
                        priceTimeout = setTimeout(() => {
                            // Можно добавить автоотправку или оставить ручную
                        }, 1000);
                    });
                });
            }
        });
    </script>

</body>
</html>