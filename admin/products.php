<?php require_once 'check_admin.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Управление товарами</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f25081; color: white; }
        .actions a { margin-right: 10px; text-decoration: none; }
        .btn { padding: 6px 12px; border-radius: 4px; color: white; text-decoration: none; }
        .btn-edit { background: #007BFF; }
        .btn-delete { background: #e74c3c; }
        .btn-add { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #f25081; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Управление товарами</h1>
    <a href="add_product.php" class="btn-add">+ Добавить товар</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Цена</th>
                <th>Изображение</th>
                <th>Рекомендуемый</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
            while ($product = $stmt->fetch()):
            ?>
            <tr>
                <td><?= $product['id'] ?></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= number_format($product['price'], 0, ',', ' ') ?> ₽</td>
                <td>
                    <?php if ($product['image']): ?>
                        <img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" width="50">
                    <?php endif; ?>
                </td>
                <td><?= $product['is_featured'] ? 'Да' : 'Нет' ?></td>
                <td class="actions">
                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-edit">Редактировать</a>
                    <a href="delete_product.php?id=<?= $product['id'] ?>" class="btn btn-delete" onclick="return confirm('Удалить товар?')">Удалить</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>