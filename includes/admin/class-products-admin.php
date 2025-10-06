<?php

/**
 * File: includes/admin/class-products-admin.php
 * Class riêng để quản lý menu Sản phẩm - Tách biệt khỏi settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Products_Admin
{
    private $meta_manager;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_products_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_vinapet_load_erp_products', array($this, 'ajax_load_products'));
        add_action('wp_ajax_vinapet_save_product_meta', array($this, 'ajax_save_product_meta'));
        add_action('wp_ajax_vinapet_delete_product_meta', array($this, 'ajax_delete_product_meta'));

        if (class_exists('Product_Meta_Manager')) {
            $this->meta_manager = new Product_Meta_Manager();
        }
    }

    public function add_products_menu()
    {
        add_menu_page(
            'Quản lý Sản phẩm ERP',
            'Sản phẩm',
            'manage_options',
            'vinapet-products-erp',
            array($this, 'products_page'),
            'dashicons-archive',
            26 // Position sau menu Settings
        );

        add_submenu_page(
            'vinapet-products-erp',
            'Chỉnh sửa Mô tả & SEO',
            'Tùy chỉnh Mô tả',
            'manage_options',
            'vinapet-product-editor',
            array($this, 'product_editor_page')
        );
    }

    public function enqueue_scripts($hook)
    {
        // Script cho trang danh sách
        if ($hook === 'toplevel_page_vinapet-products-erp') {
            wp_enqueue_script('jquery');
        }



        // THÊM MỚI: Script cho trang editor
        if ($hook === 'san-pham_page_vinapet-product-editor') {
            wp_enqueue_editor();
            wp_enqueue_media();

            wp_enqueue_script(
                'vinapet-product-editor',
                get_template_directory_uri() . '/assets/js/admin-product-editor.js',
                array('jquery', 'wp-util'),
                '1.0',
                true
            );

            wp_localize_script('vinapet-product-editor', 'vinapet_editor_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vinapet_product_meta_nonce')
            ));

            wp_enqueue_style(
                'vinapet-product-editor',
                get_template_directory_uri() . '/assets/css/admin-product-editor.css',
                array(),
                '2.0.0'
            );
        }
    }

    /**
     * THÊM MỚI: Trang editor mô tả & SEO
     */
    public function product_editor_page()
    {
        // Lấy product code từ URL
        $product_code = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';

        if (empty($product_code)) {
            echo '<div class="wrap"><h1>Lỗi: Không tìm thấy mã sản phẩm</h1></div>';
            return;
        }

        // Lấy thông tin sản phẩm từ ERP
        require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
        $data_manager = new Product_Data_Manager();
        $product_response = $data_manager->get_product($product_code);

        if (!isset($product_response['product'])) {
            echo '<div class="wrap"><h1>Lỗi: Không tìm thấy sản phẩm</h1></div>';
            return;
        }

        $product = $product_response['product'];
        $product_name = $product['product_name'] ?? 'Sản phẩm';
        $product_price = $product['standard_rate'][0]['price_list_rate'] ?? 0;
        $product_stock = $product['actual_qty'] ?? 0;
        $product_image = $product['thumbnail'] ?? get_template_directory_uri() . '/assets/images/placeholder.jpg';

        // Lấy meta hiện tại (nếu có)
        $meta = $this->meta_manager ? $this->meta_manager->get_product_meta($product_code) : null;

        $custom_description = $meta['custom_description'] ?? '';
        $custom_short_desc = $meta['custom_short_desc'] ?? '';
        // Lấy custom images
        $custom_image_1 = $meta['custom_image_1'] ?? '';
        $custom_image_2 = $meta['custom_image_2'] ?? '';
        $custom_image_3 = $meta['custom_image_3'] ?? '';
        $custom_image_4 = $meta['custom_image_4'] ?? '';

        $seo_title = $meta['seo_title'] ?? '';
        $seo_description = $meta['seo_description'] ?? '';
        $seo_og_image = $meta['seo_og_image'] ?? '';
        $is_featured = isset($meta['is_featured']) ? (bool)$meta['is_featured'] : false;

?>
        <div class="wrap vinapet-product-editor">
            <div class="editor-header">
                <h1>
                    <a href="<?php echo admin_url('admin.php?page=vinapet-products-erp'); ?>" class="page-title-action">
                        ← Quay lại
                    </a>
                    Chỉnh sửa: <?php echo esc_html($product_name); ?>
                </h1>
            </div>

            <input type="hidden" id="product-code" value="<?php echo esc_attr($product_code); ?>">

            <div class="editor-container">
                <!-- Left: Editor -->
                <div class="editor-main">

                    <!-- Thông tin ERP (Readonly) -->
                    <div class="erp-info-box">
                        <h3>📦 Thông tin từ ERPNext</h3>
                        <div class="erp-info-content">
                            <div class="erp-info-item">
                                <img src="<?php echo esc_url($product_image); ?>" alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                            </div>
                            <div class="erp-info-item">
                                <label>Mã sản phẩm:</label>
                                <code><?php echo esc_html($product_code); ?></code>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="editor-tabs">
                        <button class="tab-btn active" data-tab="description">
                            📝 Mô tả
                        </button>
                        <button class="tab-btn" data-tab="seo">
                            🔍 SEO
                        </button>
                    </div>

                    <!-- Tab Content: Mô tả -->
                    <div class="tab-content active" id="tab-description">
                        <h3>Mô tả chi tiết (hiển thị cho khách hàng)</h3>
                        <div>
                            <?php
                            wp_editor($custom_description, 'custom_description', array(
                                'textarea_name' => 'custom_description',
                                'textarea_rows' => 15,
                                'media_buttons' => true,

                                'teeny' => false,
                                'tinymce' => array(
                                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,image,undo,redo',
                                    'toolbar2' => 'alignleft,aligncenter,alignright,forecolor,backcolor,removeformat'
                                )
                            ));
                            ?>
                            <!-- Hình ảnh sản phẩm tùy chỉnh -->
                            <div class="product-images-section" style="margin-top: 30px;">
                                <h3>📷 Hình ảnh sản phẩm (4 ảnh)</h3>
                                <p class="description">4 hình ảnh này sẽ hiển thị trong gallery trang chi tiết sản phẩm, sau thumbnail từ API</p>

                                <div class="images-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 15px;">

                                    <?php for ($i = 1; $i <= 4; $i++):
                                        $img_var = 'custom_image_' . $i;
                                        $img_url = $$img_var;
                                    ?>
                                        <div class="image-upload-box">
                                            <label><strong>Hình <?php echo $i; ?>:</strong></label>
                                            <div class="image-preview-wrapper" style="border: 2px dashed #ddd; border-radius: 8px; padding: 15px; text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                                <input type="hidden" id="custom_image_<?php echo $i; ?>" name="custom_image_<?php echo $i; ?>" value="<?php echo esc_attr($img_url); ?>">
                                                <div id="preview-custom-image-<?php echo $i; ?>" style="width: 100%;">
                                                    <?php if ($img_url): ?>
                                                        <img src="<?php echo esc_url($img_url); ?>" style="max-width: 100%; height: auto; border-radius: 5px;">
                                                    <?php else: ?>
                                                        <p style="color: #999;">Chưa có ảnh</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div style="margin-top: 10px; display: flex; gap: 10px;">
                                                <button type="button" class="button upload-custom-image-btn" data-index="<?php echo $i; ?>">
                                                    📷 Chọn ảnh
                                                </button>
                                                <button type="button" class="button remove-custom-image-btn" data-index="<?php echo $i; ?>" <?php echo empty($img_url) ? 'style="display:none;"' : ''; ?>>
                                                    🗑️ Xóa
                                                </button>
                                            </div>
                                        </div>
                                    <?php endfor; ?>

                                </div>
                            </div>
                        </div>


                        <div style="margin-top: 20px;">
                            <label for="custom_short_desc"><strong>Mô tả ngắn (150-200 ký tự):</strong></label>
                            <textarea id="custom_short_desc" name="custom_short_desc" rows="3" style="width: 100%;" maxlength="200"><?php echo esc_textarea($custom_short_desc); ?></textarea>
                            <p class="description">
                                <span id="short-desc-count">0</span>/200 ký tự
                            </p>
                        </div>
                    </div>

                    <!-- Tab Content: SEO -->
                    <div class="tab-content" id="tab-seo">
                        <h3>🔍 Tối ưu hóa công cụ tìm kiếm (SEO)</h3>

                        <div class="seo-field">
                            <label for="seo_title"><strong>SEO Title (50-60 ký tự):</strong></label>
                            <input type="text" id="seo_title" name="seo_title" value="<?php echo esc_attr($seo_title); ?>" maxlength="70" style="width: 100%;">
                            <p class="description">
                                <span id="seo-title-count">0</span>/60 ký tự |
                                Tiêu đề hiển thị trên Google Search
                            </p>
                        </div>

                        <div class="seo-field">
                            <label for="seo_description"><strong>SEO Description (150-160 ký tự):</strong></label>
                            <textarea id="seo_description" name="seo_description" rows="3" maxlength="200" style="width: 100%;"><?php echo esc_textarea($seo_description); ?></textarea>
                            <p class="description">
                                <span id="seo-desc-count">0</span>/160 ký tự |
                                Mô tả ngắn gọn dưới link trên Google
                            </p>
                        </div>

                        <div class="seo-field">
                            <label><strong>Ảnh đại diện khi share (OG Image):</strong></label>
                            <div class="og-image-wrapper">
                                <input type="hidden" id="seo_og_image" name="seo_og_image" value="<?php echo esc_attr($seo_og_image); ?>">
                                <div id="og-image-preview">
                                    <?php if ($seo_og_image): ?>
                                        <img src="<?php echo esc_url($seo_og_image); ?>" style="max-width: 300px;">
                                    <?php else: ?>
                                        <p style="color: #999;">Chưa có ảnh (sẽ dùng ảnh sản phẩm chính)</p>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="upload-og-image">
                                    📷 Chọn ảnh
                                </button>
                                <button type="button" class="button" id="remove-og-image" <?php echo empty($seo_og_image) ? 'style="display:none;"' : ''; ?>>
                                    🗑️ Xóa ảnh
                                </button>
                            </div>
                            <p class="description">Kích thước đề xuất: 1200x630px</p>
                        </div>

                        <!-- Preview Snippet
                        <div class="seo-preview">
                            <h4>👁️ Xem trước trên Google:</h4>
                            <div class="google-snippet">
                                <div class="snippet-url">https://vinapet.com/san-pham/<?php echo esc_html($product_code); ?></div>
                                <div class="snippet-title" id="preview-title">
                                    <?php echo $seo_title ? esc_html($seo_title) : esc_html($product_name); ?>
                                </div>
                                <div class="snippet-desc" id="preview-desc">
                                    <?php echo $seo_description ? esc_html($seo_description) : 'Mô tả sản phẩm sẽ hiển thị ở đây...'; ?>
                                </div>
                            </div>
                        </div> -->
                    </div>

                    <!-- Action Buttons -->
                    <div class="editor-actions">
                        <button type="button" class="button button-primary button-large" id="save-meta">
                            💾 Lưu thay đổi
                        </button>
                        <!-- <button type="button" class="button button-large" id="preview-product">
                            👁️ Xem trước
                        </button> -->
                        <?php if ($meta): ?>
                            <button type="button" class="button button-link-delete" id="delete-meta">
                                🗑️ Xóa tùy chỉnh (quay về ERP)
                            </button>
                        <?php endif; ?>
                        <span class="spinner" id="save-spinner"></span>
                    </div>
                </div>

                <!-- Right: Settings Sidebar -->
                <div class="editor-sidebar">
                    <!-- <div class="sidebar-box">
                        <h3>⚙️ Cài đặt</h3>
                        <label>
                            <input type="checkbox" id="is_featured" <?php checked($is_featured); ?>>
                            ⭐ Sản phẩm nổi bật
                        </label>
                    </div> -->

                    <div class="sidebar-box">
                        <h3>💡 Hướng dẫn</h3>
                        <ul style="font-size: 13px; line-height: 1.6;">
                            <li>✅ Mô tả chi tiết dành cho khách hàng đọc</li>
                            <li>✅ Có thể upload ảnh trực tiếp vào mô tả</li>
                            <li>✅ Xóa tùy chỉnh sẽ quay về dùng data từ ERP</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * AJAX: Lưu meta
     */
    public function ajax_save_product_meta()
    {
        check_ajax_referer('vinapet_product_meta_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền');
        }

        $product_code = sanitize_text_field($_POST['product_code'] ?? '');

        if (empty($product_code)) {
            wp_send_json_error('Mã sản phẩm không hợp lệ');
        }

        error_log( 'custom_image_1: ' . print_r($_POST['custom_image_1'], true));
        $data = [
            'custom_description' => $_POST['custom_description'] ?? '',
            'custom_short_desc' => sanitize_textarea_field($_POST['custom_short_desc'] ?? ''),
            'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
            'seo_description' => sanitize_textarea_field($_POST['seo_description'] ?? ''),
            'seo_og_image' => esc_url_raw($_POST['seo_og_image'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'custom_image_1' => esc_url_raw($_POST['custom_image_1'] ?? ''),
            'custom_image_2' => esc_url_raw($_POST['custom_image_2'] ?? ''),
            'custom_image_3' => esc_url_raw($_POST['custom_image_3'] ?? ''),
            'custom_image_4' => esc_url_raw($_POST['custom_image_4'] ?? ''),
        ];

        if ($this->meta_manager) {
            $result = $this->meta_manager->save_product_meta($product_code, $data);

            if ($result) {
                // Clear cache
                require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
                $manager = new Product_Data_Manager();
                $manager->clear_cache($product_code);

                wp_send_json_success('Lưu thành công!');
            } else {
                wp_send_json_error('Lỗi khi lưu dữ liệu');
            }
        } else {
            wp_send_json_error('Meta Manager chưa được khởi tạo');
        }
    }

    /**
     * AJAX: Xóa meta
     */
    public function ajax_delete_product_meta()
    {
        check_ajax_referer('vinapet_product_meta_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Không có quyền');
        }

        $product_code = sanitize_text_field($_POST['product_code'] ?? '');

        if (empty($product_code)) {
            wp_send_json_error('Mã sản phẩm không hợp lệ');
        }

        if ($this->meta_manager) {
            $result = $this->meta_manager->delete_product_meta($product_code);

            if ($result) {
                // Clear cache
                require_once get_template_directory() . '/includes/helpers/class-product-data-manager.php';
                $manager = new Product_Data_Manager();
                $manager->clear_cache($product_code);

                wp_send_json_success('Đã xóa tùy chỉnh!');
            } else {
                wp_send_json_error('Lỗi khi xóa');
            }
        } else {
            wp_send_json_error('Meta Manager chưa được khởi tạo');
        }
    }

    public function products_page()
    {
    ?>
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-archive"></span>
                Quản lý Sản phẩm ERP
            </h1>

            <!-- Thanh công cụ -->
            <div class="products-toolbar" style="background: #f8f9fa; padding: 15px; margin: 15px 0; border: 1px solid #ddd; border-radius: 5px;">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="search-input" placeholder="Tìm kiếm sản phẩm..."
                        style="width: 300px;">
                    <button type="button" id="btn-search" class="button">Tìm kiếm</button>
                    <button type="button" id="btn-load-erp" class="button button-primary">
                        <span class="dashicons dashicons-download" style="vertical-align: text-top;"></span>
                        Lấy từ ERP
                    </button>
                    <span id="loading" style="display: none;">
                        <span class="spinner is-active" style="float: none;"></span>
                        Đang tải...
                    </span>
                </div>
            </div>

            <!-- Bảng sản phẩm -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 120px;">Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th width="10%">Mô tả tùy chỉnh</th>
                        <th width="20%">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 30px; color: #666;">
                            Nhấn "Lấy từ ERP" để tải danh sách sản phẩm
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Phân trang -->
            <div id="pagination" style="margin: 20px 0; text-align: center;"></div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                let allProducts = [];
                let filteredProducts = [];
                let currentPage = 1;
                const itemsPerPage = 20;

                loadProducts();
                // Lấy từ ERP
                $('#btn-load-erp').click(function() {
                    loadProducts();
                });

                // Tìm kiếm
                $('#btn-search').click(function() {
                    searchProducts();
                });

                $('#search-input').keypress(function(e) {
                    if (e.which === 13) searchProducts();
                });

                function loadProducts() {
                    $('#loading').show();
                    $('#btn-load-erp').prop('disabled', true);

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vinapet_load_erp_products',
                            nonce: '<?php echo wp_create_nonce('vinapet_erp_products'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                allProducts = response.data || [];
                                // console.log(allProducts);
                                filteredProducts = allProducts;
                                currentPage = 1;
                                renderTable();
                                showNotice('Đã tải ' + allProducts.length + ' sản phẩm từ ERP', 'success');
                            } else {
                                showNotice('Lỗi: ' + (response.data || 'Không thể tải dữ liệu'), 'error');
                            }
                        },
                        error: function() {
                            showNotice('Lỗi kết nối. Vui lòng kiểm tra cài đặt ERP.', 'error');
                        },
                        complete: function() {
                            $('#loading').hide();
                            $('#btn-load-erp').prop('disabled', false);
                        }
                    });
                }

                function searchProducts() {
                    const search = $('#search-input').val().toLowerCase().trim();

                    if (!search) {
                        filteredProducts = allProducts;
                    } else {
                        filteredProducts = allProducts.filter(function(product) {
                            return (product.item_name && product.item_name.toLowerCase().includes(search)) ||
                                (product.item_code && product.item_code.toLowerCase().includes(search));
                        });
                    }

                    currentPage = 1;
                    renderTable();
                }

                function renderTable() {
                    const start = (currentPage - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    const pageProducts = filteredProducts.slice(start, end);

                    let html = '';

                    if (pageProducts.length === 0) {
                        if (filteredProducts.length === 0 && allProducts.length > 0) {
                            html = '<tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">Không tìm thấy sản phẩm phù hợp</td></tr>';
                        } else {
                            html = '<tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">Chưa có dữ liệu</td></tr>';
                        }
                    } else {
                        pageProducts.forEach(function(product) {
                            const code = product.product_id || product.ProductID || '';
                            const editUrl = '<?php echo admin_url('admin.php?page=vinapet-product-editor&product='); ?>' + code;
                            // console.log(product);

                            html += '<tr>';
                            html += '<td><code>' + (product.ProductID || '') + '</code></td>';
                            html += '<td><strong>' + (product.Ten_SP || '') + '</strong></td>';
                            html += '<td style="text-align:center;">' + (product.has_custom_meta == 1 ? '✅ Có' : '❌ Chưa') + '</td>';
                            html += `<td>
                                <a href="${editUrl}" class="button button-small">
                                    <span class="dashicons dashicons-edit"></span> Tùy chỉnh
                                </a>
                            </td>`;
                            html += '</tr>';
                        });
                    }

                    $('#products-tbody').html(html);
                    renderPagination();
                }

                function renderPagination() {
                    const totalPages = Math.ceil(filteredProducts.length / itemsPerPage);
                    let html = '';

                    if (totalPages > 1) {
                        html += '<p style="margin-bottom: 10px;">Trang ' + currentPage + ' / ' + totalPages + ' (' + filteredProducts.length + ' sản phẩm)</p>';

                        // Previous button
                        if (currentPage > 1) {
                            html += '<button class="button" onclick="changePage(' + (currentPage - 1) + ')">‹ Trước</button> ';
                        }

                        // Page numbers
                        const startPage = Math.max(1, currentPage - 2);
                        const endPage = Math.min(totalPages, currentPage + 2);

                        for (let i = startPage; i <= endPage; i++) {
                            if (i === currentPage) {
                                html += '<button class="button button-primary">' + i + '</button> ';
                            } else {
                                html += '<button class="button" onclick="changePage(' + i + ')">' + i + '</button> ';
                            }
                        }

                        // Next button
                        if (currentPage < totalPages) {
                            html += '<button class="button" onclick="changePage(' + (currentPage + 1) + ')">Sau ›</button>';
                        }
                    } else if (filteredProducts.length > 0) {
                        html += '<p>' + filteredProducts.length + ' sản phẩm</p>';
                    }

                    $('#pagination').html(html);
                }

                // Global function cho pagination
                window.changePage = function(page) {
                    currentPage = page;
                    renderTable();
                };

                function showNotice(message, type) {
                    // Xóa notice cũ
                    $('.wrap .notice').remove();

                    const className = type === 'success' ? 'notice-success' : 'notice-error';
                    const notice = $('<div class="notice ' + className + ' is-dismissible"><p>' + message + '</p></div>');
                    $('.wrap h1').after(notice);

                    // Auto hide sau 5 giây
                    setTimeout(function() {
                        notice.fadeOut(function() {
                            notice.remove();
                        });
                    }, 5000);
                }

                function formatPrice(price) {
                    if (!price || price === 0) return '0 ₫';
                    return new Intl.NumberFormat('vi-VN').format(price) + ' ₫';
                }

                function truncate(text, length) {
                    if (!text || text.length <= length) return text;
                    return text.substring(0, length) + '...';
                }
            });
        </script>

        <style>
            .products-toolbar {
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            #products-tbody code {
                background: #f0f0f0;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
                color: #333;
            }

            #products-tbody strong {
                color: #0073aa;
            }

            .notice {
                margin: 15px 0 5px 0;
            }
        </style>
<?php
    }

    public function ajax_load_products()
    {
        check_ajax_referer('vinapet_erp_products', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Bạn không có quyền thực hiện thao tác này');
        }

        // Kiểm tra xem ERP API Client có tồn tại không
        if (!class_exists('ERP_API_Client')) {
            wp_send_json_error('ERP API Client chưa được cài đặt');
        }

        try {
            $erp_client = new ERP_API_Client();
            $products = $erp_client->get_products();
            //error_log('Products from ERP: ' . print_r($products, true));

            if (is_wp_error($products)) {
                wp_send_json_error('Lỗi ERP API: ' . $products->get_error_message());
            }

            // Kiểm tra cấu trúc dữ liệu trả về
            $product_data = [];
            if (isset($products['data']) && is_array($products['data'])) {
                $product_data = $products['data'];
            } elseif (is_array($products)) {
                $product_data = $products;
            }

            foreach ($product_data as &$product) {
                $product['has_custom_meta'] = '❌';
                if ($this->meta_manager) {
                    $meta = $this->meta_manager->get_product_meta($product['ProductID'] ?? '');
                    if ($meta) {
                        $product['has_custom_meta'] = true;
                    }
                }
            }

            wp_send_json_success($product_data);
        } catch (Exception $e) {
            wp_send_json_error('Lỗi: ' . $e->getMessage());
        }
    }
}

// Khởi tạo class
new VinaPet_Products_Admin();
