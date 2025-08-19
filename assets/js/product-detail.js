(function($) {
        $(document).ready(function() {
            let currentSlide = 0;
            const totalSlides = $('.slide').length;
            
            // Hiển thị slide đầu tiên
            showSlide(0);
            
            // Xử lý click thumbnail
            $('.thumbnail').on('click', function() {
                const index = $(this).data('index');
                showSlide(index);
            });
            
            // Xử lý nút prev
            $('.prev-slide').on('click', function() {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Xử lý nút next
            $('.next-slide').on('click', function() {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Hàm hiển thị slide
            function showSlide(index) {
                currentSlide = index;
                
                // Ẩn tất cả slides
                $('.slide').removeClass('active');
                
                // Hiển thị slide được chọn
                $('.slide').eq(index).addClass('active');
                
                // Cập nhật thumbnail active
                $('.thumbnail').removeClass('active');
                $('.thumbnail').eq(index).addClass('active');
            }
            
            // Xử lý keyboard navigation
            $(document).on('keydown', function(e) {
                if (e.which === 37) { // Left arrow
                    $('.prev-slide').click();
                } else if (e.which === 39) { // Right arrow
                    $('.next-slide').click();
                }
            });
            
            // Touch/swipe support cho mobile
            let startX = 0;
            let endX = 0;
            
            $('.product-main-image').on('touchstart', function(e) {
                startX = e.originalEvent.touches[0].clientX;
            });
            
            $('.product-main-image').on('touchend', function(e) {
                endX = e.originalEvent.changedTouches[0].clientX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const diffX = startX - endX;
                const threshold = 50; // Minimum swipe distance
                
                if (Math.abs(diffX) > threshold) {
                    if (diffX > 0) {
                        // Swipe left - next slide
                        $('.next-slide').click();
                    } else {
                        // Swipe right - prev slide
                        $('.prev-slide').click();
                    }
                }
            }
            
            // Tabs
            $('.tab-btn').on('click', function() {
                const tabId = $(this).data('tab');
                
                // Xóa class active từ tất cả các tab và tab content
                $('.tab-btn').removeClass('active');
                $('.tab-content').removeClass('active');
                
                // Thêm class active cho tab và tab content được chọn
                $(this).addClass('active');
                $('#' + tabId).addClass('active');
            });
            
            // Product Variants
            $('.variant-option').on('click', function() {
                $('.variant-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Product Sizes
            $('.size-option').on('click', function() {
                $('.size-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // Chọn variant và size đầu tiên mặc định
            $('.variant-option:first-child').addClass('selected');
            $('.size-option:first-child').addClass('selected');
        });
    })(jQuery);