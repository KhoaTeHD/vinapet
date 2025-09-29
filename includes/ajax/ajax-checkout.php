<?php
/**
 * File: includes/ajax/ajax-checkout.php
 * VinaPet Checkout AJAX Handler - CLEAN & MAINTAINABLE VERSION
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Checkout_Ajax {

    // ============================================================================
    // CONFIGURATION - TẬP TRUNG CẤU HÌNH
    // ============================================================================

    const NONCE_ACTION = 'vinapet_nonce';
    
    const RESPONSE_CODES = [
        'SUCCESS' => 'SUCCESS',
        'SECURITY_ERROR' => 'SECURITY_ERROR',
        'AUTH_REQUIRED' => 'AUTH_REQUIRED',
        'INVALID_INPUT' => 'INVALID_INPUT',
        'NO_ORDER_DATA' => 'NO_ORDER_DATA',
        'VALIDATION_FAILED' => 'VALIDATION_FAILED',
        'API_ERROR' => 'API_ERROR',
        'SYSTEM_ERROR' => 'SYSTEM_ERROR'
    ];

    const MESSAGES = [
        'success' => 'Yêu cầu báo giá đã được gửi thành công!',
        'security_error' => 'Phiên làm việc không hợp lệ!',
        'auth_required' => 'Vui lòng đăng nhập để gửi yêu cầu',
        'invalid_input' => 'Dữ liệu không hợp lệ',
        'no_order_data' => 'Không tìm thấy dữ liệu đơn hàng. Vui lòng thử lại từ bước đầu.',
        'validation_failed' => 'Dữ liệu báo giá không hợp lệ',
        'api_error' => 'Có lỗi xảy ra khi tạo báo giá',
        'system_error' => 'Có lỗi hệ thống xảy ra'
    ];

    // ============================================================================
    // CONSTRUCTOR & INIT
    // ============================================================================

    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vinapet_submit_checkout_with_erp', [$this, 'handle_checkout_submission']);
        add_action('wp_ajax_nopriv_vinapet_submit_checkout_with_erp', [$this, 'handle_checkout_submission']);
        
        // Additional checkout related AJAX actions
        add_action('wp_ajax_vinapet_validate_checkout_data', [$this, 'validate_checkout_data']);
        add_action('wp_ajax_nopriv_vinapet_validate_checkout_data', [$this, 'validate_checkout_data']);
    }

    // ============================================================================
    // MAIN CHECKOUT HANDLER
    // ============================================================================

    /**
     * Main checkout submission handler
     */
    public function handle_checkout_submission() {
        try {
            // 1. Security validation
            $this->validate_security();

            // 2. Authentication check
            $this->validate_authentication();

            // 3. Process and validate form data
            $checkout_form = $this->process_form_data();
            
            // 4. Get session order data
            $order_data = $this->get_session_order_data();

            // 5. Prepare quotation data
            $quotation_data = $this->prepare_quotation_data($order_data, $checkout_form);

            // 6. Validate quotation data
            $this->validate_quotation_data($quotation_data);

            // 7. Submit to ERP API
            $api_response = $this->submit_to_erp_api($quotation_data);

            // 8. Handle success response
            $this->handle_success($quotation_data, $api_response, $order_data);

        } catch (VinaPet_Checkout_Exception $e) {
            $this->handle_error($e);
        } catch (Exception $e) {
            $this->handle_system_error($e);
        }
    }

    // ============================================================================
    // VALIDATION METHODS
    // ============================================================================

    /**
     * Validate security (nonce)
     */
    private function validate_security() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', self::NONCE_ACTION)) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['security_error'], 
                self::RESPONSE_CODES['SECURITY_ERROR']
            );
        }
    }

    /**
     * Validate authentication
     */
    private function validate_authentication() {
        if (!is_user_logged_in()) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['auth_required'], 
                self::RESPONSE_CODES['AUTH_REQUIRED']
            );
        }
    }

    /**
     * Process and validate form data
     */
    private function process_form_data() {
        $checkout_form = isset($_POST['checkout_form']) ? 
            json_decode(stripslashes($_POST['checkout_form']), true) : [];
            
        if (empty($checkout_form)) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['invalid_input'], 
                self::RESPONSE_CODES['INVALID_INPUT']
            );
        }

        return $this->sanitize_checkout_form($checkout_form);
    }

    /**
     * Get session order data - CẢI THIỆN: Ưu tiên data từ form trước
     */
    private function get_session_order_data() {
        // Thử lấy order_data từ form trước (JavaScript gửi lên)
        $form_data = isset($_POST['checkout_form']) ? 
            json_decode(stripslashes($_POST['checkout_form']), true) : [];
        
        if (!empty($form_data['order_data'])) {
            error_log('VinaPet Debug: Using order_data from JavaScript form');
            return $form_data['order_data'];
        }
        
        // Fallback: Lấy từ session như cũ
        if (class_exists('VinaPet_Order_Session')) {
            $session = VinaPet_Order_Session::get_instance();
            $order_data = $session->get_checkout_data();
            
            if ($order_data) {
                error_log('VinaPet Debug: Using order_data from PHP session');
                return $order_data;
            }
        }
        
        // Nếu không có dữ liệu nào
        throw new VinaPet_Checkout_Exception(
            self::MESSAGES['no_order_data'], 
            self::RESPONSE_CODES['NO_ORDER_DATA']
        );
    }

    /**
     * Validate quotation data
     */
    private function validate_quotation_data($quotation_data) {
        if (!VinaPet_Quotation_Helper::validate_quotation_data($quotation_data)) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['validation_failed'], 
                self::RESPONSE_CODES['VALIDATION_FAILED']
            );
        }
    }

    // ============================================================================
    // DATA PROCESSING
    // ============================================================================

    /**
     * Sanitize checkout form data
     */
    private function sanitize_checkout_form($form_data) {
        return [
            'packaging_design' => sanitize_text_field($form_data['packaging_design'] ?? ''),
            'delivery_timeline' => sanitize_text_field($form_data['delivery_timeline'] ?? ''),
            'shipping_method' => sanitize_text_field($form_data['shipping_method'] ?? ''),
            'shipping_cost' => intval($form_data['shipping_cost'] ?? 50000),
            'desired_delivery_time_amount' => intval($form_data['desired_delivery_time_amount'] ?? 30000),
            'date_to_receive' => intval($form_data['date_to_receive'] ?? 15),
            'ship_method' => sanitize_text_field($form_data['ship_method'] ?? ''),
            'contact_info' => [
                'notes' => sanitize_textarea_field($form_data['contact_info']['notes'] ?? ''),
                'phone' => sanitize_text_field($form_data['contact_info']['phone'] ?? ''),
                'email' => sanitize_email($form_data['contact_info']['email'] ?? '')
            ]
        ];
    }

    /**
     * Prepare quotation data for ERP API
     */
    private function prepare_quotation_data($order_data, $checkout_form) {
        $current_user = wp_get_current_user();
        
        // Base quotation structure
        $quotation_data = [
            'customer' => $current_user->user_email,
            'shipping_cost' => $checkout_form['shipping_cost'],
            'desired_delivery_time_amount' => $checkout_form['desired_delivery_time_amount'],
            'delivery_method' => $checkout_form['shipping_method'],
            'date_to_receive' => $checkout_form['date_to_receive'],
            'items' => $order_data
        ];

        // Add ship_method if provided
        if (!empty($checkout_form['ship_method'])) {
            $quotation_data['ship_method'] = $checkout_form['ship_method'];
        }

        // Process items based on order type
        if ($order_data['type'] === 'mix') {
            $quotation_data['items'] = $this->prepare_mix_items($order_data);
        } else {
            $quotation_data['items'] = $this->prepare_normal_items($order_data);
        }

        // Sanitize final data
        return VinaPet_Quotation_Helper::sanitize_quotation_data($quotation_data);
    }

    /**
     * Prepare items for mix order
     */
    private function prepare_mix_items($order_data) {
        $items = [];
        
        if (empty($order_data['products'])) {
            return $items;
        }

        foreach ($order_data['products'] as $product) {
            if (empty($product['code']) || empty($product['percentage'])) {
                continue;
            }

            $items[] = [
                'item_code' => $product['code'],
                'packet_item' => $product['packet_item'] ?? 'SPTUI01',
                'mix_percent' => floatval($product['percentage']),
                'qty' => intval($order_data['quantity_kg'] ?? 1000),
                'uom' => 'Nos',
                'rate' => intval($product['price'] ?? 25000),
                'is_free_item' => 0,
                'additional_notes' => $this->generate_mix_notes($order_data['products'])
            ];
        }

        return $items;
    }

    /**
     * Prepare items for normal order
     */
    private function prepare_normal_items($order_data) {
        $items = [];
        
        if (empty($order_data['products'])) {
            return $items;
        }

        foreach ($order_data['products'] as $product) {
            if (empty($product['code'])) {
                continue;
            }

            $items[] = [
                'item_code' => $product['code'],
                'item_name' => $product['name'] ?? $product['code'],
                'qty' => intval($product['quantity'] ?? 1000),
                'uom' => 'Nos',
                'rate' => intval($product['price'] ?? 50000),
                'packet_item' => $product['packet_item'] ?? 'SPTUI01',
                'mix_percent' => 0.0,
                'additional_notes' => $product['notes'] ?? ''
            ];
        }

        return $items;
    }

    /**
     * Generate mix notes
     */
    private function generate_mix_notes($products) {
        $product_names = array_filter(array_column($products, 'code'));
        return 'Mix ' . implode(' + ', $product_names);
    }

    // ============================================================================
    // ERP API INTEGRATION
    // ============================================================================

    /**
     * Submit to ERP API
     */
    private function submit_to_erp_api($quotation_data) {
        // Log request
        $log_data = VinaPet_Quotation_Helper::format_for_log($quotation_data);
        error_log('VinaPet Quotation Request: ' . json_encode($log_data));

        // Get ERP client
        $erp_api = new ERP_API_Client();
        
        if (!$erp_api->is_configured()) {
            throw new VinaPet_Checkout_Exception(
                'ERPNext chưa được cấu hình', 
                self::RESPONSE_CODES['API_ERROR']
            );
        }

        // Make API call
        $api_response = $erp_api->create_quotation($quotation_data);

        if (!$api_response || $api_response['status'] !== 'success') {
            $error_message = $api_response['message'] ?? self::MESSAGES['api_error'];
            error_log('VinaPet Quotation API Error: ' . json_encode($api_response));
            
            throw new VinaPet_Checkout_Exception(
                $error_message, 
                self::RESPONSE_CODES['API_ERROR']
            );
        }

        return $api_response;
    }

    // ============================================================================
    // SUCCESS & ERROR HANDLING
    // ============================================================================

    /**
     * Handle successful submission
     */
    private function handle_success($quotation_data, $api_response, $order_data) {
        // Generate summary
        $summary = VinaPet_Quotation_Helper::generate_quotation_summary($quotation_data);
        
        // Log success
        $success_log = VinaPet_Quotation_Helper::format_for_log($quotation_data, $api_response);
        error_log('VinaPet Quotation Success: ' . json_encode($success_log));

        // Clear session
        $session = VinaPet_Order_Session::get_instance();
        $session->clear_all();

        // Prepare response
        $response_data = [
            'message' => self::MESSAGES['success'],
            'quotation_id' => $api_response['data']['name'] ?? 'N/A',
            'order_type' => $order_data['type'] ?? 'normal',
            'redirect' => home_url('/tai-khoan'),
            'summary' => $summary
        ];

        wp_send_json_success($response_data);
    }

    /**
     * Handle known errors
     */
    private function handle_error(VinaPet_Checkout_Exception $e) {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }

    /**
     * Handle system errors
     */
    private function handle_system_error(Exception $e) {
        error_log('VinaPet Checkout System Error: ' . $e->getMessage());

        wp_send_json_error([
            'message' => self::MESSAGES['system_error'],
            'code' => self::RESPONSE_CODES['SYSTEM_ERROR']
        ]);
    }

    // ============================================================================
    // ADDITIONAL AJAX METHODS
    // ============================================================================

    /**
     * Validate checkout data (for real-time validation)
     */
    public function validate_checkout_data() {
        try {
            $this->validate_security();
            
            $form_data = $this->process_form_data();
            
            wp_send_json_success([
                'message' => 'Dữ liệu hợp lệ',
                'data' => $form_data
            ]);
            
        } catch (VinaPet_Checkout_Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}

/**
 * Custom exception class for checkout errors
 */
class VinaPet_Checkout_Exception extends Exception {
    private $error_code;
    
    public function __construct($message, $error_code = 'GENERAL_ERROR', $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
    }
    
    public function getErrorCode() {
        return $this->error_code;
    }
}

// Initialize the class
new VinaPet_Checkout_Ajax();