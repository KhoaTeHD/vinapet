<?php

/**
 * File: includes/ajax/ajax-lead.php
 * VinaPet Lead Form AJAX Handler - CLEAN & MAINTAINABLE VERSION
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Lead_Ajax
{

    // ============================================================================
    // CONFIGURATION - TẬP TRUNG CẤU HÌNH
    // ============================================================================

    const NONCE_ACTION = 'vinapet_lead_form_nonce';

    const RATE_LIMIT = [
        'max_attempts' => 7,
        'time_window' => 15 * MINUTE_IN_SECONDS, // 15 phút
        'transient_prefix' => 'vinapet_lead_rate_limit_'
    ];

    const VALIDATION_RULES = [
        'contact_name' => ['min' => 2, 'max' => 100],
        'needs' => ['min' => 10, 'max' => 1000],
        'address' => ['min' => 0, 'max' => 500]
    ];

    const MESSAGES = [
        'success' => 'Gửi thông tin thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.',
        'error' => 'Có lỗi xảy ra, vui lòng thử lại sau.',
        'loading' => 'Đang gửi thông tin...',
        'required' => 'Vui lòng điền đầy đủ thông tin bắt buộc.',
        'invalid_email' => 'Địa chỉ email không hợp lệ.',
        'invalid_phone' => 'Số điện thoại không hợp lệ.',
        'rate_limit' => 'Bạn đã gửi quá nhiều yêu cầu. Vui lòng thử lại sau 15 phút.',
        'nonce_failed' => 'Phiên làm việc không hợp lệ!',
        'system_error' => 'Có lỗi hệ thống xảy ra. Vui lòng thử lại sau.'
    ];

    public function __construct()
    {
        $this->init_hooks();
    }

    // ============================================================================
    // INITIALIZATION
    // ============================================================================

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks()
    {
        // AJAX handlers (public - cho cả guest và logged-in user)
        add_action('wp_ajax_nopriv_create_lead_submission', array($this, 'handle_lead_submission'));
        add_action('wp_ajax_create_lead_submission', array($this, 'handle_lead_submission'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_lead_scripts'));
    }

    /**
     * Enqueue lead form scripts với localization
     */
    public function enqueue_lead_scripts()
    {
        if (!$this->should_load_scripts()) {
            return;
        }

        wp_localize_script('jquery', 'vinapet_lead_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'messages' => self::MESSAGES
        ));
    }

    /**
     * Check if current page should load lead scripts
     */
    private function should_load_scripts()
    {
        global $post;

        if (is_admin()) {
            return false;
        }

        // Kiểm tra shortcode [form_lien_he] trong content
        if (is_a($post, 'WP_Post')) {
            if (has_shortcode($post->post_content, 'form_lien_he')) {
                return true;
            }
        }

        // Kiểm tra các trang cụ thể (fallback)
        if (is_page(array('lien-he', 'hop-tac', 'contact'))) {
            return true;
        }

        return false;
    }

    // ============================================================================
    // MAIN AJAX HANDLER
    // ============================================================================

    /**
     * Handle lead form submission - Main entry point
     */
    public function handle_lead_submission()
    {
        try {
            // 1. Security validation
            $this->validate_security();

            // 2. Rate limiting check
            $this->check_rate_limit();

            // 3. Sanitize và validate input
            $form_data = $this->process_form_data();

            // 4. Create lead via ERP API
            $api_response = $this->create_lead_via_api($form_data);

            // 5. Process success
            if($api_response['status'] === 'success'){
                $this->handle_success($form_data, $api_response);
            }
            else{
                $this->handle_error(new VinaPet_Lead_Exception('Yêu cầu bị trùng hoặc có lỗi xảy ra, vui lòng thử lại sau.', 'api'));
            }
            
        } catch (VinaPet_Lead_Exception $e) {
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
            throw new VinaPet_Lead_Exception(self::MESSAGES['nonce_failed'], 'security');
        }
    }

    /**
     * Check rate limiting
     */
    private function check_rate_limit()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = self::RATE_LIMIT['transient_prefix'] . md5($ip);
        $current_count = get_transient($transient_key) ?: 0;

        if ($current_count >= self::RATE_LIMIT['max_attempts']) {
            throw new VinaPet_Lead_Exception(self::MESSAGES['rate_limit'], 'rate_limit');
        }
    }

    /**
     * Process và validate form data
     */
    private function process_form_data()
    {
        $form_data = $this->sanitize_input();
        $this->validate_input($form_data);
        return $form_data;
    }

    // ============================================================================
    // DATA PROCESSING
    // ============================================================================

    /**
     * Sanitize form input
     */
    private function sanitize_input()
    {
        return [
            'contact_name' => sanitize_text_field($_POST['contact_name'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'needs' => sanitize_textarea_field($_POST['needs'] ?? '')
        ];
    }

    /**
     * Validate input data
     */
    private function validate_input($data)
    {
        // Required fields
        $required_fields = ['contact_name', 'email', 'phone', 'needs'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new VinaPet_Lead_Exception(self::MESSAGES['required'], 'validation');
            }
        }

        // Email validation
        if (!is_email($data['email'])) {
            throw new VinaPet_Lead_Exception(self::MESSAGES['invalid_email'], 'validation');
        }

        // Phone validation
        if (!$this->validate_vietnamese_phone($data['phone'])) {
            throw new VinaPet_Lead_Exception(self::MESSAGES['invalid_phone'], 'validation');
        }

        // Length validation
        $this->validate_field_lengths($data);
    }

    /**
     * Validate field lengths theo rules
     */
    private function validate_field_lengths($data)
    {
        foreach (self::VALIDATION_RULES as $field => $rules) {
            if (!isset($data[$field])) continue;

            $length = strlen($data[$field]);
            $min = $rules['min'];
            $max = $rules['max'];

            if ($length < $min || $length > $max) {
                throw new VinaPet_Lead_Exception(
                    "Trường {$field} phải từ {$min}-{$max} ký tự.",
                    'validation'
                );
            }
        }
    }

    /**
     * Validate Vietnamese phone number
     */
    private function validate_vietnamese_phone($phone)
    {
        $cleaned_phone = preg_replace('/[\s\-\.]/', '', $phone);

        $patterns = [
            '/^(09|08|07|05|03)[0-9]{8}$/',     // 0xxxxxxxxx
            '/^\+84(9|8|7|5|3)[0-9]{8}$/',     // +84xxxxxxxxx  
            '/^84(9|8|7|5|3)[0-9]{8}$/'        // 84xxxxxxxxx
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleaned_phone)) {
                return true;
            }
        }

        return false;
    }

    // ============================================================================
    // API INTEGRATION
    // ============================================================================

    /**
     * Create lead via ERP API
     */
    private function create_lead_via_api($form_data)
    {
        $erp_client = $this->get_erp_client();
        $erpnext_data = $this->map_to_erpnext_format($form_data);

        $result = $erp_client->create_lead($erpnext_data);

        if ($result === false) {
            throw new VinaPet_Lead_Exception('Không thể tạo lead trong ERPNext.', 'api');
        }

        return $result;
    }

    /**
     * Get ERP API Client instance
     */
    private function get_erp_client()
    {
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }

        $erp_client = new ERP_API_Client();

        if (!$erp_client->is_configured()) {
            throw new VinaPet_Lead_Exception('ERPNext chưa được cấu hình.', 'config');
        }

        return $erp_client;
    }

    /**
     * Map form data to ERPNext format
     */
    private function map_to_erpnext_format($form_data)
    {
        return [
            'company_name' => 'Cá nhân', // Default value
            'contact_name' => $form_data['contact_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'address' => $form_data['address'] ?: '',
            'needs' => $form_data['needs']
        ];
    }

    // ============================================================================
    // SUCCESS & ERROR HANDLING
    // ============================================================================

    /**
     * Handle successful submission
     */
    private function handle_success($form_data, $api_response)
    {
        // Log success
        $this->log_submission($form_data, $api_response, 'success');

        // Update rate limit
        $this->update_rate_limit_counter();

        // Save to database (optional)
        //$this->save_to_database($form_data, $api_response);

        // Return success response
        wp_send_json_success([
            'message' => self::MESSAGES['success'],
            'lead_id' => $api_response ?? null
        ]);
    }

    /**
     * Handle known errors
     */
    private function handle_error(VinaPet_Lead_Exception $e)
    {
        $this->log_error($e);

        wp_send_json_error([
            'message' => $e->getMessage(),
            'type' => $e->getType()
        ]);
    }

    /**
     * Handle system errors
     */
    private function handle_system_error(Exception $e)
    {
        error_log('VinaPet Lead System Error: ' . $e->getMessage());

        wp_send_json_error([
            'message' => self::MESSAGES['system_error']
        ]);
    }

    // ============================================================================
    // RATE LIMITING
    // ============================================================================

    /**
     * Update rate limit counter
     */
    private function update_rate_limit_counter()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = self::RATE_LIMIT['transient_prefix'] . md5($ip);

        $current_count = get_transient($transient_key) ?: 0;
        $new_count = $current_count + 1;

        set_transient($transient_key, $new_count, self::RATE_LIMIT['time_window']);
    }

    // ============================================================================
    // LOGGING & STORAGE
    // ============================================================================

    /**
     * Log submission
     */
    private function log_submission($form_data, $api_response, $status)
    {
        $log_data = [
            'timestamp' => current_time('mysql'),
            'status' => $status,
            'contact_name' => $form_data['contact_name'],
            'email' => $form_data['email'],
            'phone' => $form_data['phone'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'api_response' => $api_response
        ];

        error_log('VinaPet Lead ' . ucfirst($status) . ': ' . json_encode($log_data));
    }

    /**
     * Log errors
     */
    private function log_error(VinaPet_Lead_Exception $e)
    {
        error_log(sprintf(
            'VinaPet Lead Error [%s]: %s - IP: %s',
            $e->getType(),
            $e->getMessage(),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ));
    }

    /**
     * Save submission to database (optional)
     */
    private function save_to_database($form_data, $api_response)
    {
        if (!$this->should_save_to_database()) {
            return;
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'vinapet_lead_submissions';
        $this->ensure_table_exists($table_name);

        $wpdb->insert(
            $table_name,
            [
                'contact_name' => $form_data['contact_name'],
                'email' => $form_data['email'],
                'phone' => $form_data['phone'],
                'address' => $form_data['address'],
                'needs' => $form_data['needs'],
                'erpnext_response' => json_encode($api_response),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Check if should save to database
     */
    private function should_save_to_database()
    {
        return apply_filters('vinapet_lead_save_to_database', true);
    }

    /**
     * Ensure database table exists
     */
    private function ensure_table_exists($table_name)
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            contact_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            address text,
            needs text NOT NULL,
            erpnext_response text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// ============================================================================
// CUSTOM EXCEPTION CLASS
// ============================================================================

/**
 * Custom exception cho Lead processing
 */
class VinaPet_Lead_Exception extends Exception
{
    private $type;

    public function __construct($message, $type = 'general', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }
}

// ============================================================================
// INITIALIZE CLASS
// ============================================================================

// Initialize the class
new VinaPet_Lead_Ajax();
