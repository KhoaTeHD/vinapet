<?php

/**
 * Template Name: Order Page
 * Description: Trang đặt hàng sản phẩm
 */

get_header();

// Lấy dữ liệu từ session trước
$session = VinaPet_Order_Session::get_instance();
$session_data = $session->get_order();

if ($session_data) {
    // Dùng session data
    $product_code = $session_data['products'][0]['product_code'];
    $selected_variant = $session_data['products'][0]['variant'];
    $order_type = $session_data['order_type'] ?? 'normal';

    // Nếu là mix order, redirect về mix page
    if ($order_type === 'mix') {
        wp_redirect(home_url('/mix-voi-hat-khac'));
        exit;
    }
} else {
    // Fallback sang URL params
    $product_code = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
    $selected_variant = isset($_GET['variant']) ? sanitize_text_field($_GET['variant']) : '';

    if (empty($product_code)) {
        wp_redirect(home_url('/san-pham'));
        exit;
    }
}

$product_code = strtoupper($product_code);

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
$data_manager = new Product_Data_Manager();
$product_response = $data_manager->get_product($product_code);

if (!isset($product_response['product']) || !$product_response['product']) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$product = $product_response['product'];

// Lấy thông tin sản phẩm (thống nhất với single-product.php)
$product_name = isset($product['product_name']) ? $product['product_name'] : '';
$product_desc = isset($product['short_description']) ? $product['short_description'] : '';
$product_image = isset($product['thumbnail']) ? $product['thumbnail'] : '';

if (empty($product_image)) {
    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
}

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
    ['name' => $product_name, 'url' => Smart_URL_Router::generate_product_url($product)],
    ['name' => 'Đặt hàng', 'url' => '']
];

// Tiered pricing with quantity mapping
$product_sizes = [
    ['name' => '0,5 - 1 tấn', 'price' => 50000, 'unit' => 'đ/kg', 'quantities' => ['100', '300', '500', '1000']],
    ['name' => '1 - 5 tấn', 'price' => 42000, 'unit' => 'đ/kg', 'quantities' => ['3000']],
    ['name' => 'Trên 5 tấn', 'price' => 34000, 'unit' => 'đ/kg', 'quantities' => ['5000']],
];

// Biến thể sản phẩm (màu sắc)
$product_variants = [
    ['id' => 'com', 'name' => 'Mùi cốm - Màu xanh non', 'image' => get_template_directory_uri() . '/assets/images/variants/green.jpg'],
    ['id' => 'sua', 'name' => 'Mùi sữa - Màu tự nhiên', 'image' => get_template_directory_uri() . '/assets/images/variants/white.jpg'],
    ['id' => 'cafe', 'name' => 'Mùi cà phê - Màu nâu', 'image' => get_template_directory_uri() . '/assets/images/variants/brown.jpg'],
    ['id' => 'sen', 'name' => 'Mùi sen - Màu hồng', 'image' => get_template_directory_uri() . '/assets/images/variants/pink.jpg'],
    ['id' => 'vanilla', 'name' => 'Mùi vanilla - Màu vàng', 'image' => get_template_directory_uri() . '/assets/images/variants/yellow.jpg'],
    ['id' => 'other', 'name' => 'Mùi khác - Màu khác', 'image' => get_template_directory_uri() . '/assets/images/variants/other.jpg'],
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

$grain_size_options = [
    ['value' => '2.0', 'label' => '2.0'],
    ['value' => '2.5', 'label' => '2.5'],
    ['value' => '3.0', 'label' => '3.0'],
    ['value' => '3.5', 'label' => '3.5'],
    ['value' => 'dap 3.5', 'label' => 'Đập 3.5'],
];

$packages = $data_manager->get_packages();

// Loại túi đóng gói
if ($packages) {
    $packaging_options = $packages;
} else {
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
}

?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <div class="order-page-container">
        <!-- Left Column - Product Info (40%) -->
        <div class="order-left-column">
            <div class="product-info-card">
                <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>
                <p class="product-short-desc"><?php echo $product_desc; ?></p>

                <!-- Product Sizes - Copy layout from single-product.php -->
                <div class="product-sizes">
                    <?php foreach ($product_sizes as $index => $size) : ?>
                        <div class="size-option" data-quantities='<?php echo json_encode($size['quantities']); ?>' data-price="<?php echo $size['price']; ?>">
                            <div class="size-name"><?php echo esc_html($size['name']); ?></div>
                            <div class="size-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></div>
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

        <!-- Right Column - Order Form (60%) -->
        <div class="order-right-column">
            <div class="order-form-card">
                <form id="order-form" class="order-form">
                    <!-- SKU Selection - Hidden Radio (like checkout page) -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn SKU (Mùi - Màu)</h3>
                        <div class="variant-grid">
                            <?php foreach ($product_variants as $index => $variant): ?>
                                <div class="variant-option" value="<?php echo esc_attr($variant['name']); ?>">
                                    <input type="radio" name="variant" value="<?php echo esc_attr($variant['id']); ?>" <?php echo ($variant['id'] === $selected_variant) ? 'checked' : ''; ?>>
                                    <div class="variant-content">
                                        <div class="variant-image">
                                            <img src="<?php echo esc_url($variant['image']); ?>" alt="<?php echo esc_attr($variant['name']); ?>">
                                        </div>
                                        <!-- <div class="variant-label"><?php //echo esc_html($variant['name']); 
                                                                        ?></div> -->
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="variant-label"></div>
                    </div>

                    <!-- size Selection -->
                    <div class="form-section" style="border-top: 1px dashed #C6C5C9; padding-top: 20px;">
                        <h3 class="section-title">Chọn thông số đặt hàng</h3>
                        <div class="subsection">
                            <h4 class="subsection-title">Kích thước hạt</h4>
                            <div class="grain-size-grid">
                                <?php foreach ($grain_size_options as $index => $option): ?>
                                    <label class="grain-size-option">
                                        <input type="radio" name="grain_size" value="<?php echo esc_attr($option['value']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <span class="option-label"><?php echo esc_html($option['label']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selection -->
                    <div class="form-section">
                        <div class="subsection">
                            <h4 class="subsection-title">Số lượng</h4>
                            <div class="quantity-grid">
                                <?php foreach ($quantity_options as $index => $option): ?>
                                    <label class="quantity-option">
                                        <input type="radio" name="quantity" value="<?php echo esc_attr($option['value']); ?>" <?php echo $index === 0 ? 'checked' : ''; ?>>
                                        <span class="option-label"><?php echo esc_html($option['label']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Packaging Selection - Updated with Radio Buttons -->
                    <div class="form-section">
                        <div class="subsection">
                            <div class="subsection-header">
                                <h4 class="subsection-title">Loại túi đóng gói</h4>
                                <a href="#" class="view-details-link">Xem minh họa các loại túi</a>
                            </div>
                            <div class="packaging-options">
                                <?php foreach ($packaging_options as $index => $option): ?>
                                    <label class="packaging-option">
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
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Fixed Footer Summary - Two main sections: left info + right button -->
<div class="order-footer-summary">
    <div class="footer-summary-container">
        <div class="footer-left-section">
            <!-- Info Group 1: SKU and Bag Count -->
            <div class="footer-info-group">
                <div class="footer-top-row">
                    <span id="footer-sku-count">1 SKU</span>, <span id="footer-bag-count">1 loại túi</span>
                </div>
                <div class="footer-bottom-row">
                    Tổng số lượng: <span id="footer-total-quantity">1000 kg</span>
                </div>
            </div>

            <div class="footer-divider"></div>

            <!-- Info Group 2: Pricing -->
            <div class="footer-info-group">
                <div class="footer-top-row">
                    Báo giá dự kiến
                </div>
                <div class="footer-bottom-row">
                    <div class="footer-pricing-row">
                        <span class="footer-total-amount" id="footer-estimated-price">52 triệu</span>
                        <span class="footer-price-per-unit" id="footer-price-per-kg">42,950 đ/kg</span>
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

<script>
    let packagingPrices = <?php echo json_encode(array_column($packaging_options, 'price', 'id')); ?>;
    const product_code = <?php echo json_encode($product_code); ?>;
</script>
<?php get_footer(); ?>