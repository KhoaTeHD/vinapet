<?php
/**
 * Product Data Manager
 * File: includes/helpers/class-product-data-manager.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Product_Data_Manager {
    
    private $erp_client;
    private $sample_provider;
    private $cache_prefix = 'vinapet_products_';
    private $cache_time = 3600;
    
    public function __construct() {
        if (class_exists('ERP_API_Client')) {
            $this->erp_client = new ERP_API_Client();
        }
        
        if (class_exists('Sample_Product_Provider')) {
            $this->sample_provider = new Sample_Product_Provider();
        }
    }
    
    /**
     * Lấy danh sách sản phẩm -
     */
    public function get_products($params = []) {
        // Clean params
        $search = isset($params['search']) ? trim($params['search']) : '';
        $sort = isset($params['sort']) ? $params['sort'] : 'default';
        $key = isset($params['key']) ? trim($params['key']) : '';
        
        // Cache key
        $cache_key = $this->cache_prefix . md5(serialize(compact('search', 'sort', 'key')));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            $cached['is_cached'] = true;
            return $cached;
        }
        
        $result = [
            'products' => [],
            'total' => 0,
            'source' => 'none',
            'error' => '',
            'is_cached' => false
        ];
        
        // Thử ERPNext trước
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_products($params);
                
                if ($erp_response !== false && is_array($erp_response)) {
                    // Lấy data từ response
                    $products_data = [];
                    if (isset($erp_response['message']['data'])) {
                        $products_data = $erp_response['message']['data'];
                    } elseif (isset($erp_response['data'])) {
                        $products_data = $erp_response['data'];
                    } else {
                        $products_data = $erp_response;
                    }
                    
                    // Apply search và sort LOCAL
                    $products_data = $this->filter_products($products_data, $search, $sort);
                    
                    $result['products'] = $products_data;
                    $result['total'] = count($products_data);
                    $result['source'] = 'erp';
                    
                    set_transient($cache_key, $result, $this->cache_time);
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext Error: ' . $e->getMessage();
            }
        }
        
        // Fallback sample data
        if ($this->sample_provider) {
            try {
                $sample_response = $this->sample_provider->get_products($params);
                
                if (isset($sample_response['data'])) {
                    $products_data = $this->filter_products($sample_response['data'], $search, $sort);
                    
                    $result['products'] = $products_data;
                    $result['total'] = count($products_data);
                    $result['source'] = 'sample';
                    
                    set_transient($cache_key, $result, 600);
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] .= ' | Sample Error: ' . $e->getMessage();
            }
        }
        
        return $result;
    }
    
    /**
     * Filter và sort products LOCAL - QUAN TRỌNG: Method này làm sort hoạt động
     */
    private function filter_products($products, $search = '', $sort = 'default') {
        if (empty($products)) {
            return [];
        }
        
        // Search filter
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $products = array_filter($products, function($product) use ($search_lower) {
                // Dùng trực tiếp ERPNext fields
                $name = strtolower($product['Ten_SP'] ?? $product['item_name'] ?? '');
                $desc = strtolower(strip_tags($product['Mo_ta_ngan'] ?? $product['description'] ?? ''));
                $code = strtolower($product['Ma_SP'] ?? $product['ProductID'] ?? $product['item_code'] ?? '');
                
                return stripos($name, $search_lower) !== false ||
                       stripos($desc, $search_lower) !== false ||
                       stripos($code, $search_lower) !== false;
            });
        }
        
        // Sort - ĐÂY LÀ PHẦN QUAN TRỌNG
        if ($sort !== 'default') {
            switch ($sort) {
                case 'name-asc':
                    usort($products, function($a, $b) {
                        $name_a = $a['Ten_SP'] ?? $a['item_name'] ?? '';
                        $name_b = $b['Ten_SP'] ?? $b['item_name'] ?? '';
                        return strcmp($name_a, $name_b);
                    });
                    break;
                case 'name-desc':
                    usort($products, function($a, $b) {
                        $name_a = $a['Ten_SP'] ?? $a['item_name'] ?? '';
                        $name_b = $b['Ten_SP'] ?? $b['item_name'] ?? '';
                        return strcmp($name_b, $name_a); // Đảo ngược để Z→A
                    });
                    break;
                case 'price-asc':
                    usort($products, function($a, $b) {
                        $price_a = floatval($a['Gia_ban_le'] ?? $a['standard_rate'] ?? 0);
                        $price_b = floatval($b['Gia_ban_le'] ?? $b['standard_rate'] ?? 0);
                        return $price_a - $price_b;
                    });
                    break;
                case 'price-desc':
                    usort($products, function($a, $b) {
                        $price_a = floatval($a['Gia_ban_le'] ?? $a['standard_rate'] ?? 0);
                        $price_b = floatval($b['Gia_ban_le'] ?? $b['standard_rate'] ?? 0);
                        return $price_b - $price_a;
                    });
                    break;
                case 'newest':
                    usort($products, function($a, $b) {
                        $date_a = strtotime($a['Ngay_tao'] ?? $a['creation'] ?? '');
                        $date_b = strtotime($b['Ngay_tao'] ?? $b['creation'] ?? '');
                        return $date_b - $date_a;
                    });
                    break;
            }
        }
        
        return array_values($products);
    }
    
    /**
     * Get single product
     */
    public function get_product($item_code) {
        $result = [
            'product' => null,
            'source' => 'none',
            'error' => ''
        ];
        
        if (empty($item_code)) {
            $result['error'] = 'Mã sản phẩm không hợp lệ';
            return $result;
        }
        
        // Try ERP first
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_product_detail($item_code);
                if ($erp_response !== false) {
                    $result['product'] = $erp_response;
                    $result['source'] = 'erp';
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext Error: ' . $e->getMessage();
            }
        }
        
        // Fallback to sample
        if ($this->sample_provider) {
            try {
                $sample_response = $this->sample_provider->get_product($item_code);
                if (isset($sample_response['data'])) {
                    $result['product'] = $sample_response['data'];
                    $result['source'] = 'sample';
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] .= ' | Sample Error: ' . $e->getMessage();
            }
        }
        
        $result['error'] = 'Không tìm thấy sản phẩm';
        return $result;
    }
    
    /**
     * Clear cache
     */
    public function clear_cache($item_code = null) {
        global $wpdb;
        
        if ($item_code) {
            $cache_key = $this->cache_prefix . 'single_' . md5($item_code);
            delete_transient($cache_key);
        } else {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . '%'
            ));
        }
    }
    
    /**
     * Get categories
     */
    public function get_categories() {
        return [
            'categories' => [],
            'source' => 'sample',
            'error' => ''
        ];
    }

    public function count_products() {
        $products = $this->get_products();
        $cat_tre = $this->filter_products($products['products'], 'Cát tre', 'default');
        $cat_tofu = $this->filter_products($products['products'], 'Cát tofu', 'default');
        $hat_sap = $this->filter_products($products['products'], 'S.A.P', 'default');

        return [
            'count' => [
                'all' => count($products['products']),
                'cat_tre' => count($cat_tre),
                'cat_tofu' => count($cat_tofu),
                'hat_sap' => count($hat_sap)
            ],
            'source' => 'none',
            'error' => 'Chưa triển khai'
        ];
    }

    public function get_product_price_detail($item_code) {
        $result = [
            'price_detail' => null,
            'source' => 'none',
            'error' => ''
        ];
        
        if (empty($item_code)) {
            $result['error'] = 'Mã sản phẩm không hợp lệ';
            return $result;
        }
        
        // Try ERP first
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_product_price_detail($item_code);
                if ($erp_response !== false) {
                    $result['price_detail'] = $erp_response;
                    $result['source'] = 'erp';
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext Error: ' . $e->getMessage();
            }
        }
        
        $result['error'] = 'Không tìm thấy chi tiết giá';
        return $result;
    }

    /**
     * Get packages
     */
    public function get_packages() {
        $packages = $this->erp_client->get_packages();

        $result = [];
        foreach ($packages as $package) {
            $result[] = [
                'id' => $package['PacketID'] ?? '',
                'name' => $package['Ten_SP'] ?? '',
                'description' => $package['Mo_ta_ngan'] ?? '',
                'price' => floatval($package['Gia_ban_le'] ?? 0)
            ];
        }

        return $result;
    }

    /**
     * Get connection status
     */
    public function get_connection_status() {
        $status = [
            'erp_configured' => false,
            'erp_working' => false,
            'sample_available' => false,
            'last_test' => time()
        ];
        
        if ($this->erp_client && $this->erp_client->is_configured()) {
            $status['erp_configured'] = true;
            try {
                $test = $this->erp_client->get_products(['limit' => 1]);
                $status['erp_working'] = ($test !== false);
            } catch (Exception $e) {
                $status['erp_working'] = false;
            }
        }
        
        $status['sample_available'] = ($this->sample_provider !== null);
        
        return $status;
    }
    
    /**
     * Validate params
     */
    public function validate_params($params) {
        $validated = [];
        
        if (isset($params['search'])) {
            $validated['search'] = sanitize_text_field($params['search']);
        }
        
        $allowed_sorts = ['default', 'name-asc', 'name-desc', 'price-asc', 'price-desc', 'newest'];
        if (isset($params['sort']) && in_array($params['sort'], $allowed_sorts)) {
            $validated['sort'] = $params['sort'];
        }
        
        return $validated;
    }
}