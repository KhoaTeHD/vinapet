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
        });
    })(jQuery);

jQuery(document).ready(function($) {
    $('.size-option').on('click', function() {
        var $tieredPricing = $(this).find('.tiered-pricing');
        if ($tieredPricing.length) {
            $tieredPricing.toggle();
            // Ẩn các tiered pricing khác
            $('.tiered-pricing').not($tieredPricing).hide();
        }
    });
    
    // Ẩn khi click bên ngoài
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.size-option').length) {
            $('.tiered-pricing').hide();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Sử dụng class có sẵn từ CSS
    const description = document.querySelector('.product-description-content');
    
    if (!description) return;
    
    const fullText = description.textContent;
    const limit = 200;
    
    if (fullText.length > limit) {
        const shortText = fullText.substring(0, limit);
        
        description.innerHTML = `
            <span class="short-text">${shortText}...</span>
            <span class="full-text" style="display:none;">${fullText}</span>
            <button class="read-more-btn">Đọc thêm</button>
        `;
        
        const btn = description.querySelector('.read-more-btn');
        const short = description.querySelector('.short-text');
        const full = description.querySelector('.full-text');
        
        btn.addEventListener('click', function() {
            if (full.style.display === 'none') {
                short.style.display = 'none';
                full.style.display = 'inline';
                btn.textContent = 'Thu gọn';
            } else {
                short.style.display = 'inline';
                full.style.display = 'none';
                btn.textContent = 'Đọc thêm';
            }
        });
    }
});