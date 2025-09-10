<?php

/**
 * Template for displaying single product
 *
 * @package VinaPet
 */

get_header();

// Lấy mã sản phẩm từ URL
$product_code = get_query_var('product_code', '');

if (empty($product_code)) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

$product_code = strtoupper($product_code);

// Nhúng class Product Data Manager
require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';

// Khởi tạo manager
$data_manager = new Product_Data_Manager();

// Lấy thông tin sản phẩm
$product_response = $data_manager->get_product($product_code);


// Kiểm tra sản phẩm có tồn tại không
if (!isset($product_response['product']) || !$product_response['product']) {
    echo "$product_code";
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

$product = $product_response['product'];

// Lấy thông tin sản phẩm
$product_name = isset($product['product_name']) ? $product['product_name'] : '';
$product_desc = isset($product['short_description']) ? $product['short_description'] : '';
$product_image = isset($product['thumbnail']) ? $product['thumbnail'] : '';
$product_price = isset($product['standard_rate']) ? $product['standard_rate'] : 0;
$product_code = isset($product['product_code']) ? $product['product_code'] : '';
$product_category = isset($product['item_group']) ? $product['item_group'] : '';

// Nếu không có hình ảnh, sử dụng hình mặc định
if (empty($product_image)) {
    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
}

// Lấy thông tin tên danh mục
$category_info = [];
if (!empty($product_category)) {
    $categories_response = $data_manager->get_categories();
    if (isset($categories_response['categories'])) {
        foreach ($categories_response['categories'] as $cat) {
            if ($cat['name'] === $product_category) {
                $category_info = $cat;
                break;
            }
        }
    }
}

// Thêm breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
];

if (!empty($category_info)) {
    $breadcrumb_data[] = [
        'name' => $category_info['display_name'],
        'url' => home_url('/san-pham?category=' . $category_info['name'])
    ];
}

$breadcrumb_data[] = ['name' => $product_name, 'url' => ''];

// Thêm hình ảnh sản phẩm
$product_gallery = isset($product['gallery']) && !empty($product['gallery']) ? $product['gallery'] : [];
if (empty($product_gallery)) {
    $product_gallery = [
        $product_image,
        get_template_directory_uri() . '/assets/images/products/cat-tre-2.jpg',
        get_template_directory_uri() . '/assets/images/products/cat-tre-3.jpg',
        get_template_directory_uri() . '/assets/images/products/cat-tre-4.jpg',
        get_template_directory_uri() . '/assets/images/products/cat-tre-5.jpg',
    ];
}

// Biến thể sản phẩm
$product_variants = isset($product['variants']) && !empty($product['variants']) ? $product['variants'] : [
    ['variant_name' => 'Cốm - Màu xanh non', 'thumbnail' => get_template_directory_uri() . '/assets/images/variants/green.jpg'],
    ['variant_name' => 'Sữa - Màu tự nhiên', 'thumbnail' => get_template_directory_uri() . '/assets/images/variants/white.jpg'],
    ['variant_name' => 'Cà phê - Màu nâu', 'thumbnail' => get_template_directory_uri() . '/assets/images/variants/brown.jpg'],
    ['variant_name' => 'Sen - Màu hồng', 'thumbnail' => get_template_directory_uri() . '/assets/images/variants/pink.jpg'],
];

// Các quy cách đóng gói
$product_sizes = [
    ['name' => '0,5 - 1 tấn', 'price' => 50000, 'unit' => 'đ/kg'],
    ['name' => '1 - 5 tấn', 'price' => 42000, 'unit' => 'đ/kg'],
    ['name' => 'Trên 5 tấn', 'price' => 34000, 'unit' => 'đ/kg'],
];

// Thông số kỹ thuật
$product_specs = isset($product['specifications']['original']) ? $product['specifications']['original'] : [
    ['specification' => 'Độ bụi', 'value' => 'dưới 0.5%'],
    ['specification' => 'Thời gian vón cục', 'value' => 'dưới 10 giây'],
    ['specification' => 'Khả năng thấm hút', 'value' => '210 - 250%'],
    ['specification' => 'Thời gian rã trong nước', 'value' => 'dưới 3 giây'],
    ['specification' => 'Kháng khuẩn, nấm mốc', 'value' => 'trên 14 ngày'],
    ['specification' => 'Khử mùi', 'value' => 'trên 3 ngày'],
    ['specification' => 'Tỉ trọng', 'value' => '0.45 - 0.5 g/ml'],
];

// Thông số kỹ thuật SAP
$product_specs_sap = isset($product['specifications']['sap']) ? $product['specifications']['sap'] : [];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <div class="product-detail-container">
        <!-- Left column: Product Gallery & Description (60%) -->
        <div class="product-left-column">
            <!-- Product Gallery with Thumbnails -->
            <div class="product-gallery">
                <!-- Thumbnails Column - Bên trái -->
                <div class="product-thumbnails">
                    <?php foreach ($product_gallery as $index => $gallery_image) : ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <img src="<?php echo esc_url($gallery_image); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Main Image - Bên phải -->
                <div class="product-main-image">
                    <div class="product-slider">
                        <?php foreach ($product_gallery as $index => $gallery_image) : ?>
                            <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo esc_url($gallery_image); ?>" alt="<?php echo esc_attr($product_name . ' - ' . ($index + 1)); ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Navigation arrows -->
                    <button class="nav-arrow prev" aria-label="Previous image">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" fill="currentColor"/>
                        </svg>
                    </button>
                    <button class="nav-arrow next" aria-label="Next image">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Product Details -->
            <div class="product-specs-section">
                <h2 class="section-title">thông số kỹ thuật</h2>
                
                <!-- Tab buttons -->
                <div class="specs-tabs">
                    <button class="tab-btn active" data-tab="tab-1">Nguyên bản</button>
                    <button class="tab-btn" data-tab="tab-2">Có hạt SAP</button>
                </div>

                <!-- Tab content -->
                <div class="tab-content active" id="tab-1">
                    <table class="specs-table">
                        <tbody>
                            <?php foreach ($product_specs as $spec) : ?>
                                <tr>
                                    <th><?php echo esc_html($spec['specification']); ?></th>
                                    <td><?php echo esc_html($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-content" id="tab-2">
                    <table class="specs-table">
                        <tbody>
                            <?php 
                            // Nếu có specs SAP thì dùng, không thì dùng specs original + thêm info SAP
                            $specs_to_show = !empty($product_specs_sap) ? $product_specs_sap : $product_specs;
                            foreach ($specs_to_show as $spec) : 
                            ?>
                                <tr>
                                    <th><?php echo esc_html($spec['specification']); ?></th>
                                    <td><?php echo esc_html($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($product_specs_sap)) : ?>
                            <tr>
                                <th>Tỷ lệ SAP</th>
                                <td>8-12%</td>
                            </tr>
                            <tr>
                                <th>Độ bền SAP</th>
                                <td>Trên 30 ngày</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right column: Product Info & Actions (40%) -->
        <div class="product-right-column">
            <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>

            <div class="product-short-desc">
                <?php if (!empty($product_desc)) : ?>
                    <?php echo wp_kses_post($product_desc); ?>
                <?php else : ?>
                    <p>Siêu Khử Mùi & Khống Chế Mùi Tự Ưu, Siêu Nhẹ & Thấm Hút Mạnh Mẽ</p>
                <?php endif; ?>
            </div>

            <!-- Product Sizes -->
            <div class="product-sizes">
                <?php foreach ($product_sizes as $size) : ?>
                    <div class="size-option">
                        <div class="size-name"><?php echo esc_html($size['name']); ?></div>
                        <div class="size-price"><?php echo number_format($size['price'], 0, ',', '.'); ?> <span class="unit"><?php echo esc_html($size['unit']); ?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Product Variants (Colors) -->
            <div class="product-variants">
                <div class="variant-label">SKU (Mùi - Màu)</div>
                <div class="variant-options">
                    <?php foreach ($product_variants as $index => $variant) : ?>
                        <div class="variant-option" data-variant="<?php echo $index === 0 ? 'com' : ($index === 1 ? 'sua' : ($index === 2 ? 'cafe' : 'sen')); ?>">
                            <div class="variant-image-wrap">
                                <img src="<?php echo esc_url(isset($variant['thumbnail']) ? $variant['thumbnail'] : get_template_directory_uri() . '/assets/images/variants/default.jpg'); ?>" alt="<?php echo esc_attr($variant['variant_name']); ?>">
                            </div>
                            <div class="variant-name"><?php echo esc_html($variant['variant_name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Actions -->
            <div class="product-actions">
                <button class="primary-button add-to-cart-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                    Đặt hàng
                </button>
                <button class="secondary-button mix-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Mix với hạt khác
                </button>
            </div>
        </div>
    </div>

    <?php
    // Lấy sản phẩm liên quan
    $related_products_response = $data_manager->get_products([
        'limit' => 4
    ]);
    
    $related_products = [];
    if (isset($related_products_response['products']) && !empty($related_products_response['products'])) {
        foreach ($related_products_response['products'] as $related) {
            if (isset($related['product_code']) && $related['product_code'] !== $product_code && count($related_products) < 4) {
                $related_products[] = $related;
            }
        }
    }

    if (!empty($related_products)):
    ?>
        <div class="related-products">
            <h2 class="section-title">Sản phẩm khác của vinapet</h2>

            <div class="products-grid">
                <?php foreach ($related_products as $related_product):
                    $related_name = isset($related_product['product_name']) ? $related_product['product_name'] : '';
                    $related_desc = isset($related_product['short_description']) ? strip_tags($related_product['short_description']) : '';
                    $related_image = isset($related_product['thumbnail']) ? $related_product['thumbnail'] : '';
                    $related_code = isset($related_product['product_code']) ? $related_product['product_code'] : '';
                    $related_url = home_url('/san-pham/' . sanitize_title($related_code));

                    if (empty($related_image)) {
                        $related_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
                    }
                ?>
                    <div class="product-card" onclick="window.location.href='<?php echo esc_url($related_url); ?>'">
                        <div class="product-image" style="background-image: url('<?php echo esc_url($related_image); ?>');">
                            <div class="product-overlay">
                                <div class="product-title-container">
                                    <h3 class="product-title">
                                        <span class="title-text"><?php echo esc_html($related_name); ?></span>
                                    </h3>
                                    <div class="arrow-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
                                            <path d="M5 12h14" />
                                            <path d="m12 5 7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="product-description"><?php echo esc_html(wp_trim_words($related_desc, 12, '...')); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Cập nhật nút đặt hàng
        $('.add-to-cart-btn').on('click', function(e) {
            e.preventDefault();
            redirectToOrderPage();
        });

        function redirectToOrderPage() {
            // Lấy variant được chọn từ data-variant attribute
            var selectedVariant = $('.variant-option.selected').data('variant') || 'com';

            // Lấy product code
            var productCode = '<?php echo $product_code; ?>';

            // Redirect với parameters
            var orderUrl = '<?php echo home_url("/dat-hang"); ?>?product=' + encodeURIComponent(productCode) + '&variant=' + encodeURIComponent(selectedVariant);

            console.log('Redirecting to:', orderUrl);
            window.location.href = orderUrl;
        }

        // Cập nhật nút mix với hạt khác
        $('.mix-button').on('click', function(e) {
            e.preventDefault();
            redirectToMixPage();
        });

        function redirectToMixPage() {
            // Lấy variant được chọn từ data-variant attribute
            var selectedVariant = $('.variant-option.selected').data('variant') || 'com';

            // Lấy product code
            var productCode = '<?php echo $product_code; ?>';

            // Redirect với parameters
            var mixUrl = '<?php echo home_url("/mix-voi-hat-khac"); ?>?product=' + encodeURIComponent(productCode) + '&variant=' + encodeURIComponent(selectedVariant);

            console.log('Redirecting to:', mixUrl);
            window.location.href = mixUrl;
        }

    });
</script>


<?php get_footer(); ?>