<?php
$pageTitle = 'Отзывы клиентов';
require_once '../config.php';
include './header.php';

// Получаем только одобренные отзывы
try {
    $reviews = $pdo->query("SELECT name, email, text, rating, created_at FROM reviews WHERE status = 'approved' ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $reviews = [];
}
?>

<!-- Подключаем стили -->
<link rel="stylesheet" href="/assets/css/reviewers.css">
<link rel="stylesheet" href="/assets/css/style.css">

<section class="all-reviews">
    <div class="container">
        <div class="section-header">
            <h2>Отзывы наших клиентов</h2>
            <p>Более 10 000 довольных покупателей по всей стране — и их число растёт каждый день!</p>
        </div>

        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="no-orders" style="padding:30px; text-align:center;">
                    Пока нет опубликованных отзывов. Будьте первым!
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card">
                        <div class="review-content">
                            <div class="review-text">«<?= htmlspecialchars($rev['text']) ?>»</div>
                            <div class="review-footer">
                                <div class="review-author">
                                    <?= htmlspecialchars($rev['name']) ?>
                                    <span><?= date('d.m.Y', strtotime($rev['created_at'])) ?></span>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <span class="star <?= $i <= (int)$rev['rating'] ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './footer.php'; ?>