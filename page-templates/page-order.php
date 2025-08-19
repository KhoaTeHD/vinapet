<?php
/**
 * Template Name: Order Page
 * Description: Trang đặt hàng sản phẩm
 */

get_header();

// Lấy thông tin sản phẩm từ URL parameters
$product_code = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
$selected_variant = isset($_GET['variant']) ? sanitize_text_field($_GET['variant']) : '';

if (empty($product_code)) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$product_code = strtoupper($product_code);

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';
$product_provider = new Sample_Product_Provider();
$product_response = $product_provider->get_product($product_code);

if (!isset($product_response['success']) || !$product_response['success']) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$product = $product_response['data'];

// Lấy thông tin sản phẩm
$product_name = isset($product['item_name']) ? $product['item_name'] : '';
$product_desc = isset($product['description']) ? $product['description'] : '';
$product_image = isset($product['image']) ? $product['image'] : '';

if (empty($product_image)) {
    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
}

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
    ['name' => $product_name, 'url' => home_url('/san-pham/' . $product_code)],
    ['name' => 'Đặt hàng', 'url' => '']
];

// Tiered pricing
$product_sizes = [
    ['name' => '0,5 - 1 tấn', 'price' => 50000, 'unit' => 'đ/kg'],
    ['name' => '1 - 5 tấn', 'price' => 42000, 'unit' => 'đ/kg'],
    ['name' => 'Trên 5 tấn', 'price' => 34000, 'unit' => 'đ/kg'],
];

// Biến thể sản phẩm (màu sắc)
$product_variants = [
    ['id' => 'com', 'name' => 'Mùi cốm - Màu xanh non', 'image' => get_template_directory_uri() . '/assets/images/variants/green.jpg'],
    ['id' => 'sua', 'name' => 'Mùi sữa - Màu tự nhiên', 'image' => get_template_directory_uri() . '/assets/images/variants/white.jpg'],
    ['id' => 'cafe', 'name' => 'Mùi cà phê - Màu nâu', 'image' => get_template_directory_uri() . '/assets/images/variants/brown.jpg'],
    ['id' => 'sen', 'name' => 'Mùi sen - Màu hồng', 'image' => get_template_directory_uri() . '/assets/images/variants/pink.jpg'],
    ['id' => 'vanilla', 'name' => 'Mùi vanilla - Màu vàng', 'image' => get_template_directory_uri() . '/assets/images/variants/yellow.jpg'],
];

// Số lượng options
$quantity_options = [
    ['value' => '100', 'label' => '100 kg'],
    ['value' => '300', 'label' => '300 kg'],
    ['value' => '500', 'label' => '500 kg'],
    ['value' => '1000', 'label' => '1 tấn'],
    ['value' => '3000', 'label' => '3 tấn'],
    ['value' => '5000', 'label' => '5 tấn'],
];

// Loại túi đóng gói
$packaging_options = [
    [
        'id' => 'pa_pe_thuong',
        'name' => 'Túi PA/PE in màu thường',
        'description' => 'Trọng tải: 2.2~2.4kg/túi - Đã bao gồm chi phí thùng carton dùng cho vận chuyển (800đ/kg)',
        'price' => 2600
    ],
    [
        'id' => 'pa_pe_khong',
        'name' => 'Túi PA/PE in màu hút chân không',
        'description' => 'Trọng tải: 2.2~2.4kg/túi - Đã bao gồm chi phí thùng carton dùng cho vận chuyển (800đ/kg)',
        'price' => 2600
    ],
    [
        'id' => 'pa_pe_decal',
        'name' => 'Túi PA/PE trong và dán decal',
        'description' => 'Trọng tải: 2.2~2.4kg/túi - Đã bao gồm chi phí thùng carton dùng cho vận chuyển (800đ/kg)',
        'price' => 2350
    ],
    [
        'id' => 'bao_dua',
        'name' => 'Bao tải dừa + 1 lót PE',
        'description' => 'Trọng tải: 20~25kg/túi',
        'price' => 160
    ],
    [
        'id' => 'tui_jumbo',
        'name' => 'Túi Jumbo (chỉ áp dụng từ 1 tấn)',
        'description' => 'Trọng tải: 500kg - 1 tấn/túi',
        'price' => 105
    ]
];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <div class="order-page-container">
        <!-- Left Column - Product Info (35%) -->
        <div class="order-left-column">
            <div class="product-info-card">
                <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>
                <p class="product-short-desc"><?php echo esc_html($product_desc); ?></p>
                
                <!-- Tiered Pricing -->
                <div class="pricing-info">
                    <?php foreach ($product_sizes as $size): ?>
                        <div class="price-tier">
                            <span class="tier-range"><?php echo esc_html($size['name']); ?></span>
                            <span class="tier-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Product Image -->
                <div class="product-image-container">
                    <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_name); ?>" class="product-image">
                </div>
                
                <!-- Store Logo -->
                <div class="store-logo">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/vinapet-logo.png" alt="VinaPet Logo" class="logo">
                </div>
            </div>
        </div>
        
        <!-- Right Column - Order Form (65%) -->
        <div class="order-right-column">
            <div class="order-form-card">
                <form id="order-form" class="order-form">
                    <!-- SKU Selection -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn SKU (Mùi - Màu)</h3>
                        <div class="variant-grid">
                            <?php foreach ($product_variants as $index => $variant): ?>
                                <label class="variant-option <?php echo ($index === 0 || $variant['id'] === $selected_variant) ? 'selected' : ''; ?>">
                                    <input type="radio" name="variant" value="<?php echo esc_attr($variant['id']); ?>" <?php echo ($index === 0 || $variant['id'] === $selected_variant) ? 'checked' : ''; ?>>
                                    <div class="variant-content">
                                        <div class="variant-image">
                                            <img src="<?php echo esc_url($variant['image']); ?>" alt="<?php echo esc_attr($variant['name']); ?>">
                                        </div>
                                        <div class="variant-label"><?php echo esc_html($variant['name']); ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quantity Selection -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn thông số đặt hàng</h3>
                        <div class="subsection">
                            <h4 class="subsection-title">Số lượng</h4>
                            <div class="quantity-grid">
                                <?php foreach ($quantity_options as $index => $option): ?>
                                    <label class="quantity-option <?php echo $index === 0 ? 'selected' : ''; ?>">
                                        <input type="radio" name="quantity" value="<?php echo esc_attr($option['value']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <span class="option-label"><?php echo esc_html($option['label']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Packaging Selection -->
                    <div class="form-section">
                        <div class="subsection">
                            <div class="subsection-header">
                                <h4 class="subsection-title">Loại túi đóng gói</h4>
                                <a href="#" class="view-details-link">Xem minh họa các loại túi</a>
                            </div>
                            <div class="packaging-options">
                                <?php foreach ($packaging_options as $index => $option): ?>
                                    <label class="packaging-option <?php echo $index === 0 ? 'selected' : ''; ?>">
                                        <input type="radio" name="packaging" value="<?php echo esc_attr($option['id']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <div class="option-content">
                                            <div class="option-header">
                                                <span class="option-name"><?php echo esc_html($option['name']); ?></span>
                                                <span class="option-price">+<?php echo number_format($option['price'], 0, ',', '.'); ?> đ/kg</span>
                                            </div>
                                            <div class="option-description"><?php echo esc_html($option['description']); ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Tổng số lượng:</span>
                            <span id="total-quantity">100 kg</span>
                        </div>
                        <div class="summary-row">
                            <span>Giá cơ bản:</span>
                            <span id="base-price">5,000,000 đ</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí đóng gói:</span>
                            <span id="packaging-fee">260,000 đ</span>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng cộng:</span>
                            <span id="total-price">5,260,000 đ</span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Quay lại</button>
                        <button type="submit" class="btn btn-primary">Tiếp tục đặt hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>