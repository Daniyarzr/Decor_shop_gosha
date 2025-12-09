<?php 
session_start();
// Подключаем конфиг
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Декор для дома</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.ico">
    <style>
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
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }
        
        .logo-image {
            height: 50px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            white-space: nowrap;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="logo-container">
                <img src="<?= BASE_URL_RELATIVE ?>assets/img/logo-icon.svg" alt="Логотип" class="logo-image">
                <div class="logo-text">Декор для дома</div>
            </div>
            <nav>
                <ul>
                    <li><a href="<?= BASE_URL_RELATIVE ?>index.php">Главная</a></li>
                    <li><a href="<?= BASE_URL_RELATIVE ?>pages/about.php">О нас</a></li>
                    <li><a href="<?= BASE_URL_RELATIVE ?>pages/catalog.php">Каталог</a></li>
                    <li><a href="<?= BASE_URL_RELATIVE ?>pages/reviews.php">Отзывы</a></li>
                    <li><a href="<?= BASE_URL_RELATIVE ?>pages/contacts.php">Контакты</a></li>
                    <li><a href="<?= BASE_URL_RELATIVE ?>pages/promotions.php">Акции</a></li>
                    <li>
                        <a href="<?= BASE_URL_RELATIVE ?>pages/cart.php">Корзина
                            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="cart-count"><?= array_sum($_SESSION['cart']) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?= BASE_URL_RELATIVE ?>pages/profile.php">Личный кабинет</a></li>
                        <li><a href="<?= BASE_URL_RELATIVE ?>../logout.php">Выход</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL_RELATIVE ?>../register.php">Регистрация</a></li>
                        <li><a href="<?= BASE_URL_RELATIVE ?>../login.php">Вход</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-icons">
                <a href="#" class="icon-search">🔍</a>
                <a href="<?= BASE_URL_RELATIVE ?>pages/cart.php" class="icon-cart">🛒
                    <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="cart-count"><?= array_sum($_SESSION['cart']) ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>
</body>
</html>