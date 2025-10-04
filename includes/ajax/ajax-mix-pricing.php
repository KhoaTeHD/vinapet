<?php
/**
 * AJAX Handlers cho Mix Dynamic Pricing
 * 
 * File: includes/ajax/ajax-mix-pricing.php
 * Thêm vào functions.php: require_once get_template_directory() . '/includes/ajax/ajax-mix-pricing.php';
 */

/**
 * AJAX: Lấy giá cho 1 sản phẩm theo khối lượng
 * Sử dụng get_product_price_detail() đã có sẵn
 */
add_action('wp_ajax_get_mix_product_price', 'vinapet_ajax_get_mix_product_price');
add_action('wp_ajax_nopriv_get_mix_product_price', 'vinapet_ajax_get_mix_product_price');

function vinapet_ajax_get_mix_product_price() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    $product_code = sanitize_text_field($_POST['product_code'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 10000);
    
    if (empty($product_code)) {
        wp_send_json_error(['message' => 'Mã sản phẩm không được để trống']);
        return;
    }
    
    // Sử dụng Product_Data_Manager đã có
    require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
    $data_manager = new Product_Data_Manager();
    
    // Lấy giá chi tiết từ ERPNext
    $price_response = $data_manager->get_product_price_detail($product_code);
    
    if (empty($price_response['price_detail'])) {
        wp_send_json_error([
            'message' => 'Không lấy được giá sản phẩm',
            'source' => $price_response['source'] ?? 'none'
        ]);
        return;
    }
    
    // Format response theo structure price_detail[1] (tiered pricing)
    $price_tiers = $price_response['price_detail'][1] ?? [];
    
    // Tìm giá phù hợp dựa trên quantity
    $matched_price = vinapet_find_matching_price_tier($price_tiers, $quantity);
    
    if (!$matched_price) {
        wp_send_json_error(['message' => 'Không tìm thấy giá phù hợp với số lượng']);
        return;
    }
    
    wp_send_json_success([
        'product_code' => $product_code,
        'quantity' => $quantity,
        'price_per_kg' => floatval($matched_price['value']),
        'total_price' => floatval($matched_price['value']) * $quantity,
        'tier_name' => $matched_price['title'] ?? '',
        'min_qty' => $matched_price['min_qty'] ?? 0,
        'currency' => 'VND',
        'all_tiers' => $price_tiers // Trả về tất cả tiers để client có thể cache
    ]);
}

/**
 * Helper: Tìm price tier phù hợp với quantity
 */
function vinapet_find_matching_price_tier($price_tiers, $quantity) {
    if (empty($price_tiers)) {
        return null;
    }
    
    // Sort tiers theo min_qty giảm dần
    usort($price_tiers, function($a, $b) {
        return ($b['min_qty'] ?? 0) - ($a['min_qty'] ?? 0);
    });
    
    // Tìm tier đầu tiên có min_qty <= quantity
    foreach ($price_tiers as $tier) {
        $min_qty = floatval($tier['min_qty'] ?? 0);
        if ($quantity >= $min_qty) {
            return $tier;
        }
    }
    
    // Fallback: trả về tier có min_qty thấp nhất
    return end($price_tiers);
}

/**
 * AJAX: Tính giá cho toàn bộ mix order
 */
add_action('wp_ajax_calculate_mix_price', 'vinapet_ajax_calculate_mix_price');
add_action('wp_ajax_nopriv_calculate_mix_price', 'vinapet_ajax_calculate_mix_price');

function vinapet_ajax_calculate_mix_price() {
    check_ajax_referer('vinapet_nonce', 'nonce');
    
    $products = json_decode(stripslashes($_POST['products'] ?? '[]'), true);
    $total_quantity = intval($_POST['total_quantity'] ?? 10000);
    
    if (empty($products)) {
        wp_send_json_error(['message' => 'Danh sách sản phẩm trống']);
        return;
    }
    
    // Validate percentages
    $total_percentage = array_sum(array_column($products, 'percentage'));
    if (abs($total_percentage - 100) > 0.01) { // Cho phép sai số nhỏ
        wp_send_json_error([
            'message' => 'Tổng phần trăm phải bằng 100%',
            'total_percentage' => $total_percentage
        ]);
        return;
    }
    
    require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
    $data_manager = new Product_Data_Manager();
    
    $breakdown = [];
    $total_price = 0;
    $has_error = false;
    
    foreach ($products as $product) {
        $percentage = floatval($product['percentage']);
        $product_quantity = ($total_quantity * $percentage) / 100;
        
        // Lấy giá cho sản phẩm này
        $price_response = $data_manager->get_product_price_detail($product['code']);
        
        if (empty($price_response['price_detail'])) {
            $has_error = true;
            error_log("Mix Pricing Error: Cannot get price for {$product['code']}");
            continue;
        }
        
        $price_tiers = $price_response['price_detail'][1] ?? [];
        $matched_price = vinapet_find_matching_price_tier($price_tiers, $product_quantity);
        
        if (!$matched_price) {
            $has_error = true;
            continue;
        }
        
        $price_per_kg = floatval($matched_price['value']);
        $subtotal = $price_per_kg * $product_quantity;
        
        $breakdown[] = [
            'code' => $product['code'],
            'name' => $product['name'] ?? $product['code'],
            'percentage' => $percentage,
            'quantity' => round($product_quantity, 2),
            'price_per_kg' => $price_per_kg,
            'subtotal' => round($subtotal),
            'tier_name' => $matched_price['title'] ?? ''
        ];
        
        $total_price += $subtotal;
    }
    
    if ($has_error) {
        wp_send_json_error(['message' => 'Không thể tính giá cho một số sản phẩm']);
        return;
    }
    
    wp_send_json_success([
        'total_price' => round($total_price),
        'price_per_kg' => round($total_price / $total_quantity),
        'total_quantity' => $total_quantity,
        'breakdown' => $breakdown,
        'currency' => 'VND',
        'calculated_at' => current_time('mysql')
    ]);
}

/**
 * Localize script data cho mix page
 */
add_action('wp_enqueue_scripts', 'vinapet_localize_mix_ajax', 20);

function vinapet_localize_mix_ajax() {
    if (is_page_template('page-templates/page-mix.php')) {
        // Dùng tên 'vinapet_ajax' để tương thích với code cũ
        wp_localize_script('vinapet-mix-products', 'vinapet_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_nonce'),
            'default_quantity' => 10000,
            'currency_symbol' => 'đ',
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        // Cũng thêm vinapet_mix_ajax để backward compatible
        wp_localize_script('vinapet-mix-products', 'vinapet_mix_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vinapet_nonce'),
            'default_quantity' => 10000,
            'currency_symbol' => 'đ',
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }
}