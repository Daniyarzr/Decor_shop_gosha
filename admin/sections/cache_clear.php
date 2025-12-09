<?php
// admin/sections/cache_clear.php
require_once __DIR__ . '/../../includes/cache.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cache'])) {
    try {
        Cache::clear();
        $message = "Кэш успешно очищен";
    } catch (Exception $e) {
        $error = "Ошибка при очистке кэша: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clean_cache'])) {
    try {
        Cache::clean();
        $message = "Устаревшие записи кэша удалены";
    } catch (Exception $e) {
        $error = "Ошибка при очистке: " . $e->getMessage();
    }
}

// Получаем статистику кэша
$cacheDir = __DIR__ . '/../../cache/data/';
$cacheFiles = [];
$totalSize = 0;
if (file_exists($cacheDir)) {
    $files = glob($cacheDir . '*.cache');
    foreach ($files as $file) {
        $data = unserialize(file_get_contents($file));
        $size = filesize($file);
        $totalSize += $size;
        $cacheFiles[] = [
            'file' => basename($file),
            'size' => $size,
            'expires' => $data['expires'],
            'created' => $data['created'],
            'is_expired' => time() > $data['expires']
        ];
    }
}
?>

<div class="content-card">
    <h2><i class="fas fa-database"></i> Управление кэшем</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div style="margin-bottom: 30px;">
        <h3>Статистика кэша</h3>
        <p><strong>Всего записей:</strong> <?php echo count($cacheFiles); ?></p>
        <p><strong>Общий размер:</strong> <?php echo number_format($totalSize / 1024, 2); ?> KB</p>
        <p><strong>Устаревших записей:</strong> <?php echo count(array_filter($cacheFiles, function($f) { return $f['is_expired']; })); ?></p>
    </div>
    
    <div style="margin-bottom: 30px;">
        <h3>Действия</h3>
        <form method="POST" style="display: inline-block; margin-right: 10px;">
            <button type="submit" name="clear_cache" class="btn-submit" onclick="return confirm('Вы уверены, что хотите очистить весь кэш?');">
                <i class="fas fa-trash"></i> Очистить весь кэш
            </button>
        </form>
        
        <form method="POST" style="display: inline-block;">
            <button type="submit" name="clean_cache" class="btn-submit" style="background: #f39c12;">
                <i class="fas fa-broom"></i> Удалить устаревшие записи
            </button>
        </form>
    </div>
    
    <?php if (!empty($cacheFiles)): ?>
        <div class="table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Файл</th>
                        <th>Размер</th>
                        <th>Создан</th>
                        <th>Истекает</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cacheFiles as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['file']); ?></td>
                            <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                            <td><?php echo date('d.m.Y H:i:s', $file['created']); ?></td>
                            <td><?php echo date('d.m.Y H:i:s', $file['expires']); ?></td>
                            <td>
                                <?php if ($file['is_expired']): ?>
                                    <span style="color: #e74c3c;">Устарел</span>
                                <?php else: ?>
                                    <span style="color: #27ae60;">Активен</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">Кэш пуст</p>
    <?php endif; ?>
</div>

