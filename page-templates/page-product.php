<?php
/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị danh sách sản phẩm từ ERPNext API với fallback
 */

get_header();

// =============================================================================
// DEBUG: In ra tất cả parameters để xem
// =============================================================================
if (current_user_can('manage_options')) {
    echo '<div style="background: red; color: white; padding: 10px; margin: 10px 0;">';
    echo '<strong>DEBUG URL PARAMS:</strong><br>';
    echo 'Current URL: ' . $_SERVER['REQUEST_URI'] . '<br>';
    echo '$_GET: ' . print_r($_GET, true) . '<br>';
    echo 'search_query: "' . (isset($_GET['s']) ? $_GET['s'] : 'EMPTY') . '"<br>';
    echo 'sort_by: "' . (isset($_GET['sort']) ? $_GET['sort'] : 'EMPTY') . '"<br>';
    echo '</div>';
}

// =============================================================================
// LOGIC LẤY DỮ LIỆU
// =============================================================================

// Lấy parameters từ URL
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';

// DEBUG: Log params
if (current_user_can('manage_options')) {
    echo '<div style="background: blue; color: white; padding: 10px; margin: 10px 0;">';
    echo '<strong>PROCESSED PARAMS:</strong><br>';
    echo 'search_query after sanitize: "' . $search_query . '"<br>';
    echo 'sort_by after sanitize: "' . $sort_by . '"<br>';
    echo '</div>';
}

// Sử dụng Product Data Manager
require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
$product_manager = new Product_Data_Manager();

// Validate params - TRUYỀN ĐÚNG FORMAT
$params = [
    'search' => $search_query,  // Pass cả khi empty
    'sort' => $sort_by,        // Pass cả khi default
];

// Không cần validate vì đã sanitize rồi
// $params = $product_manager->validate_params($params);

// DEBUG: Log validated params
if (current_user_can('manage_options')) {
    echo '<div style="background: green; color: white; padding: 10px; margin: 10px 0;">';
    echo '<strong>VALIDATED PARAMS PASSED TO get_products():</strong><br>';
    echo print_r($params, true);
    echo '</div>';
}

// Lấy dữ liệu
$products_result = $product_manager->get_products($params);

// DEBUG: Log what get_products actually received
if (current_user_can('manage_options')) {
    echo '<div style="background: orange; color: white; padding: 10px; margin: 10px 0;">';
    echo '<strong>WHAT get_products() RECEIVED:</strong><br>';
    echo 'Search param: ' . (isset($params['search']) ? '"' . $params['search'] . '"' : 'NOT SET') . '<br>';
    echo 'Sort param: ' . (isset($params['sort']) ? '"' . $params['sort'] . '"' : 'NOT SET') . '<br>';
    echo 'All params: ' . print_r($params, true);
    echo '</div>';
}
$products = isset($products_result['products']) ? $products_result['products'] : [];
$total_products = isset($products_result['total']) ? $products_result['total'] : 0;
$data_source = isset($products_result['source']) ? $products_result['source'] : 'none';
$is_cached = isset($products_result['is_cached']) ? $products_result['is_cached'] : false;
$error_message = isset($products_result['error']) ? $products_result['error'] : '';

// DEBUG: Log results
if (current_user_can('manage_options')) {
    echo '<div style="background: purple; color: white; padding: 10px; margin: 10px 0;">';
    echo '<strong>PRODUCTS RESULT:</strong><br>';
    echo 'Total products: ' . count($products) . '<br>';
    echo 'Data source: ' . $data_source . '<br>';
    echo 'First product: ' . (isset($products[0]['item_name']) ? $products[0]['item_name'] : 'NONE') . '<br>';
    if (!empty($products) && count($products) > 1) {
        echo 'Second product: ' . (isset($products[1]['item_name']) ? $products[1]['item_name'] : 'NONE') . '<br>';
    }
    echo '</div>';
}

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => '']
];
?>

<div class="container">
    <!-- Admin Debug Panel -->
    <?php if (current_user_can('manage_options')): ?>
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9em;">
            <strong>🔧 Debug Info:</strong>
            <span style="margin-left: 10px;">
                Source: 
                <?php if ($data_source === 'erp'): ?>
                    <span style="color: #28a745; font-weight: bold;">ERPNext API</span>
                <?php elseif ($data_source === 'sample'): ?>
                    <span style="color: #ffc107; font-weight: bold;">Sample Data</span>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: bold;">No Data</span>
                <?php endif; ?>
            </span>
            <?php if ($is_cached): ?>
                <span style="margin-left: 10px; color: #17a2b8;">📋 Cached</span>
            <?php endif; ?>
            <button onclick="clearCache()" style="float: right; padding: 4px 8px; font-size: 0.8em;">Clear Cache</button>
        </div>
        
        <div style="background: orange; color: white; padding: 10px; margin: 10px 0;">
            <strong>CURRENT STATE:</strong><br>
            Search: "<?php echo esc_html($search_query); ?>"<br>
            Sort: "<?php echo esc_html($sort_by); ?>"<br>
            Products count: <?php echo count($products); ?><br>
            URL: <?php echo $_SERVER['REQUEST_URI']; ?>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <!-- Page Header -->
    <div class="page-header-container">
        <div class="page-title-container">
            <h1 class="page-title">Tất cả sản phẩm</h1>
            <div class="results-count"><?php echo $total_products; ?> kết quả</div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <div class="search-label">Tìm kiếm sản phẩm</div>
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
            <?php foreach ($products as $index => $product):
                $product_name = isset($product['item_name']) ? $product['item_name'] : '';
                $product_desc = isset($product['description']) ? $product['description'] : '';
                $product_image = isset($product['image']) ? $product['image'] : '';
                $product_code = isset($product['item_code']) ? $product['item_code'] : '';
                $product_url = home_url('/san-pham/' . sanitize_title($product_code));

                if (empty($product_image)) {
                    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
                }
                
                // DEBUG: Show first few products
                if (current_user_can('manage_options') && $index < 3) {
                    echo '<div style="background: yellow; padding: 5px; margin: 5px 0;">';
                    echo 'Product #' . ($index + 1) . ': ' . esc_html($product_name) . ' (Code: ' . esc_html($product_code) . ')';
                    echo '</div>';
                }
            ?>
                <div class="product-card" onclick="window.location.href='<?php echo esc_url($product_url); ?>'">
                    <!-- Admin source indicator -->
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
                    Không tìm thấy sản phẩm với từ khóa "<?php echo esc_html($search_query); ?>"
                <?php else: ?>
                    Hiện tại chưa có sản phẩm nào.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
// =============================================================================
// SEARCH & SORT - DEBUG VERSION
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Page loaded, current URL:', window.location.href);
    
    // Lấy elements
    const searchInput = document.getElementById('product-search');
    const sortSelect = document.getElementById('sort-select');
    
    console.log('🔧 Elements found:', {
        searchInput: !!searchInput,
        sortSelect: !!sortSelect
    });
    
    // Search function with debug
    function doSearch() {
        const searchValue = searchInput.value.trim();
        console.log('🔍 doSearch called with value:', searchValue);
        
        const url = new URL(window.location.href);
        console.log('🔍 Current URL before change:', url.toString());
        
        if (searchValue) {
            url.searchParams.set('s', searchValue);
        } else {
            url.searchParams.delete('s');
        }
        
        url.searchParams.delete('page');
        console.log('🔍 New URL will be:', url.toString());
        
        window.location.href = url.toString();
    }
    
    // Sort function with debug
    function doSort() {
        const sortValue = sortSelect.value;
        console.log('📊 doSort called with value:', sortValue);
        
        const url = new URL(window.location.href);
        console.log('📊 Current URL before change:', url.toString());
        
        if (sortValue && sortValue !== 'default') {
            url.searchParams.set('sort', sortValue);
        } else {
            url.searchParams.delete('sort');
        }
        
        url.searchParams.delete('page');
        console.log('📊 New URL will be:', url.toString());
        
        window.location.href = url.toString();
    }
    
    // Event listeners with debug
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            console.log('🔍 Keypress detected:', e.key, e.keyCode);
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                console.log('🔍 Enter pressed, calling doSearch');
                doSearch();
            }
        });
    }
    
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            console.log('📊 Sort change detected, new value:', this.value);
            doSort();
        });
    }
    
    // Test functions
    window.testSearch = function(query) {
        console.log('🧪 testSearch called with:', query);
        if (searchInput) {
            searchInput.value = query || 'test';
            doSearch();
        }
    };
    
    window.testSort = function(sort) {
        console.log('🧪 testSort called with:', sort);
        if (sortSelect) {
            sortSelect.value = sort || 'name-asc';
            doSort();
        }
    };
});

// Admin functions
<?php if (current_user_can('manage_options')): ?>
function clearCache() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=clear_product_cache&nonce=<?php echo wp_create_nonce('clear_product_cache'); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Cache cleared!');
            location.reload();
        } else {
            alert('Error: ' + (data.data || 'Unknown'));
        }
    });
}

console.log('💡 Debug commands: testSearch("keyword"), testSort("name-asc"), clearCache()');
console.log('💡 Current search:', '<?php echo esc_js($search_query); ?>');
console.log('💡 Current sort:', '<?php echo esc_js($sort_by); ?>');
<?php endif; ?>
</script>

<?php get_footer(); ?>