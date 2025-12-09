<?php
require_once 'check_admin.php';

$error = '';
$success = '';

$categories = [
    'Мебель',
    'Освещение',
    'Декоративные фигурки и статуэтки',
    'Картины и постеры',
    'Вазы и емкости',
    'Текстильные элементы'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? 0;
    $category = $_POST['category'] ?? '';

    if (empty($name) || $price <= 0) {
        $error = 'Название и цена обязательны.';
    } elseif (!in_array($category, $categories)) {
        $error = 'Пожалуйста, выберите категорию из списка.';
    } else {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $_FILES['image']['error'] === 0) {
                $image = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/img/' . $image);
            }
        }

        // Только name, price, image, category — всё, что есть в таблице
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image, category) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $image, $category])) {
            $success = 'Товар успешно добавлен!';
        } else {
            $error = 'Ошибка при добавлении товара.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Добавить товар</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
        .btn { padding: 10px 20px; background: #f25081; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #e04070; }
        .error { color: red; background: #ffecec; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        a { color: #666; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>Добавить товар</h2>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Название *</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Цена (₽) *</label>
            <input type="number" step="0.01" name="price" required>
        </div>
        <div class="form-group">
            <label>Категория *</label>
            <select name="category" required>
                <option value="">— Выберите категорию —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Изображение</label>
            <input type="file" name="image" accept="image/*">
        </div>
        <button type="submit" class="btn">Добавить</button>
        <a href="products.php" style="margin-left: 10px;">← Назад</a>
    </form>
</div>
</body>
</html>