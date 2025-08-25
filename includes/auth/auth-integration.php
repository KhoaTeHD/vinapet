<?php
/**
 * File: includes/auth/auth-integration.php
 * VinaPet Authentication Integration
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Auth_Integration {
    
    public function __construct() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_auth_assets'));
        
        // Add modal to footer
        add_action('wp_footer', array($this, 'add_auth_modal_to_footer'));
        
        // Localize script data
        add_action('wp_enqueue_scripts', array($this, 'localize_auth_scripts'), 20);
        
        // Create customer role on theme activation
        add_action('after_switch_theme', array($this, 'setup_customer_role'));
        
        // Handle Nextend Social Login integration
        add_action('init', array($this, 'setup_nextend_integration'));
        
        // Modify header login button
        add_filter('wp_nav_menu_items', array($this, 'modify_login_menu_item'), 10, 2);
    }

    /**
     * Enqueue auth modal assets
     */
    public function enqueue_auth_assets() {
        // Only load on frontend pages
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
    public function localize_auth_scripts() {
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
        );

        // Add Google login URL if Nextend Social Login is active
        if (class_exists('NextendSocialLogin')) {
            $auth_data['google_login_url'] = $this->get_nextend_google_url();
            $auth_data['has_nextend'] = true;
        } else {
            $auth_data['has_nextend'] = false;
            $auth_data['google_login_url'] = wp_login_url() . '?loginSocial=google';
        }

        wp_localize_script('vinapet-auth-modal', 'vinapet_auth_data', $auth_data);
    }

    /**
     * Add auth modal HTML to footer
     */
    public function add_auth_modal_to_footer() {
        // Don't show modal on admin pages or if user is already logged in
        if (is_admin()) {
            return;
        }

        // Load modal template
        get_template_part('template-parts/modal', 'auth');
    }

    /**
     * Setup customer role
     */
    public function setup_customer_role() {
        // Create customer role
        if (!get_role('customer')) {
            add_role('customer', 'Khách hàng', array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => false,
            ));
        }

        // Create necessary pages if they don't exist
        $this->create_auth_pages();
    }

    /**
     * Create necessary pages for auth flow
     */
    private function create_auth_pages() {
        $pages = array(
            'tai-khoan' => array(
                'title' => 'Tài khoản',
                'content' => '[vinapet_customer_dashboard]',
                'template' => 'page-templates/page-account.php'
            ),
            'dieu-khoan-su-dung' => array(
                'title' => 'Điều khoản sử dụng',
                'content' => 'Nội dung điều khoản sử dụng sẽ được cập nhật...',
                'template' => ''
            ),
            'chinh-sach-bao-mat' => array(
                'title' => 'Chính sách bảo mật',
                'content' => 'Nội dung chính sách bảo mật sẽ được cập nhật...',
                'template' => ''
            )
        );

        foreach ($pages as $slug => $page_data) {
            if (!get_page_by_path($slug)) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_name' => $slug,
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_author' => 1
                ));

                if ($page_id && !empty($page_data['template'])) {
                    update_post_meta($page_id, '_wp_page_template', $page_data['template']);
                }
            }
        }
    }

    /**
     * Setup Nextend Social Login integration
     */
    public function setup_nextend_integration() {
        if (!class_exists('NextendSocialLogin')) {
            return;
        }

        // Customize Nextend Social Login behavior
        add_filter('nsl_login_form_register_label', function() {
            return 'Đăng ký bằng Google';
        });

        add_filter('nsl_login_form_login_label', function() {
            return 'Đăng nhập bằng Google';
        });

        // Redirect after social login
        add_filter('nsl_login_redirect_url', array($this, 'handle_social_login_redirect'), 10, 2);
        
        // Handle social login user data
        add_action('nsl_login_new_user', array($this, 'handle_new_social_user'), 10, 2);
    }

    /**
     * Get Nextend Google login URL
     */
    private function get_nextend_google_url() {
        if (class_exists('NextendSocialLoginProviderGoogle')) {
            return NextendSocialLoginProviderGoogle::getLoginUrl();
        }
        return home_url('/wp-login.php?loginSocial=google');
    }

    /**
     * Handle social login redirect
     */
    public function handle_social_login_redirect($redirect_url, $provider) {
        if ($provider === 'google') {
            // Redirect to customer dashboard or homepage
            return home_url('/tai-khoan');
        }
        return $redirect_url;
    }

    /**
     * Handle new social user registration
     */
    public function handle_new_social_user($user_id, $provider) {
        if ($provider === 'google') {
            // Set user role to customer
            $user = new WP_User($user_id);
            $user->set_role('customer');
            
            // Add additional meta data
            update_user_meta($user_id, 'registration_method', 'google');
            update_user_meta($user_id, 'registration_date', current_time('mysql'));
            update_user_meta($user_id, 'account_status', 'active');
            
            // Send welcome email
            $user_data = get_userdata($user_id);
            $this->send_social_welcome_email($user_data);
        }
    }

    /**
     * Send welcome email for social login users
     */
    private function send_social_welcome_email($user) {
        $subject = '[VinaPet] Chào mừng bạn đến với VinaPet!';
        $message = $this->get_social_welcome_email_template($user->display_name, $user->ID);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: VinaPet <' . get_option('admin_email') . '>'
        );
        
        wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Get social welcome email template
     */
    private function get_social_welcome_email_template($name, $user_id) {
        $site_url = home_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Chào mừng đến với VinaPet</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #2E86AB; margin: 0;">Chào mừng đến với VinaPet!</h1>
                </div>
                
                <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h2 style="color: #19457B; margin-top: 0;">Xin chào <?php echo esc_html($name); ?>!</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại VinaPet thông qua Google. Chúng tôi rất vui mừng chào đón bạn vào cộng đồng yêu thương thú cưng của chúng tôi.</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h3 style="color: #19457B;">Thông tin tài khoản:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 8px;"><strong>Mã khách hàng:</strong> VP<?php echo str_pad($user_id, 6, '0', STR_PAD_LEFT); ?></li>
                        <li style="margin-bottom: 8px;"><strong>Phương thức đăng ký:</strong> Google Account</li>
                    </ul>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo esc_url($site_url); ?>" 
                       style="display: inline-block; background: #2E86AB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        Khám phá sản phẩm
                    </a>
                </div>
                
                <div style="border-top: 1px solid #ddd; padding-top: 20px; font-size: 14px; color: #666;">
                    <p>Trân trọng,<br><strong>Đội ngũ VinaPet</strong></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Modify login menu item to trigger modal
     */
    public function modify_login_menu_item($items, $args) {
        // Only modify primary menu
        if ($args->theme_location !== 'primary') {
            return $items;
        }

        // Don't modify if user is logged in
        if (is_user_logged_in()) {
            return $items;
        }

        // Replace login links with modal trigger
        $items = str_replace(
            'href="' . wp_login_url() . '"',
            'href="#" onclick="VinaPetAuth.openModal(); return false;"',
            $items
        );

        return $items;
    }

    /**
     * Setup customer role and capabilities
     */
    public function setup_customer_role() {
        VinaPet_Auth_Handlers::create_customer_role();
        
        // Add custom capabilities to customer role
        $customer_role = get_role('customer');
        if ($customer_role) {
            $customer_role->add_cap('view_orders');
            $customer_role->add_cap('edit_profile');
            $customer_role->add_cap('manage_addresses');
        }
    }

    /**
     * Get user display data for frontend
     */
    public static function get_user_display_data($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return array(
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, 'phone_number', true),
            'customer_code' => 'VP' . str_pad($user_id, 6, '0', STR_PAD_LEFT),
            'registration_date' => get_user_meta($user_id, 'registration_date', true),
            'last_login' => get_user_meta($user_id, 'last_login', true),
            'avatar' => get_avatar_url($user_id, array('size' => 96)),
            'roles' => $user->roles
        );
    }

    /**
     * Check if user has specific capability
     */
    public static function user_can($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        return user_can($user_id, $capability);
    }

    /**
     * Custom logout functionality
     */
    public static function handle_logout($redirect_url = '') {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        
        // Log logout activity
        error_log("VinaPet Logout: User ID {$user_id} logged out");
        
        // Clear auth cookies
        wp_clear_auth_cookie();
        wp_logout();

        // Redirect
        if (empty($redirect_url)) {
            $redirect_url = home_url();
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
}

// Initialize auth integration
new VinaPet_Auth_Integration();

/**
 * Helper functions for use in templates
 */

/**
 * Check if current user is customer
 */
function vinapet_is_customer($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user = get_userdata($user_id);
    return $user && in_array('customer', $user->roles);
}

/**
 * Get customer display name
 */
function vinapet_get_customer_name($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user_data = VinaPet_Auth_Integration::get_user_display_data($user_id);
    return $user_data ? $user_data['display_name'] : '';
}

/**
 * Get customer code
 */
function vinapet_get_customer_code($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return $user_id ? 'VP' . str_pad($user_id, 6, '0', STR_PAD_LEFT) : '';
}

/**
 * Shortcode for customer dashboard
 */
function vinapet_customer_dashboard_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="#" onclick="VinaPetAuth.openModal(); return false;">đăng nhập</a> để xem thông tin tài khoản.</p>';
    }

    $user_data = VinaPet_Auth_Integration::get_user_display_data();
    
    if (!$user_data) {
        return '<p>Không thể tải thông tin tài khoản.</p>';
    }

    ob_start();
    ?>
    <div class="customer-dashboard">
        <div class="dashboard-header">
            <div class="customer-info">
                <img src="<?php echo esc_url($user_data['avatar']); ?>" alt="Avatar" class="customer-avatar">
                <div class="customer-details">
                    <h2>Xin chào, <?php echo esc_html($user_data['display_name']); ?>!</h2>
                    <p class="customer-code">Mã khách hàng: <?php echo esc_html($user_data['customer_code']); ?></p>
                </div>
            </div>
            <div class="dashboard-actions">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">Đăng xuất</a>
            </div>
        </div>
        
        <div class="dashboard-content">
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Thông tin cá nhân</h3>
                    <p><strong>Email:</strong> <?php echo esc_html($user_data['email']); ?></p>
                    <?php if ($user_data['phone']): ?>
                        <p><strong>Số điện thoại:</strong> <?php echo esc_html($user_data['phone']); ?></p>
                    <?php endif; ?>
                    <p><strong>Ngày đăng ký:</strong> <?php echo date('d/m/Y', strtotime($user_data['registration_date'])); ?></p>
                </div>
                
                <div class="dashboard-card">
                    <h3>Đơn hàng gần đây</h3>
                    <p>Chức năng đang phát triển...</p>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .customer-dashboard {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .customer-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .customer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid #2E86AB;
        }
        
        .customer-details h2 {
            margin: 0;
            color: #19457B;
        }
        
        .customer-code {
            color: #666;
            margin: 0;
            font-size: 14px;
        }
        
        .logout-btn {
            background: #EF4444;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #DC2626;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dashboard-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dashboard-card h3 {
            color: #19457B;
            margin-bottom: 15px;
        }
        
        .dashboard-card p {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .customer-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('vinapet_customer_dashboard', 'vinapet_customer_dashboard_shortcode');