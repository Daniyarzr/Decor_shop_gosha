<?php
session_start();
require_once '../config.php';

// УПРОЩЕННЫЙ ЗАПРОС - показываем все активные акции
$current_date = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT * FROM promotions 
    WHERE is_active = 1 
    ORDER BY 
        CASE 
            WHEN end_date >= ? THEN 1  -- Активные сейчас
            ELSE 2                     -- Будущие или завершенные
        END,
        start_date DESC
");
$stmt->execute([$current_date]);
$promotions = $stmt->fetchAll();

// Получаем товары по акции
$promotion_products = [];
foreach ($promotions as $promotion) {
    if (!empty($promotion['product_ids'])) {
        $product_ids = explode(',', $promotion['product_ids']);
        $product_ids = array_filter($product_ids, 'is_numeric');
        
        if (!empty($product_ids)) {
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT id, name, price, image, category 
                FROM products 
                WHERE id IN ($placeholders) 
                LIMIT 4
            ");
            $stmt->execute($product_ids);
            $promotion_products[$promotion['id']] = $stmt->fetchAll();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Акции — Декор для дома</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Основные стили для страницы акций */
        .promotions-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f25081;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Стили для карточек акций */
        .promotions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .promotion-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid #f25081;
        }

        .promotion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(242, 80, 129, 0.15);
        }

        .promotion-header {
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .promotion-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f25081;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            z-index: 2;
        }

        .promotion-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .promotion-card:hover .promotion-image {
            transform: scale(1.05);
        }

        .promotion-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            background: linear-gradient(135deg, #f25081, #ff8ab5);
        }

        .promotion-content {
            padding: 25px;
        }

        .promotion-title {
            font-size: 1.4rem;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .promotion-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
            min-height: 60px;
        }

        .promotion-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .discount-badge {
            background: linear-gradient(135deg, #f25081, #ff6b9d);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.2rem;
            box-shadow: 0 3px 10px rgba(242, 80, 129, 0.2);
        }

        .promotion-dates {
            color: #888;
            font-size: 0.9rem;
            text-align: right;
        }

        .promotion-dates span {
            display: block;
        }

        /* Стили для товаров по акции */
        .promotion-products {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #eee;
        }

        .products-title {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .products-title i {
            color: #f25081;
        }

        .products-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
        }

        .product-card-small {
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .product-card-small:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .product-image-small {
            width: 100%;
            height: 100px;
            object-fit: cover;
            background: #f5f5f5;
        }

        .product-info-small {
            padding: 10px;
        }

        .product-name-small {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price-small {
            font-size: 0.95rem;
            color: #f25081;
            font-weight: bold;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        /* Баннер акции */
        .promotion-banner {
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .promotion-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .promotion-banner-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .promotion-banner h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .promotion-banner p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        .banner-countdown {
            background: rgba(242, 80, 129, 0.8);
            padding: 20px;
            border-radius: 10px;
            display: inline-block;
            backdrop-filter: blur(5px);
        }

        .countdown-title {
            font-size: 1rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .countdown-timer {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .countdown-item {
            text-align: center;
        }

        .countdown-value {
            font-size: 2.2rem;
            font-weight: bold;
            display: block;
            line-height: 1;
        }

        .countdown-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Блок информации о доставке */
        .delivery-info {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .delivery-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .delivery-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f25081;
            font-size: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .delivery-text h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .delivery-text p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        /* Кнопки */
        .view-all-promotions {
            text-align: center;
            margin-top: 40px;
        }

        .btn-promotion {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f25081, #d93a6a);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-promotion:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(242, 80, 129, 0.3);
        }

        .btn-view-more {
            background: linear-gradient(135deg, #4CAF50, #45a049);
        }

        /* Отображение при отсутствии акций */
        .no-promotions {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .no-promotions i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }

        .no-promotions h3 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 10px;
        }

        /* Статус акции */
        .promotion-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 10px;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-upcoming {
            background: #cce5ff;
            color: #004085;
        }

        .status-ended {
            background: #f8d7da;
            color: #721c24;
            opacity: 0.7;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .promotions-grid {
                grid-template-columns: 1fr;
            }

            .promotion-banner {
                padding: 25px 15px;
                min-height: 250px;
            }

            .promotion-banner h2 {
                font-size: 1.8rem;
            }

            .promotion-banner p {
                font-size: 1.1rem;
            }

            .countdown-timer {
                gap: 10px;
            }

            .countdown-value {
                font-size: 1.6rem;
            }

            .delivery-info {
                grid-template-columns: 1fr;
            }
        }

        /* Анимации */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .promotion-card {
            animation: fadeInUp 0.5s ease-out;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="promotions-container">
        <div class="page-header">
            <h1><i class="fas fa-percentage"></i> Акции и специальные предложения</h1>
            <p>Не упустите возможность приобрести качественные товары для дома со скидками до 50%! Акции обновляются каждую неделю.</p>
        </div>

        <?php if (empty($promotions)): ?>
            <div class="no-promotions">
                <i class="fas fa-tag"></i>
                <h3>Сейчас нет активных акций</h3>
                <p>Но скоро появятся новые интересные предложения! Следите за обновлениями.</p>
                <a href="catalog.php" class="btn-promotion">
                    <i class="fas fa-arrow-right"></i> Перейти в каталог
                </a>
            </div>
        <?php else: ?>
            <!-- Баннер главной акции -->
            <?php 
            $main_promotion = $promotions[0];
            // Определяем статус акции
            $start_date = new DateTime($main_promotion['start_date']);
            $end_date = new DateTime($main_promotion['end_date']);
            $current_date = new DateTime();
            $status = '';
            
            if ($current_date < $start_date) {
                $status = 'upcoming';
            } elseif ($current_date > $end_date) {
                $status = 'ended';
            } else {
                $status = 'active';
            }
            ?>
            
            <?php if ($status == 'active'): ?>
                <div class="promotion-banner" 
                     style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                     <?php 
                     if (!empty($main_promotion['image']) && file_exists("../assets/img/promotions/" . $main_promotion['image'])): 
                         echo "url('../assets/img/promotions/" . htmlspecialchars($main_promotion['image']) . "')";
                     else: 
                         echo "linear-gradient(135deg, #f25081, #ff8ab5)";
                     endif; 
                     ?>; background-size: cover; background-position: center;">
                    
                    <div class="promotion-banner-content">
                        <h2><?= htmlspecialchars($main_promotion['title']) ?></h2>
                        <p><?= htmlspecialchars($main_promotion['description']) ?></p>
                        
                        <?php 
                        // Рассчитываем оставшееся время до конца акции
                        $days_left = $current_date->diff($end_date)->days;
                        ?>
                        
                        <?php if ($days_left > 0): ?>
                            <div class="banner-countdown">
                                <div class="countdown-title">До конца акции осталось:</div>
                                <div class="countdown-timer">
                                    <div class="countdown-item">
                                        <span class="countdown-value"><?= $days_left ?></span>
                                        <span class="countdown-label">дней</span>
                                    </div>
                                    <div class="countdown-item">
                                        <span class="countdown-value"><?= $current_date->diff($end_date)->h ?></span>
                                        <span class="countdown-label">часов</span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 30px;">
                            <a href="#promotion-<?= $main_promotion['id'] ?>" class="btn-promotion btn-view-more">
                                <i class="fas fa-gift"></i> Подробнее об акции
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Сетка акций -->
            <div class="promotions-grid">
                <?php foreach ($promotions as $promotion): ?>
                    <?php
                    // Определяем статус для каждой акции
                    $start_date = new DateTime($promotion['start_date']);
                    $end_date = new DateTime($promotion['end_date']);
                    $current_date = new DateTime();
                    
                    if ($current_date < $start_date) {
                        $status = 'upcoming';
                        $status_text = 'Скоро';
                    } elseif ($current_date > $end_date) {
                        $status = 'ended';
                        $status_text = 'Завершена';
                    } else {
                        $status = 'active';
                        $status_text = 'Активна';
                    }
                    
                    // Форматируем скидку
                    $discount_text = '';
                    if ($promotion['discount_type'] === 'percentage') {
                        $discount_text = "-{$promotion['discount_value']}%";
                    } elseif ($promotion['discount_type'] === 'fixed') {
                        $discount_text = "-{$promotion['discount_value']} ₽";
                    } else {
                        $discount_text = "Спеццена";
                    }
                    ?>
                    
                    <div class="promotion-card" id="promotion-<?= $promotion['id'] ?>">
                        <div class="promotion-header">
                            <span class="promotion-badge">Акция 
                                <span class="promotion-status status-<?= $status ?>"><?= $status_text ?></span>
                            </span>
                            
                            <?php if (!empty($promotion['image']) && file_exists("../assets/img/promotions/" . $promotion['image'])): ?>
                                <img src="../assets/img/promotions/<?= htmlspecialchars($promotion['image']) ?>" 
                                     alt="<?= htmlspecialchars($promotion['title']) ?>" 
                                     class="promotion-image">
                            <?php else: ?>
                                <!-- Используем первое изображение товара или иконку -->
                                <?php if (isset($promotion_products[$promotion['id']]) && !empty($promotion_products[$promotion['id']][0]['image']) && file_exists("../assets/img/" . $promotion_products[$promotion['id']][0]['image'])): ?>
                                    <img src="../assets/img/<?= htmlspecialchars($promotion_products[$promotion['id']][0]['image']) ?>" 
                                         alt="<?= htmlspecialchars($promotion['title']) ?>" 
                                         class="promotion-image">
                                <?php else: ?>
                                    <div class="promotion-image-placeholder">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="promotion-content">
                            <h3 class="promotion-title"><?= htmlspecialchars($promotion['title']) ?></h3>
                            <p class="promotion-description"><?= htmlspecialchars($promotion['description']) ?></p>
                            
                            <div class="promotion-details">
                                <div class="discount-badge"><?= $discount_text ?></div>
                                <div class="promotion-dates">
                                    <span><i class="far fa-calendar-alt"></i> Начало: <?= date('d.m.Y', strtotime($promotion['start_date'])) ?></span>
                                    <span><i class="far fa-calendar-times"></i> Конец: <?= date('d.m.Y', strtotime($promotion['end_date'])) ?></span>
                                </div>
                            </div>
                            
                            <!-- Товары по акции -->
                            <?php if (isset($promotion_products[$promotion['id']]) && !empty($promotion_products[$promotion['id']])): ?>
                                <div class="promotion-products">
                                    <div class="products-title">
                                        <i class="fas fa-gift"></i>
                                        <span>Товары по акции:</span>
                                    </div>
                                    <div class="products-grid-small">
                                        <?php foreach ($promotion_products[$promotion['id']] as $product): ?>
                                            <a href="catalog.php?product_id=<?= $product['id'] ?>" class="product-card-small">
                                                <?php if (!empty($product['image']) && file_exists("../assets/img/" . $product['image'])): ?>
                                                    <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" 
                                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                                         class="product-image-small">
                                                <?php else: ?>
                                                    <div style="height: 100px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="product-info-small">
                                                    <div class="product-name-small"><?= htmlspecialchars($product['name']) ?></div>
                                                    <?php
                                                    $original_price = $product['price'];
                                                    $discounted_price = $original_price;
                                                    if ($promotion['discount_type'] === 'percentage') {
                                                        $discounted_price = $original_price * (1 - $promotion['discount_value'] / 100);
                                                    } elseif ($promotion['discount_type'] === 'fixed') {
                                                        $discounted_price = max(0, $original_price - $promotion['discount_value']);
                                                    }
                                                    ?>
                                                    <div class="product-price-small">
                                                        <?php if ($original_price > $discounted_price): ?>
                                                            <span class="original-price"><?= number_format($original_price, 0, ',', ' ') ?> ₽</span>
                                                        <?php endif; ?>
                                                        <?= number_format($discounted_price, 0, ',', ' ') ?> ₽
                                                    </div>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div style="text-align: center; margin-top: 20px;">
                                <?php if (!empty($promotion['category_filter'])): ?>
                                    <a href="catalog.php?category=<?= urlencode($promotion['category_filter']) ?>" class="btn-promotion" style="padding: 8px 20px; font-size: 0.9rem;">
                                        <i class="fas fa-shopping-cart"></i> Смотреть товары
                                    </a>
                                <?php elseif (!empty($promotion['product_ids'])): ?>
                                    <a href="catalog.php" class="btn-promotion" style="padding: 8px 20px; font-size: 0.9rem;">
                                        <i class="fas fa-shopping-cart"></i> Все товары акции
                                    </a>
                                <?php else: ?>
                                    <a href="catalog.php" class="btn-promotion" style="padding: 8px 20px; font-size: 0.9rem;">
                                        <i class="fas fa-shopping-cart"></i> В каталог
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Информация о доставке -->
            <div class="delivery-info">
                <div class="delivery-item">
                    <div class="delivery-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="delivery-text">
                        <h4>Бесплатная доставка</h4>
                        <p>При заказе от 5000 ₽ по всей России</p>
                    </div>
                </div>
                <div class="delivery-item">
                    <div class="delivery-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="delivery-text">
                        <h4>Подарки к покупкам</h4>
                        <p>Дарим подарки к каждому заказу</p>
                    </div>
                </div>
                <div class="delivery-item">
                    <div class="delivery-icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <div class="delivery-text">
                        <h4>Легкий возврат</h4>
                        <p>Возврат товара в течение 14 дней</p>
                    </div>
                </div>
            </div>
            
            <!-- Кнопка для перехода к другим акциям -->
            <div class="view-all-promotions">
                <a href="archive.php" class="btn-promotion">
                    <i class="fas fa-history"></i> Архив завершенных акций
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Функция для динамического обновления таймера
        function updateCountdown() {
            const countdownElements = document.querySelectorAll('.banner-countdown');
            
            countdownElements.forEach(element => {
                const countdown = element.querySelector('.countdown-timer');
                if (countdown) {
                    // Обновляем значения таймера
                    const daysElement = countdown.querySelector('.countdown-value:first-child');
                    const hoursElement = countdown.querySelector('.countdown-value:last-child');
                    
                    if (daysElement && hoursElement) {
                        let days = parseInt(daysElement.textContent);
                        let hours = parseInt(hoursElement.textContent);
                        
                        if (hours > 0) {
                            hours--;
                        } else if (days > 0) {
                            days--;
                            hours = 23;
                        }
                        
                        daysElement.textContent = days;
                        hoursElement.textContent = hours;
                    }
                }
            });
        }
        
        // Запускаем обновление таймера каждый час (в миллисекундах)
        setInterval(updateCountdown, 3600000);
        
        // Анимация при скролле
        document.addEventListener('DOMContentLoaded', function() {
            const promotionCards = document.querySelectorAll('.promotion-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });
            
            promotionCards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
            
            // Плавная прокрутка к акции при клике на кнопку в баннере
            const bannerButtons = document.querySelectorAll('.btn-view-more');
            bannerButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>
</body>
</html>