/**
 * VinaPet Product Listing JavaScript
 * Handles search, sorting, and product interactions
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Xử lý tìm kiếm
        $('#product-search').keypress(function(e) {
            if (e.which == 13) {
                let searchValue = $(this).val().trim();
                updateURLParam('s', searchValue);
            }
        });
        
        // Thêm hiệu ứng khi tìm kiếm
        $('#product-search').on('input', function() {
            $(this).addClass('searching');
            
            setTimeout(() => {
                $(this).removeClass('searching');
            }, 1000);
        });
        
        // Xử lý sắp xếp
        $('#sort-select').on('change', function() {
            let sortValue = $(this).val();
            updateURLParam('sort', sortValue);
        });
        
        // Thêm hiệu ứng hover cho sản phẩm
        $('.product-card').hover(
            function() {
                $(this).addClass('hover');
            },
            function() {
                $(this).removeClass('hover');
            }
        );
        
        // Thêm tabindex và role cho khả năng truy cập
        $('.product-card').attr('tabindex', '0');
        $('.product-card').attr('role', 'button');
        $('.product-card').attr('aria-label', function() {
            const title = $(this).find('.product-title').text();
            return 'Xem sản phẩm: ' + title;
        });
        
        // Xử lý sự kiện nhấn phím Enter trên sản phẩm
        $('.product-card').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                const url = $(this).attr('onclick').match(/window\.location\.href='([^']+)'/)[1];
                window.location.href = url;
            }
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
            
            // Hiển thị hiệu ứng loading
            $('body').addClass('page-loading');
            
            // Chuyển hướng đến URL mới
            url.search = params.toString();
            window.location.href = url.toString();
        }
    });
})(jQuery);