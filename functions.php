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
function vinapet_setup()
{
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
    add_action('elementor/frontend/after_enqueue_styles', function () {
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
function vinapet_scripts()
{
    // CSS chung
    wp_enqueue_style('vinapet-style', get_stylesheet_uri(), array(), VINAPET_VERSION);

    // CSS riêng cho trang sản phẩm
    if (is_page_template('page-templates/page-product.php')) {
        wp_enqueue_style('vinapet-product-listing', VINAPET_THEME_URI . '/assets/css/product-listing.css', array(), VINAPET_VERSION);
    }

    // CSS cho trang chi tiết sản phẩm
    if (get_query_var('product_slug')) {
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

    // JavaScript riêng cho trang sản phẩm
    if (is_page_template('page-templates/page-product.php')) {
        wp_enqueue_script('vinapet-product-listing', VINAPET_THEME_URI . '/assets/js/product-listing.js', array('jquery'), VINAPET_VERSION, true);
    }

    // JavaScript cho trang chi tiết sản phẩm
    if (get_query_var('product_slug')) {
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
 * Smart URL Rewrite Rules
 */
function vinapet_smart_rewrite_rules()
{
    // Product detail rule: /san-pham/{slug-hash}/
    add_rewrite_rule(
        '^san-pham/([^/]+)/?$',
        'index.php?product_slug=$matches[1]',
        'top'
    );
}
add_action('init', 'vinapet_smart_rewrite_rules');

/**
 * Add custom query vars
 */
function vinapet_smart_query_vars($vars)
{
    $vars[] = 'product_slug';
    return $vars;
}
add_filter('query_vars', 'vinapet_smart_query_vars');

/**
 * Load Smart URL Router
 */
function vinapet_load_smart_router()
{
    require_once get_template_directory() . '/includes/helpers/class-smart-url-router.php';
}
add_action('init', 'vinapet_load_smart_router', 5);

/**
 * Smart URL Cache Management
 */
function vinapet_smart_url_cache_groups()
{
    wp_cache_add_global_groups(['vinapet_products']);
}
add_action('init', 'vinapet_smart_url_cache_groups');

/**
 * Clear cache khi cần
 */
function vinapet_clear_smart_url_cache()
{
    wp_cache_flush_group('vinapet_products');
}

// Clear cache AJAX endpoint
add_action('wp_ajax_clear_cache', 'vinapet_clear_smart_url_cache');
add_action('wp_ajax_nopriv_clear_cache', 'vinapet_clear_smart_url_cache');

// Thêm vào functions.php (temporary)
function vinapet_flush_rules_once()
{
    if (!get_option('vinapet_smart_rules_flushed')) {
        flush_rewrite_rules();
        update_option('vinapet_smart_rules_flushed', true);
    }
}
add_action('init', 'vinapet_flush_rules_once', 999);

/**
 * Xử lý template cho trang chi tiết sản phẩm
 */
function vinapet_template_include($template)
{
    if (get_query_var('product_slug')) {
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
function vinapet_start_session()
{
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'vinapet_start_session');

/**
 * Đăng ký AJAX cho chức năng giỏ hàng
 */
function vinapet_add_to_cart()
{
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
function vinapet_cart_count()
{
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
function vinapet_truncate_text($text, $length = 100, $more = '...')
{
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
function vinapet_format_price($price)
{
    return number_format($price, 0, ',', '.') . ' đ';
}

/**
 * Sử dụng dữ liệu mẫu cho API khi chưa tích hợp ERPNext
 * Nhúng các file đã có trong source
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-products.php')) {
    //require_once VINAPET_THEME_DIR . '/includes/data/sample-products.php';
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
function vinapet_create_directories()
{
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
function vinapet_theme_activation()
{
    vinapet_create_directories();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'vinapet_theme_activation');

/**
 * Theme deactivation cleanup
 */
function vinapet_theme_deactivation()
{
    flush_rewrite_rules();
}
add_action('switch_theme', 'vinapet_theme_deactivation');

/**
 * Custom body classes
 */
function vinapet_smart_body_classes($classes)
{
    // Add class for product detail pages
    if (get_query_var('product_slug') || get_query_var('product_code')) {
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
add_filter('body_class', 'vinapet_smart_body_classes');


/**
 * Security enhancements
 */
function vinapet_security_headers()
{
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}
add_action('send_headers', 'vinapet_security_headers');

/**
 * Optimize performance
 */
function vinapet_optimize_performance()
{
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
function vinapet_excerpt_length($length)
{
    return 20;
}
add_filter('excerpt_length', 'vinapet_excerpt_length');

/**
 * Custom excerpt more
 */
function vinapet_excerpt_more($more)
{
    return '...';
}
add_filter('excerpt_more', 'vinapet_excerpt_more');

/**
 * Helper functions for mix orders
 */

/**
 * Get display name for mix variants/colors/scents
 */
function vinapet_get_mix_option_name($type, $value)
{
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
function vinapet_is_mix_checkout()
{
    // Check session storage or other indicators
    // This is primarily handled by JavaScript, but can be used for server-side logic
    return isset($_SESSION['vinapet_is_mix_checkout']) && $_SESSION['vinapet_is_mix_checkout'];
}

/**
 * Format mix percentage for display
 */
function vinapet_format_mix_percentage($percentage)
{
    return number_format($percentage, 0) . '%';
}

/**
 * Add body class for mix checkout pages
 */
function vinapet_add_mix_body_class($classes)
{
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
function vinapet_mix_scripts_enhancement()
{
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
function vinapet_header_assets()
{
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

// Include authentication integration class if exists
if (file_exists(VINAPET_THEME_DIR . '/includes/auth/auth-integration.php')) {
    require_once VINAPET_THEME_DIR . '/includes/auth/auth-integration.php';
}

function vinapet_init_auth_integration()
{
    if (class_exists('VinaPet_Auth_Integration')) {
        new VinaPet_Auth_Integration();
    }
}
add_action('init', 'vinapet_init_auth_integration', 1);

// Initialize default ERPNext settings
function vinapet_init_default_erpnext_settings()
{
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
function vinapet_add_customer_role()
{
    if (!get_role('customer')) {
        add_role('customer', 'Khách hàng', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));
    }
}
add_action('after_switch_theme', 'vinapet_add_customer_role');

// Utility functions
function vinapet_is_erpnext_enabled()
{
    $settings = get_option('vinapet_erpnext_settings', array());
    return !empty($settings['enabled']) && !empty($settings['api_url']) && !empty($settings['api_key']);
}

function vinapet_get_erpnext_settings()
{
    return get_option('vinapet_erpnext_settings', array());
}

// header

function force_custom_header()
{
    // Vô hiệu hóa header của Elementor
    remove_action('elementor/theme/before_render', 'elementor_theme_do_location');

    // Đảm bảo header custom luôn được load
    add_action('wp_head', 'ensure_custom_header', 1);
}
add_action('init', 'force_custom_header');

function ensure_custom_header()
{
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

// Include footer initialization
if (file_exists(VINAPET_THEME_DIR . '/includes/footer-init.php')) {
    require_once VINAPET_THEME_DIR . '/includes/footer-init.php';
}

// Include footer admin (chỉ trong admin)
if (is_admin() && file_exists(VINAPET_THEME_DIR . '/includes/admin/footer-admin.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/footer-admin.php';
}

// Include footer admin (chỉ trong admin)
if (is_admin() && file_exists(VINAPET_THEME_DIR . '/includes/admin/class-suggest-admin.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/class-suggest-admin.php';
}

/**
 * Add responsive viewport meta tag
 */
function vinapet_responsive_meta()
{
    if (is_page_template('page-templates/page-product.php')) {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">' . "\n";
        echo '<meta name="format-detection" content="telephone=no">' . "\n";
    }
}
add_action('wp_head', 'vinapet_responsive_meta', 1);

// Cho phép đăng ký user mới
add_filter('option_users_can_register', '__return_true');

// Include Database Migration
if (file_exists(VINAPET_THEME_DIR . '/includes/db/create-product-meta-table.php')) {
    require_once VINAPET_THEME_DIR . '/includes/db/create-product-meta-table.php';
}

// Include Product Meta Manager
if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-product-meta-manager.php')) {
    require_once VINAPET_THEME_DIR . '/includes/helpers/class-product-meta-manager.php';
}

// Include Products Admin - Class riêng cho quản lý sản phẩm
if (file_exists(VINAPET_THEME_DIR . '/includes/admin/class-products-admin.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/class-products-admin.php';
}

// Include Product Data Manager
if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-product-data-manager.php')) {
    require_once VINAPET_THEME_DIR . '/includes/helpers/class-product-data-manager.php';
}

// Include AJAX Handlers  
if (file_exists(VINAPET_THEME_DIR . '/includes/ajax/ajax-product-handlers.php')) {
    require_once VINAPET_THEME_DIR . '/includes/ajax/ajax-product-handlers.php';
}

// Include Lead AJAX Handler
if (file_exists(VINAPET_THEME_DIR . '/includes/ajax/ajax-lead.php')) {
    require_once VINAPET_THEME_DIR . '/includes/ajax/ajax-lead.php';
}

// Include Lead Form Shortcode
if (file_exists(VINAPET_THEME_DIR . '/includes/shortcodes/shortcode-lead-form.php')) {
    require_once VINAPET_THEME_DIR . '/includes/shortcodes/shortcode-lead-form.php';
}

// Include Quotation Helper
if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-quotation-helper.php')) {
    require_once VINAPET_THEME_DIR . '/includes/helpers/class-quotation-helper.php';
}

// Include Checkout AJAX Handler
if (file_exists(VINAPET_THEME_DIR . '/includes/ajax/ajax-checkout.php')) {
    require_once VINAPET_THEME_DIR . '/includes/ajax/ajax-checkout.php';
}

// ============================================================================
// THÊM VÀO functions.php - ERP CUSTOMER INTEGRATION
// ============================================================================

/**
 * Load Customer Sync Helper
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php')) {
    require_once VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php';
}

/**
 * AJAX handler - Get customer from ERP cho account page
 */
function vinapet_ajax_get_customer_from_erp()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    if (!wp_verify_nonce($_POST['nonce'], 'vinapet_ajax_nonce')) {
        wp_send_json_error('Security check failed');
    }

    $user = wp_get_current_user();

    if (!class_exists('ERP_API_Client')) {
        require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
    }

    $erp_api = new ERP_API_Client();
    $customer_data = $erp_api->get_customer_by_email($user->user_email);

    if ($customer_data && $customer_data['status'] === 'success') {
        // Update user meta từ ERP
        $customer = $customer_data['customer'];

        if (!empty($customer['name'])) {
            update_user_meta($user->ID, 'erpnext_customer_id', $customer['name']);
        }

        if (!empty($customer['custom_phone'])) {
            update_user_meta($user->ID, 'phone_number', $customer['custom_phone']);
        }

        update_user_meta($user->ID, 'erpnext_last_sync', current_time('mysql'));

        wp_send_json_success(array(
            'message' => 'Cập nhật thành công từ ERP',
            'customer_data' => $customer_data
        ));
    } else {
        wp_send_json_error('Không tìm thấy trong ERP');
    }
}
add_action('wp_ajax_vinapet_get_customer_from_erp', 'vinapet_ajax_get_customer_from_erp');

/**
 * Cleanup khi deactivate theme
 */
function vinapet_erp_cleanup()
{
    $timestamp = wp_next_scheduled('vinapet_sync_customers_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'vinapet_sync_customers_cron');
    }
}
add_action('switch_theme', 'vinapet_erp_cleanup');


// Include Session Handler
require_once VINAPET_THEME_DIR . '/includes/helpers/class-order-session.php';

/**
 * Localize order data cho order page
 */
function vinapet_localize_order_data()
{
    if (is_page_template('page-templates/page-order.php')) {
        wp_localize_script('vinapet-order-page', 'vinapet_order_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_nonce'),
            'is_logged_in' => is_user_logged_in()
        ));
    }

    // Localize cho single-product.php
    if (get_query_var('product_slug')) {
        wp_localize_script('vinapet-product-detail', 'vinapet_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_nonce'),
            'is_logged_in' => is_user_logged_in()
        ));
    }
}
add_action('wp_enqueue_scripts', 'vinapet_localize_order_data', 25);


/**
 * AJAX: Store order data trong PHP session (từ order-page)
 */
function vinapet_ajax_store_order_session()
{
    check_ajax_referer('vinapet_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Bạn cần đăng nhập để thực hiện chức năng này!');
    }

    $product_code = sanitize_text_field($_POST['product_code'] ?? '');
    $order_data = $_POST['order_data'] ?? [];

    if (empty($product_code)) {
        wp_send_json_error('Mã sản phẩm không hợp lệ!');
    }

    // Sanitize order data
    $clean_data = [
        'variant' => sanitize_text_field($order_data['variant'] ?? ''),
        'quantity' => intval($order_data['quantity'] ?? 0),
        'packaging' => sanitize_text_field($order_data['packaging'] ?? ''),
        'price_per_kg' => floatval($order_data['price_per_kg'] ?? 0),
        'rate' => floatval($order_data['rate'] ?? 0),
        'total_price' => floatval($order_data['total_price'] ?? 0),

    ];

    //error_log('VinaPet Order Data Sanitized: ' . json_encode($clean_data));
    // Store in unified session
    $session = VinaPet_Order_Session::get_instance();
    $session->store_order($product_code, $clean_data['variant'], $clean_data);

    wp_send_json_success([
        'message' => 'Dữ liệu đã được lưu!',
        'redirect' => home_url('/checkout')
    ]);
}
add_action('wp_ajax_vinapet_store_order_session', 'vinapet_ajax_store_order_session');
add_action('wp_ajax_nopriv_vinapet_store_order_session', 'vinapet_ajax_store_order_session');

/**
 * AJAX: Store mix data trong PHP session (từ mix-page)
 */
function vinapet_ajax_store_mix_session()
{
    check_ajax_referer('vinapet_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error('Bạn cần đăng nhập để thực hiện chức năng này!');
    }

    $mix_data = $_POST['mix_data'] ?? [];

    if (empty($mix_data)) {
        wp_send_json_error('Dữ liệu mix không hợp lệ!');
    }

    // Sanitize mix data
    $clean_data = [
        'products' => [],
        'options' => [],
        'quantities' => [],
        'pricing' => []
    ];

    // Sanitize products
    if (isset($mix_data['products'])) {
        foreach ($mix_data['products'] as $key => $product) {
            if (!empty($product['name'])) {
                $clean_data['products'][$key] = [
                    'code' => sanitize_text_field($product['code']),
                    'name' => sanitize_text_field($product['name']),
                    'percentage' => floatval($product['percentage'] ?? 0),
                    'quantity' => floatval($product['quantity'] ?? 0),
                    'price_per_kg' => floatval($product['price_per_kg'] ?? 0),
                    'total_price' => floatval($product['total_price'] ?? 0),
                    'details' => array_map('sanitize_text_field', $product['details'] ?? [])
                ];
            }
        }
    }

    //error_log('VinaPet Mix Products Sanitized: ' . json_encode($clean_data['products']));

    // Sanitize options
    if (isset($mix_data['options'])) {
        $clean_data['options'] = [
            'color' => sanitize_text_field($mix_data['options']['color'] ?? ''),
            'scent' => sanitize_text_field($mix_data['options']['scent'] ?? ''),
            'packaging' => sanitize_text_field($mix_data['options']['packaging'] ?? ''),
            'quantity' => sanitize_text_field($mix_data['options']['quantity'] ?? '')
        ];
    }

    // Sanitize quantities và pricing
    $clean_data['quantities']['total'] = intval($mix_data['quantities']['total'] ?? 0);
    $clean_data['pricing']['total'] = floatval($mix_data['pricing']['total'] ?? 0);
    $clean_data['pricing']['per_kg'] = floatval($mix_data['pricing']['per_kg'] ?? 0);
    $clean_data['pricing']['rate'] = floatval($mix_data['pricing']['rate'] ?? 0);

    // Validate mix data
    $active_products = array_filter($clean_data['products'], function ($p) {
        return !empty($p['name']);
    });

    if (count($active_products) < 2) {
        wp_send_json_error('Cần ít nhất 2 sản phẩm để tạo đơn hàng mix!');
    }

    $total_percentage = array_sum(array_column($active_products, 'percentage'));
    if (abs($total_percentage - 100) > 1) {
        wp_send_json_error('Tổng tỷ lệ các sản phẩm phải bằng 100%!');
    }

    // Store in unified session
    $session = VinaPet_Order_Session::get_instance();
    $session->store_mix($clean_data);

    wp_send_json_success([
        'message' => 'Dữ liệu mix đã được lưu!',
        'redirect' => home_url('/checkout')
    ]);
}
add_action('wp_ajax_vinapet_store_mix_session', 'vinapet_ajax_store_mix_session');
add_action('wp_ajax_nopriv_vinapet_store_mix_session', 'vinapet_ajax_store_mix_session');

function vinapet_create_order_record($request_data, $order_id)
{
    // Sử dụng existing function từ sample-orders.php
    if (function_exists('vinapet_create_order_from_checkout')) {
        $checkout_data = [
            'title' => ($request_data['order_type'] === 'mix') ? 'Đơn hàng tùy chỉnh' : 'Đơn hàng',
            'items' => vinapet_format_order_items($request_data['order_data']),
            'summary' => vinapet_format_order_summary($request_data),
            'raw_data' => $request_data
        ];

        return vinapet_create_order_from_checkout($checkout_data, $request_data['user_id']);
    }

    return true;
}

/**
 * Helper: Format order items for database storage
 */
function vinapet_format_order_items($order_data)
{
    if ($order_data['type'] === 'mix') {
        return array_map(function ($product) {
            return [
                'name' => $product['name'],
                'quantity' => $product['percentage'] . '%',
                'details' => $product['details'] ?? []
            ];
        }, $order_data['products']);
    } else {
        return [[
            'name' => $order_data['product_name'],
            'quantity' => number_format($order_data['quantity']) . ' kg',
            'details' => [
                'Variant: ' . $order_data['variant'],
                'Mã sản phẩm: ' . $order_data['product_code']
            ]
        ]];
    }
}

/**
 * Helper: Format order summary for database storage
 */
function vinapet_format_order_summary($request_data)
{
    $order_data = $request_data['order_data'];
    $form_data = $request_data['checkout_form'];

    return [
        'total_quantity' => number_format($order_data['total_quantity']) . ' kg',
        'packaging' => vinapet_get_packaging_name($form_data['packaging_design']),
        'delivery_time' => vinapet_get_delivery_name($form_data['delivery_timeline']),
        'shipping' => vinapet_get_shipping_name($form_data['shipping_method']),
        'total_price' => number_format($order_data['estimated_price']) . ' đ',
        'price_per_kg' => number_format($order_data['price_per_kg']) . ' đ/kg'
    ];
}

// Helper functions để get readable names
function vinapet_get_packaging_name($value)
{
    $options = [
        'factory_design' => 'Nhà máy thiết kế',
        'customer_design' => 'Khách hàng thiết kế',
        'vinapet_design' => 'VinaPet thiết kế'
    ];
    return $options[$value] ?? $value;
}

function vinapet_get_delivery_name($value)
{
    $options = [
        'fast' => 'Nhanh (7-15 ngày)',
        'normal' => 'Trung bình (15-30 ngày)',
        'slow' => 'Chậm (30-45 ngày)'
    ];
    return $options[$value] ?? $value;
}

function vinapet_get_shipping_name($value)
{
    $options = [
        'factory_support' => 'Nhà máy hỗ trợ vận chuyển',
        'customer_pickup' => 'Khách hàng tự lấy',
        'third_party' => 'Bên thứ ba vận chuyển'
    ];
    return $options[$value] ?? $value;
}

/**
 * Update AJAX handler cho single-product page
 * Sử dụng unified session thay vì old session class
 */
function vinapet_ajax_store_product_order()
{
    check_ajax_referer('vinapet_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(['code' => 'login_required']);
    }

    $product_code = sanitize_text_field($_POST['product_code'] ?? '');
    $variant = sanitize_text_field($_POST['variant'] ?? '');
    $order_type = sanitize_text_field($_POST['order_type'] ?? 'normal');

    if (empty($product_code)) {
        wp_send_json_error(['message' => 'Mã sản phẩm không hợp lệ']);
    }

    $session = VinaPet_Order_Session::get_instance();

    if ($order_type === 'normal') {
        $session->store_order($product_code, $variant, []);
        $redirect_url = home_url('/dat-hang');
    } else {
        $mix_data = [
            'products' => $product_code,
            'variant' => $variant,
            'order_type' => 'mix',
            'source' => 'single_product'  // ← THÊM để biết nguồn
        ];

        $session->store_mix($mix_data);
        $redirect_url = home_url('/mix-voi-hat-khac');
    }

    wp_send_json_success(['redirect' => $redirect_url]);
}
add_action('wp_ajax_vinapet_store_order', 'vinapet_ajax_store_product_order');
add_action('wp_ajax_nopriv_vinapet_store_order', 'vinapet_ajax_store_product_order');

/**
 * Localize script data cho tất cả pages
 */
function vinapet_localize_unified_data()
{
    // Common AJAX data
    $common_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vinapet_nonce'),
        'is_logged_in' => is_user_logged_in(),
        'home_url' => home_url(),
        'checkout_url' => home_url('/checkout'),
        'account_url' => home_url('/tai-khoan')
    ];

    // Order page
    if (is_page_template('page-templates/page-order.php')) {
        wp_localize_script('vinapet-order-page', 'vinapet_ajax', $common_data);
    }

    // Mix page
    if (is_page_template('page-templates/page-mix.php')) {
        wp_localize_script('vinapet-mix-products', 'vinapet_ajax', $common_data);
    }

    // Checkout page
    if (is_page_template('page-templates/page-checkout.php')) {
        wp_localize_script('vinapet-checkout-page', 'vinapet_ajax', $common_data);
    }

    // Single product page
    if (get_query_var('product_slug')) {
        wp_localize_script('vinapet-product-detail', 'vinapet_data', $common_data);
    }
}
// Replace old localize function
remove_action('wp_enqueue_scripts', 'vinapet_localize_order_data', 25);
add_action('wp_enqueue_scripts', 'vinapet_localize_unified_data', 25);

/**
 * Session cleanup on user logout
 */
function vinapet_cleanup_session_on_logout()
{
    if (class_exists('VinaPet_Unified_Session')) {
        $session = VinaPet_Order_Session::get_instance();
        $session->clear_all();
    }
}
add_action('wp_logout', 'vinapet_cleanup_session_on_logout');

/**
 * Debug helper function (remove in production)
 */
function vinapet_debug_session_data()
{
    if (!current_user_can('manage_options') || !isset($_GET['debug_session'])) {
        return;
    }

    $session = VinaPet_Order_Session::get_instance();
    $data = [
        'order' => $session->get_order(),
        'mix' => $session->get_mix(),
        'checkout_form' => $session->get_checkout_form(),
        'checkout_data' => $session->get_checkout_data()
    ];

    echo '<pre>' . print_r($data, true) . '</pre>';
    wp_die('Session Debug Data');
}
add_action('init', 'vinapet_debug_session_data');

/**
 * Localize script data cho checkout page
 */
function vinapet_localize_checkout_scripts()
{
    // Checkout page
    if (is_page_template('page-templates/page-checkout.php')) {
        $current_user = wp_get_current_user();

        wp_localize_script('vinapet-checkout-page', 'vinapet_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'home_url' => home_url(),
            'account_url' => home_url('/tai-khoan'),
            // Thêm thông tin user
            'current_user' => is_user_logged_in() ? array(
                'id' => $current_user->ID,
                'email' => $current_user->user_email,
                'display_name' => $current_user->display_name,
                'first_name' => get_user_meta($current_user->ID, 'first_name', true),
                'last_name' => get_user_meta($current_user->ID, 'last_name', true),
                'phone' => get_user_meta($current_user->ID, 'phone_number', true),
                'address' => get_user_meta($current_user->ID, 'user_address', true),
                'company_name' => get_user_meta($current_user->ID, 'company_name', true)
            ) : null
        ));
    }
}
add_action('wp_enqueue_scripts', 'vinapet_localize_checkout_scripts', 25);

/**
 * SEO Meta cho trang sản phẩm
 */
function vinapet_add_product_seo_meta()
{
    global $product, $seo_title, $seo_description, $seo_og_image;

    if (is_singular() && isset($product)) {
?>
        <!-- SEO Meta Tags -->
        <title><?php echo esc_html($seo_title); ?></title>
        <meta name="description" content="<?php echo esc_attr($seo_description); ?>">

        <!-- Open Graph -->
        <meta property="og:type" content="product">
        <meta property="og:title" content="<?php echo esc_attr($seo_title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($seo_description); ?>">
        <meta property="og:image" content="<?php echo esc_url($seo_og_image); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
        <meta property="og:site_name" content="VinaPet">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($seo_title); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($seo_description); ?>">
        <meta name="twitter:image" content="<?php echo esc_url($seo_og_image); ?>">

        <!-- Product Schema -->
        <script type="application/ld+json">
            {
                "@context": "https://schema.org/",
                "@type": "Product",
                "name": "<?php echo esc_js($product['product_name']); ?>",
                "image": "<?php echo esc_url($seo_og_image); ?>",
                "description": "<?php echo esc_js($seo_description); ?>",
                "sku": "<?php echo esc_js($product['product_code']); ?>",
                "offers": {
                    "@type": "Offer",
                    "priceCurrency": "VND",
                    "price": "<?php echo esc_js($product['standard_rate'] ?? 0); ?>",
                    "availability": "https://schema.org/InStock"
                }
            }
        </script>
    <?php
    }
}
add_action('wp_head', 'vinapet_add_product_seo_meta');

require_once get_template_directory() . '/includes/ajax/ajax-mix-pricing.php';

// Include Products Grid Shortcode
if (file_exists(VINAPET_THEME_DIR . '/includes/shortcodes/shortcode-products-grid.php')) {
    require_once VINAPET_THEME_DIR . '/includes/shortcodes/shortcode-products-grid.php';
}


/**
 * Floating Contact Button 
 */

// 1. ENQUEUE CSS & JS
function vinapet_floating_contact_assets()
{
    ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Floating Contact Button Styles */
        .floating-container {
            position: fixed;
            bottom: 50px;
            right: 30px;
            z-index: 9999;
        }

        .floating-button {
            width: 60px;
            height: 60px;
            background: #19457B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
        }

        .floating-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }

        .floating-button i {
            color: white;
            font-size: 24px;
            transition: transform 0.3s ease;
        }

        /* Icon chính (message) */
        .floating-button i {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }

        /* Các icon brands (Facebook, TikTok, WhatsApp, WeChat) */
        .floating-contact-item.facebook i,
        .floating-contact-item.tiktok i,
        .floating-contact-item.whatsapp i,
        .floating-contact-item.wechat i {
            font-family: "Font Awesome 6 Brands" !important;
            font-weight: 400 !important;
        }

        /* Các icon solid còn lại (Phone, comment-dots) */
        .floating-contact-item i {
            font-family: "Font Awesome 6 Free" !important;
            font-weight: 900 !important;
        }

        .floating-button.active i {
            transform: rotate(45deg);
        }

        .contact-menu {
            position: absolute;
            bottom: 75px;
            right: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .contact-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .floating-contact-item {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            text-decoration: none;
            animation: slideIn 0.3s ease forwards;
            opacity: 0;
            position: relative;
        }

        .floating-contact-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .floating-contact-item:nth-child(2) {
            animation-delay: 0.15s;
        }

        .floating-contact-item:nth-child(3) {
            animation-delay: 0.2s;
        }

        .floating-contact-item:nth-child(4) {
            animation-delay: 0.25s;
        }

        .floating-contact-item:nth-child(5) {
            animation-delay: 0.3s;
        }

        .floating-contact-item:nth-child(6) {
            animation-delay: 0.35s;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .floating-contact-item:hover {
            transform: scale(1.15);
        }

        .floating-contact-item i,
        .floating-contact-item img {
            color: white;
            font-size: 22px;
            max-width: 24px;
            max-height: 24px;
        }

        .floating-contact-item.facebook {
            background: #1877f2;
        }

        .floating-contact-item.tiktok {
            background: #000000;
        }

        .floating-contact-item.wechat {
            background: #09b83e;
        }

        .floating-contact-item.whatsapp {
            background: #25d366;
        }

        .floating-contact-item.zalo {
            background: #0068ff;
        }

        .floating-contact-item.phone {
            background: #19457B;
        }

        .contact-tooltip {
            position: absolute;
            right: 65px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 13px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
            top: 50%;
            transform: translateY(-50%);
        }

        .floating-contact-item:hover .contact-tooltip {
            opacity: 1;
            visibility: visible;
        }

        .contact-tooltip::after {
            content: '';
            position: absolute;
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid rgba(0, 0, 0, 0.8);
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .floating-container {
                bottom: 20px;
                right: 20px;
            }

            .floating-button {
                width: 55px;
                height: 55px;
            }

            .floating-button i {
                font-size: 22px;
            }

            .floating-contact-item {
                width: 45px;
                height: 45px;
            }

            .floating-contact-item i,
            .floating-contact-item img {
                font-size: 20px;
                max-width: 22px;
                max-height: 22px;
            }

            .contact-tooltip {
                font-size: 12px;
                padding: 6px 10px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const floatingBtn = document.getElementById('floatingContactBtn');
            const contactMenu = document.getElementById('floatingContactMenu');
            let isOpen = false;

            if (!floatingBtn || !contactMenu) return;

            floatingBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                isOpen = !isOpen;

                if (isOpen) {
                    contactMenu.classList.add('show');
                    floatingBtn.classList.add('active');
                } else {
                    contactMenu.classList.remove('show');
                    floatingBtn.classList.remove('active');
                }
            });

            // Đóng menu khi click bên ngoài
            document.addEventListener('click', function(e) {
                if (!floatingBtn.contains(e.target) && !contactMenu.contains(e.target)) {
                    contactMenu.classList.remove('show');
                    floatingBtn.classList.remove('active');
                    isOpen = false;
                }
            });
        });
    </script>
<?php
}
add_action('wp_head', 'vinapet_floating_contact_assets');


// 2. RENDER HTML
function vinapet_floating_contact_button()
{
?>
    <div class="floating-container">
        <div class="contact-menu" id="floatingContactMenu">

            <!-- Facebook -->
            <a href="https://www.facebook.com/profile.php?id=100094599485921"
                target="_blank"
                rel="noopener noreferrer"
                class="floating-contact-item facebook"
                aria-label="Facebook">
                <i class="fab fa-facebook-f"></i>
                <span class="contact-tooltip">Facebook</span>
            </a>

            <!-- TikTok -->
            <a href="https://www.tiktok.com/@nha_may_vinapet"
                target="_blank"
                rel="noopener noreferrer"
                class="floating-contact-item tiktok"
                aria-label="TikTok">
                <i class="fab fa-tiktok"></i>
                <span class="contact-tooltip">TikTok</span>
            </a>

            <!-- WeChat -->
            <a href="weixin://dl/chat?0911818518"
                class="floating-contact-item wechat"
                aria-label="WeChat">
                <i class="fab fa-weixin"></i>
                <span class="contact-tooltip">WeChat: 0911818518</span>
            </a>

            <!-- WhatsApp -->
            <a href="https://wa.me/840911818518"
                target="_blank"
                rel="noopener noreferrer"
                class="floating-contact-item whatsapp"
                aria-label="WhatsApp">
                <i class="fab fa-whatsapp"></i>
                <span class="contact-tooltip">WhatsApp: 0911818518</span>
            </a>

            <!-- Zalo -->
            <a href="https://zalo.me/0911818518"
                target="_blank"
                rel="noopener noreferrer"
                class="floating-contact-item zalo"
                aria-label="Zalo">
                <?php
                // Kiểm tra có icon Zalo SVG không
                $zalo_icon = get_template_directory_uri() . '/assets/images/icon/icon-zalo.svg';
                if (file_exists(get_template_directory() . '/assets/images/icon/icon-zalo.svg')) : ?>
                    <img src="<?php echo esc_url($zalo_icon); ?>" alt="Zalo">
                <?php else : ?>
                    <i class="fas fa-comment-dots"></i>
                <?php endif; ?>
                <span class="contact-tooltip">Zalo: 0911818518</span>
            </a>

            <!-- Phone -->
            <a href="tel:0911818518"
                class="floating-contact-item phone"
                aria-label="Điện thoại">
                <i class="fas fa-phone"></i>
                <span class="contact-tooltip">Phone: 0911818518</span>
            </a>
        </div>

        <div class="floating-button" id="floatingContactBtn" role="button" aria-label="Liên hệ">
            <i class="fas fa-message"></i>
        </div>
    </div>
<?php
}
add_action('wp_footer', 'vinapet_floating_contact_button', 999);

// Load Auto Migration
if (file_exists(VINAPET_THEME_DIR . '/includes/admin/class-auto-erp-migration.php')) {
    require_once VINAPET_THEME_DIR . '/includes/admin/class-auto-erp-migration.php';
}
