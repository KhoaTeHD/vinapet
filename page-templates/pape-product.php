<?php
/**
 * Template Name: Product Listing Page
 * Description: Trang hiển thị tất cả sản phẩm cho VinaPet
 * Path: page-templates/page-product.php
 */

get_header(); 

// Lấy parameters từ URL
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'default';
$products_per_page = 12;

// Dữ liệu giả lập sản phẩm VinaPet
$mock_products = [
    [
        'id' => 1,
        'name' => 'Cát tre',
        'short_description' => 'Siêu Khử Mùi & Khôi Mũi Tự Uy. Siêu Nhẹ & Thấm Hút Mạnh Mẽ',
        'image' => get_template_directory_uri() . '/assets/images/products/cat-tre.jpg',
        'url' => '/san-pham/cat-tre',
        'category' => 'cat-ve-sinh',
        'price' => 150000,
        'created_date' => '2024-01-15'
    ],
    [
        'id' => 2,
        'name' => 'Cát đậu nành',
        'short_description' => 'Vón Cục Tốt & Ít Bụi. Thân thiện môi trường',
        'image' => get_template_directory_uri() . '/assets/images/products/cat-dau-nanh.jpg',
        'url' => '/san-pham/cat-dau-nanh',
        'category' => 'cat-ve-sinh',
        'price' => 120000,
        'created_date' => '2024-01-20'
    ],
    [
        'id' => 3,
        'name' => 'Cát vỏ trấu',
        'short_description' => 'Khang Khuẩn & Khang Nấm Mốc. Tự Nhiên trong môi trường ẩm',
        'image' => get_template_directory_uri() . '/assets/images/products/cat-vo-trau.jpg',
        'url' => '/san-pham/cat-vo-trau',
        'category' => 'cat-ve-sinh',
        'price' => 95000,
        'created_date' => '2024-01-25'
    ],
    [
        'id' => 4,
        'name' => 'Cát đất sét',
        'short_description' => 'Vón Cực Chặt-Chắn & Tức Thì. Xả thông toilet dễ dàng lót',
        'image' => get_template_directory_uri() . '/assets/images/products/cat-dat-set.jpg',
        'url' => '/san-pham/cat-dat-set',
        'category' => 'cat-ve-sinh',
        'price' => 85000,
        'created_date' => '2024-01-30'
    ],
    [
        'id' => 5,
        'name' => 'Pet Bowl',
        'short_description' => 'For Cats and Dogs Best Seller Auto Dog Feeder',
        'image' => get_template_directory_uri() . '/assets/images/products/pet-bowl.jpg',
        'url' => '/san-pham/pet-bowl',
        'category' => 'dung-cu-an-uong',
        'price' => 75000,
        'created_date' => '2024-02-01'
    ],
    [
        'id' => 6,
        'name' => 'Pet Soft Cushion',
        'short_description' => 'Comfortable Bed for Dog Cats Washable Winter Warm Mattress',
        'image' => get_template_directory_uri() . '/assets/images/products/pet-soft-cushion.jpg',
        'url' => '/san-pham/pet-soft-cushion',
        'category' => 'nha-o-thu-cung',
        'price' => 280000,
        'created_date' => '2024-02-05'
    ],
    [
        'id' => 7,
        'name' => 'Pet Cave',
        'short_description' => 'Cozy House Cats Tent',
        'image' => get_template_directory_uri() . '/assets/images/products/pet-cave.jpg',
        'url' => '/san-pham/pet-cave',
        'category' => 'nha-o-thu-cung',
        'price' => 320000,
        'created_date' => '2024-02-10'
    ],
    [
        'id' => 8,
        'name' => 'Metal Cat Litter',
        'short_description' => 'Pet Cleaning Products with Small Hole Dog Accessories',
        'image' => get_template_directory_uri() . '/assets/images/products/metal-cat-litter.jpg',
        'url' => '/san-pham/metal-cat-litter',
        'category' => 'dung-cu-ve-sinh',
        'price' => 45000,
        'created_date' => '2024-02-15'
    ],
    [
        'id' => 9,
        'name' => 'Lược chải lông mèo',
        'short_description' => 'Removing Cat Hair and Loose Hair for Cat Dog Pet',
        'image' => get_template_directory_uri() . '/assets/images/products/luoc-chai-long-meo.jpg',
        'url' => '/san-pham/luoc-chai-long-meo',
        'category' => 'dung-cu-ve-sinh',
        'price' => 65000,
        'created_date' => '2024-02-20'
    ],
    [
        'id' => 10,
        'name' => 'Vòng cổ thú cưng',
        'short_description' => 'Pet Dog Necklace Collar Dogs Valentine\'s Day New Year Gift',
        'image' => get_template_directory_uri() . '/assets/images/products/vong-co-thu-cung.jpg',
        'url' => '/san-pham/vong-co-thu-cung',
        'category' => 'phu-kien',
        'price' => 95000,
        'created_date' => '2024-02-25'
    ]
];

// Xử lý tìm kiếm
if (!empty($search_query)) {
    $mock_products = array_filter($mock_products, function($product) use ($search_query) {
        return stripos($product['name'], $search_query) !== false || 
               stripos($product['short_description'], $search_query) !== false;
    });
}

// Xử lý sắp xếp
switch ($sort_by) {
    case 'name-asc':
        usort($mock_products, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        break;
    case 'name-desc':
        usort($mock_products, function($a, $b) {
            return strcmp($b['name'], $a['name']);
        });
        break;
    case 'price-asc':
        usort($mock_products, function($a, $b) {
            return $a['price'] - $b['price'];
        });
        break;
    case 'price-desc':
        usort($mock_products, function($a, $b) {
            return $b['price'] - $a['price'];
        });
        break;
    case 'newest':
        usort($mock_products, function($a, $b) {
            return strtotime($b['created_date']) - strtotime($a['created_date']);
        });
        break;
}

// Phân trang
$total_products = count($mock_products);
$total_pages = ceil($total_products / $products_per_page);
$offset = ($current_page - 1) * $products_per_page;
$products = array_slice($mock_products, $offset, $products_per_page);

// Add breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => '']
];
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
            <?php foreach ($products as $product): ?>
                <div class="product-card" onclick="window.location.href='<?php echo esc_url($product['url']); ?>'">
                    <div class="product-image" style="background-image: url('<?php echo esc_url($product['image']); ?>');">
                        <div class="product-overlay">
                            <h3 class="product-title"><?php echo esc_html($product['name']); ?></h3>
                            <p class="product-description"><?php echo esc_html($product['short_description']); ?></p>
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

<?php get_footer(); ?>