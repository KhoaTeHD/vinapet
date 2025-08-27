<?php
/**
 * VinaPet Footer AJAX Handler
 * Thêm vào functions.php hoặc file riêng biệt
 * 
 * @package VinaPet
 */

/**
 * Localize footer scripts with AJAX data
 */
function vinapet_footer_localize_scripts() {
    wp_localize_script('vinapet-footer', 'vinapet_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vinapet_footer_nonce'),
        'theme_url' => VINAPET_THEME_URI,
        'is_user_logged_in' => is_user_logged_in(),
        'user_id' => get_current_user_id(),
    ));
}
add_action('wp_enqueue_scripts', 'vinapet_footer_localize_scripts');

/**
 * Handle footer event tracking
 */
function vinapet_handle_footer_event_tracking() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'vinapet_footer_nonce')) {
        wp_send_json_error('Invalid nonce');
        return;
    }
    
    $event_type = sanitize_text_field($_POST['event_type']);
    $event_data = json_decode(stripslashes($_POST['event_data']), true);
    
    // Add common data
    $event_data['user_id'] = get_current_user_id();
    $event_data['session_id'] = session_id() ?: wp_generate_uuid4();
    $event_data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $event_data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $event_data['page_url'] = wp_get_referer() ?: home_url();
    
    // Track to database
    $tracked = vinapet_track_footer_event_to_db($event_type, $event_data);
    
    // Send to ERPNext if enabled
    if (vinapet_is_erpnext_enabled()) {
        vinapet_send_footer_event_to_erpnext($event_type, $event_data);
    }
    
    if ($tracked) {
        wp_send_json_success('Event tracked successfully');
    } else {
        wp_send_json_error('Failed to track event');
    }
}
add_action('wp_ajax_vinapet_track_footer_event', 'vinapet_handle_footer_event_tracking');
add_action('wp_ajax_nopriv_vinapet_track_footer_event', 'vinapet_handle_footer_event_tracking');

/**
 * Track footer event to database
 */
function vinapet_track_footer_event_to_db($event_type, $event_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vinapet_footer_events';
    
    // Create table if not exists
    vinapet_create_footer_events_table();
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'user_id' => $event_data['user_id'] ?? 0,
            'session_id' => $event_data['session_id'] ?? '',
            'ip_address' => $event_data['ip_address'] ?? '',
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%d', '%s', '%s', '%s')
    );
    
    return $result !== false;
}

/**
 * Create footer events table
 */
function vinapet_create_footer_events_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vinapet_footer_events';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_type varchar(50) NOT NULL,
        event_data longtext,
        user_id bigint(20),
        session_id varchar(255),
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY event_type (event_type),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Send footer event to ERPNext
 */
function vinapet_send_footer_event_to_erpnext($event_type, $event_data) {
    $erpnext_settings = vinapet_get_erpnext_settings();
    
    if (empty($erpnext_settings['api_url']) || empty($erpnext_settings['api_key'])) {
        return false;
    }
    
    // Prepare ERPNext document
    $doc_data = array(
        'doctype' => 'Website Event Log', // Custom DocType in ERPNext
        'event_type' => $event_type,
        'event_source' => 'Website Footer',
        'event_data' => json_encode($event_data),
        'user_id' => $event_data['user_id'] ?? 0,
        'session_id' => $event_data['session_id'] ?? '',
        'timestamp' => $event_data['timestamp'] ?? current_time('c'),
        'page_url' => $event_data['page_url'] ?? '',
        'ip_address' => $event_data['ip_address'] ?? ''
    );
    
    // Send to ERPNext
    $api_url = trailingslashit($erpnext_settings['api_url']) . 'api/resource/Website Event Log';
    
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'token ' . $erpnext_settings['api_key'] . ':' . $erpnext_settings['api_secret'],
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($doc_data),
        'timeout' => 15
    ));
    
    if (is_wp_error($response)) {
        error_log('VinaPet Footer ERPNext Error: ' . $response->get_error_message());
        return false;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    return $response_code >= 200 && $response_code < 300;
}

/**
 * Get footer analytics data
 */
function vinapet_get_footer_analytics($days = 7) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vinapet_footer_events';
    $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            event_type,
            COUNT(*) as event_count,
            COUNT(DISTINCT session_id) as unique_sessions,
            DATE(created_at) as event_date
        FROM {$table_name} 
        WHERE created_at >= %s 
        GROUP BY event_type, DATE(created_at)
        ORDER BY created_at DESC
    ", $date_from));
    
    return $results;
}

/**
 * Admin page for footer analytics
 */
function vinapet_footer_analytics_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $analytics_data = vinapet_get_footer_analytics(30);
    
    ?>
    <div class="wrap">
        <h1>Footer Analytics - VinaPet</h1>
        
        <div class="footer-analytics-dashboard">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Total Events</th>
                        <th>Unique Sessions</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($analytics_data) : ?>
                        <?php foreach ($analytics_data as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row->event_type); ?></td>
                                <td><?php echo esc_html($row->event_count); ?></td>
                                <td><?php echo esc_html($row->unique_sessions); ?></td>
                                <td><?php echo esc_html($row->event_date); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4">Chưa có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <style>
    .footer-analytics-dashboard {
        margin-top: 20px;
    }
    
    .footer-analytics-dashboard table {
        background: white;
    }
    </style>
    <?php
}

/**
 * Add footer analytics to admin menu
 */
function vinapet_footer_analytics_admin_menu() {
    add_submenu_page(
        'vinapet-settings',
        'Footer Analytics',
        'Footer Analytics',
        'manage_options',
        'vinapet-footer-analytics',
        'vinapet_footer_analytics_page'
    );
}
add_action('admin_menu', 'vinapet_footer_analytics_admin_menu');

/**
 * Clean up old footer events (run daily)
 */
function vinapet_cleanup_footer_events() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vinapet_footer_events';
    $days_to_keep = apply_filters('vinapet_footer_events_retention_days', 90);
    $date_cutoff = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE created_at < %s",
        $date_cutoff
    ));
    
    if ($deleted !== false) {
        error_log("VinaPet Footer: Cleaned up {$deleted} old event records");
    }
}

/**
 * Schedule cleanup task
 */
function vinapet_schedule_footer_cleanup() {
    if (!wp_next_scheduled('vinapet_footer_cleanup_events')) {
        wp_schedule_event(time(), 'daily', 'vinapet_footer_cleanup_events');
    }
}
add_action('wp', 'vinapet_schedule_footer_cleanup');
add_action('vinapet_footer_cleanup_events', 'vinapet_cleanup_footer_events');

/**
 * Create tables on theme activation
 */
function vinapet_footer_activation() {
    vinapet_create_footer_events_table();
    vinapet_schedule_footer_cleanup();
}
add_action('after_switch_theme', 'vinapet_footer_activation');