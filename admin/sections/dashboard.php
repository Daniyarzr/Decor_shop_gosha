<?php
// admin/sections/dashboard.php

try {
    // Статистика
    $stats['products'] = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['revenue'] = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'")->fetchColumn() ?? 0;
    $stats['pending_orders'] = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $error = "Ошибка при загрузке статистики: " . $e->getMessage();
}
?>

<div class="content-card">
    <h2>Панель управления</h2>
    <p>Обзор состояния магазина</p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $stats['products'] ?? 0; ?></h3>
            <p>Товаров</p>
        </div>
        
        <div class="stat-card" style="border-left-color: #27ae60;">
            <h3><?php echo $stats['orders'] ?? 0; ?></h3>
            <p>Заказов</p>
        </div>
        
        <div class="stat-card" style="border-left-color: #f39c12;">
            <h3><?php echo $stats['users'] ?? 0; ?></h3>
            <p>Пользователей</p>
        </div>
        
        <div class="stat-card" style="border-left-color: #9b59b6;">
            <h3><?php echo $stats['pending_orders'] ?? 0; ?></h3>
            <p>Ожидают обработки</p>
        </div>
    </div>
    
    <h3>Быстрые действия</h3>
    <div class="admin-nav" style="background: transparent; padding: 0;">
        <a href="?section=products&action=add" class="btn-add">
            <i class="fas fa-plus"></i> Добавить товар
        </a>
        <a href="?section=orders" class="btn-add" style="background: #3498db;">
            <i class="fas fa-shopping-cart"></i> Просмотреть заказы
        </a>
        <?php if ($is_admin): ?>
        <a href="?section=users" class="btn-add" style="background: #f39c12;">
            <i class="fas fa-users"></i> Управление пользователями
        </a>
        <?php endif; ?>
    </div>
</div>

