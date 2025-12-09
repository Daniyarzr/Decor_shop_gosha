<?php
// admin/index.php
require_once 'auth_check.php';

// Определяем какой раздел показывать
$section = $_GET['section'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Доступные разделы для модератора
$moderator_sections = ['dashboard', 'products', 'orders', 'promotions'];
if (!$is_admin && !in_array($section, $moderator_sections)) {
    $section = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Decor Shop</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        .nav-btn {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .nav-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .nav-btn.active {
            background: #3498db;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .user-role {
            background: <?php echo $is_admin ? '#e74c3c' : '#3498db'; ?>;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 28px;
            margin: 0;
            color: #2c3e50;
        }
        
        .stat-card p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        
        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-add {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        
        .tab-btn {
            padding: 8px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .tab-btn.active {
            background: #3498db;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><i class="fas fa-cog"></i> Админ-панель Decor Shop</h1>
            <div class="user-info">
                <span>Привет, <?php echo htmlspecialchars($username); ?></span>
                <span class="user-role">
                    <?php echo $is_admin ? 'Администратор' : 'Модератор'; ?>
                </span>
                <a href="../index.php" class="nav-btn" style="background: #27ae60;">
                    <i class="fas fa-home"></i> На сайт
                </a>
                <a href="../logout.php" class="nav-btn" style="background: #e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> Выход
                </a>
            </div>
            
            <nav class="admin-nav">
                <a href="?section=dashboard" class="nav-btn <?php echo $section == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Панель
                </a>
                <a href="?section=products" class="nav-btn <?php echo $section == 'products' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Товары
                </a>
                <a href="?section=orders" class="nav-btn <?php echo $section == 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Заказы
                </a>
                <a href="?section=reviews" class="nav-btn <?php echo $section == 'reviews' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Отзывы
                </a>
                <a href="?section=users" class="nav-btn <?php echo ($section == 'users' && $is_admin) ? 'active' : ''; ?>" 
                   <?php echo !$is_admin ? 'style="display:none;"' : ''; ?>>
                    <i class="fas fa-users"></i> Пользователи
                </a>
                <a href="?section=promotions" class="nav-btn <?php echo $section == 'promotions' ? 'active' : ''; ?>">
                    <i class="fas fa-percent"></i> Акции
                </a>
                <a href="?section=sliders" class="nav-btn <?php echo $section == 'sliders' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i> Слайдеры
                </a>
            </nav>
        </header>
        
        <main>
            <?php
            // Включаем соответствующий раздел
            switch ($section) {
                case 'dashboard':
                    include 'sections/dashboard.php';
                    break;
                case 'products':
                    include 'sections/products.php';
                    break;
                case 'orders':
                    include 'sections/orders.php';
                    break;
                case 'users':
                    if ($is_admin) include 'sections/users.php';
                    break;
                case 'promotions':
                    include 'sections/promotions.php';
                    break;
                case 'sliders':
                    include 'sections/sliders.php';
                    break;
                case 'reviews':
                    include 'sections/reviews.php';
                    break;
                default:
                    include 'sections/dashboard.php';
            }
            ?>
        </main>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Переключение табов
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // Убираем активный класс у всех кнопок
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active');
                });
                
                // Добавляем активный класс текущей кнопке
                this.classList.add('active');
                
                // Скрываем все табы
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.remove('active');
                });
                
                // Показываем выбранный таб
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Подтверждение удаления
        function confirmDelete(message) {
            return confirm(message || 'Вы уверены, что хотите удалить этот элемент?');
        }
    </script>
</body>
</html>