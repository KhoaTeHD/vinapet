<?php

/**
 * Account AJAX Handlers
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Account_Ajax
{

    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Update profile info
        add_action('wp_ajax_update_profile_info', array($this, 'update_profile_info'));
        add_action('wp_ajax_nopriv_update_profile_info', array($this, 'ajax_login_required'));

        // Change password
        add_action('wp_ajax_change_user_password', array($this, 'change_user_password'));
        add_action('wp_ajax_nopriv_change_user_password', array($this, 'ajax_login_required'));
    }

    /**
     * Update user profile information
     */
    public function update_profile_info()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['profile_info_nonce'], 'update_profile_info')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Phiên làm việc không hợp lệ!'
            )));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn cần đăng nhập để thực hiện chức năng này!'
            )));
        }

        $current_user_id = get_current_user_id();

        // Sanitize input data
        $display_name = sanitize_text_field($_POST['display_name']);
        $user_phone = sanitize_text_field($_POST['user_phone']);
        $user_email = sanitize_email($_POST['user_email']);

        // Validate email
        if (!is_email($user_email)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Địa chỉ email không hợp lệ!'
            )));
        }

        // Check if email already exists for another user
        $email_exists = email_exists($user_email);
        if ($email_exists && $email_exists != $current_user_id) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Email này đã được sử dụng bởi tài khoản khác!'
            )));
        }

        // Update user data
        $user_data = array(
            'ID' => $current_user_id,
            'display_name' => $display_name,
            'user_email' => $user_email
        );

        $user_id = wp_update_user($user_data);

        if (is_wp_error($user_id)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Có lỗi xảy ra khi cập nhật thông tin: ' . $user_id->get_error_message()
            )));
        }

        // Update phone number
        if (!empty($user_phone)) {
            update_user_meta($current_user_id, 'phone', $user_phone);
        }

        // Sync with ERPNext if enabled
        if (function_exists('vinapet_is_erpnext_enabled') && vinapet_is_erpnext_enabled()) {
            // $this->sync_user_to_erpnext($current_user_id, array(
            //     'customer_name' => $display_name,
            //     'email_id' => $user_email,
            //     'mobile_no' => $user_phone
            // ));
        }

        wp_die(json_encode(array(
            'success' => true,
            'data' => 'Cập nhật thông tin thành công!'
        )));
    }

    /**
     * Change user password
     */
    public function change_user_password()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['change_password_nonce'], 'change_password')) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Phiên làm việc không hợp lệ!'
            )));
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn cần đăng nhập để thực hiện chức năng này!'
            )));
        }

        $current_user_id = get_current_user_id();
        $current_user = get_userdata($current_user_id);

        // Get form data
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate current password
        if (!wp_check_password($current_password, $current_user->user_pass, $current_user_id)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Mật khẩu hiện tại không đúng!'
            )));
        }

        // Validate new password
        if (strlen($new_password) < 6) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Mật khẩu mới phải có ít nhất 6 ký tự!'
            )));
        }

        if ($new_password !== $confirm_password) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Mật khẩu xác nhận không khớp!'
            )));
        }

        // Update password
        wp_set_password($new_password, $current_user_id);

        wp_die(json_encode(array(
            'success' => true,
            'data' => 'Đổi mật khẩu thành công!'
        )));
    }

    /**
     * Sync user data to ERPNext
     */
    // private function sync_user_to_erpnext($user_id, $data) {
    //     try {
    //         if (class_exists('VinaPet_ERP_API_Client')) {
    //             $erp_client = new VinaPet_ERP_API_Client();

    //             // Get user's ERPNext customer ID
    //             $customer_id = get_user_meta($user_id, 'erpnext_customer_id', true);

    //             if ($customer_id) {
    //                 // Update existing customer
    //                 $erp_client->update_customer($customer_id, $data);
    //             } else {
    //                 // Create new customer
    //                 $response = $erp_client->create_customer($data);
    //                 if ($response && isset($response['name'])) {
    //                     update_user_meta($user_id, 'erpnext_customer_id', $response['name']);
    //                 }
    //             }
    //         }
    //     } catch (Exception $e) {
    //         error_log('ERPNext sync error: ' . $e->getMessage());
    //     }
    // }

    /**
     * Handle AJAX requests from non-logged-in users
     */
    public function ajax_login_required()
    {
        wp_die(json_encode(array(
            'success' => false,
            'data' => 'Bạn cần đăng nhập để thực hiện chức năng này!',
            'login_required' => true
        )));
    }
}

// Initialize the AJAX handler
new VinaPet_Account_Ajax();
