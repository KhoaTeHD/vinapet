<?php
/**
 * VinaPet Footer Initialization
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Footer_Init {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('init', array($this, 'init_company_info'), 5);
        add_action('wp_head', array($this, 'add_preconnects'));
    }
    
    /**
     * Enqueue footer assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'vinapet-footer', 
            VINAPET_THEME_URI . '/assets/css/footer.css', 
            array(), 
            VINAPET_VERSION
        );
    }
    
    /**
     * Initialize company info with default data
     */
    public function init_company_info() {
        $default_company_info = array(
            'name' => 'CÔNG TY CP ĐẦU TƯ VÀ SẢN XUẤT VINAPET',
            'factory_address' => 'Nhà máy công nghệ cao Vinapet - Lô CN 3.1 - Khu CN Phú Nghĩa - Chương Mỹ, Hà Nội',
            'office_address' => 'Văn phòng: Tòa nhà Cung Tri Thức - Số 1 Tôn Thất Thuyết - Cầu Giấy, Hà Nội',
            'tax_code' => '0110064359',
            'phone1' => '(+84) 911 818 518',
            'phone2' => '(+84) 2471008333',
            'email' => 'support@vinapet.com.vn',
            'facebook_url' => 'https://www.facebook.com/p/Vinapet-100094599485921/',
            'website' => 'https://vinapet.com.vn'
        );
        
        if (!get_option('vinapet_company_info')) {
            add_option('vinapet_company_info', $default_company_info);
        }
    }
    
    /**
     * Add preconnect links for performance
     */
    public function add_preconnects() {
        echo '<link rel="preconnect" href="https://connect.facebook.net">' . "\n";
        echo '<link rel="dns-prefetch" href="//connect.facebook.net">' . "\n";
    }
}

// Initialize
new VinaPet_Footer_Init();