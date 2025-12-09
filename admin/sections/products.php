<?php
// admin/sections/products.php

$current_action = $action ?: 'list';
$uploadDir = realpath(__DIR__ . '/../../assets/img');

// Удаление товара
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Товар успешно удален";
    } catch (PDOException $e) {
        $error = "Ошибка при удалении: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $image = trim($_POST['image']);
    $category = trim($_POST['category']);

    // Если загружают файл — сохраняем его и подменяем путь
    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK && $uploadDir) {
        $safeName = basename($_FILES['image_file']['name']);
        $target = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
            $image = 'assets/img/' . $safeName;
        }
    }
    
    if ($id > 0) {
        // Редактирование
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, image = ?, category = ? WHERE id = ?");
        $stmt->execute([$name, $price, $image, $category, $id]);
        $message = "Товар успешно обновлен";
    } else {
        // Добавление
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $image, $category]);
        $id = $pdo->lastInsertId();
        $message = "Товар успешно добавлен";
    }
}

if ($current_action == 'add' || $current_action == 'edit') {
    $product = null;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    }
    ?>
    
    <div class="content-card">
        <h2><?php echo $id > 0 ? 'Редактировать товар' : 'Добавить товар'; ?></h2>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название товара</label>
                <input type="text" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Цена (₽)</label>
                <input type="number" name="price" class="form-control" step="0.01" 
                       value="<?php echo $product['price'] ?? ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Изображение (путь)</label>
                <input type="text" name="image" class="form-control" 
                       value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                <small class="text-muted">Пример: assets/img/product.jpg или загрузите файл ниже</small>
            </div>
            <div class="form-group">
                <label>Загрузить изображение</label>
                <input type="file" name="image_file" accept="image/*" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Категория</label>
                <input type="text" name="category" class="form-control" 
                       value="<?php echo htmlspecialchars($product['category'] ?? ''); ?>">
            </div>
            
            <button type="submit" name="save_product" class="btn-submit">
                <?php echo $id > 0 ? 'Сохранить изменения' : 'Добавить товар'; ?>
            </button>
            <a href="?section=products" class="btn-action" style="background: #95a5a6;">Отмена</a>
        </form>
    </div>
    
    <?php
} else {
    // Список товаров
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        $products = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Ошибка при загрузке товаров: " . $e->getMessage();
        $products = [];
    }
    ?>
    
    <div class="content-card">
        <h2>Управление товарами</h2>
        
        <div class="tabs">
            <button class="tab-btn active" data-tab="list">Список товаров</button>
            <button class="tab-btn" data-tab="add">Добавить товар</button>
        </div>
        
        <div id="list" class="tab-content active">
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Название</th>
                            <th>Цена</th>
                            <th>Категория</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <?php if ($product['image']): ?>
                                    <?php 
                                        $img = $product['image'];
                                        if (strpos($img, '/') === false) {
                                            $img = 'assets/img/' . $img;
                                        }
                                    ?>
                                    <img src="../<?php echo htmlspecialchars($img); ?>" 
                                         alt="Изображение" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>
                                <a href="?section=products&action=edit&id=<?php echo $product['id']; ?>" 
                                   class="btn-action btn-edit">Изменить</a>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirmDelete('Удалить товар &quot;<?php echo addslashes($product['name']); ?>&quot;?')">
                                    <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="btn-action btn-delete">Удалить</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="add" class="tab-content">
            <h3>Добавить новый товар</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Название товара</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Цена (₽)</label>
                    <input type="number" name="price" class="form-control" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Изображение (путь)</label>
                    <input type="text" name="image" class="form-control">
                </div>
                <div class="form-group">
                    <label>Загрузить изображение</label>
                    <input type="file" name="image_file" accept="image/*" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Категория</label>
                    <input type="text" name="category" class="form-control">
                </div>
                
                <button type="submit" name="save_product" class="btn-submit">Добавить товар</button>
            </form>
        </div>
    </div>
    <?php
}
?>

