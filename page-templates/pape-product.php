<?php
/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị danh sách sản phẩm từ dữ liệu mẫu
 * Path: page-templates/page-product.php
 */

get_header();

// Lấy parameters từ URL
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';
$category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$products_per_page = 8; // Số sản phẩm trên một trang

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';

// Khởi tạo provider
$product_provider = new Sample_Product_Provider();

// Lấy sản phẩm với các tham số
$products_response = $product_provider->get_products([
    'search' => $search_query,
    'category' => $category,
    'sort' => $sort_by,
    'limit' => $products_per_page,
    'page' => $current_page
]);

// Xử lý dữ liệu trả về
$products = isset($products_response['data']) ? $products_response['data'] : [];
$total_products = isset($products_response['total']) ? $products_response['total'] : 0;
$total_pages = ceil($total_products / $products_per_page);

// Thêm breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => '']
];

// Lấy danh sách danh mục sản phẩm
$categories_response = $product_provider->get_product_categories();
$categories = isset($categories_response['data']) ? $categories_response['data'] : [];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Tất cả sản phẩm</h1>
        <div class="results-count"><?php echo $total_products; ?> kết quả</div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="search-filter-bar">
        <div class="search-container">
            <input 
                type="text" 
                class="search-input" 
                placeholder="Tìm theo tên, mẫu, mã hàng..." 
                value="<?php echo esc_attr($search_query); ?>"
                id="product-search"
            >
            <i class="search-icon">🔍</i>
        </div>
        
        <!-- Thêm dropdown danh mục nếu có -->
        <?php if (!empty($categories)): ?>
        <select class="category-dropdown" id="category-select">
            <option value="">Tất cả danh mục</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo esc_attr($cat['name']); ?>" <?php selected($category, $cat['name']); ?>>
                    <?php echo esc_html($cat['display_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        
        <select class="sort-dropdown" id="sort-select">
            <option value="default" <?php selected($sort_by, 'default'); ?>>Thứ tự mặc định</option>
            <option value="name-asc" <?php selected($sort_by, 'name-asc'); ?>>Tên A → Z</option>
            <option value="name-desc" <?php selected($sort_by, 'name-desc'); ?>>Tên Z → A</option>
            <option value="price-asc" <?php selected($sort_by, 'price-asc'); ?>>Giá thấp → cao</option>
            <option value="price-desc" <?php selected($sort_by, 'price-desc'); ?>>Giá cao → thấp</option>
            <option value="newest" <?php selected($sort_by, 'newest'); ?>>Mới nhất</option>
        </select>
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
                            <h3 class="product-title"><?php echo esc_html($product_name); ?></h3>
                            <p class="product-description"><?php echo esc_html(wp_trim_words($product_desc, 12, '...')); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <nav class="pagination-nav">
                    <?php
                    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
                    $query_params = $_GET;
                    
                    // Previous page
                    if ($current_page > 1):
                        $query_params['paged'] = $current_page - 1;
                        $prev_url = $current_url . '?' . http_build_query($query_params);
                    ?>
                        <a href="<?php echo esc_url($prev_url); ?>" class="pagination-link pagination-prev">
                            ‹ Trước
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                        $query_params['paged'] = $i;
                        $page_url = $current_url . '?' . http_build_query($query_params);
                        $active_class = ($i == $current_page) ? 'active' : '';
                    ?>
                        <a href="<?php echo esc_url($page_url); ?>" class="pagination-link <?php echo $active_class; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php
                    // Next page
                    if ($current_page < $total_pages):
                        $query_params['paged'] = $current_page + 1;
                        $next_url = $current_url . '?' . http_build_query($query_params);
                    ?>
                        <a href="<?php echo esc_url($next_url); ?>" class="pagination-link pagination-next">
                            Sau ›
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
        
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
            
            // Xử lý lọc danh mục
            $('#category-select').on('change', function() {
                let categoryValue = $(this).val();
                updateURLParam('category', categoryValue);
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
                
                // Reset trang về 1 khi lọc hoặc tìm kiếm
                params.delete('paged');
                
                // Chuyển hướng đến URL mới
                url.search = params.toString();
                window.location.href = url.toString();
            }
        });
    })(jQuery);
</script>

<?php get_footer(); ?>