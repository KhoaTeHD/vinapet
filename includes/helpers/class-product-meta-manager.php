<?php

/**
 * File: includes/helpers/class-product-meta-manager.php
 * Quản lý meta data tùy chỉnh cho sản phẩm
 */

if (!defined('ABSPATH')) {
    exit;
}

class Product_Meta_Manager
{

    private $table_name;
    private $cache_prefix = 'vinapet_meta_';
    private $cache_time = 1800; // 30 phút

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vinapet_product_meta';
    }

    /**
     * Lấy meta của một sản phẩm
     * @param string $product_code
     * @return array|null
     */
    public function get_product_meta($product_code)
    {
        if (empty($product_code)) {
            return null;
        }

        // Check cache
        $cache_key = $this->cache_prefix . md5($product_code);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE product_code = %s AND status = 'active'",
            $product_code
        );

        $result = $wpdb->get_row($sql, ARRAY_A);

        if ($result) {
            set_transient($cache_key, $result, $this->cache_time);
        }

        return $result;
    }

    /**
     * Lưu/Cập nhật meta
     * @param string $product_code
     * @param array $data
     * @return bool|int
     */
    public function save_product_meta($product_code, $data)
    {
        if (empty($product_code)) {
            return false;
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $now = current_time('mysql');

        // Kiểm tra đã tồn tại chưa
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE product_code = %s",
            $product_code
        ));

        // Prepare data
        $save_data = [
            'product_code' => $product_code,
            'custom_description' => isset($data['custom_description']) ? wp_kses_post($data['custom_description']) : null,
            'custom_short_desc' => isset($data['custom_short_desc']) ? sanitize_textarea_field($data['custom_short_desc']) : null,
            'use_custom_desc' => isset($data['use_custom_desc']) ? (int)$data['use_custom_desc'] : 1,
            'seo_title' => isset($data['seo_title']) ? sanitize_text_field($data['seo_title']) : null,
            'seo_description' => isset($data['seo_description']) ? sanitize_textarea_field($data['seo_description']) : null,
            'seo_og_image' => isset($data['seo_og_image']) ? esc_url_raw($data['seo_og_image']) : null,

            'custom_image_1' => isset($data['custom_image_1']) ? esc_url_raw($data['custom_image_1']) : null,
            'custom_image_2' => isset($data['custom_image_2']) ? esc_url_raw($data['custom_image_2']) : null,
            'custom_image_3' => isset($data['custom_image_3']) ? esc_url_raw($data['custom_image_3']) : null,
            'custom_image_4' => isset($data['custom_image_4']) ? esc_url_raw($data['custom_image_4']) : null,

            'display_order' => isset($data['display_order']) ? (int)$data['display_order'] : 0,
            'is_featured' => isset($data['is_featured']) ? (int)$data['is_featured'] : 0,
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'updated_by' => $user_id,
            'updated_at' => $now
        ];

        if ($exists) {
            // Update
            $result = $wpdb->update(
                $this->table_name,
                $save_data,
                ['product_code' => $product_code],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s'
                ],
                ['%s']
            );
        } else {
            // Insert
            $save_data['created_by'] = $user_id;
            $save_data['created_at'] = $now;

            $result = $wpdb->insert(
                $this->table_name,
                $save_data,
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s'
                ]
            );
        }

        // Clear cache
        $this->clear_cache($product_code);

        return $result !== false;
    }

    /**
     * Xóa meta (soft delete - chuyển status = hidden)
     * @param string $product_code
     * @return bool
     */
    public function delete_product_meta($product_code)
    {
        if (empty($product_code)) {
            return false;
        }

        global $wpdb;

        // Xóa hẳn khỏi DB
        $result = $wpdb->delete(
            $this->table_name,
            ['product_code' => $product_code],
            ['%s']
        );

        // Clear cache
        $this->clear_cache($product_code);

        return $result !== false;
    }

    /**
     * Kiểm tra sản phẩm có meta không
     * @param string $product_code
     * @return bool
     */
    public function has_meta($product_code)
    {
        $meta = $this->get_product_meta($product_code);
        return !empty($meta);
    }

    /**
     * Lấy danh sách sản phẩm có meta
     * @return array
     */
    public function get_all_products_with_meta()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT product_code, seo_title, is_featured, created_at, updated_at 
             FROM {$this->table_name} 
             WHERE status = 'active' 
             ORDER BY updated_at DESC",
            ARRAY_A
        );

        return $results ? $results : [];
    }

    /**
     * Clear cache
     * @param string|null $product_code
     */
    public function clear_cache($product_code = null)
    {
        if ($product_code) {
            $cache_key = $this->cache_prefix . md5($product_code);
            delete_transient($cache_key);
        } else {
            // Clear all meta cache
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%',
                '_transient_timeout_' . $this->cache_prefix . '%'
            ));
        }
    }

    /**
     * Lấy số lượng sản phẩm có meta
     * @return int
     */
    public function count_products_with_meta()
    {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active'"
        );

        return (int)$count;
    }
}
