<?php
session_start();
require_once 'config.php';
require_once 'includes/cache.php';

if (!isset($pdo)) {
    die("Ошибка: Нет подключения к базе данных");
}

define('ASSETS_VERSION', '1.0.0');

$cache_key_slides = 'home_slides';
$slides = Cache::get($cache_key_slides);
if ($slides === null) {
    try {
        $stmt = $pdo->query("SELECT * FROM promo_sliders WHERE is_active = 1 ORDER BY sort_order ASC");
        $slides = $stmt->fetchAll();
        Cache::set($cache_key_slides, $slides, 1800);
    } catch (PDOException $e) {
        $slides = [];
        error_log("Ошибка при загрузке слайдов: " . $e->getMessage());
    }
}

$cache_key_reviews = 'home_reviews';
$reviews = Cache::get($cache_key_reviews);
if ($reviews === null) {
    try {
        $reviews = $pdo->query("SELECT name, text, rating, created_at FROM reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6")->fetchAll();
        Cache::set($cache_key_reviews, $reviews, 3600);
    } catch (PDOException $e) {
        $reviews = [];
    }
}

$cache_key_promotions = 'home_promotions_' . date('Y-m-d-H');
$home_promotions = Cache::get($cache_key_promotions);
if ($home_promotions === null) {
    $current_date = date('Y-m-d');
    try {
        $stmt = $pdo->prepare("
            SELECT * 
            FROM promotions 
            WHERE is_active = 1 
              AND start_date <= ? 
              AND end_date >= ?
            ORDER BY start_date DESC 
            LIMIT 4
        ");
        $stmt->execute([$current_date, $current_date]);
        $home_promotions = $stmt->fetchAll();
        Cache::set($cache_key_promotions, $home_promotions, 900);
    } catch (PDOException $e) {
        $home_promotions = [];
    }
}

?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Декор для дома — стиль и уют</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo ASSETS_VERSION; ?>">
    <link rel="stylesheet" href="assets/css/pages.css?v=<?php echo ASSETS_VERSION; ?>">
</head>
<body>


    <?php include 'pages/header.php'; ?>

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
        
        <button class="slider-arrow left">‹</button>
        <button class="slider-arrow right">›</button>
        
        <div class="promo-dots">
            <?php for ($i = 0; $i < count($slides); $i++): ?>
                <span class="dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></span>
            <?php endfor; ?>
        </div>
    </section>

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
                    $placeholders = str_repeat('?,', count($popular_ids) - 1) . '?';
                    $query = "SELECT * FROM products WHERE id IN ($placeholders)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute($popular_ids);
                    $all_products = $stmt->fetchAll();
                    
                    foreach ($all_products as $product) {
                        $products_by_id[$product['id']] = $product;
                    }
                    
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
                                    <img src="<?php echo $actual_image; ?>" alt="<?php echo htmlspecialchars($image_path); ?>">
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

    <section class="special-offers">
        <div class="container">
            <div class="section-header">
                <h2>Популярные акции</h2>
                <p>Актуальные скидки и спецпредложения, которые мы рекомендуем сейчас.</p>
            </div>

            <div class="offers-grid">
                <?php if (empty($home_promotions)): ?>
                    <div class="offer-box offer-box-span-2">
                        <span class="offer-tag">Скоро</span>
                        <h3>Здесь появятся горячие акции</h3>
                        <p>Мы уже готовим новые предложения. Загляните чуть позже или посмотрите все акции.</p>
                        <a class="offer-link" href="pages/promotions.php">Перейти к акциям</a>
                    </div>
                <?php else: ?>
                    <?php 
                    $promo_fallbacks = [
                        'assets/img/promotions/newyear_sale.jpg',
                        'assets/img/slide1.jpg',
                        'assets/img/slide2.jpg',
                        'assets/img/slide3.jpg'
                    ];
                    ?>
                    <?php foreach ($home_promotions as $promo_index => $promo): ?>
                        <?php
                            $discount_text = '';
                            if ($promo['discount_type'] === 'percentage' && $promo['discount_value'] !== null) {
                                $discount_text = '-' . (float)$promo['discount_value'] . '%';
                            } elseif ($promo['discount_type'] === 'fixed' && $promo['discount_value'] !== null) {
                                $discount_text = '-' . number_format((float)$promo['discount_value'], 0, '', ' ') . ' ₽';
                            } else {
                                $discount_text = 'Спецпредложение';
                            }
                            
                            $image_src = '';
                            $promo_image = trim($promo['image'] ?? '');
                            $baseDir = __DIR__ . '/';
                            
                            if (!empty($promo_image)) {
                                $promo_image = ltrim($promo_image, '/');
                                $check_paths = [
                                    $promo_image,
                                    'assets/img/promotions/' . $promo_image,
                                    'assets/img/' . $promo_image,
                                ];
                                foreach ($check_paths as $check_path) {
                                    $full_path = $baseDir . ltrim($check_path, '/');
                                    if (file_exists($full_path)) {
                                        $image_src = $check_path;
                                        break;
                                    }
                                }
                            }

                            if (empty($image_src)) {
                                $fallback = $promo_fallbacks[$promo_index % count($promo_fallbacks)];
                                if (file_exists($baseDir . ltrim($fallback, '/'))) {
                                    $image_src = $fallback;
                                } else {
                                    $image_src = '';
                                }
                            }
                        ?>
                        <div class="offer-box">
                            <div class="offer-image">
                                <img src="<?php echo htmlspecialchars($image_src ?: 'assets/img/slide1.jpg'); ?>" alt="<?php echo htmlspecialchars($promo['title']); ?>">
                                <span class="offer-tag"><?php echo htmlspecialchars($discount_text); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($promo['title']); ?></h3>
                            <p><?php echo htmlspecialchars($promo['description']); ?></p>
                            <div class="offer-meta">
                                <span class="offer-dates">
                                    до <?php echo date('d.m.Y', strtotime($promo['end_date'])); ?>
                                </span>
                                <a class="offer-link" href="pages/promotions.php#promo-<?php echo (int)$promo['id']; ?>">Подробнее</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                <?php if (empty($reviews)): ?>
                    <div class="testimonial-card testimonial-card-span-3">
                        <div class="testimonial-content">
                            <p>Пока нет опубликованных отзывов. Будьте первым!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <p>«<?= htmlspecialchars($rev['text']); ?>»</p>
                            </div>
                            <div class="testimonial-author">
                                <strong><?= htmlspecialchars($rev['name']); ?></strong>
                                <div class="rating">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <?= $i <= (int)$rev['rating'] ? '★' : '☆'; ?>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-date"><?= date('d.m.Y', strtotime($rev['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <a href="pages/reviews.php" class="btn-secondary">Смотреть все отзывы</a>
        </div>
    </section>

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

    <?php include 'pages/footer.php'; ?>

    <script src="assets/js/main.js?v=<?php echo ASSETS_VERSION; ?>"></script>
    <script src="assets/js/index-slider.js?v=<?php echo ASSETS_VERSION; ?>"></script>

</body>
<script>
function showNotification(message, isError = false) {
    const oldNotification = document.querySelector('.cart-notification');
    if (oldNotification) {
        oldNotification.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'cart-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <svg class="notification-icon" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
            <span>${message}</span>
        </div>
    `;
    if (isError) {
        notification.style.background = '#f44336';
    }
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('hiding');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

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
            
            const originalText = this.textContent;
            const originalBackground = this.style.background;
            const originalColor = this.style.color;
            
            this.textContent = 'Добавляем...';
            this.disabled = true;
            this.style.opacity = '0.7';
            
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
                    showNotification(`${productName} добавлен в корзину!`);
                    updateCartCount(data.count);
                    this.textContent = '✓ В корзине';
                    this.style.background = '#4CAF50';
                    this.style.color = 'white';
                    
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
                this.textContent = originalText;
                this.disabled = false;
                this.style.opacity = '1';
            });
        });
    });
    
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
</script>
</body>
</html>