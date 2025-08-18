(function($) {
        $(document).ready(function() {
            // Product Image Slider
            let currentSlide = 0;
            const totalSlides = $('.slide').length;
            
            // Hiển thị slide hiện tại
            function showSlide(index) {
                // Ẩn tất cả các slide
                $('.slide').css('display', 'none');
                // Hiển thị slide hiện tại
                $('.slide').eq(index).css('display', 'block');
                // Cập nhật thumbnail active
                $('.thumbnail').removeClass('active');
                $('.thumbnail').eq(index).addClass('active');
                // Cập nhật chỉ số slide hiện tại
                currentSlide = index;
            }
            
            // Chuyển đến slide tiếp theo
            $('.next-slide').on('click', function() {
                currentSlide = (currentSlide + 1) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Chuyển đến slide trước đó
            $('.prev-slide').on('click', function() {
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                showSlide(currentSlide);
            });
            
            // Khi nhấp vào thumbnail
            $('.thumbnail').on('click', function() {
                const index = $(this).data('index');
                showSlide(index);
            });
            
            // Hiển thị slide đầu tiên khi trang tải
            showSlide(0);
            
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