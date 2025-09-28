<?php
/**
 * Smart URL Router với Hash Verification
 */
class Smart_URL_Router {
    
    private static $salt = 'vinapet_secret_2025'; // secret riêng
    
    /**
     * Tạo URL từ product data
     */
    public static function generate_product_url($product) {
        $product_code = $product['Ma_SP'] ?? $product['item_code'] ?? '';
        $product_name = $product['Ten_SP'] ?? $product['item_name'] ?? '';
        
        if (empty($product_code)) {
            return home_url('/san-pham/');
        }
        
        $slug = sanitize_title($product_name);
        $hash = self::generate_hash($product_code);
        
        return home_url("/san-pham/{$slug}-{$hash}/");
    }
    
    /**
     * Parse URL và tìm product
     */
    public static function resolve_product($url_segment) {
        $parts = self::parse_url_segment($url_segment);
        
        if (!$parts) {
            return null;
        }
        
        // Strategy 1: Hash verification (fastest)
        $product_code = self::find_by_hash($parts['hash']);
        if ($product_code) {
            return $product_code;
        }
        
        // Strategy 2: Slug search (fallback)
        return self::find_by_slug($parts['slug']);
    }
    
    /**
     * Generate verification hash
     */
    private static function generate_hash($product_code) {
        return substr(md5($product_code . self::$salt), 0, 8);
    }
    
    /**
     * Parse URL segment
     */
    private static function parse_url_segment($segment) {
        // Format: slug-hash8
        if (preg_match('/^(.+)-([a-f0-9]{8})$/', $segment, $matches)) {
            return [
                'slug' => $matches[1],
                'hash' => $matches[2],
                'full' => $segment
            ];
        }
        
        // Fallback: treat as slug only
        return [
            'slug' => $segment,
            'hash' => '',
            'full' => $segment
        ];
    }
    
    /**
     * Find product by hash verification
     */
    private static function find_by_hash($hash) {
        if (empty($hash)) {
            return null;
        }
        
        // Cache key cho hash lookup
        $cache_key = "product_hash_{$hash}";
        $cached = wp_cache_get($cache_key, 'vinapet_products');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Load product manager
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $manager = new Product_Data_Manager();
        
        // Get all products và verify hash
        $result = $manager->get_products(['limit' => 500]);
        $products = $result['products'] ?? [];
        
        foreach ($products as $product) {
            $product_code = $product['Ma_SP'] ?? $product['item_code'] ?? '';
            if ($product_code && self::generate_hash($product_code) === $hash) {
                // Cache result
                wp_cache_set($cache_key, $product_code, 'vinapet_products', 300);
                return $product_code;
            }
        }
        
        return null;
    }
    
    /**
     * Find product by slug search
     */
    private static function find_by_slug($slug) {
        if (empty($slug)) {
            return null;
        }
        
        // Load product manager
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $manager = new Product_Data_Manager();
        
        // Search by name similarity
        $result = $manager->get_products(['search' => str_replace('-', ' ', $slug)]);
        $products = $result['products'] ?? [];
        
        if (!empty($products)) {
            // Return first match
            $first_product = $products[0];
            return $first_product['Ma_SP'] ?? $first_product['item_code'] ?? null;
        }
        
        return null;
    }
}