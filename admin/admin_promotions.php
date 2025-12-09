<?php
// Включите буферизацию вывода в самом начале
ob_start();
session_start();
require_once '../config.php';

require_once 'check_admin.php';

// Обработка добавления/редактирования акции
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'] ?: NULL;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $category_filter = $_POST['category_filter'] ?: NULL;
    $product_ids = trim($_POST['product_ids']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id > 0) {
        // Обновление существующей акции
        $stmt = $pdo->prepare("UPDATE promotions SET title = ?, description = ?, discount_type = ?, discount_value = ?, start_date = ?, end_date = ?, category_filter = ?, product_ids = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $discount_type, $discount_value, $start_date, $end_date, $category_filter, $product_ids, $is_active, $id]);
    } else {
        // Добавление новой акции
        $stmt = $pdo->prepare("INSERT INTO promotions (title, description, discount_type, discount_value, start_date, end_date, category_filter, product_ids, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $discount_type, $discount_value, $start_date, $end_date, $category_filter, $product_ids, $is_active]);
    }
    
    // Очистите буфер перед редиректом
    ob_end_clean();
    header('Location: admin_promotions.php?success=1');
    exit;
}

// Получение списка акций для админки
$stmt = $pdo->query("SELECT * FROM promotions ORDER BY start_date DESC");
$promotions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление акциями — Админка</title>
    <style>
        /* Стили для админки */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f25081; color: white; }
        tr:hover { background: #f9f9f9; }
        .btn { padding: 8px 15px; background: #f25081; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #d93a6a; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Управление акциями</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success">Акция успешно сохранена!</div>
        <?php endif; ?>
        
        <h2>Список акций</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Скидка</th>
                    <th>Даты</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $promotion): ?>
                <tr>
                    <td><?= $promotion['id'] ?></td>
                    <td><?= htmlspecialchars($promotion['title']) ?></td>
                    <td>
                        <?= $promotion['discount_type'] === 'percentage' ? "-{$promotion['discount_value']}%" : '' ?>
                        <?= $promotion['discount_type'] === 'fixed' ? "-{$promotion['discount_value']} ₽" : '' ?>
                        <?= $promotion['discount_type'] === 'special' ? 'Спеццена' : '' ?>
                    </td>
                    <td><?= date('d.m.Y', strtotime($promotion['start_date'])) ?> - <?= date('d.m.Y', strtotime($promotion['end_date'])) ?></td>
                    <td><?= $promotion['is_active'] ? 'Активна' : 'Не активна' ?></td>
                    <td>
                        <a href="#" class="btn" onclick="editPromotion(<?= $promotion['id'] ?>)">Редактировать</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Добавить/редактировать акцию</h2>
        <form method="POST" id="promotion-form">
            <input type="hidden" name="id" id="promotion-id" value="0">
            
            <div class="form-group">
                <label for="title">Название акции *</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Описание *</label>
                <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="discount_type">Тип скидки *</label>
                <select id="discount_type" name="discount_type" class="form-control" required>
                    <option value="percentage">Процентная скидка (%)</option>
                    <option value="fixed">Фиксированная скидка (₽)</option>
                    <option value="special">Специальное предложение</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="discount_value">Размер скидки *</label>
                <input type="number" id="discount_value" name="discount_value" class="form-control" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="start_date">Дата начала *</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">Дата окончания *</label>
                <input type="date" id="end_date" name="end_date" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="category_filter">Категория (опционально)</label>
                <select id="category_filter" name="category_filter" class="form-control">
                    <option value="">Все категории</option>
                    <?php
                    $categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category")->fetchAll();
                    foreach ($categories as $category):
                    ?>
                        <option value="<?= htmlspecialchars($category['category']) ?>"><?= htmlspecialchars($category['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="product_ids">ID товаров через запятую (опционально)</label>
                <input type="text" id="product_ids" name="product_ids" class="form-control" placeholder="1,2,3,4">
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked> Активна
                </label>
            </div>
            
            <button type="submit" class="btn">Сохранить акцию</button>
        </form>
    </div>
    
    <script>
        // Функция для редактирования акции
        function editPromotion(id) {
            fetch('get_promotion.php?id=' + id)
                .then(response => response.json())
                .then(promotion => {
                    document.getElementById('promotion-id').value = promotion.id;
                    document.getElementById('title').value = promotion.title;
                    document.getElementById('description').value = promotion.description;
                    document.getElementById('discount_type').value = promotion.discount_type;
                    document.getElementById('discount_value').value = promotion.discount_value;
                    document.getElementById('start_date').value = promotion.start_date;
                    document.getElementById('end_date').value = promotion.end_date;
                    document.getElementById('category_filter').value = promotion.category_filter || '';
                    document.getElementById('product_ids').value = promotion.product_ids || '';
                    document.getElementById('is_active').checked = promotion.is_active == 1;
                    
                    // Прокрутка к форме
                    document.getElementById('promotion-form').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Очистка формы для добавления новой акции
        document.querySelector('form').addEventListener('reset', function() {
            document.getElementById('promotion-id').value = '0';
        });
    </script>
</body>
</html>
<?php
// Завершите буферизацию и отправьте вывод
ob_end_flush();
?>