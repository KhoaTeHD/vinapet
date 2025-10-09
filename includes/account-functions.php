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
