<?php
// File: includes/admin/class-admin-settings.php

class VinaPet_Admin_Settings {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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
        add_menu_page(
            'Product Settings',
            'Sản phẩm',
            'manage_options',
            'vinapet-product-settings',
            array($this, 'product_settings_page'),
            'dashicons-archive',
            27
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

    public function connection_section_callback() {
        echo '<p>Cấu hình kết nối đến ERPNext API.</p>';
    }

    public function api_url_callback() {
        $api_url = get_option('erp_api_url');
        echo '<input type="text" name="erp_api_url" value="' . esc_attr($api_url) . '" class="regular-text" placeholder="https://your-erp-domain.com">';
    }

    public function api_key_callback() {
        $api_key = get_option('erp_api_key');
        echo '<input type="text" name="erp_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    }

    public function api_secret_callback() {
        $api_secret = get_option('erp_api_secret');
        echo '<input type="password" name="erp_api_secret" value="' . esc_attr($api_secret) . '" class="regular-text">';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>VinaPet ERPNext Integration</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('vinapet_erp_settings');
                do_settings_sections('vinapet-settings');
                submit_button();
                ?>
            </form>
            <div class="card">
                <h2>Test Connection</h2>
                <p>Test kết nối với ERPNext API.</p>
                <button class="button button-primary" id="test-erp-connection">Test Connection</button>
                <div id="connection-result" style="margin-top: 10px;"></div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#test-erp-connection').on('click', function(e) {
                        e.preventDefault();
                        var result = $('#connection-result');
                        result.html('<span class="spinner is-active"></span> Đang kiểm tra kết nối...');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'test_erp_connection',
                                nonce: '<?php echo wp_create_nonce('test_erp_connection'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    result.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                                } else {
                                    result.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                                }
                            },
                            error: function() {
                                result.html('<div class="notice notice-error inline"><p>Có lỗi xảy ra. Vui lòng thử lại.</p></div>');
                            }
                        });
                    });
                });
            </script>
        </div>
        <?php
    }

    public function product_settings_page() {
        ?>
        <div class="wrap">
            <h1>Sản phẩm</h1>
            <p>Trang cài đặt sản phẩm sẽ được phát triển sau.</p>
        </div>
        <?php
    }
}

// Khởi tạo class
$vinapet_admin_settings = new VinaPet_Admin_Settings();

// Ajax handler
function test_erp_connection() {
    check_ajax_referer('test_erp_connection', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Bạn không có quyền thực hiện hành động này.');
    }
    
    $api_client = new ERP_API_Client();
    $result = $api_client->get_products(['limit' => 1]);
    
    if ($result && isset($result['data'])) {
        wp_send_json_success('Kết nối thành công! Đã tìm thấy ' . count($result['data']) . ' sản phẩm.');
    } else {
        wp_send_json_error('Kết nối thất bại. Vui lòng kiểm tra lại thông tin cấu hình API.');
    }
}
add_action('wp_ajax_test_erp_connection', 'test_erp_connection');