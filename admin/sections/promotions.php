<?php
// admin/sections/promotions.php

$current_action = $action ?: 'list';
$uploadDir = realpath(__DIR__ . '/../../assets/img');

// Подтянуть товары для привязки к акции
$allProducts = [];
try {
    $allProducts = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    $allProducts = [];
}

// Только администратор может управлять акциями
if (!$is_admin) {
    echo "<div class='content-card'><div class='alert alert-danger'>Доступ только для администратора</div></div>";
    return;
}

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Акция удалена";
    } catch (PDOException $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
    }
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_promo'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $discount_type = $_POST['discount_type'];
    $discount_value = $_POST['discount_value'] !== '' ? (float)$_POST['discount_value'] : null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $category_filter = trim($_POST['category_filter']);
    if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
        $ids = array_filter(
            array_map('intval', $_POST['product_ids']),
            function ($v) { return $v > 0; }
        );
        $product_ids = implode(',', $ids);
    } else {
        $product_ids = trim($_POST['product_ids']);
    }
    $image = trim($_POST['image']);
    $image = $image ? basename($image) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK && $uploadDir) {
        $safeName = basename($_FILES['image_file']['name']);
        $target = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
            $image = $safeName;
        }
    }

    if ($id > 0) {
        $sql = "UPDATE promotions 
                SET title=?, description=?, discount_type=?, discount_value=?, start_date=?, end_date=?, 
                    category_filter=?, product_ids=?, image=?, is_active=? 
                WHERE id=?";
        $params = [$title,$description,$discount_type,$discount_value,$start_date,$end_date,
                   $category_filter,$product_ids,$image,$is_active,$id];
        $msg = "Акция обновлена";
    } else {
        $sql = "INSERT INTO promotions (title, description, discount_type, discount_value, start_date, end_date, category_filter, product_ids, image, is_active) 
                VALUES (?,?,?,?,?,?,?,?,?,?)";
        $params = [$title,$description,$discount_type,$discount_value,$start_date,$end_date,
                   $category_filter,$product_ids,$image,$is_active];
        $msg = "Акция добавлена";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($id == 0) {
            $id = $pdo->lastInsertId();
        }
        $message = $msg;
        $current_action = 'edit';
    } catch (PDOException $e) {
        $error = "Ошибка сохранения: " . $e->getMessage();
    }
}

if ($current_action === 'add' || $current_action === 'edit') {
    $promo = [
        'title' => '',
        'description' => '',
        'discount_type' => 'percentage',
        'discount_value' => '',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+30 days')),
        'category_filter' => '',
        'product_ids' => '',
        'image' => '',
        'is_active' => 1,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $promo = $stmt->fetch();
    }
    ?>
    <div class="content-card">
        <h2><?php echo $id ? 'Редактирование акции' : 'Создание акции'; ?></h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($promo['title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($promo['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Тип скидки</label>
                <select name="discount_type" class="form-control">
                    <option value="percentage" <?php echo $promo['discount_type']=='percentage'?'selected':''; ?>>Процент</option>
                    <option value="fixed" <?php echo $promo['discount_type']=='fixed'?'selected':''; ?>>Фиксированная сумма</option>
                    <option value="special" <?php echo $promo['discount_type']=='special'?'selected':''; ?>>Спец-логика</option>
                </select>
            </div>
            <div class="form-group">
                <label>Величина скидки</label>
                <input type="number" step="0.01" name="discount_value" class="form-control" value="<?php echo $promo['discount_value']; ?>">
            </div>
            <div class="form-group">
                <label>Дата начала</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $promo['start_date']; ?>" required>
            </div>
            <div class="form-group">
                <label>Дата окончания</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $promo['end_date']; ?>" required>
            </div>
            <div class="form-group">
                <label>Категория (фильтр)</label>
                <input type="text" name="category_filter" class="form-control" value="<?php echo htmlspecialchars($promo['category_filter']); ?>">
            </div>
            <div class="form-group">
                <label>Товары в акции</label>
                <select name="product_ids[]" class="form-control" multiple size="6" style="height:auto;">
                    <?php
                    $selectedIds = array_filter(array_map('intval', explode(',', $promo['product_ids'] ?? '')));
                    foreach ($allProducts as $p):
                        $sel = in_array((int)$p['id'], $selectedIds) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $sel; ?>>
                            #<?php echo $p['id']; ?> — <?php echo htmlspecialchars($p['name']); ?> (<?php echo number_format($p['price'], 0, '', ' '); ?> ₽)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Удерживайте Ctrl/⌘ для выбора нескольких. Можно не выбирать — акция будет без привязанных товаров.</small>
            </div>
            <div class="form-group">
                <label>Изображение (имя файла в assets/img)</label>
                <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($promo['image']); ?>">
            </div>
            <div class="form-group">
                <label>Загрузить изображение</label>
                <input type="file" name="image_file" accept="image/*" class="form-control">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" <?php echo $promo['is_active'] ? 'checked' : ''; ?>> Активна</label>
            </div>
            <button type="submit" name="save_promo" class="btn-submit"><?php echo $id ? 'Сохранить' : 'Создать'; ?></button>
            <a href="?section=promotions" class="btn-action" style="background:#95a5a6;">Отмена</a>
        </form>
    </div>
    <?php
} else {
    try {
        $promos = $pdo->query("SELECT * FROM promotions ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $e) {
        $promos = [];
        $error = "Ошибка при загрузке акций: " . $e->getMessage();
    }
    ?>
    <div class="content-card">
        <h2><i class="fas fa-percent"></i> Акции</h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <a href="?section=promotions&action=add" class="btn-add"><i class="fas fa-plus"></i> Добавить акцию</a>

        <div class="table-container mt-3">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Тип</th>
                        <th>Скидка</th>
                        <th>Период</th>
                        <th>Активна</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promos as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['title']); ?></td>
                        <td><?php echo $p['discount_type']; ?></td>
                        <td>
                            <?php 
                            if ($p['discount_type']=='percentage') echo $p['discount_value'].'%';
                            elseif ($p['discount_type']=='fixed') echo $p['discount_value'].' ₽';
                            else echo 'спец';
                            ?>
                        </td>
                        <td><?php echo $p['start_date'].' — '.$p['end_date']; ?></td>
                        <td><?php echo $p['is_active'] ? 'Да' : 'Нет'; ?></td>
                        <td>
                            <a class="btn-action btn-edit" href="?section=promotions&action=edit&id=<?php echo $p['id']; ?>">Изменить</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить акцию?');">
                                <input type="hidden" name="delete_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($promos)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Акций нет</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

