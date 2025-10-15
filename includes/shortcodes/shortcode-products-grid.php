<?php

/**
 * Shortcode: Products Grid - Hiển thị danh sách sản phẩm
 * File: includes/shortcodes/shortcode-products-grid.php
 * 
 * Usage: [vinapet_products limit="8" category="" sort="default"]
 * 
 * CSS được nhúng trực tiếp trong shortcode để dễ quản lý
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Products_Shortcode
{

    private $data_manager;
    private static $css_printed = false;

    public function __construct()
    {
        add_shortcode('vinapet_products', [$this, 'render']);
    }

    /**
     * In CSS một lần duy nhất
     */
    private function print_css()
    {
        if (self::$css_printed) {
            return '';
        }

        self::$css_printed = true;

        ob_start();
?>
        <style>
            /* ========== PRODUCTS GRID SHORTCODE CSS ========== */
            :root {
                --vp-primary: #2E86AB;
                --vp-secondary: #19457B;
                --vp-white: #ffffff;
                --vp-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
                --vp-shadow-hover: 0 8px 25px rgba(0, 0, 0, 0.15);
                --vp-radius: 12px;
                --vp-transition: all 0.3s ease;
            }

            .vinapet-products-grid-wrapper {
                width: 100%;
                padding: 0;
                margin: 0 auto;
            }

            .vinapet-products-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 30px;
                width: 100%;
            }

            /* Product Card */
            .vinapet-product-card {
                position: relative;
                width: 100%;
                height: 360px;
                background: white;
                border-radius: 8px;
                border: 4px solid rgba(217, 216, 220, 1);
                overflow: hidden;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                font-family: 'SVN-Gilroy', sans-serif;
            }

            .vinapet-product-card:hover {
                transform: translateY(-8px);
                box-shadow: var(--vp-shadow-hover);
            }

            /* Image */
            .product-image-wrapper {
                position: relative;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }

            .product-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center;
                transition: transform 0.5s ease;
            }

            .vinapet-product-card:hover .product-image {
                transform: scale(1.05);
            }

            /* Overlay */
            .product-overlay {
                position: absolute;
                bottom: 12px;
                left: 12px;
                right: 12px;
                background: rgba(6, 29, 57, 0.8);
                border-radius: var(--vp-radius);
                border: 1px solid rgba(255, 255, 255, 0.8);
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 4px;
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                transition: background 0.3s ease;
                min-height: 82px;
            }

            .vinapet-product-card:hover .product-overlay {
                background: rgba(6, 29, 57, 1);
            }

            /* Title & Arrow */
            .product-title-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
            }

            .vinapet-product-card .product-title {
                font-size: 18px;
                font-weight: 700;
                color: var(--vp-white);
                margin: 0;
                line-height: 1.3;
            }

            .vinapet-product-card .title-text {
                flex: 1;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .arrow-icon {
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                color: var(--vp-white);
                opacity: 0;
                transform: translateX(10px);
                transition: opacity 0.3s ease, transform 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .vinapet-product-card:hover .arrow-icon {
                opacity: 1;
                transform: translateX(0);
            }

            /* Description */
            .vinapet-product-card .product-description {
                font-size: 14px;
                color: rgba(255, 255, 255, 0.9);
                margin: 4px 0 0 0;
                line-height: 1.4;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            /* No Products */
            .vinapet-no-products {
                text-align: center;
                padding: 60px 20px;
                color: #666;
                font-size: 16px;
            }

            /* Animation */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .vinapet-product-card {
                animation: fadeInUp 0.6s ease forwards;
            }

            .vinapet-product-card:nth-child(1) {
                animation-delay: 0.05s;
            }

            .vinapet-product-card:nth-child(2) {
                animation-delay: 0.10s;
            }

            .vinapet-product-card:nth-child(3) {
                animation-delay: 0.15s;
            }

            .vinapet-product-card:nth-child(4) {
                animation-delay: 0.20s;
            }

            .vinapet-product-card:nth-child(5) {
                animation-delay: 0.25s;
            }

            .vinapet-product-card:nth-child(6) {
                animation-delay: 0.30s;
            }

            .vinapet-product-card:nth-child(7) {
                animation-delay: 0.35s;
            }

            .vinapet-product-card:nth-child(8) {
                animation-delay: 0.40s;
            }

            /* ========== RESPONSIVE ========== */

            /* Desktop (1200px - 1599px) */
            @media (min-width: 1200px) and (max-width: 1599px) {
                .vinapet-products-grid {
                    grid-template-columns: repeat(4, 1fr);
                    gap: 24px;
                }

                .vinapet-product-card {
                    height: 360px;
                }
            }

            /* Tablet Landscape (992px - 1199px) - 3 columns */
            @media (min-width: 992px) and (max-width: 1199px) {
                .vinapet-products-grid {
                    grid-template-columns: repeat(3, 1fr);
                    gap: 20px;
                }

                .vinapet-product-card {
                    height: 340px;
                }

                .vinapet-product-card .product-title {
                    font-size: 16px;
                }

                .vinapet-product-card .product-description {
                    font-size: 13px;
                }
            }

            /* Tablet Portrait (768px - 991px) - 2 columns */
            @media (min-width: 768px) and (max-width: 991px) {
                .vinapet-products-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 20px;
                }

                .vinapet-product-card {
                    height: 320px;
                }

                .vinapet-product-card .product-title {
                    font-size: 16px;
                }

                .vinapet-product-card .product-description {
                    font-size: 13px;
                }
            }

            /* Mobile Landscape (576px - 767px) - 2 columns */
            @media (min-width: 576px) and (max-width: 767px) {
                .vinapet-products-grid {
                    grid-template-columns: repeat(2, 1fr);
                    gap: 16px;
                }

                .vinapet-product-card {
                    height: 280px;
                }

                .product-overlay {
                    padding: 12px;
                    min-height: 70px;
                }

                .vinapet-product-card .product-title {
                    font-size: 14px;
                }

                .vinapet-product-card .product-description {
                    font-size: 12px;
                }

                .arrow-icon {
                    width: 20px;
                    height: 20px;
                }
            }

            /* Mobile Portrait (< 576px) - 1 column */
            @media (max-width: 575px) {
                .vinapet-products-grid {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }

                .vinapet-product-card {
                    height: 300px;
                }

                .product-overlay {
                    padding: 12px;
                    min-height: 70px;
                }

                .vinapet-product-card .product-title {
                    font-size: 16px;
                }

                .vinapet-product-card .product-description {
                    font-size: 13px;
                }
            }

            /* Accessibility */
            .vinapet-product-card:focus-visible {
                outline: 3px solid var(--vp-primary);
                outline-offset: 3px;
            }

            /* Reduced Motion */
            @media (prefers-reduced-motion: reduce) {

                .vinapet-product-card,
                .product-image,
                .arrow-icon,
                .product-overlay {
                    animation: none !important;
                    transition: none !important;
                }
            }

            /* High Contrast */
            @media (prefers-contrast: high) {
                .vinapet-product-card {
                    border: 3px solid #000;
                }

                .product-overlay {
                    background: rgba(0, 0, 0, 0.95);
                    border: 2px solid #fff;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Render shortcode
     */
    public function render($atts)
    {
        // Parse attributes
        $atts = shortcode_atts([
            'limit' => 8,
            'category' => '',
            'sort' => 'default',
            'exclude' => ''
        ], $atts, 'vinapet_products');

        // Khởi tạo Product Data Manager
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $this->data_manager = new Product_Data_Manager();

        // Chuẩn bị params cho Product_Data_Manager
        $params = [
            'key' => sanitize_text_field($atts['category']),
            'sort' => sanitize_text_field($atts['sort']),
            'search' => '', // Shortcode không có search
            'page' => 1
        ];

        // Lấy dữ liệu sản phẩm
        $result = $this->data_manager->get_products($params);
        $products = $result['products'] ?? [];

        // Filter exclude nếu có
        if (!empty($atts['exclude'])) {
            $exclude_code = sanitize_text_field($atts['exclude']);
            $products = array_filter($products, function ($product) use ($exclude_code) {
                $product_code = $product['Ma_SP'] ?? $product['ProductID'] ?? $product['item_code'] ?? '';
                return $product_code !== $exclude_code;
            });
            // Re-index array sau khi filter
            $products = array_values($products);
        }

        // Apply limit - QUAN TRỌNG: Slice array theo limit
        $limit = intval($atts['limit']);
        if ($limit > 0 && count($products) > $limit) {
            $products = array_slice($products, 0, $limit);
        }

        // Bắt đầu output
        ob_start();

        // In CSS (chỉ 1 lần)
        echo $this->print_css();

        if (empty($products)) {
            echo $this->render_no_products();
        } else {
        ?>
            <div class="vinapet-products-grid-wrapper">
                <div class="vinapet-products-grid">
                    <?php foreach ($products as $product): ?>
                        <?php echo $this->render_product_card($product); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php
        }

        return ob_get_clean();
    }

    /**
     * Render một product card
     */
    private function render_product_card($product)
    {
        // Extract dữ liệu từ ERPNext
        $product_name = $product['Ten_SP'] ?? $product['item_name'] ?? 'Sản phẩm';
        $product_desc = strip_tags($product['Mo_ta_ngan'] ?? $product['short_description'] ?? '');
        $product_image = $this->get_product_image($product);
        $product_url = $this->generate_product_url($product);

        ob_start();
        ?>

        <div class="vinapet-product-card" onclick="window.location.href='<?php echo esc_url($product_url); ?>'">
            <div class="product-image-wrapper">
                <img
                    src="<?php echo esc_url($product_image); ?>"
                    alt="<?php echo esc_attr($product_name); ?>"
                    class="product-image"
                    loading="lazy" />
            </div>

            <div class="product-overlay">
                <div class="product-title-container">
                    <h3 class="product-title title-text">
                        <?php echo esc_html($product_name); ?>
                    </h3>
                    <div class="arrow-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>

                <?php if (!empty($product_desc)): ?>
                    <p class="product-description">
                        <?php echo esc_html($product_desc); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

    <?php
        return ob_get_clean();
    }

    /**
     * Lấy hình ảnh sản phẩm với fallback
     */
    private function get_product_image($product)
    {
        $image = $product['Thumbnail_File'] ?? $product['image'] ?? '';

        if (empty($image)) {
            return get_template_directory_uri() . '/assets/images/placeholder.jpg';
        }

        // Nếu là relative URL, thêm ERPNext domain
        if (strpos($image, 'http') !== 0) {
            $erp_url = get_option('erp_api_url');
            if (!empty($erp_url)) {
                $image = trailingslashit($erp_url) . ltrim($image, '/');
            }
        }

        return $image;
    }

    /**
     * Tạo URL sản phẩm
     */
    private function generate_product_url($product)
    {
        if (class_exists('Smart_URL_Router')) {
            return Smart_URL_Router::generate_product_url($product);
        }

        // Fallback
        $product_code = $product['Ma_SP'] ?? $product['ProductID'] ?? $product['item_code'] ?? '';
        return home_url('/san-pham/' . urlencode($product_code));
    }

    /**
     * Render thông báo khi không có sản phẩm
     */
    private function render_no_products()
    {
        ob_start();
    ?>
        <div class="vinapet-no-products">
            <p>Hiện tại chưa có sản phẩm nào.</p>
        </div>
<?php
        return ob_get_clean();
    }
}

// Khởi tạo shortcode
new VinaPet_Products_Shortcode();
