/**
 * Account Page JavaScript
 * Handles tab switching, form submissions, and AJAX requests
 */

(function ($) {
  "use strict";

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
      $(".menu-item[data-tab]").on("click", (e) => {
        this.switchMainTab($(e.currentTarget).data("tab"));
      });

      // Profile sub-tabs
      $(".tab-button[data-target]").on("click", (e) => {
        this.switchSubTab($(e.currentTarget).data("target"));
      });

      // Orders sub-tabs
      $(".orders-tab-button[data-target]").on("click", (e) => {
        this.switchOrdersTab($(e.currentTarget).data("target"));
      });

      // Order card toggle
      $(document).on("click", ".order-header", (e) => {
        this.toggleOrderCard($(e.currentTarget).closest(".order-card"));
      });

      // Order actions
      $(document).on("click", ".btn-cancel", (e) => {
        e.preventDefault();
        this.cancelOrder($(e.currentTarget).data("order-id"));
      });

      $(document).on("click", ".btn-continue", (e) => {
        e.preventDefault();
        this.continueOrder($(e.currentTarget).data("order-id"));
      });

      // Logout button
      $("#logout-btn").on("click", () => {
        this.handleLogout();
      });

      // Form submissions
      $("#profile-info-form").on("submit", (e) => {
        e.preventDefault();
        this.updateProfile();
      });

      $("#change-password-form").on("submit", (e) => {
        e.preventDefault();
        this.changePassword();
      });

      // Close message
      $(document).on("click", ".message-close", () => {
        this.hideMessage();
      });
    }

    initTabs() {
      // Ensure first tab is active by default
      $(".menu-item").first().addClass("active");
      $(".tab-content").first().addClass("active");
      $(".tab-button").first().addClass("active");
      $(".tab-pane").first().addClass("active");
    }

    switchMainTab(tabId) {
      // Update sidebar menu
      $(".menu-item").removeClass("active");
      $(`.menu-item[data-tab="${tabId}"]`).addClass("active");

      // Update main content
      $(".tab-content").removeClass("active");
      $(`#${tabId}-tab`).addClass("active");
    }

    switchSubTab(targetId) {
      // Update tab buttons
      $(".tab-button").removeClass("active");
      $(`.tab-button[data-target="${targetId}"]`).addClass("active");

      // Update tab panes
      $(".tab-pane").removeClass("active");
      $(`#${targetId}`).addClass("active");
    }

    switchOrdersTab(targetId) {
      // Update orders tab buttons
      $(".orders-tab-button").removeClass("active");
      $(`.orders-tab-button[data-target="${targetId}"]`).addClass("active");

      // Update orders tab panes
      $(".orders-tab-pane").removeClass("active");
      $(`#${targetId}`).addClass("active");
    }

    toggleOrderCard(orderCard) {
      orderCard.toggleClass("expanded");
    }

    updateProfile() {
      const $form = $("#profile-info-form");
      const $submitBtn = $form.find(".btn-save");
      const originalBtnText = $submitBtn.find(".btn-text").text();

      // Validate form
      if (!this.validateProfileForm($form)) {
        return;
      }

      // Show loading state
      $submitBtn.prop("disabled", true);
      $submitBtn.find(".btn-text").text("Đang lưu...");
      $submitBtn.find(".btn-icon").text("⏳");

      // Prepare form data
      const formData = {
        action: "update_profile_info",
        profile_info_nonce: $form.find("#profile_info_nonce").val(),
        display_name: $form.find("#display_name").val().trim(),
        user_phone: $form.find("#user_phone").val().trim(),
        user_email: $form.find("#user_email").val().trim(),
        user_address: $form.find("#user_address").val().trim(),
        //user_address: "ABCDE",
      };

      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: formData,
        success: (response) => {
          if (response.success) {
            this.showMessage(response.data, "success");

            // Update displayed info if email changed
            if (formData.user_email !== window.vinapet_current_email) {
              window.vinapet_current_email = formData.user_email;
            }
          } else {
            this.showMessage(response.data || "Cập nhật thất bại!", "error");
          }
        },
        error: (xhr, status, error) => {
          console.error("Update profile error:", error);
          this.showMessage("Có lỗi xảy ra khi cập nhật thông tin!", "error");
        },
        complete: () => {
          // Reset button state
          $submitBtn.prop("disabled", false);
          $submitBtn.find(".btn-text").text(originalBtnText);
          $submitBtn.find(".btn-icon").text("💾");
        },
      });
    }

    // THÊM METHOD VALIDATE PROFILE FORM (nếu chưa có)
    validateProfileForm($form) {
      let isValid = true;

      const displayName = $form.find("#display_name").val().trim();
      const userEmail = $form.find("#user_email").val().trim();

      // Reset previous errors
      $form.find(".form-group").removeClass("error");
      $form.find(".error-message").remove();

      // Validate display name
      if (!displayName || displayName.length < 2) {
        this.showFieldError(
          $form.find("#display_name"),
          "Họ và tên phải có ít nhất 2 ký tự"
        );
        isValid = false;
      }

      // Validate email
      if (!userEmail || !this.validateEmail(userEmail)) {
        this.showFieldError($form.find("#user_email"), "Email không hợp lệ");
        isValid = false;
      }

      return isValid;
    }

    // THÊM HELPER METHODS (nếu chưa có)
    validateEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    showFieldError($field, message) {
      const $formGroup = $field.closest(".form-group");
      $formGroup.addClass("error");

      if ($formGroup.find(".error-message").length === 0) {
        $formGroup.append(`<div class="error-message">${message}</div>`);
      }
    }

    changePassword() {
      const currentPassword = $("#current_password").val();
      const newPassword = $("#new_password").val();
      const confirmPassword = $("#confirm_password").val();

      // Validation
      if (!currentPassword || !newPassword || !confirmPassword) {
        this.showMessage("Vui lòng điền đầy đủ thông tin!", "error");
        return;
      }

      if (newPassword !== confirmPassword) {
        this.showMessage("Mật khẩu mới không khớp!", "error");
        return;
      }

      if (newPassword.length < 6) {
        this.showMessage("Mật khẩu mới phải có ít nhất 6 ký tự!", "error");
        return;
      }

      const formData = new FormData($("#change-password-form")[0]);
      formData.append("action", "change_user_password");

      this.showLoading();

      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: (response) => {
          this.hideLoading();
          if (response.success) {
            this.showMessage("Đổi mật khẩu thành công!", "success");
            $("#change-password-form")[0].reset();
          } else {
            this.showMessage(response.data || "Có lỗi xảy ra!", "error");
          }
        },
        error: () => {
          this.hideLoading();
          this.showMessage("Có lỗi xảy ra khi đổi mật khẩu!", "error");
        },
      });
    }

    handleLogout() {
      if (confirm("Bạn có chắc muốn đăng xuất?")) {
        window.location.href = vinapet_ajax.logout_url || wp_logout_url();
      }
    }

    showLoading() {
      $("#loading-overlay").show();
    }

    hideLoading() {
      $("#loading-overlay").hide();
    }

    showMessage(message, type = "success") {
      const messageOverlay = $("#message-overlay");
      const messageContent = messageOverlay.find(".message-content");
      const messageText = messageOverlay.find(".message-text");

      messageContent.removeClass("success error").addClass(type);
      messageText.text(message);
      messageOverlay.show();

      // Auto hide after 3 seconds
      setTimeout(() => {
        this.hideMessage();
      }, 3000);
    }

    hideMessage() {
      $("#message-overlay").hide();
    }

    // Orders functionality
    loadOrders() {
      this.loadQuotationsFromERP();
    }

    renderSentRequestOrders() {
      const sampleOrders = this.getSampleOrders();
      const container = $("#sent-request-orders");

      if (sampleOrders.length === 0) {
        container.html(
          '<div class="empty-orders"><p>Chưa có đơn hàng đã gửi yêu cầu</p></div>'
        );
        return;
      }

      let html = "";
      sampleOrders.forEach((order) => {
        html += this.generateOrderCardHTML(order);
      });

      container.html(html);
    }

    generateOrderCardHTML(order) {
      const itemsHTML = order.items
        .map(
          (item) => `
                <div class="order-item">
                    <div class="item-header">
                        <span class="item-name">${item.name}</span>
                        <span class="item-quantity">${item.quantity}</span>
                    </div>
                    <div class="item-details">
                        ${item.details
                          .map(
                            (detail) =>
                              `<div class="item-detail">• ${detail}</div>`
                          )
                          .join("")}
                    </div>
                </div>
            `
        )
        .join("");

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
              details: ["Túi 8 Biên PA / PE Hút Chân Không"],
            },
            {
              name: "Cát tre: Mùi sen - Màu hồng",
              quantity: "3000 kg",
              details: ["Bao Tái Dữa + Lót 1 lớp PE"],
            },
          ],
          summary: {
            total_quantity: "4000 kg",
            packaging: "Vui lòng chọn",
            delivery_time: "Vui lòng chọn",
            shipping: "Vui lòng chọn",
            total_price: "171,800,000 đ",
            price_per_kg: "42,950 đ/kg",
          },
        },
        {
          id: 2,
          title: "Cát Tre + Cát đất sét",
          created_at: "8:42 ngày 29/6/2025",
          items: [
            {
              name: "Cát tre",
              quantity: "tỷ lệ 75%",
              details: ["Màu xanh non", "Mùi trà xanh", "Túi Jumbo 1 tấn"],
            },
            {
              name: "Cát đất sét",
              quantity: "tỷ lệ 25%",
              details: [],
            },
          ],
          summary: {
            total_quantity: "10,000 kg",
            packaging: "0 đ",
            delivery_time: "0 đ",
            shipping: "3,000,000 đ",
            total_price: "253,000,000 đ",
            price_per_kg: "25,300 đ/kg",
          },
        },
      ];
    }

    /**
     * Load quotations từ ERP API
     */
    loadQuotationsFromERP() {
      //this.showLoading();

      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: {
          action: "vinapet_get_quotations",
          nonce: vinapet_ajax.nonce,
        },
        success: (response) => {
          this.hideLoading();

          if (response.success) {
            console.log("Loaded quotations from ERP:", response.data.quotations);
            this.renderQuotations(response.data.quotations);
          } else {
            this.showEmptyQuotations(response.data.message);
          }
        },
        error: (error) => {
          this.hideLoading();
          this.showEmptyQuotations("Có lỗi xảy ra khi tải báo giá");
          console.error("AJAX error:", error);
        },
      });
    }

    /**
     * Render quotations vào tab "Đã gửi yêu cầu"
     */
    renderQuotations(quotations) {
      const container = $("#sent-request-orders");

      if (!quotations || quotations.length === 0) {
        this.showEmptyQuotations("Chưa có báo giá nào");
        return;
      }

      //console.log("Loaded quotations:", quotations);
      let html = "";
      quotations.quotations.forEach((quotation) => {
        html += this.generateQuotationCardHTML(quotation);
      });

      container.html(html);
    }

    /**
     * Generate HTML cho từng quotation card
     */
    generateQuotationCardHTML(quotation) {
      // Format thời gian
      const createdAt = this.formatDateTime(quotation.transaction_date);

      // Generate items HTML
      const itemsHTML = this.generateQuotationItemsHTML(quotation.items);

      // Calculate totals
      const totalQuantity = this.calculateTotalQuantity(quotation.items);

      // Build summary values
      const packaging = quotation.packaging || "Vui lòng chọn";
      const deliveryTime = quotation.delivery_time || "Vui lòng chọn";
      const shipping = quotation.shipping_cost
        ? `${this.formatNumber(quotation.shipping_cost)} đ`
        : "Vui lòng chọn";
      const totalPrice = quotation.grand_total
        ? `${this.formatNumber(quotation.grand_total)} đ`
        : "0 đ";
      const pricePerKg = this.formatNumber((quotation.grand_total / this.calculateTotalQuantityAsNumber(quotation.items)).toFixed(0)) + " đ/kg";

      return `
    <div class="order-card" data-order-id="${quotation.name}">
        <div class="order-header">
            <h3 class="order-title">${this.escapeHtml(
              quotation.title || "Báo giá"
            )}</h3>
            <div class="order-header-right">
                <span class="order-date">Tạo lúc ${createdAt}</span>
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
                        <span class="summary-value">${totalQuantity}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Bao bì:</span>
                        <span class="summary-value highlight-text">${packaging}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Thời gian nhận hàng:</span>
                        <span class="summary-value highlight-text">${deliveryTime}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Vận chuyển:</span>
                        <span class="summary-value highlight-text">${shipping}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Báo giá dự kiến:</span>
                        <div class="total-price-section">
                            <span class="total-price">${totalPrice}</span>
                            <span class="price-note">(Giá cost: ${pricePerKg})</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  `;
    }

    /**
     * Generate HTML cho items trong quotation
     * Xử lý logic hiển thị % cho mix type
     */
    generateQuotationItemsHTML(items) {
      if (!items || items.length === 0) {
        return "<p>Không có sản phẩm</p>";
      }

      return items
        .map((item) => {
          // Check if mix type (mix_percent > 0)
          const isMixItem =
            item.mix_percent && parseFloat(item.mix_percent) > 0;

          // Display quantity or percentage
          const quantityDisplay = isMixItem
            ? `tỷ lệ ${item.mix_percent}%`
            : `${this.formatNumber(item.qty)} kg`;

          // Build details array
          const details = [];
          if (item.item_name) {
            details.push(item.item_name);
          }
          if (item.uom) {
            details.push(item.custom_packet_item_name);
          }

          const detailsHTML =
            details.length > 0
              ? `<div class="item-details">
                ${details
                  .map(
                    (d) =>
                      `<div class="item-detail">• ${this.escapeHtml(d)}</div>`
                  )
                  .join("")}
               </div>`
              : "";

          return `
            <div class="order-item">
                <div class="item-header">
                    <span class="item-name">${this.escapeHtml(
                      item.item_code || item.item_name
                    )}</span>
                    <span class="item-quantity">${quantityDisplay}</span>
                </div>
                ${detailsHTML}
            </div>
        `;
        })
        .join("");
    }

    /**
     * Check if quotation is mix type
     */
    isMixQuotation(items) {
      return items.some(
        (item) => item.mix_percent && parseFloat(item.mix_percent) > 0
      );
    }

    /**
     * Calculate total quantity
     */
    calculateTotalQuantity(items) {
      const isMix = this.isMixQuotation(items);

      if (isMix) {
        // Với mix type, hiển thị tổng kg nếu có total field
        const firstItem = items[0];
        if (firstItem && firstItem.total_qty) {
          return `${this.formatNumber(firstItem.total_qty)} kg`;
        }
        return "Mix";
      }

      // Tính tổng quantity cho non-mix
      const total = items.reduce((sum, item) => {
        return sum + (parseFloat(item.qty) || 0);
      }, 0);

      return `${this.formatNumber(total)} kg`;
    }

    calculateTotalQuantityAsNumber(items) {
      const isMix = this.isMixQuotation(items);

      if (isMix) {
        // Với mix type, hiển thị tổng kg nếu có total field
        const firstItem = items[0];
        if (firstItem && firstItem.total_qty) {
          return `${this.formatNumber(firstItem.total_qty)} kg`;
        }
        return "Mix";
      }

      // Tính tổng quantity cho non-mix
      const total = items.reduce((sum, item) => {
        return sum + (parseFloat(item.qty) || 0);
      }, 0);

      return total;
    }

    /**
     * Format datetime
     */
    formatDateTime(datetime) {
      if (!datetime) return "";

      const date = new Date(datetime);
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      const day = date.getDate();
      const month = date.getMonth() + 1;
      const year = date.getFullYear();

      return `${hours}:${minutes} ngày ${day}/${month}/${year}`;
    }

    /**
     * Format number with thousand separator
     */
    formatNumber(num) {
      return new Intl.NumberFormat("vi-VN").format(num);
    }

    /**
     * Escape HTML để prevent XSS
     */
    escapeHtml(text) {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };
      return String(text).replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Show empty quotations message
     */
    showEmptyQuotations(message) {
      const container = $("#sent-request-orders");
      container.html(`
        <div class="empty-orders">
            <p>${this.escapeHtml(message || "Chưa có báo giá nào")}</p>
        </div>
    `);
    }

    cancelOrder(orderId) {
      if (confirm("Bạn có chắc muốn hủy đơn hàng này?")) {
        this.showMessage("Đơn hàng đã được hủy!", "success");
        // Here you would make an AJAX call to cancel the order
        this.loadOrders(); // Reload orders
      }
    }

    continueOrder(orderId) {
      this.showMessage(
        "Chuyển hướng đến trang tiếp tục đặt hàng...",
        "success"
      );
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
  return (
    window.location.origin +
    "/wp-login.php?action=logout&redirect_to=" +
    encodeURIComponent(window.location.origin)
  );
}

// ============================================================================
// THÊM VÀO assets/js/account-page.js (hoặc thêm vào cuối file hiện có)
// ============================================================================

/**
 * ERP Customer Integration cho Account Page
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    initERPIntegration();
  });

  function initERPIntegration() {
    // Load customer data từ ERP khi vào trang
    loadCustomerFromERP();
  }

  /**
   * Load customer data từ ERP
   */
  function loadCustomerFromERP() {
    $.ajax({
      url: vinapet_ajax.ajax_url,
      type: "POST",
      data: {
        action: "vinapet_get_customer_from_erp",
        nonce: vinapet_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          updateFormWithERPData(response.data.customer_data);
          showERPStatus(true, "Đã đồng bộ với ERP");
        } else {
          showERPStatus(false, "Chưa có trong ERP");
        }
      },
      error: function () {
        showERPStatus(false, "Lỗi kết nối ERP");
      },
    });
  }

  /**
   * Update form với data từ ERP
   */
  function updateFormWithERPData(customerData) {
    if (!customerData || customerData.status !== "success") {
      return;
    }

    const customer = customerData.customer;

    // Update form fields
    if (customer.customer_name) {
      $("#display_name").val(customer.customer_name);
    }

    if (customer.custom_phone) {
      $("#user_phone").val(customer.custom_phone);
    }

    // Update address
    if (customer.address) {
      //const addressString = formatERPAddress(customer.address);
      const addressString = customer.address;
      //$("#user_address").val(addressString);
    }
  }

  /**
   * Show ERP status
   */
  function showERPStatus(success, message) {
    const statusClass = success ? "success" : "error";
    const statusHtml = `<div class="erp-status-message ${statusClass}">${message}</div>`;

    $(".erp-status").html(statusHtml);

    // Auto hide sau 3 giây
    setTimeout(() => {
      $(".erp-status-message").fadeOut();
    }, 3000);
  }
})(jQuery);
