<?php
require_once 'check_admin.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die('Неверный ID товара.');
}

// Получаем товар
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    die('Товар не найден.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($name) || $price <= 0) {
        $error = 'Название и цена обязательны.';
    } else {
        $image = $product['image']; // сохраняем старое изображение
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['image']['error'] === 0) {
                // Удаляем старое изображение
                if ($product['image'] && file_exists('../assets/img/' . $product['image'])) {
                    unlink('../assets/img/' . $product['image']);
                }
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/img/' . $image);
            }
        }

        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ?, is_featured = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $price, $image, $is_featured, $id])) {
            $success = 'Товар обновлён!';
            // Обновляем данные для формы
            $product = ['name' => $name, 'description' => $description, 'price' => $price, 'image' => $image, 'is_featured' => $is_featured];
        } else {
            $error = 'Ошибка при обновлении.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Редактировать товар</title>
    <meta charset="utf-8">
    <style>
        /* тот же стиль, что и в add_product.php */
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 10px 20px; background: #f25081; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; background: #ffecec; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h2>Редактировать товар</h2>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label>Цена (₽) *</label>
            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
        </div>
        <div class="form-group">
            <label>Текущее изображение</label>
            <?php if ($product['image']): ?>
                <div><img src="../assets/img/<?= htmlspecialchars($product['image']) ?>" width="100"></div>
            <?php else: ?>
                <div>Нет изображения</div>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Заменить изображение</label>
            <input type="file" name="image" accept="image/*">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?>> Рекомендуемый товар
            </label>
        </div>
        <button type="submit" class="btn">Сохранить</button>
        <a href="products.php" style="margin-left: 10px;">← Назад</a>
    </form>
</div>
</body>
</html>