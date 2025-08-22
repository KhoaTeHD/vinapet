<?php
/**
 * VinaPet Theme functions and definitions
 *
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Định nghĩa các hằng số cho theme
 */
define('VINAPET_THEME_DIR', get_template_directory());
define('VINAPET_THEME_URI', get_template_directory_uri());
define('VINAPET_VERSION', '1.0.0');

/**
 * Các tính năng cơ bản của theme
 */
function vinapet_setup() {
    // Thêm hỗ trợ cho title tag động
    add_theme_support('title-tag');
    
    // Thêm hỗ trợ cho thumbnails
    add_theme_support('post-thumbnails');
    
    // Thêm hỗ trợ cho HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    
    // Đăng ký menu
    register_nav_menus(array(
        'primary' => esc_html__('Menu chính', 'vinapet'),
        'footer' => esc_html__('Menu footer', 'vinapet'),
    ));
    
    // Thêm kích thước hình ảnh tùy chỉnh
    add_image_size('product-thumbnail', 600, 800, true);
}
add_action('after_setup_theme', 'vinapet_setup');

/**
 * Đăng ký các scripts và styles cho theme
 */
function vinapet_scripts() {
    // CSS chung
    wp_enqueue_style('vinapet-style', get_stylesheet_uri(), array(), VINAPET_VERSION);
    
    // CSS riêng cho trang sản phẩm
    if (is_page_template('page-templates/page-product.php')) {
        wp_enqueue_style('vinapet-product-listing', VINAPET_THEME_URI . '/assets/css/product-listing.css', array(), VINAPET_VERSION);
    }
    
    // CSS cho trang chi tiết sản phẩm
    if (get_query_var('product_code')) {
        wp_enqueue_style('vinapet-product-detail', VINAPET_THEME_URI . '/assets/css/product-detail.css', array(), VINAPET_VERSION);
    }
    
    // JavaScript chung
    wp_enqueue_script('jquery');
    wp_enqueue_script('vinapet-navigation', VINAPET_THEME_URI . '/assets/js/navigation.js', array('jquery'), VINAPET_VERSION, true);
    
    // JavaScript riêng cho trang sản phẩm
    if (is_page_template('page-templates/page-product.php')) {
        wp_enqueue_script('vinapet-product-listing', VINAPET_THEME_URI . '/assets/js/product-listing.js', array('jquery'), VINAPET_VERSION, true);
    }
    
    // JavaScript cho trang chi tiết sản phẩm
    if (get_query_var('product_code')) {
        wp_enqueue_script('vinapet-product-detail', VINAPET_THEME_URI . '/assets/js/product-detail.js', array('jquery'), VINAPET_VERSION, true);
    }

    if (is_page_template('page-templates/page-order.php')) {
        wp_enqueue_style('vinapet-order-page', VINAPET_THEME_URI . '/assets/css/order-page.css', array(), VINAPET_VERSION);
        wp_enqueue_script('vinapet-order-page', VINAPET_THEME_URI . '/assets/js/order-page.js', array('jquery'), VINAPET_VERSION, true);
    }

    // CSS và JS cho trang checkout
    if (is_page_template('page-templates/page-checkout.php')) {
        wp_enqueue_style('vinapet-checkout-page', VINAPET_THEME_URI . '/assets/css/checkout-page.css', array(), VINAPET_VERSION);
        wp_enqueue_script('vinapet-checkout-page', VINAPET_THEME_URI . '/assets/js/checkout-page.js', array('jquery'), VINAPET_VERSION, true);
    }
    
    // Cung cấp dữ liệu cho JavaScript
    wp_localize_script('vinapet-navigation', 'vinapet_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vinapet_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'vinapet_scripts');

/**
 * Thiết lập rewrite rules cho trang sản phẩm
 * Điều này sẽ cho phép URL có dạng: /san-pham/ten-san-pham
 */
function vinapet_rewrite_rules() {
    // Rewrite rule cho trang chi tiết sản phẩm
    add_rewrite_rule(
        'san-pham/([^/]+)/?$',
        'index.php?product_code=$matches[1]',
        'top'
    );
    
    // Đăng ký query var mới
    add_rewrite_tag('%product_code%', '([^&]+)');
}
add_action('init', 'vinapet_rewrite_rules');

/**
 * Xử lý template cho trang chi tiết sản phẩm
 */
function vinapet_template_include($template) {
    if (get_query_var('product_code')) {
        $new_template = locate_template('single-product.php');
        if (!empty($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'vinapet_template_include');

/**
 * Khởi tạo session
 */
function vinapet_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'vinapet_start_session');

/**
 * Đăng ký AJAX cho chức năng giỏ hàng
 */
function vinapet_add_to_cart() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    $product_code = isset($_POST['product_code']) ? sanitize_text_field($_POST['product_code']) : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if (empty($product_code) || $quantity < 1) {
        wp_send_json_error('Dữ liệu không hợp lệ');
    }
    
    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['vinapet_cart'])) {
        $_SESSION['vinapet_cart'] = array();
    }
    
    // Thêm sản phẩm vào giỏ hàng hoặc cập nhật số lượng
    if (isset($_SESSION['vinapet_cart'][$product_code])) {
        $_SESSION['vinapet_cart'][$product_code] += $quantity;
    } else {
        $_SESSION['vinapet_cart'][$product_code] = $quantity;
    }
    
    // Tính tổng số lượng sản phẩm trong giỏ hàng
    $cart_count = 0;
    foreach ($_SESSION['vinapet_cart'] as $qty) {
        $cart_count += $qty;
    }
    
    wp_send_json_success(array(
        'cart_count' => $cart_count,
        'message' => 'Thêm sản phẩm vào giỏ hàng thành công'
    ));
}
add_action('wp_ajax_add_to_cart', 'vinapet_add_to_cart');
add_action('wp_ajax_nopriv_add_to_cart', 'vinapet_add_to_cart');

/**
 * Cập nhật số lượng giỏ hàng trong header
 */
function vinapet_cart_count() {
    $cart_count = 0;
    
    if (isset($_SESSION['vinapet_cart'])) {
        foreach ($_SESSION['vinapet_cart'] as $qty) {
            $cart_count += $qty;
        }
    }
    
    return $cart_count;
}

/**
 * Hàm rút gọn văn bản
 */
function vinapet_truncate_text($text, $length = 100, $more = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $more;
}

/**
 * Xử lý hiển thị giá sản phẩm
 */
function vinapet_format_price($price) {
    return number_format($price, 0, ',', '.') . ' đ';
}

/**
 * Tạo template part cho breadcrumbs
 */
function vinapet_breadcrumbs() {
    global $breadcrumb_data;
    
    if (empty($breadcrumb_data)) {
        return;
    }
    
    echo '<div class="breadcrumbs-bar">';
    echo '<ul class="breadcrumbs-list">';
    
    foreach ($breadcrumb_data as $index => $item) {
        echo '<li class="breadcrumb-item">';
        
        if (!empty($item['url'])) {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
        } else {
            echo '<span>' . esc_html($item['name']) . '</span>';
        }
        
        if ($index < count($breadcrumb_data) - 1) {
            echo '<span class="breadcrumb-separator">/</span>';
        }
        
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
}

/**
 * Sử dụng mã tạm thời cho API khi chưa tích hợp ERPNext
 * Nhúng các file cần thiết để tạo dữ liệu mẫu
 */
require_once VINAPET_THEME_DIR . '/includes/data/sample-products.php';
require_once VINAPET_THEME_DIR . '/includes/api/class-sample-product-provider.php';

/**
 * Tạo thư mục includes nếu chưa tồn tại
 */
function vinapet_create_directories() {
    $directories = array(
        VINAPET_THEME_DIR . '/includes',
        VINAPET_THEME_DIR . '/includes/api',
        VINAPET_THEME_DIR . '/includes/data',
        VINAPET_THEME_DIR . '/template-parts',
        VINAPET_THEME_DIR . '/assets',
        VINAPET_THEME_DIR . '/assets/css',
        VINAPET_THEME_DIR . '/assets/js',
        VINAPET_THEME_DIR . '/assets/images',
        VINAPET_THEME_DIR . '/assets/images/products',
        VINAPET_THEME_DIR . '/page-templates'
    );
    
    foreach ($directories as $directory) {
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }
    }
}
add_action('after_switch_theme', 'vinapet_create_directories');

/**
 * Flush rewrite rules sau khi theme được kích hoạt
 */
function vinapet_theme_activation() {
    vinapet_create_directories();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'vinapet_theme_activation');

