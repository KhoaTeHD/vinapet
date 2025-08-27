<?php
/**
 * Account Helper Class
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Account_Helper {

    /**
     * Get user display information
     */
    public static function get_user_display_info($user_id = null) {
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
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name ?: $user->user_login,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => get_user_meta($user_id, 'phone', true),
            'erpnext_customer_id' => get_user_meta($user_id, 'erpnext_customer_id', true),
            'erpnext_last_sync' => get_user_meta($user_id, 'erpnext_last_sync', true),
            'registration_date' => $user->user_registered,
            'roles' => $user->roles,
        );
    }

    /**
     * Check if user can access account features
     */
    public static function can_user_access_account($user_id = null) {
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
        
        // Check if user has appropriate role
        $allowed_roles = array('customer', 'administrator', 'editor');
        return array_intersect($allowed_roles, $user->roles) ? true : false;
    }

    /**
     * Update user profile information
     */
    public static function update_user_profile($user_id, $data) {
        if (!self::can_user_access_account($user_id)) {
            return new WP_Error('access_denied', 'Không có quyền truy cập.');
        }

        $user_data = array('ID' => $user_id);
        $meta_data = array();

        // Handle standard WordPress user fields
        if (isset($data['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($data['display_name']);
        }

        if (isset($data['user_email'])) {
            $email = sanitize_email($data['user_email']);
            if (!is_email($email)) {
                return new WP_Error('invalid_email', 'Email không hợp lệ.');
            }
            
            // Check if email exists for another user
            $email_exists = email_exists($email);
            if ($email_exists && $email_exists != $user_id) {
                return new WP_Error('email_exists', 'Email này đã được sử dụng.');
            }
            
            $user_data['user_email'] = $email;
        }

        if (isset($data['first_name'])) {
            $user_data['first_name'] = sanitize_text_field($data['first_name']);
        }

        if (isset($data['last_name'])) {
            $user_data['last_name'] = sanitize_text_field($data['last_name']);
        }

        // Handle custom meta fields
        if (isset($data['phone'])) {
            $meta_data['phone'] = sanitize_text_field($data['phone']);
        }

        // Update user data
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) {
            return $result;
        }

        // Update meta data
        foreach ($meta_data as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }

        // Log activity
        if (function_exists('vinapet_log_account_activity')) {
            vinapet_log_account_activity($user_id, 'profile_updated', json_encode($data));
        }

        return true;
    }

    /**
     * Change user password
     */
    public static function change_user_password($user_id, $current_password, $new_password) {
        if (!self::can_user_access_account($user_id)) {
            return new WP_Error('access_denied', 'Không có quyền truy cập.');
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Người dùng không tồn tại.');
        }

        // Verify current password
        if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
            return new WP_Error('incorrect_password', 'Mật khẩu hiện tại không đúng.');
        }

        // Validate new password
        if (strlen($new_password) < 6) {
            return new WP_Error('weak_password', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
        }

        // Change password
        wp_set_password($new_password, $user_id);

        // Log activity
        if (function_exists('vinapet_log_account_activity')) {
            vinapet_log_account_activity($user_id, 'password_changed');
        }

        return true;
    }

    /**
     * Get account navigation menu
     */
    public static function get_account_navigation() {
        $nav_items = array(
            'profile' => array(
                'label' => 'Hồ sơ cá nhân',
                'icon' => '👤',
                'active' => true,
                'url' => '#profile'
            ),
            'orders' => array(
                'label' => 'Đơn hàng',
                'icon' => '📦',
                'active' => false,
                'url' => '#orders'
            ),
            'logout' => array(
                'label' => 'Đăng xuất',
                'icon' => '🚪',
                'active' => false,
                'url' => wp_logout_url(home_url()),
                'class' => 'logout'
            )
        );

        return apply_filters('vinapet_account_navigation', $nav_items);
    }

    /**
     * Get profile tabs
     */
    public static function get_profile_tabs() {
        $tabs = array(
            'info' => array(
                'label' => 'Thông tin của tôi',
                'active' => true,
                'target' => 'info-tab'
            ),
            'password' => array(
                'label' => 'Thay đổi mật khẩu',
                'active' => false,
                'target' => 'password-tab'
            )
        );

        return apply_filters('vinapet_profile_tabs', $tabs);
    }

    /**
     * Sync user with ERPNext
     */
    // public static function sync_with_erpnext($user_id, $force = false) {
    //     if (!function_exists('vinapet_is_erpnext_integration_active') || 
    //         !vinapet_is_erpnext_integration_active()) {
    //         return false;
    //     }

    //     $user_info = self::get_user_display_info($user_id);
    //     if (!$user_info) {
    //         return false;
    //     }

    //     // Check if sync is needed
    //     if (!$force) {
    //         $last_sync = $user_info['erpnext_last_sync'];
    //         if ($last_sync && strtotime($last_sync) > strtotime('-1 hour')) {
    //             return true; // Recently synced, skip
    //         }
    //     }

    //     try {
    //         if (class_exists('VinaPet_ERP_API_Client')) {
    //             $erp_client = new VinaPet_ERP_API_Client();
                
    //             $customer_data = array(
    //                 'customer_name' => $user_info['display_name'],
    //                 'email_id' => $user_info['email'],
    //                 'mobile_no' => $user_info['phone'],
    //                 'customer_group' => 'Individual',
    //                 'territory' => 'Vietnam'
    //             );

    //             if ($user_info['erpnext_customer_id']) {
    //                 // Update existing customer
    //                 $response = $erp_client->update_customer($user_info['erpnext_customer_id'], $customer_data);
    //             } else {
    //                 // Create new customer
    //                 $response = $erp_client->create_customer($customer_data);
    //                 if ($response && isset($response['name'])) {
    //                     update_user_meta($user_id, 'erpnext_customer_id', $response['name']);
    //                 }
    //             }

    //             // Update sync timestamp
    //             update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));

    //             // Log activity
    //             if (function_exists('vinapet_log_account_activity')) {
    //                 vinapet_log_account_activity($user_id, 'erpnext_sync', 'Success');
    //             }

    //             return true;
    //         }
    //     } catch (Exception $e) {
    //         // Log error
    //         error_log('ERPNext sync error for user ' . $user_id . ': ' . $e->getMessage());
            
    //         if (function_exists('vinapet_log_account_activity')) {
    //             vinapet_log_account_activity($user_id, 'erpnext_sync_error', $e->getMessage());
    //         }
    //     }

    //     return false;
    // }

    /**
     * Format date for display
     */
    public static function format_date($date, $format = 'd/m/Y H:i') {
        if (empty($date)) {
            return '';
        }
        
        return date_i18n($format, strtotime($date));
    }

    /**
     * Generate secure token for user actions
     */
    public static function generate_user_token($user_id, $action) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $data = $user_id . '|' . $action . '|' . time();
        return wp_hash($data, 'auth');
    }

    /**
     * Verify user token
     */
    public static function verify_user_token($user_id, $action, $token, $expiry = 3600) {
        $expected_token = self::generate_user_token($user_id, $action);
        return hash_equals($expected_token, $token);
    }
}