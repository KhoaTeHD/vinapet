<?php
/**
 * File: includes/admin/class-products-admin.php
 * Class riêng để quản lý menu Sản phẩm - Tách biệt khỏi settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class VinaPet_Products_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_products_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_vinapet_load_erp_products', array($this, 'ajax_load_products'));
    }
    
    public function add_products_menu() {
        add_menu_page(
            'Quản lý Sản phẩm ERP',
            'Sản phẩm',
            'manage_options',
            'vinapet-products-erp',
            array($this, 'products_page'),
            'dashicons-archive',
            26 // Position sau menu Settings
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_vinapet-products-erp') return;
        
        wp_enqueue_script('jquery');
    }
    
    public function products_page() {
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
                        <th style="width: 150px;">Nhóm sản phẩm</th>
                        <th style="width: 100px;">Giá bán</th>
                        <th>Mô tả</th>
                    </tr>
                </thead>
                <tbody id="products-tbody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #666;">
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
                        html += '<tr>';
                        html += '<td><code>' + (product.ProductID || '') + '</code></td>';
                        html += '<td><strong>' + (product.Ten_SP || '') + '</strong></td>';
                        html += '<td>' + (product.item_group || '') + '</td>';
                        html += '<td>' + formatPrice(product.Gia_ban_le) + '</td>';
                        html += '<td>' + truncate(product.Mo_ta_ngan || '', 80) + '</td>';
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
    
    public function ajax_load_products() {
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
            
            wp_send_json_success($product_data);
            
        } catch (Exception $e) {
            wp_send_json_error('Lỗi: ' . $e->getMessage());
        }
    }
}

// Khởi tạo class
new VinaPet_Products_Admin();