let currentIndex = 0;
const slides = document.querySelectorAll('.slide');
const totalSlides = slides.length;

// İlk başta aktif olan slide'ı ve çevresindeki slide'ları hizala
function initializeSlides() {
    slides.forEach((slide, i) => {
        slide.style.transform = `translateX(${(i - currentIndex) * 110}%)`;
        slide.classList.remove('active');
    });
    slides[currentIndex].classList.add('active');
}

// Slide'ı gösterirken döngüsel hale getir
function showSlides(index) {
    if (index >= totalSlides) {
        currentIndex = 0; // Eğer son slide'a gelindiyse, başa dön
    } else if (index < 0) {
        currentIndex = totalSlides - 1; // Eğer ilk slide'dan geri gidiliyorsa, sona dön
    } else {
        currentIndex = index; // Normal ilerleme
    }
    
    slides.forEach((slide, i) => {
        let position = (i - currentIndex) * 60; // Slide'ları 110%'lik bir mesafede hizala
        if (i === currentIndex) {
            slide.classList.add('active'); // Ortadaki slide
        } else {
            slide.classList.remove('active');
        }
        slide.style.transform = `translateX(${position}%)`;
    });
}

function changeSlide(direction) {
    showSlides(currentIndex + direction);
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSlides();
    showSlides(currentIndex);
});
