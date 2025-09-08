<?php
/**
 * VinaPet Admin Settings - FIXED VERSION
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
            'ERPNext API Connection',
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
        echo '<input type="text" name="erp_api_url" value="' . esc_attr($api_url) . '" class="regular-text" placeholder="https://your-erp-domain.com">';
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
        ?>
        <div class="wrap">
            <h1>VinaPet ERPNext Integration</h1>
            
            <!-- Settings Form -->
            <form method="post" action="options.php">
                <?php
                settings_fields('vinapet_erp_settings');
                do_settings_sections('vinapet-settings');
                submit_button('Lưu cài đặt');
                ?>
            </form>
            
            <!-- Test Connection - KHÔNG PHẢI FORM -->
            <div class="card" style="margin-top: 20px;">
                <h2>Test Connection</h2>
                <p>Kiểm tra kết nối với ERPNext API</p>
                
                <button type="button" id="test-connection-btn" class="button button-primary">Test Connection</button>
                <button type="button" id="clear-cache-btn" class="button" style="margin-left: 10px;">Clear Cache</button>
                
                <div id="test-result" style="margin-top: 15px;"></div>
                <div id="test-data" style="margin-top: 15px;"></div>
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
            //console.log('VinaPet Admin JS loaded');
            
            // Test Connection AJAX
            $('#test-connection-btn').click(function() {
                //console.log('Test Connection clicked');

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
                        console.log('Test response:', response);
                        
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
                        console.log('AJAX Error:', xhr, status, error);
                        $result.html('<div class="notice notice-error"><p><strong>❌ AJAX Error: ' + error + '</strong></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Connection');
                    }
                });
                
                return false; // Prevent any default action
            });
            
            // Clear Cache AJAX
            $('#clear-cache-btn').click(function() {
                console.log('Clear Cache clicked');
                
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
                        console.log('Clear cache response:', response);
                        
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p><strong>✅ ' + response.data + '</strong></p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p><strong>❌ ' + response.data + '</strong></p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr, status, error);
                        $result.html('<div class="notice notice-error"><p><strong>❌ AJAX Error: ' + error + '</strong></p></div>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Clear Cache');
                    }
                });
                
                return false; // Prevent any default action
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
            wp_send_json_error('Connection failed. Check API configuration.');
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