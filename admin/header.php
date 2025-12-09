<?php
// admin/header.php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Decor Shop</title>
    
    <!-- Bootstrap 5 (опционально) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Ваши стили -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-danger: #e74c3c;
            --admin-warning: #f39c12;
        }
        
        .admin-container {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            background-color: var(--admin-primary);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar {
            background-color: var(--admin-secondary);
            color: white;
            min-height: calc(100vh - 73px);
            width: 250px;
            position: fixed;
            left: 0;
            top: 73px;
            padding-top: 20px;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 20px;
            min-height: calc(100vh - 73px);
        }
        
        .sidebar-menu a {
            color: #ecf0f1;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu a.active {
            background-color: var(--admin-accent);
            color: white;
        }
        
        .card-admin {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-admin {
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-admin-primary {
            background-color: var(--admin-accent);
            color: white;
            border: none;
        }
        
        .btn-admin-primary:hover {
            background-color: #2980b9;
            color: white;
        }
        
        .btn-admin-danger {
            background-color: var(--admin-danger);
            color: white;
            border: none;
        }
        
        .btn-admin-success {
            background-color: var(--admin-success);
            color: white;
            border: none;
        }
        
        .table-admin {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table-admin th {
            background-color: var(--admin-primary);
            color: white;
            border: none;
            padding: 15px;
        }
        
        .table-admin td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background-color: #fef3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .user-role {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin { background-color: #e74c3c; color: white; }
        .role-moderator { background-color: #3498db; color: white; }
        .role-user { background-color: #2ecc71; color: white; }
        
        .product-image-admin {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Шапка -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Админ-панель Decor Shop
                    </h1>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Администратор'); ?>
                        <span class="user-role role-<?php echo $_SESSION['user_role'] ?? 'user'; ?>">
                            <?php echo $_SESSION['user_role'] ?? 'user'; ?>
                        </span>
                    </span>
                    <a href="logout.php" class="btn btn-sm btn-light">
                        <i class="fas fa-sign-out-alt"></i> Выйти
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="admin-container">
        <!-- Боковое меню -->
        <div class="admin-sidebar">
            <nav class="sidebar-menu">
                <div class="mb-3 px-3">
                    <small class="text-muted">ГЛАВНАЯ</small>
                </div>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Панель управления
                </a>
                
                <div class="mb-3 mt-4 px-3">
                    <small class="text-muted">КАТАЛОГ</small>
                </div>
                <a href="products/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'products/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-box me-2"></i> Товары
                </a>
                <a href="categories/index.php">
                    <i class="fas fa-tags me-2"></i> Категории
                </a>
                
                <div class="mb-3 mt-4 px-3">
                    <small class="text-muted">МАГАЗИН</small>
                </div>
                <a href="orders/index.php">
                    <i class="fas fa-shopping-cart me-2"></i> Заказы
                </a>
                <a href="promotions/index.php">
                    <i class="fas fa-percent me-2"></i> Акции
                </a>
                <a href="sliders/index.php">
                    <i class="fas fa-images me-2"></i> Слайдеры
                </a>
                
                <div class="mb-3 mt-4 px-3">
                    <small class="text-muted">ПОЛЬЗОВАТЕЛИ</small>
                </div>
                <a href="users/index.php">
                    <i class="fas fa-users me-2"></i> Пользователи
                </a>
                
                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <div class="mb-3 mt-4 px-3">
                    <small class="text-muted">СИСТЕМА</small>
                </div>
                <a href="settings/index.php">
                    <i class="fas fa-cog me-2"></i> Настройки
                </a>
                <?php endif; ?>
            </nav>
        </div>
        
        <!-- Основной контент -->
        <div class="admin-content">