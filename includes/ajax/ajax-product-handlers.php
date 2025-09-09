<?php
/**
 * AJAX Handlers cho Product Management
 * File: includes/ajax/ajax-product-handlers.php
 * 
 * Thêm vào functions.php hoặc tạo file riêng và include
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AJAX handler để clear product cache
 */
function vinapet_clear_product_cache_ajax() {
    // Kiểm tra nonce security
    check_ajax_referer('clear_product_cache', 'nonce');
    
    // Kiểm tra quyền admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền thực hiện thao tác này.');
    }
    
    try {
        // Khởi tạo Product Data Manager
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        // Clear all product cache
        $product_manager->clear_cache();
        
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Log action
        error_log('VinaPet: Product cache cleared by user ID ' . get_current_user_id());
        
        wp_send_json_success([
            'message' => 'Cache sản phẩm đã được xóa thành công!',
            'timestamp' => current_time('mysql')
        ]);
        
    } catch (Exception $e) {
        error_log('VinaPet Clear Cache Error: ' . $e->getMessage());
        wp_send_json_error('Lỗi khi xóa cache: ' . $e->getMessage());
    }
}

// Đăng ký AJAX handlers
add_action('wp_ajax_clear_product_cache', 'vinapet_clear_product_cache_ajax');
add_action('wp_ajax_nopriv_clear_product_cache', 'vinapet_clear_product_cache_ajax'); // Allow for non-logged users if needed

/**
 * AJAX handler để refresh product data (force reload from API)
 */
function vinapet_refresh_products_ajax() {
    check_ajax_referer('refresh_products', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền thực hiện thao tác này.');
    }
    
    try {
        // Khởi tạo Product Data Manager
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        // Clear cache trước
        $product_manager->clear_cache();
        
        // Get fresh data với params mặc định
        $params = $product_manager->validate_params($_POST);
        $result = $product_manager->get_products($params);
        
        wp_send_json_success([
            'message' => 'Dữ liệu sản phẩm đã được làm mới!',
            'products_count' => count($result['products']),
            'source' => $result['source'],
            'error' => $result['error'],
            'timestamp' => current_time('mysql')
        ]);
        
    } catch (Exception $e) {
        error_log('VinaPet Refresh Products Error: ' . $e->getMessage());
        wp_send_json_error('Lỗi khi làm mới dữ liệu: ' . $e->getMessage());
    }
}

add_action('wp_ajax_refresh_products', 'vinapet_refresh_products_ajax');

/**
 * AJAX handler để lấy thông tin connection status
 */
function vinapet_get_connection_status_ajax() {
    check_ajax_referer('get_connection_status', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Không có quyền thực hiện thao tác này.');
    }
    
    try {
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        $status = $product_manager->get_connection_status();
        
        wp_send_json_success($status);
        
    } catch (Exception $e) {
        error_log('VinaPet Connection Status Error: ' . $e->getMessage());
        wp_send_json_error('Lỗi khi kiểm tra kết nối: ' . $e->getMessage());
    }
}

add_action('wp_ajax_get_connection_status', 'vinapet_get_connection_status_ajax');

/**
 * AJAX handler để search products (cho autocomplete hoặc instant search)
 */
function vinapet_search_products_ajax() {
    check_ajax_referer('search_products', 'nonce');
    
    $search_term = sanitize_text_field($_POST['search'] ?? '');
    $limit = min(10, intval($_POST['limit'] ?? 5)); // Giới hạn tối đa 10 kết quả cho autocomplete
    
    if (strlen($search_term) < 2) {
        wp_send_json_error('Từ khóa tìm kiếm quá ngắn.');
    }
    
    try {
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        $params = [
            'search' => $search_term,
            'limit' => $limit,
            'page' => 1
        ];
        
        $result = $product_manager->get_products($params);
        
        // Format data cho autocomplete
        $suggestions = [];
        foreach ($result['products'] as $product) {
            $suggestions[] = [
                'label' => $product['item_name'],
                'value' => $product['item_code'],
                'url' => $product['product_url'],
                'image' => $product['image'],
                'price' => $product['formatted_price'],
                'category' => $product['item_group']
            ];
        }
        
        wp_send_json_success([
            'suggestions' => $suggestions,
            'total' => $result['total'],
            'source' => $result['source']
        ]);
        
    } catch (Exception $e) {
        error_log('VinaPet Search Products Error: ' . $e->getMessage());
        wp_send_json_error('Lỗi khi tìm kiếm: ' . $e->getMessage());
    }
}

add_action('wp_ajax_search_products', 'vinapet_search_products_ajax');
add_action('wp_ajax_nopriv_search_products', 'vinapet_search_products_ajax');

/**
 * AJAX handler để lấy product details nhanh (cho quick view)
 */
function vinapet_get_product_quick_view_ajax() {
    check_ajax_referer('product_quick_view', 'nonce');
    
    $product_code = sanitize_text_field($_POST['product_code'] ?? '');
    
    if (empty($product_code)) {
        wp_send_json_error('Mã sản phẩm không hợp lệ.');
    }
    
    try {
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        $result = $product_manager->get_product($product_code);
        
        if (!$result['product']) {
            wp_send_json_error('Không tìm thấy sản phẩm.');
        }
        
        $product = $result['product'];
        
        // Tạo HTML cho quick view
        ob_start();
        ?>
        <div class="product-quick-view">
            <div class="product-image">
                <img src="<?php echo esc_url($product['image']); ?>" 
                     alt="<?php echo esc_attr($product['item_name']); ?>">
            </div>
            <div class="product-details">
                <h3><?php echo esc_html($product['item_name']); ?></h3>
                <p class="product-code">Mã: <?php echo esc_html($product['item_code']); ?></p>
                <p class="product-price"><?php echo esc_html($product['formatted_price']); ?></p>
                
                <?php if (!empty($product['description'])): ?>
                    <div class="product-description">
                        <?php echo wp_kses_post(wpautop($product['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-actions">
                    <a href="<?php echo esc_url($product['product_url']); ?>" class="btn btn-primary">
                        Xem chi tiết
                    </a>
                    <?php if ($product['is_available']): ?>
                        <a href="<?php echo esc_url($product['product_url'] . '/dat-hang'); ?>" class="btn btn-secondary">
                            Đặt hàng
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'product' => $product,
            'source' => $result['source']
        ]);
        
    } catch (Exception $e) {
        error_log('VinaPet Quick View Error: ' . $e->getMessage());
        wp_send_json_error('Lỗi khi tải thông tin sản phẩm: ' . $e->getMessage());
    }
}

add_action('wp_ajax_product_quick_view', 'vinapet_get_product_quick_view_ajax');
add_action('wp_ajax_nopriv_product_quick_view', 'vinapet_get_product_quick_view_ajax');

/**
 * AJAX handler để track product views (analytics)
 */
function vinapet_track_product_view_ajax() {
    // Không cần nonce cho tracking vì không sensitive
    
    $product_code = sanitize_text_field($_POST['product_code'] ?? '');
    $user_id = get_current_user_id();
    $session_id = session_id();
    
    if (empty($product_code)) {
        wp_send_json_error('Invalid product code.');
    }
    
    try {
        // Lưu tracking data
        $tracking_data = [
            'product_code' => $product_code,
            'user_id' => $user_id,
            'session_id' => $session_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql'),
            'page_url' => $_POST['page_url'] ?? ''
        ];
        
        // Có thể lưu vào custom table hoặc meta
        // Ở đây chỉ log để demo
        error_log('VinaPet Product View: ' . json_encode($tracking_data));
        
        wp_send_json_success(['tracked' => true]);
        
    } catch (Exception $e) {
        error_log('VinaPet Tracking Error: ' . $e->getMessage());
        wp_send_json_error('Tracking failed.');
    }
}

add_action('wp_ajax_track_product_view', 'vinapet_track_product_view_ajax');
add_action('wp_ajax_nopriv_track_product_view', 'vinapet_track_product_view_ajax');

/**
 * Helper function để enqueue AJAX scripts
 */
function vinapet_enqueue_product_ajax_scripts() {
    if (is_page_template('page-templates/page-product.php')) {
        wp_localize_script('vinapet-product-listing', 'vinapet_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'clear_cache' => wp_create_nonce('clear_product_cache'),
                'refresh_products' => wp_create_nonce('refresh_products'),
                'search_products' => wp_create_nonce('search_products'),
                'connection_status' => wp_create_nonce('get_connection_status'),
                'quick_view' => wp_create_nonce('product_quick_view')
            ],
            'settings' => [
                'search_delay' => 500,
                'autocomplete_min_length' => 2,
                'enable_tracking' => true
            ]
        ]);
    }
}

add_action('wp_enqueue_scripts', 'vinapet_enqueue_product_ajax_scripts');

/**
 * Admin bar menu để clear cache nhanh (chỉ cho admin)
 */
function vinapet_admin_bar_cache_menu($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $wp_admin_bar->add_node([
        'id' => 'vinapet-cache',
        'title' => '🔄 VinaPet Cache',
        'href' => '#',
        'meta' => [
            'onclick' => 'clearProductCache(); return false;'
        ]
    ]);
}

add_action('admin_bar_menu', 'vinapet_admin_bar_cache_menu', 100);

/**
 * Thêm debug info cho admin
 */
function vinapet_add_debug_info($debug_info) {
    if (!current_user_can('manage_options')) {
        return $debug_info;
    }
    
    try {
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        $status = $product_manager->get_connection_status();
        
        $debug_info['vinapet'] = [
            'label' => 'VinaPet Product System',
            'fields' => [
                'erp_configured' => [
                    'label' => 'ERPNext Configured',
                    'value' => $status['erp_configured'] ? 'Yes' : 'No'
                ],
                'erp_working' => [
                    'label' => 'ERPNext Working',
                    'value' => $status['erp_working'] ? 'Yes' : 'No'
                ],
                'sample_available' => [
                    'label' => 'Sample Data Available',
                    'value' => $status['sample_available'] ? 'Yes' : 'No'
                ],
                'last_test' => [
                    'label' => 'Last API Test',
                    'value' => $status['last_test'] ? date('Y-m-d H:i:s', $status['last_test']) : 'Never'
                ]
            ]
        ];
        
    } catch (Exception $e) {
        $debug_info['vinapet'] = [
            'label' => 'VinaPet Product System',
            'fields' => [
                'error' => [
                    'label' => 'System Error',
                    'value' => $e->getMessage()
                ]
            ]
        ];
    }
    
    return $debug_info;
}

add_filter('debug_information', 'vinapet_add_debug_info');

/**
 * Cron job để warm up cache định kỳ
 */
function vinapet_warm_up_product_cache() {
    try {
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $product_manager = new Product_Data_Manager();
        
        // Warm up với một số params phổ biến
        $common_params = [
            ['limit' => 12, 'page' => 1],
            ['limit' => 12, 'page' => 1, 'sort' => 'name-asc'],
            ['limit' => 12, 'page' => 1, 'sort' => 'price-asc']
        ];
        
        foreach ($common_params as $params) {
            $product_manager->get_products($params);
        }
        
        error_log('VinaPet: Product cache warmed up successfully');
        
    } catch (Exception $e) {
        error_log('VinaPet Cache Warmup Error: ' . $e->getMessage());
    }
}

// Schedule cron job (chạy mỗi 6 giờ)
if (!wp_next_scheduled('vinapet_warm_up_cache')) {
    wp_schedule_event(time(), 'twicedaily', 'vinapet_warm_up_cache');
}

add_action('vinapet_warm_up_cache', 'vinapet_warm_up_product_cache');

/**
 * Cleanup khi deactivate theme
 */
function vinapet_cleanup_product_cache() {
    // Clear all transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vinapet_products_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vinapet_products_%'");
    
    // Clear scheduled events
    wp_clear_scheduled_hook('vinapet_warm_up_cache');
}

add_action('switch_theme', 'vinapet_cleanup_product_cache');