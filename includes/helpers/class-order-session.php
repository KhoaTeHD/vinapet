<?php
/**
 * File: includes/helpers/class-order-session.php
 */

class VinaPet_Order_Session {
    private static $instance = null;
    private $key = 'vinapet_order';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            if (!session_id()) session_start();
        }
        return self::$instance;
    }
    
    public function store($product_code, $variant = '', $order_type = 'normal') {
        $_SESSION[$this->key] = [
            'product_code' => sanitize_text_field($product_code),
            'variant' => sanitize_text_field($variant),
            'order_type' => sanitize_text_field($order_type), // 'normal' hoặc 'mix'
            'user_id' => get_current_user_id(),
            'time' => time()
        ];
    }
    
    public function get() {
        if (!isset($_SESSION[$this->key])) return false;
        $data = $_SESSION[$this->key];
        
        // Check timeout 30 phút
        if ((time() - $data['time']) > 1800) {
            $this->clear();
            return false;
        }
        
        return $data;
    }
    
    public function clear() {
        unset($_SESSION[$this->key]);
    }
}