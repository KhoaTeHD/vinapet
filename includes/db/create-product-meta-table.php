<?php

/**
 * File: includes/db/create-product-meta-table.php
 * Tạo bảng lưu trữ meta data tùy chỉnh cho sản phẩm
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tạo bảng wp_vinapet_product_meta
 */
function vinapet_create_product_meta_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'vinapet_product_meta';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_code varchar(50) NOT NULL,
        
        -- TAB 1: MÔ TẢ
        custom_description longtext DEFAULT NULL,
        custom_short_desc text DEFAULT NULL,
        use_custom_desc tinyint(1) DEFAULT 1,
        
        -- TAB 2: SEO
        seo_title varchar(70) DEFAULT NULL,
        seo_description varchar(200) DEFAULT NULL,
        seo_og_image varchar(255) DEFAULT NULL,

        -- HÌNH ẢNH TÙY CHỈNH (4 ảnh)
        custom_image_1 varchar(255) DEFAULT NULL,
        custom_image_2 varchar(255) DEFAULT NULL,
        custom_image_3 varchar(255) DEFAULT NULL,
        custom_image_4 varchar(255) DEFAULT NULL,
        
        -- GENERAL
        display_order int(11) DEFAULT 0,
        is_featured tinyint(1) DEFAULT 0,
        status enum('active','hidden') DEFAULT 'active',
        
        -- TRACKING
        created_by bigint(20) DEFAULT NULL,
        updated_by bigint(20) DEFAULT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        
        PRIMARY KEY (id),
        UNIQUE KEY product_code (product_code),
        KEY idx_status (status),
        KEY idx_featured (is_featured)
    ) {$charset_collate};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Log kết quả
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
        error_log('VinaPet: Product meta table created successfully');
        return true;
    } else {
        error_log('VinaPet: Failed to create product meta table');
        return false;
    }
}

/**
 * Hook khi theme được activate
 */
function vinapet_activate_product_meta()
{
    vinapet_create_product_meta_table();

    // Set flag để không tạo lại
    update_option('vinapet_product_meta_table_version', '1.0');
}
add_action('after_switch_theme', 'vinapet_activate_product_meta');

/**
 * Kiểm tra và tạo table nếu chưa có
 */
function vinapet_check_product_meta_table()
{
    $version = get_option('vinapet_product_meta_table_version');

    if (!$version) {
        vinapet_create_product_meta_table();
        update_option('vinapet_product_meta_table_version', '1.0');
    }
}
add_action('admin_init', 'vinapet_check_product_meta_table');
