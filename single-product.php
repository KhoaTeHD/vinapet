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

// Nhúng class cung cấp dữ liệu mẫu
require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';

// Khởi tạo provider
$product_provider = new Sample_Product_Provider();

// Lấy thông tin sản phẩm
$product_response = $product_provider->get_product($product_code);

// Kiểm tra sản phẩm có tồn tại không
if (!isset($product_response['success']) || !$product_response['success']) {
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
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <div class="product-detail">
        <div class="product-gallery">
            <div class="product-main-image">
                <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_name); ?>">
            </div>
        </div>
        
        <div class="product-info">
            <h1 class="product-title"><?php echo esc_html($product_name); ?></h1>
            
            <?php if ($product_price > 0): ?>
                <div class="product-price">
                    <?php echo number_format($product_price, 0, ',', '.'); ?> đ
                </div>
            <?php endif; ?>
            
            <?php if (!empty($category_info)): ?>
                <div class="product-category">
                    <span class="label">Danh mục:</span>
                    <span class="value"><?php echo esc_html($category_info['display_name']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($product_code)): ?>
                <div class="product-code">
                    <span class="label">Mã sản phẩm:</span>
                    <span class="value"><?php echo esc_html($product_code); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($product_desc)): ?>
                <div class="product-description">
                    <?php echo wpautop($product_desc); ?>
                </div>
            <?php endif; ?>
            
            <div class="product-actions">
                <div class="quantity-selector">
                    <button class="qty-btn minus">-</button>
                    <input type="number" class="qty-input" value="1" min="1" max="100">
                    <button class="qty-btn plus">+</button>
                </div>
                
                <button class="add-to-cart-btn" data-product-code="<?php echo esc_attr($product_code); ?>">
                    <span class="icon">🛒</span>
                    <span class="text">Thêm vào giỏ hàng</span>
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
                                <h3 class="product-title"><?php echo esc_html($related_name); ?></h3>
                                <p class="product-description"><?php echo esc_html(wp_trim_words($related_desc, 12, '...')); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
