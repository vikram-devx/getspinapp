/**
 * Promotional Slider Functionality
 * - Handles slide transitions with fade effect
 * - Auto-slides every 4 seconds with manual controls
 * - Touch-enabled for mobile swipe
 */
$(document).ready(function() {
    // Initialize promo slider if it exists
    initPromoSlider();
    
    function initPromoSlider() {
        if ($('.promo-slides').length) {
            var currentSlide = 0;
            var slides = $('.promo-slide');
            var totalSlides = slides.length;
            var autoSlideInterval;
            
            console.log("Found " + totalSlides + " promo slides");
            
            // Initial setup - show first slide
            slides.eq(0).addClass('active');
            
            // Function to show a specific slide
            function showSlide(index) {
                // Handle circular navigation
                if (index < 0) {
                    index = totalSlides - 1;
                } else if (index >= totalSlides) {
                    index = 0;
                }
                
                console.log("Moving to promo slide " + index);
                
                // Update current slide
                currentSlide = index;
                
                // Hide all slides and show the current one
                slides.removeClass('active');
                slides.eq(index).addClass('active');
                
                // Update dot indicators
                $('.promo-slider-dot').removeClass('active');
                $('.promo-slider-dot[data-slide="' + index + '"]').addClass('active');
            }
            
            // Auto-slide functionality
            function startAutoSlide() {
                autoSlideInterval = setInterval(function() {
                    showSlide(currentSlide + 1);
                }, 4000); // Change slide every 4 seconds
            }
            
            function resetAutoSlide() {
                clearInterval(autoSlideInterval);
                startAutoSlide();
            }
            
            // Start auto-sliding
            startAutoSlide();
            
            // Handle dot click
            $(document).on('click', '.promo-slider-dot', function() {
                var index = parseInt($(this).data('slide'));
                showSlide(index);
                resetAutoSlide();
            });
            
            // Add touch swipe functionality for mobile
            var touchStartX = 0;
            var touchEndX = 0;
            
            $('.promo-slides').on('touchstart', function(e) {
                touchStartX = e.originalEvent.touches[0].clientX;
            });
            
            $('.promo-slides').on('touchend', function(e) {
                touchEndX = e.originalEvent.changedTouches[0].clientX;
                handleSwipe();
            });
            
            function handleSwipe() {
                var swipeThreshold = 50;
                if (touchEndX < touchStartX - swipeThreshold) {
                    // Swiped left, go to next slide
                    showSlide(currentSlide + 1);
                    resetAutoSlide();
                } else if (touchEndX > touchStartX + swipeThreshold) {
                    // Swiped right, go to previous slide
                    showSlide(currentSlide - 1);
                    resetAutoSlide();
                }
            }
        }
    }
});