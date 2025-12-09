<?php
session_start();
require 'config.php'; // Этот файл создает переменную $pdo

// Проверяем подключение
if (!isset($pdo)) {
    die("Ошибка: Нет подключения к базе данных");
}


// Если у вас уже есть подключение $conn

    
 
// Подключаем конфигурацию
require_once 'config.php';

// Получаем все активные слайды из БД
try {
    $stmt = $pdo->query("SELECT * FROM promo_sliders WHERE is_active = 1 ORDER BY sort_order ASC");
    $slides = $stmt->fetchAll();
} catch (PDOException $e) {
    // Если ошибка - используем пустой массив
    $slides = [];
    error_log("Ошибка при загрузке слайдов: " . $e->getMessage());
}



?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Декор для дома — стиль и уют</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>


    <?php include 'pages/header.php'; ?>

        <!-- Promo Banner Slider — как на референсе -->


    <section class="promo-banner-slider">
        <?php if (!empty($slides)): ?>
            <?php foreach ($slides as $index => $slide): ?>
                <div class="promo-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                    <div class="promo-overlay"></div>
                    <div class="promo-content">
                        <span class="promo-tag <?php echo htmlspecialchars($slide['tag_class']); ?>">
                            <?php echo htmlspecialchars($slide['tag']); ?>
                        </span>
                        <h2><?php echo htmlspecialchars($slide['title']); ?></h2>
                        <p><?php echo htmlspecialchars($slide['description']); ?></p>
                        <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" class="btn-promo">
                            <?php echo htmlspecialchars($slide['button_text']); ?>
                        </a>
                    </div>
                    <div class="promo-image-placeholder">
                        <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($slide['title']); ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Если нет слайдов в БД, показываем заглушку -->
            <div class="promo-slide active" data-index="0">
                <div class="promo-overlay"></div>
                <div class="promo-content">
                    <span class="promo-tag new">НОВИНКА</span>
                    <h2>Добро пожаловать в наш магазин!</h2>
                    <p>Скоро здесь появятся новые акции и предложения</p>
                    <a href="/catalog.php" class="btn-promo">Перейти в каталог</a>
                </div>
                <div class="promo-image-placeholder">
                    <img src="assets/img/default-slide.jpg" alt="Добро пожаловать">
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Навигационные кнопки -->
        <button class="slider-arrow left">‹</button>
        <button class="slider-arrow right">›</button>
        
        <!-- Индикаторы -->
        <div class="promo-dots">
            <?php for ($i = 0; $i < count($slides); $i++): ?>
                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
    </section>

    <!-- О компании -->
    <section class="about-company">
        <div class="container">
            <h2>О компании</h2>
            <p>Мы предлагаем широкий ассортимент товаров для декора дома — от стильных аксессуаров до функциональной мебели. Наша миссия — помочь вам создать идеальное пространство, отражающее ваш вкус и стиль жизни.</p>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <img src="assets/icons/1.jpg" alt="Большой выбор">
                        </div>
                        <h3>Большой выбор</h3>
                        <p>Более 5000 товаров для декора вашего дома</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <img src="assets/icons/2.jpg" alt="Гарантия качества">
                        </div>
                        <h3>Гарантия качества</h3>
                        <p>Все товары сертифицированы и проверены</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <img src="assets/icons/3.jpg" alt="Быстрая доставка">
                        </div>
                        <h3>Быстрая доставка</h3>
                        <p>Доставляем по всей России за 1-3 дня</p>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <img src="assets/icons/4.jpg" alt="Работаем 24/7">
                        </div>
                        <h3>Работаем 24/7</h3>
                        <p>Обращайтесь к нам в любое время</p>
                    </div>
                </div>
    </section>

    <!-- Популярные товары — статичная сетка 1x4 -->
    <section class="popular-products">
        <div class="products-wrapper">
            <div class="section-header">
                <div class="black-text">Популярные товары</div>
                <a href="pages/catalog.php" class="view-all">Все товары →</a>
            </div>
            <div class="products-grid">
                <?php
                $popular_ids = [1, 13, 5, 11];
                $products_by_id = [];
                
                try {
                    // Получаем все товары одним запросом
                    $placeholders = str_repeat('?,', count($popular_ids) - 1) . '?';
                    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($popular_ids);
                    $all_products = $stmt->fetchAll();
                    
                    // Создаем ассоциативный массив [id => product]
                    foreach ($all_products as $product) {
                        $products_by_id[$product['id']] = $product;
                    }
                    
                    // Выводим в нужном порядке
                    foreach ($popular_ids as $id) {
                        if (isset($products_by_id[$id])) {
                            $product = $products_by_id[$id];
                            $price_formatted = number_format($product['price'], 0, '', ' ');
                            $image_name = basename($product['image']);
                            $image_path = 'assets/img/' . $image_name;
                            $actual_image = file_exists($image_path) ? $image_path : 'assets/img/no-image.jpg';
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $actual_image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="rating">★★★★☆ (<?php echo rand(10, 50); ?>)</div>
                                    <div class="price">
                                        <span class="current-price"><?php echo $price_formatted; ?> ₽</span>
                                    </div>
                                    <button class="add-to-cart" 
                                            data-product-id="<?php echo $product['id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                            data-product-price="<?php echo $product['price']; ?>">
                                        В корзину
                                    </button>
                                </div>
                            </div>
                            <?php
                        } else {
                            echo '<div class="product-card"><p>Товар #' . $id . ' не найден</p></div>';
                        }
                    }
                    
                } catch (PDOException $e) {
                    echo '<p class="error">Ошибка при загрузке товаров</p>';
                }
                ?>
            </div>
        </div>
    </section>
        <!-- Навигация -->
        <button class="slider-arrow left">‹</button>
        <button class="slider-arrow right">›</button>
        <div class="slider-dots">
            <span class="dot active" data-slide="0"></span>
            <span class="dot" data-slide="1"></span>
            <span class="dot" data-slide="2"></span>
            <span class="dot" data-slide="3"></span>
        </div>
    </section>

    <!-- Специальные предложения -->
    <section class="special-offers">
        <div class="container">
            <div class="section-header">
                <h2>Специальные предложения</h2>
                <p>Не упустите возможность приобрести качественные товары для дома со скидками до 35%. Акции обновляются каждую неделю!</p>
            </div>
            <div class="offers-grid">
                <div class="offer-box">
                    <span class="offer-tag">Хит продаж</span>
                    <h3>35% Скидка на светильники</h3>
                    <p>Освещайте свой дом с умом! Выбирайте стильные и энергоэффективные модели.</p>
                </div>
                <div class="offer-box">
                    <span class="offer-tag">Новинка</span>
                    <h3>10% Новогодний декор</h3>
                    <p>Уютные пледы, подушки и постельное белье — создайте атмосферу комфорта.</p>
                </div>
                <div class="offer-box">
                    <span class="offer-tag">Лучшая цена</span>
                    <h3>5% Декоративные вазы</h3>
                    <p>Добавьте изюминку интерьеру! Разнообразие форм, цветов и материалов.</p>
                </div>
                <div class="offer-box">
                    <span class="offer-tag">Тренд</span>
                    <h3>25% Картины и постеры</h3>
                    <p>Превратите стены в галерею! Современный дизайн и классика на любой вкус.</p>
                </div>
            </div>
            <a href="pages/promotions.php" class="btn-secondary">Смотреть все акции</a>
        </div>
    </section>

    <section class="customer-testimonials">
        <div class="container">
            <div class="section-header">
                <h2>Отзывы наших клиентов</h2>
                <p>Более 10 000 довольных покупателей по всей стране — и их число растёт каждый день!</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>«Очень понравилось качество постельного белья — мягкое, приятное к телу, цвет не выцветает даже после множества стирок. Доставка быстрая, упаковка аккуратная. Обязательно закажу ещё!»</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Анна К.</strong>
                        <div class="rating">★★★★★</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>«Заказывала декоративные подушки и вазу — всё пришло в идеальном состоянии. Дизайн именно такой, как на фото. Интерьер сразу стал уютнее! Спасибо за отличный сервис.»</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Михаил С.</strong>
                        <div class="rating">★★★★★</div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>«Покупал светильник в спальню — стильный, современный, свет мягкий и не режет глаза. Ценник приятно удивил. Рекомендую этот магазин всем друзьям!»</p>
                    </div>
                    <div class="testimonial-author">
                        <strong>Елена В.</strong>
                        <div class="rating">★★★★☆</div>
                    </div>
                </div>
            </div>
            <a href="pages/reviews.php" class="btn-secondary">Смотреть все отзывы</a>
        </div>
    </section>

    <!-- Контактная информация / Консультация -->
    <section class="consultation-cta">
        <div class="container">
            <div class="cta-content">
                <div class="cta-text">
                    <h2>Нужна помощь в выборе?</h2>
                    <p>Наши специалисты бесплатно проконсультируют вас и помогут подобрать идеальный декор для вашего интерьера.</p>
                </div>
                <div class="cta-contact">
                    <div class="contact-item">
                        <strong>Телефон:</strong> <a href="tel:+78001234567">+7 (800) 123-45-67</a>
                    </div>
                    <div class="contact-item">
                        <strong>Email:</strong> <a href="mailto:info@decorhome.ru">info@decorhome.ru</a>
                    </div>
                    <div class="contact-item">
                        <strong>Режим работы:</strong> ежедневно с 9:00 до 21:00 (МСК)
                    </div>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'pages/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/index-slider.js"></script>

</body>
<script>
// Функция для показа уведомления
function showNotification(message, isError = false) {
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
    if (isError) {
        notification.style.background = '#f44336';
    }
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
}

// Обработчик кликов по кнопкам "В корзину" в популярных товарах
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            
            if (!productId) {
                console.error('Product ID not found');
                return;
            }
            
            // Сохраняем оригинальный текст и состояние
            const originalText = this.textContent;
            const originalBackground = this.style.background;
            const originalColor = this.style.color;
            
            // Показываем состояние загрузки
            this.textContent = 'Добавляем...';
            this.disabled = true;
            this.style.opacity = '0.7';
            
            // Отправляем запрос на добавление в корзину
            fetch('pages/cart.php', {
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
                    // Показываем уведомление
                    showNotification(`${productName} добавлен в корзину!`);
                    
                    // Обновляем счетчик в корзине
                    updateCartCount(data.count);
                    
                    // Визуальная обратная связь на кнопке
                    this.textContent = '✓ В корзине';
                    this.style.background = '#4CAF50';
                    this.style.color = 'white';
                    
                    // Возвращаем кнопку в исходное состояние через 2 секунды
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.background = originalBackground;
                        this.style.color = originalColor;
                        this.disabled = false;
                        this.style.opacity = '1';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Ошибка при добавлении товара в корзину', true);
                
                // Возвращаем кнопку в исходное состояние
                this.textContent = originalText;
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });
    
    // Загружаем начальное количество товаров в корзине
    fetch('pages/cart.php?get_count=true')
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                updateCartCount(data.count);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
});
document.head.appendChild(style);
</script>
</body>
</html>