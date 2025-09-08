<?php
/**
 * VinaPet Admin Settings - CLEAN FINAL
 * File: includes/admin/class-admin-setting.php
 */

class VinaPet_Admin_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers  
        add_action('wp_ajax_test_erp_connection_ajax', array($this, 'test_erp_connection_ajax'));
        add_action('wp_ajax_clear_erp_cache_ajax', array($this, 'clear_erp_cache_ajax'));
        add_action('wp_ajax_health_check_endpoints', array($this, 'health_check_endpoints'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'VinaPet Settings',
            'VinaPet',
            'manage_options',
            'vinapet-settings',
            array($this, 'settings_page'),
            'dashicons-pets',
            6
        );
    }

    public function register_settings() {
        register_setting('vinapet_erp_settings', 'erp_api_url');
        register_setting('vinapet_erp_settings', 'erp_api_key');
        register_setting('vinapet_erp_settings', 'erp_api_secret');
        
        add_settings_section(
            'vinapet_erp_connection',
            '',
            array($this, 'connection_section_callback'),
            'vinapet-settings'
        );

        add_settings_field(
            'erp_api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'vinapet-settings',
            'vinapet_erp_connection'
        );

        add_settings_field(
            'erp_api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'vinapet-settings',
            'vinapet_erp_connection'
        );

        add_settings_field(
            'erp_api_secret',
            'API Secret',
            array($this, 'api_secret_callback'),
            'vinapet-settings',
            'vinapet_erp_connection'
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_vinapet-settings') {
            return;
        }
        wp_enqueue_script('jquery');
    }

    public function connection_section_callback() {
        echo '<p>Cấu hình kết nối đến ERPNext API.</p>';
    }

    public function api_url_callback() {
        $api_url = get_option('erp_api_url');
        echo '<input type="url" name="erp_api_url" value="' . esc_attr($api_url) . '" class="regular-text" placeholder="https://your-erp-domain.com">';
        echo '<p class="description">Ví dụ: https://yourdomain.erpnext.com (không có /api ở cuối)</p>';
    }

    public function api_key_callback() {
        $api_key = get_option('erp_api_key');
        echo '<input type="text" name="erp_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
        echo '<p class="description">API Key từ ERPNext User (để trống nếu API không cần xác thực)</p>';
    }

    public function api_secret_callback() {
        $api_secret = get_option('erp_api_secret');
        echo '<input type="password" name="erp_api_secret" value="' . esc_attr($api_secret) . '" class="regular-text">';
        echo '<p class="description">API Secret từ ERPNext User (để trống nếu API không cần xác thực)</p>';
    }

    public function settings_page() {
        // Hiển thị thông báo sau khi lưu
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Cài đặt đã được lưu thành công!</strong></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Cấu hình API tích hợp ERP</h1>
            
            <!-- Settings Form - WordPress Standard -->
            <form method="post" action="options.php">
                <?php
                settings_fields('vinapet_erp_settings');
                do_settings_sections('vinapet-settings');
                submit_button('Lưu cài đặt');
                ?>
            </form>
            
            <!-- Test Connection -->
            <div class="card" style="margin-top: 20px;">
                <h2>Test Connection</h2>
                <p>Kiểm tra kết nối với ERPNext API</p>
                
                <button type="button" id="test-connection-btn" class="button button-primary">Test Connection</button>
                <button type="button" id="clear-cache-btn" class="button" style="margin-left: 10px;">Clear Cache</button>
                
                <div id="test-result" style="margin-top: 15px;"></div>
                <div id="test-data" style="margin-top: 15px;"></div>
            </div>
            
            <!-- Health Check -->
            <div class="card" style="margin-top: 20px;">
                <h2>🔍 Health Check - Endpoints Status</h2>
                <p>Kiểm tra trạng thái từng endpoint riêng biệt</p>
                
                <button type="button" id="health-check-btn" class="button button-secondary">Run Health Check</button>
                
                <div id="health-check-results" style="margin-top: 15px;"></div>
            </div>
            
            <!-- Current Status -->
            <div class="card" style="margin-top: 20px;">
                <h2>Current Status</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>API URL:</strong></td>
                        <td><?php echo esc_html(get_option('erp_api_url', 'Chưa cấu hình')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>API Key:</strong></td>
                        <td><?php echo !empty(get_option('erp_api_key')) ? 'Đã cấu hình' : 'Chưa cấu hình'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><?php echo $this->get_connection_status(); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Auto-hide success message after 5 seconds
            $('.notice.is-dismissible').delay(5000).fadeOut();
            
            // Test Connection AJAX
            $('#test-connection-btn').click(function() {
                var $btn = $(this);
                var $result = $('#test-result');
                var $data = $('#test-data');
                
                $btn.prop('disabled', true).text('Testing...');
                $result.html('<div class="notice notice-info"><p>🔄 Đang test connection...</p></div>');
                $data.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_erp_connection_ajax',
                        nonce: '<?php echo wp_create_nonce('test_erp_connection_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p><strong>✅ ' + response.data.message + '</strong></p></div>');
                            
                            if (response.data.products && response.data.products.length > 0) {
                                var html = '<div class="card"><h3>📦 Sản phẩm (' + response.data.products.length + ' tìm thấy)</h3>';
                                html += '<pre style="background: #f0f0f0; padding: 10px; font-size: 11px; max-height: 300px; overflow-y: auto; border: 1px solid #ccc;">';
                                html += JSON.stringify(response.data.products, null, 2);
                                html += '</pre></div>';
                                $data.html(html);
                            }
                        } else {
                            $result.html('<div class="notice notice-error"><p><strong>❌ ' + response.data + '</strong></p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $result.html('<div class="notice notice-error"><p><strong>❌ AJAX Error: ' + error + '</strong></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Connection');
                    }
                });
            });
            
            // Clear Cache AJAX
            $('#clear-cache-btn').click(function() {
                var $btn = $(this);
                var $result = $('#test-result');
                
                $btn.prop('disabled', true).text('Clearing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_erp_cache_ajax',
                        nonce: '<?php echo wp_create_nonce('clear_erp_cache_ajax'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p><strong>✅ ' + response.data + '</strong></p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p><strong>❌ ' + response.data + '</strong></p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $result.html('<div class="notice notice-error"><p><strong>❌ AJAX Error: ' + error + '</strong></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Clear Cache');
                    }
                });
            });
            
            // Health Check AJAX
            $('#health-check-btn').click(function() {
                var $btn = $(this);
                var $results = $('#health-check-results');
                
                $btn.prop('disabled', true).text('Checking...');
                $results.html('<div class="notice notice-info"><p>🔍 Đang kiểm tra các endpoints...</p></div>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'health_check_endpoints',
                        nonce: '<?php echo wp_create_nonce('health_check_endpoints'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="health-check-container">';
                            
                            // Overall status
                            var overallClass = response.data.overall_status === 'healthy' ? 'notice-success' : 'notice-warning';
                            html += '<div class="notice ' + overallClass + '"><p><strong>';
                            html += response.data.overall_status === 'healthy' ? '✅ All Endpoints Healthy' : '⚠️ Some Issues Found';
                            html += '</strong> (' + response.data.healthy_count + '/' + response.data.total_count + ' healthy)</p></div>';
                            
                            // Individual endpoint results
                            html += '<div class="endpoints-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 15px;">';
                            
                            $.each(response.data.endpoints, function(key, endpoint) {
                                var statusIcon = endpoint.status === 'healthy' ? '✅' : '❌';
                                
                                html += '<div class="endpoint-card" style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; background: #fff;">';
                                html += '<h4 style="margin: 0 0 10px 0; color: #333;">' + statusIcon + ' ' + endpoint.name + '</h4>';
                                html += '<p style="margin: 0 0 5px 0; font-size: 12px; color: #666;"><strong>Endpoint:</strong> ' + endpoint.endpoint + '</p>';
                                html += '<p style="margin: 0 0 5px 0; font-size: 12px;"><strong>Status:</strong> <span style="color: ' + (endpoint.status === 'healthy' ? 'green' : 'red') + ';">' + endpoint.status.toUpperCase() + '</span></p>';
                                html += '<p style="margin: 0 0 10px 0; font-size: 12px;"><strong>Response Time:</strong> ' + endpoint.response_time + 'ms</p>';
                                
                                if (endpoint.status === 'error') {
                                    html += '<p style="margin: 0 0 10px 0; font-size: 12px; color: red;"><strong>Error:</strong> ' + endpoint.error + '</p>';
                                } else {
                                    html += '<p style="margin: 0 0 10px 0; font-size: 12px; color: green;"><strong>Data Count:</strong> ' + (endpoint.data_count || 0) + '</p>';
                                }
                                
                                // THÊM MỚI: Hiển thị Response Data
                                if (endpoint.response_data) {
                                    html += '<div style="margin-top: 10px;">';
                                    html += '<p style="margin: 0 0 5px 0; font-size: 12px; font-weight: bold;">Response Data:</p>';
                                    html += '<pre style="background: #f8f8f8; padding: 8px; font-size: 10px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 3px; margin: 0;">';
                                    html += JSON.stringify(endpoint.response_data, null, 2);
                                    html += '</pre>';
                                    html += '</div>';
                                }
                                
                                html += '</div>';
                            });
                            
                            html += '</div></div>';
                            $results.html(html);
                        } else {
                            $results.html('<div class="notice notice-error"><p><strong>❌ ' + response.data + '</strong></p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $results.html('<div class="notice notice-error"><p><strong>❌ AJAX Error: ' + error + '</strong></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Run Health Check');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for test connection
     */
    public function test_erp_connection_ajax() {
        check_ajax_referer('test_erp_connection_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền thực hiện.');
        }
        
        $api_client = new ERP_API_Client();
        
        if (!$api_client->is_configured()) {
            wp_send_json_error('API chưa được cấu hình. Vui lòng điền API URL.');
        }
        
        $result = $api_client->get_products(['limit' => 5]);
        
        if ($result !== false && is_array($result)) {
            $count = count($result);
            $message = "Kết nối thành công! Đã tìm thấy {$count} sản phẩm.";
            
            wp_send_json_success([
                'message' => $message,
                'count' => $count,
                'products' => $result
            ]);
        } else {
            wp_send_json_error('Kết nối thất bại. Vui lòng kiểm tra cấu hình API.');
        }
    }
    
    /**
     * AJAX handler for clear cache
     */
    public function clear_erp_cache_ajax() {
        check_ajax_referer('clear_erp_cache_ajax', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền thực hiện.');
        }
        
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_erp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_erp_%'");
        
        wp_send_json_success('Cache cleared successfully!');
    }
    
    /**
     * AJAX handler for health check
     */
    public function health_check_endpoints() {
        check_ajax_referer('health_check_endpoints', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền thực hiện.');
        }
        
        $api_client = new ERP_API_Client();
        
        if (!$api_client->is_configured()) {
            wp_send_json_error('API chưa được cấu hình. Vui lòng điền API URL.');
        }
        
        // Define endpoints to check
        $endpoints_to_check = [
            'products_list' => [
                'name' => 'Products List',
                'method' => 'get_products',
                'params' => ['limit' => 3]
            ],
            'product_detail' => [
                'name' => 'Product Detail',
                'method' => 'get_product',
                'params' => ['SPBAMBOO1']
            ],
            'categories' => [
                'name' => 'Categories',
                'method' => 'get_product_categories',
                'params' => []
            ]
        ];
        
        $results = [];
        $healthy_count = 0;
        
        foreach ($endpoints_to_check as $key => $config) {
            $start_time = microtime(true);
            
            try {
                if ($config['method'] === 'get_product') {
                    $result = $api_client->{$config['method']}($config['params'][0]);
                } else {
                    $result = $api_client->{$config['method']}($config['params']);
                }
                
                $end_time = microtime(true);
                $response_time = round(($end_time - $start_time) * 1000, 2);
                
                if ($result !== false && !is_wp_error($result)) {
                    $data_count = is_array($result) ? count($result) : 1;
                    
                    // THÊM MỚI: Chuẩn bị response data để hiển thị
                    $response_data = $result;
                    if (is_array($result) && count($result) > 2) {
                        // Chỉ lấy 20 items đầu để hiển thị
                        $response_data = array_slice($result, 0, 30);
                    }
                    
                    $results[$key] = [
                        'name' => $config['name'],
                        'endpoint' => $this->get_endpoint_url($key),
                        'status' => 'healthy',
                        'response_time' => $response_time,
                        'data_count' => $data_count,
                        'response_data' => $response_data,  // THÊM MỚI
                        'error' => null
                    ];
                    $healthy_count++;
                } else {
                    $error_message = is_wp_error($result) ? $result->get_error_message() : 'Unknown error';
                    
                    $results[$key] = [
                        'name' => $config['name'],
                        'endpoint' => $this->get_endpoint_url($key),
                        'status' => 'error',
                        'response_time' => $response_time,
                        'data_count' => 0,
                        'response_data' => null,  // THÊM MỚI
                        'error' => $error_message
                    ];
                }
            } catch (Exception $e) {
                $end_time = microtime(true);
                $response_time = round(($end_time - $start_time) * 1000, 2);
                
                $results[$key] = [
                    'name' => $config['name'],
                    'endpoint' => $this->get_endpoint_url($key),
                    'status' => 'error',
                    'response_time' => $response_time,
                    'data_count' => 0,
                    'response_data' => null,  // THÊM MỚI
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $total_count = count($endpoints_to_check);
        $overall_status = ($healthy_count === $total_count) ? 'healthy' : 'degraded';
        
        wp_send_json_success([
            'endpoints' => $results,
            'healthy_count' => $healthy_count,
            'total_count' => $total_count,
            'overall_status' => $overall_status,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    /**
     * Get endpoint URL for display
     */
    private function get_endpoint_url($endpoint_key) {
        $endpoints = [
            'products_list' => '/api/resource/Item',
            'product_detail' => '/api/method/vinapet.api.item.item.get_item_detail',
            'categories' => '/api/resource/Item Group'
        ];
        
        return isset($endpoints[$endpoint_key]) ? $endpoints[$endpoint_key] : 'Unknown';
    }
    
    private function get_connection_status() {
        $api_url = get_option('erp_api_url');
        $api_key = get_option('erp_api_key');
        $api_secret = get_option('erp_api_secret');
        
        if (empty($api_url)) {
            return '<span style="color: red;">✗ Chưa cấu hình API URL</span>';
        }
        
        if (empty($api_key) && empty($api_secret)) {
            return '<span style="color: orange;">⚠ API URL đã cấu hình (chế độ public)</span>';
        }
        
        if (empty($api_key) || empty($api_secret)) {
            return '<span style="color: red;">✗ Thiếu API Key hoặc Secret</span>';
        }
        
        return '<span style="color: green;">✓ Đã cấu hình đầy đủ</span>';
    }
}

// Khởi tạo class
$vinapet_admin_settings = new VinaPet_Admin_Settings();