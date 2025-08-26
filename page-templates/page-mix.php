<?php
/**
 * Template Name: Mix Products Page
 * Description: Trang mix với hạt khác
 */

get_header();

// Lấy sản phẩm chính từ URL parameters
$main_product_code = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
$selected_variant = isset($_GET['variant']) ? sanitize_text_field($_GET['variant']) : '';

if (empty($main_product_code)) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$main_product_code = strtoupper($main_product_code);

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';
$product_provider = new Sample_Product_Provider();

// Lấy sản phẩm chính
$main_product_response = $product_provider->get_product($main_product_code);
if (!isset($main_product_response['success']) || !$main_product_response['success']) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$main_product = $main_product_response['data'];

// Lấy tất cả sản phẩm để hiển thị trong dropdown
$all_products_response = $product_provider->get_products(['limit' => 100]);
$all_products = isset($all_products_response['data']) ? $all_products_response['data'] : [];

// Lọc bỏ sản phẩm chính khỏi danh sách
$other_products = array_filter($all_products, function($product) use ($main_product_code) {
    return $product['item_code'] !== $main_product_code;
});

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
    ['name' => $main_product['item_name'], 'url' => home_url('/san-pham/' . $main_product_code)],
    ['name' => 'Mix với hạt khác', 'url' => '']
];

// Tiered pricing
$product_sizes = [
    ['name' => '0,5 - 1 tấn', 'price' => 50000, 'unit' => 'đ/kg', 'quantities' => ['100', '300', '500', '1000']],
    ['name' => '1 - 5 tấn', 'price' => 42000, 'unit' => 'đ/kg', 'quantities' => ['3000']],
    ['name' => 'Trên 5 tấn', 'price' => 34000, 'unit' => 'đ/kg', 'quantities' => ['5000']],
];

// Color options - match design with square colors
$color_options = [
    ['id' => 'xanh_non', 'name' => 'Màu xanh non', 'color' => '#8BC34A'],
    ['id' => 'hong_nhat', 'name' => 'Màu hồng nhạt', 'color' => '#FFB6C1'], 
    ['id' => 'vang_dat', 'name' => 'Màu vàng đất', 'color' => '#DAA520'],
    ['id' => 'do_gach', 'name' => 'Màu đỏ gạch', 'color' => '#CD5C5C'],
    ['id' => 'be_nhat', 'name' => 'Màu be nhạt', 'color' => '#F5F5DC'],
    ['id' => 'den', 'name' => 'Màu đen', 'color' => '#333333'],
];

// Scent options
$scent_options = [
    ['id' => 'com', 'name' => 'Mùi cốm'],
    ['id' => 'tro_xanh', 'name' => 'Mùi trà xanh'],
    ['id' => 'ca_phe', 'name' => 'Mùi cà phê'],
    ['id' => 'sen', 'name' => 'Mùi sen'],
    ['id' => 'sua', 'name' => 'Mùi sữa'],
    ['id' => 'chanh', 'name' => 'Mùi chanh'],
];

// Quantity options
$quantity_options = [
    ['value' => '5000', 'label' => '5 tấn'],
    ['value' => '7000', 'label' => '7 tấn'],
    ['value' => '10000', 'label' => '10 tấn'],
    ['value' => 'khac', 'label' => 'Khác'],
];

// Packaging options
$packaging_options = [
    [
        'id' => 'tui_jumbo_500',
        'name' => 'Túi Jumbo 500 kg',
        'description' => '+800 đ/kg',
        'price' => 800
    ],
    [
        'id' => 'tui_jumbo_1000',
        'name' => 'Túi Jumbo 1 tấn',
        'description' => 'Miễn phí',
        'price' => 0
    ],
];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <!-- Notice -->
    <div class="mix-notice">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 16v-4M12 8h.01"/>
        </svg>
        Đặt hàng tùy chỉnh theo công thức riêng chỉ áp dụng cho đơn hàng trên 5 tấn
    </div>
    
    <!-- Products Selection Section -->
    <div class="mix-products-section">
        <div class="products-row">
            <!-- Product 1 (Main Product) -->
            <div class="product-card main-product">
                <div class="product-header">
                    <span class="product-label">Sản phẩm 1</span>
                    <h3 class="product-title"><?php echo esc_html($main_product['item_name']); ?></h3>
                    <p class="product-description"><?php echo esc_html($main_product['description']); ?></p>
                </div>
                
                <!-- Tiered Pricing -->
                <div class="product-sizes">
                    <?php foreach ($product_sizes as $size) : ?>
                        <div class="size-option">
                            <div class="size-name"><?php echo esc_html($size['name']); ?></div>
                            <div class="size-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Mix Percentage Control -->
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Tỷ lệ thành phần:</span>
                        <span class="progress-percentage" id="product1-percentage">50%</span>
                    </div>
                    <div class="mix-slider-container">
                        <input type="range" class="mix-slider" id="product1-slider" min="10" max="90" value="50" data-product="1">
                    </div>
                    <!-- <div class="progress-bar">
                        <div class="progress-fill" id="product1-fill" style="width: 50%"></div>
                    </div> -->
                </div>
            </div>
            
            <!-- Product 2 (Dropdown Selection) -->
            <div class="product-card secondary-product">
                <div class="product-header">
                    <div class="product-header-top">
                        <span class="product-label">Sản phẩm 2</span>
                        <a href="#" class="change-product-link" id="change-product-2">Đổi sản phẩm</a>
                    </div>
                    <div class="product-dropdown-container" id="dropdown-container-2">
                        <select class="product-dropdown" id="second-product-select">
                            <option value="">Bấm vào để chọn sản phẩm</option>
                            <?php foreach ($other_products as $product): ?>
                                <option value="<?php echo esc_attr($product['item_code']); ?>" 
                                        data-name="<?php echo esc_attr($product['item_name']); ?>"
                                        data-description="<?php echo esc_attr($product['description']); ?>">
                                    <?php echo esc_html($product['item_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="dropdown-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </div>
                </div>
                
                <!-- Will be populated when product is selected -->
                <div class="second-product-content" style="display: none;">
                    <div class="product-sizes">
                        <!-- Same pricing structure as product 1 -->
                        <?php foreach ($product_sizes as $size) : ?>
                            <div class="size-option">
                                <div class="size-name"><?php echo esc_html($size['name']); ?></div>
                                <div class="size-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Mix Percentage Control -->
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Tỷ lệ thành phần:</span>
                            <span class="progress-percentage" id="product2-percentage">50%</span>
                        </div>
                        <div class="mix-slider-container">
                            <input type="range" class="mix-slider" id="product2-slider" min="10" max="90" value="50" data-product="2">
                        </div>
                        <!-- <div class="progress-bar">
                            <div class="progress-fill" id="product2-fill" style="width: 50%"></div>
                        </div> -->
                    </div>
                </div>
            </div>
            
            <!-- Add Product Badge -->
            <div class="add-product-badge" id="add-product-badge">+</div>
            
            <!-- Product 3 (Hidden initially) -->
            <div class="product-card third-product" id="third-product-card">
                <button class="remove-product-btn" id="remove-product-3">×</button>
                <div class="product-header">
                    <div class="product-header-top">
                        <span class="product-label">Sản phẩm 3</span>
                        <a href="#" class="change-product-link" id="change-product-3">Đổi sản phẩm</a>
                    </div>
                    <div class="product-dropdown-container" id="dropdown-container-3">
                        <select class="product-dropdown" id="third-product-select">
                            <option value="">Bấm vào để chọn sản phẩm</option>
                            <?php foreach ($other_products as $product): ?>
                                <option value="<?php echo esc_attr($product['item_code']); ?>" 
                                        data-name="<?php echo esc_attr($product['item_name']); ?>"
                                        data-description="<?php echo esc_attr($product['description']); ?>">
                                    <?php echo esc_html($product['item_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="dropdown-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </div>
                </div>
                
                <!-- Will be populated when product is selected -->
                <div class="third-product-content" style="display: none;">
                    <div class="product-sizes">
                        <?php foreach ($product_sizes as $size) : ?>
                            <div class="size-option">
                                <div class="size-name"><?php echo esc_html($size['name']); ?></div>
                                <div class="size-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Mix Percentage Control -->
                    <div class="progress-section">
                        <div class="progress-label">
                            <span>Tỷ lệ thành phần:</span>
                            <span class="progress-percentage" id="product3-percentage">0%</span>
                        </div>
                        <div class="mix-slider-container">
                            <input type="range" class="mix-slider" id="product3-slider" min="10" max="80" value="0" data-product="3">
                        </div>
                        <!-- <div class="progress-bar">
                            <div class="progress-fill" id="product3-fill" style="width: 0%"></div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Options Section (Hidden initially) -->
    <div class="mix-options-section" id="mix-options" style="display: none;">
        
        <!-- Color Selection - Giống SKU selection -->
        <div class="option-group">
            <h3 class="option-title">Chọn màu</h3>
            <div class="color-grid">
                <?php foreach ($color_options as $index => $color): ?>
                    <div class="color-option <?php echo $index === 0 ? 'selected' : ''; ?>">
                        <div class="color-content">
                            <input type="radio" name="color" value="<?php echo esc_attr($color['id']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                            <div class="color-image-wrap">
                                <div style="width: 100%; height: 100%; background-color: <?php echo esc_attr($color['color']); ?>; border-radius: 50%;"></div>
                            </div>
                            <div class="color-name"><?php echo esc_html($color['name']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Scent Selection -->
        <div class="option-group">
            <h3 class="option-title">Chọn mùi</h3>
            <div class="scent-grid">
                <?php foreach ($scent_options as $index => $scent): ?>
                    <label class="scent-option <?php echo $index === 1 ? 'selected' : ''; ?>">
                        <input type="radio" name="scent" value="<?php echo esc_attr($scent['id']); ?>" <?php echo $index === 1 ? 'checked' : ''; ?>>
                        <span class="scent-name"><?php echo esc_html($scent['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Quantity Selection -->
        <div class="option-group">
            <h3 class="option-title">Chọn số lượng</h3>
            <div class="quantity-grid">
                <?php foreach ($quantity_options as $index => $quantity): ?>
                    <label class="quantity-option <?php echo $index === 2 ? 'selected' : ''; ?>">
                        <input type="radio" name="quantity" value="<?php echo esc_attr($quantity['value']); ?>" <?php echo $index === 2 ? 'checked' : ''; ?>>
                        <span class="quantity-label"><?php echo esc_html($quantity['label']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Packaging Selection -->
        <div class="option-group">
            <h3 class="option-title">Chọn loại túi đóng gói</h3>
            <div class="packaging-options">
                <a href="#" class="view-details-link">Xem minh họa các loại túi</a>
                <?php foreach ($packaging_options as $index => $packaging): ?>
                    <label class="packaging-option <?php echo $index === 1 ? 'selected' : ''; ?>">
                        <input type="radio" name="packaging" value="<?php echo esc_attr($packaging['id']); ?>" <?php echo $index === 1 ? 'checked' : ''; ?>>
                        <div class="packaging-content">
                            <div class="packaging-header">
                                <span class="packaging-name"><?php echo esc_html($packaging['name']); ?></span>
                                <span class="packaging-price"><?php echo esc_html($packaging['description']); ?></span>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Fixed Footer Summary (Hidden initially) -->
<div class="mix-footer-summary" id="mix-footer" style="display: none;">
    <div class="footer-summary-container">
        <div class="footer-left-section">
            <div class="footer-info-group">
                <div class="footer-top-row">
                    Mix 2 loại sản phẩm
                </div>
                <div class="footer-bottom-row">
                    Tổng số lượng: <span id="footer-total-quantity">10,000 kg</span>
                </div>
            </div>
            
            <div class="footer-divider"></div>
            
            <div class="footer-info-group">
                <div class="footer-top-row">
                    Báo giá dự kiến
                </div>
                <div class="footer-bottom-row">
                    <div class="footer-pricing-row">
                        <span class="footer-total-amount" id="footer-estimated-price">250 triệu</span>
                        <span class="footer-price-per-unit" id="footer-price-per-kg">25,000 đ/kg</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-right-section">
            <button type="button" class="next-step-btn" id="next-step-button">
                Qua bước tiếp theo
                <span class="arrow-icon">→</span>
            </button>
        </div>
    </div>
</div>

<?php get_footer(); ?>