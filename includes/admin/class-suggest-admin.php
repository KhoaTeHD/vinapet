<?php

/**
 * VinaPet Suggest Admin Settings
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

class Vinapet_Suggest_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {

        // Footer submenu
        add_submenu_page(
            'vinapet-settings',
            'Cài đặt đề xuất',
            'Cài đặt đề xuất',
            'manage_options',
            'vinapet-suggest-settings',
            array($this, 'suggest_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('vinapet_suggest_settings', 'vinapet_suggest_info', array(
            'sanitize_callback' => array($this, 'sanitize_suggest_info')
        ));
    }

    /**
     * Suggest settings page
     */
    public function suggest_settings_page()
    {
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['vinapet_suggest_nonce'], 'vinapet_suggest_settings')) {
            $suggest_data = array(
                'product_2' => array(
                    'suggest_1' => sanitize_text_field($_POST['product_2_suggest_1'] ?? ''),
                    'suggest_2' => sanitize_text_field($_POST['product_2_suggest_2'] ?? ''),
                    'suggest_3' => sanitize_text_field($_POST['product_2_suggest_3'] ?? '')
                ),
                'product_3' => array(
                    'suggest_1' => sanitize_text_field($_POST['product_3_suggest_1'] ?? ''),
                    'suggest_2' => sanitize_text_field($_POST['product_3_suggest_2'] ?? ''),
                    'suggest_3' => sanitize_text_field($_POST['product_3_suggest_3'] ?? '')
                )
            );

            update_option('vinapet_suggest_products', $suggest_data);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Đã lưu!</strong> Cài đặt đề xuất đã được cập nhật thành công.</p></div>';
        }

        // Lấy danh sách sản phẩm
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $data_manager = new Product_Data_Manager();
        $all_products_response = $data_manager->get_products();
        $all_products = isset($all_products_response['products']) ? $all_products_response['products'] : [];

        $suggest_data = get_option('vinapet_suggest_products', array(
            'product_2' => array('suggest_1' => '', 'suggest_2' => '', 'suggest_3' => ''),
            'product_3' => array('suggest_1' => '', 'suggest_2' => '', 'suggest_3' => '')
        ));
?>
        <div class="wrap">
            <h1><span class="dashicons dashicons-admin-settings"></span> Cài đặt đề xuất VinaPet</h1>
            <p>Cấu hình sản phẩm đề xuất cho trang Mix hạt.</p>

            <form method="post" action="" class="vinapet-suggest-form">
                <?php wp_nonce_field('vinapet_suggest_settings', 'vinapet_suggest_nonce'); ?>

                <h2>Đề xuất cho Sản phẩm 2</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <tr>
                                <th scope="row">
                                    <label for="product_2_suggest_<?php echo $i; ?>">Sản phẩm đề xuất <?php echo $i; ?></label>
                                </th>
                                <td>
                                    <select name="product_2_suggest_<?php echo $i; ?>" id="product_2_suggest_<?php echo $i; ?>" class="regular-text">
                                        <option value="">-- Chọn sản phẩm --</option>
                                        <?php foreach ($all_products as $product): ?>
                                            <option value="<?php echo esc_attr($product['ProductID']); ?>"
                                                <?php selected($suggest_data['product_2']['suggest_' . $i], $product['ProductID']); ?>>
                                                <?php echo esc_html($product['Ten_SP']); ?> (<?php echo esc_html($product['ProductID']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <h2>Đề xuất cho Sản phẩm 3</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <tr>
                                <th scope="row">
                                    <label for="product_3_suggest_<?php echo $i; ?>">Sản phẩm đề xuất <?php echo $i; ?></label>
                                </th>
                                <td>
                                    <select name="product_3_suggest_<?php echo $i; ?>" id="product_3_suggest_<?php echo $i; ?>" class="regular-text">
                                        <option value="">-- Chọn sản phẩm --</option>
                                        <?php foreach ($all_products as $product): ?>
                                            <option value="<?php echo esc_attr($product['ProductID']); ?>"
                                                <?php selected($suggest_data['product_3']['suggest_' . $i], $product['ProductID']); ?>>
                                                <?php echo esc_html($product['Ten_SP']); ?> (<?php echo esc_html($product['ProductID']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <?php submit_button('Lưu cài đặt', 'primary', 'submit', false); ?>
            </form>
        </div>

        <style>
            .vinapet-suggest-form h2 {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }

            .vinapet-suggest-form h2:first-of-type {
                margin-top: 20px;
                border-top: none;
            }
        </style>
<?php
    }

    /**
     * Sanitize suggest info
     */
    public function sanitize_suggest_info($input)
    {
        $sanitized = array(
            'product_2' => array(),
            'product_3' => array()
        );

        for ($i = 1; $i <= 3; $i++) {
            $sanitized['product_2']['suggest_' . $i] = sanitize_text_field($input['product_2']['suggest_' . $i] ?? '');
            $sanitized['product_3']['suggest_' . $i] = sanitize_text_field($input['product_3']['suggest_' . $i] ?? '');
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'vinapet-') === false) {
            return;
        }

        wp_enqueue_style('vinapet-admin', VINAPET_THEME_URI . '/assets/css/admin.css', array(), VINAPET_VERSION);
    }
}

new Vinapet_Suggest_Admin();
