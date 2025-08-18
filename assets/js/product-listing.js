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