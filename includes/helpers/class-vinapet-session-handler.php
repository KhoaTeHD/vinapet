<?php
/**
 * Simple Session Order Handler
 * 
 * Lưu file: includes/helpers/class-simple-session-handler.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Simple_Session {
    
    private static $instance = null;
    private $session_key = 'vinapet_order_data';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        if (!session_id()) {
            session_start();
        }
    }
    
    /**
     * Lưu order data vào session
     */
    public function store_order($product_code, $variant = '') {
        $_SESSION[$this->session_key] = array(
            'product_code' => sanitize_text_field($product_code),
            'variant' => sanitize_text_field($variant),
            'timestamp' => time()
        );
        return true;
    }
    
    /**
     * Lấy order data từ session
     */
    public function get_order() {
        if (!isset($_SESSION[$this->session_key])) {
            return false;
        }
        
        $data = $_SESSION[$this->session_key];
        
        // Kiểm tra timeout 30 phút
        if ((time() - $data['timestamp']) > 1800) {
            $this->clear_order();
            return false;
        }
        
        return $data;
    }
    
    /**
     * Xóa order data
     */
    public function clear_order() {
        if (isset($_SESSION[$this->session_key])) {
            unset($_SESSION[$this->session_key]);
        }
        return true;
    }
}