<?php
$current_action = $action ?: 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'], $_POST['status']) && !isset($_POST['save_review'])) {
    $review_id = (int)$_POST['review_id'];
    $status = $_POST['status'];
    $allowed = ['pending','approved','rejected'];
    if (in_array($status, $allowed, true)) {
        try {
        $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        $stmt->execute([$status, $review_id]);
        $message = "Статус обновлен";
        require_once __DIR__ . '/../../includes/cache.php';
        Cache::delete('home_reviews');
        } catch (PDOException $e) {
            $error = "Ошибка обновления: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
    $review_id = (int)$_POST['review_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $text = trim($_POST['text']);
    $rating = (int)$_POST['rating'];
    $status = $_POST['status'];
    if ($rating < 1) $rating = 1;
    if ($rating > 5) $rating = 5;
    $allowed = ['pending','approved','rejected'];
    if (!in_array($status, $allowed, true)) $status = 'pending';

    try {
        $stmt = $pdo->prepare("UPDATE reviews SET name=?, email=?, text=?, rating=?, status=? WHERE id=?");
        $stmt->execute([$name, $email, $text, $rating, $status, $review_id]);
        $message = "Отзыв обновлен";
        require_once __DIR__ . '/../../includes/cache.php';
        Cache::delete('home_reviews');
        $current_action = 'list';
    } catch (PDOException $e) {
        $error = "Ошибка сохранения: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Отзыв удален";
    } catch (PDOException $e) {
        $error = "Ошибка удаления: " . $e->getMessage();
    }
}

if ($current_action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$id]);
    $rev = $stmt->fetch();
    if (!$rev) {
        $error = "Отзыв не найден";
        $current_action = 'list';
    }
}

try {
    $reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
    $error = "Таблица отзывов недоступна. Создайте таблицу `reviews` с полями id, user_id, name, email, text, rating, status, created_at. Ошибка: " . $e->getMessage();
}
?>

<div class="content-card">
    <h2><i class="fas fa-comments"></i> Отзывы</h2>
    <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($current_action === 'edit' && isset($rev)): ?>
        <h3>Редактирование отзыва #<?php echo $rev['id']; ?></h3>
        <form method="POST">
            <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
            <div class="form-group">
                <label>Имя</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($rev['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($rev['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Оценка</label>
                <input type="number" name="rating" class="form-control" min="1" max="5" value="<?php echo (int)$rev['rating']; ?>" required>
            </div>
            <div class="form-group">
                <label>Текст</label>
                <textarea name="text" class="form-control" rows="4" required><?php echo htmlspecialchars($rev['text']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Статус</label>
                <select name="status" class="form-control">
                    <option value="pending" <?php echo $rev['status']=='pending'?'selected':''; ?>>pending</option>
                    <option value="approved" <?php echo $rev['status']=='approved'?'selected':''; ?>>approved</option>
                    <option value="rejected" <?php echo $rev['status']=='rejected'?'selected':''; ?>>rejected</option>
                </select>
            </div>
            <button type="submit" name="save_review" class="btn-submit">Сохранить</button>
            <a class="btn-action btn-cancel" href="?section=reviews">Отмена</a>
        </form>
        <hr class="hr-margin">
    <?php endif; ?>

    <div class="table-container mt-3">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Оценка</th>
                    <th>Текст</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                    <td><?php echo (int)$r['rating']; ?></td>
                    <td><?php echo htmlspecialchars(mb_substr($r['text'], 0, 120)); ?></td>
                    <td>
                        <form method="POST" class="form-inline-flex">
                            <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
                            <select name="status" class="form-control select-min-width">
                                <option value="pending" <?php echo $r['status']=='pending'?'selected':''; ?>>pending</option>
                                <option value="approved" <?php echo $r['status']=='approved'?'selected':''; ?>>approved</option>
                                <option value="rejected" <?php echo $r['status']=='rejected'?'selected':''; ?>>rejected</option>
                            </select>
                            <button type="submit" class="btn-action btn-edit">OK</button>
                        </form>
                    </td>
                    <td><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
                    <td>
                        <a class="btn-action btn-edit" href="?section=reviews&action=edit&id=<?php echo $r['id']; ?>">Изменить</a>
                        <form method="POST" class="btn-action-inline" onsubmit="return confirm('Удалить отзыв?');">
                            <input type="hidden" name="delete_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn-action btn-delete">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Нет отзывов или таблица не создана</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

