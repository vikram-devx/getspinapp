/**
 * Advanced Promo Slider with Arrow Navigation
 * - Fully responsive across all devices
 * - Left/right arrow navigation
 * - Auto-slide functionality with pause on hover
 * - Smooth transitions between slides
 * - Perfect looping through slides
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
        prevArrow.setAttribute('aria-label', 'Previous slide');
        
        const nextArrow = document.createElement('button');
        nextArrow.className = 'promo-slider-arrow promo-slider-next';
        nextArrow.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextArrow.setAttribute('aria-label', 'Next slide');
        
        slider.appendChild(prevArrow);
        slider.appendChild(nextArrow);
        
        // Set up initial slide
        let currentSlide = 0;
        showSlide(currentSlide);
        
        // Set up auto-sliding
        let slideInterval = startAutoSlide();
        
        // Add event listeners for navigation
        prevArrow.addEventListener('click', function() {
            goToPrevSlide();
        });
        
        nextArrow.addEventListener('click', function() {
            goToNextSlide();
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
            // Make sure index is within valid range
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            
            // Hide all slides and show only the active one
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
            });
            
            slides[index].classList.add('active');
            currentSlide = index; // Update current slide position
            console.log("Moving to promo slide " + index);
        }
        
        function goToNextSlide() {
            showSlide((currentSlide + 1) % totalSlides);
            resetAutoSlide();
        }
        
        function goToPrevSlide() {
            showSlide((currentSlide - 1 + totalSlides) % totalSlides);
            resetAutoSlide();
        }
        
        function startAutoSlide() {
            return setInterval(function() {
                goToNextSlide();
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
                goToNextSlide();
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right - go to previous slide
                goToPrevSlide();
            }
        }
    }
});