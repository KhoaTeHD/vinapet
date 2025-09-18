<?php

/**
 * File: includes/auth/auth-integration.php
 * VinaPet Authentication Integration with ERPNext
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Auth_Integration
{

    private $erpnext_settings;

    public function __construct()
    {
        // Initialize ERPNext settings
        $this->erpnext_settings = get_option('vinapet_erpnext_settings', array());

        // Core hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_auth_assets'));
        add_action('wp_footer', array($this, 'add_auth_modal_to_footer'));
        add_action('wp_enqueue_scripts', array($this, 'localize_auth_scripts'), 20);

        // AJAX handlers
        add_action('wp_ajax_nopriv_vinapet_ajax_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_nopriv_vinapet_ajax_register', array($this, 'handle_ajax_register'));
        add_action('wp_ajax_vinapet_check_login_status', array($this, 'handle_check_login_status'));
        add_action('wp_ajax_nopriv_vinapet_check_login_status', array($this, 'handle_check_login_status'));

        // Theme setup
        add_action('after_switch_theme', array($this, 'setup_customer_role'));

        // Menu modifications
        add_filter('wp_nav_menu_items', array($this, 'modify_login_menu_item'), 10, 2);

        // ERPNext integration
        add_action('user_register', array($this, 'sync_user_to_erpnext'), 10, 1);
        add_action('wp_login', array($this, 'update_user_login_erpnext'), 10, 2);
        add_filter('nsl_register_redirect_url', array($this, 'force_register_redirect'), 10, 2);
    }
    public function force_register_redirect($redirect_url, $provider)
    {
        return home_url('/tai-khoan');
    }

    /**
     * Enqueue auth modal assets
     */
    public function enqueue_auth_assets()
    {
        if (is_admin()) {
            return;
        }

        // CSS for auth modal
        wp_enqueue_style(
            'vinapet-auth-modal',
            VINAPET_THEME_URI . '/assets/css/modal-auth.css',
            array(),
            VINAPET_VERSION
        );

        // JavaScript for auth modal
        wp_enqueue_script(
            'vinapet-auth-modal',
            VINAPET_THEME_URI . '/assets/js/modal-auth.js',
            array('jquery'),
            VINAPET_VERSION,
            true
        );
    }

    /**
     * Localize auth scripts with necessary data
     */
    public function localize_auth_scripts()
    {
        if (!wp_script_is('vinapet-auth-modal', 'enqueued')) {
            return;
        }

        $auth_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_auth_nonce'),
            'login_nonce' => wp_create_nonce('vinapet_login_action'),
            'register_nonce' => wp_create_nonce('vinapet_register_action'),
            'home_url' => home_url(),
            'login_redirect' => home_url('/tai-khoan'),
            'is_user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id(),
            'has_nextend' => $this->has_nextend_social_login(),
        );

        // Add Nextend Social Login URLs if available
        if ($this->has_nextend_social_login()) {
            $auth_data['social_login_providers'] = $this->get_available_social_providers();
        }

        wp_localize_script('vinapet-auth-modal', 'vinapet_auth_data', $auth_data);
    }

    /**
     * Add auth modal to footer
     */
    public function add_auth_modal_to_footer()
    {
        if (is_admin() || is_user_logged_in()) {
            return;
        }

        get_template_part('template-parts/modal', 'auth');
    }

    /**
     * Setup customer role on theme activation
     */
    public function setup_customer_role()
    {
        // Add custom customer role if it doesn't exist
        if (!get_role('customer')) {
            add_role('customer', 'Khách hàng', array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
            ));
        }
    }

    /**
     * Check if Nextend Social Login is available
     */
    private function has_nextend_social_login()
    {
        return class_exists('NextendSocialLogin') || function_exists('NextendSocialLogin');
    }

    /**
     * Get available social providers
     */
    private function get_available_social_providers()
    {
        $providers = array();

        if ($this->has_nextend_social_login() && class_exists('NextendSocialLogin')) {
            // Get enabled providers from Nextend
            $providers[] = 'google';
            // Add other providers as needed
        }

        return $providers;
    }

    /**
     * Handle AJAX login
     */
    public function handle_ajax_login()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_login_action')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_password'];
        $remember = isset($_POST['remember']) ? true : false;

        // Validate inputs
        if (empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Vui lòng nhập đầy đủ thông tin'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Email không hợp lệ'));
        }

        // Attempt login
        $creds = array(
            'user_login' => $email,
            'user_password' => $password,
            'remember' => $remember
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => 'Email hoặc mật khẩu không đúng'));
        }

        // Update ERPNext if needed
        $this->update_user_login_erpnext($user->user_login, $user);

        wp_send_json_success(array(
            'message' => 'Đăng nhập thành công',
            'redirect_url' => home_url('/tai-khoan'),
            'user_id' => $user->ID
        ));
    }


    /**
     * Handle AJAX register với tích hợp ERP Customer API
     */
    public function handle_ajax_register()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_register_action')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Sanitize input
        $name = sanitize_text_field($_POST['user_name']);
        $email = sanitize_email($_POST['user_email']);
        $password = $_POST['user_password'];
        $phone = sanitize_text_field($_POST['user_phone'] ?? '');
        $address = sanitize_textarea_field($_POST['user_address'] ?? '');
        $company_name = sanitize_text_field($_POST['company_name'] ?? '');
        $agree_terms = isset($_POST['agree_terms']) ? true : false;

        // Validation
        if (empty($name) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Vui lòng điền đầy đủ thông tin bắt buộc'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Email không hợp lệ'));
        }

        if (strlen($password) < 6) {
            wp_send_json_error(array('message' => 'Mật khẩu phải có ít nhất 6 ký tự'));
        }

        if (strlen($address) > 500) {
            wp_send_json_error(array('message' => 'Địa chỉ quá dài (tối đa 500 ký tự)'));
        }

        if (!$agree_terms) {
            wp_send_json_error(array('message' => 'Vui lòng đồng ý với điều khoản sử dụng'));
        }

        // Check if user exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email đã được sử dụng'));
        }

        // Validate phone number
        if (!empty($phone) && !$this->validate_phone_number($phone)) {
            wp_send_json_error(array('message' => 'Số điện thoại không hợp lệ'));
        }

        // Create user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $name,
            'first_name' => $this->get_first_name($name),
            'last_name' => $this->get_last_name($name),
            'role' => 'customer'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Không thể tạo tài khoản. Vui lòng thử lại.'));
        }

        // Save additional user meta
        update_user_meta($user_id, 'phone_number', $phone);
        update_user_meta($user_id, 'user_address', $address);
        update_user_meta($user_id, 'company_name', $company_name);
        update_user_meta($user_id, 'registration_date', current_time('mysql'));
        update_user_meta($user_id, 'terms_agreed', true);
        update_user_meta($user_id, 'terms_agreed_date', current_time('mysql'));

        // ============================================================================
        // TÍCH HỢP ERP CUSTOMER API
        // ============================================================================

        // Tạo customer trong ERP
        if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php')) {
            require_once VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php';

            $erp_customer_data = array(
                'customer_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'company_name' => $company_name ?? ''
            );

            $erp_result = VinaPet_Customer_Sync_Helper::create_erp_customer_for_user($user_id, $erp_customer_data);

            if ($erp_result && $erp_result['status'] === 'success') {
                update_user_meta($user_id, 'erpnext_customer_id', $erp_result['name']);
                update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));
            }
        }

        wp_send_json_success(array(
            'message' => 'Đăng ký thành công',
            'user_id' => $user_id
        ));
    }

    /**
     * Sync user to ERPNext on registration - CẬP NHẬT PHƯƠNG THỨC CŨ
     */
    public function sync_user_to_erpnext($user_id)
    {
        // Method này sẽ được gọi bởi hook user_register
        // Nhưng bây giờ chúng ta đã xử lý trong handle_ajax_register rồi
        // Nên chỉ cần log để tránh duplicate
        error_log("VinaPet: sync_user_to_erpnext called for user {$user_id} - already handled in registration");
    }

    /**
     * Handle login status check
     */
    public function handle_check_login_status()
    {
        wp_send_json_success(array(
            'is_logged_in' => is_user_logged_in(),
            'user_id' => get_current_user_id(),
            'redirect_url' => home_url('/tai-khoan')
        ));
    }
    /**
     * Modify login menu item
     */
    public function modify_login_menu_item($items, $args)
    {
        // Only modify main menu
        if ($args->theme_location !== 'primary') {
            return $items;
        }

        if (is_user_logged_in()) {
            // Replace login link with account link for logged-in users
            $items = str_replace(
                'class="login-trigger"',
                'href="' . home_url('/tai-khoan') . '"',
                $items
            );
        } else {
            // Ensure login triggers modal
            $items = str_replace(
                'href="#"',
                'href="#" data-auth-modal="open"',
                $items
            );
        }

        return $items;
    }

    // =============================================================================
    // ERPNext Integration Methods
    // =============================================================================

    /**
     * Sync user to ERPNext on registration
     */
    // public function sync_user_to_erpnext($user_id)
    // {
    //     if (empty($this->erpnext_settings['api_url']) || empty($this->erpnext_settings['api_key'])) {
    //         return;
    //     }

    //     $user = get_user_by('ID', $user_id);
    //     if (!$user) {
    //         return;
    //     }

    //     $customer_data = array(
    //         'doctype' => 'Customer',
    //         'customer_name' => $user->display_name,
    //         'customer_type' => 'Individual',
    //         'customer_group' => 'All Customer Groups',
    //         'territory' => 'All Territories',
    //         'email_id' => $user->user_email,
    //         'mobile_no' => get_user_meta($user_id, 'phone_number', true),
    //         'custom_wordpress_user_id' => $user_id,
    //         'custom_registration_source' => 'Website',
    //         'custom_address' => get_user_meta($user_id, 'user_address', true)
    //     );

    //     $this->send_to_erpnext('resource/Customer', $customer_data, 'POST');
    // }

    /**
     * Update user login info in ERPNext
     */
    public function update_user_login_erpnext($user_login, $user)
    {
        if (empty($this->erpnext_settings['api_url']) || empty($this->erpnext_settings['api_key'])) {
            return;
        }

        // Find customer in ERPNext and update last login
        $update_data = array(
            'custom_last_login' => current_time('Y-m-d H:i:s')
        );

        // This would require the customer name/ID from ERPNext
        // Implementation depends on how you store the ERPNext customer reference
        $erpnext_customer_id = get_user_meta($user->ID, 'erpnext_customer_id', true);
        if ($erpnext_customer_id) {
            $this->send_to_erpnext("resource/Customer/{$erpnext_customer_id}", $update_data, 'PUT');
        }
    }

    /**
     * Send data to ERPNext API
     */
    private function send_to_erpnext($endpoint, $data, $method = 'POST')
    {
        $api_url = trailingslashit($this->erpnext_settings['api_url']) . 'api/' . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'token ' . $this->erpnext_settings['api_key'] . ':' . $this->erpnext_settings['api_secret'],
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        $response = wp_remote_request($api_url, $args);

        if (is_wp_error($response)) {
            error_log('ERPNext API Error: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    // =============================================================================
    // Helper Methods
    // =============================================================================

    /**
     * Validate phone number
     */
    private function validate_phone_number($phone)
    {
        // Remove all non-numeric characters
        $phone_clean = preg_replace('/[^0-9+]/', '', $phone);

        // Check Vietnamese phone number format
        return preg_match('/^(\+84|84|0)?[3|5|7|8|9][0-9]{8}$/', $phone_clean);
    }

    /**
     * Get first name from full name
     */
    private function get_first_name($full_name)
    {
        $parts = explode(' ', trim($full_name));
        return $parts[0];
    }

    /**
     * Get last name from full name
     */
    private function get_last_name($full_name)
    {
        $parts = explode(' ', trim($full_name));
        if (count($parts) > 1) {
            array_shift($parts);
            return implode(' ', $parts);
        }
        return '';
    }

    /**
     * Send welcome email
     */
    private function send_welcome_email($user_id)
    {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        $subject = 'Chào mừng bạn đến với ' . get_bloginfo('name');
        $message = "Xin chào {$user->display_name},\n\n";
        $message .= "Chào mừng bạn đến với " . get_bloginfo('name') . "!\n\n";
        $message .= "Tài khoản của bạn đã được tạo thành công.\n";
        $message .= "Email: {$user->user_email}\n\n";
        $message .= "Bạn có thể đăng nhập tại: " . home_url('/tai-khoan') . "\n\n";
        $message .= "Cảm ơn bạn đã tin tưởng chúng tôi!\n\n";
        $message .= "Trân trọng,\n";
        $message .= "Đội ngũ " . get_bloginfo('name');

        wp_mail($user->user_email, $subject, $message);
    }
}

// Initialize the authentication integration
new VinaPet_Auth_Integration();
