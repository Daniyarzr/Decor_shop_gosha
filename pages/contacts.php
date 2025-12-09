<?php
session_start();
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты | Декор для дома</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <link rel="alternate icon" href="../favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Стили для header (уже есть в header.php) */
        header {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Основной контент */
        .main-content {
            padding: 40px 0 60px;
            min-height: calc(100vh - 300px);
        }

        .page-title {
            text-align: center;
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-subtitle {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.2rem;
            margin-bottom: 40px;
        }

        /* Контактные секции */
        .contact-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-top: 4px solid #f25081;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(242, 80, 129, 0.15);
        }

        .contact-card h3 {
            color: #f25081;
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-card h3 i {
            font-size: 1.8rem;
        }

        .contact-info {
            list-style: none;
        }

        .contact-info li {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .contact-info li:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }

        .icon {
            background: #fff5f8;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #f25081;
            font-size: 1.2rem;
        }

        .info-text h4 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }

        .info-text p {
            color: #666;
            font-size: 1rem;
        }

        /* Карта */
        .map-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 50px;
            border-top: 4px solid #f25081;
        }

        .map-frame {
            width: 100%;
            height: 400px;
            border: none;
        }

        .map-header {
            background: #f25081;
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Форма обратной связи */
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-top: 4px solid #f25081;
        }

        .form-container h3 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 1.8rem;
            text-align: center;
        }

        .contact-form {
            max-width: 800px;
            margin: 0 auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #f25081;
            box-shadow: 0 0 0 3px rgba(242, 80, 129, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, #f25081, #d43d6d);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 30px auto 0;
            min-width: 200px;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #d43d6d, #b8325c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(242, 80, 129, 0.3);
        }

        /* Часы работы */
        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .hours-table tr {
            border-bottom: 1px solid #eee;
        }

        .hours-table tr:last-child {
            border-bottom: none;
        }

        .hours-table td {
            padding: 10px 0;
            color: #666;
        }

        .hours-table td:first-child {
            font-weight: 500;
            color: #2c3e50;
        }

        /* Иконки */
        .icon-large {
            font-size: 2.5rem !important;
        }

        /* Сообщения формы */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #f25081;
        }

        .alert-success {
            background: #f0f9f0;
            color: #155724;
            border-color: #28a745;
        }

        .alert-error {
            background: #fdf2f2;
            color: #721c24;
            border-color: #dc3545;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .contact-sections {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .contact-card {
                padding: 20px;
            }
            
            .form-container {
                padding: 25px;
            }
        }

        /* Дополнительные стили для корпоративного цвета */
        .highlight {
            color: #f25081;
        }
        
        .contact-link {
            color: #f25081;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .contact-link:hover {
            color: #d43d6d;
            text-decoration: underline;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <div class="main-content">
        <div class="container">
            <h1 class="page-title">Свяжитесь с <span class="highlight">нами</span></h1>
            <p class="page-subtitle">Мы всегда рады помочь и ответить на ваши вопросы</p>

            <!-- Блок с контактной информацией -->
            <div class="contact-sections">
                <div class="contact-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Наш адрес</h3>
                    <ul class="contact-info">
                        <li>
                            <div class="icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="info-text">
                                <h4>Магазин и шоу-рум</h4>
                                <p>г. Москва, ул. Декоративная, д. 25<br>
                                БЦ "Арт-Пространство", 3 этаж</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div class="info-text">
                                <h4>Склад и доставка</h4>
                                <p>Московская область, г. Химки<br>
                                Производственная зона "Северная"</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="contact-card">
                    <h3><i class="fas fa-clock"></i> Часы работы</h3>
                    <table class="hours-table">
                        <tr>
                            <td>Понедельник - Пятница</td>
                            <td>10:00 - 20:00</td>
                        </tr>
                        <tr>
                            <td>Суббота</td>
                            <td>11:00 - 19:00</td>
                        </tr>
                        <tr>
                            <td>Воскресенье</td>
                            <td>11:00 - 18:00</td>
                        </tr>
                        <tr>
                            <td style="color: #f25081; font-weight: 600;">Без перерыва</td>
                            <td style="color: #f25081;">Работаем без выходных</td>
                        </tr>
                    </table>
                </div>

                <div class="contact-card">
                    <h3><i class="fas fa-phone-alt"></i> Контакты</h3>
                    <ul class="contact-info">
                        <li>
                            <div class="icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="info-text">
                                <h4>Телефоны</h4>
                                <p>+7 (495) 123-45-67<br>
                                +7 (800) 100-20-30 (бесплатно)</p>
                            </div>
                        </li>
                        <li>
                            <div class="icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-text">
                                <h4>Email</h4>
                                <p><a href="mailto:info@decor-doma.ru" class="contact-link">info@decor-doma.ru</a><br>
                                <a href="mailto:support@decor-doma.ru" class="contact-link">support@decor-doma.ru</a></p>
                            </div>
                        </li>
                        <li>
                            <div class="icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="info-text">
                                <h4>Техническая поддержка</h4>
                                <p>24/7 по email<br>
                                Чат на сайте: с 9:00 до 21:00</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Карта -->
            <div class="map-container">
                <div class="map-header">
                    <i class="fas fa-map-marked-alt icon-large"></i>
                    <h3>Как нас найти</h3>
                </div>
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2243.678174488467!2d37.61842331589975!3d55.775441998047!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46b54a5a738fa419%3A0x7c347d406e0eabe8!2z0YPQuy4g0JTQtdC60L7RgNGD0L3QsCwgMjUsINCc0L7RgdC60LLQsCwgMTI1MDQ3!5e0!3m2!1sru!2sru!4v1647612345678!5m2!1sru!2sru" 
                    class="map-frame" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>

            <!-- Форма обратной связи -->
            <div class="form-container">
                <h3>Форма обратной <span class="highlight">связи</span></h3>
                
                <?php
                // Обработка формы
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $name = htmlspecialchars(trim($_POST['name']));
                    $email = htmlspecialchars(trim($_POST['email']));
                    $phone = htmlspecialchars(trim($_POST['phone']));
                    $subject = htmlspecialchars(trim($_POST['subject']));
                    $message = htmlspecialchars(trim($_POST['message']));
                    
                    $errors = [];
                    
                    // Валидация
                    if (empty($name)) $errors[] = 'Введите имя';
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email';
                    if (empty($message)) $errors[] = 'Введите сообщение';
                    
                    if (empty($errors)) {
                        // В реальном проекте здесь была бы отправка на email
                        // или сохранение в базу данных
                        
                        $success_message = "Спасибо, <strong>$name</strong>! Ваше сообщение отправлено. Мы ответим вам в течение 24 часов.";
                    }
                }
                ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 10px;"></i>
                        <?= $success_message ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle" style="color: #dc3545; margin-right: 10px;"></i>
                        <?php foreach ($errors as $error): ?>
                            <p><?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form class="contact-form" method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Ваше имя <span style="color: #f25081;">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span style="color: #f25081;">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= isset($_POST['email']) ? $_POST['email'] : '' ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?= isset($_POST['phone']) ? $_POST['phone'] : '' ?>" 
                                   placeholder="+7 (999) 123-45-67">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Тема обращения</label>
                            <select id="subject" name="subject" class="form-control">
                                <option value="general" <?= (isset($_POST['subject']) && $_POST['subject'] == 'general') ? 'selected' : '' ?>>Общий вопрос</option>
                                <option value="order" <?= (isset($_POST['subject']) && $_POST['subject'] == 'order') ? 'selected' : '' ?>>Вопрос по заказу</option>
                                <option value="delivery" <?= (isset($_POST['subject']) && $_POST['subject'] == 'delivery') ? 'selected' : '' ?>>Доставка</option>
                                <option value="return" <?= (isset($_POST['subject']) && $_POST['subject'] == 'return') ? 'selected' : '' ?>>Возврат товара</option>
                                <option value="wholesale" <?= (isset($_POST['subject']) && $_POST['subject'] == 'wholesale') ? 'selected' : '' ?>>Оптовые закупки</option>
                                <option value="cooperation" <?= (isset($_POST['subject']) && $_POST['subject'] == 'cooperation') ? 'selected' : '' ?>>Сотрудничество</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Ваше сообщение <span style="color: #f25081;">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="5" required><?= isset($_POST['message']) ? $_POST['message'] : '' ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Отправить сообщение
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Футер -->
    <?php include 'footer.php'; ?>

    <script>
        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + (x[3] ? ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '') : '');
        });

        // Подсветка обязательных полей
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = '#f25081';
                    this.style.boxShadow = '0 0 0 3px rgba(242, 80, 129, 0.2)';
                } else {
                    this.style.borderColor = '#4CAF50';
                    this.style.boxShadow = 'none';
                }
            });
        });

        // Анимация отправки формы
        document.querySelector('.contact-form')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            
            // В реальном проекте здесь был бы AJAX запрос
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }, 2000);
        });

        // Плавный скролл для якорных ссылок
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>