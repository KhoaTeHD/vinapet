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

    // CSS và JS cho trang order
    if (is_page_template('page-templates/page-order.php')) {
        wp_enqueue_style('vinapet-order-page', VINAPET_THEME_URI . '/assets/css/order-page.css', array(), VINAPET_VERSION);
        wp_enqueue_script('vinapet-order-page', VINAPET_THEME_URI . '/assets/js/order-page.js', array('jquery'), VINAPET_VERSION, true);
    }

    // CSS và JS cho trang checkout
    if (is_page_template('page-templates/page-checkout.php')) {
        wp_enqueue_style('vinapet-checkout-page', VINAPET_THEME_URI . '/assets/css/checkout-page.css', array(), VINAPET_VERSION);
        wp_enqueue_script('vinapet-checkout-page', VINAPET_THEME_URI . '/assets/js/checkout-page.js', array('jquery'), VINAPET_VERSION, true);
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
 * Tạo template part cho breadcrumbs - XÓA FUNCTION NÀY VÌ ĐÃ CÓ TEMPLATE PART
 */
// KHÔNG CẦN HÀM NÀY VÌ ĐÃ CÓ template-parts/breadcrumbs-bar.php

/**
 * AJAX handler cho submit checkout request
 */
function vinapet_submit_checkout_request() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error('Bạn không có quyền thực hiện hành động này.');
    }
    
    // Lấy dữ liệu từ POST
    $order_data = isset($_POST['order_data']) ? json_decode(stripslashes($_POST['order_data']), true) : array();
    $checkout_data = isset($_POST['checkout_data']) ? json_decode(stripslashes($_POST['checkout_data']), true) : array();
    
    // Validate dữ liệu
    if (empty($order_data) || empty($checkout_data)) {
        wp_send_json_error('Dữ liệu không hợp lệ.');
    }
    
    // Kiểm tra các trường bắt buộc
    $required_fields = ['packaging_design', 'delivery_timeline', 'shipping_method'];
    foreach ($required_fields as $field) {
        if (!isset($checkout_data[$field]) || empty($checkout_data[$field])) {
            wp_send_json_error('Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
    }
    
    // Tạo request data
    $request_data = array_merge($order_data, $checkout_data);
    $request_data['request_time'] = current_time('mysql');
    $request_data['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Lưu vào database hoặc gửi email
    // Ở đây bạn có thể tích hợp với ERPNext API
    
    // Log request for debugging
    error_log('VinaPet Checkout Request: ' . json_encode($request_data));
    
    // Simulate processing time
    sleep(1);
    
    wp_send_json_success(array(
        'message' => 'Yêu cầu đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.',
        'request_id' => 'VP' . time()
    ));
}
add_action('wp_ajax_submit_checkout_request', 'vinapet_submit_checkout_request');
add_action('wp_ajax_nopriv_submit_checkout_request', 'vinapet_submit_checkout_request');

/**
 * Sử dụng dữ liệu mẫu cho API khi chưa tích hợp ERPNext
 * Nhúng các file đã có trong source
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-products.php')) {
    require_once VINAPET_THEME_DIR . '/includes/data/sample-products.php';
}

if (file_exists(VINAPET_THEME_DIR . '/includes/api/class-sample-product-provider.php')) {
    require_once VINAPET_THEME_DIR . '/includes/api/class-sample-product-provider.php';
}

// Nhúng file admin settings nếu tồn tại (đã có trong source)
if (file_exists(VINAPET_THEME_DIR . '/includes/admin/class-admin-setting.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/class-admin-setting.php';
}

// Nhúng file ERP API client nếu tồn tại (đã có trong source)  
if (file_exists(VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php')) {
    require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
}

/**
 * Tạo thư mục nếu chưa tồn tại - chỉ tạo những thư mục chưa có
 */
function vinapet_create_directories() {
    $directories = array(
        // Chỉ tạo những thư mục có thể chưa tồn tại
        VINAPET_THEME_DIR . '/assets/images/products',
        VINAPET_THEME_DIR . '/assets/images/variants',
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

/**
 * Theme deactivation cleanup
 */
function vinapet_theme_deactivation() {
    flush_rewrite_rules();
}
add_action('switch_theme', 'vinapet_theme_deactivation');

/**
 * Custom body classes
 */
function vinapet_body_classes($classes) {
    // Add class for product detail pages
    if (get_query_var('product_code')) {
        $classes[] = 'single-product-page';
    }
    
    // Add class for checkout pages
    if (is_page_template('page-templates/page-checkout.php')) {
        $classes[] = 'checkout-page';
    }
    
    // Add class for order pages
    if (is_page_template('page-templates/page-order.php')) {
        $classes[] = 'order-page';
    }
    
    return $classes;
}
add_filter('body_class', 'vinapet_body_classes');

/**
 * Add custom query vars
 */
function vinapet_query_vars($vars) {
    $vars[] = 'product_code';
    return $vars;
}
add_filter('query_vars', 'vinapet_query_vars');

/**
 * Security enhancements
 */
function vinapet_security_headers() {
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}
add_action('send_headers', 'vinapet_security_headers');

/**
 * Optimize performance
 */
function vinapet_optimize_performance() {
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Disable emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
}
add_action('init', 'vinapet_optimize_performance');

/**
 * Custom excerpt length
 */
function vinapet_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'vinapet_excerpt_length');

/**
 * Custom excerpt more
 */
function vinapet_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'vinapet_excerpt_more');