<?php
$pageTitle = 'Отзывы клиентов';
include './header.php';
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

        <!-- Радио-селекторы для пагинации -->
        <input type="radio" name="page" id="page1" class="page-selector" checked>
        <input type="radio" name="page" id="page2" class="page-selector">
        <input type="radio" name="page" id="page3" class="page-selector">

        <!-- Контейнер отзывов -->
        <div class="reviews-list">
            <!-- Страница 1 -->
            <div id="page1" class="page-container">
                <?php includeReview('Анна К.', 'Москва', 'Очень понравилось качество постельного белья — мягкое, приятное к телу, цвет не выцветает даже после множества стирок. Доставка быстрая, упаковка аккуратная. Обязательно закажу ещё!', 5); ?>
                <?php includeReview('Михаил С.', 'Санкт-Петербург', 'Заказывала декоративные подушки и вазу — всё пришло в идеальном состоянии. Дизайн именно такой, как на фото. Интерьер сразу стал уютнее! Спасибо за отличный сервис.', 5); ?>
                <?php includeReview('Елена В.', 'Екатеринбург', 'Покупал светильник в спальню — стильный, современный, свет мягкий и не режет глаза. Ценник приятно удивил. Рекомендую всем, кто хочет уют без переплат.', 4); ?>
            </div>

            <!-- Страница 2 -->
            <div id="page2" class="page-container">
                <?php includeReview('Светлана Р.', 'Нижний Новгород', 'Заказывала комплект постельного белья в подарок маме — она в восторге! Ткань плотная, швы ровные, цвет не линяет. Упаковка с ленточкой — очень приятно. Спасибо за внимание к деталякам!', 5); ?>
                <?php includeReview('Артём К.', 'Краснодар', 'Купил настольную лампу — работает тихо, свет регулируется плавно, дизайн минималистичный. Вписалась идеально в мой домашний офис. Доставка за 1 день — wow!', 5); ?>
                <?php includeReview('Наталья М.', 'Челябинск', 'Халат из бамбука — мечта! Не мнётся, сохнет быстро, приятен к телу. Раньше покупала в ТЦ за 5000₽, а тут за 2500 — и качество лучше!', 5); ?>
            </div>

            <!-- Страница 3 -->
            <div id="page3" class="page-container">
                <?php includeReview('Марина Л.', 'Красноярск', 'Заказала вазы и подсвечники — всё пришло целым, упаковано как для музея! Расставила по дому — теперь гости восхищаются интерьером. Спасибо!', 5); ?>
                <?php includeReview('Алексей В.', 'Волгоград', 'Покупал плед из шерсти — тёплый, не колючий, не садится при стирке. Идеален для вечеров с книгой. Доставка на следующий день — приятный бонус.', 5); ?>
                <?php includeReview('Татьяна К.', 'Пермь', 'Очень нравится текстиль для кухни — фартуки, прихватки, салфетки. Всё в едином стиле, легко стирается, не выцветает. Делает кухню уютной!', 5); ?>
            </div>
        </div>

        <!-- Пагинация -->
        <div class="pagination">
            <label for="page1" class="page-link">1</label>
            <label for="page2" class="page-link">2</label>
            <label for="page3" class="page-link">3</label>
        </div>
    </div>
</section>

<?php
// Вспомогательная функция для вывода отзыва — БЕЗ АВАТАРКИ И БУКВ
function includeReview($name, $city, $text, $rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $starClass = ($i <= $rating) ? 'filled' : '';
        $stars .= "<span class=\"star $starClass\">★</span>";
    }
    echo <<<HTML
    <div class="review-card">
        <div class="review-content">
            <div>
                <div class="review-text">«{$text}»</div>
                <div class="review-footer">
                    <div class="review-author">
                        {$name}
                        <span>{$city}</span>
                    </div>
                    <div class="review-rating">
                        {$stars}
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
}

include './footer.php';
?>