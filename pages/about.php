<?php
session_start();
$title = "О нас — Декор для дома";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/about.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Баннер -->
    <section class="about-hero">
        <h1>О нас</h1>
        <p>Мы создаем уникальные интерьеры вместе с вами уже более 7 лет</p>
    </section>

    <!-- Миссия -->
    <section class="about-mission">
        <div class="container">
            <h2>Наша миссия</h2>
            <div class="mission-content">
                <div class="mission-text">
                    <p>Мы верим, что каждый дом заслуживает быть особенным. Наша миссия — помочь вам создать пространство, которое отражает вашу индивидуальность и приносит радость каждый день.</p>
                    <p>Компания «Декор для дома» была основана в 2018 году группой энтузиастов, которые объединили свою страсть к дизайну интерьера и стремление делать качественные товары доступными для всех.</p>
                    <p>За годы работы мы помогли тысячам семей создать дом их мечты, предлагая широкий ассортимент товаров от ведущих производителей и обеспечивая превосходное обслуживание на каждом этапе.</p>
                </div>
                <div class="mission-image">
                    <img src="../assets/img/team.jpg" alt="Наша команда">
                </div>
            </div>
        </div>
    </section>

    <!-- Статистика — ТОЧНО КАК НА РЕФЕРЕНСЕ -->
    <section class="about-stats">
        <div class="container">
            <div class="stat-item">
                <h3>5000+</h3>
                <p>Товаров в каталоге</p>
            </div>
            <div class="stat-item">
                <h3>15000+</h3>
                <p>Довольных клиентов</p>
            </div>
            <div class="stat-item">
                <h3>7 лет</h3>
                <p>На рынке</p>
            </div>
            <div class="stat-item">
                <h3>98%</h3>
                <p>Положительных отзывов</p>
            </div>
        </div>
    </section>

    <!-- Склад -->
    <section class="warehouse-section">
        <h2>Наш склад</h2>
        <p>Все товары хранятся на собственном складе площадью более 1 500 м², что позволяет нам обеспечивать быструю комплектацию и отправку заказов в течение 24 часов.</p>
        <div class="warehouse-image">
            <img src="../assets/img/warehouse.jpg" alt="Склад компании Декор для дома">
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>