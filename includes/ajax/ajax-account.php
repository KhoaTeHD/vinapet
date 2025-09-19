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

        // Load orders
        add_action('wp_ajax_load_user_orders', array($this, 'load_user_orders'));
        add_action('wp_ajax_nopriv_load_user_orders', array($this, 'ajax_login_required'));

        // Cancel order
        add_action('wp_ajax_cancel_user_order', array($this, 'cancel_user_order'));
        add_action('wp_ajax_nopriv_cancel_user_order', array($this, 'ajax_login_required'));

        // Continue order
        add_action('wp_ajax_continue_user_order', array($this, 'continue_user_order'));
        add_action('wp_ajax_nopriv_continue_user_order', array($this, 'ajax_login_required'));
    }

    /**
     * Update user profile information - WITH ROLLBACK SUPPORT
     */
    public function update_profile_info() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['profile_info_nonce'], 'update_profile_info')) {
            wp_send_json_error('Phiên làm việc không hợp lệ!');
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('Bạn cần đăng nhập để thực hiện chức năng này!');
            return;
        }

        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        // Sanitize input data
        $display_name = sanitize_text_field($_POST['display_name']);
        $user_phone = sanitize_text_field($_POST['user_phone']);
        $user_email = sanitize_email($_POST['user_email']);
        $user_address = sanitize_textarea_field($_POST['user_address'] ?? '');

        // Validate email
        if (!is_email($user_email)) {
            wp_send_json_error('Địa chỉ email không hợp lệ!');
            return;
        }

        // Check if email already exists for another user
        $email_exists = email_exists($user_email);
        if ($email_exists && $email_exists != $current_user_id) {
            wp_send_json_error('Email này đã được sử dụng bởi tài khoản khác!');
            return;
        }

        // ============================================================================
        // BACKUP CURRENT DATA FOR ROLLBACK
        // ============================================================================
        
        $backup_data = array(
            'display_name' => $current_user->display_name,
            'user_email' => $current_user->user_email,
            'phone' => get_user_meta($current_user_id, 'phone', true),
            'user_address' => get_user_meta($current_user_id, 'user_address', true),
            'last_sync' => get_user_meta($current_user_id, 'erpnext_last_sync', true)
        );

        // Check ERP configuration
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $erp_api = new ERP_API_Client();
        
        if (!$erp_api->is_configured()) {
            wp_send_json_error('Hệ thống ERP chưa được cấu hình. Không thể cập nhật thông tin!');
            return;
        }

        // ============================================================================
        // STEP 1: UPDATE WORDPRESS FIRST (dễ rollback)
        // ============================================================================
        
        try {
            error_log('VinaPet: Starting WordPress update for user ' . $current_user_id);

            // Update user data
            $user_data = array(
                'ID' => $current_user_id,
                'display_name' => $display_name,
                'user_email' => $user_email
            );

            $user_update_result = wp_update_user($user_data);

            if (is_wp_error($user_update_result)) {
                throw new Exception('WordPress user update failed: ' . $user_update_result->get_error_message());
            }

            // Update user meta
            $phone_update = update_user_meta($current_user_id, 'phone', $user_phone);
            $address_update = true;
            if (!empty($user_address)) {
                $address_update = update_user_meta($current_user_id, 'user_address', $user_address);
            }

            if ($phone_update === false || $address_update === false) {
                throw new Exception('Failed to update user meta data');
            }

            error_log('VinaPet: WordPress update successful for user ' . $current_user_id);

        } catch (Exception $e) {
            error_log('VinaPet: WordPress update failed for user ' . $current_user_id . ': ' . $e->getMessage());
            wp_send_json_error('Không thể cập nhật thông tin WordPress: ' . $e->getMessage());
            return;
        }

        // ============================================================================
        // STEP 2: UPDATE ERP CUSTOMER
        // ============================================================================
        
        try {
            error_log('VinaPet: Starting ERP update for user ' . $current_user_id);

            // Prepare ERP customer data
            $erp_customer_data = array(
                'customer_name' => $display_name,
                'email' => $user_email,
                'name' => $user_email, // Key để update
                'phone' => $user_phone,
                'address' => $user_address,
                'company_name' => ''
            );
            
            $erp_result = $erp_api->update_customer_vinapet($erp_customer_data);
            
            if (!$erp_result || $erp_result['status'] !== 'success') {
                throw new Exception('ERP update failed: ' . ($erp_result['message'] ?? 'Unknown error'));
            }

            // Update ERP sync timestamp
            update_user_meta($current_user_id, 'erpnext_last_sync', current_time('mysql'));

            error_log('VinaPet: ERP update successful for user ' . $current_user_id);

            // ============================================================================
            // SUCCESS - CẢ WORDPRESS VÀ ERP ĐỀU THÀNH CÔNG
            // ============================================================================

            wp_send_json_success('Cập nhật thông tin thành công! Đã đồng bộ với hệ thống ERP.');

        } catch (Exception $e) {
            error_log('VinaPet: ERP update failed for user ' . $current_user_id . ': ' . $e->getMessage());

            // ============================================================================
            // ROLLBACK WORDPRESS DATA
            // ============================================================================
            
            error_log('VinaPet: Starting rollback for user ' . $current_user_id);
            
            try {
                // Rollback user data
                $rollback_user_data = array(
                    'ID' => $current_user_id,
                    'display_name' => $backup_data['display_name'],
                    'user_email' => $backup_data['user_email']
                );

                $rollback_result = wp_update_user($rollback_user_data);
                
                if (is_wp_error($rollback_result)) {
                    throw new Exception('Rollback user data failed: ' . $rollback_result->get_error_message());
                }

                // Rollback user meta
                update_user_meta($current_user_id, 'phone', $backup_data['phone']);
                update_user_meta($current_user_id, 'user_address', $backup_data['user_address']);
                
                // Restore last sync time
                if ($backup_data['last_sync']) {
                    update_user_meta($current_user_id, 'erpnext_last_sync', $backup_data['last_sync']);
                } else {
                    delete_user_meta($current_user_id, 'erpnext_last_sync');
                }

                error_log('VinaPet: Rollback successful for user ' . $current_user_id);

                wp_send_json_error('Cập nhật ERP thất bại. Đã khôi phục thông tin ban đầu. Chi tiết: ' . $e->getMessage());

            } catch (Exception $rollback_error) {
                // Critical error - không thể rollback
                error_log('VinaPet: CRITICAL - Rollback failed for user ' . $current_user_id . ': ' . $rollback_error->getMessage());
                error_log('VinaPet: Backup data: ' . print_r($backup_data, true));

                wp_send_json_error('Cập nhật ERP thất bại và không thể khôi phục dữ liệu. Vui lòng liên hệ admin ngay!');
            }
        }
    }

    /**
     * Helper method để validate dữ liệu trước khi update
     */
    private function validate_update_data($data) {
        $errors = array();

        if (empty($data['display_name']) || strlen($data['display_name']) < 2) {
            $errors[] = 'Họ và tên phải có ít nhất 2 ký tự';
        }

        if (!is_email($data['user_email'])) {
            $errors[] = 'Email không hợp lệ';
        }

        return $errors;
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

    /**
     * Load user orders
     */
    public function load_user_orders()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn cần đăng nhập để xem đơn hàng!'
            )));
        }

        $user_id = get_current_user_id();
        $order_type = sanitize_text_field($_POST['order_type'] ?? 'creating_request');

        // Include sample orders data
        if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-orders.php')) {
            require_once VINAPET_THEME_DIR . '/includes/data/sample-orders.php';
        }

        $orders = array();

        switch ($order_type) {
            case 'creating_request':
                $orders = function_exists('vinapet_get_user_creating_orders')
                    ? vinapet_get_user_creating_orders($user_id)
                    : array();
                break;
            // Add other order types later
            default:
                $orders = array();
        }

        wp_die(json_encode(array(
            'success' => true,
            'data' => $orders
        )));
    }

    /**
     * Cancel user order
     */
    public function cancel_user_order()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn cần đăng nhập để thực hiện chức năng này!'
            )));
        }

        $user_id = get_current_user_id();
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');

        if (empty($order_id)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'ID đơn hàng không hợp lệ!'
            )));
        }

        // Include sample orders data
        if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-orders.php')) {
            require_once VINAPET_THEME_DIR . '/includes/data/sample-orders.php';
        }

        // Check if user can manage this order
        if (
            function_exists('vinapet_user_can_manage_order') &&
            !vinapet_user_can_manage_order($order_id, $user_id)
        ) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn không có quyền hủy đơn hàng này!'
            )));
        }

        // Cancel the order
        $result = function_exists('vinapet_cancel_user_order')
            ? vinapet_cancel_user_order($order_id, $user_id)
            : new WP_Error('function_not_found', 'Chức năng chưa được tích hợp');

        if (is_wp_error($result)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => $result->get_error_message()
            )));
        }

        wp_die(json_encode(array(
            'success' => true,
            'data' => 'Đơn hàng đã được hủy thành công!'
        )));
    }

    /**
     * Continue user order (redirect to checkout)
     */
    public function continue_user_order()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Bạn cần đăng nhập để thực hiện chức năng này!'
            )));
        }

        $user_id = get_current_user_id();
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');

        if (empty($order_id)) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'ID đơn hàng không hợp lệ!'
            )));
        }

        // Include sample orders data
        if (file_exists(VINAPET_THEME_DIR . '/includes/data/sample-orders.php')) {
            require_once VINAPET_THEME_DIR . '/includes/data/sample-orders.php';
        }

        // Get order data
        $order = function_exists('vinapet_get_user_order_by_id')
            ? vinapet_get_user_order_by_id($order_id, $user_id)
            : false;

        if (!$order) {
            wp_die(json_encode(array(
                'success' => false,
                'data' => 'Không tìm thấy đơn hàng!'
            )));
        }

        // Generate checkout URL with order data
        $checkout_url = home_url('/checkout/') . '?continue_order=' . $order_id;

        wp_die(json_encode(array(
            'success' => true,
            'data' => array(
                'message' => 'Chuyển hướng đến trang thanh toán...',
                'redirect_url' => $checkout_url
            )
        )));
    }
}

// Initialize the AJAX handler
new VinaPet_Account_Ajax();
