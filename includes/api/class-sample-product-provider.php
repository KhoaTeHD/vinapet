<?php
/**
 * Class cung cấp dữ liệu sản phẩm mẫu
 *
 * @package VinaPet
 */

class Sample_Product_Provider {
    /**
     * Lấy danh sách sản phẩm với các tham số lọc
     */
    public function get_products($params = []) {
        // Nhúng file dữ liệu mẫu
        require_once get_template_directory() . '/includes/data/sample-products.php';
        
        $products = filter_sample_products($params);
        $total_products = count(get_sample_products());
        
        if (!empty($params['category'])) {
            $filtered_by_category = array_filter(get_sample_products(), function($product) use ($params) {
                return $product['item_group'] === $params['category'];
            });
            $total_products = count($filtered_by_category);
        }
        
        if (!empty($params['search'])) {
            $search = strtolower($params['search']);
            $filtered_by_search = array_filter(get_sample_products(), function($product) use ($search) {
                return stripos(strtolower($product['item_name']), $search) !== false || 
                       stripos(strtolower($product['description']), $search) !== false;
            });
            $total_products = count($filtered_by_search);
        }
        
        return [
            'data' => $products,
            'total' => $total_products
        ];
    }
    
    /**
     * Lấy chi tiết sản phẩm theo mã
     */
    public function get_product($product_code) {
        // Nhúng file dữ liệu mẫu
        require_once get_template_directory() . '/includes/data/sample-products.php';
        
        $product = get_sample_product_by_code($product_code);
        
        if ($product) {
            return [
                'success' => true,
                'data' => $product
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Product not found'
            ];
        }
    }
    
    /**
     * Lấy danh sách nhóm sản phẩm
     */
    public function get_product_categories() {
        // Nhúng file dữ liệu mẫu
        require_once get_template_directory() . '/includes/data/sample-products.php';
        
        return [
            'data' => get_sample_product_categories()
        ];
    }
}