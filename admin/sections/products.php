<?php
$current_action = $action ?: 'list';
$uploadDir = realpath(__DIR__ . '/../../assets/img');

if (!$is_admin) {
    echo "<div class='content-card'><div class='alert alert-danger'>Доступ только для администратора</div></div>";
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Товар успешно удален";
        require_once __DIR__ . '/../../includes/cache.php';
        Cache::delete('catalog_categories');
    } catch (PDOException $e) {
        $error = "Ошибка при удалении: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $image = trim($_POST['image']);
    $image = $image ? basename($image) : '';
    $category = !empty(trim($_POST['category_new'] ?? '')) 
        ? trim($_POST['category_new']) 
        : trim($_POST['category'] ?? '');

    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK && $uploadDir) {
        $safeName = basename($_FILES['image_file']['name']);
        $target = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
            $image = $safeName;
        }
    }
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ?, image = ?, category = ? WHERE id = ?");
        $stmt->execute([$name, $price, $image, $category, $id]);
        $message = "Товар успешно обновлен";
        require_once __DIR__ . '/../../includes/cache.php';
        Cache::delete('catalog_categories');
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, price, image, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $image, $category]);
        $id = $pdo->lastInsertId();
        $message = "Товар успешно добавлен";
        require_once __DIR__ . '/../../includes/cache.php';
        Cache::delete('catalog_categories');
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
        
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateCategory()">
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
                <?php
                // Получаем все уникальные категории из БД
                try {
                    $categories_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
                    $existing_categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $e) {
                    $existing_categories = [];
                }
                ?>
                <select name="category" class="form-control" id="category-select">
                    <option value="">-- Выберите категорию --</option>
                    <?php foreach ($existing_categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" 
                                <?php echo (isset($product['category']) && $product['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Или введите новую категорию ниже</small>
                <input type="text" name="category_new" class="form-control category-new-input" 
                       placeholder="Новая категория (если нет в списке)">
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const categorySelect = document.querySelector('select[name="category"]');
                        const categoryNewInput = document.querySelector('input[name="category_new"]');
                        
                        if (categorySelect && categoryNewInput) {
                            if (categorySelect.value === '') {
                                categoryNewInput.style.display = 'block';
                            } else {
                                categoryNewInput.style.display = 'none';
                            }
                            
                            categorySelect.addEventListener('change', function() {
                                if (this.value === '') {
                                    categoryNewInput.style.display = 'block';
                                    categoryNewInput.required = true;
                                } else {
                                    categoryNewInput.style.display = 'none';
                                    categoryNewInput.required = false;
                                    categoryNewInput.value = '';
                                }
                            });
                            
                            categoryNewInput.addEventListener('input', function() {
                                if (this.value.trim() !== '') {
                                    categorySelect.value = '';
                                    categorySelect.required = false;
                                }
                            });
                        }
                        
                        window.validateCategory = function() {
                            const select = document.getElementById('category-select');
                            const newInput = document.querySelector('input[name="category_new"]');
                            
                            if ((!select || select.value === '') && (!newInput || newInput.value.trim() === '')) {
                                alert('Пожалуйста, выберите категорию из списка или введите новую');
                                return false;
                            }
                            return true;
                        };
                    });
                </script>
            </div>
            
            <button type="submit" name="save_product" class="btn-submit">
                <?php echo $id > 0 ? 'Сохранить изменения' : 'Добавить товар'; ?>
            </button>
            <a href="?section=products" class="btn-action btn-cancel">Отмена</a>
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
                                         alt="Изображение" class="product-image-admin">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['price'], 0, '', ' '); ?> ₽</td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>
                                <a href="?section=products&action=edit&id=<?php echo $product['id']; ?>" 
                                   class="btn-action btn-edit">Изменить</a>
                                <form method="POST" class="btn-action-inline" 
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

