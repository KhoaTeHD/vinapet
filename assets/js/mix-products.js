(function ($) {
  $(document).ready(function () {
    // Mix data structure
    let mixData = {
      product1: {
        code: "CAT-TRE-001",
        name: "Cát Tre",
        percentage: 100,
        pricing_rules: mainMixProduct.pricing_rules ?? [],
        quantity: 0, // - sẽ được tính động
        price_per_kg: 0, // - sẽ được tính động
        total_price: 0, // - sẽ được tính động
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
      const $selectedOption = $(this).find("option:selected");
      const selectedValue = $(this).val();

      if (selectedValue) {
        // ✅ Parse pricing rules từ data-attribute
        let pricingRules = [];
        try {
          const rulesAttr = $selectedOption.attr("data-pricing-rules");
          console.log("Pricing rules attribute:", rulesAttr);
          pricingRules = rulesAttr ? JSON.parse(rulesAttr) : [];
        } catch (e) {
          console.error("Error parsing pricing rules for product2:", e);
        }

        mixData.product2 = {
          code: selectedValue,
          name: $selectedOption.data("name"),
          percentage: 50,
          pricing_rules: pricingRules, // ✅ THÊM MỚI
          quantity: 0, // ✅ THÊM MỚI
          price_per_kg: 0, // ✅ THÊM MỚI
          total_price: 0, // ✅ THÊM MỚI
        };

        showSecondProductContent(
          $selectedOption.data("name"),
          $selectedOption.data("description")
        );
        showMixOptions();
        redistributePercentages();
        updateMixSliders();
        showFooterSummary();
        updateFooterSummary(); // ✅ Hàm này sẽ tính toán pricing
        $(".secondary-product").addClass("has-selection");
        $("#mixsuggest-container-2").slideUp();
      } else {
        resetProduct2();
      }
    });

    // Handle third product selection
    $("#third-product-select").on("change", function () {
      const $selectedOption = $(this).find("option:selected");
      const selectedValue = $(this).val();

      if (selectedValue) {
        // ✅ Parse pricing rules từ data-attribute
        let pricingRules = [];
        try {
          const rulesAttr = $selectedOption.attr("data-pricing-rules");
          pricingRules = rulesAttr ? JSON.parse(rulesAttr) : [];
        } catch (e) {
          console.error("Error parsing pricing rules for product2:", e);
        }

        mixData.product3 = {
          code: selectedValue,
          name: $selectedOption.data("name"),
          percentage: 33,
          pricing_rules: pricingRules, // ✅ THÊM MỚI
          quantity: 0, // ✅ THÊM MỚI
          price_per_kg: 0, // ✅ THÊM MỚI
          total_price: 0, // ✅ THÊM MỚI
        };

        showThirdProductContent(
          $selectedOption.data("name"),
          $selectedOption.data("description")
        );
        showMixOptions();
        redistributePercentages();
        updateMixSliders();
        showFooterSummary();
        updateFooterSummary(); // ✅ Hàm này sẽ tính toán pricing
        $(".third-product").addClass("has-selection");
        $("#mixsuggest-container-3").slideUp();
      } else {
        resetProduct3();
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
     * Tìm giá phù hợp dựa trên pricing rules và quantity
     * @param {Array} pricingRules - Mảng pricing rules từ ERPNext
     * @param {Number} quantity - Số lượng cần tính giá (kg)
     * @returns {Number} - Giá mỗi kg (VND)
     */
    function getPriceByQuantity(pricingRules, quantity) {
      if (!pricingRules || pricingRules.length === 0) {
        console.warn("No pricing rules available, returning 0");
        return 0;
      }

      // Sắp xếp rules theo min_qty tăng dần để tìm đúng bậc
      const sortedRules = [...pricingRules].sort((a, b) => {
        return parseFloat(a.min_qty || 0) - parseFloat(b.min_qty || 0);
      });

      // Tìm rule phù hợp
      for (let i = sortedRules.length - 1; i >= 0; i--) {
        const rule = sortedRules[i];
        const minQty = parseFloat(rule.min_qty) || 0;
        const maxQty = parseFloat(rule.max_qty) || 0;

        // max_qty = 0 nghĩa là không giới hạn trên
        if (quantity >= minQty) {
          if (maxQty === 0 || quantity <= maxQty) {
            console.log(
              `✅ Matched rule: ${rule.title} (${minQty}-${
                maxQty || "∞"
              }kg) = ${rule.value} VND/kg`
            );
            return parseFloat(rule.value) || 0;
          }
        }
      }

      // Fallback: dùng rule đầu tiên nếu không match
      console.warn("No matching rule found, using first rule as fallback");
      return parseFloat(sortedRules[0]?.value) || 0;
    }

    /**
     * Tính toán pricing cho TẤT CẢ các products trong mix
     * Gọi hàm này mỗi khi percentage hoặc total_quantity thay đổi
     */
    function calculateProductPricing() {
      const totalQty = calculateTotalQuantity(); // Tổng kg từ options
      const activeProducts = getActiveProducts();

      activeProducts.forEach((productKey) => {
        const product = mixData[productKey];

        // Tính quantity cho product này
        product.quantity = (product.percentage / 100) * totalQty;

        //console.log(`🔍 ${productKey}: ${product.pricing_rules}`);
        // Tính giá theo quantity
        product.price_per_kg = getPriceByQuantity(
          product.pricing_rules,
          product.quantity
        );

        // Tính tổng tiền
        product.total_price = product.quantity * product.price_per_kg;

        console.log(
          `💰 ${productKey}: ${product.quantity}kg × ${product.price_per_kg}đ = ${product.total_price}đ`
        );
      });
    }

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

      // ✅ BƯỚC 1: Tính pricing cho tất cả products
      calculateProductPricing();

      // ✅ BƯỚC 2: Tính tổng của mix
      const totalQuantity = calculateTotalQuantity();

      // Tổng giá mix = tổng của từng product
      const totalMixPrice = activeProducts.reduce((sum, productKey) => {
        return sum + (mixData[productKey].total_price || 0);
      }, 0);

      // Giá trung bình mỗi kg của mix
      const avgPricePerKg =
        totalQuantity > 0 ? totalMixPrice / totalQuantity : 0;

      // ✅ BƯỚC 3: Format hiển thị
      let quantityText;
      if (totalQuantity >= 1000) {
        quantityText = (totalQuantity / 1000).toLocaleString("vi-VN") + " tấn";
      } else {
        quantityText = totalQuantity.toLocaleString("vi-VN") + " kg";
      }

      let formattedTotalPrice;
      if (totalMixPrice >= 1000000000) {
        formattedTotalPrice = (totalMixPrice / 1000000000).toFixed(1) + " tỷ";
      } else if (totalMixPrice >= 1000000) {
        formattedTotalPrice = Math.round(totalMixPrice / 1000000) + " triệu";
      } else {
        formattedTotalPrice = formatPrice(totalMixPrice) + " đ";
      }

      const productCountText =
        activeProducts.length === 2
          ? "Mix 2 loại sản phẩm"
          : "Mix 3 loại sản phẩm";

      // ✅ BƯỚC 4: Cập nhật UI
      $(".footer-top-row").first().text(productCountText);
      $("#footer-total-quantity").text(quantityText);
      $("#footer-estimated-price").text(formattedTotalPrice);
      $("#footer-price-per-kg").text(formatPrice(avgPricePerKg) + " đ/kg");

      // ✅ BƯỚC 5: Log debug (có thể xóa sau khi test xong)
      console.log("📊 Mix Summary:", {
        totalQuantity: totalQuantity + "kg",
        totalPrice: totalMixPrice.toLocaleString("vi-VN") + "đ",
        avgPrice: avgPricePerKg.toLocaleString("vi-VN") + "đ/kg",
        breakdown: activeProducts.map((key) => ({
          product: mixData[key].name,
          percentage: mixData[key].percentage + "%",
          quantity: mixData[key].quantity + "kg",
          price: mixData[key].price_per_kg.toLocaleString("vi-VN") + "đ/kg",
          total: mixData[key].total_price.toLocaleString("vi-VN") + "đ",
        })),
      });
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

      // ✅ Đảm bảo pricing đã được tính
      calculateProductPricing();

      // Collect mix data
      const checkoutMixData = {
        products: {
          product1: mixData.product1
            ? {
                code: mixData.product1.code,
                name: mixData.product1.name,
                percentage: mixData.product1.percentage,
                quantity: mixData.product1.quantity, // ✅ THÊM
                price_per_kg: mixData.product1.price_per_kg, // ✅ THÊM
                total_price: mixData.product1.total_price, // ✅ THÊM
              }
            : null,

          product2: mixData.product2
            ? {
                code: mixData.product2.code,
                name: mixData.product2.name,
                percentage: mixData.product2.percentage,
                quantity: mixData.product2.quantity, // ✅ THÊM
                price_per_kg: mixData.product2.price_per_kg, // ✅ THÊM
                total_price: mixData.product2.total_price, // ✅ THÊM
              }
            : null,

          product3: mixData.product3
            ? {
                code: mixData.product3.code,
                name: mixData.product3.name,
                percentage: mixData.product3.percentage,
                quantity: mixData.product3.quantity, // ✅ THÊM
                price_per_kg: mixData.product3.price_per_kg, // ✅ THÊM
                total_price: mixData.product3.total_price, // ✅ THÊM
              }
            : null,
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
          total: Object.values(mixData)
            .filter((p) => p && p.total_price)
            .reduce((sum, p) => sum + p.total_price, 0),
          per_kg:
            calculateTotalQuantity() > 0
              ? Object.values(mixData)
                  .filter((p) => p && p.total_price)
                  .reduce((sum, p) => sum + p.total_price, 0) /
                calculateTotalQuantity()
              : 0,
        },
      };

      // Validate
      if (!validateMixData(checkoutMixData)) {
        return;
      }

      // AJAX call
      $.ajax({
        url: vinapet_ajax.ajax_url,
        type: "POST",
        data: {
          action: "vinapet_store_mix_session",
          nonce: vinapet_ajax.nonce,
          mix_data: checkoutMixData,
        },
        success: function (response) {
          if (response.success) {
            window.location.href = response.data.redirect;
          } else {
            alert(response.data || "Có lỗi xảy ra!");
          }
        },
        error: function () {
          alert("Lỗi kết nối! Vui lòng thử lại.");
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
      console.log("Validating mix data:", data);
      const activeProducts = Object.values(data.products).filter((p) => p && p.name);
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
