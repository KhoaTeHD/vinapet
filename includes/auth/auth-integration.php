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

        // Direct Nextend Social Login integration
        add_filter('nsl_register_redirect_url', array($this, 'force_register_redirect'), 10, 2);

        // AJAX handlers - thêm dòng này
        add_action('wp_ajax_nopriv_vinapet_ajax_google_register', array($this, 'handle_ajax_google_register'));

        // Nextend hooks - thêm các dòng này
        add_filter('nsl_registration_require_valid_email', '__return_true');
        add_filter('nsl_registration_email_verified', '__return_true');
        add_action('nsl_register_new_user', array($this, 'intercept_google_registration'), 10, 3);
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

        // Add Google register check
        session_start();
        $auth_data['google_pending'] = isset($_SESSION['google_pending_user']);
        if ($auth_data['google_pending']) {
            $auth_data['google_data'] = $_SESSION['google_pending_user'];
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

        // Check for Google register modal trigger
        $this->check_google_register_modal();
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
     * Intercept Google registration để chuyển sang modal
     * 
     * Hook signature: nsl_register_new_user($user_id, $provider_object)
     * NOT: nsl_register_new_user($provider, $email, $user_data)
     */
    public function intercept_google_registration($user_id, $provider_object)
    {
        // Kiểm tra provider
        if (!is_object($provider_object) || $provider_object->getId() !== 'google') {
            return;
        }

        // Lấy thông tin user vừa được tạo 
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        // Kiểm tra có phải manual register không
        if (isset($_GET['google_manual_register'])) {
            return; // Để Nextend xử lý bình thường
        }

        // Kiểm tra user có meta đặc biệt không (từ form register)
        $has_custom_data = get_user_meta($user_id, 'custom_google_register', true);
        if ($has_custom_data) {
            return; // Đây là user từ custom form, không cần intercept
        }

        // Đây là auto-registration từ Nextend → chúng ta muốn chặn và show modal

        // Lưu thông tin user
        if (!session_id()) {
            session_start();
        }

        $_SESSION['google_pending_user'] = array(
            'email' => $user->user_email,
            'name' => $user->display_name,
            'google_id' => $provider_object->getAuthUserId(),
            'temp_user_id' => $user_id // Lưu để xóa sau
        );

        // XÓA user vừa tạo (vì chúng ta muốn tạo lại với đầy đủ thông tin)
        wp_delete_user($user_id);

        // Redirect to show modal
        wp_safe_redirect(add_query_arg('show_google_register', '1', home_url()));
        exit;
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
     * Handle AJAX Google Register
     */
    public function handle_ajax_google_register()
    {
        // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'vinapet_register_action')) {
        wp_send_json_error(array('message' => 'Security check failed'));
    }

    if (!session_id()) {
        session_start();
    }
    
    $google_data = isset($_SESSION['google_pending_user']) ? $_SESSION['google_pending_user'] : null;

    if (!$google_data) {
        wp_send_json_error(array('message' => 'Phiên Google đã hết hạn. Vui lòng thử lại.'));
    }

    // Validate form data
    $user_name = sanitize_text_field($_POST['user_name']);
    $user_address = sanitize_textarea_field($_POST['user_address']);
    $user_email = sanitize_email($_POST['user_email']);
    $user_phone = sanitize_text_field($_POST['user_phone']);
    $agree_terms = isset($_POST['agree_terms']) && $_POST['agree_terms'] === 'true';

    // Validation
    if (empty($user_name) || empty($user_address) || empty($user_phone)) {
        wp_send_json_error(array('message' => 'Vui lòng điền đầy đủ thông tin'));
    }

    if (!$agree_terms) {
        wp_send_json_error(array('message' => 'Vui lòng đồng ý với điều khoản sử dụng'));
    }

    if ($user_email !== $google_data['email']) {
        wp_send_json_error(array('message' => 'Email không khớp với tài khoản Google'));
    }

    // Check if email already exists (double check)
    if (email_exists($user_email)) {
        wp_send_json_error(array('message' => 'Email đã được sử dụng'));
    }

    // Create user với thông tin đầy đủ
    $user_data = array(
        'user_login' => $user_email,
        'user_email' => $user_email,
        'user_pass' => wp_generate_password(12, false),
        'display_name' => $user_name,
        'first_name' => $user_name,
        'role' => 'customer'
    );

    $user_id = wp_insert_user($user_data);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => 'Không thể tạo tài khoản: ' . $user_id->get_error_message()));
    }

    // Add user meta
    update_user_meta($user_id, 'billing_phone', $user_phone);
    update_user_meta($user_id, 'billing_address_1', $user_address);
    update_user_meta($user_id, 'user_phone', $user_phone);
    update_user_meta($user_id, 'user_address', $user_address);
    update_user_meta($user_id, 'google_registered', true);
    update_user_meta($user_id, 'google_id', $google_data['google_id']);
    update_user_meta($user_id, 'custom_google_register', true); // Flag đặc biệt

    // Link với Nextend Social Login
    if (class_exists('NextendSocialLogin')) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'nextend_social_login_users',
            array(
                'user_id' => $user_id,
                'provider' => 'google',
                'provider_id' => $google_data['google_id'],
                'created' => current_time('mysql')
            )
        );
    }

    // Auto login
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    // Clear session
    unset($_SESSION['google_pending_user']);

    // Success response
    wp_send_json_success(array(
        'message' => 'Đăng ký thành công',
        'redirect_url' => home_url('/tai-khoan'),
        'user_id' => $user_id
    ));
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
     * Check if should show Google register modal
     */
    public function check_google_register_modal()
    {
        if (isset($_GET['show_google_register']) && !is_user_logged_in()) {
            session_start();
            if (isset($_SESSION['google_pending_user'])) {
                $google_data = $_SESSION['google_pending_user'];
?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof window.openGoogleRegisterModal === 'function') {
                            window.openGoogleRegisterModal('<?php echo esc_js($google_data['email']); ?>', '<?php echo esc_js($google_data['name']); ?>');
                        }
                    });
                </script>
<?php
            }
        }
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
}

// Initialize the authentication integration
new VinaPet_Auth_Integration();
