<?php

/**
 * Template for displaying single product
 *
 * @package VinaPet
 */

// THÊM MỚI: Lấy SEO data nếu có
$seo_title = '';
$seo_description = '';
$seo_og_image = '';

if (isset($product['seo'])) {
    $seo_title = $product['seo']['title'];
    $seo_description = $product['seo']['description'];
    $seo_og_image = $product['seo']['og_image'];
}

// Fallback nếu không có SEO custom
if (empty($seo_title)) {
    $seo_title = $product_name . ' - VinaPet';
}

if (empty($seo_description)) {
    $seo_description = wp_trim_words($product_desc, 25, '...');
}

if (empty($seo_og_image)) {
    $seo_og_image = $product_image;
}

get_header();

// Lấy mã sản phẩm từ URL
//$product_code = get_query_var('product_code', '');

// Lấy product slug từ URL
$product_slug = get_query_var('product_slug', '');

if (empty($product_slug)) {
    // Fallback: check old query var
    $product_slug = get_query_var('product_code', '');
}

if (empty($product_slug)) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

// Resolve product code từ smart URL
$product_code = Smart_URL_Router::resolve_product($product_slug);
    
if (!$product_code) {
    // Show 404 với suggestions
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

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
$product_description = isset($product['description']) ? $product['description'] : '';
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

$product_prices = $data_manager->get_product_price_detail($product_code);

$prices = [];

foreach ($product_prices['price_detail'][1] as $price) {
    $prices[]  = [
        'name' => $price['title'],
        'price' => $price['value'],
        'unit' => 'đ/kg',
        'min_quantity' => $price['min_qty']
    ];
}


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
                    <button class="slider-nav prev-slide">‹</button>
                    <button class="slider-nav next-slide">›</button>
                </div>
            </div>

            <!-- Product Description -->
            <div class="product-description-section">
                <h2 class="section-title">Mô tả</h2>
                <div class="product-description-content">
                    <?php echo $product_description; ?>
                    <!-- <p>Tre, nguyên liệu của sự kiện cường và tinh thần Việt Nam, nay được chuyển hóa thành một sản phẩm đầy ý nghĩa – cát vệ sinh cho mèo từ nguồn nguyên liệu phụ phẩm nông nghiệp Việt Nam. Sản phẩm này không những được chế tạc từ tre, mà còn gắn liền với những giá trị chuyên về thiên nhiên và con người Việt, được thu mua từ các hộ tác xã và bà con nông dân địa phương. Từng hạt cát tre là kết tinh của sự chăm chỉ, sự khéo léo, và lòng yêu thương dành cho thú cưng.</p>
                    <p>Cát tre Vinapet sở hữu các đặc tính ưu việt:</p>
                    <ul>
                        <li><strong>Siêu khử mùi:</strong> Loại bỏ hoàn toàn mùi hôi từ chất thải của mèo.</li>
                        <li><strong>Khống chế mùi tự nhiên:</strong> Không cần thêm hương liệu hóa học.</li>
                        <li><strong>Siêu nhẹ:</strong> Trọng lượng chỉ bằng 1/3 cát thông thường.</li>
                        <li><strong>Thấm hút mạnh mẽ:</strong> Khả năng hút nước gấp 2.5 lần trọng lượng.</li>
                        <li><strong>Vón cục nhanh:</strong> Dễ dàng loại bỏ chất thải mà không lãng phí.</li>
                    </ul> -->
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

            <!-- Product About Us -->
            <div class="product-about-us gallery-vinapet">
                <h2 class="section-title">Về Vinapet</h2>
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/aboutVinapet/image1.png" alt="Vinapet">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/aboutVinapet/image2.png" alt="Vinapet">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/aboutVinapet/image3.png" alt="Vinapet">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/aboutVinapet/image4.png" alt="Vinapet">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/aboutVinapet/image5.png" alt="Vinapet">
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
                <?php foreach ($prices as $size) : ?>
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
                <button class="primary-button" id="vinapet-order-btn" data-product="<?php echo esc_attr($product_code); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1" />
                        <circle cx="20" cy="21" r="1" />
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                    </svg>
                    <span class="btn-text">Đặt hàng</span>
                    <span class="btn-loading" style="display:none;">Đang xử lý...</span>
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
            if (isset($related['ProductID']) && $related['ProductID'] !== $product_code && count($related_products) < 4) {
                $related_products[] = $related;
            }
        }
    }

    //$related_products[] = $related_products_response;

    if (!empty($related_products)):
    ?>
        <div class="related-products">
            <h2 class="section-title">Sản phẩm khác của vinapet</h2>

            <div class="products-grid">
                <?php foreach ($related_products as $related_product):
                    $related_name = isset($related_product['Ten_SP']) ? $related_product['Ten_SP'] : '';
                    $related_desc = isset($related_product['Mo_ta_ngan']) ? strip_tags($related_product['Mo_ta_ngan']) : '';
                    $related_image = isset($related_product['Thumbnail_File']) ? $related_product['Thumbnail_File'] : '';
                    $related_code = isset($related_product['ProductID']) ? $related_product['ProductID'] : '';
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
    // Check nếu vinapet_data tồn tại (từ functions.php hiện tại)
    const ajaxData = typeof vinapet_data !== 'undefined' ? vinapet_data : 
                     typeof vinapet_order_ajax !== 'undefined' ? vinapet_order_ajax : null;
    
    if (!ajaxData) {
        console.error('VinaPet: AJAX data not found');
        return;
    }
    
    // Handler cho button đặt hàng (normal order)
    $('#vinapet-order-btn').on('click', function(e) {
        e.preventDefault();
        processOrder($(this), 'normal');
    });
    
    // Handler cho button mix (mix order)
    $('.mix-button').on('click', function(e) {
        e.preventDefault();
        processOrder($(this), 'mix');
        //redirectToMixPage();
    });
    
    function processOrder($btn, orderType) {
        const productCode = $('#vinapet-order-btn').data('product');
        const variant = $('.variant-option.selected').data('variant') || '';
        
        // Loading state
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.html('<span>Đang xử lý...</span>');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'vinapet_store_order',
                product_code: productCode,
                variant: variant,
                order_type: orderType,
                nonce: ajaxData.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else if (response.data.code === 'login_required') {
                    // Mở popup login
                    if (typeof VinaPetAuth === 'object' && typeof VinaPetAuth.open === 'function') {
                        VinaPetAuth.open();
                    } else {
                        alert('Bạn cần đăng nhập để đặt hàng');
                    }
                } else {
                    alert('Lỗi: ' + (response.data.message || 'Không thể xử lý'));
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            },
            complete: function() {
                // Reset button
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    }
});
</script>


<?php get_footer(); ?>