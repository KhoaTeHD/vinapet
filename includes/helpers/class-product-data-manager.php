<?php
/**
 * Product Data Manager
 * File: includes/helpers/class-product-data-manager.php
 * 
 * Helper class để quản lý việc lấy và xử lý dữ liệu sản phẩm
 * Cung cấp fallback mechanism và caching
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Product_Data_Manager {
    
    private $erp_client;
    private $sample_provider;
    private $cache_prefix = 'vinapet_products_';
    private $cache_time = 3600; // 1 hour
    
    /**
     * Constructor
     */
    public function __construct() {
        // Khởi tạo ERP API Client
        if (class_exists('ERP_API_Client')) {
            $this->erp_client = new ERP_API_Client();
        }
        
        // Khởi tạo Sample Provider
        if (class_exists('Sample_Product_Provider')) {
            $this->sample_provider = new Sample_Product_Provider();
        }
    }
    
    /**
     * Lấy danh sách sản phẩm với fallback mechanism
     * 
     * @param array $params Tham số tìm kiếm và lọc
     * @return array Kết quả chứa products, total, source, error
     */
    public function get_products($params = []) {
        $result = [
            'products' => [],
            'total' => 0,
            'source' => 'none',
            'error' => '',
            'is_cached' => false
        ];
        
        // Tạo cache key dựa trên params
        $cache_key = $this->cache_prefix . md5(serialize($params));
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            $cached_result['is_cached'] = true;
            return $cached_result;
        }
        
        // Thử lấy từ ERPNext trước
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_products($params);
                
                if ($erp_response !== false && is_array($erp_response)) {
                    $result['products'] = $this->normalize_products($erp_response);
                    $result['total'] = count($result['products']);
                    $result['source'] = 'erp';
                    
                    // Cache kết quả thành công
                    set_transient($cache_key, $result, $this->cache_time);
                    
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext API Error: ' . $e->getMessage();
                error_log('VinaPet Product Manager - ERP Error: ' . $e->getMessage());
            }
        }
        
        // Fallback sang sample data
        if ($this->sample_provider) {
            try {
                $sample_response = $this->sample_provider->get_products($params);
                
                if (isset($sample_response['data']) && is_array($sample_response['data'])) {
                    $result['products'] = $this->normalize_products($sample_response['data']);
                    $result['total'] = isset($sample_response['total']) ? $sample_response['total'] : count($result['products']);
                    $result['source'] = 'sample';
                    
                    if (empty($result['error'])) {
                        $result['error'] = 'Sử dụng dữ liệu mẫu - ERPNext API không khả dụng';
                    }
                    
                    // Cache kết quả fallback (thời gian ngắn hơn)
                    set_transient($cache_key, $result, 600); // 10 minutes
                    
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] .= ' | Sample Data Error: ' . $e->getMessage();
                error_log('VinaPet Product Manager - Sample Error: ' . $e->getMessage());
            }
        }
        
        $result['error'] = 'Không thể lấy dữ liệu sản phẩm từ bất kỳ nguồn nào';
        return $result;
    }
    
    /**
     * Lấy chi tiết một sản phẩm
     * 
     * @param string $item_code Mã sản phẩm
     * @return array Kết quả chứa product, source, error
     */
    public function get_product($item_code) {
        $result = [
            'product' => null,
            'source' => 'none',
            'error' => '',
            'is_cached' => false
        ];
        
        if (empty($item_code)) {
            $result['error'] = 'Mã sản phẩm không hợp lệ';
            return $result;
        }
        
        // Cache key cho sản phẩm đơn
        $cache_key = $this->cache_prefix . 'single_' . md5($item_code);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            $cached_result['is_cached'] = true;
            return $cached_result;
        }
        
        // Thử lấy từ ERPNext trước
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_product($item_code);
                
                if ($erp_response !== false) {
                    $result['product'] = $this->normalize_product($erp_response);
                    $result['source'] = 'erp';
                    
                    // Cache kết quả thành công
                    set_transient($cache_key, $result, $this->cache_time);
                    
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext API Error: ' . $e->getMessage();
                error_log('VinaPet Product Manager - ERP Single Error: ' . $e->getMessage());
            }
        }
        
        // Fallback sang sample data
        if ($this->sample_provider) {
            try {
                $sample_response = $this->sample_provider->get_product($item_code);
                
                if (isset($sample_response['success']) && $sample_response['success'] && isset($sample_response['data'])) {
                    $result['product'] = $this->normalize_product($sample_response['data']);
                    $result['source'] = 'sample';
                    
                    if (empty($result['error'])) {
                        $result['error'] = 'Sử dụng dữ liệu mẫu - ERPNext API không khả dụng';
                    }
                    
                    // Cache kết quả fallback
                    set_transient($cache_key, $result, 600); // 10 minutes
                    
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] .= ' | Sample Data Error: ' . $e->getMessage();
                error_log('VinaPet Product Manager - Sample Single Error: ' . $e->getMessage());
            }
        }
        
        $result['error'] = 'Không tìm thấy sản phẩm';
        return $result;
    }
    
    /**
     * Chuẩn hóa dữ liệu sản phẩm từ các nguồn khác nhau
     * 
     * @param array $products Mảng sản phẩm hoặc response từ API
     * @return array Mảng sản phẩm đã chuẩn hóa
     */
    private function normalize_products($products) {
        if (!is_array($products)) {
            return [];
        }
        
        // Xử lý response format từ ERPNext API mới
        $products_data = [];
        
        if (isset($products['message']['data']) && is_array($products['message']['data'])) {
            // Format từ ERPNext: {"message": {"success": true, "data": [...]}}
            $products_data = $products['message']['data'];
        } elseif (isset($products['data']) && is_array($products['data'])) {
            // Format khác: {"data": [...]}
            $products_data = $products['data'];
        } elseif (is_array($products) && !isset($products['message'])) {
            // Direct array format (từ sample data)
            $products_data = $products;
        }
        
        $normalized = [];
        
        foreach ($products_data as $product) {
            $normalized[] = $this->normalize_product($product);
        }
        
        return $normalized;
    }
    
    /**
     * Chuẩn hóa dữ liệu một sản phẩm
     * Map từ format ERPNext mới sang format template hiện tại đang expect
     * 
     * @param array $product Dữ liệu sản phẩm thô
     * @return array Dữ liệu sản phẩm theo format template hiện tại
     */
    private function normalize_product($product) {
        if (!is_array($product)) {
            return [];
        }
        
        // Xử lý nếu product được wrap trong message
        if (isset($product['message']['data'])) {
            $product = $product['message']['data'];
        } elseif (isset($product['data'])) {
            $product = $product['data'];
        }
        
        // Mapping theo format ERPNext mới của bạn sang format template hiện tại
        $normalized = [
            // Map ERPNext fields -> Template expected fields
            'item_code' => $this->get_field($product, ['Ma_SP', 'ProductID', 'item_code'], ''),
            'item_name' => $this->get_field($product, ['Ten_SP', 'item_name', 'name'], ''),
            'description' => $this->clean_html_description($this->get_field($product, ['Mo_ta_ngan', 'description'], '')),
            'item_group' => $this->get_field($product, ['item_group', 'category'], 'Cát tre'), // Default category
            'standard_rate' => floatval($this->get_field($product, ['Gia_ban_le', 'standard_rate', 'price'], 0)),
            'image' => $this->normalize_image_url($this->get_field($product, ['Thumbnail_File', 'image', 'thumbnail'], '')),
            'stock_qty' => floatval($this->get_field($product, ['stock_qty', 'quantity'], 100)), // Default stock
            'is_stock_item' => 1, // Default to stock item
            'disabled' => 0, // Default to enabled
            'created_date' => $this->normalize_date($this->get_field($product, ['Ngay_tao', 'creation', 'created_at'], '')),
            'modified_date' => $this->normalize_date($this->get_field($product, ['modified', 'updated_at'], ''))
        ];
        
        // Thêm các trường mà template hiện tại cần
        $normalized['custom_fields'] = $this->extract_custom_fields($product);
        
        // Tạo URL sản phẩm (template hiện tại expect field này)
        $normalized['product_url'] = home_url('/san-pham/' . sanitize_title($normalized['item_code']));
        
        // Format giá hiển thị (template hiện tại dùng)
        $normalized['formatted_price'] = $this->format_price($normalized['standard_rate']);
        
        // Trạng thái sản phẩm
        $normalized['is_available'] = !$normalized['disabled'] && ($normalized['stock_qty'] > 0 || !$normalized['is_stock_item']);
        
        // Rút gọn mô tả cho hiển thị
        $normalized['short_description'] = wp_trim_words(strip_tags($normalized['description']), 15, '...');
        
        return $normalized;
    }
    
    /**
     * Clean HTML description từ ERPNext editor
     * 
     * @param string $description Raw description
     * @return string Cleaned description
     */
    private function clean_html_description($description) {
        if (empty($description)) {
            return '';
        }
        
        // Remove div wrapper từ QL editor
        $description = preg_replace('/<div class="ql-editor[^"]*"[^>]*>/', '', $description);
        $description = str_replace('</div>', '', $description);
        
        // Chỉ giữ lại basic HTML tags
        $allowed_tags = '<p><br><strong><b><em><i><ul><ol><li>';
        $description = strip_tags($description, $allowed_tags);
        
        return trim($description);
    }
    
    /**
     * Normalize date format
     * 
     * @param string $date Raw date
     * @return string Formatted date
     */
    private function normalize_date($date) {
        if (empty($date)) {
            return date('Y-m-d H:i:s');
        }
        
        // Convert ERPNext date format to standard
        try {
            $datetime = new DateTime($date);
            return $datetime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Lấy giá trị từ array với nhiều key khác nhau
     * 
     * @param array $data Dữ liệu nguồn
     * @param array $keys Danh sách key cần tìm
     * @param mixed $default Giá trị mặc định
     * @return mixed Giá trị tìm được hoặc default
     */
    private function get_field($data, $keys, $default = '') {
        foreach ($keys as $key) {
            if (isset($data[$key]) && !empty($data[$key])) {
                return $data[$key];
            }
        }
        return $default;
    }
    
    /**
     * Chuẩn hóa URL hình ảnh
     * 
     * @param string $image_url URL hình ảnh
     * @return string URL hình ảnh đã chuẩn hóa
     */
    private function normalize_image_url($image_url) {
        if (empty($image_url)) {
            return get_template_directory_uri() . '/assets/images/placeholder.jpg';
        }
        
        // Nếu là URL tương đối, thêm domain ERPNext
        if (strpos($image_url, 'http') !== 0 && $this->erp_client) {
            $erp_url = get_option('erp_api_url');
            if (!empty($erp_url)) {
                $image_url = trailingslashit($erp_url) . ltrim($image_url, '/');
            }
        }
        
        return $image_url;
    }
    
    /**
     * Format giá tiền
     * 
     * @param float $price Giá
     * @return string Giá đã format
     */
    private function format_price($price) {
        if ($price <= 0) {
            return 'Liên hệ';
        }
        
        return number_format($price, 0, ',', '.') . ' đ';
    }
    
    /**
     * Trích xuất các custom fields
     * 
     * @param array $product Dữ liệu sản phẩm
     * @return array Custom fields
     */
    private function extract_custom_fields($product) {
        $custom_fields = [];
        
        // Danh sách các trường tiêu chuẩn
        $standard_fields = [
            'item_code', 'item_name', 'description', 'item_group', 
            'standard_rate', 'image', 'stock_qty', 'is_stock_item', 
            'disabled', 'creation', 'modified', 'created_at', 'updated_at'
        ];
        
        foreach ($product as $key => $value) {
            if (!in_array($key, $standard_fields) && !empty($value)) {
                $custom_fields[$key] = $value;
            }
        }
        
        return $custom_fields;
    }
    
    /**
     * Lấy danh mục sản phẩm
     * 
     * @return array Danh sách danh mục
     */
    public function get_categories() {
        $cache_key = $this->cache_prefix . 'categories';
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $result = [
            'categories' => [],
            'source' => 'none',
            'error' => ''
        ];
        
        // Thử lấy từ ERPNext
        if ($this->erp_client && $this->erp_client->is_configured()) {
            try {
                $erp_response = $this->erp_client->get_product_categories();
                
                if ($erp_response !== false && is_array($erp_response)) {
                    $result['categories'] = $erp_response;
                    $result['source'] = 'erp';
                    
                    set_transient($cache_key, $result, $this->cache_time);
                    return $result;
                }
            } catch (Exception $e) {
                $result['error'] = 'ERPNext API Error: ' . $e->getMessage();
            }
        }
        
        // Fallback sang sample data
        if ($this->sample_provider) {
            try {
                $sample_response = $this->sample_provider->get_product_categories();
                
                if (isset($sample_response['data'])) {
                    $result['categories'] = $sample_response['data'];
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
     * Clear cache cho sản phẩm
     * 
     * @param string $item_code Mã sản phẩm cụ thể (optional)
     */
    public function clear_cache($item_code = null) {
        global $wpdb;
        
        if ($item_code) {
            // Clear cache cho sản phẩm cụ thể
            $cache_key = $this->cache_prefix . 'single_' . md5($item_code);
            delete_transient($cache_key);
        } else {
            // Clear tất cả cache sản phẩm
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . '%'
            ));
        }
    }
    
    /**
     * Kiểm tra trạng thái kết nối API
     * 
     * @return array Thông tin trạng thái
     */
    public function get_connection_status() {
        $status = [
            'erp_configured' => false,
            'erp_working' => false,
            'sample_available' => false,
            'last_test' => get_option('vinapet_last_api_test', 0)
        ];
        
        // Kiểm tra ERP configuration
        if ($this->erp_client && $this->erp_client->is_configured()) {
            $status['erp_configured'] = true;
            
            // Test API connection
            try {
                $test_response = $this->erp_client->get_products(['limit' => 1]);
                $status['erp_working'] = ($test_response !== false);
            } catch (Exception $e) {
                $status['erp_working'] = false;
            }
        }
        
        // Kiểm tra sample data
        $status['sample_available'] = ($this->sample_provider !== null);
        
        // Cập nhật thời gian test cuối
        update_option('vinapet_last_api_test', time());
        
        return $status;
    }
    
    /**
     * Log hoạt động để debug
     * 
     * @param string $message Thông điệp
     * @param string $level Mức độ (info, warning, error)
     */
    private function log($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("VinaPet Product Manager [{$level}]: {$message}");
        }
    }
    
    /**
     * Validate tham số đầu vào
     * 
     * @param array $params Tham số cần validate
     * @return array Tham số đã được validate
     */
    public function validate_params($params) {
        $validated = [];
        
        // Validate search
        if (isset($params['search'])) {
            $validated['search'] = sanitize_text_field($params['search']);
            if (strlen($validated['search']) < 2) {
                unset($validated['search']);
            }
        }
        
        // Validate sort
        $allowed_sorts = ['default', 'name-asc', 'name-desc', 'price-asc', 'price-desc', 'newest'];
        if (isset($params['sort']) && in_array($params['sort'], $allowed_sorts)) {
            $validated['sort'] = $params['sort'];
        }
        
        // Validate category
        if (isset($params['category'])) {
            $validated['category'] = sanitize_text_field($params['category']);
        }
        
        // Validate pagination
        if (isset($params['page'])) {
            $validated['page'] = max(1, intval($params['page']));
        }
        
        if (isset($params['limit'])) {
            $validated['limit'] = max(1, min(100, intval($params['limit']))); // Giới hạn từ 1-100
        }
        
        return $validated;
    }
}