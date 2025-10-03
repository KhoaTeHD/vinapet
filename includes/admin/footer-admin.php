<?php
/**
 * VinaPet Footer Admin Settings
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Footer_Admin {
    
    public function __construct() {
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
            'Cài đặt Footer',
            'Footer Settings',
            'manage_options',
            'vinapet-footer-settings',
            array($this, 'footer_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vinapet_footer_settings', 'vinapet_company_info', array(
            'sanitize_callback' => array($this, 'sanitize_company_info')
        ));
    }
    
    /**
     * Sanitize company info
     */
    public function sanitize_company_info($input) {
        $sanitized = array();
        
        if (isset($input['name'])) {
            $sanitized['name'] = sanitize_text_field($input['name']);
        }
        
        if (isset($input['factory_address'])) {
            $sanitized['factory_address'] = sanitize_textarea_field($input['factory_address']);
        }
        
        if (isset($input['office_address'])) {
            $sanitized['office_address'] = sanitize_textarea_field($input['office_address']);
        }
        
        if (isset($input['tax_code'])) {
            $sanitized['tax_code'] = sanitize_text_field($input['tax_code']);
        }
        
        if (isset($input['phone1'])) {
            $sanitized['phone1'] = sanitize_text_field($input['phone1']);
        }
        
        if (isset($input['phone2'])) {
            $sanitized['phone2'] = sanitize_text_field($input['phone2']);
        }
        
        if (isset($input['email'])) {
            $sanitized['email'] = sanitize_email($input['email']);
        }
        
        if (isset($input['facebook_url'])) {
            $sanitized['facebook_url'] = esc_url_raw($input['facebook_url']);
        }
        
        if (isset($input['website'])) {
            $sanitized['website'] = esc_url_raw($input['website']);
        }
        
        return $sanitized;
    }
    
    /**
     * Main settings page
     */
    public function main_settings_page() {
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-store"></span> VinaPet Theme Settings</h1>
            <p>Chào mừng đến với panel cài đặt VinaPet Theme. Quản lý tất cả cài đặt cho theme của bạn.</p>
            
            <div class="vinapet-admin-cards">
                <div class="card">
                    <h3><span class="dashicons dashicons-admin-settings"></span> Footer Settings</h3>
                    <p>Cài đặt thông tin công ty, liên hệ và mạng xã hội hiển thị trong footer.</p>
                    <a href="<?php echo admin_url('admin.php?page=vinapet-footer-settings'); ?>" class="button button-primary">Cài đặt Footer</a>
                </div>
                
                <div class="card">
                    <h3><span class="dashicons dashicons-chart-area"></span> Theme Info</h3>
                    <p>Phiên bản: <?php echo VINAPET_VERSION; ?></p>
                    <p>Tích hợp ERPNext: <?php echo function_exists('vinapet_is_erpnext_enabled') ? 'Có' : 'Không'; ?></p>
                </div>
            </div>
        </div>
        
        <style>
        .vinapet-admin-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .vinapet-admin-cards .card {
            background: white;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .vinapet-admin-cards .card h3 {
            margin-top: 0;
            color: #23282d;
        }
        .vinapet-admin-cards .card .dashicons {
            margin-right: 5px;
            color: #0073aa;
        }
        </style>
        <?php
    }
    
    /**
     * Footer settings page
     */
    public function footer_settings_page() {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vinapet_footer_nonce'], 'vinapet_footer_settings')) {
            $company_info = array(
                'name' => sanitize_text_field($_POST['company_name']),
                'factory_address' => sanitize_textarea_field($_POST['factory_address']),
                'office_address' => sanitize_textarea_field($_POST['office_address']),
                'tax_code' => sanitize_text_field($_POST['tax_code']),
                'phone1' => sanitize_text_field($_POST['phone1']),
                'phone2' => sanitize_text_field($_POST['phone2']),
                'email' => sanitize_email($_POST['email']),
                'facebook_url' => esc_url_raw($_POST['facebook_url']),
                'website' => esc_url_raw($_POST['website'])
            );
            
            update_option('vinapet_company_info', $company_info);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Đã lưu!</strong> Cài đặt footer đã được cập nhật thành công.</p></div>';
        }
        
        $company_info = get_option('vinapet_company_info', array());
        ?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-admin-settings"></span> Cài đặt Footer VinaPet</h1>
            <p>Cấu hình thông tin công ty hiển thị trong footer website.</p>
            
            <form method="post" action="" class="vinapet-footer-form">
                <?php wp_nonce_field('vinapet_footer_settings', 'vinapet_footer_nonce'); ?>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="company_name">Tên công ty</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="company_name" 
                                       name="company_name" 
                                       value="<?php echo esc_attr($company_info['name'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="CÔNG TY CP ĐẦU TƯ VÀ SẢN XUẤT VINAPET" />
                                <p class="description">Tên công ty hiển thị trong footer.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="factory_address">Địa chỉ nhà máy</label>
                            </th>
                            <td>
                                <textarea id="factory_address" 
                                          name="factory_address" 
                                          rows="3" 
                                          cols="50" 
                                          class="large-text"
                                          placeholder="Nhà máy công nghệ cao Vinapet - Lô CN 3.1 - Khu CN Phú Nghĩa - Chương Mỹ, Hà Nội"><?php echo esc_textarea($company_info['factory_address'] ?? ''); ?></textarea>
                                <p class="description">Địa chỉ nhà máy sản xuất.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="office_address">Địa chỉ văn phòng</label>
                            </th>
                            <td>
                                <textarea id="office_address" 
                                          name="office_address" 
                                          rows="3" 
                                          cols="50" 
                                          class="large-text"
                                          placeholder="Văn phòng: Tòa nhà Cung Tri Thức - Số 1 Tôn Thất Thuyết - Cầu Giấy, Hà Nội"><?php echo esc_textarea($company_info['office_address'] ?? ''); ?></textarea>
                                <p class="description">Địa chỉ văn phòng làm việc.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="tax_code">Mã số thuế</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="tax_code" 
                                       name="tax_code" 
                                       value="<?php echo esc_attr($company_info['tax_code'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="0110064359" />
                                <p class="description">Mã số thuế của công ty.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="phone1">Số điện thoại chính</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="phone1" 
                                       name="phone1" 
                                       value="<?php echo esc_attr($company_info['phone1'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="(+84) 911 818 518" />
                                <p class="description">Số điện thoại liên hệ chính.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="phone2">Số điện thoại phụ</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="phone2" 
                                       name="phone2" 
                                       value="<?php echo esc_attr($company_info['phone2'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="(+84) 2471008333" />
                                <p class="description">Số điện thoại liên hệ phụ (tuỳ chọn).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email">Email</label>
                            </th>
                            <td>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo esc_attr($company_info['email'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="support@vinapet.com.vn" />
                                <p class="description">Địa chỉ email liên hệ.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="facebook_url">Facebook URL</label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="facebook_url" 
                                       name="facebook_url" 
                                       value="<?php echo esc_attr($company_info['facebook_url'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="https://www.facebook.com/p/Vinapet-100094599485921/" />
                                <p class="description">Link trang Facebook của công ty.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="website">Website</label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="website" 
                                       name="website" 
                                       value="<?php echo esc_attr($company_info['website'] ?? ''); ?>" 
                                       class="regular-text" 
                                       placeholder="https://vinapet.com.vn" />
                                <p class="description">Website chính của công ty.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button('Lưu cài đặt', 'primary', 'submit', false); ?>
                <a href="<?php echo home_url(); ?>" class="button" target="_blank">Xem Footer</a>
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
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'vinapet-') === false) {
            return;
        }
        
        wp_enqueue_style('vinapet-admin', VINAPET_THEME_URI . '/assets/css/admin.css', array(), VINAPET_VERSION);
    }
}

// Initialize
new VinaPet_Footer_Admin();