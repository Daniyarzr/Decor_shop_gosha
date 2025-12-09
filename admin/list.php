<?php
// admin/list.php

if (!in_array($entity, ['products', 'orders', 'users', 'promotions', 'sliders'])) {
    echo '<div class="content-card"><div class="alert alert-danger">Неверный тип сущности</div></div>';
    return;
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        switch ($entity) {
            case 'products':
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                break;
            case 'users':
                // Не даем удалить себя
                if ($delete_id != $user_id) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                }
                break;
            case 'promotions':
                $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
                break;
            case 'sliders':
                $stmt = $pdo->prepare("DELETE FROM promo_sliders WHERE id = ?");
                break;
            case 'orders':
                // Для заказов лучше менять статус
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                break;
        }
        
        if (isset($stmt)) {
            $stmt->execute([$delete_id]);
            $message = "Запись успешно удалена";
        }
    } catch (PDOException $e) {
        $error = "Ошибка при удалении: " . $e->getMessage();
    }
}

// Получение данных
try {
    switch ($entity) {
        case 'products':
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            $title = "Товары";
            $columns = ['ID', 'Изображение', 'Название', 'Цена', 'Категория', 'Действия'];
            break;
            
        case 'orders':
            $stmt = $pdo->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
            $title = "Заказы";
            $columns = ['ID', 'Номер', 'Клиент', 'Сумма', 'Статус', 'Дата', 'Действия'];
            break;
            
        case 'users':
            $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id DESC");
            $title = "Пользователи";
            $columns = ['ID', 'Логин', 'Email', 'Роль', 'Дата регистрации', 'Действия'];
            break;
            
        case 'promotions':
            $stmt = $pdo->query("SELECT * FROM promotions ORDER BY id DESC");
            $title = "Акции";
            $columns = ['ID', 'Название', 'Скидка', 'Дата начала', 'Дата окончания', 'Статус', 'Действия'];
            break;
            
        case 'sliders':
            $stmt = $pdo->query("SELECT * FROM promo_sliders ORDER BY sort_order ASC");
            $title = "Слайдеры";
            $columns = ['ID', 'Метка', 'Заголовок', 'Ссылка', 'Порядок', 'Статус', 'Действия'];
            break;
    }
    
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Ошибка при загрузке данных: " . $e->getMessage();
    $items = [];
}
?>

<div class="content-card">
    <h2><i class="fas fa-list"></i> <?php echo $title; ?></h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <a href="?action=add&entity=<?php echo $entity; ?>" class="btn-add">
        <i class="fas fa-plus"></i> Добавить
    </a>
    
    <div class="table-container mt-3">
        <table class="admin-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                        <th><?php echo $col; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <?php if ($entity == 'products'): ?>
                        <td><?php echo $item['id']; ?></td>
                        <td>
                            <?php if ($item['image']): ?>
                                <img src="../assets/img/<?php echo basename($item['image']); ?>" 
                                     alt="Изображение" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                                <span class="text-muted">Нет</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo number_format($item['price'], 2, '.', ' '); ?> ₽</td>
                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                        <td>
                            <a href="?action=edit&entity=products&id=<?php echo $item['id']; ?>" 
                               class="btn-action btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Удалить этот товар?');">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    
                    <?php elseif ($entity == 'orders'): ?>
                        <td><?php echo $item['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['order_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['username'] ?: $item['customer_name']); ?></td>
                        <td><?php echo number_format($item['total_amount'], 2, '.', ' '); ?> ₽</td>
                        <td>
                            <?php 
                            $statuses = [
                                'pending' => ['Ожидание', '#f39c12'],
                                'processing' => ['В обработке', '#3498db'],
                                'completed' => ['Завершен', '#27ae60'],
                                'cancelled' => ['Отменен', '#e74c3c']
                            ];
                            $status = $item['status'];
                            ?>
                            <span style="background: <?php echo $statuses[$status][1]; ?>; 
                                  color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo $statuses[$status][0]; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></td>
                        <td>
                            <a href="?action=edit&entity=orders&id=<?php echo $item['id']; ?>" 
                               class="btn-action btn-edit">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    
                    <?php elseif ($entity == 'users'): ?>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td>
                            <span style="background: <?php echo $item['role'] == 'admin' ? '#e74c3c' : 
                                                     ($item['role'] == 'moderator' ? '#3498db' : '#27ae60'); ?>; 
                                  color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo $item['role']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($item['created_at'])); ?></td>
                        <td>
                            <?php if ($item['id'] != $user_id): ?>
                                <a href="?action=edit&entity=users&id=<?php echo $item['id']; ?>" 
                                   class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Удалить этого пользователя?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Это вы</span>
                            <?php endif; ?>
                        </td>
                    
                    <?php elseif ($entity == 'promotions'): ?>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                        <td>
                            <?php if ($item['discount_type'] == 'percentage'): ?>
                                <?php echo $item['discount_value']; ?>%
                            <?php elseif ($item['discount_type'] == 'fixed'): ?>
                                <?php echo $item['discount_value']; ?> ₽
                            <?php else: ?>
                                <?php echo htmlspecialchars($item['discount_type']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($item['start_date'])); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($item['end_date'])); ?></td>
                        <td>
                            <?php if ($item['is_active']): ?>
                                <span style="background: #27ae60; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                    Активна
                                </span>
                            <?php else: ?>
                                <span style="background: #95a5a6; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                    Не активна
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?action=edit&entity=promotions&id=<?php echo $item['id']; ?>" 
                               class="btn-action btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Удалить эту акцию?');">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    
                    <?php elseif ($entity == 'sliders'): ?>
                        <td><?php echo $item['id']; ?></td>
                        <td>
                            <span style="background: 
                                <?php echo $item['tag_class'] == 'hot' ? '#e74c3c' : 
                                       ($item['tag_class'] == 'best' ? '#f39c12' : '#3498db'); ?>; 
                                color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                <?php echo htmlspecialchars($item['tag']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(mb_substr($item['title'], 0, 50) . '...'); ?></td>
                        <td><?php echo htmlspecialchars($item['button_link']); ?></td>
                        <td><?php echo $item['sort_order']; ?></td>
                        <td>
                            <?php if ($item['is_active']): ?>
                                <span style="background: #27ae60; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                    Активен
                                </span>
                            <?php else: ?>
                                <span style="background: #95a5a6; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                    Не активен
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?action=edit&entity=sliders&id=<?php echo $item['id']; ?>" 
                               class="btn-action btn-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Удалить этот слайд?');">
                                <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?php echo count($columns); ?>" class="text-center text-muted py-4">
                        Нет данных для отображения
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>