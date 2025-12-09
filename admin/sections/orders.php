<?php
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowed = ['pending','processing','completed','cancelled'];
    if (in_array($status, $allowed, true)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $message = "Статус заказа обновлен";
        } catch (PDOException $e) {
            $error = "Ошибка при обновлении статуса: " . $e->getMessage();
        }
    }
}

try {
    $orders = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = "Ошибка при загрузке заказов: " . $e->getMessage();
}
?>

<div class="content-card">
    <h2><i class="fas fa-shopping-cart"></i> Заказы</h2>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Номер</th>
                    <th>Клиент</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['username'] ?: $order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['phone']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><?php echo number_format($order['total_amount'], 2, '.', ' '); ?> ₽</td>
                    <td>
                        <?php 
                        $statuses = [
                            'pending' => 'Ожидает',
                            'processing' => 'В обработке',
                            'completed' => 'Завершен',
                            'cancelled' => 'Отменен'
                        ];
                        ?>
                        <?php if ($is_admin): ?>
                            <form method="POST" class="form-inline-flex">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-control select-min-width-large">
                                    <?php foreach ($statuses as $key => $title): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $order['status']==$key?'selected':''; ?>>
                                            <?php echo $title; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-action btn-edit">OK</button>
                            </form>
                        <?php else: ?>
                            <span class="status-badge status-<?php echo htmlspecialchars($order['status']); ?>">
                                <?php echo $statuses[$order['status']] ?? $order['status']; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                    <td>
                        <button class="btn-action btn-edit" onclick="toggleDetails(<?php echo $order['id']; ?>)">Показать</button>
                    </td>
                </tr>
                <tr id="order-details-<?php echo $order['id']; ?>" class="order-details-row">
                    <td colspan="9">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT product_name, product_price, quantity, subtotal 
                                FROM order_items WHERE order_id = ?
                            ");
                            $stmt->execute([$order['id']]);
                            $items = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            $items = [];
                        }
                        ?>
                        <strong>Товары:</strong>
                        <div class="table-container table-container-margin">
                            <table class="admin-table table-margin-zero">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Цена</th>
                                        <th>Кол-во</th>
                                        <th>Сумма</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($it['product_name']); ?></td>
                                        <td><?php echo number_format($it['product_price'],2,'.',' '); ?> ₽</td>
                                        <td><?php echo $it['quantity']; ?></td>
                                        <td><?php echo number_format($it['subtotal'],2,'.',' '); ?> ₽</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($items)): ?>
                                    <tr><td colspan="4" class="text-muted">Нет позиций</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="margin-top-small">
                            <div><strong>Адрес:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></div>
                            <div><strong>Город:</strong> <?php echo htmlspecialchars($order['city']); ?></div>
                            <div><strong>Оплата:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></div>
                            <div><strong>Комментарий:</strong> <?php echo htmlspecialchars($order['comment']); ?></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Заказов пока нет</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleDetails(id) {
    const row = document.getElementById('order-details-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

