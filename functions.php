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
    
    // JavaScript chung
    wp_enqueue_script('vinapet-navigation', VINAPET_THEME_URI . '/assets/js/navigation.js', array('jquery'), VINAPET_VERSION, true);
    
    // JavaScript riêng cho trang sản phẩm
    if (is_page_template('page-templates/page-product.php')) {
        wp_enqueue_script('vinapet-product-listing', VINAPET_THEME_URI . '/assets/js/product-listing.js', array('jquery'), VINAPET_VERSION, true);
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
 * Hàm helper để lấy thông tin trang hiện tại
 */
function vinapet_get_current_page_id() {
    global $post;
    
    if (is_home() || is_archive() || is_search()) {
        return 0;
    }
    
    if (isset($post->ID)) {
        return $post->ID;
    }
    
    return 0;
}

/**
 * Tạo widget areas
 */
function vinapet_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'vinapet'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Thêm các widgets vào đây.', 'vinapet'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));
    
    register_sidebar(array(
        'name'          => esc_html__('Footer Widget Area', 'vinapet'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Thêm các widgets vào footer.', 'vinapet'),
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'vinapet_widgets_init');

/**
 * Bổ sung chức năng yêu thích sản phẩm
 */
function vinapet_toggle_favorite() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    $product_code = isset($_POST['product_code']) ? sanitize_text_field($_POST['product_code']) : '';
    
    if (empty($product_code)) {
        wp_send_json_error('Mã sản phẩm không hợp lệ');
    }
    
    // Khởi tạo danh sách yêu thích nếu chưa có
    if (!isset($_SESSION['vinapet_favorites'])) {
        $_SESSION['vinapet_favorites'] = array();
    }
    
    // Kiểm tra sản phẩm đã có trong danh sách chưa
    $favorites = $_SESSION['vinapet_favorites'];
    $is_favorite = in_array($product_code, $favorites);
    
    if ($is_favorite) {
        // Nếu đã có, xóa khỏi danh sách
        $key = array_search($product_code, $favorites);
        unset($favorites[$key]);
        $action = 'removed';
    } else {
        // Nếu chưa có, thêm vào danh sách
        $favorites[] = $product_code;
        $action = 'added';
    }
    
    // Cập nhật lại danh sách
    $_SESSION['vinapet_favorites'] = array_values($favorites);
    
    wp_send_json_success(array(
        'action' => $action,
        'favorites_count' => count($favorites),
        'message' => $action === 'added' ? 'Đã thêm vào danh sách yêu thích' : 'Đã xóa khỏi danh sách yêu thích'
    ));
}
add_action('wp_ajax_toggle_favorite', 'vinapet_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'vinapet_toggle_favorite');

/**
 * Kiểm tra sản phẩm có trong danh sách yêu thích không
 */
function vinapet_is_favorite($product_code) {
    if (!isset($_SESSION['vinapet_favorites'])) {
        return false;
    }
    
    return in_array($product_code, $_SESSION['vinapet_favorites']);
}

/**
 * Xử lý AJAX tìm kiếm sản phẩm
 */
function vinapet_ajax_search_products() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    
    if (empty($keyword)) {
        wp_send_json_error('Từ khóa tìm kiếm không hợp lệ');
    }
    
    // Nhúng class cung cấp dữ liệu mẫu
    require_once get_template_directory() . '/includes/api/class-sample-product-provider.php';
    
    // Khởi tạo provider
    $product_provider = new Sample_Product_Provider();
    
    // Tìm kiếm sản phẩm
    $products_response = $product_provider->get_products([
        'search' => $keyword,
        'limit' => 5
    ]);
    
    $products = isset($products_response['data']) ? $products_response['data'] : [];
    
    // Chuẩn bị dữ liệu trả về
    $results = array();
    
    foreach ($products as $product) {
        $results[] = array(
            'id' => $product['id'],
            'name' => $product['item_name'],
            'image' => $product['image'],
            'price' => number_format($product['standard_rate'], 0, ',', '.') . ' đ',
            'url' => home_url('/san-pham/' . sanitize_title($product['item_code']))
        );
    }
    
    wp_send_json_success(array(
        'results' => $results,
        'count' => count($results)
    ));
}
add_action('wp_ajax_search_products', 'vinapet_ajax_search_products');
add_action('wp_ajax_nopriv_search_products', 'vinapet_ajax_search_products');

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
 * Hàm hỗ trợ hiển thị thông báo
 */
function vinapet_show_notice($message, $type = 'success') {
    if (!isset($_SESSION['vinapet_notices'])) {
        $_SESSION['vinapet_notices'] = array();
    }
    
    $_SESSION['vinapet_notices'][] = array(
        'message' => $message,
        'type' => $type
    );
}

/**
 * Hiển thị thông báo cho người dùng
 */
function vinapet_display_notices() {
    if (empty($_SESSION['vinapet_notices'])) {
        return;
    }
    
    $notices = $_SESSION['vinapet_notices'];
    
    echo '<div class="vinapet-notices">';
    
    foreach ($notices as $notice) {
        echo '<div class="notice notice-' . esc_attr($notice['type']) . '">';
        echo '<p>' . esc_html($notice['message']) . '</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Xóa các thông báo đã hiển thị
    $_SESSION['vinapet_notices'] = array();
}
add_action('wp_footer', 'vinapet_display_notices');

/**
 * Sử dụng mã tạm thời cho API khi chưa tích hợp ERPNext
 * Nhúng các file cần thiết để tạo dữ liệu mẫu
 */
require_once VINAPET_THEME_DIR . '/includes/data/sample-products.php';
require_once VINAPET_THEME_DIR . '/includes/api/class-sample-product-provider.php';

/**
 * Thiết lập cấu trúc thư mục cho theme
 */
// Nhúng các file template parts
require_once VINAPET_THEME_DIR . '/includes/template-functions.php';

// Nhúng các file helper functions
require_once VINAPET_THEME_DIR . '/includes/helpers.php';

/**
 * Tắt xác thực CSP (Content Security Policy) cho việc phát triển
 * CHÚ Ý: Nên bật lại khi triển khai lên môi trường thực tế
 */
function vinapet_disable_csp() {
    remove_action('wp_head', 'wp_post_preview_js', 1);
}
add_action('after_setup_theme', 'vinapet_disable_csp');

/**
 * Vô hiệu hóa emoji để tăng hiệu suất
 */
function vinapet_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'vinapet_disable_emojis');

/**
 * Thêm các thẻ async và defer vào script để tăng hiệu suất
 */
function vinapet_script_attributes($tag, $handle) {
    // Danh sách các script cần thêm async
    $async_scripts = array('vinapet-product-listing');
    
    // Danh sách các script cần thêm defer
    $defer_scripts = array('vinapet-navigation');
    
    if (in_array($handle, $async_scripts)) {
        return str_replace(' src', ' async src', $tag);
    }
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}
add_filter('script_loader_tag', 'vinapet_script_attributes', 10, 2);

/**
 * Thêm trình soạn thảo CSS tùy chỉnh vào Customizer
 */
function vinapet_customize_register($wp_customize) {
    // Thêm section mới
    $wp_customize->add_section('vinapet_options', array(
        'title'    => __('VinaPet Options', 'vinapet'),
        'priority' => 120,
    ));
    
    // Thêm setting cho màu chủ đạo
    $wp_customize->add_setting('primary_color', array(
        'default'           => '#2E86AB',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    // Thêm control cho màu chủ đạo
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'primary_color', array(
        'label'    => __('Primary Color', 'vinapet'),
        'section'  => 'vinapet_options',
        'settings' => 'primary_color',
    )));
    
    // Thêm setting cho màu phụ
    $wp_customize->add_setting('secondary_color', array(
        'default'           => '#A23B72',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    // Thêm control cho màu phụ
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'secondary_color', array(
        'label'    => __('Secondary Color', 'vinapet'),
        'section'  => 'vinapet_options',
        'settings' => 'secondary_color',
    )));
    
    // Thêm setting cho màu nhấn
    $wp_customize->add_setting('accent_color', array(
        'default'           => '#F18F01',
        'sanitize_callback' => 'sanitize_hex_color',
    ));
    
    // Thêm control cho màu nhấn
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'accent_color', array(
        'label'    => __('Accent Color', 'vinapet'),
        'section'  => 'vinapet_options',
        'settings' => 'accent_color',
    )));
}
add_action('customize_register', 'vinapet_customize_register');

/**
 * Thêm CSS tùy chỉnh từ Customizer
 */
function vinapet_customizer_css() {
    ?>
    <style type="text/css">
        :root {
            --primary-color: <?php echo get_theme_mod('primary_color', '#2E86AB'); ?>;
            --secondary-color: <?php echo get_theme_mod('secondary_color', '#A23B72'); ?>;
            --accent-color: <?php echo get_theme_mod('accent_color', '#F18F01'); ?>;
        }
    </style>
    <?php
}
add_action('wp_head', 'vinapet_customizer_css');

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
        } e