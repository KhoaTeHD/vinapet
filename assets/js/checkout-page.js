/**
 * Fixed checkout-page.js - Pricing theo source VinaPet + Add to Cart handler
 * Thay thế toàn bộ file checkout-page.js
 */

(function ($) {
  $(document).ready(function () {
    // Pricing modifiers dựa trên source VinaPet
    const packagingDesignPrices = {
      company_design: 0, // Nhà máy hỗ trợ thiết kế: Miễn phí
      custom_file: 0, // File thiết kế riêng: Báo giá sau (0đ hiển thị)
    };

    const deliveryTimelinePrices = {
      urgent: 100000, // Gấp (dưới 15 ngày): +100,000đ
      normal: 20000, // Trung bình (15-30 ngày): +20,000đ
      flexible: 0, // Linh hoạt (trên 30 ngày): Miễn phí
    };

    const shippingMethodPrices = {
      factory_support: 300000, // Nhà máy hỗ trợ vận chuyển: +3,000,000đ
      self_pickup: 0, // Tự lấy hàng: Miễn phí
    };

    // Get initial data từ PHP (đã render sẵn)
    let checkoutData = window.vinapet_checkout_data || {};
    let baseQuantity = checkoutData.total_quantity || 1000;
    let baseTotalPrice = checkoutData.estimated_price || 50000000;

    // Initialize
    initializeFormHandlers();
    updateDynamicPricing();

    /**
     * Add to Cart button handler
     */
    $(".add-to-cart-btn").on("click", function (e) {
      e.preventDefault();

      // TODO: Implement add to cart functionality
      // Hiện tại để trống cho tương lai phát triển

      alert("Tính năng giỏ hàng sẽ được phát triển trong tương lai!");

      // Template cho tương lai:
      // const cartData = {
      //     type: checkoutData.type,
      //     products: checkoutData.products || [checkoutData],
      //     total_quantity: baseQuantity,
      //     total_price: getCurrentTotalPrice(),
      //     checkout_options: getCurrentCheckoutOptions()
      // };
      //
      // $.ajax({
      //     url: vinapet_ajax.ajax_url,
      //     type: 'POST',
      //     data: {
      //         action: 'vinapet_add_to_cart',
      //         nonce: vinapet_ajax.nonce,
      //         cart_data: cartData
      //     },
      //     success: function(response) {
      //         if (response.success) {
      //             alert('Đã thêm vào giỏ hàng!');
      //             // Update cart count in header
      //         }
      //     }
      // });
    });

    /**
     * Form submit handler (Gửi yêu cầu) - Hoàn chỉnh với customer data VÀ items
     */
    $(".submit-request-btn").on("click", function (e) {
      e.preventDefault();

      const $button = $(this);
      const originalText = $button.html();

      // Get customer email
      const customerEmail = vinapet_ajax.current_user.email;
      if (!customerEmail) {
        vinapet_show_message(
          "error",
          "Vui lòng cung cấp email để tạo báo giá!"
        );
        return;
      }

      // Check if we have order data (items) from session/PHP
      if (!checkoutData) {
        vinapet_show_message(
          "error",
          "Không tìm thấy dữ liệu sản phẩm! Vui lòng quay lại trang đặt hàng."
        );
        setTimeout(() => {
          window.location.href = vinapet_ajax.home_url + "/dat-hang";
        }, 2000);
        return;
      }
      if(checkoutData.type == 'order'){
        items = checkoutData.items || [checkoutData];
      }

      // Collect form data với đầy đủ thông tin cho API
      const formData = {
        // Customer information
        customer: customerEmail,

        // Pricing information
        shipping_cost: parseInt($("#shipping-cost").val()) || 50000,
        desired_delivery_time_amount:
          parseInt($("#delivery-time-cost").val()) || 30000,
        date_to_receive: parseInt($("#date-to-receive").val()) || 15,

        // Method information
        delivery_method: mapShippingMethodToAPI(
          $('input[name="shipping_method"]:checked').val()
        ),
        ship_method: getShipMethodIfAvailable(),

        // Form selections (for internal processing)
        packaging_design: $('input[name="packaging_design"]:checked').val(),
        delivery_timeline: $('input[name="delivery_timeline"]:checked').val(),
        shipping_method: $('input[name="shipping_method"]:checked').val(),

        // Contact information
        contact_info: {
          notes: $("#additional-support").val() || "",
          phone: $("#contact-phone").val() || "",
          email: $("#contact-email").val() || customerEmail,
        },

        // ORDER DATA - Thông tin sản phẩm từ session
        order_data: checkoutData,
      };

      console.log("Complete form data for API (including items):", formData);
      console.log("Order data (items):", checkoutData);

      // Validate required fields
      if (
        !formData.packaging_design ||
        !formData.delivery_timeline ||
        !formData.shipping_method
      ) {
        vinapet_show_message(
          "error",
          "Vui lòng chọn đầy đủ thông tin bắt buộc!"
        );
        return;
      }

      // Validate customer email
      if (!isValidEmail(formData.customer)) {
        vinapet_show_message("error", "Email không hợp lệ!");
        return;
      }

      // Show loading state
      $button.prop("disabled", true).html(`
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin inline-block mr-2">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Đang tạo báo giá...
            `);

      // Call AJAX
      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: {
          action: "vinapet_submit_checkout_with_erp",
          nonce: vinapet_ajax.nonce,
          checkout_form: JSON.stringify(formData),
        },
        timeout: 30000, // 30 seconds timeout
        success: function (response) {
          console.log("AJAX Success Response:", response);

          if (response.success) {
            vinapet_show_message("success", response.data.message);

            // Log quotation details
            if (response.data.quotation_id) {
              console.log("Quotation created successfully:");
              console.log("- ID:", response.data.quotation_id);
              console.log("- Type:", response.data.order_type);
              console.log("- Summary:", response.data.summary);
            }

            // Redirect after delay
            setTimeout(() => {
              if (response.data.redirect) {
                window.location.href = response.data.redirect;
              } else {
                window.location.reload();
              }
            }, 2000);
          } else {
            console.error("Checkout Error Details:", response.data);
            let errorMessage =
              response.data.message || "Có lỗi xảy ra khi tạo báo giá!";

            // Handle specific error codes
            if (response.data.code === "AUTH_REQUIRED") {
              errorMessage = "Vui lòng đăng nhập để tiếp tục!";
              setTimeout(() => {
                window.location.href = "/dang-nhap";
              }, 2000);
            } else if (response.data.code === "NO_ORDER_DATA") {
              errorMessage =
                "Không tìm thấy dữ liệu đơn hàng! Vui lòng đặt hàng lại.";
              setTimeout(() => {
                window.location.href = vinapet_ajax.home_url + "/dat-hang";
              }, 3000);
            }

            vinapet_show_message("error", errorMessage);

            // Restore button
            $button.prop("disabled", false).html(originalText);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", { xhr, status, error });

          let errorMessage = "Lỗi kết nối! Vui lòng kiểm tra mạng và thử lại.";

          if (xhr.status === 403) {
            errorMessage = "Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại!";
            setTimeout(() => {
              window.location.href = "/dang-nhap";
            }, 2000);
          } else if (xhr.status >= 500) {
            errorMessage = "Lỗi server! Vui lòng thử lại sau.";
          } else if (status === "timeout") {
            errorMessage = "Yêu cầu hết thời gian chờ. Vui lòng thử lại!";
          }

          vinapet_show_message("error", errorMessage);

          // Restore button
          $button.prop("disabled", false).html(originalText);
        },
      });
    });

    /**
         * Validate email format
         */
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

    /**
     * Get current user email (từ PHP hoặc form)
     */
    function getCurrentUserEmail() {
      // Thử lấy từ hidden input hoặc data attribute trước
      let userEmail = window.user.email || "";

      // Nếu không có, thử lấy từ global JavaScript variable
      if (!userEmail && typeof window.current_user !== "undefined") {
        userEmail = window.current_user.email;
      }

      

      return userEmail || "";
    }

    /**
     * Map shipping method display to API value
     */
    function mapShippingMethodToAPI(shippingMethod) {
      const mapping = {
        factory_support: "Giao tận nơi",
        self_pickup: "Tự vận chuyển",
      };
      return mapping[shippingMethod] || shippingMethod;
    }

    /**
     * Map ship method (nếu có)
     */
    function getShipMethodIfAvailable() {
      const shipMethod = $('input[name="ship_method"]:checked').val();
      return shipMethod || null;
    }

    /**
     * Helper function to show messages
     */
    function vinapet_show_message(type, message) {
      // Remove existing messages
      $(".vinapet-message").remove();

      const messageClass =
        type === "error"
          ? "bg-red-100 text-red-800 border-red-200"
          : "bg-green-100 text-green-800 border-green-200";
      const icon = type === "error" ? "⚠️" : "✅";

      const messageHtml = `
        <div class="vinapet-message ${messageClass} border rounded-lg p-4 mb-4 animate-fade-in">
            <div class="flex items-center">
                <span class="mr-2">${icon}</span>
                <span>${message}</span>
            </div>
        </div>
    `;

      // Insert message at top of form
      $(".checkout-form").prepend(messageHtml);

      // Auto hide success messages
      if (type === "success") {
        setTimeout(() => {
          $(".vinapet-message").fadeOut();
        }, 5000);
      }

      // Scroll to top to show message
      $("html, body").animate(
        {
          scrollTop: $(".vinapet-message").offset().top - 20,
        },
        500
      );
    }

    /**
     * Initialize form handlers
     */
    function initializeFormHandlers() {
      // Update pricing khi user chọn options
      $(
        'input[name="packaging_design"], input[name="delivery_timeline"], input[name="shipping_method"]'
      ).change(function () {
        updateSummaryDisplay();
        updateDynamicPricing();
      });
    }

    /**
     * Update summary display text
     */
    function updateSummaryDisplay() {
      // Update packaging text
      const packagingValue = $('input[name="packaging_design"]:checked').val();
      let packagingText = "Vui lòng chọn";
      if (packagingValue === "company_design") {
        packagingText = "Nhà máy hỗ trợ thiết kế decal/ túi đơn giản";
      } else if (packagingValue === "custom_file") {
        packagingText = "File thiết kế riêng";
      }
      $("#summary-packaging")
        .text(packagingText)
        .toggleClass("highlight-text", !packagingValue);

      // Update delivery text
      const deliveryValue = $('input[name="delivery_timeline"]:checked').val();
      let deliveryText = "Vui lòng chọn";
      if (deliveryValue === "urgent") {
        deliveryText = "Gấp (dưới 15 ngày)";
      } else if (deliveryValue === "normal") {
        deliveryText = "Trung bình (15-30 ngày)";
      } else if (deliveryValue === "flexible") {
        deliveryText = "Linh hoạt (trên 30 ngày)";
      }
      $("#summary-delivery")
        .text(deliveryText)
        .toggleClass("highlight-text", !deliveryValue);

      // Update shipping text
      const shippingValue = $('input[name="shipping_method"]:checked').val();
      let shippingText = "Vui lòng chọn";
      if (shippingValue === "factory_support") {
        shippingText = "Nhà máy hỗ trợ vận chuyển";
      } else if (shippingValue === "self_pickup") {
        shippingText = "Tự vận chuyển";
      }
      $("#summary-shipping")
        .text(shippingText)
        .toggleClass("highlight-text", !shippingValue);
    }

    /**
     * Calculate và update dynamic pricing
     */
    function updateDynamicPricing() {
      // Get current selections
      const packagingDesign = $('input[name="packaging_design"]:checked').val();
      const deliveryTimeline = $(
        'input[name="delivery_timeline"]:checked'
      ).val();
      const shippingMethod = $('input[name="shipping_method"]:checked').val();

      // Start với base price từ order/mix data
      let additionalCosts = 0;

      // Add packaging design cost (hiện tại cả 2 đều = 0 theo source)
      if (
        packagingDesign &&
        packagingDesignPrices[packagingDesign] !== undefined
      ) {
        additionalCosts += packagingDesignPrices[packagingDesign];
      }

      // Add delivery timeline cost (fixed amount, không per kg)
      if (
        deliveryTimeline &&
        deliveryTimelinePrices[deliveryTimeline] !== undefined
      ) {
        additionalCosts += deliveryTimelinePrices[deliveryTimeline];
      }

      // Add shipping cost (fixed amount)
      if (
        shippingMethod &&
        shippingMethodPrices[shippingMethod] !== undefined
      ) {
        additionalCosts += shippingMethodPrices[shippingMethod];
      }

      // Calculate final totals
      const newTotalPrice = baseTotalPrice + additionalCosts;
      const newPricePerKg = newTotalPrice / baseQuantity;

      // Update display
      $("#summary-total-price").text(formatPrice(newTotalPrice) + " đ");
      $("#summary-price-per-kg").text(formatPrice(newPricePerKg) + " đ/kg");
    }

    /**
     * Get current total price (for add to cart)
     */
    function getCurrentTotalPrice() {
      const packagingDesign = $('input[name="packaging_design"]:checked').val();
      const deliveryTimeline = $(
        'input[name="delivery_timeline"]:checked'
      ).val();
      const shippingMethod = $('input[name="shipping_method"]:checked').val();

      let additionalCosts = 0;
      if (
        packagingDesign &&
        packagingDesignPrices[packagingDesign] !== undefined
      ) {
        additionalCosts += packagingDesignPrices[packagingDesign];
      }
      if (
        deliveryTimeline &&
        deliveryTimelinePrices[deliveryTimeline] !== undefined
      ) {
        additionalCosts += deliveryTimelinePrices[deliveryTimeline];
      }
      if (
        shippingMethod &&
        shippingMethodPrices[shippingMethod] !== undefined
      ) {
        additionalCosts += shippingMethodPrices[shippingMethod];
      }

      return baseTotalPrice + additionalCosts;
    }

    /**
     * Get current checkout options (for add to cart)
     */
    function getCurrentCheckoutOptions() {
      return {
        packaging_design: $('input[name="packaging_design"]:checked').val(),
        delivery_timeline: $('input[name="delivery_timeline"]:checked').val(),
        shipping_method: $('input[name="shipping_method"]:checked').val(),
        additional_support: $("#additional-support").val(),
      };
    }

    /**
     * Format price với thousands separator
     */
    function formatPrice(price) {
      return Math.round(price).toLocaleString("vi-VN");
    }
  });
})(jQuery);
