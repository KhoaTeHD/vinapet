<?php
/**
 * ERP API Client - FINAL CLEAN VERSION
 * File: includes/api/class-erp-api-client.php
 */

class ERP_API_Client {
    private $api_url;
    private $api_key;
    private $api_secret;
    private $cache_time = 3600;
    
    // ============================================================================
    // ENDPOINTS CONFIGURATION - DỄ DÀNG THAY ĐỔI TẠI ĐÂY
    // ============================================================================
    const ENDPOINTS = [
        'products_list'     => '/api/method/vinapet.api.item.item.get_products',
        'product_detail'    => '/api/method/vinapet.api.item.item.get_item_detail',  // Sẽ thêm item_code vào sau
        'categories'        => '/api/resource/Item Group',
        // Thêm endpoints khác ở đây khi cần
    ];
    
    // ============================================================================
    // CACHE CONFIGURATION - DỄ DÀNG THAY ĐỔI THỜI GIAN CACHE
    // ============================================================================
    const CACHE_TIMES = [
        'products_list' => 3600,    // 1 giờ
        'product_detail' => 3600,   // 1 giờ  
        'categories' => 21600,      // 6 giờ
    ];

    public function __construct() {
        $this->api_url = get_option('erp_api_url');
        $this->api_key = get_option('erp_api_key');
        $this->api_secret = get_option('erp_api_secret');
    }
    
    /**
     * Check if API is configured
     */
    public function is_configured() {
        return !empty($this->api_url);
    }
    
    /**
     * Check if authentication is required
     */
    private function has_authentication() {
        return !empty($this->api_key) && !empty($this->api_secret);
    }
    
    /**
     * Get endpoint URL
     */
    private function get_endpoint($endpoint_key, $param = '') {
        if (!isset(self::ENDPOINTS[$endpoint_key])) {
            return '/api/resource/Item'; // fallback
        }
        
        $endpoint = self::ENDPOINTS[$endpoint_key];
        
        if (!empty($param)) {
            $endpoint .= urlencode($param);
        }
        
        return $endpoint;
    }
    
    /**
     * Get cache time for endpoint
     */
    private function get_cache_time($endpoint_key) {
        if (!isset(self::CACHE_TIMES[$endpoint_key])) {
            return $this->cache_time;
        }
        
        return self::CACHE_TIMES[$endpoint_key];
    }

    /**
     * Lấy danh sách sản phẩm từ API
     */
    public function get_products($params = []) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $cache_key = 'erp_products_' . md5(serialize($params));
        $products = get_transient($cache_key);

        if (false === $products) {
            $endpoint = $this->get_endpoint('products_list');
            
            $query_params = [];
            
            if (isset($params['limit'])) {
                $query_params['limit'] = $params['limit'];
            }
            
            if (isset($params['page']) && $params['page'] > 1 && isset($params['limit'])) {
                $query_params['start'] = ($params['page'] - 1) * $params['limit'];
            }

            $response = $this->make_request('GET', $endpoint, $query_params);

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
     * Lấy chi tiết một sản phẩm
     */
    public function get_product($item_code) {
        if (!$this->is_configured()) {
            return false;
        }
        
        $cache_key = 'erp_product_' . $item_code;
        $product = get_transient($cache_key);

        if (false === $product) {
            $endpoint = $this->get_endpoint('product_detail');
            
            // Thêm product_id vào query parameters
            $query_params = [
                'product_id' => $item_code
            ];
            
            $response = $this->make_request('GET', $endpoint, $query_params);

            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data['message']['success']) && $data['message']['success'] && isset($data['message']['data'])) {
                    $product = $data['message']['data'];
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
    public function get_product_categories() {
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

    /**
     * Thực hiện HTTP request đến API
     */
    public function make_request($method, $endpoint, $params = []) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'ERPNext API URL chưa được cấu hình');
        }
        
        $url = trailingslashit($this->api_url) . ltrim($endpoint, '/');
        
        if (!empty($params) && $method == 'GET') {
            $url = add_query_arg($params, $url);
        }

        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];
        
        if ($this->has_authentication()) {
            $args['headers']['Authorization'] = 'token ' . $this->api_key . ':' . $this->api_secret;
        }

        if ($method != 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }

        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code >= 400) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : "HTTP {$status_code} Error";
            return new WP_Error('api_error', $error_message);
        }

        return $response;
    }

    /**
     * Xóa cache
     */
    public function clear_cache() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_erp_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_erp_%'");
    }
}