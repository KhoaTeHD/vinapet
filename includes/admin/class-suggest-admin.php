<?php

/**
 * VinaPet Suggest Admin Settings
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vinapet_Suggest_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        
        // Footer submenu
        add_submenu_page(
            'vinapet-settings',
            'Cài đặt đề xuất',
            'Cài đặt đề xuất',
            'manage_options',
            'vinapet-suggest-settings',
            array($this, 'suggest_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vinapet_suggest_settings', 'vinapet_suggest_info', array(
            'sanitize_callback' => array($this, 'sanitize_suggest_info')
        ));
    }

    /**
     * Suggest settings page
     */
    public function suggest_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vinapet_suggest_nonce'], 'vinapet_suggest_settings')) {
            $suggest_info = array(
                'id' => sanitize_text_field($_POST['id']),
                'name' => sanitize_textarea_field($_POST['name'])
            );

            update_option('vinapet_suggest_info', $suggest_info);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Đã lưu!</strong> Cài đặt đề xuất đã được cập nhật thành công.</p></div>';
        }

        $suggest_info = get_option('vinapet_suggest_info', array());
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-admin-settings"></span> Cài đặt đề xuất VinaPet</h1>
            <p>Cấu hình thông tin đề xuất mix hạt.</p>

            <form method="post" action="" class="vinapet-suggest-form">
                <?php wp_nonce_field('vinapet_suggest_settings', 'vinapet_suggest_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="id">ID sản phẩm</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="id" 
                                       name="id" 
                                       value="<?php echo esc_attr($suggest_info['id'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="Cát tre XLTX" />
                                <p class="description">ID sản phẩm hiển thị trong website.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="name">Tên sản phẩm</label>
                            </th>
                            <td>
                                <textarea id="name" 
                                          name="name" 
                                          rows="3" 
                                          cols="50" 
                                          class="large-text"
                                          placeholder="Cát tre xanh lá trà xanh"><?php echo esc_textarea($suggest_info['name'] ?? ''); ?></textarea>
                                <p class="description">Tên sản phẩm hiển thị trong website.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button('Lưu cài đặt', 'primary', 'submit', false); ?>
            </form>
        </div>
        
        <style>
        .vinapet-footer-form .form-table th {
            width: 200px;
        }
        .vinapet-footer-form .description {
            color: #666;
            font-style: italic;
        }
        .vinapet-footer-form .large-text {
            width: 100%;
            max-width: 500px;
        }
        </style>
        <?php
    }

    /**
     * Sanitize suggest info
     */
    public function sanitize_suggest_info($input) {
        $sanitized = array();

        if (isset($input['id'])) {
            $sanitized['id'] = sanitize_text_field($input['id']);
        }
        if (isset($input['name'])) {
            $sanitized['name'] = sanitize_textarea_field($input['name']);
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'vinapet-') === false) {
            return;
        }
        
        wp_enqueue_style('vinapet-admin', VINAPET_THEME_URI . '/assets/css/admin.css', array(), VINAPET_VERSION);
    }
}

new Vinapet_Suggest_Admin();