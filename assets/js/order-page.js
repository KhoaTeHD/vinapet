(function($) {
    $(document).ready(function() {
        // Price calculation constants
        const basePrices = {
            '100': 50000,   // 100kg = 50,000/kg
            '300': 50000,   // 300kg = 50,000/kg
            '500': 50000,   // 500kg = 50,000/kg
            '1000': 42000,  // 1 tấn = 42,000/kg
            '3000': 42000,  // 3 tấn = 42,000/kg
            '5000': 34000   // 5 tấn = 34,000/kg
        };
        
        const packagingPrices = {
            'pa_pe_thuong': 2600,
            'pa_pe_khong': 2600,
            'pa_pe_decal': 2350,
            'bao_dua': 160,
            'tui_jumbo': 105
        };
        
        // Handle variant selection - SKU với radio ẩn (như checkout page)
        $('.variant-option').on('click', function() {
            // Uncheck all other variants first
            $('.variant-option input[type="radio"]').prop('checked', false);
            // Check this variant
            $(this).find('input[type="radio"]').prop('checked', true);
            updateFooterSummary();
        });

        // Handle quantity selection - Radio hiển thị ở bên trái
        $('.quantity-option').on('click', function(e) {
            // Nếu click vào radio button thì không cần xử lý gì thêm
            if (e.target.type === 'radio') {
                updatePriceTierHighlighting();
                updateFooterSummary();
                return;
            }
            
            // Nếu click vào label/container thì check radio
            $(this).find('input[type="radio"]').prop('checked', true);
            updatePriceTierHighlighting();
            updateFooterSummary();
        });

        // Direct radio change handler for quantity
        $('input[name="quantity"]').on('change', function() {
            updatePriceTierHighlighting();
            updateFooterSummary();
        });

        // Handle packaging selection - Radio hiển thị ở bên trái
        $('.packaging-option').on('click', function(e) {
            // Nếu click vào radio button thì không cần xử lý gì thêm
            if (e.target.type === 'radio') {
                updateFooterSummary();
                return;
            }
            
            // Nếu click vào label/container thì check radio
            $(this).find('input[type="radio"]').prop('checked', true);
            updateFooterSummary();
        });

        // Direct radio change handler for packaging
        $('input[name="packaging"]').on('change', function() {
            updateFooterSummary();
        });
        
        // Update price tier highlighting based on selected quantity
        function updatePriceTierHighlighting() {
            const selectedQuantity = $('input[name="quantity"]:checked').val();
            
            // Remove all active classes first
            $('.size-option').removeClass('active');
            
            // Find and highlight the correct price tier
            $('.size-option').each(function() {
                const quantities = $(this).data('quantities');
                if (quantities && quantities.includes(selectedQuantity)) {
                    $(this).addClass('active');
                }
            });
        }
        
        // Update footer summary
        function updateFooterSummary() {
            const selectedQuantity = $('input[name="quantity"]:checked').val();
            const selectedPackaging = $('input[name="packaging"]:checked').val();
            const selectedVariant = $('input[name="variant"]:checked').val();
            
            if (!selectedQuantity || !selectedPackaging || !selectedVariant) return;
            
            const quantity = parseInt(selectedQuantity);
            const basePrice = basePrices[selectedQuantity];
            const packagingPrice = packagingPrices[selectedPackaging];
            
            const totalBasePrice = quantity * basePrice;
            const totalPackagingFee = quantity * packagingPrice;
            const totalPrice = totalBasePrice + totalPackagingFee;
            const pricePerKg = totalPrice / quantity;
            
            // Update footer display
            let quantityText = quantity + ' kg';
            
            // Format total price for display
            let formattedTotalPrice;
            if (totalPrice >= 1000000) {
                formattedTotalPrice = Math.round(totalPrice / 1000000) + ' triệu';
            } else {
                formattedTotalPrice = formatPrice(totalPrice) + ' đ';
            }
            
            // Update footer values
            $('#footer-sku-count').text('1 SKU');
            $('#footer-bag-count').text('1 loại túi');
            $('#footer-total-quantity').text(quantityText);
            $('#footer-estimated-price').text(formattedTotalPrice);
            $('#footer-price-per-kg').text(formatPrice(pricePerKg) + ' đ/kg');
        }
        
        // Format price with thousands separator
        function formatPrice(price) {
            return Math.round(price).toLocaleString('vi-VN');
        }
        
        // Initialize with default values
        updatePriceTierHighlighting();
        updateFooterSummary();
        
        // Handle next step button
        $('#next-step-button').on('click', function(e) {
            e.preventDefault();
            
            const formData = {
                variant: $('input[name="variant"]:checked').val(),
                quantity: $('input[name="quantity"]:checked').val(),
                packaging: $('input[name="packaging"]:checked').val()
            };
            
            // Validate form data
            if (!formData.variant || !formData.quantity || !formData.packaging) {
                alert('Vui lòng chọn đầy đủ thông tin trước khi tiếp tục.');
                return;
            }
            
            // Calculate totals for order data
            const quantity = parseInt(formData.quantity);
            const basePrice = basePrices[formData.quantity];
            const packagingPrice = packagingPrices[formData.packaging];
            const totalBasePrice = quantity * basePrice;
            const totalPackagingFee = quantity * packagingPrice;
            const totalPrice = totalBasePrice + totalPackagingFee;
            
            // Store order data in session storage for next page
            const orderData = {
                ...formData,
                quantity_kg: quantity,
                base_price_per_kg: basePrice,
                packaging_price_per_kg: packagingPrice,
                total_base_price: totalBasePrice,
                total_packaging_fee: totalPackagingFee,
                total_price: totalPrice,
                product_code: new URLSearchParams(window.location.search).get('product')
            };
            
            sessionStorage.setItem('vinapet_order_data', JSON.stringify(orderData));
            
            // Redirect to summary page
            window.location.href = '/vinapet/checkout';
        });
        
        // Handle "View bag details" link click
        $('.view-details-link').on('click', function(e) {
            e.preventDefault();
            alert('Tính năng xem minh họa các loại túi sẽ được cập nhật sớm.');
        });
        
        // Auto-select variant if passed from product page
        const urlParams = new URLSearchParams(window.location.search);
        const selectedVariant = urlParams.get('variant');
        if (selectedVariant) {
            const targetVariant = $('input[name="variant"][value="' + selectedVariant + '"]');
            if (targetVariant.length) {
                targetVariant.prop('checked', true);
                updateFooterSummary();
            }
        }
        
        // Handle keyboard navigation for accessibility
        $(document).on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                const focused = $(document.activeElement);
                if (focused.hasClass('variant-option') || 
                    focused.hasClass('quantity-option') || 
                    focused.hasClass('packaging-option')) {
                    e.preventDefault();
                    focused.click();
                }
            }
        });
        
        // Make options focusable for accessibility
        $('.variant-option, .quantity-option, .packaging-option').attr('tabindex', '0');
    });
})(jQuery);