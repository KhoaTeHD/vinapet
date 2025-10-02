(function($) {
        $(document).ready(function() {
            // Xử lý tìm kiếm
            $('#product-search').keypress(function(e) {
                if (e.which == 13) {
                    let searchValue = $(this).val().trim();
                    updateURLParam('s', searchValue);
                }
            });
            
            // Xử lý sắp xếp
            $('#sort-select').on('change', function() {
                let sortValue = $(this).val();
                updateURLParam('sort', sortValue);
            });
            
            // Hàm cập nhật URL
            function updateURLParam(param, value) {
                let url = new URL(window.location.href);
                let params = new URLSearchParams(url.search);
                
                // Cập nhật tham số
                if (value && value !== '') {
                    params.set(param, value);
                } else {
                    params.delete(param);
                }
                
                // Chuyển hướng đến URL mới
                url.search = params.toString();
                window.location.href = url.toString();
            }
        });
    })(jQuery);

    /* ================================================================
   SIDEBAR FILTER FUNCTIONALITY
   ================================================================ */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Toggle Sidebar
        $('#sidebarToggle').on('click', function() {
            const $sidebar = $('#productSidebar');
            const $icon = $(this).find('.toggle-icon');
            
            $sidebar.toggleClass('collapsed');
            
            if ($sidebar.hasClass('collapsed')) {
                $icon.text('+');
                $(this).attr('aria-label', 'Mở rộng sidebar');
            } else {
                $icon.text('−');
                $(this).attr('aria-label', 'Thu gọn sidebar');
            }
        });
        
        // Category Filter Change
        $('.category-checkbox').on('change', function() {
            filterProducts();
        });
        
        // Clear Filters
        $('#clearFilters').on('click', function() {
            $('.category-checkbox').prop('checked', false);
            filterProducts();
        });
        
        // Filter Products Function
        function filterProducts() {
            const selectedCategories = [];
            
            $('.category-checkbox:checked').each(function() {
                selectedCategories.push($(this).val());
            });
            
            // Build URL
            const currentUrl = new URL(window.location.href);
            
            if (selectedCategories.length > 0) {
                currentUrl.searchParams.set('category', selectedCategories.join(','));
            } else {
                currentUrl.searchParams.delete('category');
            }
            
            // Reload page with new filters
            window.location.href = currentUrl.toString();
        }
        
    });
    
})(jQuery);