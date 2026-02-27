/**
 * JFXTECH Hero Carousel - Vanilla JS
 */
document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.carousel-slide');
    const indicators = document.querySelectorAll('.carousel-indicator');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    const playPauseBtn = document.getElementById('carousel-play-pause');

    if (!slides.length) return;

    let current = 0;
    let isPlaying = true;
    let timer = null;

    function showSlide(index) {
        slides.forEach(function (slide, i) {
            slide.classList.remove('active');
            if (indicators[i]) {
                indicators[i].classList.remove('w-12', 'bg-white');
                indicators[i].classList.add('w-4', 'bg-gray-600');
            }
        });
        slides[index].classList.add('active');
        if (indicators[index]) {
            indicators[index].classList.remove('w-4', 'bg-gray-600');
            indicators[index].classList.add('w-12', 'bg-white');
        }
        current = index;
    }

    function nextSlide() {
        showSlide((current + 1) % slides.length);
    }

    function prevSlide() {
        showSlide((current - 1 + slides.length) % slides.length);
    }

    function startAutoplay() {
        if (timer) clearInterval(timer);
        timer = setInterval(nextSlide, 5000);
        isPlaying = true;
        updatePlayPauseIcon();
    }

    function stopAutoplay() {
        if (timer) clearInterval(timer);
        timer = null;
        isPlaying = false;
        updatePlayPauseIcon();
    }

    function updatePlayPauseIcon() {
        if (!playPauseBtn) return;
        if (isPlaying) {
            playPauseBtn.innerHTML = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';
        } else {
            playPauseBtn.innerHTML = '<svg class="w-4 h-4 ml-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="6 3 20 12 6 21 6 3"/></svg>';
        }
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { prevSlide(); if (isPlaying) startAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { nextSlide(); if (isPlaying) startAutoplay(); });
    if (playPauseBtn) playPauseBtn.addEventListener('click', function () { isPlaying ? stopAutoplay() : startAutoplay(); });

    indicators.forEach(function (indicator, index) {
        indicator.addEventListener('click', function () {
            showSlide(index);
            if (isPlaying) startAutoplay();
        });
    });

    // Initialize
    showSlide(0);
    startAutoplay();
});
