<?php
/**
 * Sample Orders Data
 * Tạo dữ liệu mẫu cho đơn hàng đang tạo yêu cầu
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get sample orders for "Đang tạo yêu cầu" tab
 */
function vinapet_get_sample_creating_orders($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // In thực tế, dữ liệu này sẽ được lấy từ database hoặc ERPNext API
    // Hiện tại chỉ return dữ liệu mẫu
    return array(
        array(
            'id' => 'VP_001_' . $user_id,
            'title' => 'Cát Tre',
            'created_at' => '18:55 ngày 1/7/2025',
            'status' => 'creating_request',
            'items' => array(
                array(
                    'name' => 'Cát tre: Mùi cốm - Màu xanh non',
                    'quantity' => '1000 kg',
                    'details' => array(
                        'Túi 8 Biên PA / PE Hút Chân Không'
                    )
                ),
                array(
                    'name' => 'Cát tre: Mùi sen - Màu hồng',
                    'quantity' => '3000 kg',
                    'details' => array(
                        'Bao Tái Dữa + Lót 1 lớp PE'
                    )
                )
            ),
            'summary' => array(
                'total_quantity' => '4000 kg',
                'packaging' => 'Vui lòng chọn',
                'delivery_time' => 'Vui lòng chọn',
                'shipping' => 'Vui lòng chọn',
                'total_price' => '171,800,000 đ',
                'price_per_kg' => '42,950 đ/kg'
            ),
            'raw_data' => array(
                'total_quantity_kg' => 4000,
                'total_price_vnd' => 171800000,
                'price_per_kg_vnd' => 42950
            )
        ),
        array(
            'id' => 'VP_002_' . $user_id,
            'title' => 'Cát Tre + Cát đất sét',
            'created_at' => '8:42 ngày 29/6/2025',
            'status' => 'creating_request',
            'items' => array(
                array(
                    'name' => 'Cát tre',
                    'quantity' => 'tỷ lệ 75%',
                    'details' => array(
                        'Màu xanh non',
                        'Mùi trà xanh', 
                        'Túi Jumbo 1 tấn'
                    )
                ),
                array(
                    'name' => 'Cát đất sét',
                    'quantity' => 'tỷ lệ 25%',
                    'details' => array()
                )
            ),
            'summary' => array(
                'total_quantity' => '10,000 kg',
                'packaging' => 'Nhà máy thiết kế đan giãn: 0 đ',
                'delivery_time' => 'Trung bình (15 - 30 ngày): 0 đ',
                'shipping' => 'Nhà máy hỗ trợ vận chuyển: 3,000,000 đ',
                'total_price' => '253,000,000 đ',
                'price_per_kg' => '25,300 đ/kg'
            ),
            'raw_data' => array(
                'total_quantity_kg' => 10000,
                'total_price_vnd' => 253000000,
                'price_per_kg_vnd' => 25300
            )
        )
    );
}

/**
 * Get order by ID for current user
 */
function vinapet_get_user_order_by_id($order_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $orders = vinapet_get_sample_creating_orders($user_id);
    
    foreach ($orders as $order) {
        if ($order['id'] === $order_id) {
            return $order;
        }
    }
    
    return false;
}

/**
 * Create order from checkout data
 * Function này sẽ được gọi từ checkout page để tạo đơn hàng mới
 */
function vinapet_create_order_from_checkout($checkout_data, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return new WP_Error('user_not_logged_in', 'Người dùng chưa đăng nhập');
    }
    
    // Validate checkout data
    if (empty($checkout_data)) {
        return new WP_Error('invalid_data', 'Dữ liệu không hợp lệ');
    }
    
    // Generate unique order ID
    $order_id = 'VP_' . time() . '_' . $user_id;
    
    // Tạo order data structure
    $order_data = array(
        'id' => $order_id,
        'user_id' => $user_id,
        'title' => $checkout_data['title'] ?? 'Đơn hàng mới',
        'created_at' => current_time('H:i \n\g\à\y d/m/Y'),
        'status' => 'creating_request',
        'items' => $checkout_data['items'] ?? array(),
        'summary' => $checkout_data['summary'] ?? array(),
        'raw_data' => $checkout_data['raw_data'] ?? array(),
        'checkout_data' => $checkout_data
    );
    
    // Trong thực tế, sẽ lưu vào database hoặc gửi đến ERPNext
    // Hiện tại chỉ lưu vào user meta để demo
    $user_orders = get_user_meta($user_id, 'vinapet_creating_orders', true) ?: array();
    $user_orders[] = $order_data;
    update_user_meta($user_id, 'vinapet_creating_orders', $user_orders);
    
    // Log activity
    if (function_exists('vinapet_log_account_activity')) {
        vinapet_log_account_activity($user_id, 'order_created', 'Order ID: ' . $order_id);
    }
    
    return $order_data;
}

/**
 * Cancel order
 */
function vinapet_cancel_user_order($order_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return new WP_Error('user_not_logged_in', 'Người dùng chưa đăng nhập');
    }
    
    // Get user orders
    $user_orders = get_user_meta($user_id, 'vinapet_creating_orders', true) ?: array();
    
    // Find and remove the order
    foreach ($user_orders as $key => $order) {
        if ($order['id'] === $order_id) {
            // Move to cancelled orders (in real app)
            $cancelled_orders = get_user_meta($user_id, 'vinapet_cancelled_orders', true) ?: array();
            $order['status'] = 'cancelled';
            $order['cancelled_at'] = current_time('mysql');
            $cancelled_orders[] = $order;
            update_user_meta($user_id, 'vinapet_cancelled_orders', $cancelled_orders);
            
            // Remove from creating orders
            unset($user_orders[$key]);
            update_user_meta($user_id, 'vinapet_creating_orders', array_values($user_orders));
            
            // Log activity
            if (function_exists('vinapet_log_account_activity')) {
                vinapet_log_account_activity($user_id, 'order_cancelled', 'Order ID: ' . $order_id);
            }
            
            return true;
        }
    }
    
    return new WP_Error('order_not_found', 'Không tìm thấy đơn hàng');
}

/**
 * Get user's creating orders (mix sample + real data)
 */
function vinapet_get_user_creating_orders($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return array();
    }
    
    // Get real orders from user meta
    $real_orders = get_user_meta($user_id, 'vinapet_creating_orders', true) ?: array();
    
    // Get sample orders for demo
    $sample_orders = vinapet_get_sample_creating_orders($user_id);
    
    // Merge and sort by creation time
    $all_orders = array_merge($sample_orders, $real_orders);
    
    // Sort by created_at (most recent first)
    usort($all_orders, function($a, $b) {
        $time_a = strtotime($a['created_at']);
        $time_b = strtotime($b['created_at']);
        return $time_b - $time_a;
    });
    
    return $all_orders;
}

/**
 * Update order status
 */
function vinapet_update_order_status($order_id, $new_status, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $valid_statuses = array(
        'creating_request',
        'sent_request', 
        'vinapet_quote',
        'completed',
        'cancelled'
    );
    
    if (!in_array($new_status, $valid_statuses)) {
        return new WP_Error('invalid_status', 'Trạng thái không hợp lệ');
    }
    
    // Get user orders from current status
    $current_orders = get_user_meta($user_id, 'vinapet_creating_orders', true) ?: array();
    
    // Find and update the order
    foreach ($current_orders as $key => $order) {
        if ($order['id'] === $order_id) {
            $order['status'] = $new_status;
            $order['updated_at'] = current_time('mysql');
            
            // Move to appropriate meta key based on status
            $meta_key = 'vinapet_' . $new_status . '_orders';
            $target_orders = get_user_meta($user_id, $meta_key, true) ?: array();
            $target_orders[] = $order;
            update_user_meta($user_id, $meta_key, $target_orders);
            
            // Remove from current orders
            unset($current_orders[$key]);
            update_user_meta($user_id, 'vinapet_creating_orders', array_values($current_orders));
            
            // Log activity
            if (function_exists('vinapet_log_account_activity')) {
                vinapet_log_account_activity($user_id, 'order_status_updated', 
                    'Order ID: ' . $order_id . ' -> ' . $new_status);
            }
            
            return true;
        }
    }
    
    return new WP_Error('order_not_found', 'Không tìm thấy đơn hàng');
}

/**
 * Get order summary for display
 */
function vinapet_format_order_summary($order) {
    $summary = array(
        'total_items' => count($order['items']),
        'total_quantity' => $order['summary']['total_quantity'] ?? 'N/A',
        'packaging' => $order['summary']['packaging'] ?? 'Chưa chọn',
        'delivery_time' => $order['summary']['delivery_time'] ?? 'Chưa chọn', 
        'shipping' => $order['summary']['shipping'] ?? 'Chưa chọn',
        'total_price' => $order['summary']['total_price'] ?? 'N/A',
        'price_per_kg' => $order['summary']['price_per_kg'] ?? 'N/A'
    );
    
    return $summary;
}

/**
 * Check if user can manage order
 */
function vinapet_user_can_manage_order($order_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Admin can manage all orders
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Check if order belongs to user
    $order = vinapet_get_user_order_by_id($order_id, $user_id);
    return $order !== false;
}