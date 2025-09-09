<?php
/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị danh sách sản phẩm từ ERPNext API với fallback
 * 
 */

get_header();

// =============================================================================
// PHẦN THAY ĐỔI DUY NHẤT: Logic lấy dữ liệu
// =============================================================================

// Lấy parameters từ URL 
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';

// THAY ĐỔI: Sử dụng Product Data Manager thay vì Sample Provider
require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';

// Khởi tạo manager
$product_manager = new Product_Data_Manager();

// Validate params
$params = $product_manager->validate_params([
    'search' => $search_query,
    'sort' => $sort_by,
]);

// Lấy dữ liệu thông qua manager (ERPNext + fallback)
$products_result = $product_manager->get_products($params);

// Xử lý dữ liệu trả về
$products = isset($products_result['products']) ? $products_result['products'] : [];
$total_products = isset($products_result['total']) ? $products_result['total'] : 0;

// THÊM: Tracking data source cho debug (chỉ admin thấy)
$data_source = isset($products_result['source']) ? $products_result['source'] : 'none';
$is_cached = isset($products_result['is_cached']) ? $products_result['is_cached'] : false;
$error_message = isset($products_result['error']) ? $products_result['error'] : '';

// Thêm breadcrumb data 
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => '']
];
?>

<div class="container">
    <!-- THÊM: Admin Debug Panel (chỉ admin thấy, không ảnh hưởng layout) -->
    <?php if (current_user_can('manage_options')): ?>
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9em; position: relative;">
            <strong>🔧 Debug Info:</strong>
            <span style="margin-left: 10px;">
                Source: 
                <?php if ($data_source === 'erp'): ?>
                    <span style="color: #28a745; font-weight: bold;">ERPNext API</span>
                <?php elseif ($data_source === 'sample'): ?>
                    <span style="color: #ffc107; font-weight: bold;">Sample Data (Fallback)</span>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: bold;">No Data</span>
                <?php endif; ?>
            </span>
            <?php if ($is_cached): ?>
                <span style="margin-left: 10px; color: #17a2b8;">📋 Cached</span>
            <?php endif; ?>
            <?php if (!empty($error_message) && $data_source !== 'erp'): ?>
                <span style="margin-left: 10px; color: #dc3545;">⚠️ <?php echo esc_html($error_message); ?></span>
            <?php endif; ?>
            <button onclick="clearProductCache()" style="float: right; padding: 4px 8px; font-size: 0.8em; cursor: pointer;">
                Clear Cache
            </button>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <!-- Page Header with Search and Filter -->
    <div class="page-header-container">
        <div class="page-title-container">
            <h1 class="page-title">Tất cả sản phẩm</h1>
            <div class="results-count">
                <?php echo $total_products; ?> kết quả
                <?php if (current_user_can('manage_options')): ?>
                    <small style="color: #6c757d; margin-left: 8px;">
                        (<?php echo $data_source === 'erp' ? 'ERP' : ($data_source === 'sample' ? 'Demo' : 'None'); ?>)
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <div class="search-label"> Tìm kiếm sản phẩm</div>
                <input
                    type="text"
                    class="search-input"
                    placeholder="🔍 Tìm theo tên, mẫu, mã hàng..."
                    value="<?php echo esc_attr($search_query); ?>"
                    id="product-search">

            </div>

            <div class="sort-container">
                <div class="sort-label">Sắp xếp theo</div>
                <select class="sort-dropdown" id="sort-select">
                <option value="default" <?php selected($sort_by, 'default'); ?>>Thứ tự mặc định</option>
                <option value="name-asc" <?php selected($sort_by, 'name-asc'); ?>>Tên A → Z</option>
                <option value="name-desc" <?php selected($sort_by, 'name-desc'); ?>>Tên Z → A</option>
                <option value="price-asc" <?php selected($sort_by, 'price-asc'); ?>>Giá thấp → cao</option>
                <option value="price-desc" <?php selected($sort_by, 'price-desc'); ?>>Giá cao → thấp</option>
                <option value="newest" <?php selected($sort_by, 'newest'); ?>>Mới nhất</option>
            </select>
            </div>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if (!empty($products)): ?>
        <div class="products-grid" id="products-container">
            <?php foreach ($products as $product):
                // Lấy thông tin sản phẩm
                $product_name = isset($product['item_name']) ? $product['item_name'] : '';
                $product_desc = isset($product['description']) ? $product['description'] : '';
                $product_image = isset($product['image']) ? $product['image'] : '';
                $product_code = isset($product['item_code']) ? $product['item_code'] : '';
                $product_url = home_url('/san-pham/' . sanitize_title($product_code));

                // Nếu không có hình ảnh, sử dụng hình mặc định
                if (empty($product_image)) {
                    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
                }
            ?>
                <div class="product-card" onclick="window.location.href='<?php echo esc_url($product_url); ?>'">
                    <!-- THÊM: Admin source indicator (góc trên trái, chỉ admin thấy) -->
                    <?php if (current_user_can('manage_options')): ?>
                        <div style="position: absolute; top: 5px; left: 5px; z-index: 10; background: <?php echo $data_source === 'erp' ? '#28a745' : '#ffc107'; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7em; font-weight: bold;">
                            <?php echo $data_source === 'erp' ? 'ERP' : 'DEMO'; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-image" style="background-image: url('<?php echo esc_url($product_image); ?>');">
                        <div class="product-overlay">
                            <div class="product-title-container">
                                <h3 class="product-title">
                                    <span class="title-text"><?php echo esc_html($product_name); ?></span>
                                </h3>
                                <div class="arrow-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
                                        <path d="M5 12h14" />
                                        <path d="m12 5 7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                            <p class="product-description"><?php echo esc_html(wp_trim_words($product_desc, 12, '...')); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <h3>Không tìm thấy sản phẩm nào</h3>
            <p>
                <?php if ($data_source === 'none'): ?>
                    Không thể tải dữ liệu từ hệ thống. Vui lòng kiểm tra cấu hình API.
                <?php elseif (!empty($search_query)): ?>
                    Vui lòng thử lại với từ khóa khác hoặc kiểm tra lại chính tả.
                <?php else: ?>
                    Vui lòng thử lại với từ khóa khác hoặc kiểm tra lại chính tả.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            // THÊM: Debug info cho admin (console)
            <?php if (current_user_can('manage_options')): ?>
                console.log('🔧 VinaPet Debug:', {
                    dataSource: '<?php echo esc_js($data_source); ?>',
                    productsCount: <?php echo count($products); ?>,
                    isCached: <?php echo $is_cached ? 'true' : 'false'; ?>,
                    totalProducts: <?php echo $total_products; ?>,
                    errorMessage: '<?php echo esc_js($error_message); ?>'
                });
            <?php endif; ?>

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

    // THÊM: Admin functions
    <?php if (current_user_can('manage_options')): ?>
        // Clear cache function
        function clearProductCache() {
            if (!confirm('Bạn có chắc muốn xóa cache sản phẩm?')) {
                return;
            }
            
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'clear_product_cache',
                nonce: '<?php echo wp_create_nonce('clear_product_cache'); ?>'
            })
            .done(function(response) {
                if (response.success) {
                    alert('✅ Cache đã được xóa thành công!');
                    location.reload();
                } else {
                    alert('❌ Lỗi: ' + (response.data || 'Unknown error'));
                }
            })
            .fail(function() {
                alert('❌ Lỗi kết nối. Vui lòng thử lại.');
            });
        }

        // Keyboard shortcut: Ctrl+Shift+C để clear cache
        jQuery(document).on('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.keyCode === 67) { // Ctrl+Shift+C
                clearProductCache();
            }
        });

        // Debug function
        function debugVinaPet() {
            console.log('=== VinaPet Debug Info ===');
            console.log('Data Source:', '<?php echo esc_js($data_source); ?>');
            console.log('Products Count:', <?php echo count($products); ?>);
            console.log('Total Products:', <?php echo $total_products; ?>);
            console.log('Is Cached:', <?php echo $is_cached ? 'true' : 'false'; ?>);
            console.log('Error Message:', '<?php echo esc_js($error_message); ?>');
            console.log('Search Query:', '<?php echo esc_js($search_query); ?>');
            console.log('Sort By:', '<?php echo esc_js($sort_by); ?>');
        }

        // Expose to console
        window.debugVinaPet = debugVinaPet;
        console.log('💡 Run debugVinaPet() to see full debug info');
    <?php endif; ?>
</script>

<!-- THÊM: Hidden debug data cho admin -->
<?php if (current_user_can('manage_options')): ?>
    <div style="display: none;" data-vinapet-debug='<?php echo json_encode([
        'source' => $data_source,
        'cached' => $is_cached,
        'count' => count($products),
        'total' => $total_products,
        'error' => $error_message
    ]); ?>'></div>
<?php endif; ?>

<?php get_footer(); ?>