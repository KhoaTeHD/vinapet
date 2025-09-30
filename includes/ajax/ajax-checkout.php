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

class VinaPet_Checkout_Ajax
{

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

    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks()
    {
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
     * ✅ CẢI THIỆN: Không cần lấy order_data từ session nữa
     */
    public function handle_checkout_submission()
    {
        try {
            // 1. Security validation
            $this->validate_security();

            // 2. Authentication check
            $this->validate_authentication();

            // 3. Process and validate form data (đã chứa items)
            $checkout_form = $this->process_form_data();

            // ✅ THAY ĐỔI: Không cần get_session_order_data() nữa
            // Vì checkout_form đã chứa items array và order_type

            // 4. Prepare quotation data (sử dụng trực tiếp checkout_form)
            $quotation_data = $this->prepare_quotation_data_v2($checkout_form);

            // 5. Validate quotation data
            $this->validate_quotation_data($quotation_data);

            // 6. Submit to ERP API
            $api_response = $this->submit_to_erp_api($quotation_data);

            // 7. Handle success response
            $this->handle_success_v2($quotation_data, $api_response, $checkout_form);
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
    private function validate_security()
    {
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
    private function validate_authentication()
    {
        if (!is_user_logged_in()) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['auth_required'],
                self::RESPONSE_CODES['AUTH_REQUIRED']
            );
        }
    }

    /**
     * Process and validate form data
     * ✅ CẢI THIỆN: Log chi tiết để debug
     */
    private function process_form_data()
    {
        // Lấy raw POST data
        $raw_checkout_form = $_POST['checkout_form'] ?? '';

        // Log raw data để debug
        error_log('VinaPet Checkout - Raw POST checkout_form: ' . substr($raw_checkout_form, 0, 500));

        // Decode JSON
        $checkout_form = !empty($raw_checkout_form) ?
            json_decode(stripslashes($raw_checkout_form), true) : [];

        // Kiểm tra JSON decode có lỗi không
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('VinaPet Checkout - JSON decode error: ' . json_last_error_msg());
            throw new VinaPet_Checkout_Exception(
                'Dữ liệu không đúng định dạng JSON',
                self::RESPONSE_CODES['INVALID_INPUT']
            );
        }

        if (empty($checkout_form)) {
            error_log('VinaPet Checkout - Empty checkout_form after decode');
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['invalid_input'],
                self::RESPONSE_CODES['INVALID_INPUT']
            );
        }

        // Log để debug
        error_log('VinaPet Checkout - Decoded checkout_form keys: ' . implode(', ', array_keys($checkout_form)));
        error_log('VinaPet Checkout - Has items: ' . (isset($checkout_form['items']) ? 'YES (' . count($checkout_form['items']) . ')' : 'NO'));

        return $this->sanitize_checkout_form($checkout_form);
    }

    /**
     * Validate quotation data
     */
    private function validate_quotation_data($quotation_data)
    {
        if (!VinaPet_Quotation_Helper::validate_quotation_data($quotation_data)) {
            throw new VinaPet_Checkout_Exception(
                self::MESSAGES['validation_failed'],
                self::RESPONSE_CODES['VALIDATION_FAILED']
            );
        }
    }

    /**
     * Sanitize checkout form data
     * ✅ CẢI THIỆN: Thêm customer và items
     */
    private function sanitize_checkout_form($form_data)
    {
        $sanitized = [
            // ✅ THÊM: Customer email
            'customer' => sanitize_email($form_data['customer'] ?? ''),

            // ✅ THÊM: Items array (đã format từ JavaScript)
            'items' => [], // Sẽ sanitize riêng bên dưới

            // Form selections
            'packaging_design' => sanitize_text_field($form_data['packaging_design'] ?? ''),
            'delivery_timeline' => sanitize_text_field($form_data['delivery_timeline'] ?? ''),
            'shipping_method' => sanitize_text_field($form_data['shipping_method'] ?? ''),

            // Pricing
            'shipping_cost' => intval($form_data['shipping_cost'] ?? 50000),
            'desired_delivery_time_amount' => intval($form_data['desired_delivery_time_amount'] ?? 30000),
            'date_to_receive' => intval($form_data['date_to_receive'] ?? 15),

            // Delivery method
            'delivery_method' => sanitize_text_field($form_data['delivery_method'] ?? ''),
            'ship_method' => sanitize_text_field($form_data['ship_method'] ?? ''),

            // Contact info
            'contact_info' => [
                'notes' => sanitize_textarea_field($form_data['contact_info']['notes'] ?? ''),
                'phone' => sanitize_text_field($form_data['contact_info']['phone'] ?? ''),
                'email' => sanitize_email($form_data['contact_info']['email'] ?? '')
            ],

            // ✅ THÊM: Metadata
            'order_type' => sanitize_text_field($form_data['order_type'] ?? 'normal')
        ];

        // ✅ Sanitize items array
        if (!empty($form_data['items']) && is_array($form_data['items'])) {
            foreach ($form_data['items'] as $item) {
                $sanitized_item = [
                    'item_code' => sanitize_text_field($item['item_code'] ?? ''),
                    'qty' => intval($item['qty'] ?? 0),
                    'uom' => sanitize_text_field($item['uom'] ?? 'Nos'),
                    'rate' => intval($item['rate'] ?? 0),
                    'is_free_item' => intval($item['is_free_item'] ?? 0),
                ];

                // Optional fields
                if (!empty($item['item_name'])) {
                    $sanitized_item['item_name'] = sanitize_text_field($item['item_name']);
                }
                if (!empty($item['packet_item'])) {
                    $sanitized_item['packet_item'] = sanitize_text_field($item['packet_item']);
                }
                if (isset($item['mix_percent'])) {
                    $sanitized_item['mix_percent'] = floatval($item['mix_percent']);
                }
                if (!empty($item['additional_notes'])) {
                    $sanitized_item['additional_notes'] = sanitize_textarea_field($item['additional_notes']);
                }

                $sanitized['items'][] = $sanitized_item;
            }
        }

        return $sanitized;
    }

    /**
     * Prepare quotation data for ERP API - VERSION 2
     * ✅ THAY ĐỔI: Nhận checkout_form đã chứa items array
     * 
     * @param array $checkout_form Form data đã bao gồm items
     * @return array Quotation data đã format
     */
    private function prepare_quotation_data_v2($checkout_form)
    {
        // Validate items array
        if (empty($checkout_form['items']) || !is_array($checkout_form['items'])) {
            error_log('VinaPet Checkout - Missing or invalid items array in checkout_form');
            throw new VinaPet_Checkout_Exception(
                'Không tìm thấy danh sách sản phẩm',
                self::RESPONSE_CODES['INVALID_INPUT']
            );
        }

        // Log items để debug
        error_log('VinaPet Checkout - Items count: ' . count($checkout_form['items']));
        error_log('VinaPet Checkout - First item: ' . json_encode($checkout_form['items'][0] ?? []));

        // Build quotation data theo format ERPNext
        $quotation_data = [
            'customer' => $checkout_form['customer'],
            'items' => $checkout_form['items'], // ✅ Sử dụng trực tiếp items đã format
            'shipping_cost' => intval($checkout_form['shipping_cost']),
            'desired_delivery_time_amount' => intval($checkout_form['desired_delivery_time_amount']),
            'delivery_method' => sanitize_text_field($checkout_form['delivery_method']),
            'date_to_receive' => intval($checkout_form['date_to_receive'])
        ];

        // Add ship_method if provided
        if (!empty($checkout_form['ship_method'])) {
            $quotation_data['ship_method'] = sanitize_text_field($checkout_form['ship_method']);
        }

        // Log final quotation data
        error_log('VinaPet Checkout - Final quotation_data: ' . json_encode($quotation_data));

        // Sanitize final data
        return VinaPet_Quotation_Helper::sanitize_quotation_data($quotation_data);
    }
    
    // ============================================================================
    // ERP API INTEGRATION
    // ============================================================================

    /**
     * Submit to ERP API
     */
    private function submit_to_erp_api($quotation_data)
    {
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
     * Handle successful submission - VERSION 2
     * ✅ THAY ĐỔI: Nhận checkout_form thay vì order_data
     */
    private function handle_success_v2($quotation_data, $api_response, $checkout_form)
    {
        // Generate summary
        $summary = VinaPet_Quotation_Helper::generate_quotation_summary($quotation_data);

        // Log success với đầy đủ thông tin
        $success_log = [
            'timestamp' => current_time('mysql'),
            'customer' => $checkout_form['customer'],
            'order_type' => $checkout_form['order_type'],
            'items_count' => count($checkout_form['items']),
            'quotation_id' => $api_response['data']['name'] ?? 'N/A',
            'api_response' => $api_response
        ];
        error_log('VinaPet Quotation Success: ' . json_encode($success_log));

        // Clear session (nếu có sử dụng)
        if (class_exists('VinaPet_Order_Session')) {
            $session = VinaPet_Order_Session::get_instance();
            $session->clear_all();
        }

        // Prepare response
        $response_data = [
            'message' => self::MESSAGES['success'],
            'quotation_id' => $api_response['data']['name'] ?? 'N/A',
            'order_type' => $checkout_form['order_type'] ?? 'normal',
            'redirect' => home_url('/tai-khoan'),
            'summary' => $summary
        ];

        wp_send_json_success($response_data);
    }

    /**
     * Handle known errors
     */
    private function handle_error(VinaPet_Checkout_Exception $e)
    {
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }

    /**
     * Handle system errors
     */
    private function handle_system_error(Exception $e)
    {
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
    public function validate_checkout_data()
    {
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
class VinaPet_Checkout_Exception extends Exception
{
    private $error_code;

    public function __construct($message, $error_code = 'GENERAL_ERROR', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
    }

    public function getErrorCode()
    {
        return $this->error_code;
    }
}

// Initialize the class
new VinaPet_Checkout_Ajax();
