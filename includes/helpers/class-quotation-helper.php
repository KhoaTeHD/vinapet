<?php
/**
 * File: includes/helpers/class-quotation-helper.php
 * VinaPet Quotation Helper - Helper class cho việc mapping dữ liệu quotation
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Quotation_Helper {
    
    /**
     * Validate quotation data structure
     */
    public static function validate_quotation_data($data) {
        $required_fields = ['customer', 'items'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Validate items
        if (!is_array($data['items']) || empty($data['items'])) {
            return false;
        }
        
        foreach ($data['items'] as $item) {
            $required_item_fields = ['item_code', 'qty', 'rate'];
            foreach ($required_item_fields as $field) {
                if (!isset($item[$field])) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize quotation data
     */
    public static function sanitize_quotation_data($data) {
        $sanitized = [
            'customer' => sanitize_email($data['customer']),
            'shipping_cost' => intval($data['shipping_cost'] ?? 0),
            'desired_delivery_time_amount' => intval($data['desired_delivery_time_amount'] ?? 0),
            'delivery_method' => sanitize_text_field($data['delivery_method'] ?? ''),
            'date_to_receive' => intval($data['date_to_receive'] ?? 15),
            'items' => []
        ];
        
        // Add optional fields
        if (!empty($data['ship_method'])) {
            $sanitized['ship_method'] = sanitize_text_field($data['ship_method']);
        }
        
        // Sanitize items
        foreach ($data['items'] as $item) {
            $sanitized_item = [
                'item_code' => sanitize_text_field($item['item_code']),
                'qty' => intval($item['qty']),
                'uom' => sanitize_text_field($item['uom'] ?? 'Nos'),
                'rate' => intval($item['rate']),
                'is_free_item' => intval($item['is_free_item'] ?? 0),
                'additional_notes' => sanitize_text_field($item['additional_notes'] ?? '')
            ];
            
            // Add optional item fields
            if (isset($item['item_name'])) {
                $sanitized_item['item_name'] = sanitize_text_field($item['item_name']);
            }
            
            if (isset($item['packet_item'])) {
                $sanitized_item['packet_item'] = sanitize_text_field($item['packet_item']);
            }
            
            if (isset($item['mix_percent'])) {
                $sanitized_item['mix_percent'] = floatval($item['mix_percent']);
            }
            
            $sanitized['items'][] = $sanitized_item;
        }
        
        return $sanitized;
    }
    
    /**
     * Generate quotation summary
     */
    public static function generate_quotation_summary($quotation_data) {
        $summary = [
            'total_items' => count($quotation_data['items']),
            'total_qty' => 0,
            'total_amount' => 0,
            'is_mix_order' => false
        ];
        
        foreach ($quotation_data['items'] as $item) {
            $summary['total_qty'] += $item['qty'];
            $summary['total_amount'] += ($item['qty'] * $item['rate']);
            
            if (isset($item['mix_percent']) && $item['mix_percent'] > 0) {
                $summary['is_mix_order'] = true;
            }
        }
        
        // Add shipping and delivery costs
        $summary['total_amount'] += $quotation_data['shipping_cost'] ?? 0;
        $summary['total_amount'] += $quotation_data['desired_delivery_time_amount'] ?? 0;
        
        return $summary;
    }
    
    /**
     * Format quotation for logging
     */
    public static function format_for_log($quotation_data, $response = null) {
        $log_data = [
            'timestamp' => current_time('mysql'),
            'customer' => $quotation_data['customer'] ?? 'N/A',
            'items_count' => count($quotation_data['items'] ?? []),
            'total_amount' => 0,
            'delivery_method' => $quotation_data['delivery_method'] ?? 'N/A'
        ];
        
        // Calculate total amount
        foreach ($quotation_data['items'] as $item) {
            $log_data['total_amount'] += ($item['qty'] * $item['rate']);
        }
        
        if ($response) {
            $log_data['api_response'] = [
                'status' => $response['status'] ?? 'unknown',
                'quotation_id' => $response['data']['name'] ?? 'N/A'
            ];
        }
        
        return $log_data;
    }
}

