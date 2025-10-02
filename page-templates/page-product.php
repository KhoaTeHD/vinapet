<?php

/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị danh sách sản phẩm từ ERPNext API với fallback
 */

get_header();

// Lấy parameters từ URL - Đổi từ 's' sang 'search' để tránh conflict với WordPress
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';

// Sử dụng Product Data Manager
require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
$product_manager = new Product_Data_Manager();
$selected_categories = isset($_GET['category']) ? explode(',', sanitize_text_field($_GET['category'])) : [];

// Prepare params
$params = [
    'key' => $selected_categories[0] ?? '', // Lấy category đầu tiên để lọc (nếu có)
    'search' => $search_query,
    'sort' => $sort_by,
    'limit' => 50,
    'page' => 1
];

// Lấy dữ liệu
$products_result = $product_manager->get_products($params);

// Extract data
$products = isset($products_result['products']) ? $products_result['products'] : [];
$total_products = isset($products_result['total']) ? $products_result['total'] : 0;
$data_source = isset($products_result['source']) ? $products_result['source'] : 'none';
$is_cached = isset($products_result['is_cached']) ? $products_result['is_cached'] : false;
$error_message = isset($products_result['error']) ? $products_result['error'] : '';

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
            <strong>🔧 Debug:</strong>
            Source:
            <?php if ($data_source === 'erp'): ?>
                <span style="color: #28a745; font-weight: bold;">ERPNext API</span>
            <?php elseif ($data_source === 'sample'): ?>
                <span style="color: #ffc107; font-weight: bold;">Sample Data</span>
            <?php else: ?>
                <span style="color: #dc3545; font-weight: bold;">No Data</span>
            <?php endif; ?>

            <?php if ($is_cached): ?>
                | <span style="color: #17a2b8;">Cached</span>
            <?php endif; ?>

            <button onclick="clearCache()" style="float: right; padding: 4px 8px; font-size: 0.8em; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;">Clear Cache</button>
        </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <!-- Wrapper: Sidebar + Main Content -->
    <div class="product-page-wrapper">

        <!-- Sidebar Filter -->
        <aside class="product-filter-sidebar" id="productSidebar">
            <div class="sidebar-header">
                <h3 class="sidebar-title">Danh mục sản phẩm</h3>
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Thu gọn sidebar">
                    <span class="toggle-icon">−</span>
                </button>
            </div>

            <div class="sidebar-content" id="sidebarContent">
                <form id="categoryFilterForm" class="category-filter-form">
                    <?php
                    // Lấy category hiện tại từ URL
                    //$selected_categories = isset($_GET['category']) ? explode(',', sanitize_text_field($_GET['category'])) : [];

                    // 3 danh mục cố định
                    $categories = [
                        ['name' => 'Cát tre', 'label' => 'Cát tre', 'count' => 0],
                        ['name' => 'Cát tofu', 'label' => 'Cát tofu', 'count' => 0],
                        ['name' => 'S.A.P', 'label' => 'SAP', 'count' => 0],
                    ];

                    // Đếm số lượng sản phẩm theo danh mục
                    // if (!empty($products)) {
                    //     foreach ($products as $product) {
                    //         $cat = strtolower($product['Nhom_hang'] ?? $product['item_group'] ?? '');

                    //         foreach ($categories as &$category) {
                    //             if (strpos($cat, str_replace('-', ' ', $category['name'])) !== false) {
                    //                 $category['count']++;
                    //                 break;
                    //             }
                    //         }
                    //     }
                    // }
                    ?>

                    <div class="filter-group">
                        <?php foreach ($categories as $category): ?>
                            <label class="filter-item">
                                <input
                                    type="radio"
                                    name="category"
                                    value="<?php echo esc_attr($category['name']); ?>"
                                    class="category-checkbox"
                                    <?php echo in_array($category['name'], $selected_categories) ? 'checked' : ''; ?>>
                                <span class="filter-label">
                                    <?php echo esc_html($category['label']); ?>
                                    <span class="filter-count">(<?php echo $category['count']; ?>)</span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($selected_categories)): ?>
                        <button type="button" class="clear-filters-btn" id="clearFilters">
                            Xóa bộ lọc
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="product-main-area" id="productMainArea">

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
                        // Dùng trực tiếp ERPNext fields, fallback to old fields để tương thích
                        $product_name = $product['Ten_SP'] ?? $product['item_name'] ?? '';
                        $product_desc = strip_tags($product['Mo_ta_ngan'] ?? $product['description'] ?? '');
                        $product_image = $product['Thumbnail_File'] ?? $product['image'] ?? '';
                        $product_code = $product['Ma_SP'] ?? $product['ProductID'] ?? $product['item_code'] ?? '';
                        $product_price = floatval($product['Gia_ban_le'] ?? $product['standard_rate'] ?? 0);
                        $product_url = Smart_URL_Router::generate_product_url($product);


                        // Handle image
                        if (empty($product_image)) {
                            $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
                        } elseif (strpos($product_image, 'http') !== 0) {
                            // Nếu là relative URL, add ERPNext domain
                            $erp_url = get_option('erp_api_url');
                            if (!empty($erp_url)) {
                                $product_image = trailingslashit($erp_url) . ltrim($product_image, '/');
                            }
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
                        <?php elseif (!empty($error_message)): ?>
                            Lỗi: <?php echo esc_html($error_message); ?>
                        <?php else: ?>
                            Hiện tại chưa có sản phẩm nào.
                        <?php endif; ?>
                    </p>

                    <?php if (current_user_can('manage_options') && !empty($error_message)): ?>
                        <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 0.9em;">
                            <strong>Debug Error:</strong> <?php echo esc_html($error_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div> <!-- End product-main-area -->

    </div> <!-- End product-page-wrapper -->

</div> <!-- End container -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lấy elements
        const searchInput = document.getElementById('product-search');
        const sortSelect = document.getElementById('sort-select');

        if (!searchInput || !sortSelect) {
            console.error('Required elements not found!');
            return;
        }

        let searchTimeout;

        // Search functionality
        function handleSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchValue = searchInput.value.trim();
                updateURL({
                    search: searchValue
                }); // Dùng 'search' thay vì 's'
            }, 500);
        }

        // Search events
        searchInput.addEventListener('input', handleSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const searchValue = searchInput.value.trim();
                updateURL({
                    search: searchValue
                }); // Dùng 'search' thay vì 's'
            }
        });

        // Sort functionality
        function handleSort() {
            const sortValue = sortSelect.value;
            updateURL({
                sort: sortValue
            });
        }

        sortSelect.addEventListener('change', handleSort);

        // URL update function
        function updateURL(params) {
            const url = new URL(window.location);

            Object.keys(params).forEach(key => {
                if (params[key] && params[key] !== '' && params[key] !== 'default') {
                    url.searchParams.set(key, params[key]);
                } else {
                    url.searchParams.delete(key);
                }
            });

            window.location.href = url.toString();
        }

        // Debug functions (admin only)
        <?php if (current_user_can('manage_options')): ?>

            window.testSearch = function(keyword) {
                searchInput.value = keyword;
                handleSearch();
            };

            window.testSort = function(sortValue) {
                sortSelect.value = sortValue;
                handleSort();
            };

            window.clearCache = function() {
                if (!confirm('Xóa cache sản phẩm?')) return;

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'clear_product_cache',
                            nonce: '<?php echo wp_create_nonce('clear_product_cache'); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Cache đã được xóa thành công!');
                            location.reload();
                        } else {
                            alert('Lỗi: ' + (data.data || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        alert('Lỗi kết nối: ' + error.message);
                    });
            };

            console.log('💡 Debug commands: testSearch("keyword"), testSort("name-asc"), clearCache()');

        <?php endif; ?>
    });

    // Handle browser back/forward buttons
    window.addEventListener('popstate', function(event) {
        location.reload();
    });
</script>

<?php get_footer(); ?>