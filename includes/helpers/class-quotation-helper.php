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

class VinaPet_Quotation_Helper
{

    /**
     * Validate quotation data with detailed error logging
     * ✅ CẢI THIỆN: Thêm chi tiết log để debug dễ dàng
     * 
     * @param array $data Quotation data cần validate
     * @return bool True nếu valid, false nếu invalid
     */
    public static function validate_quotation_data($data)
    {
        error_log('=== VinaPet Quotation Validation START ===');

        // 1. Validate top-level structure
        if (!is_array($data)) {
            error_log('❌ Validation failed: Data is not an array');
            return false;
        }

        error_log('✓ Data is array');
        error_log('Data keys: ' . implode(', ', array_keys($data)));

        // 2. Check required top-level fields
        $required_fields = ['customer', 'items'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                error_log("❌ Validation failed: Missing required field '{$field}'");
                return false;
            }
            error_log("✓ Field '{$field}' exists");
        }

        // 3. Validate customer email
        if (!is_email($data['customer'])) {
            error_log("❌ Validation failed: Invalid customer email '{$data['customer']}'");
            return false;
        }
        error_log("✓ Customer email valid: {$data['customer']}");

        // 4. Validate items array
        if (!is_array($data['items'])) {
            error_log('❌ Validation failed: Items is not an array');
            return false;
        }

        if (empty($data['items'])) {
            error_log('❌ Validation failed: Items array is empty');
            return false;
        }

        $items_count = count($data['items']);
        error_log("✓ Items array valid with {$items_count} items");

        // 5. Validate each item
        foreach ($data['items'] as $index => $item) {
            error_log("--- Validating item {$index} ---");

            if (!is_array($item)) {
                error_log("❌ Item {$index}: Not an array");
                return false;
            }

            // Check required item fields
            $required_item_fields = ['item_code', 'qty', 'uom', 'rate'];
            foreach ($required_item_fields as $field) {
                if (!isset($item[$field])) {
                    error_log("❌ Item {$index}: Missing required field '{$field}'");
                    error_log("Item data: " . json_encode($item));
                    return false;
                }
            }

            // Validate data types
            if (empty($item['item_code']) || !is_string($item['item_code'])) {
                error_log("❌ Item {$index}: Invalid item_code '{$item['item_code']}'");
                return false;
            }

            if (!is_numeric($item['qty']) || $item['qty'] <= 0) {
                error_log("❌ Item {$index}: Invalid qty '{$item['qty']}'");
                return false;
            }

            if (empty($item['uom']) || !is_string($item['uom'])) {
                error_log("❌ Item {$index}: Invalid uom '{$item['uom']}'");
                return false;
            }

            if (!is_numeric($item['rate']) || $item['rate'] < 0) {
                error_log("❌ Item {$index}: Invalid rate '{$item['rate']}'");
                return false;
            }

            error_log("✓ Item {$index} valid: {$item['item_code']} x {$item['qty']}");
        }

        error_log('=== VinaPet Quotation Validation PASSED ===');
        return true;
    }

    /**
     * Sanitize quotation data
     */
    public static function sanitize_quotation_data($data)
    {
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
    public static function generate_quotation_summary($quotation_data)
    {
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
    public static function format_for_log($quotation_data, $response = null)
    {
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
