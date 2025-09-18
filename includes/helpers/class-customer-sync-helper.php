<?php
/**
 * Customer Sync Helper
 * Quản lý đồng bộ customer giữa WordPress và ERPNext
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Customer_Sync_Helper {
    
    private $erp_api;
    private $sync_interval = 6 * HOUR_IN_SECONDS; // 6 tiếng
    
    public function __construct() {
        // Load ERP API client có sẵn
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $this->erp_api = new ERP_API_Client();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Scheduled sync
        add_action('vinapet_sync_customers_cron', array($this, 'scheduled_sync_customers'));
        
        // AJAX handlers
        add_action('wp_ajax_vinapet_manual_sync_customers', array($this, 'handle_manual_sync'));
        add_action('wp_ajax_vinapet_get_customer_from_erp', array($this, 'handle_get_customer_from_erp'));
        
        // User registration hooks
        add_action('user_register', array($this, 'sync_new_user_to_erp'), 20, 1);
        
        // Schedule cron job
        if (!wp_next_scheduled('vinapet_sync_customers_cron')) {
            wp_schedule_event(time(), 'vinapet_six_hours', 'vinapet_sync_customers_cron');
        }
        
        // Add custom cron interval
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['vinapet_six_hours'] = array(
            'interval' => $this->sync_interval,
            'display' => __('Mỗi 6 tiếng', 'vinapet')
        );
        
        return $schedules;
    }
    
    // ============================================================================
    // SCHEDULED SYNC METHODS
    // ============================================================================
    
    /**
     * Scheduled sync customers từ ERP
     */
    public function scheduled_sync_customers() {
        if (!$this->erp_api->is_configured()) {
            error_log('VinaPet: ERP API không được cấu hình, bỏ qua scheduled sync');
            return;
        }
        
        error_log('VinaPet: Bắt đầu scheduled sync customers từ ERP');
        
        $result = $this->sync_all_customers_from_erp();
        
        // Update last sync time
        update_option('vinapet_last_customer_sync', current_time('mysql'));
        
        // Log kết quả
        error_log('VinaPet: Scheduled sync hoàn thành - ' . $result['message']);
        
        // Gửi email thông báo nếu có lỗi
        if ($result['status'] === 'error' || (isset($result['errors']) && $result['errors'] > 0)) {
            $this->send_sync_error_notification($result);
        }
    }
    
    /**
     * Manual sync customers (từ admin)
     */
    public function handle_manual_sync() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền thực hiện');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!$this->erp_api->is_configured()) {
            wp_send_json_error('ERP API chưa được cấu hình');
        }
        
        $result = $this->sync_all_customers_from_erp();
        
        // Update last sync time
        update_option('vinapet_last_customer_sync', current_time('mysql'));
        
        if ($result['status'] === 'success') {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Sync all customers từ ERP về WordPress
     */
    private function sync_all_customers_from_erp() {
        if (!$this->erp_api->is_configured()) {
            return array('status' => 'error', 'message' => 'ERP chưa được cấu hình');
        }
        
        $customers_response = $this->erp_api->get_customers_list_vinapet();
        
        if (!$customers_response || $customers_response['status'] !== 'success') {
            return array('status' => 'error', 'message' => 'Không thể lấy danh sách customer từ ERP');
        }
        
        $synced_count = 0;
        $error_count = 0;
        
        foreach ($customers_response['customers'] as $customer) {
            try {
                $this->sync_customer_to_wordpress($customer);
                $synced_count++;
            } catch (Exception $e) {
                $error_count++;
                error_log("VinaPet: Lỗi sync customer {$customer['email']}: " . $e->getMessage());
            }
        }
        
        return array(
            'status' => 'success',
            'synced' => $synced_count,
            'errors' => $error_count,
            'message' => "Đã sync {$synced_count} customers, {$error_count} lỗi"
        );
    }
    
    /**
     * Sync customer data to WordPress user
     */
    private function sync_customer_to_wordpress($customer_data) {
        if (empty($customer_data['custom_email'])) {
            return false;
        }
        
        $email = $customer_data['custom_email'];
        $user = get_user_by('email', $email);
        
        // Nếu user chưa tồn tại, tạo mới
        if (!$user) {
            $user_data = array(
                'user_login' => $email,
                'user_email' => $email,
                'user_pass' => wp_generate_password(),
                'display_name' => $customer_data['customer_name'],
                'role' => 'customer'
            );
            
            $user_id = wp_insert_user($user_data);
            
            if (is_wp_error($user_id)) {
                throw new Exception("Không thể tạo user: " . $user_id->get_error_message());
            }
        } else {
            $user_id = $user->ID;
        }
        
        // Update user meta với thông tin từ ERP
        update_user_meta($user_id, 'erpnext_customer_id', $customer_data['name']);
        update_user_meta($user_id, 'phone_number', $customer_data['custom_phone'] ?? '');
        update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));
        
        // Update address nếu có
        if (!empty($customer_data['address'])) {
            $address_string = $this->format_address_string($customer_data['address']);
            update_user_meta($user_id, 'user_address', $address_string);
        }
        
        return true;
    }
    
    // ============================================================================
    // INDIVIDUAL CUSTOMER SYNC
    // ============================================================================
    
    /**
     * Get customer from ERP by email (for account page)
     */
    public function handle_get_customer_from_erp() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'vinapet_auth_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $user = wp_get_current_user();
        $email = $user->user_email;
        
        if (!$this->erp_api->is_configured()) {
            wp_send_json_error('ERP API chưa được cấu hình');
        }
        
        $customer_data = $this->erp_api->get_customer_by_email($email);
        
        if ($customer_data && $customer_data['status'] === 'success') {
            // Update WordPress user với data từ ERP
            $sync_result = $this->erp_api->sync_erp_customer_to_wp($user->ID, $email);
            
            wp_send_json_success(array(
                'message' => 'Thông tin đã được cập nhật từ ERP',
                'customer_data' => $customer_data,
                'sync_result' => $sync_result
            ));
        } else {
            wp_send_json_error('Không tìm thấy thông tin khách hàng trong ERP');
        }
    }
    
    /**
     * Sync new WordPress user to ERP
     */
    public function sync_new_user_to_erp($user_id) {
        if (!$this->erp_api->is_configured()) {
            return;
        }
        
        $result = $this->erp_api->sync_wp_user_to_erp($user_id);
        
        if ($result) {
            error_log("VinaPet: User {$user_id} sync result: " . $result['message']);
        } else {
            error_log("VinaPet: Failed to sync user {$user_id} to ERP");
        }
    }
    
    // ============================================================================
    // STATIC HELPER METHODS
    // ============================================================================
    
    /**
     * Get customer info for current user
     */
    public static function get_current_user_customer_info() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $email = $user->user_email;
        
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $erp_api = new ERP_API_Client();
        
        if (!$erp_api->is_configured()) {
            return false;
        }
        
        return $erp_api->get_customer_by_email($email);
    }
    
    /**
     * Sync current user with ERP (manual call từ account page)
     */
    public static function sync_current_user_with_erp() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $erp_api = new ERP_API_Client();
        
        if (!$erp_api->is_configured()) {
            return false;
        }
        
        // Sync từ ERP về WordPress
        $sync_result = $erp_api->sync_erp_customer_to_wp($user->ID, $user->user_email);
        
        return $sync_result;
    }
    
    /**
     * Create customer in ERP for new WordPress user
     */
    public static function create_erp_customer_for_user($user_id, $additional_data = array()) {
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $erp_api = new ERP_API_Client();
        
        if (!$erp_api->is_configured()) {
            return false;
        }
        
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }
        
        // Prepare customer data
        $customer_data = array(
            'customer_name' => $user->display_name ?: $user->user_login,
            'email' => $user->user_email,
            'phone' => get_user_meta($user_id, 'phone_number', true) ?: '',
            'address' => get_user_meta($user_id, 'user_address', true) ?: '',
            'company_name' => ''
        );
        
        // Merge additional data từ registration form
        if (!empty($additional_data)) {
            $customer_data = array_merge($customer_data, $additional_data);
        }
        
        $result = $erp_api->create_customer_vinapet($customer_data);
        
        if ($result && $result['status'] === 'success') {
            // Save ERP customer ID to WordPress user
            update_user_meta($user_id, 'erpnext_customer_id', $result['name']);
            update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));
            
            return $result;
        }
        
        return false;
    }
    
    // ============================================================================
    // UTILITY METHODS
    // ============================================================================
    
    /**
     * Format address từ ERP data
     */
    private function format_address_string($address_data) {
        if (is_string($address_data)) {
            return $address_data;
        }
        
        $parts = array();
        
        if (!empty($address_data['address_line1'])) {
            $parts[] = $address_data['address_line1'];
        }
        
        if (!empty($address_data['city']) && $address_data['city'] !== 'Unknow') {
            $parts[] = $address_data['city'];
        }
        
        if (!empty($address_data['country'])) {
            $parts[] = $address_data['country'];
        }
        
        return implode(', ', $parts);
    }
    
    /**
     * Send sync error notification to admin
     */
    private function send_sync_error_notification($result) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Lỗi đồng bộ ERP Customer";
        
        $message = "Có lỗi xảy ra khi đồng bộ customer từ ERP:\n\n";
        $message .= "Kết quả: {$result['message']}\n";
        $message .= "Thời gian: " . current_time('d/m/Y H:i:s') . "\n\n";
        $message .= "Vui lòng kiểm tra log để biết thêm chi tiết.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get sync status information
     */
    public static function get_sync_status() {
        $last_sync = get_option('vinapet_last_customer_sync');
        $next_sync = wp_next_scheduled('vinapet_sync_customers_cron');
        
        return array(
            'is_configured' => self::is_erp_configured(),
            'last_sync' => $last_sync,
            'last_sync_formatted' => $last_sync ? date('d/m/Y H:i:s', strtotime($last_sync)) : 'Chưa có',
            'next_sync' => $next_sync,
            'next_sync_formatted' => $next_sync ? date('d/m/Y H:i:s', $next_sync) : 'Chưa lên lịch',
            'sync_interval_hours' => 6
        );
    }
    
    /**
     * Check if ERP is configured
     */
    public static function is_erp_configured() {
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }
        
        $erp_api = new ERP_API_Client();
        return $erp_api->is_configured();
    }
    
    /**
     * Force manual sync (for testing)
     */
    public function force_sync_now() {
        return $this->scheduled_sync_customers();
    }
    
    /**
     * Clear scheduled sync (for deactivation)
     */
    public function clear_scheduled_sync() {
        $timestamp = wp_next_scheduled('vinapet_sync_customers_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vinapet_sync_customers_cron');
        }
    }
}

// Initialize the sync helper
function vinapet_init_customer_sync_helper() {
    if (class_exists('VinaPet_Customer_Sync_Helper')) {
        new VinaPet_Customer_Sync_Helper();
    }
}
add_action('init', 'vinapet_init_customer_sync_helper', 15);