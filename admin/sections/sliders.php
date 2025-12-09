<?php
// admin/sections/sliders.php

$current_action = $action ?: 'list';

// Только администратор может управлять слайдерами
if (!$is_admin) {
    echo "<div class='content-card'><div class='alert alert-danger'>Доступ только для администратора</div></div>";
    return;
}

// Удаление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM promo_sliders WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Слайд удален";
    } catch (PDOException $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
    }
}

// Сохранение
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_slider'])) {
    $tag = trim($_POST['tag']);
    $tag_class = trim($_POST['tag_class']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $button_text = trim($_POST['button_text']);
    $button_link = trim($_POST['button_link']);
    $image_url = trim($_POST['image_url']);
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $sql = "UPDATE promo_sliders 
                SET tag=?, tag_class=?, title=?, description=?, button_text=?, button_link=?, image_url=?, sort_order=?, is_active=? 
                WHERE id=?";
        $params = [$tag,$tag_class,$title,$description,$button_text,$button_link,$image_url,$sort_order,$is_active,$id];
        $msg = "Слайд обновлен";
    } else {
        $sql = "INSERT INTO promo_sliders (tag, tag_class, title, description, button_text, button_link, image_url, sort_order, is_active) 
                VALUES (?,?,?,?,?,?,?,?,?)";
        $params = [$tag,$tag_class,$title,$description,$button_text,$button_link,$image_url,$sort_order,$is_active];
        $msg = "Слайд добавлен";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($id == 0) $id = $pdo->lastInsertId();
        $message = $msg;
        $current_action = 'edit';
    } catch (PDOException $e) {
        $error = "Ошибка сохранения: " . $e->getMessage();
    }
}

if ($current_action === 'add' || $current_action === 'edit') {
    $slider = [
        'tag' => '',
        'tag_class' => 'default',
        'title' => '',
        'description' => '',
        'button_text' => '',
        'button_link' => '',
        'image_url' => '',
        'sort_order' => 0,
        'is_active' => 1,
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM promo_sliders WHERE id = ?");
        $stmt->execute([$id]);
        $slider = $stmt->fetch();
    }
    ?>
    <div class="content-card">
        <h2><?php echo $id ? 'Редактирование слайда' : 'Создание слайда'; ?></h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Метка (tag)</label>
                <input type="text" name="tag" class="form-control" value="<?php echo htmlspecialchars($slider['tag']); ?>" required>
            </div>
            <div class="form-group">
                <label>Класс метки (tag_class)</label>
                <input type="text" name="tag_class" class="form-control" value="<?php echo htmlspecialchars($slider['tag_class']); ?>" placeholder="new / hot / best / default">
            </div>
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($slider['title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($slider['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Текст кнопки</label>
                <input type="text" name="button_text" class="form-control" value="<?php echo htmlspecialchars($slider['button_text']); ?>">
            </div>
            <div class="form-group">
                <label>Ссылка кнопки</label>
                <input type="text" name="button_link" class="form-control" value="<?php echo htmlspecialchars($slider['button_link']); ?>" required>
            </div>
            <div class="form-group">
                <label>Изображение (путь, например assets/img/slide1.jpg)</label>
                <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($slider['image_url']); ?>" required>
            </div>
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$slider['sort_order']; ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" <?php echo $slider['is_active'] ? 'checked' : ''; ?>> Активен</label>
            </div>
            <button type="submit" name="save_slider" class="btn-submit"><?php echo $id ? 'Сохранить' : 'Создать'; ?></button>
            <a href="?section=sliders" class="btn-action" style="background:#95a5a6;">Отмена</a>
        </form>
    </div>
    <?php
} else {
    try {
        $sliders = $pdo->query("SELECT * FROM promo_sliders ORDER BY sort_order ASC")->fetchAll();
    } catch (PDOException $e) {
        $sliders = [];
        $error = "Ошибка при загрузке слайдов: " . $e->getMessage();
    }
    ?>
    <div class="content-card">
        <h2><i class="fas fa-images"></i> Слайдеры</h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <a href="?section=sliders&action=add" class="btn-add"><i class="fas fa-plus"></i> Добавить слайд</a>

        <div class="table-container mt-3">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Метка</th>
                        <th>Заголовок</th>
                        <th>Ссылка</th>
                        <th>Порядок</th>
                        <th>Активен</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sliders as $s): ?>
                    <tr>
                        <td><?php echo $s['id']; ?></td>
                        <td><span style="background:#eee;padding:3px 6px;border-radius:4px;"><?php echo htmlspecialchars($s['tag']); ?></span></td>
                        <td><?php echo htmlspecialchars(mb_substr($s['title'],0,50)); ?></td>
                        <td><?php echo htmlspecialchars($s['button_link']); ?></td>
                        <td><?php echo $s['sort_order']; ?></td>
                        <td><?php echo $s['is_active'] ? 'Да' : 'Нет'; ?></td>
                        <td>
                            <a class="btn-action btn-edit" href="?section=sliders&action=edit&id=<?php echo $s['id']; ?>">Изменить</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Удалить слайд?');">
                                <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sliders)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Слайдов нет</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

