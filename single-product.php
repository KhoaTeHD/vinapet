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

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';

// Khởi tạo provider
$product_provider = new Sample_Product_Provider();

// Lấy thông tin sản phẩm
$product_response = $product_provider->get_product($product_code);


// Kiểm tra sản phẩm có tồn tại không
if (!isset($product_response['success']) || !$product_response['success']) {
    echo "$product_code";
    get_template_part('template-parts/content', 'none');
    get_footer();
    return;
}

$product = $product_response['data'];

// Lấy thông tin sản phẩm
$product_name = isset($product['item_name']) ? $product['item_name'] : '';
$product_desc = isset($product['description']) ? $product['description'] : '';
$product_image = isset($product['image']) ? $product['image'] : '';
$product_price = isset($product['standard_rate']) ? $product['standard_rate'] : 0;
$product_code = isset($product['item_code']) ? $product['item_code'] : '';
$product_category = isset($product['item_group']) ? $product['item_group'] : '';

// Nếu không có hình ảnh, sử dụng hình mặc định
if (empty($product_image)) {
    $product_image = get_template_directory_uri() . '/assets/images/placeholder.jpg';
}

// Lấy thông tin tên danh mục
$category_info = [];
if (!empty($product_category)) {
    $categories_response = $product_provider->get_product_categories();
    if (isset($categories_response['data'])) {
        foreach ($categories_response['data'] as $cat) {
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

// Thêm hình ảnh sản phẩm demo
$product_gallery = [
    $product_image,
    get_template_directory_uri() . '/assets/images/products/cat-tre-2.jpg',
    get_template_directory_uri() . '/assets/images/products/cat-tre-3.jpg',
    get_template_directory_uri() . '/assets/images/products/cat-tre-4.jpg',
    get_template_directory_uri() . '/assets/images/products/cat-tre-5.jpg',
];

// Biến thể sản phẩm (màu sắc)
$product_variants = [
    ['name' => 'Cốm - Màu xanh non', 'image' => get_template_directory_uri() . '/assets/images/variants/green.jpg'],
    ['name' => 'Sữa - Màu tự nhiên', 'image' => get_template_directory_uri() . '/assets/images/variants/white.jpg'],
    ['name' => 'Cà phê - Màu nâu', 'image' => get_template_directory_uri() . '/assets/images/variants/brown.jpg'],
    ['name' => 'Sen - Màu hồng', 'image' => get_template_directory_uri() . '/assets/images/variants/pink.jpg'],
];

// Các quy cách đóng gói
$product_sizes = [
    ['name' => '0,5 - 1 tấn', 'price' => 50000, 'unit' => 'đ/kg'],
    ['name' => '1 - 5 tấn', 'price' => 42000, 'unit' => 'đ/kg'],
    ['name' => 'Trên 5 tấn', 'price' => 34000, 'unit' => 'đ/kg'],
];

// Thông số kỹ thuật
$product_specs = [
    ['name' => 'Độ bụi', 'value' => 'dưới 0.5%'],
    ['name' => 'Thời gian vón cục', 'value' => 'dưới 10 giây'],
    ['name' => 'Khả năng thấm hút', 'value' => '210 - 250%'],
    ['name' => 'Thời gian rã trong nước', 'value' => 'dưới 3 giây'],
    ['name' => 'Kháng khuẩn, nấm mốc', 'value' => 'trên 14 ngày'],
    ['name' => 'Khử mùi', 'value' => 'trên 3 ngày'],
    ['name' => 'Tỉ trọng', 'value' => '0.45 - 0.5 g/ml'],
];
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
                    <button class="slider-nav prev-slide">‹</button>
                    <button class="slider-nav next-slide">›</button>
                </div>
            </div>
            
            <!-- Product Description -->
            <div class="product-description-section">
                <h2 class="section-title">Mô tả</h2>
                <div class="product-description-content">
                    <p>Tre, nguyên liệu của sự kiện cường và tinh thần Việt Nam, nay được chuyển hóa thành một sản phẩm đầy ý nghĩa – cát vệ sinh cho mèo từ nguồn nguyên liệu phụ phẩm nông nghiệp Việt Nam. Sản phẩm này không những được chế tạc từ tre, mà còn gắn liền với những giá trị chuyên về thiên nhiên và con người Việt, được thu mua từ các hộ tác xã và bà con nông dân địa phương. Từng hạt cát tre là kết tinh của sự chăm chỉ, sự khéo léo, và lòng yêu thương dành cho thú cưng.</p>
                    <p>Cát tre Vinapet sở hữu các đặc tính ưu việt:</p>
                    <ul>
                        <li><strong>Siêu khử mùi:</strong> Loại bỏ hoàn toàn mùi hôi từ chất thải của mèo.</li>
                        <li><strong>Khống chế mùi tự nhiên:</strong> Không cần thêm hương liệu hóa học.</li>
                        <li><strong>Siêu nhẹ:</strong> Trọng lượng chỉ bằng 1/3 cát thông thường.</li>
                        <li><strong>Thấm hút mạnh mẽ:</strong> Khả năng hút nước gấp 2.5 lần trọng lượng.</li>
                        <li><strong>Vón cục nhanh:</strong> Dễ dàng loại bỏ chất thải mà không lãng phí.</li>
                    </ul>
                </div>
            </div>
            
            <!-- Product Specifications -->
            <div class="product-specs-section">
                <h2 class="section-title">Thông tin sản phẩm</h2>
                
                <!-- Tabs -->
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
                                    <th><?php echo esc_html($spec['name']); ?></th>
                                    <td><?php echo esc_html($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tab-content" id="tab-2">
                    <table class="specs-table">
                        <tbody>
                            <?php foreach ($product_specs as $spec) : ?>
                                <tr>
                                    <th><?php echo esc_html($spec['name']); ?></th>
                                    <td><?php echo esc_html($spec['value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <th>Tỷ lệ SAP</th>
                                <td>8-12%</td>
                            </tr>
                            <tr>
                                <th>Độ bền SAP</th>
                                <td>Trên 30 ngày</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Right column: Product Info & Actions (40%) -->
        <div class="product-right-column">
            <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>
            
            <div class="product-short-desc">
                <p>Siêu Khử Mùi & Khống Chế Mùi Tự Ưu, Siêu Nhẹ & Thấm Hút Mạnh Mẽ</p>
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
                <div class="variant-label">SKU (Màu - Mùi)</div>
                <div class="variant-options">
                    <?php foreach ($product_variants as $index => $variant) : ?>
                        <div class="variant-option" data-variant="<?php echo $index === 0 ? 'com' : ($index === 1 ? 'sua' : ($index === 2 ? 'cafe' : 'sen')); ?>">
                            <div class="variant-image-wrap">
                                <img src="<?php echo esc_url($variant['image']); ?>" alt="<?php echo esc_attr($variant['name']); ?>">
                            </div>
                            <div class="variant-name"><?php echo esc_html($variant['name']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Product Actions -->
            <div class="product-actions">
                <button class="primary-button add-to-cart-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    Đặt hàng
                </button>
                <button class="secondary-button mix-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Mix với hạt khác
                </button>
            </div>
        </div>
    </div>
    
    <?php
    // Lấy sản phẩm liên quan
    $related_products = $product_provider->get_products([
        'category' => $product_category,
        'limit' => 4,
        'exclude' => $product_code
    ]);
    
    if (isset($related_products['data']) && !empty($related_products['data'])): 
    ?>
        <div class="related-products">
            <h2 class="section-title">Sản phẩm liên quan</h2>
            
            <div class="products-grid">
                <?php foreach ($related_products['data'] as $related_product): 
                    $related_name = isset($related_product['item_name']) ? $related_product['item_name'] : '';
                    $related_desc = isset($related_product['description']) ? $related_product['description'] : '';
                    $related_image = isset($related_product['image']) ? $related_product['image'] : '';
                    $related_code = isset($related_product['item_code']) ? $related_product['item_code'] : '';
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
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
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
        redirectToOrderPage();
    });
    
    function redirectToOrderPage() {
        // Lấy variant được chọn từ data-variant attribute
        var selectedVariant = $('.variant-option.selected').data('variant') || 'com';
        
        // Lấy product code
        var productCode = '<?php echo $product_code; ?>';
        
        // Redirect với parameters
        var orderUrl = '<?php echo home_url("/mix-voi-hat-khac"); ?>?product=' + encodeURIComponent(productCode) + '&variant=' + encodeURIComponent(selectedVariant);
        
        console.log('Redirecting to:', orderUrl);
        window.location.href = orderUrl;
    }
    
});
</script>



<?php get_footer(); ?>