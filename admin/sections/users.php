<?php
if (!$is_admin) {
    echo "<div class='content-card'><div class='alert alert-danger'>Доступ запрещен</div></div>";
    return;
}

$current_action = $action ?: 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    if ($delete_id !== $user_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $message = "Пользователь удален";
        } catch (PDOException $e) {
            $error = "Ошибка удаления: " . $e->getMessage();
        }
    } else {
        $error = "Нельзя удалить свой аккаунт";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';

    if ($id > 0) {
        // обновление
        try {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
                $stmt->execute([$username, $email, $role, $hash, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $role, $id]);
            }
            $message = "Пользователь обновлен";
            $current_action = 'edit';
        } catch (PDOException $e) {
            $error = "Ошибка сохранения: " . $e->getMessage();
        }
    } else {
        // создание
        if ($password === '') {
            $error = "Пароль обязателен для нового пользователя";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?,?,?,?)");
                $stmt->execute([$username, $email, $hash, $role]);
                $id = $pdo->lastInsertId();
                $message = "Пользователь создан";
                $current_action = 'edit';
            } catch (PDOException $e) {
                $error = "Ошибка создания: " . $e->getMessage();
            }
        }
    }
}

if ($current_action === 'add' || $current_action === 'edit') {
    $u = [
        'username' => '',
        'email' => '',
        'role' => 'user'
    ];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
    }
    ?>
    <div class="content-card">
        <h2><?php echo $id ? 'Редактирование пользователя' : 'Создание пользователя'; ?></h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($u['username']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Роль</label>
                <select name="role" class="form-control">
                    <option value="user" <?php echo $u['role']=='user'?'selected':''; ?>>user</option>
                    <option value="moderator" <?php echo $u['role']=='moderator'?'selected':''; ?>>moderator</option>
                    <option value="admin" <?php echo $u['role']=='admin'?'selected':''; ?>>admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Пароль <?php echo $id ? '(оставьте пустым, чтобы не менять)' : ''; ?></label>
                <input type="password" name="password" class="form-control">
            </div>
            <button type="submit" name="save_user" class="btn-submit"><?php echo $id ? 'Сохранить' : 'Создать'; ?></button>
            <a href="?section=users" class="btn-action btn-cancel">Отмена</a>
        </form>
    </div>
    <?php
} else {
    try {
        $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    } catch (PDOException $e) {
        $users = [];
        $error = "Ошибка загрузки: " . $e->getMessage();
    }
    ?>
    <div class="content-card">
        <h2><i class="fas fa-users"></i> Пользователи</h2>
        <?php if (isset($message)): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <a href="?section=users&action=add" class="btn-add"><i class="fas fa-plus"></i> Добавить</a>

        <div class="table-container mt-3">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo $u['role']; ?></td>
                        <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a class="btn-action btn-edit" href="?section=users&action=edit&id=<?php echo $u['id']; ?>">Изменить</a>
                            <?php if ($u['id'] != $user_id): ?>
                            <form method="POST" class="btn-action-inline" onsubmit="return confirm('Удалить пользователя?');">
                                <input type="hidden" name="delete_id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn-action btn-delete">Удалить</button>
                            </form>
                            <?php else: ?>
                                <span class="text-muted">Это вы</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Нет пользователей</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

