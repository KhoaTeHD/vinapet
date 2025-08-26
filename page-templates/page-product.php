<?php
/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị danh sách sản phẩm từ dữ liệu mẫu
 */

get_header();

// Lấy parameters từ URL
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';

// Khởi tạo provider
$product_provider = new Sample_Product_Provider();

// Lấy tất cả sản phẩm với các tham số (không giới hạn số lượng)
$products_response = $product_provider->get_products([
    'search' => $search_query,
    'sort' => $sort_by,
]);

// Xử lý dữ liệu trả về
$products = isset($products_response['data']) ? $products_response['data'] : [];
$total_products = isset($products_response['total']) ? $products_response['total'] : 0;

// Thêm breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => '']
];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <!-- Page Header with Search and Filter - nằm cùng một hàng -->
    <div class="page-header-container">
        <div class="page-title-container">
            <h1 class="page-title">Tất cả sản phẩm</h1>
            <div class="results-count"><?php echo $total_products; ?> kết quả</div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <!-- <i class="search-icon">🔍</i> -->
                <input 
                    type="text" 
                    class="search-input" 
                    placeholder="🔍 Tìm theo tên, mẫu, mã hàng..." 
                    value="<?php echo esc_attr($search_query); ?>"
                    id="product-search"
                >
                
            </div>
            
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
                    <div class="product-image" style="background-image: url('<?php echo esc_url($product_image); ?>');">
                        <div class="product-overlay">
                            <div class="product-title-container">
                                <h3 class="product-title">
                                    <span class="title-text"><?php echo esc_html($product_name); ?></span>
                                </h3>
                                <div class="arrow-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
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
            <p>Vui lòng thử lại với từ khóa khác hoặc kiểm tra lại chính tả.</p>
        </div>
    <?php endif; ?>
</div>

<script>
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
</script>

<?php get_footer(); ?>