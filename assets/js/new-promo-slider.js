/**
 * Advanced Promo Slider with Arrow Navigation
 * - Fully responsive across all devices
 * - Left/right arrow navigation
 * - Auto-slide functionality with pause on hover
 * - Smooth transitions between slides
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the promo slider
    initPromoSlider();
    
    function initPromoSlider() {
        const slider = document.querySelector('.promo-slider');
        if (!slider) return;
        
        const slides = slider.querySelectorAll('.promo-slide');
        const totalSlides = slides.length;
        if (totalSlides === 0) return;
        
        // Create navigation arrows
        const prevArrow = document.createElement('button');
        prevArrow.className = 'promo-slider-arrow promo-slider-prev';
        prevArrow.innerHTML = '<i class="fas fa-chevron-left"></i>';
        
        const nextArrow = document.createElement('button');
        nextArrow.className = 'promo-slider-arrow promo-slider-next';
        nextArrow.innerHTML = '<i class="fas fa-chevron-right"></i>';
        
        slider.appendChild(prevArrow);
        slider.appendChild(nextArrow);
        
        // Set up initial slide
        let currentSlide = 0;
        showSlide(currentSlide);
        
        // Set up auto-sliding
        let slideInterval = startAutoSlide();
        
        // Add event listeners for navigation
        prevArrow.addEventListener('click', function() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
            resetAutoSlide();
        });
        
        nextArrow.addEventListener('click', function() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
            resetAutoSlide();
        });
        
        // Pause auto-sliding when hovering over the slider
        slider.addEventListener('mouseenter', function() {
            clearInterval(slideInterval);
        });
        
        slider.addEventListener('mouseleave', function() {
            slideInterval = startAutoSlide();
        });
        
        // Handle touch events for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        slider.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        slider.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.classList.add('active');
                }
            });
            console.log("Moving to promo slide " + index);
        }
        
        function startAutoSlide() {
            return setInterval(function() {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            }, 4000); // Change slide every 4 seconds
        }
        
        function resetAutoSlide() {
            clearInterval(slideInterval);
            slideInterval = startAutoSlide();
        }
        
        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchEndX < touchStartX - swipeThreshold) {
                // Swipe left - go to next slide
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
                resetAutoSlide();
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right - go to previous slide
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
                resetAutoSlide();
            }
        }
    }
});