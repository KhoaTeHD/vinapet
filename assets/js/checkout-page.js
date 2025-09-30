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
     * Prepare items array theo đúng format ERPNext API
     * @param {Object} checkoutData - Data từ PHP session
     * @returns {Array} items - Mảng items đã format chuẩn
     */

    function prepareItemsForAPI(checkoutData) {
      const items = [];

      // Kiểm tra loại đơn hàng
      if (!checkoutData || !checkoutData.type) {
        console.error("Invalid checkoutData:", checkoutData);
        return items;
      }

      if (checkoutData.type === "mix") {
        // ============ XỬ LÝ MIX ORDER ============
        console.log("Processing MIX order:", checkoutData);

        if (!checkoutData.products || !Array.isArray(checkoutData.products)) {
          console.error("Mix order missing products array");
          return items;
        }

        // Lấy tổng số lượng từ total_quantity
        const totalQty = parseInt(checkoutData.total_quantity);

        const packet_item = checkoutData.details.packaging;

        const rate = parseInt(checkoutData.rate);

        // Convert mỗi product thành item format
        checkoutData.products.forEach((product, index) => {
          if (!product.code || !product.percentage) {
            console.warn(
              `Product ${index} missing code or percentage:`,
              product
            );
            return; // Skip invalid product
          }

          const item = {
            item_code: product.code,
            packet_item: packet_item || "SPTUI01",
            mix_percent: parseFloat(product.percentage) || 0.0,
            qty: totalQty, // Tất cả items trong mix có cùng qty
            uom: "Nos",
            rate: rate || 25000,
            is_free_item: 0,
          };

          // Thêm item_name nếu có
          if (product.name) {
            item.item_name = product.name;
          }

          // Generate additional_notes cho mix
          const productNames = checkoutData.products
            .map((p) => p.code)
            .filter(Boolean)
            .join(" + ");
          item.additional_notes = `Mix ${productNames}`;

          items.push(item);
          console.log(`Added mix item ${index + 1}:`, item);
        });
      } else {
        // ============ XỬ LÝ NORMAL ORDER ============
        console.log("Processing NORMAL order:", checkoutData);

        // Xử lý nếu có products array
        if (checkoutData.products && Array.isArray(checkoutData.products)) {
          checkoutData.products.forEach((product, index) => {
            if (!product.code) {
              console.warn(`Product ${index} missing code:`, product);
              return; // Skip invalid product
            }

            const item = {
              item_code: product.code,
              item_name: product.name || product.code,
              qty: parseInt(product.quantity) || 1000,
              uom: "Nos",
              rate: parseInt(product.price) || 50000,
              packet_item: product.packet_item || "SPTUI01",
              mix_percent: 0.0,
              is_free_item: 0,
              additional_notes: product.notes || "",
            };

            items.push(item);
            console.log(`Added normal item ${index + 1}:`, item);
          });
        }
        // Xử lý nếu chỉ có 1 sản phẩm (checkoutData chính là product)
        else if (checkoutData.product_code) {
          const item = {
            item_code: checkoutData.product_code,
            item_name: checkoutData.product_name || checkoutData.code,
            qty: parseInt(checkoutData.quantity) || 1000,
            uom: "Nos",
            rate: parseInt(checkoutData.rate) || 50000,
            packet_item: checkoutData.packet_item || "SPTUI01",
            mix_percent: 0.0,
            is_free_item: 0,
            additional_notes: checkoutData.notes || "",
          };

          items.push(item);
          console.log("Added single product item:", item);
        } else {
          console.error("Normal order has no valid products:", checkoutData);
        }
      }

      console.log(`Total items prepared: ${items.length}`, items);
      return items;
    }

    /**
     * ===================================================================
     * SỬA HÀM SUBMIT BUTTON HANDLER
     * ===================================================================
     */
    $(".submit-request-btn").on("click", function (e) {
      e.preventDefault();

      const $button = $(this);
      const originalText = $button.html();

      // ======== 1. VALIDATE CUSTOMER ========
      const customerEmail = vinapet_ajax.current_user.email;
      if (!customerEmail) {
        vinapet_show_message(
          "error",
          "Vui lòng cung cấp email để tạo báo giá!"
        );
        return;
      }

      // ======== 2. CHECK CHECKOUT DATA ========
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

      // ======== 3. PREPARE ITEMS (QUAN TRỌNG) ========
      const items = prepareItemsForAPI(checkoutData);

      if (items.length === 0) {
        vinapet_show_message(
          "error",
          "Không có sản phẩm hợp lệ trong đơn hàng!"
        );
        console.error("No valid items prepared from:", checkoutData);
        return;
      }

      // ======== 4. COLLECT FORM DATA ========
      const formData = {
        // ✅ Customer information
        customer: customerEmail,

        // ✅ Items array - ĐÃ FORMAT CHUẨN
        items: items,

        // ✅ Pricing information
        shipping_cost: parseInt($("#shipping-cost").val()) || 50000,
        desired_delivery_time_amount:
          parseInt($("#delivery-time-cost").val()) || 30000,
        date_to_receive: parseInt($("#date-to-receive").val()) || 15,

        // ✅ Method information
        delivery_method: mapShippingMethodToAPI(
          $('input[name="shipping_method"]:checked').val()
        ),
        ship_method: getShipMethodIfAvailable(),

        // ✅ Form selections (for internal processing)
        packaging_design: $('input[name="packaging_design"]:checked').val(),
        delivery_timeline: $('input[name="delivery_timeline"]:checked').val(),
        shipping_method: $('input[name="shipping_method"]:checked').val(),

        // ✅ Contact information
        contact_info: {
          notes: $("#additional-support").val() || "",
          phone: $("#contact-phone").val() || "",
          email: $("#contact-email").val() || customerEmail,
        },

        // ✅ Metadata (for internal tracking)
        order_type: checkoutData.type || "normal",
        original_checkout_data: checkoutData, // Giữ lại để log/debug
      };

      // ======== 5. LOG ĐỂ DEBUG ========
      console.log("=== FINAL FORM DATA TO SEND ===");
      console.log("Customer:", formData.customer);
      console.log("Items count:", formData.items.length);
      console.log("Items detail:", formData.items);
      console.log("Order type:", formData.order_type);
      console.log("Full formData:", formData);

      // ======== 6. VALIDATE REQUIRED FIELDS ========
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

      // ======== 7. SEND AJAX REQUEST ========
      $button
        .prop("disabled", true)
        .html('<span class="spinner"></span> Đang gửi...');

      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        dataType: "json",
        data: {
          action: "vinapet_submit_checkout_with_erp",
          nonce: vinapet_ajax.nonce,
          checkout_form: JSON.stringify(formData), // ✅ Gửi toàn bộ formData đã format
        },
        success: function (response) {
          console.log("AJAX Response:", response);

          if (response.success) {
            vinapet_show_message("success", response.data.message);

            // Log quotation info
            if (response.data.quotation_id) {
              console.log("Quotation created:", response.data.quotation_id);
            }

            // Redirect sau 2 giây
            setTimeout(function () {
              window.location.href =
                response.data.redirect || vinapet_ajax.home_url + "/tai-khoan";
            }, 2000);
          } else {
            vinapet_show_message(
              "error",
              response.data.message || "Có lỗi xảy ra khi gửi yêu cầu!"
            );
            $button.prop("disabled", false).html(originalText);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", {
            status: status,
            error: error,
            response: xhr.responseText,
          });

          vinapet_show_message(
            "error",
            "Có lỗi kết nối! Vui lòng kiểm tra và thử lại."
          );
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
