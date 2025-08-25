<?php
/**
 * File: includes/auth/class-auth-handlers.php
 * VinaPet Authentication Handlers
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Auth_Handlers {
    
    public function __construct() {
        // AJAX handlers for logged in and non-logged in users
        add_action('wp_ajax_vinapet_ajax_login', array($this, 'handle_ajax_login'));
        add_action('wp_ajax_nopriv_vinapet_ajax_login', array($this, 'handle_ajax_login'));
        
        add_action('wp_ajax_vinapet_ajax_register', array($this, 'handle_ajax_register'));
        add_action('wp_ajax_nopriv_vinapet_ajax_register', array($this, 'handle_ajax_register'));
        
        add_action('wp_ajax_vinapet_forgot_password', array($this, 'handle_forgot_password'));
        add_action('wp_ajax_nopriv_vinapet_forgot_password', array($this, 'handle_forgot_password'));
        
        // Hook để xử lý sau khi đăng nhập thành công
        add_action('wp_login', array($this, 'after_login'), 10, 2);
        
        // Hook để xử lý sau khi đăng ký thành công
        add_action('user_register', array($this, 'after_register'), 10, 1);
    }

    /**
     * Xử lý AJAX đăng ký
     */
    public function handle_ajax_register() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_register_action')) {
            wp_send_json_error(array(
                'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'
            ));
        }

        // Sanitize input data
        $user_name = sanitize_text_field($_POST['user_name']);
        $user_email = sanitize_email($_POST['user_email']);
        $user_phone = sanitize_text_field($_POST['user_phone']);
        $user_password = sanitize_text_field($_POST['user_password']);
        $agree_terms = isset($_POST['agree_terms']) ? (bool) $_POST['agree_terms'] : false;

        // Validate required fields
        if (empty($user_name) || empty($user_email) || empty($user_password)) {
            wp_send_json_error(array(
                'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc.'
            ));
        }

        // Validate email format
        if (!is_email($user_email)) {
            wp_send_json_error(array(
                'message' => 'Email không hợp lệ.'
            ));
        }

        // Validate phone number
        if (!$this->validate_vietnamese_phone($user_phone)) {
            wp_send_json_error(array(
                'message' => 'Số điện thoại không hợp lệ.'
            ));
        }

        // Validate password strength
        if (strlen($user_password) < 6) {
            wp_send_json_error(array(
                'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'
            ));
        }

        // Check terms agreement
        if (!$agree_terms) {
            wp_send_json_error(array(
                'message' => 'Vui lòng đồng ý với điều khoản sử dụng.'
            ));
        }

        // Check if email already exists
        if (email_exists($user_email)) {
            wp_send_json_error(array(
                'message' => 'Email đã tồn tại trong hệ thống.'
            ));
        }

        // Check if phone already exists
        if ($this->phone_exists($user_phone)) {
            wp_send_json_error(array(
                'message' => 'Số điện thoại đã được sử dụng.'
            ));
        }

        // Create username from email
        $username = $this->generate_username_from_email($user_email);

        // Prepare user data
        $user_data = array(
            'user_login' => $username,
            'user_email' => $user_email,
            'user_pass' => $user_password,
            'display_name' => $user_name,
            'first_name' => $this->extract_first_name($user_name),
            'last_name' => $this->extract_last_name($user_name),
            'role' => 'customer' // Custom role for customers
        );

        // Create user
        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => 'Không thể tạo tài khoản: ' . $user_id->get_error_message()
            ));
        }

        // Save additional user meta
        update_user_meta($user_id, 'phone_number', $user_phone);
        update_user_meta($user_id, 'registration_date', current_time('mysql'));
        update_user_meta($user_id, 'registration_ip', $this->get_client_ip());
        update_user_meta($user_id, 'account_status', 'active');
        update_user_meta($user_id, 'terms_agreed', true);
        update_user_meta($user_id, 'terms_agreed_date', current_time('mysql'));

        // Send welcome email
        $this->send_welcome_email($user_id, $user_email, $user_name);

        // Log successful registration
        error_log("VinaPet Registration Success: User {$user_email} (ID: {$user_id}) registered from IP: " . $this->get_client_ip());

        wp_send_json_success(array(
            'message' => 'Tài khoản đã được tạo thành công!',
            'user' => array(
                'ID' => $user_id,
                'display_name' => $user_name,
                'email' => $user_email
            )
        ));
    }

    /**
     * Xử lý quên mật khẩu
     */
    public function handle_forgot_password() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_auth_nonce')) {
            wp_send_json_error(array(
                'message' => 'Phiên làm việc không hợp lệ.'
            ));
        }

        $user_email = sanitize_email($_POST['user_email']);

        if (empty($user_email) || !is_email($user_email)) {
            wp_send_json_error(array(
                'message' => 'Email không hợp lệ.'
            ));
        }

        $user = get_user_by('email', $user_email);
        
        if (!$user) {
            wp_send_json_error(array(
                'message' => 'Email không tồn tại trong hệ thống.'
            ));
        }

        // Generate password reset key
        $reset_key = get_password_reset_key($user);
        
        if (is_wp_error($reset_key)) {
            wp_send_json_error(array(
                'message' => 'Không thể tạo link khôi phục. Vui lòng thử lại.'
            ));
        }

        // Send reset email
        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
        
        $subject = '[VinaPet] Khôi phục mật khẩu';
        $message = $this->get_password_reset_email_template($user->display_name, $reset_url);
        
        $sent = wp_mail($user_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));

        if ($sent) {
            wp_send_json_success(array(
                'message' => 'Link khôi phục mật khẩu đã được gửi đến email của bạn.'
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'Không thể gửi email. Vui lòng thử lại sau.'
            ));
        }
    }

    /**
     * Xử lý sau khi đăng nhập thành công
     */
    public function after_login($user_login, $user) {
        // Update last login time
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
        update_user_meta($user->ID, 'last_login_ip', $this->get_client_ip());
        
        // Log login activity
        $this->log_user_activity($user->ID, 'login', 'User logged in successfully');
    }

    /**
     * Xử lý sau khi đăng ký thành công
     */
    public function after_register($user_id) {
        // Send notification to admin
        $user = get_userdata($user_id);
        $admin_email = get_option('admin_email');
        
        $subject = '[VinaPet] Khách hàng mới đăng ký';
        $message = "Khách hàng mới vừa đăng ký:\n\n";
        $message .= "Tên: " . $user->display_name . "\n";
        $message .= "Email: " . $user->user_email . "\n";
        $message .= "Số điện thoại: " . get_user_meta($user_id, 'phone_number', true) . "\n";
        $message .= "Thời gian: " . current_time('d/m/Y H:i:s') . "\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Log registration activity
        $this->log_user_activity($user_id, 'register', 'User registered successfully');
    }

    /**
     * Validate Vietnamese phone number
     */
    private function validate_vietnamese_phone($phone) {
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check Vietnamese phone number patterns
        $patterns = array(
            '/^(84|0)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-6|8|9]|9[0-4|6-9])[0-9]{7}$/', // Mobile
            '/^(84|0)(2[0-9])[0-9]{8}$/' // Landline
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if phone number exists
     */
    private function phone_exists($phone) {
        global $wpdb;
        
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = 'phone_number' 
             AND meta_value = %s",
            $phone
        ));
        
        return !empty($user_id);
    }

    /**
     * Generate unique username from email
     */
    private function generate_username_from_email($email) {
        $username = strtolower(substr($email, 0, strpos($email, '@')));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        
        // Ensure username is unique
        $original_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Extract first name from full name
     */
    private function extract_first_name($full_name) {
        $names = explode(' ', trim($full_name));
        return isset($names[0]) ? $names[0] : '';
    }

    /**
     * Extract last name from full name
     */
    private function extract_last_name($full_name) {
        $names = explode(' ', trim($full_name));
        if (count($names) > 1) {
            array_shift($names); // Remove first name
            return implode(' ', $names);
        }
        return '';
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Get login redirect URL
     */
    private function get_login_redirect_url($user) {
        // Check if there's a redirect parameter
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '';
        
        if (!empty($redirect_to) && wp_validate_redirect($redirect_to)) {
            return $redirect_to;
        }
        
        // Default redirects based on user role
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } else if (in_array('customer', $user->roles)) {
            return home_url('/tai-khoan'); // Customer dashboard
        }
        
        return home_url(); // Default to homepage
    }

    /**
     * Send welcome email to new users
     */
    private function send_welcome_email($user_id, $email, $name) {
        $subject = '[VinaPet] Chào mừng bạn đến với VinaPet!';
        $message = $this->get_welcome_email_template($name, $user_id);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: VinaPet <' . get_option('admin_email') . '>'
        );
        
        wp_mail($email, $subject, $message, $headers);
    }

    /**
     * Get welcome email template
     */
    private function get_welcome_email_template($name, $user_id) {
        $login_url = wp_login_url();
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Chào mừng đến với VinaPet</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #2E86AB; margin: 0;">Chào mừng đến với VinaPet!</h1>
                </div>
                
                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2 style="color: #19457B; margin-top: 0;">Xin chào <?php echo esc_html($name); ?>!</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại VinaPet. Chúng tôi rất vui mừng chào đón bạn vào cộng đồng yêu thương thú cưng của chúng tôi.</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #19457B;">Thông tin tài khoản:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><strong>Email:</strong> <?php echo esc_html($email); ?></li>
                        <li style="margin-bottom: 8px;"><strong>Mã khách hàng:</strong> VP<?php echo str_pad($user_id, 6, '0', STR_PAD_LEFT); ?></li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($site_url); ?>" 
                       style="display: inline-block; background: #2E86AB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        Khám phá sản phẩm
                    </a>
                </div>
                
                <div style="border-top: 1px solid #ddd; padding-top: 20px; font-size: 14px; color: #666;">
                    <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua email: 
                       <a href="mailto:<?php echo get_option('admin_email'); ?>" style="color: #2E86AB;">
                           <?php echo get_option('admin_email'); ?>
                       </a>
                    </p>
                    <p style="margin-top: 15px;">
                        Trân trọng,<br>
                        <strong>Đội ngũ VinaPet</strong>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get password reset email template
     */
    private function get_password_reset_email_template($name, $reset_url) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Khôi phục mật khẩu - VinaPet</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #2E86AB; margin: 0;">Khôi phục mật khẩu</h1>
                </div>
                
                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2 style="color: #19457B; margin-top: 0;">Xin chào <?php echo esc_html($name); ?>!</h2>
                    <p>Chúng tôi nhận được yêu cầu khôi phục mật khẩu cho tài khoản của bạn.</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($reset_url); ?>" 
                       style="display: inline-block; background: #2E86AB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        Đặt lại mật khẩu
                    </a>
                </div>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404;">
                        <strong>Lưu ý:</strong> Link này sẽ hết hạn sau 24 giờ. Nếu bạn không yêu cầu khôi phục mật khẩu, vui lòng bỏ qua email này.
                    </p>
                </div>
                
                <div style="border-top: 1px solid #ddd; padding-top: 20px; font-size: 14px; color: #666;">
                    <p>Nếu nút không hoạt động, bạn có thể copy link sau vào trình duyệt:</p>
                    <p style="word-break: break-all; background: #f5f5f5; padding: 10px; border-radius: 4px;">
                        <?php echo esc_url($reset_url); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Log user activity
     */
    private function log_user_activity($user_id, $action, $description) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vinapet_user_activity';
        
        // Create table if not exists
        $this->create_activity_table();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Create user activity table
     */
    private function create_activity_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vinapet_user_activity';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            description text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create customer role if not exists
     */
    public static function create_customer_role() {
        if (!get_role('customer')) {
            add_role('customer', 'Khách hàng', array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
            ));
        }
    }

    /**
     * Security: Rate limiting for login attempts
     */
    private function check_login_rate_limit($email) {
        $transient_key = 'vinapet_login_attempts_' . md5($email . $this->get_client_ip());
        $attempts = get_transient($transient_key);
        
        if ($attempts >= 5) {
            return false; // Too many attempts
        }
        
        return true;
    }

    /**
     * Increment login attempts
     */
    private function increment_login_attempts($email) {
        $transient_key = 'vinapet_login_attempts_' . md5($email . $this->get_client_ip());
        $attempts = get_transient($transient_key) ?: 0;
        $attempts++;
        
        // Lock for 15 minutes after 5 failed attempts
        set_transient($transient_key, $attempts, 15 * MINUTE_IN_SECONDS);
        
        return $attempts;
    }

    /**
     * Clear login attempts after successful login
     */
    private function clear_login_attempts($email) {
        $transient_key = 'vinapet_login_attempts_' . md5($email . $this->get_client_ip());
        delete_transient($transient_key);
    }
}

// Initialize the auth handlers
new VinaPet_Auth_Handlers();ử lý AJAX đăng nhập
     */
    public function handle_ajax_login() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_login_action')) {
            wp_send_json_error(array(
                'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'
            ));
        }

        // Sanitize input data
        $user_email = sanitize_email($_POST['user_email']);
        $user_password = sanitize_text_field($_POST['user_password']);
        $remember = isset($_POST['remember']) ? (bool) $_POST['remember'] : false;

        // Validate required fields
        if (empty($user_email) || empty($user_password)) {
            wp_send_json_error(array(
                'message' => 'Vui lòng nhập đầy đủ email và mật khẩu.'
            ));
        }

        // Validate email format
        if (!is_email($user_email)) {
            wp_send_json_error(array(
                'message' => 'Email không hợp lệ.'
            ));
        }

        // Get user by email
        $user = get_user_by('email', $user_email);
        
        if (!$user) {
            wp_send_json_error(array(
                'message' => 'Email không tồn tại trong hệ thống.'
            ));
        }

        // Verify password
        if (!wp_check_password($user_password, $user->user_pass, $user->ID)) {
            wp_send_json_error(array(
                'message' => 'Mật khẩu không chính xác.'
            ));
        }

        // Check if user account is active
        if (get_user_meta($user->ID, 'account_status', true) === 'inactive') {
            wp_send_json_error(array(
                'message' => 'Tài khoản của bạn đã bị tạm khóa. Vui lòng liên hệ admin.'
            ));
        }

        // Log the user in
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember, is_ssl());

        // Update last login time
        update_user_meta($user->ID, 'last_login', current_time('mysql'));

        // Log successful login
        error_log("VinaPet Login Success: User {$user->user_email} (ID: {$user->ID}) logged in from IP: " . $this->get_client_ip());

        // Determine redirect URL
        $redirect_url = $this->get_login_redirect_url($user);

        wp_send_json_success(array(
            'message' => 'Đăng nhập thành công!',
            'user' => array(
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email
            ),
            'redirect_url' => $redirect_url
        ));
    }

    /**
     * X