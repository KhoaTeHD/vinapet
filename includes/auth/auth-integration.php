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

        // Nextend Social Login integration
        add_filter('nsl_register_redirect_url', array($this, 'force_register_redirect'), 10, 2);

        // AJAX handlers - Google register
        add_action('wp_ajax_nopriv_vinapet_ajax_google_register', array($this, 'handle_ajax_google_register'));

        // Nextend hooks
        add_filter('nsl_registration_require_valid_email', '__return_true');
        add_filter('nsl_registration_email_verified', '__return_true');
        add_action('nsl_register_new_user', array($this, 'intercept_google_registration'), 10, 2);

        // Session management
        add_action('init', array($this, 'start_session_safely'));
    }

    /**
     * Start session safely
     */
    public function start_session_safely()
    {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Get session safely
     */
    private function get_session_safely()
    {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
        return $_SESSION;
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
        $session = $this->get_session_safely();
        $auth_data['google_pending'] = isset($session['google_pending_user']);
        if ($auth_data['google_pending']) {
            $auth_data['google_data'] = $session['google_pending_user'];
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
     */
    public function intercept_google_registration($user_id, $provider_object)
    {
        try {
            // Kiểm tra provider
            if (!is_object($provider_object)) {
                return;
            }

            // Lấy provider ID một cách an toàn
            $provider_id = '';
            if (method_exists($provider_object, 'getId')) {
                $provider_id = $provider_object->getId();
            } elseif (property_exists($provider_object, 'id')) {
                $provider_id = $provider_object->id;
            }

            if ($provider_id !== 'google') {
                return;
            }

            // Lấy thông tin user vừa được tạo
            $user = get_user_by('ID', $user_id);
            if (!$user) {
                return;
            }

            // Kiểm tra có phải manual register không
            if (isset($_GET['google_manual_register'])) {
                return;
            }

            // Kiểm tra user có meta đặc biệt không (từ custom form)
            $has_custom_data = get_user_meta($user_id, 'custom_google_register', true);
            if ($has_custom_data) {
                return;
            }

            // Lấy Google ID một cách an toàn - không dùng getAuthUserId()
            $google_id = $user->user_email; // Fallback sử dụng email

            // Lưu thông tin user vào session
            $session = $this->get_session_safely();
            $session['google_pending_user'] = array(
                'email' => $user->user_email,
                'name' => $user->display_name,
                'google_id' => $google_id,
                'temp_user_id' => $user_id
            );
            $_SESSION = $session;

            // Include WordPress admin functions để có wp_delete_user()
            if (!function_exists('wp_delete_user')) {
                require_once(ABSPATH . 'wp-admin/includes/user.php');
            }

            // XÓA user vừa tạo
            wp_delete_user($user_id);

            // OUTPUT JAVASCRIPT ĐỂ ĐÓNG POPUP VÀ COMMUNICATE VỚI PARENT
            $home_url = home_url();
            $email = esc_js($user->user_email);
            $name = esc_js($user->display_name);

?>
            <!DOCTYPE html>
            <html>

            <head>
                <title>Google Registration</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        padding: 50px;
                        background: #f5f5f5;
                    }

                    .message {
                        background: white;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                        max-width: 400px;
                        margin: 0 auto;
                    }
                </style>
            </head>

            <body>
                <div class="message">
                    <h3>Đang xử lý...</h3>
                    <p>Vui lòng đợi trong giây lát.</p>
                </div>

                <script>
                    (function() {
                        var homeUrl = '<?php echo $home_url; ?>';
                        var email = '<?php echo $email; ?>';
                        var name = '<?php echo $name; ?>';

                        // Kiểm tra có phải popup không
                        if (window.opener && window.opener !== window) {
                            try {
                                // Đây là popup - gửi message đến parent window
                                window.opener.postMessage({
                                    type: 'google_register_required',
                                    email: email,
                                    name: name
                                }, homeUrl);

                                // Đóng popup sau 1 giây
                                setTimeout(function() {
                                    window.close();
                                }, 1000);

                            } catch (e) {
                                console.error('Error communicating with parent:', e);
                                // Fallback: redirect parent window
                                window.opener.location.href = homeUrl + '?show_google_register=1';
                                window.close();
                            }
                        } else {
                            // Không phải popup - redirect bình thường
                            window.location.href = homeUrl + '?show_google_register=1';
                        }
                    })();
                </script>
            </body>

            </html>
            <?php
            exit;
        } catch (Exception $e) {
            // Log error nhưng không crash
            error_log('VinaPet Google Intercept Error: ' . $e->getMessage());
            return;
        }
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

        if (!$agree_terms) {
            wp_send_json_error(array('message' => 'Vui lòng đồng ý với điều khoản sử dụng'));
        }

        if (username_exists($email) || email_exists($email)) {
            wp_send_json_error(array('message' => 'Email này đã được sử dụng'));
        }

        // Create WordPress user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $name,
            'first_name' => $name,
            'role' => 'customer'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Không thể tạo tài khoản'));
        }

        // Store additional user meta
        update_user_meta($user_id, 'user_phone', $phone);
        update_user_meta($user_id, 'user_address', $address);
        update_user_meta($user_id, 'company_name', $company_name);

        // Sync to ERPNext if helper exists
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

        $session = $this->get_session_safely();
        $google_data = isset($session['google_pending_user']) ? $session['google_pending_user'] : null;

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

        // Check if email already exists
        if (email_exists($user_email)) {
            wp_send_json_error(array('message' => 'Email đã được sử dụng'));
        }

        // Create user với đầy đủ thông tin
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
        update_user_meta($user_id, 'custom_google_register', true);

        // Link với Nextend Social Login
        if (class_exists('NextendSocialLogin')) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'nextend_social_login_users';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'user_id' => $user_id,
                        'provider' => 'google',
                        'provider_id' => $google_data['google_id'],
                        'created' => current_time('mysql')
                    )
                );
            }
        }

        // Sync to ERPNext giống như register thường
        if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php')) {
            require_once VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php';

            $erp_customer_data = array(
                'customer_name' => $user_name,
                'email' => $user_email,
                'phone' => $user_phone,
                'address' => $user_address,
                'company_name' => ''
            );

            $erp_result = VinaPet_Customer_Sync_Helper::create_erp_customer_for_user($user_id, $erp_customer_data);

            if ($erp_result && $erp_result['status'] === 'success') {
                update_user_meta($user_id, 'erpnext_customer_id', $erp_result['name']);
                update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));
            }
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
            $session = $this->get_session_safely();
            if (isset($session['google_pending_user'])) {
                $google_data = $session['google_pending_user'];
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
        }

        return $items;
    }
}
