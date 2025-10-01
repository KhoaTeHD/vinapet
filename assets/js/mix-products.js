(function ($) {
  $(document).ready(function () {
    // Mix data structure
    let mixData = {
      product1: {
        code: "CAT-TRE-001",
        name: "Cát Tre",
        percentage: 100,
      },
      product2: null,
      product3: null,
      options: {
        color: "xanh_non",
        scent: "tro_xanh",
        quantity: "10000",
        packaging: "tui_jumbo_1000",
      },
    };

    // Price calculation constants
    const basePrices = {
      5000: 34000,
      7000: 34000,
      10000: 34000,
      khac: 34000,
    };

    const packagingPrices = {
      tui_jumbo_500: 800,
      tui_jumbo_1000: 0,
    };

    // =============================================================================
    // PRODUCT MANAGEMENT
    // =============================================================================

    // Handle second product selection
    $("#second-product-select").on("change", function () {
      const selectedValue = $(this).val();
      const selectedName = $(this).find("option:selected").data("name");
      const selectedDescription = $(this)
        .find("option:selected")
        .data("description");

      if (selectedValue) {
        mixData.product2 = {
          code: selectedValue,
          name: selectedName,
          percentage: 50,
        };

        showSecondProductContent(selectedName, selectedDescription);
        showMixOptions();
        redistributePercentages();
        updateMixSliders();
        showFooterSummary();
        $(".secondary-product").addClass("has-selection");
        $("#mixsuggest-container-2").slideUp();
      } else {
        resetProduct2();
      }
    });

    // Handle third product selection
    $("#third-product-select").on("change", function () {
      const selectedValue = $(this).val();
      const selectedName = $(this).find("option:selected").data("name");
      const selectedDescription = $(this)
        .find("option:selected")
        .data("description");

      if (selectedValue) {
        mixData.product3 = {
          code: selectedValue,
          name: selectedName,
          percentage: 33,
        };

        showThirdProductContent(selectedName, selectedDescription);
        redistributePercentages();
        updateMixSliders();
        updateFooterSummary();
        $(".third-product").addClass("has-selection");
      }
    });

    // Add product badge click
    $("#add-product-badge").on("click", function () {
      if (!mixData.product2) {
        showMessage(
          "Vui lòng chọn sản phẩm thứ 2 trước khi thêm sản phẩm thứ 3.",
          "warning"
        );
        return;
      }
      showThirdProduct();
    });

    // Remove product 3
    $("#remove-product-3").on("click", function (e) {
      e.stopPropagation();
      removeThirdProduct();
    });

    // Change product links
    $("#change-product-2").on("click", function (e) {
      e.preventDefault();
      resetProduct2();
      if (mixData.product3) {
        removeThirdProduct();
      }
    });

    $("#change-product-3").on("click", function (e) {
      e.preventDefault();
      resetProduct3();
    });

    // =============================================================================
    // PRODUCT DISPLAY FUNCTIONS
    // =============================================================================

    function showSecondProductContent(name, description) {
      $(
        ".secondary-product .product-title, .secondary-product .product-description"
      ).remove();
      $(".secondary-product .product-header").append(`
                <h3 class="product-title">${name}</h3>
                <p class="product-description">${description}</p>
            `);

      $(".second-product-content").slideDown(300);
      $("#dropdown-container-2").addClass("selected");
      $(".progress-section").addClass("show");
    }

    function showThirdProduct() {
      $("#third-product-card").addClass("visible");
      $("#add-product-badge").addClass("hidden");
    }

    function showThirdProductContent(name, description) {
      $(
        ".third-product .product-title, .third-product .product-description"
      ).remove();
      $(".third-product .product-header").append(`
                <h3 class="product-title">${name}</h3>
                <p class="product-description">${description}</p>
            `);

      $(".third-product-content").slideDown(300);
      $("#dropdown-container-3").addClass("selected");
      $(".third-product .progress-section").addClass("show");
    }

    function removeThirdProduct() {
      mixData.product3 = null;
      $("#third-product-card").removeClass("visible");
      $("#add-product-badge").removeClass("hidden");
      $("#third-product-select").val("");
      $(".third-product-content").hide();
      $(
        ".third-product .product-title, .third-product .product-description"
      ).remove();
      $("#dropdown-container-3").removeClass("selected");
      $(".third-product").removeClass("has-selection");

      redistributePercentages();
      updateMixSliders();
      updateFooterSummary();
    }

    function resetProduct2() {
      mixData.product2 = null;
      $("#second-product-select").val("");
      $(".second-product-content").slideUp(300);
      $(
        ".secondary-product .product-title, .secondary-product .product-description"
      ).remove();
      $("#dropdown-container-2").removeClass("selected");
      $(".secondary-product").removeClass("has-selection");
      $(".progress-section").removeClass("show");
      $("#mixsuggest-container-2").slideDown(300);
      $("#mixsuggest-container-3").slideDown(300);

      mixData.product1.percentage = 100;
      updateMixSliders();
      hideMixOptions();
      hideFooterSummary();
    }

    function resetProduct3() {
      mixData.product3 = null;
      $("#third-product-select").val("");
      $(".third-product-content").slideUp(300);
      $(
        ".third-product .product-title, .third-product .product-description"
      ).remove();
      $("#dropdown-container-3").removeClass("selected");
      $(".third-product").removeClass("has-selection");

      redistributePercentages();
      updateMixSliders();
      updateFooterSummary();
    }

    // =============================================================================
    // MIX PERCENTAGE MANAGEMENT
    // =============================================================================

    // Handle mix slider input
    $(document).on("input", ".mix-slider", function () {
      const productNum = $(this).data("product");
      const value = parseInt($(this).val());
      updateMixPercentage(productNum, value);
    });

    function updateMixPercentage(productNum, value) {
      // Update the changed product
      mixData[`product${productNum}`].percentage = value;

      // Redistribute other products
      redistributePercentages(productNum);
      updateMixSliders();
      updateFooterSummary();
    }

    function redistributePercentages(changedProduct = null) {
      const activeProducts = getActiveProducts();
      const productCount = activeProducts.length;

      if (productCount === 1) {
        mixData.product1.percentage = 100;
        return;
      }

      if (changedProduct) {
        // User changed a specific product, adjust others proportionally
        const changedValue = mixData[`product${changedProduct}`].percentage;
        const remaining = 100 - changedValue;
        const otherProducts = activeProducts.filter(
          (p) => p !== `product${changedProduct}`
        );

        if (otherProducts.length === 1) {
          // Only one other product - give it all remaining
          mixData[otherProducts[0]].percentage = remaining;
        } else if (otherProducts.length === 2) {
          // Two other products - distribute proportionally
          const currentTotal =
            mixData[otherProducts[0]].percentage +
            mixData[otherProducts[1]].percentage;
          if (currentTotal > 0) {
            const ratio1 = mixData[otherProducts[0]].percentage / currentTotal;
            const ratio2 = mixData[otherProducts[1]].percentage / currentTotal;

            mixData[otherProducts[0]].percentage = Math.round(
              remaining * ratio1
            );
            mixData[otherProducts[1]].percentage =
              remaining - mixData[otherProducts[0]].percentage;
          } else {
            // Equal distribution if both are 0
            mixData[otherProducts[0]].percentage = Math.round(remaining / 2);
            mixData[otherProducts[1]].percentage =
              remaining - mixData[otherProducts[0]].percentage;
          }
        }
      } else {
        // Initial distribution - equal percentages
        const equalPercentage = Math.floor(100 / productCount);
        const remainder = 100 % productCount;

        activeProducts.forEach((product, index) => {
          mixData[product].percentage =
            equalPercentage + (index < remainder ? 1 : 0);
        });
      }

      // Ensure percentages are within valid ranges (10-90)
      activeProducts.forEach((product) => {
        const current = mixData[product].percentage;
        if (current < 10) mixData[product].percentage = 10;
        if (current > 90) mixData[product].percentage = 90;
      });

      // Final adjustment to ensure exactly 100%
      const total = activeProducts.reduce(
        (sum, product) => sum + mixData[product].percentage,
        0
      );
      if (total !== 100) {
        const diff = 100 - total;
        mixData[activeProducts[0]].percentage += diff;

        // Ensure the adjusted value is still within bounds
        if (mixData[activeProducts[0]].percentage < 10) {
          mixData[activeProducts[0]].percentage = 10;
          // Redistribute the excess to other products
          const excess =
            10 - (100 - total + mixData[activeProducts[0]].percentage);
          if (activeProducts.length > 1) {
            mixData[activeProducts[1]].percentage -= excess;
          }
        }
        if (mixData[activeProducts[0]].percentage > 90) {
          mixData[activeProducts[0]].percentage = 90;
          const excess =
            100 - total + mixData[activeProducts[0]].percentage - 90;
          if (activeProducts.length > 1) {
            mixData[activeProducts[1]].percentage += excess;
          }
        }
      }
    }

    function getActiveProducts() {
      const products = ["product1"];
      if (mixData.product2) products.push("product2");
      if (mixData.product3) products.push("product3");
      return products;
    }

    function updateMixSliders() {
      const activeProducts = getActiveProducts();

      activeProducts.forEach((product) => {
        const productNum = product.replace("product", "");
        const percentage = mixData[product].percentage;

        // Update slider value
        $(`#product${productNum}-slider`).val(percentage);

        // Update percentage display
        $(`#product${productNum}-percentage`).text(percentage + "%");

        // Update progress bar
        $(`#product${productNum}-fill`).css("width", percentage + "%");

        // Update slider track color
        const slider = $(`#product${productNum}-slider`)[0];
        if (slider) {
          const progress =
            ((percentage - slider.min) / (slider.max - slider.min)) * 100;
          slider.style.background = `linear-gradient(to right, var(--highlight-color) 0%, var(--highlight-color) ${progress}%, var(--border-color) ${progress}%, var(--border-color) 100%)`;
        }
      });
    }

    // =============================================================================
    // OPTIONS MANAGEMENT
    // =============================================================================

    function showMixOptions() {
      $("#mix-options").show().addClass("show");
      scrollToOptions();
    }

    function hideMixOptions() {
      $("#mix-options").removeClass("show");
      setTimeout(() => {
        $("#mix-options").hide();
      }, 500);
    }

    function showFooterSummary() {
      $("#mix-footer").show().addClass("show");
    }

    function hideFooterSummary() {
      $("#mix-footer").removeClass("show");
      setTimeout(() => {
        $("#mix-footer").hide();
      }, 500);
    }

    // Handle option selections
    $(".color-option").on("click", function () {
      $(".color-option").removeClass("selected");
      $(this).addClass("selected");
      $(this).find('input[type="radio"]').prop("checked", true);
      $(".color-name").text(this.getAttribute("value"));
      mixData.options.color = $(this).find("input").val();
      updateFooterSummary();
    });

    $(".scent-option").on("click", function () {
      $(".scent-option").removeClass("selected");
      $(this).addClass("selected");
      $(this).find('input[type="radio"]').prop("checked", true);
      mixData.options.scent = $(this).find("input").val();
      updateFooterSummary();
    });

    $(".grain-size-option").on("click", function () {
      $(".grain-size-option").removeClass("selected");
      $(this).addClass("selected");
      $(this).find('input[type="radio"]').prop("checked", true);
      mixData.options.grainSize = $(this).find("input").val();
      updateFooterSummary();
    });

    $(".quantity-option").on("click", function () {
      $(".quantity-option").removeClass("selected");
      $(this).addClass("selected");
      $(this).find('input[type="radio"]').prop("checked", true);
      mixData.options.quantity = $(this).find("input").val();
      updateFooterSummary();
    });

    $(".packaging-option").on("click", function () {
      $(".packaging-option").removeClass("selected");
      $(this).addClass("selected");
      $(this).find('input[type="radio"]').prop("checked", true);
      mixData.options.packaging = $(this).find("input").val();
      updateFooterSummary();
    });

    // =============================================================================
    // CALCULATION HELPERS
    // =============================================================================

    /**
     * Tính tổng số lượng (kg) dựa trên option được chọn
     * @returns {number} Tổng số lượng tính bằng kg
     */
    function calculateTotalQuantity() {
      if (!mixData || !mixData.options || !mixData.options.quantity) {
        console.warn("Quantity option not selected");
        return 0;
      }

      return mixData.options.quantity === "khac"
        ? 10000
        : parseInt(mixData.options.quantity);
    }

    /**
     * Tính giá mỗi kg dựa trên số lượng và loại bao bì
     * @returns {number} Giá mỗi kg (đ/kg)
     */
    function calculatePricePerKg() {
      const quantity = mixData.options.quantity || "10000";
      const packaging = mixData.options.packaging || "normal";

      const basePrice = basePrices[quantity] || basePrices["10000"];
      const packagingPrice = packagingPrices[packaging] || 0;

      return basePrice + packagingPrice;
    }

    /**
     * Tính tổng giá trị đơn hàng
     * @returns {number} Tổng giá trị (đồng)
     */
    function calculateTotalPrice() {
      const quantity = calculateTotalQuantity();
      const pricePerKg = calculatePricePerKg();

      return quantity * pricePerKg;
    }

    // =============================================================================
    // FOOTER AND PRICING
    // =============================================================================

    function updateFooterSummary() {
      const activeProducts = getActiveProducts();
      if (activeProducts.length < 2) return;

      // ✅ Sử dụng các hàm helper
      const quantity = calculateTotalQuantity();
      const totalPrice = calculateTotalPrice();
      const pricePerKg = calculatePricePerKg();

      // Update quantity display
      let quantityText;
      if (quantity >= 1000) {
        quantityText = (quantity / 1000).toLocaleString("vi-VN") + " tấn";
      } else {
        quantityText = quantity.toLocaleString("vi-VN") + " kg";
      }

      // Update price display
      let formattedTotalPrice;
      if (totalPrice >= 1000000000) {
        formattedTotalPrice = Math.round(totalPrice / 1000000000) + " tỷ";
      } else if (totalPrice >= 1000000) {
        formattedTotalPrice = Math.round(totalPrice / 1000000) + " triệu";
      } else {
        formattedTotalPrice = formatPrice(totalPrice) + " đ";
      }

      // Update footer text based on number of products
      const productCountText =
        activeProducts.length === 2
          ? "Mix 2 loại sản phẩm"
          : "Mix 3 loại sản phẩm";
      $(".footer-top-row").first().text(productCountText);

      // Update footer values
      $("#footer-total-quantity").text(quantityText);
      $("#footer-estimated-price").text(formattedTotalPrice);
      $("#footer-price-per-kg").text(formatPrice(pricePerKg) + " đ/kg");
    }

    function formatPrice(price) {
      return Math.round(price).toLocaleString("vi-VN");
    }

    // =============================================================================
    // CHECKOUT AND NAVIGATION
    // =============================================================================

    // Submit mix data về server
    $("#next-step-button").on("click", function (e) {
      e.preventDefault();

      // Collect mix data (existing logic)
      const mixData = {
        products: {
          product1: getMixProduct("product1"),
          product2: getMixProduct("product2"),
          product3: getMixProduct("product3"),
        },
        options: {
          color: $('input[name="color"]:checked').val(),
          scent: $('input[name="scent"]:checked').val(),
          packaging: $('input[name="packaging"]:checked').val(),
          quantity: $('input[name="quantity"]:checked').val(),
        },
        quantities: {
          total: calculateTotalQuantity(),
        },
        pricing: {
          rate: basePrices[$('input[name="quantity"]:checked').val()],
          total: calculateTotalPrice(),
          per_kg: calculatePricePerKg(),
        },
      };

      // Validate mix data
      if (!validateMixData(mixData)) {
        return;
      }


      // AJAX call để store mix data trong PHP session
      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: {
          action: "vinapet_store_mix_session",
          nonce: vinapet_ajax.nonce,
          mix_data: mixData,
        },
        beforeSend: function () {
          showLoading($(this));
        },
        success: function (response) {
          if (response.success) {
            // Redirect to checkout
            window.location.href = "/vinapet/checkout";
          } else {
            alert(response.data || "Có lỗi xảy ra!");
          }
        },
        error: function () {
          alert("Lỗi kết nối! Vui lòng thử lại.");
        },
        complete: function () {
          hideLoading($("#next-step-button"));
        },
      });
    });

    // Helper functions (existing)
    function getMixProduct(productKey) {
      // Existing logic to get product data
      return mixData[productKey] || {};
    }

    function validateMixData(data) {
      // Validate total percentage = 100%
      const activeProducts = Object.values(data.products).filter((p) => p.name);
      if (activeProducts.length < 2) {
        alert("Cần ít nhất 2 sản phẩm để mix!");
        return false;
      }

      const totalPercentage = activeProducts.reduce(
        (sum, p) => sum + (p.percentage || 0),
        0
      );
      if (Math.abs(totalPercentage - 100) > 1) {
        alert("Tổng tỷ lệ phải bằng 100%!");
        return false;
      }

      return true;
    }

    // =============================================================================
    // UTILITY FUNCTIONS
    // =============================================================================

    function scrollToOptions() {
      if ($("#mix-options").is(":visible")) {
        $("html, body").animate(
          {
            scrollTop: $("#mix-options").offset().top - 100,
          },
          800
        );
      }
    }

    function showLoading(element) {
      element.prop("disabled", true).html(`
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Đang xử lý...
            `);
    }

    function hideLoading(element, originalText) {
      element.prop("disabled", false).html(originalText);
    }

    function showMessage(message, type = "info") {
      $(".message-popup").remove();

      const messageClass =
        type === "success"
          ? "success"
          : type === "error"
          ? "error"
          : type === "warning"
          ? "warning"
          : "info";
      const icon =
        type === "success"
          ? "✓"
          : type === "error"
          ? "✗"
          : type === "warning"
          ? "⚠"
          : "ℹ";

      const popup = $(`
                <div class="message-popup ${messageClass}">
                    <span class="message-icon">${icon}</span>
                    <span class="message-text">${message}</span>
                </div>
            `);

      $("body").append(popup);

      setTimeout(() => {
        popup.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }

    // Handle "View bag details" link
    $(".view-details-link").on("click", function (e) {
      e.preventDefault();
      showMessage(
        "Tính năng xem minh họa các loại túi sẽ được cập nhật sớm.",
        "info"
      );
    });

    // =============================================================================
    // KEYBOARD AND ACCESSIBILITY
    // =============================================================================

    $(document).on("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        const focused = $(document.activeElement);
        if (
          focused.hasClass("color-option") ||
          focused.hasClass("scent-option") ||
          focused.hasClass("quantity-option") ||
          focused.hasClass("packaging-option")
        ) {
          e.preventDefault();
          focused.click();
        }
      }
    });

    $(".color-option, .scent-option, .quantity-option, .packaging-option").attr(
      "tabindex",
      "0"
    );

    // =============================================================================
    // INITIALIZATION AND DEBUG
    // =============================================================================

    // Auto-select main product if passed from URL

    if (mainMixProduct) {
      mixData.product1.code = mainMixProduct.product_id;
      mixData.product1.name = mainMixProduct.product_name;
    }

    // Initialize UI
    $(".progress-section").removeClass("show");
    updateMixSliders();

    // Debug functions (for development)
    window.resetMixData = function () {
      mixData.product2 = null;
      mixData.product3 = null;
      resetProduct2();
      removeThirdProduct();
      console.log("Mix data reset");
    };

    window.getMixData = function () {
      console.log("Current Mix Data:", mixData);
      return mixData;
    };

    window.debugPercentages = function () {
      const activeProducts = getActiveProducts();
      const total = activeProducts.reduce(
        (sum, product) => sum + mixData[product].percentage,
        0
      );
      console.log("Active products:", activeProducts);
      console.log(
        "Percentages:",
        activeProducts.map((p) => `${p}: ${mixData[p].percentage}%`)
      );
      console.log("Total:", total + "%");
    };

    // Add CSS for animations and messages
    if (!$("#mix-animations").length) {
      $("head").append(`
                <style id="mix-animations">
                    .animate-spin {
                        animation: spin 1s linear infinite;
                    }
                    
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                    
                    .message-popup {
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        padding: 12px 16px;
                        border-radius: 8px;
                        font-weight: 500;
                        z-index: 9999;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                        animation: slideInRight 0.3s ease;
                    }
                    
                    .message-popup.success { background: #10B981; color: white; }
                    .message-popup.error { background: #EF4444; color: white; }
                    .message-popup.warning { background: #F59E0B; color: white; }
                    .message-popup.info { background: #3B82F6; color: white; }
                    
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                </style>
            `);
    }

    console.log(
      "Mix Products page initialized with 3-product support and percentage sliders"
    );
  });
})(jQuery);
