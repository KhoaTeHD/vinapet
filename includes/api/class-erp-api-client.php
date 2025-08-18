<?php
// File: includes/api/class-erp-api-client.php

class ERP_API_Client {
    private $api_url;
    private $api_key;
    private $api_secret;
    private $cache_time = 3600; // 1 giờ

    public function __construct() {
        $this->api_url = get_option('erp_api_url');
        $this->api_key = get_option('erp_api_key');
        $this->api_secret = get_option('erp_api_secret');
    }

    /**
     * Lấy danh sách sản phẩm từ ERPNext
     */
    public function get_products($params = []) {
        $cache_key = 'erp_products_' . md5(serialize($params));
        $products = get_transient($cache_key);

        if (false === $products) {
            $endpoint = '/api/resource/Item';
            $query_params = [
                'fields' => '["name", "item_name", "description", "image", "item_code", "item_group", "standard_rate"]',
                'limit' => isset($params['limit']) ? $params['limit'] : 20,
                'order_by' => 'item_name asc'
            ];

            // Thêm tìm kiếm nếu có
            if (!empty($params['search'])) {
                $query_params['filters'] = json_encode([
                    ['Item', 'item_name', 'like', '%' . $params['search'] . '%']
                ]);
            }

            // Thêm lọc theo nhóm sản phẩm nếu có
            if (!empty($params['category'])) {
                $query_params['filters'] = json_encode([
                    ['Item', 'item_group', '=', $params['category']]
                ]);
            }

            // Thêm sắp xếp
            if (!empty($params['sort'])) {
                switch ($params['sort']) {
                    case 'name-asc':
                        $query_params['order_by'] = 'item_name asc';
                        break;
                    case 'name-desc':
                        $query_params['order_by'] = 'item_name desc';
                        break;
                    case 'price-asc':
                        $query_params['order_by'] = 'standard_rate asc';
                        break;
                    case 'price-desc':
                        $query_params['order_by'] = 'standard_rate desc';
                        break;
                    case 'newest':
                        $query_params['order_by'] = 'creation desc';
                        break;
                }
            }

            // Thêm phân trang
            if (isset($params['page']) && $params['page'] > 1) {
                $query_params['start'] = ($params['page'] - 1) * $query_params['limit'];
            }

            $response = $this->make_request('GET', $endpoint, $query_params);

            if (!is_wp_error($response)) {
                $products = json_decode(wp_remote_retrieve_body($response), true);
                set_transient($cache_key, $products, $this->cache_time);
            } else {
                // Xử lý lỗi
                error_log('ERPNext API Error: ' . $response->get_error_message());
                return false;
            }
        }

        return $products;
    }

    /**
     * Lấy chi tiết một sản phẩm
     */
    public function get_product($item_code) {
        $cache_key = 'erp_product_' . $item_code;
        $product = get_transient($cache_key);

        if (false === $product) {
            $endpoint = '/api/resource/Item/' . urlencode($item_code);
            $response = $this->make_request('GET', $endpoint);

            if (!is_wp_error($response)) {
                $product = json_decode(wp_remote_retrieve_body($response), true);
                set_transient($cache_key, $product, $this->cache_time);
            } else {
                // Xử lý lỗi
                error_log('ERPNext API Error: ' . $response->get_error_message());
                return false;
            }
        }

        return $product;
    }

    /**
     * Lấy danh sách nhóm sản phẩm
     */
    public function get_product_categories() {
        $cache_key = 'erp_product_categories';
        $categories = get_transient($cache_key);

        if (false === $categories) {
            $endpoint = '/api/resource/Item Group';
            $query_params = [
                'fields' => '["name", "parent_item_group"]',
                'limit' => 50
            ];

            $response = $this->make_request('GET', $endpoint, $query_params);

            if (!is_wp_error($response)) {
                $categories = json_decode(wp_remote_retrieve_body($response), true);
                set_transient($cache_key, $categories, $this->cache_time * 6); // 6 giờ
            } else {
                // Xử lý lỗi
                error_log('ERPNext API Error: ' . $response->get_error_message());
                return false;
            }
        }

        return $categories;
    }

    /**
     * Thực hiện HTTP request đến ERPNext API
     */
    private function make_request($method, $endpoint, $params = []) {
        $url = trailingslashit($this->api_url) . ltrim($endpoint, '/');
        
        if (!empty($params) && $method == 'GET') {
            $url = add_query_arg($params, $url);
        }

        $args = [
            'method' => $method,
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'token ' . $this->api_key . ':' . $this->api_secret,
                'Content-Type' => 'application/json'
            ]
        ];

        if ($method != 'GET' && !empty($params)) {
            $args['body'] = json_encode($params);
        }

        return wp_remote_request($url, $args);
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