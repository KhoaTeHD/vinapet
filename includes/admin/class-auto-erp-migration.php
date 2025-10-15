<?php

/**
 * Auto ERP Migration
 * Tự động sync users khi active theme - Đơn giản, tự động, chỉ có admin notice
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Auto_Migration
{

    const OPTION_MIGRATED = 'vinapet_erp_auto_migrated';
    const BATCH_SIZE = 50; // Sync 50 users/lần

    private $helper;

    public function __construct()
    {
        // Hook chạy khi active theme
        add_action('after_switch_theme', array($this, 'schedule_migration'));

        //  Cleanup khi deactive theme
        add_action('switch_theme', array($this, 'cleanup_on_deactivate'));

        // Cron job xử lý migration
        add_action('vinapet_run_migration', array($this, 'process_migration'));

        // Admin notice
        //add_action('admin_notices', array($this, 'show_notice'));

        $this->helper = new VinaPet_Customer_Sync_Helper();
    }

    /**
     *  Cleanup khi deactive theme
     */
    public function cleanup_on_deactivate()
    {
        // Khi switch theme (deactivate Vinapet)
        error_log('VinaPet: Theme deactivated, resetting migration flag');

        // Xóa flag để có thể chạy lại khi active
        delete_option(self::OPTION_MIGRATED);

        // Clear cron
        $timestamp = wp_next_scheduled('vinapet_run_migration');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vinapet_run_migration');
        }

        // Giữ lại progress data để biết đã sync được bao nhiêu
        // Không xóa: vinapet_migration_total, _processed, _failed
    }

    /**
     * Schedule migration khi active theme
     */
    public function schedule_migration()
    {
        error_log('VinaPet: Theme activated, checking migration...');

        // Check ERP đã config chưa
        if (!$this->is_erp_configured()) {
            error_log('VinaPet Migration: ERP chưa được cấu hình, bỏ qua migration');
            return;
        }

        $unsynced = $this->count_unsynced_users();
        
        if ($unsynced === 0) {
            error_log('VinaPet Migration: Không có users nào cần sync');
            
            // Đánh dấu completed
            if (!get_option(self::OPTION_MIGRATED)) {
                update_option(self::OPTION_MIGRATED, true);
            }
            
            return;
        }

        error_log("VinaPet Migration: Phát hiện {$unsynced} users cần sync, bắt đầu migration...");

        // Lưu thông tin để tracking
        update_option('vinapet_migration_total', $unsynced);
        update_option('vinapet_migration_processed', 0);
        update_option('vinapet_migration_failed', 0);
        update_option('vinapet_migration_start', current_time('mysql'));

        // Reset flag
        delete_option(self::OPTION_MIGRATED);

        // Schedule cron chạy ngay lập tức
        if (!wp_next_scheduled('vinapet_run_migration')) {
            wp_schedule_single_event(time(), 'vinapet_run_migration');
        }
    }

    /**
     * Xử lý migration - chạy theo batch để tránh timeout
     */
    public function process_migration()
    {
        error_log('VinaPet Migration: Batch đang chạy...');

        // Load helper
        if (!class_exists('VinaPet_Customer_Sync_Helper')) {
            require_once VINAPET_THEME_DIR . '/includes/helpers/class-customer-sync-helper.php';
        }

        // Lấy batch users
        $users = $this->get_unsynced_users(self::BATCH_SIZE);

        // Nếu không còn users -> hoàn thành
        if (empty($users)) {
            $this->complete_migration();
            return;
        }

        $success = 0;
        $failed = 0;

        foreach ($users as $user) {
            $result = $this->helper->sync_new_user_to_erp($user->ID);

            if ($result && $result['status'] === 'success') {
                $success++;
            } else {
                $failed++;
                error_log("VinaPet Migration: Failed user {$user->ID} ({$user->user_email})");
            }
        }

        // Update progress
        $processed = (int) get_option('vinapet_migration_processed', 0);
        $total_failed = (int) get_option('vinapet_migration_failed', 0);

        update_option('vinapet_migration_processed', $processed + $success + $failed);
        update_option('vinapet_migration_failed', $total_failed + $failed);

        error_log("VinaPet Migration: Batch hoàn thành - Success: {$success}, Failed: {$failed}");

        // Schedule batch tiếp theo sau 30 giây
        wp_schedule_single_event(time() + 30, 'vinapet_run_migration');
    }

    /**
     * Hoàn thành migration
     */
    private function complete_migration()
    {
        $total = (int) get_option('vinapet_migration_total', 0);
        $processed = (int) get_option('vinapet_migration_processed', 0);
        $failed = (int) get_option('vinapet_migration_failed', 0);

        // Đánh dấu hoàn thành
        update_option(self::OPTION_MIGRATED, true);
        update_option('vinapet_migration_end', current_time('mysql'));

        // Log kết quả
        error_log("VinaPet Migration: HOÀN THÀNH - Total: {$total}, Processed: {$processed}, Failed: {$failed}");
    }

    /**
     * Hiển thị admin notice
     */
    public function show_notice()
    {
        // Chỉ hiện cho admin
        if (!current_user_can('manage_options')) {
            return;
        }

        $migrated = get_option(self::OPTION_MIGRATED);
        $total = (int) get_option('vinapet_migration_total', 0);
        $processed = (int) get_option('vinapet_migration_processed', 0);
        $failed = (int) get_option('vinapet_migration_failed', 0);

        // Notice: Đang chạy
        if (!$migrated && $total > 0) {
            $percentage = $total > 0 ? round(($processed / $total) * 100) : 0;
            $remaining_minutes = ceil(($total - $processed) / self::BATCH_SIZE * 0.5);

?>
            <div class="notice notice-info">
                <p>
                    <strong>🔄 ERP Migration đang chạy...</strong><br>
                    Đã sync: <strong><?php echo $processed; ?>/<?php echo $total; ?></strong> users
                    (<?php echo $percentage; ?>%)
                    <?php if ($failed > 0): ?>
                        - <span style="color: #dc3232;">Lỗi: <?php echo $failed; ?></span>
                    <?php endif; ?>
                </p>
                <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">
                    ℹ️ Quá trình tự động, không cần can thiệp.
                    Còn khoảng <?php echo $remaining_minutes; ?> phút nữa.
                </p>
            </div>
        <?php
            return;
        }

        // Notice: Hoàn thành (hiện 1 lần sau khi xong)
        if ($migrated && $total > 0 && !get_option('vinapet_migration_notice_shown')) {
            $success = $processed - $failed;

        ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✅ ERP Migration hoàn thành!</strong><br>
                    Đã đồng bộ: <strong><?php echo $success; ?>/<?php echo $total; ?></strong> users thành công
                    <?php if ($failed > 0): ?>
                        - <span style="color: #dc3232;"><?php echo $failed; ?> users thất bại</span>
                    <?php endif; ?>
                </p>
                <?php if ($failed > 0): ?>
                    <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">
                        ⚠️ Kiểm tra log để xem chi tiết users thất bại.
                    </p>
                <?php endif; ?>
            </div>
<?php

            // Đánh dấu đã hiện notice
            update_option('vinapet_migration_notice_shown', true);

            // Cleanup sau 7 ngày
            wp_schedule_single_event(time() + (7 * DAY_IN_SECONDS), 'vinapet_cleanup_migration_data');
            add_action('vinapet_cleanup_migration_data', function () {
                delete_option('vinapet_migration_total');
                delete_option('vinapet_migration_processed');
                delete_option('vinapet_migration_failed');
                delete_option('vinapet_migration_start');
                delete_option('vinapet_migration_end');
                delete_option('vinapet_migration_notice_shown');
            });
        }
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Check ERP đã cấu hình chưa
     */
    private function is_erp_configured()
    {
        if (!class_exists('ERP_API_Client')) {
            require_once VINAPET_THEME_DIR . '/includes/api/class-erp-api-client.php';
        }

        $erp_api = new ERP_API_Client();
        return $erp_api->is_configured();
    }

    /**
     * Đếm users chưa sync
     */
    private function count_unsynced_users()
    {
        $args = array(
            'fields' => 'ID',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'erpnext_customer_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'erpnext_customer_id',
                    'value' => '',
                    'compare' => '='
                )
            )
        );

        $users = get_users($args);
        return count($users);
    }

    /**
     * Lấy users chưa sync
     */
    private function get_unsynced_users($limit = 50)
    {
        $args = array(
            'number' => $limit,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'erpnext_customer_id',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'erpnext_customer_id',
                    'value' => '',
                    'compare' => '='
                )
            )
        );

        return get_users($args);
    }
}

// Initialize
new VinaPet_Auto_Migration();
