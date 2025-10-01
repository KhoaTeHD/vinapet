<?php

/**
 * ERP API Client - COMPLETE FINAL VERSION
 * File: includes/api/class-erp-api-client.php
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class ERP_API_Client
{
    private $api_url;
    private $api_key;
    private $api_secret;
    private $cache_time = 3600;

    // ============================================================================
    // ENDPOINTS CONFIGURATION - TẬP TRUNG TẤT CẢ ENDPOINTS TẠI ĐÂY
    // ============================================================================
    const ENDPOINTS = [
        // Product endpoints
        'products_list'         => 'method/vinapet.api.item.item.get_products',
        'product_detail'        => 'method/vinapet.api.item.item.get_item_detail',
        'categories'            => 'resource/Item Group',
        'item_price_detail'     => 'method/vinapet.api.item.item.get_item_price',

        // Lead endpoints  
        'create_lead'           => 'method/vinapet.api.lead.lead.create_lead',
        'get_leads'             => 'method/vinapet.api.lead.lead.get_leads',
        'update_lead'           => 'method/vinapet.api.lead.lead.update_lead',

        // Customer endpoints
        'create_customer'      => 'method/vinapet.api.customer.customer.create_customer',
        'get_customer_by_email' => 'method/vinapet.api.customer.customer.get_customer',
        'update_customer'       => 'method/vinapet.api.customer.customer.update_customer',

        // Order endpoints
        'create_order'          => 'method/vinapet.api.order.order.create_order',
        'get_orders'            => 'method/vinapet.api.order.order.get_orders',

        // Quotation endpoints
        'get_quotations'        => 'method/vinapet.vinapet.quotation.get_quotations',

        // Package endpoints
        'get_packages'        => 'method/vinapet.api.item.item.get_list_packets',

        // General endpoints
        'health_check'          => 'method/ping',
        'get_settings'          => 'method/vinapet.api.settings.get_settings',

        // File upload endpoints
        'upload_file'           => 'method/upload_file',
        'get_file'              => 'method/vinapet.api.file.get_file'
    ];

    // ============================================================================
    // CACHE CONFIGURATION - THỜI GIAN CACHE CHO TỪNG LOẠI DATA
    // ============================================================================
    const CACHE_TIMES = [
        'products_list'         => 3600,    // 1 giờ
        'product_detail'        => 3600,    // 1 giờ  
        'categories'            => 21600,   // 6 giờ
        'get_leads'             => 1800,    // 30 phút
        'get_customer_by_email' => 1800,    // 30 phút
        'create_customer'       => 0,       // No cache
        'get_quotations'        => 0,     // 2 phút
        'get_packages'          => 0,    // No cache
        'get_settings'          => 86400,   // 24 giờ
    ];

    // ============================================================================
    // HTTP STATUS CODES - TẬP TRUNG XỬ LÝ STATUS CODES
    // ============================================================================
    const HTTP_STATUS = [
        'SUCCESS' => [200, 201, 202],
        'CLIENT_ERROR' => [400, 401, 403, 404, 422],
        'SERVER_ERROR' => [500, 502, 503, 504]
    ];

    public function __construct()
    {
        $this->api_url = get_option('erp_api_url');
        $this->api_key = get_option('erp_api_key');
        $this->api_secret = get_option('erp_api_secret');
    }

    // ============================================================================
    // CONFIGURATION METHODS
    // ============================================================================

    /**
     * Check if API is configured
     */
    public function is_configured()
    {
        return !empty($this->api_url);
    }

    /**
     * Check if authentication is required
     */
    private function has_authentication()
    {
        return !empty($this->api_key) && !empty($this->api_secret);
    }

    /**
     * Get endpoint URL by key
     */
    private function get_endpoint($endpoint_key, $param = '')
    {
        if (!isset(self::ENDPOINTS[$endpoint_key])) {
            throw new InvalidArgumentException("Endpoint '{$endpoint_key}' not found");
        }

        $endpoint = self::ENDPOINTS[$endpoint_key];

        if (!empty($param)) {
            $endpoint .= '/' . urlencode($param);
        }

        return $endpoint;
    }

    /**
     * Get cache time for endpoint
     */
    private function get_cache_time($endpoint_key)
    {
        return self::CACHE_TIMES[$endpoint_key] ?? $this->cache_time;
    }

    // ============================================================================
    // PRODUCT METHODS
    // ============================================================================

    /**
     * Lấy danh sách sản phẩm từ API
     */
    public function get_products($params = [])
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_products_' . md5(serialize($params));
        $products = get_transient($cache_key);

        if (false === $products) {
            $endpoint = $this->get_endpoint('products_list');
            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['success']) && $data['message']['success'] && isset($data['message']['data'])) {
                    $products = $data['message']['data'];
                } else {
                    $products = [];
                }

                $cache_time = $this->get_cache_time('products_list');
                set_transient($cache_key, $products, $cache_time);
            } else {
                return false;
            }
        }

        return $products;
    }

    /**
     * Lấy thông tin chi tiết sản phẩm
     */
    public function get_product_detail($item_code)
    {
        if (!$this->is_configured() || empty($item_code)) {
            return false;
        }

        $cache_key = 'erp_product_detail_' . sanitize_key($item_code);
        $product = get_transient($cache_key);

        if (false === $product) {
            $endpoint = $this->get_endpoint('product_detail');

            $query_params = [
                'product_id' => $item_code
            ];

            $response = $this->make_request('GET', $endpoint, $query_params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message'])) {
                    $product = $data['message'];
                } else {
                    $product = null;
                }

                if ($product) {
                    $cache_time = $this->get_cache_time('product_detail');
                    set_transient($cache_key, $product, $cache_time);
                }
            } else {
                return false;
            }
        }

        return $product;
    }

    /**
     * Lấy danh sách nhóm sản phẩm
     */
    public function get_product_categories()
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_product_categories';
        $categories = get_transient($cache_key);

        if (false === $categories) {
            $endpoint = $this->get_endpoint('categories');
            $response = $this->make_request('GET', $endpoint);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['success']) && $data['message']['success'] && isset($data['message']['data'])) {
                    $categories = $data['message']['data'];
                } else {
                    $categories = [];
                }

                $cache_time = $this->get_cache_time('categories');
                set_transient($cache_key, $categories, $cache_time);
            } else {
                return false;
            }
        }

        return $categories;
    }

    // ============================================================================
    // LEAD METHODS
    // ============================================================================

    /**
     * Tạo lead trong ERPNext
     * 
     * @param array $lead_data Dữ liệu lead với format:
     * {
     *   "company_name": "Công ty ESight",
     *   "contact_name": "Trần Văn B", 
     *   "email": "Egisht@x.com",
     *   "phone": "0912345678",
     *   "address": "Tân Sơn Nhì",
     *   "needs": "Nội dung Hợp tác"
     * }
     * @return array|false Response từ ERPNext hoặc false nếu lỗi
     */
    public function create_lead($lead_data)
    {
        if (!$this->is_configured()) {
            return false;
        }

        // Validate required fields
        $required_fields = ['contact_name', 'email', 'phone', 'needs'];
        foreach ($required_fields as $field) {
            if (empty($lead_data[$field])) {
                return false;
            }
        }

        $endpoint = $this->get_endpoint('create_lead');
        $response = $this->make_request('POST', $endpoint, $lead_data);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['message'])) {
                return $data['message'];
            }

            return $data;
        }

        return false;
    }

    /**
     * Lấy danh sách leads
     */
    public function get_leads($params = [])
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_leads_' . md5(serialize($params));
        $leads = get_transient($cache_key);

        if (false === $leads) {
            $endpoint = $this->get_endpoint('get_leads');
            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['data'])) {
                    $leads = $data['message']['data'];
                } else {
                    $leads = [];
                }

                $cache_time = $this->get_cache_time('get_leads');
                set_transient($cache_key, $leads, $cache_time);
            } else {
                return false;
            }
        }

        return $leads;
    }

    /**
     * Cập nhật lead
     */
    public function update_lead($lead_id, $lead_data)
    {
        if (!$this->is_configured() || empty($lead_id)) {
            return false;
        }

        $endpoint = $this->get_endpoint('update_lead');
        $data = array_merge(['lead_id' => $lead_id], $lead_data);
        $response = $this->make_request('PUT', $endpoint, $data);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);

            // Clear related cache
            $this->clear_cache_by_pattern('erp_leads_*');

            return $result;
        }

        return false;
    }

    // ============================================================================
    // CUSTOMER METHODS
    // ============================================================================

    /**
     * POST create customer - API có sẵn
     * 
     * @param array $customer_data Customer data
     * @return array|false Response từ API hoặc false nếu lỗi
     */
    public function create_customer_vinapet($customer_data)
    {
        if (!$this->is_configured() || empty($customer_data)) {
            return false;
        }

        // Validate required fields
        $required_fields = ['customer_name', 'email', 'phone'];
        foreach ($required_fields as $field) {
            if (empty($customer_data[$field])) {
                return false;
            }
        }

        $endpoint = $this->get_endpoint('create_customer');
        $response = $this->make_request('POST', $endpoint, $customer_data);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['message']['status']) && $data['message']['status'] === 'success') {
                // Clear customer cache
                $cache_key = 'erp_customer_' . md5($customer_data['email']);
                delete_transient($cache_key);

                // Clear customers list cache
                $this->clear_cache_by_pattern('erp_customers_*');

                return $data['message'];
            }
        }

        return false;
    }

    /**
     * GET customer by email - API có sẵn
     * 
     * @param string $email Customer email
     * @return array|false Response từ API hoặc false nếu lỗi
     */
    public function get_customer_by_email($email)
    {
        if (!$this->is_configured() || empty($email)) {
            return false;
        }

        $cache_key = 'erp_customer_' . md5($email);
        $customer = get_transient($cache_key);

        if (false === $customer) {
            $endpoint = $this->get_endpoint('get_customer_by_email');
            $params = array('name' => $email);

            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['status']) && $data['message']['status'] === 'success') {
                    $customer = $data['message'];

                    $cache_time = $this->get_cache_time('get_customer_by_email');
                    set_transient($cache_key, $customer, $cache_time);
                    return $customer;
                }
            }

            // Cache negative result
            set_transient($cache_key, false, 300);
            return false;
        }

        return $customer;
    }

    /**
     * UPDATE customer - Sử dụng API create với name field
     * 
     * @param array $customer_data Customer data với name field
     * @return array|false Response từ API hoặc false nếu lỗi
     */
    public function update_customer_vinapet($customer_data)
    {
        if (!$this->is_configured() || empty($customer_data)) {
            return false;
        }

        // Validate required fields
        $required_fields = ['customer_name', 'email', 'name'];
        foreach ($required_fields as $field) {
            if (empty($customer_data[$field])) {
                error_log("VinaPet ERP: Missing required field for update: {$field}");
                return false;
            }
        }

        // Sử dụng endpoint create nhưng với name field để update
        $endpoint = 'method/vinapet.api.customer.customer.create_customer';
        $response = $this->make_request('POST', $endpoint, $customer_data);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['message']['status']) && $data['message']['status'] === 'success') {
                // Clear customer cache
                $cache_key = 'erp_customer_' . md5($customer_data['email']);
                delete_transient($cache_key);

                return $data['message'];
            }
        }

        return false;
    }

    /**
     * Sync WordPress user to ERP
     * 
     * @param int $user_id WordPress user ID
     * @return array|false Sync result
     */
    public function sync_wp_user_to_erp($user_id)
    {
        if (!$this->is_configured() || empty($user_id)) {
            return false;
        }

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return false;
        }

        $customer_data = array(
            'customer_name' => $user->display_name ?: $user->user_login,
            'email' => $user->user_email,
            'name' => $user->user_email, // Unique identifier
            'phone' => get_user_meta($user_id, 'phone_number', true) ?: '',
            'address' => get_user_meta($user_id, 'user_address', true) ?: '',
            'company_name' => get_user_meta($user_id, 'company_name', true) ?: ''
        );

        // Check if customer exists
        $existing_customer = $this->get_customer_by_email($user->user_email);

        if ($existing_customer && $existing_customer['status'] === 'success') {
            // Update existing customer
            $result = $this->update_customer_vinapet($customer_data);

            if ($result && $result['status'] === 'success') {
                update_user_meta($user_id, 'erpnext_customer_id', $existing_customer['customer']['name']);
                update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));

                return array('status' => 'updated', 'message' => 'Customer updated in ERP');
            }
        } else {
            // Create new customer
            $result = $this->create_customer_vinapet($customer_data);

            if ($result && $result['status'] === 'success') {
                update_user_meta($user_id, 'erpnext_customer_id', $result['name']);
                update_user_meta($user_id, 'erpnext_last_sync', current_time('mysql'));

                return array('status' => 'created', 'message' => 'Customer created in ERP');
            }
        }

        return false;
    }

    // ============================================================================
    // ORDER METHODS
    // ============================================================================

    /**
     * Tạo quotation trong ERPNext
     * 
     * @param array $quotation_data Dữ liệu quotation theo format yêu cầu
     * @return array|false Response từ ERPNext hoặc false nếu lỗi
     */
    public function create_quotation($quotation_data)
    {
        if (!$this->is_configured()) {
            return false;
        }

        // Validate required fields
        $required_fields = ['customer', 'items'];
        foreach ($required_fields as $field) {
            if (empty($quotation_data[$field])) {
                return false;
            }
        }

        // Validate items structure
        if (!is_array($quotation_data['items']) || empty($quotation_data['items'])) {
            return false;
        }

        // Set headers theo yêu cầu
        $headers = [
            'Content-Type' => 'application/json',
            'X-ERP-Topic' => 'quotation_create'
        ];

        // Thêm authentication headers nếu có
        if ($this->has_authentication()) {
            $headers['Authorization'] = 'token ' . $this->api_key . ':' . $this->api_secret;
        }

        // Endpoint theo yêu cầu của bạn
        $endpoint = 'method/vinapet.vinapet.connections.store_request_data';

        $response = $this->make_request_with_custom_headers('POST', $endpoint, $quotation_data, $headers);

        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (in_array($response_code, self::HTTP_STATUS['SUCCESS'])) {
                return [
                    'status' => 'success',
                    'data' => $data,
                    'response_code' => $response_code
                ];
            }

            return [
                'status' => 'error',
                'message' => $data['message'] ?? 'Unknown error occurred',
                'response_code' => $response_code
            ];
        }

        return [
            'status' => 'error',
            'message' => $response->get_error_message(),
            'response_code' => 0
        ];
    }

    /**
     * Lấy danh sách quotations theo email customer
     * 
     * @param string $customer_email Email của customer
     * @param string $status Status filter (Draft, Submitted, etc.)
     * @return array|false Danh sách quotations hoặc false nếu lỗi
     */
    public function get_quotations($customer_email, $status = 'Draft')
    {
        if (!$this->is_configured() || empty($customer_email)) {
            return false;
        }

        // Cache key theo email và status
         $cache_key = 'erp_quotations_' . md5($customer_email . '_' . $status);
        // $quotations = get_transient($cache_key);

        $quotations = false; // Disable caching for real-time data

        if (false === $quotations) {
            $endpoint = $this->get_endpoint('get_quotations');

            $params = [
                'customer' => sanitize_email($customer_email)
            ];

            // Thêm status filter nếu có
            if (!empty($status)) {
                $params['status'] = sanitize_text_field($status);
            }

            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (in_array($response_code, self::HTTP_STATUS['SUCCESS']) && isset($data['message'])) {
                    $quotations = $data['message'];

                    // Cache theo thời gian cấu hình
                    $cache_time = $this->get_cache_time('get_quotations');
                    set_transient($cache_key, $quotations, $cache_time);
                } else {
                    $quotations = [];
                }
            } else {
                error_log('VinaPet: Error getting quotations - ' . $response->get_error_message());
                return false;
            }
        }

        return $quotations;
    }

    /**
     * Lấy danh sách orders
     */
    public function get_orders($params = [])
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_orders_' . md5(serialize($params));
        $orders = get_transient($cache_key);

        if (false === $orders) {
            $endpoint = $this->get_endpoint('get_orders');
            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['data'])) {
                    $orders = $data['message']['data'];
                } else {
                    $orders = [];
                }

                $cache_time = $this->get_cache_time('get_orders');
                set_transient($cache_key, $orders, $cache_time);
            } else {
                return false;
            }
        }

        return $orders;
    }

    // ============================================================================
    // PACKAGE METHODS
    // ============================================================================

    public function get_packages($params = [])
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_packages_' . md5(serialize($params));
        $packages = get_transient($cache_key);

        if (false === $packages) {
            $endpoint = $this->get_endpoint('get_packages');
            $response = $this->make_request('GET', $endpoint, $params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message']['success']) && $data['message']['success'] && isset($data['message']['data'])) {
                    $packages = $data['message']['data'];
                } else {
                    $packages = [];
                }

                $cache_time = $this->get_cache_time('get_packages');
                set_transient($cache_key, $packages, $cache_time);
            } else {
                return false;
            }
        }

        return $packages;
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Health check API
     */
    public function health_check()
    {
        if (!$this->is_configured()) {
            return false;
        }

        $endpoint = $this->get_endpoint('health_check');
        $response = $this->make_request('GET', $endpoint);

        if (!is_wp_error($response)) {
            $response_code = wp_remote_retrieve_response_code($response);
            return in_array($response_code, self::HTTP_STATUS['SUCCESS']);
        }

        return false;
    }

    /**
     * Get ERPNext settings
     */
    public function get_settings()
    {
        if (!$this->is_configured()) {
            return false;
        }

        $cache_key = 'erp_settings';
        $settings = get_transient($cache_key);

        if (false === $settings) {
            $endpoint = $this->get_endpoint('get_settings');
            $response = $this->make_request('GET', $endpoint);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);

                if (isset($data['message'])) {
                    $settings = $data['message'];

                    $cache_time = $this->get_cache_time('get_settings');
                    set_transient($cache_key, $settings, $cache_time);
                }
            } else {
                return false;
            }
        }

        return $settings;
    }

    // ============================================================================
    // CORE HTTP METHOD
    // ============================================================================

    /**
     * Thực hiện HTTP request đến API
     */
    public function make_request($method, $endpoint, $params = [])
    {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'ERPNext API URL chưa được cấu hình');
        }

        $url = trailingslashit($this->api_url) . 'api/' . ltrim($endpoint, '/');

        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'sslverify' => true
        ];

        // Add authentication
        if ($this->has_authentication()) {
            $args['headers']['Authorization'] = 'token ' . $this->api_key . ':' . $this->api_secret;
        }

        // Handle different HTTP methods
        if ($method === 'GET' && !empty($params)) {
            $url = add_query_arg($params, $url);
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']) && !empty($params)) {
            $args['body'] = json_encode($params);
        }

        // Make request
        $response = wp_remote_request($url, $args);

        // Log request for debugging (only in development)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'ERP API Request: %s %s - Response Code: %s',
                $method,
                $url,
                is_wp_error($response) ? 'ERROR' : wp_remote_retrieve_response_code($response)
            ));
        }

        return $response;
    }

    /**
     * Make request với custom headers
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint  
     * @param array $params Request parameters
     * @param array $custom_headers Custom headers
     * @return WP_Error|array Response
     */
    private function make_request_with_custom_headers($method, $endpoint, $params = [], $custom_headers = [])
    {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'ERPNext API URL chưa được cấu hình');
        }

        // Build full URL - sử dụng URL cụ thể theo yêu cầu
        $url = trailingslashit($this->api_url) . 'api/' . ltrim($endpoint, '/');

        $args = [
            'method' => strtoupper($method),
            'timeout' => 30,
            'headers' => array_merge([
                'Content-Type' => 'application/json',
            ], $custom_headers),
            'sslverify' => false
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && !empty($params)) {
            $args['body'] = json_encode($params);
        } elseif (strtoupper($method) === 'GET' && !empty($params)) {
            $url = add_query_arg($params, $url);
        }

        // Log request for debugging
        error_log('VinaPet ERP API Request: ' . $method . ' ' . $url);
        error_log('VinaPet ERP API Data: ' . json_encode($params));

        return wp_remote_request($url, $args);
    }

    // ============================================================================
    // CACHE MANAGEMENT
    // ============================================================================

    /**
     * Clear all ERP cache
     */
    public function clear_all_cache()
    {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_erp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_erp_%'");
    }

    /**
     * Clear cache by pattern
     */
    public function clear_cache_by_pattern($pattern)
    {
        global $wpdb;

        $pattern = str_replace('*', '%', $pattern);
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $pattern,
            '_transient_timeout_' . $pattern
        ));
    }

    /**
     * Get cache status
     */
    public function get_cache_status()
    {
        global $wpdb;

        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_erp_%'"
        );

        return [
            'cache_entries' => (int) $cache_count,
            'last_cleared' => get_option('erp_cache_last_cleared', 'Never')
        ];
    }

    /**
     * Set cache cleared timestamp
     */
    public function set_cache_cleared()
    {
        update_option('erp_cache_last_cleared', current_time('mysql'));
    }
}
