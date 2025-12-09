document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.promo-banner-slider');
    const slides = document.querySelectorAll('.promo-slide');
    const dots = document.querySelectorAll('.dot');
    const arrowLeft = document.querySelector('.slider-arrow.left');
    const arrowRight = document.querySelector('.slider-arrow.right');

    let currentIndex = 0;
    const totalSlides = slides.length;

    // Функция показа слайда
    function showSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;

        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (i === index) {
                slide.classList.add('active');
            }
        });

        dots.forEach((dot, i) => {
            dot.classList.remove('active');
            if (i === index) {
                dot.classList.add('active');
            }
        });

        currentIndex = index;
    }

    // Переключение по клику на точки
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
        });
    });

    // Переключение по стрелкам
    arrowLeft.addEventListener('click', () => {
        showSlide(currentIndex - 1);
    });

    arrowRight.addEventListener('click', () => {
        showSlide(currentIndex + 1);
    });

    // Автопрокрутка (каждые 5 секунд)
    setInterval(() => {
        showSlide(currentIndex + 1);
    }, 5000);

    // Клавиатура (стрелки влево/вправо)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            showSlide(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            showSlide(currentIndex + 1);
        }
    });

    // Инициализация
    showSlide(0);
});