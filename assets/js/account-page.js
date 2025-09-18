/**
 * Account Page JavaScript
 * Handles tab switching, form submissions, and AJAX requests
 */

(function($) {
    'use strict';

    class AccountPage {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTabs();
            this.loadOrders();
        }

        bindEvents() {
            // Sidebar menu items
            $('.menu-item[data-tab]').on('click', (e) => {
                this.switchMainTab($(e.currentTarget).data('tab'));
            });

            // Profile sub-tabs
            $('.tab-button[data-target]').on('click', (e) => {
                this.switchSubTab($(e.currentTarget).data('target'));
            });

            // Orders sub-tabs
            $('.orders-tab-button[data-target]').on('click', (e) => {
                this.switchOrdersTab($(e.currentTarget).data('target'));
            });

            // Order card toggle
            $(document).on('click', '.order-header', (e) => {
                this.toggleOrderCard($(e.currentTarget).closest('.order-card'));
            });

            // Order actions
            $(document).on('click', '.btn-cancel', (e) => {
                e.preventDefault();
                this.cancelOrder($(e.currentTarget).data('order-id'));
            });

            $(document).on('click', '.btn-continue', (e) => {
                e.preventDefault();
                this.continueOrder($(e.currentTarget).data('order-id'));
            });

            // Logout button
            $('#logout-btn').on('click', () => {
                this.handleLogout();
            });

            // Form submissions
            $('#profile-info-form').on('submit', (e) => {
                e.preventDefault();
                this.updateProfile();
            });

            $('#change-password-form').on('submit', (e) => {
                e.preventDefault();
                this.changePassword();
            });

            // Close message
            $(document).on('click', '.message-close', () => {
                this.hideMessage();
            });
        }

        initTabs() {
            // Ensure first tab is active by default
            $('.menu-item').first().addClass('active');
            $('.tab-content').first().addClass('active');
            $('.tab-button').first().addClass('active');
            $('.tab-pane').first().addClass('active');
        }

        switchMainTab(tabId) {
            // Update sidebar menu
            $('.menu-item').removeClass('active');
            $(`.menu-item[data-tab="${tabId}"]`).addClass('active');

            // Update main content
            $('.tab-content').removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
        }

        switchSubTab(targetId) {
            // Update tab buttons
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-target="${targetId}"]`).addClass('active');

            // Update tab panes
            $('.tab-pane').removeClass('active');
            $(`#${targetId}`).addClass('active');
        }

        switchOrdersTab(targetId) {
            // Update orders tab buttons
            $('.orders-tab-button').removeClass('active');
            $(`.orders-tab-button[data-target="${targetId}"]`).addClass('active');

            // Update orders tab panes
            $('.orders-tab-pane').removeClass('active');
            $(`#${targetId}`).addClass('active');
        }

        toggleOrderCard(orderCard) {
            orderCard.toggleClass('expanded');
        }

        updateProfile() {
            const formData = new FormData($('#profile-info-form')[0]);
            formData.append('action', 'update_profile_info');

            this.showLoading();

            $.ajax({
                url: vinapet_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showMessage('Cập nhật thông tin thành công!', 'success');
                    } else {
                        this.showMessage(response.data || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Có lỗi xảy ra khi cập nhật!', 'error');
                }
            });
        }

        changePassword() {
            const currentPassword = $('#current_password').val();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();

            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                this.showMessage('Vui lòng điền đầy đủ thông tin!', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                this.showMessage('Mật khẩu mới không khớp!', 'error');
                return;
            }

            if (newPassword.length < 6) {
                this.showMessage('Mật khẩu mới phải có ít nhất 6 ký tự!', 'error');
                return;
            }

            const formData = new FormData($('#change-password-form')[0]);
            formData.append('action', 'change_user_password');

            this.showLoading();

            $.ajax({
                url: vinapet_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showMessage('Đổi mật khẩu thành công!', 'success');
                        $('#change-password-form')[0].reset();
                    } else {
                        this.showMessage(response.data || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Có lỗi xảy ra khi đổi mật khẩu!', 'error');
                }
            });
        }

        handleLogout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = vinapet_ajax.logout_url || wp_logout_url();
            }
        }

        showLoading() {
            $('#loading-overlay').show();
        }

        hideLoading() {
            $('#loading-overlay').hide();
        }

        showMessage(message, type = 'success') {
            const messageOverlay = $('#message-overlay');
            const messageContent = messageOverlay.find('.message-content');
            const messageText = messageOverlay.find('.message-text');

            messageContent.removeClass('success error').addClass(type);
            messageText.text(message);
            messageOverlay.show();

            // Auto hide after 3 seconds
            setTimeout(() => {
                this.hideMessage();
            }, 3000);
        }

        hideMessage() {
            $('#message-overlay').hide();
        }

        // Orders functionality
        loadOrders() {
            // Load sample orders data
            this.renderCreatingRequestOrders();
        }

        renderCreatingRequestOrders() {
            const sampleOrders = this.getSampleOrders();
            const container = $('#creating-request-orders');
            
            if (sampleOrders.length === 0) {
                container.html('<div class="empty-orders"><p>Chưa có đơn hàng đang tạo yêu cầu</p></div>');
                return;
            }

            let html = '';
            sampleOrders.forEach(order => {
                html += this.generateOrderCardHTML(order);
            });

            container.html(html);
        }

        generateOrderCardHTML(order) {
            const itemsHTML = order.items.map(item => `
                <div class="order-item">
                    <div class="item-header">
                        <span class="item-name">${item.name}</span>
                        <span class="item-quantity">${item.quantity}</span>
                    </div>
                    <div class="item-details">
                        ${item.details.map(detail => `<div class="item-detail">• ${detail}</div>`).join('')}
                    </div>
                </div>
            `).join('');

            return `
                <div class="order-card" data-order-id="${order.id}">
                    <div class="order-header">
                        <h3 class="order-title">${order.title}</h3>
                        <div class="order-header-right">
                            <span class="order-date">Tạo lúc ${order.created_at}</span>
                            <button class="order-toggle">▼</button>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-content">
                            <div class="order-items">
                                ${itemsHTML}
                            </div>
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span class="summary-label">Tổng số lượng:</span>
                                    <span class="summary-value">${order.summary.total_quantity}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Bao bì:</span>
                                    <span class="summary-value highlight-text">${order.summary.packaging}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Thời gian nhận hàng:</span>
                                    <span class="summary-value highlight-text">${order.summary.delivery_time}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Vận chuyển:</span>
                                    <span class="summary-value highlight-text">${order.summary.shipping}</span>
                                </div>
                                <div class="summary-row">
                                    <span class="summary-label">Báo giá dự kiến:</span>
                                    <div class="total-price-section">
                                        <span class="total-price">${order.summary.total_price}</span>
                                        <span class="price-note">(Giá cost: ${order.summary.price_per_kg})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="order-footer">
                            <div class="footer-actions">
                                <button class="btn-cancel" data-order-id="${order.id}">Hủy đơn hàng</button>
                                <button class="btn-continue" data-order-id="${order.id}">Tiếp tục thực hiện</button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            `;
        }

        getSampleOrders() {
            return [
                {
                    id: 1,
                    title: "Cát Tre",
                    created_at: "18:55 ngày 1/7/2025",
                    items: [
                        {
                            name: "Cát tre: Mùi cốm - Màu xanh non",
                            quantity: "1000 kg",
                            details: ["Túi 8 Biên PA / PE Hút Chân Không"]
                        },
                        {
                            name: "Cát tre: Mùi sen - Màu hồng",
                            quantity: "3000 kg",
                            details: ["Bao Tái Dữa + Lót 1 lớp PE"]
                        }
                    ],
                    summary: {
                        total_quantity: "4000 kg",
                        packaging: "Vui lòng chọn",
                        delivery_time: "Vui lòng chọn", 
                        shipping: "Vui lòng chọn",
                        total_price: "171,800,000 đ",
                        price_per_kg: "42,950 đ/kg"
                    }
                },
                {
                    id: 2,
                    title: "Cát Tre + Cát đất sét",
                    created_at: "8:42 ngày 29/6/2025",
                    items: [
                        {
                            name: "Cát tre",
                            quantity: "tỷ lệ 75%",
                            details: ["Màu xanh non", "Mùi trà xanh", "Túi Jumbo 1 tấn"]
                        },
                        {
                            name: "Cát đất sét", 
                            quantity: "tỷ lệ 25%",
                            details: []
                        }
                    ],
                    summary: {
                        total_quantity: "10,000 kg",
                        packaging: "0 đ",
                        delivery_time: "0 đ",
                        shipping: "3,000,000 đ",
                        total_price: "253,000,000 đ",
                        price_per_kg: "25,300 đ/kg"
                    }
                }
            ];
        }

        cancelOrder(orderId) {
            if (confirm('Bạn có chắc muốn hủy đơn hàng này?')) {
                this.showMessage('Đơn hàng đã được hủy!', 'success');
                // Here you would make an AJAX call to cancel the order
                this.loadOrders(); // Reload orders
            }
        }

        continueOrder(orderId) {
            this.showMessage('Chuyển hướng đến trang tiếp tục đặt hàng...', 'success');
            // Here you would redirect to the checkout page with the order data
            // window.location.href = '/checkout/?order_id=' + orderId;
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new AccountPage();
    });

})(jQuery);

// Helper function to get WordPress logout URL
function wp_logout_url() {
    return window.location.origin + '/wp-login.php?action=logout&redirect_to=' + encodeURIComponent(window.location.origin);
}

// ============================================================================
// THÊM VÀO assets/js/account-page.js (hoặc thêm vào cuối file hiện có)
// ============================================================================

/**
 * ERP Customer Integration cho Account Page
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        initERPIntegration();
    });

    function initERPIntegration() {
        // Load customer data từ ERP khi vào trang
        loadCustomerFromERP();
        
        // Add sync button
        addERPSyncButton();
    }

    /**
     * Load customer data từ ERP
     */
    function loadCustomerFromERP() {
        $.ajax({
            url: vinapet_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vinapet_get_customer_from_erp',
                nonce: vinapet_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateFormWithERPData(response.data.customer_data);
                    showERPStatus(true, 'Đã đồng bộ với ERP');
                } else {
                    showERPStatus(false, 'Chưa có trong ERP');
                }
            },
            error: function() {
                showERPStatus(false, 'Lỗi kết nối ERP');
            }
        });
    }

    /**
     * Update form với data từ ERP
     */
    function updateFormWithERPData(customerData) {
        if (!customerData || customerData.status !== 'success') {
            return;
        }

        const customer = customerData.customer;
        
        // Update form fields
        if (customer.customer_name) {
            $('#display_name').val(customer.customer_name);
        }
        
        if (customer.custom_phone) {
            $('#phone_number').val(customer.custom_phone);
        }
        
        // Update address
        if (customer.address) {
            const addressString = formatERPAddress(customer.address);
            $('#user_address').val(addressString);
        }
    }

    /**
     * Format address từ ERP
     */
    function formatERPAddress(addressData) {
        if (typeof addressData === 'string') {
            return addressData;
        }
        
        const parts = [];
        if (addressData.address_line1) parts.push(addressData.address_line1);
        if (addressData.city && addressData.city !== 'Unknow') parts.push(addressData.city);
        if (addressData.country) parts.push(addressData.country);
        
        return parts.join(', ');
    }

    /**
     * Add ERP sync button
     */
    function addERPSyncButton() {
        if ($('.erp-sync-btn').length > 0) return;
        
        const syncBtn = `
            <div class="form-group erp-sync-section">
                <button type="button" class="btn btn-secondary erp-sync-btn">
                    Đồng bộ với ERP
                </button>
                <div class="erp-status"></div>
            </div>
        `;
        
        $('#profile-info-form').append(syncBtn);
        
        // Bind click event
        $('.erp-sync-btn').on('click', function() {
            syncWithERP();
        });
    }

    /**
     * Sync với ERP
     */
    function syncWithERP() {
        const $btn = $('.erp-sync-btn');
        const originalText = $btn.text();
        
        $btn.text('Đang đồng bộ...').prop('disabled', true);

        $.ajax({
            url: vinapet_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vinapet_get_customer_from_erp',
                nonce: vinapet_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateFormWithERPData(response.data.customer_data);
                    showERPStatus(true, 'Đồng bộ thành công!');
                } else {
                    showERPStatus(false, 'Lỗi: ' + response.data);
                }
            },
            error: function() {
                showERPStatus(false, 'Lỗi kết nối');
            },
            complete: function() {
                $btn.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Show ERP status
     */
    function showERPStatus(success, message) {
        const statusClass = success ? 'success' : 'error';
        const statusHtml = `<div class="erp-status-message ${statusClass}">${message}</div>`;
        
        $('.erp-status').html(statusHtml);
        
        // Auto hide sau 3 giây
        setTimeout(() => {
            $('.erp-status-message').fadeOut();
        }, 3000);
    }

})(jQuery);

