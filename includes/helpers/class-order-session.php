<?php
/**
 * File: includes/helpers/class-order-session.php
 * Enhanced VinaPet_Order_Session - Support both normal và mix orders
 */

class VinaPet_Order_Session { private static $instance = null;
    private $order_key = 'vinapet_order_data';
    private $mix_key = 'vinapet_mix_data';
    private $checkout_key = 'vinapet_checkout_data';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            if (!session_id()) session_start();
        }
        return self::$instance;
    }
    
    // ================================
    // STORE METHODS
    // ================================
    
    /**
     * Store single product order data
     */
    public function store_order($product_code, $variant = '', $additional_data = []) {
        $_SESSION[$this->order_key] = [
            'order_type' => 'normal',
            'product_code' => sanitize_text_field($product_code),
            'variant' => sanitize_text_field($variant),
            'user_id' => get_current_user_id(),
            'timestamp' => time(),
            'additional' => $additional_data
        ];
        
        // Clear mix data khi store order
        $this->clear_mix();
    }
    
    /**
     * Store mix products data
     */
    public function store_mix($mix_data) {
        $_SESSION[$this->mix_key] = [
            'order_type' => 'mix',
            'products' => $mix_data['products'] ?? [],
            'options' => $mix_data['options'] ?? [],
            'quantities' => $mix_data['quantities'] ?? [],
            'pricing' => $mix_data['pricing'] ?? [],
            'user_id' => get_current_user_id(),
            'timestamp' => time()
        ];
        
        // Clear order data khi store mix
        $this->clear_order();
    }
    
    /**
     * Store checkout form data
     */
    public function store_checkout($checkout_form_data) {
        $_SESSION[$this->checkout_key] = [
            'packaging_design' => $checkout_form_data['packaging_design'] ?? '',
            'delivery_timeline' => $checkout_form_data['delivery_timeline'] ?? '',
            'shipping_method' => $checkout_form_data['shipping_method'] ?? '',
            'contact_info' => $checkout_form_data['contact_info'] ?? [],
            'timestamp' => time()
        ];
    }
    
    // ================================
    // GET METHODS
    // ================================
    
    /**
     * Get checkout data - Detect type và return unified format
     */
    public function get_checkout_data() {
        // Check timeout (30 phút)
        $this->cleanup_expired();
        
        $order_data = $this->get_order();
        $mix_data = $this->get_mix();
        $checkout_data = $this->get_checkout_form();
        
        if ($mix_data) {
            return $this->format_mix_checkout($mix_data, $checkout_data);
        } elseif ($order_data) {
            return $this->format_order_checkout($order_data, $checkout_data);
        }
        
        return $this->get_fallback_data();
    }
    
    /**
     * Get raw order data
     */
    public function get_order() {
        return $_SESSION[$this->order_key] ?? null;
    }
    
    /**
     * Get raw mix data
     */
    public function get_mix() {
        return $_SESSION[$this->mix_key] ?? null;
    }
    
    /**
     * Get checkout form data
     */
    public function get_checkout_form() {
        return $_SESSION[$this->checkout_key] ?? null;
    }
    
    // ================================
    // CLEAR METHODS
    // ================================
    
    public function clear_order() {
        unset($_SESSION[$this->order_key]);
    }
    
    public function clear_mix() {
        unset($_SESSION[$this->mix_key]);
    }
    
    public function clear_checkout() {
        unset($_SESSION[$this->checkout_key]);
    }
    
    public function clear_all() {
        $this->clear_order();
        $this->clear_mix();
        $this->clear_checkout();
    }
    
    // ================================
    // UTILITY METHODS
    // ================================
    
    /**
     * Format mix data for checkout display
     */
    private function format_mix_checkout($mix_data, $checkout_data = null) {
        return [
            'type' => 'mix',
            'source' => 'mix_page',
            'title' => 'Đơn hàng tùy chỉnh',
            'products' => $this->format_mix_products($mix_data['products']),
            'total_quantity' => $this->calculate_mix_quantity($mix_data),
            'estimated_price' => $this->calculate_mix_price($mix_data),
            'price_per_kg' => $this->calculate_mix_price_per_kg($mix_data),
            'details' => $mix_data['options'] ?? [],
            'checkout_form' => $checkout_data,
            'raw_data' => $mix_data
        ];
    }
    
    /**
     * Format order data for checkout display
     */
    private function format_order_checkout($order_data, $checkout_data = null) {
        $product_info = $this->get_product_info($order_data['product_code']);
        
        return [
            'type' => 'normal',
            'source' => 'order_page',
            'title' => 'Đơn hàng',
            'product_code' => $order_data['product_code'],
            'product_name' => $product_info['product_name'] ?? 'Sản phẩm',
            'variant' => $order_data['variant'],
            'quantity' => $order_data['additional']['quantity'] ?? 1000,
            'packaging' => $order_data['additional']['packaging'] ?? '',
            'total_quantity' => $order_data['additional']['quantity'] ?? 1000,
            'estimated_price' => $this->calculate_order_price($order_data),
            'price_per_kg' => $this->calculate_order_price_per_kg($order_data),
            'checkout_form' => $checkout_data,
            'raw_data' => $order_data
        ];
    }
    
    /**
     * Cleanup expired sessions
     */
    private function cleanup_expired($timeout = 1800) { // 30 phút
        $keys = [$this->order_key, $this->mix_key, $this->checkout_key];
        
        foreach ($keys as $key) {
            if (isset($_SESSION[$key]) && isset($_SESSION[$key]['timestamp'])) {
                if ((time() - $_SESSION[$key]['timestamp']) > $timeout) {
                    unset($_SESSION[$key]);
                }
            }
        }
    }
    
    /**
     * Get fallback data for development
     */
    private function get_fallback_data() {
        return [
            'type' => 'normal',
            'source' => 'fallback',
            'title' => 'Đơn hàng mẫu',
            'product_code' => 'CAT-TRE-001',
            'product_name' => 'Cát tre cao cấp',
            'variant' => 'com',
            'quantity' => 1000,
            'total_quantity' => 1000,
            'estimated_price' => 50000000,
            'price_per_kg' => 50000,
            'checkout_form' => null,
            'raw_data' => null
        ];
    }
    
    // Helper calculation methods
    private function format_mix_products($products) {
        // Format mix products cho display
        $formatted = [];
        foreach ($products as $key => $product) {
            if (!empty($product['name'])) {
                $formatted[] = [
                    'name' => $product['name'],
                    'percentage' => $product['percentage'] ?? 0,
                    'details' => $product['details'] ?? []
                ];
            }
        }
        return $formatted;
    }
    
    private function calculate_mix_quantity($mix_data) {
        return $mix_data['quantities']['total'] ?? 10000;
    }
    
    private function calculate_mix_price($mix_data) {
        return $mix_data['pricing']['total'] ?? 500000000;
    }
    
    private function calculate_mix_price_per_kg($mix_data) {
        $total = $this->calculate_mix_price($mix_data);
        $quantity = $this->calculate_mix_quantity($mix_data);
        return $quantity > 0 ? ($total / $quantity) : 0;
    }
    
    private function calculate_order_price($order_data) {
        $quantity = $order_data['additional']['quantity'] ?? 1000;
        $price_per_kg = $order_data['additional']['price_per_kg'] ?? 50000;
        return $quantity * $price_per_kg;
    }
    
    private function calculate_order_price_per_kg($order_data) {
        return $order_data['additional']['price_per_kg'] ?? 50000;
    }
    
    private function get_product_info($product_code) {
        // Tích hợp với product data manager
        if (class_exists('Product_Data_Manager')) {
            $manager = new Product_Data_Manager();
            $response = $manager->get_product($product_code);
            error_log(print_r($response, true));
            return $response['product'] ?? ['name' => 'Sản phẩm'];
        }
        return ['name' => 'Sản phẩm'];
    }
}