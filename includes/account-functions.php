<?php
/**
 * Account Page Functions
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Include account helper class
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/helpers/class-account-helper.php')) {
    require_once VINAPET_THEME_DIR . '/includes/helpers/class-account-helper.php';
}

/**
 * Include sample orders data
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-orders.php')) {
    require_once VINAPET_THEME_DIR . '/includes/data/sample-orders.php';
}

/**
 * Include account AJAX handlers
 */
if (file_exists(VINAPET_THEME_DIR . '/includes/ajax/ajax-account.php')) {
    require_once VINAPET_THEME_DIR . '/includes/ajax/ajax-account.php';
}

/**
 * Enqueue scripts and styles for account page
 */
function vinapet_account_page_assets() {
    if (is_page_template('page-templates/page-account.php')) {
        // Enqueue account page CSS
        wp_enqueue_style(
            'vinapet-account-page', 
            VINAPET_THEME_URI . '/assets/css/account-page.css', 
            array(), 
            VINAPET_VERSION
        );
        
        // Enqueue account page JavaScript
        wp_enqueue_script(
            'vinapet-account-page', 
            VINAPET_THEME_URI . '/assets/js/account-page.js', 
            array('jquery'), 
            VINAPET_VERSION, 
            true
        );
        
        // Localize script with AJAX data
        wp_localize_script('vinapet-account-page', 'vinapet_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_ajax_nonce'),
            'logout_url' => wp_logout_url(home_url()),
        ));
    }
}
add_action('wp_enqueue_scripts', 'vinapet_account_page_assets');

/**
 * Create account page automatically on theme activation
 */
function vinapet_create_account_page() {
    // Check if account page exists
    $account_page = get_page_by_path('tai-khoan');
    
    if (!$account_page) {
        // Create account page
        $page_data = array(
            'post_title'     => 'Tài khoản',
            'post_name'      => 'tai-khoan',
            'post_content'   => '',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'page_template'  => 'page-templates/page-account.php'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Set page template
            update_post_meta($page_id, '_wp_page_template', 'page-templates/page-account.php');
            
            // Log success
            error_log('VinaPet: Account page created successfully with ID: ' . $page_id);
        } else {
            // Log error
            error_log('VinaPet: Failed to create account page');
        }
    }
}
add_action('after_switch_theme', 'vinapet_create_account_page');

/**
 * Get account page URL
 */
function vinapet_get_account_page_url() {
    $account_page = get_page_by_path('tai-khoan');
    if ($account_page) {
        return get_permalink($account_page->ID);
    }
    return home_url('/tai-khoan/');
}

/**
 * Add account page URL to header script for JavaScript access
 */
function vinapet_header_account_url() {
    if (!is_admin()) {
        echo '<script type="text/javascript">';
        echo 'var vinapet_account_url = "' . esc_url(vinapet_get_account_page_url()) . '";';
        echo '</script>';
    }
}
add_action('wp_head', 'vinapet_header_account_url');

/**
 * Enhanced user meta fields for admin user profile
 */
function vinapet_add_user_meta_fields($user) {
    ?>
    <h3><?php _e('Thông tin bổ sung', 'vinapet'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="phone"><?php _e('Số điện thoại', 'vinapet'); ?></label></th>
            <td>
                <input type="text" 
                       name="phone" 
                       id="phone" 
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>" 
                       class="regular-text" />
                <p class="description"><?php _e('Số điện thoại của người dùng', 'vinapet'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="erpnext_customer_id"><?php _e('ERPNext Customer ID', 'vinapet'); ?></label></th>
            <td>
                <input type="text" 
                       name="erpnext_customer_id" 
                       id="erpnext_customer_id" 
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'erpnext_customer_id', true)); ?>" 
                       class="regular-text" 
                       readonly />
                <p class="description"><?php _e('ID khách hàng trong hệ thống ERPNext (chỉ đọc)', 'vinapet'); ?></p>
            </td>
        </tr>
        <?php 
        // Display last sync time if available
        $last_sync = get_user_meta($user->ID, 'erpnext_last_sync', true);
        if ($last_sync): 
        ?>
        <tr>
            <th><label><?php _e('Lần đồng bộ cuối', 'vinapet'); ?></label></th>
            <td>
                <input type="text" 
                       value="<?php echo esc_attr(date('d/m/Y H:i:s', strtotime($last_sync))); ?>" 
                       class="regular-text" 
                       readonly />
                <p class="description"><?php _e('Thời gian đồng bộ cuối cùng với ERPNext', 'vinapet'); ?></p>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    <?php
}
add_action('show_user_profile', 'vinapet_add_user_meta_fields');
add_action('edit_user_profile', 'vinapet_add_user_meta_fields');

/**
 * Save additional user meta fields
 */
function vinapet_save_user_meta_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    // Save phone number
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }

    // ERPNext Customer ID should not be manually editable
    // It's managed automatically by the system
}
add_action('personal_options_update', 'vinapet_save_user_meta_fields');
add_action('edit_user_profile_update', 'vinapet_save_user_meta_fields');

/**
 * Custom login redirect based on user role
 */
function vinapet_login_redirect($redirect_to, $request, $user) {
    // Only redirect for successful logins
    if (isset($user->roles) && is_array($user->roles)) {
        // If user is customer, redirect to account page
        if (in_array('customer', $user->roles)) {
            return vinapet_get_account_page_url();
        }
        
        // If user is admin/editor, let them go to admin
        if (in_array('administrator', $user->roles) || in_array('editor', $user->roles)) {
            return admin_url();
        }
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'vinapet_login_redirect', 10, 3);

/**
 * Hide admin bar for customers
 */
function vinapet_hide_admin_bar_for_customers() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        
        // Hide admin bar for customers who can't edit posts
        if (in_array('customer', $current_user->roles) && !current_user_can('edit_posts')) {
            show_admin_bar(false);
        }
    }
}
add_action('wp_loaded', 'vinapet_hide_admin_bar_for_customers');

/**
 * Add body class for account page
 */
function vinapet_account_body_class($classes) {
    if (is_page_template('page-templates/page-account.php')) {
        $classes[] = 'account-page-body';
        $classes[] = 'vinapet-account';
    }
    
    // Add user role class
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (!empty($current_user->roles)) {
            $classes[] = 'user-role-' . $current_user->roles[0];
        }
    }
    
    return $classes;
}
add_filter('body_class', 'vinapet_account_body_class');

/**
 * Redirect non-logged-in users trying to access account page
 */
function vinapet_protect_account_page() {
    if (is_page_template('page-templates/page-account.php') && !is_user_logged_in()) {
        // Redirect to login with return URL
        $login_url = wp_login_url(get_permalink());
        wp_redirect($login_url);
        exit;
    }
}
add_action('template_redirect', 'vinapet_protect_account_page');

/**
 * Add account menu item to WordPress admin menu for easier management
 */
function vinapet_add_account_admin_menu() {
    add_theme_page(
        __('Cài đặt tài khoản', 'vinapet'),
        __('Tài khoản khách hàng', 'vinapet'),
        'manage_options',
        'vinapet-account-settings',
        'vinapet_account_settings_page'
    );
}
add_action('admin_menu', 'vinapet_add_account_admin_menu');

/**
 * Account settings admin page
 */
function vinapet_account_settings_page() {
    $account_page = get_page_by_path('tai-khoan');
    $account_url = $account_page ? get_permalink($account_page->ID) : home_url('/tai-khoan/');
    
    ?>
    <div class="wrap">
        <h1><?php _e('Cài đặt tài khoản khách hàng', 'vinapet'); ?></h1>
        
        <div class="card">
            <h2><?php _e('Thông tin trang tài khoản', 'vinapet'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('URL trang tài khoản', 'vinapet'); ?></th>
                    <td>
                        <a href="<?php echo esc_url($account_url); ?>" target="_blank">
                            <?php echo esc_url($account_url); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Trạng thái', 'vinapet'); ?></th>
                    <td>
                        <?php if ($account_page): ?>
                            <span style="color: green;"><?php _e('✓ Đã tạo', 'vinapet'); ?></span>
                        <?php else: ?>
                            <span style="color: red;"><?php _e('✗ Chưa tạo', 'vinapet'); ?></span>
                            <br>
                            <button type="button" class="button" onclick="location.reload()">
                                <?php _e('Tạo trang tài khoản', 'vinapet'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <h2><?php _e('Thống kê người dùng', 'vinapet'); ?></h2>
            <?php
            $customer_count = count(get_users(array('role' => 'customer')));
            $total_users = count(get_users());
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Tổng số khách hàng', 'vinapet'); ?></th>
                    <td><?php echo number_format($customer_count); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Tổng số người dùng', 'vinapet'); ?></th>
                    <td><?php echo number_format($total_users); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Add custom CSS for admin account settings page
 */
function vinapet_account_admin_styles($hook) {
    if ('appearance_page_vinapet-account-settings' !== $hook) {
        return;
    }
    
    ?>
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
    <?php
}
add_action('admin_head', 'vinapet_account_admin_styles');

/**
 * Log account-related activities
 */
function vinapet_log_account_activity($user_id, $action, $details = '') {
    if (!function_exists('error_log')) {
        return;
    }
    
    $user = get_userdata($user_id);
    $username = $user ? $user->user_login : 'unknown';
    $timestamp = current_time('mysql');
    
    $log_message = sprintf(
        '[VinaPet Account] %s - User: %s (ID: %d) - Action: %s - Details: %s',
        $timestamp,
        $username,
        $user_id,
        $action,
        $details
    );
    
    error_log($log_message);
}

/**
 * Helper function to check if ERPNext integration is active
 */
function vinapet_is_erpnext_integration_active() {
    return function_exists('vinapet_is_erpnext_enabled') && vinapet_is_erpnext_enabled();
}