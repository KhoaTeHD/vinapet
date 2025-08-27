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
define('VINAPET_VERSION', '1.0.0');
define('VINAPET_THEME_DIR', get_template_directory());
define('VINAPET_THEME_URI', get_template_directory_uri());

/**
 * Các tính năng cơ bản của theme
 */
function vinapet_setup() {
    // Thêm hỗ trợ cho title tag động
    add_theme_support('title-tag');

    // Thêm hỗ trợ cho logo tùy chỉnh
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Support for Elementor
    add_theme_support('elementor');
    
    // Support for Elementor Pro features
    add_theme_support('elementor-pro');
    
    // Set Elementor page template support
    add_post_type_support('page', 'elementor');

    // Remove theme default CSS on Elementor pages
    add_action('elementor/frontend/after_enqueue_styles', function() {
        if (is_front_page() && get_page_template_slug() === 'elementor_header_footer') {
            wp_dequeue_style('vinapet-style');
        }
    });
    
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

    // CSS và JS cho trang mix products
    if (is_page_template('page-templates/page-mix.php')) {
        wp_enqueue_style('vinapet-mix-products', VINAPET_THEME_URI . '/assets/css/mix-products.css', array(), VINAPET_VERSION);
        wp_enqueue_script('vinapet-mix-products', VINAPET_THEME_URI . '/assets/js/mix-products.js', array('jquery'), VINAPET_VERSION, true);
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

/**
 * Helper functions for mix orders
 */

/**
 * Get display name for mix variants/colors/scents
 */
function vinapet_get_mix_option_name($type, $value) {
    $options = array(
        'color' => array(
            'xanh_non' => 'Màu xanh non',
            'hong_nhat' => 'Màu hồng nhạt',
            'vang_dat' => 'Màu vàng đất',
            'do_gach' => 'Màu đỏ gạch',
            'be_nhat' => 'Màu be nhạt',
            'den' => 'Màu đen'
        ),
        'scent' => array(
            'com' => 'Mùi cốm',
            'tro_xanh' => 'Mùi trà xanh',
            'ca_phe' => 'Mùi cà phê',
            'sen' => 'Mùi sen',
            'sua' => 'Mùi sữa',
            'chanh' => 'Mùi chanh'
        ),
        'packaging' => array(
            'tui_jumbo_500' => 'Túi Jumbo 500 kg',
            'tui_jumbo_1000' => 'Túi Jumbo 1 tấn',
            'pa_pe_thuong' => 'Túi 8 Biên PA / PE Hút Chân Không',
            'pa_pe_khong' => 'Túi 8 Biên PA / PE Hút Chân Không',
            'pa_pe_decal' => 'Túi PA / PE Trong + Decal',
            'bao_dua' => 'Bao Tải Dừa + Lót 1 lớp PE',
            'tui_jumbo' => 'Túi Jumbo'
        )
    );
    
    if (isset($options[$type]) && isset($options[$type][$value])) {
        return $options[$type][$value];
    }
    
    return $value; // Return original value if not found
}

/**
 * Check if current checkout is from mix page
 */
function vinapet_is_mix_checkout() {
    // Check session storage or other indicators
    // This is primarily handled by JavaScript, but can be used for server-side logic
    return isset($_SESSION['vinapet_is_mix_checkout']) && $_SESSION['vinapet_is_mix_checkout'];
}

/**
 * AJAX handler for mix checkout requests
 */
function vinapet_submit_mix_checkout_request() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    if (!current_user_can('read')) {
        wp_send_json_error('Bạn không có quyền thực hiện hành động này.');
    }
    
    // Lấy dữ liệu mix từ POST
    $mix_data = isset($_POST['mix_data']) ? json_decode(stripslashes($_POST['mix_data']), true) : array();
    $checkout_data = isset($_POST['checkout_data']) ? json_decode(stripslashes($_POST['checkout_data']), true) : array();
    
    // Validate dữ liệu mix
    if (empty($mix_data) || !isset($mix_data['type']) || $mix_data['type'] !== 'mix') {
        wp_send_json_error('Dữ liệu mix không hợp lệ.');
    }
    
    // Validate checkout data
    if (empty($checkout_data)) {
        wp_send_json_error('Dữ liệu checkout không hợp lệ.');
    }
    
    // Kiểm tra các trường bắt buộc
    $required_fields = ['packaging_design', 'delivery_timeline', 'shipping_method'];
    foreach ($required_fields as $field) {
        if (!isset($checkout_data[$field]) || empty($checkout_data[$field])) {
            wp_send_json_error('Vui lòng điền đầy đủ thông tin bắt buộc.');
        }
    }
    
    // Validate mix products
    if (empty($mix_data['products']) || !isset($mix_data['products']['product1']) || !isset($mix_data['products']['product2'])) {
        wp_send_json_error('Cần ít nhất 2 sản phẩm để tạo đơn hàng mix.');
    }
    
    // Tạo mix request data
    $request_data = array_merge($mix_data, $checkout_data);
    $request_data['request_type'] = 'mix_order';
    $request_data['request_time'] = current_time('mysql');
    $request_data['user_ip'] = $_SERVER['REMOTE_ADDR'];
    
    // Tính tổng tỷ lệ để validate
    $total_percentage = 0;
    foreach (['product1', 'product2', 'product3'] as $product_key) {
        if (isset($mix_data['products'][$product_key]) && isset($mix_data['products'][$product_key]['percentage'])) {
            $total_percentage += $mix_data['products'][$product_key]['percentage'];
        }
    }
    
    if (abs($total_percentage - 100) > 1) {
        wp_send_json_error('Tổng tỷ lệ các sản phẩm phải bằng 100%.');
    }
    
    // Log request for debugging
    error_log('VinaPet Mix Checkout Request: ' . json_encode($request_data));
    
    // Ở đây có thể tích hợp với ERPNext API để tạo đơn hàng mix
    // ...
    
    // Simulate processing time
    sleep(1);
    
    wp_send_json_success(array(
        'message' => 'Yêu cầu đơn hàng tùy chỉnh đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.',
        'request_id' => 'MIX' . time(),
        'mix_info' => array(
            'products_count' => $mix_data['activeProductCount'],
            'total_quantity' => $mix_data['quantity_kg'],
            'estimated_price' => $mix_data['total_price']
        )
    ));
}
add_action('wp_ajax_submit_mix_checkout_request', 'vinapet_submit_mix_checkout_request');
add_action('wp_ajax_nopriv_submit_mix_checkout_request', 'vinapet_submit_mix_checkout_request');

/**
 * Format mix percentage for display
 */
function vinapet_format_mix_percentage($percentage) {
    return number_format($percentage, 0) . '%';
}

/**
 * Generate mix order summary for emails/notifications
 */
function vinapet_generate_mix_order_summary($mix_data) {
    if (!is_array($mix_data) || !isset($mix_data['products'])) {
        return '';
    }
    
    $summary = "=== ĐƠN HÀNG TÙY CHỈNH (MIX) ===\n\n";
    
    // Products information
    $summary .= "Thành phần sản phẩm:\n";
    foreach (['product1', 'product2', 'product3'] as $product_key) {
        if (isset($mix_data['products'][$product_key]) && !empty($mix_data['products'][$product_key]['name'])) {
            $product = $mix_data['products'][$product_key];
            $summary .= "- {$product['name']}: {$product['percentage']}%\n";
        }
    }
    
    // Options information
    if (isset($mix_data['options'])) {
        $options = $mix_data['options'];
        $summary .= "\nTùy chọn:\n";
        $summary .= "- Màu: " . vinapet_get_mix_option_name('color', $options['color']) . "\n";
        $summary .= "- Mùi: " . vinapet_get_mix_option_name('scent', $options['scent']) . "\n";
        $summary .= "- Túi: " . vinapet_get_mix_option_name('packaging', $options['packaging']) . "\n";
        
        $quantity = isset($options['quantity']) && $options['quantity'] !== 'khac' ? 
            $options['quantity'] : '10000';
        $quantity_text = $quantity >= 1000 ? 
            number_format($quantity / 1000, 0) . ' tấn' : 
            number_format($quantity, 0) . ' kg';
        $summary .= "- Số lượng: {$quantity_text}\n";
    }
    
    // Price information
    if (isset($mix_data['total_price'])) {
        $summary .= "\nGiá dự kiến: " . number_format($mix_data['total_price'], 0, ',', '.') . " đ\n";
        
        if (isset($mix_data['base_price_per_kg']) && isset($mix_data['packaging_price_per_kg'])) {
            $price_per_kg = $mix_data['base_price_per_kg'] + $mix_data['packaging_price_per_kg'];
            $summary .= "Giá/kg: " . number_format($price_per_kg, 0, ',', '.') . " đ\n";
        }
    }
    
    return $summary;
}

/**
 * Add body class for mix checkout pages
 */
function vinapet_add_mix_body_class($classes) {
    if (is_page_template('page-templates/page-mix.php')) {
        $classes[] = 'mix-products-page';
    }
    
    // Check if checkout is from mix (via JavaScript flag or session)
    if (is_page_template('page-templates/page-checkout.php')) {
        $classes[] = 'checkout-page';
        // Additional class will be added by JavaScript when mix data is detected
    }
    
    return $classes;
}
add_filter('body_class', 'vinapet_add_mix_body_class');

/**
 * Enqueue scripts specifically for mix functionality
 */
function vinapet_mix_scripts_enhancement() {
    if (is_page_template('page-templates/page-checkout.php')) {
        // Add inline script to detect mix checkout and add body class
        $script = "
        jQuery(document).ready(function($) {
            // Check if this is a mix checkout
            const mixData = sessionStorage.getItem('vinapet_mix_data');
            if (mixData) {
                $('body').addClass('mix-checkout-page');
                $('.summary-title').addClass('mix-title');
                // Add mix-item class to order items
                $('.order-item').addClass('mix-item');
                // Style percentage displays
                $('.order-item .item-quantity').each(function() {
                    if ($(this).text().includes('%')) {
                        $(this).addClass('percentage');
                    }
                });
            }
        });
        ";
        
        wp_add_inline_script('vinapet-checkout-page', $script);
    }
}
add_action('wp_enqueue_scripts', 'vinapet_mix_scripts_enhancement', 20);

// Header
function vinapet_header_assets() {
   if (is_admin()) {
        return;
    }
    
    // Enqueue existing header CSS
    wp_enqueue_style('vinapet-header', VINAPET_THEME_URI . '/assets/css/header.css', array(), VINAPET_VERSION);
    
    // Enqueue header JavaScript
    wp_enqueue_script('vinapet-header', VINAPET_THEME_URI . '/assets/js/header.js', array('jquery'), VINAPET_VERSION, true);
}
add_action('wp_enqueue_scripts', 'vinapet_header_assets');

/**
 * VinaPet Authentication Integration
 */


// Include authentication integration class
require_once VINAPET_THEME_DIR . '/includes/auth/auth-integration.php';

function vinapet_init_auth_integration() {
    new VinaPet_Auth_Integration();
}
add_action('init', 'vinapet_init_auth_integration', 1);

// Initialize default ERPNext settings
function vinapet_init_default_erpnext_settings() {
    $default_settings = array(
        'api_url' => '',
        'api_key' => '',
        'api_secret' => '',
        'enabled' => false,
        'sync_on_register' => true,
        'sync_on_login' => true,
        'customer_group' => 'All Customer Groups',
        'territory' => 'All Territories',
    );
    
    if (!get_option('vinapet_erpnext_settings')) {
        add_option('vinapet_erpnext_settings', $default_settings);
    }
}
add_action('init', 'vinapet_init_default_erpnext_settings', 5);

// Add customer role on theme activation
function vinapet_add_customer_role() {
    if (!get_role('customer')) {
        add_role('customer', 'Khách hàng', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));
    }
}
add_action('after_switch_theme', 'vinapet_add_customer_role');

// Prevent customers from accessing wp-admin
// function vinapet_restrict_admin_access() {
//     if (is_admin() && !current_user_can('edit_posts') && !defined('DOING_AJAX')) {
//         wp_redirect(home_url());
//         exit;
//     }
// }
// add_action('admin_init', 'vinapet_restrict_admin_access');

// Utility functions
function vinapet_is_erpnext_enabled() {
    $settings = get_option('vinapet_erpnext_settings', array());
    return !empty($settings['enabled']) && !empty($settings['api_url']) && !empty($settings['api_key']);
}

function vinapet_get_erpnext_settings() {
    return get_option('vinapet_erpnext_settings', array());
}

// header

function force_custom_header() {
    // Vô hiệu hóa header của Elementor
    remove_action('elementor/theme/before_render', 'elementor_theme_do_location');
    
    // Đảm bảo header custom luôn được load
    add_action('wp_head', 'ensure_custom_header', 1);
}
add_action('init', 'force_custom_header');

function ensure_custom_header() {
    if (!is_admin()) {
        // Force load header template
        add_action('wp_body_open', 'load_custom_header', 1);
    }
}


add_filter('show_admin_bar', '__return_false');

// Include Account Functions - Tích hợp trang tài khoản
if (file_exists(VINAPET_THEME_DIR . '/includes/account-functions.php')) {
    require_once VINAPET_THEME_DIR . '/includes/account-functions.php';
}

// footer

// Include footer initialization
if (file_exists(VINAPET_THEME_DIR . '/includes/footer-init.php')) {
    require_once VINAPET_THEME_DIR . '/includes/footer-init.php';
}

// Include footer admin (chỉ trong admin)
if (is_admin() && file_exists(VINAPET_THEME_DIR . '/includes/admin/footer-admin.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/footer-admin.php';
}