(function($) {
    $(document).ready(function() {
        let mixData = {
            product1: {
                code: $('#second-product-select').data('main-product') || 'CAT-TRE-001',
                name: 'Cát Tre',
                percentage: 50
            },
            product2: null,
            options: {
                color: 'xanh_non',
                scent: 'tro_xanh',
                quantity: '10000',
                packaging: 'tui_jumbo_1000'
            }
        };
        
        // Price calculation constants
        const basePrices = {
            '5000': 34000,
            '7000': 34000,
            '10000': 34000,
            'khac': 34000
        };
        
        const packagingPrices = {
            'tui_jumbo_500': 800,
            'tui_jumbo_1000': 0
        };
        
        // Handle second product selection
        $('#second-product-select').on('change', function() {
            const selectedValue = $(this).val();
            const selectedName = $(this).find('option:selected').data('name');
            const selectedDescription = $(this).find('option:selected').data('description');
            
            if (selectedValue) {
                mixData.product2 = {
                    code: selectedValue,
                    name: selectedName,
                    percentage: 50
                };
                
                // Update UI
                showSecondProductContent(selectedName, selectedDescription);
                showMixOptions();
                updateProgressBars();
                showFooterSummary();
                
                // Mark secondary product as selected
                $('.secondary-product').addClass('has-selection');
            } else {
                mixData.product2 = null;
                hideSecondProductContent();
                hideMixOptions();
                hideFooterSummary();
                $('.secondary-product').removeClass('has-selection');
            }
        });
        
        // Show second product content
        function showSecondProductContent(name, description) {
            $('.secondary-product .product-title').remove();
            $('.secondary-product .product-description').remove();
            
            $('.secondary-product .product-header').append(`
                <h3 class="product-title">${name}</h3>
                <p class="product-description">${description}</p>
            `);
            
            $('.second-product-content').slideDown(300);
            $('.secondary-product .add-btn').fadeIn(300);
            
            // Ẩn hoàn toàn dropdown container và hiện chữ "Đổi sản phẩm"
            $('.product-dropdown-container').addClass('selected');
            $('.secondary-product').addClass('has-selection');
            
            // Show progress bars
            $('.progress-section').addClass('show');
        }
        
        // Hide second product content
        function hideSecondProductContent() {
            $('.second-product-content').slideUp(300);
            $('.secondary-product .add-btn').fadeOut(300);
            $('.secondary-product .product-title').remove();
            $('.secondary-product .product-description').remove();
            
            // Hiện lại dropdown container và ẩn chữ "Đổi sản phẩm"
            $('.product-dropdown-container').removeClass('selected');
            $('.secondary-product').removeClass('has-selection');
            
            // Hide progress bars
            $('.progress-section').removeClass('show');
        }
        
        // Handle change product link
        $('#change-product-link').on('click', function(e) {
            e.preventDefault();
            
            // Reset product 2
            mixData.product2 = null;
            $('#second-product-select').val('');
            
            // Hide content and show dropdown again
            hideSecondProductContent();
            hideMixOptions();
            hideFooterSummary();
        });
        
        // Show mix options
        function showMixOptions() {
            $('#mix-options').show().addClass('show');
        }
        
        // Hide mix options
        function hideMixOptions() {
            $('#mix-options').removeClass('show');
            setTimeout(() => {
                $('#mix-options').hide();
            }, 500);
        }
        
        // Show footer summary
        function showFooterSummary() {
            $('#mix-footer').show().addClass('show');
        }
        
        // Hide footer summary
        function hideFooterSummary() {
            $('#mix-footer').removeClass('show');
            setTimeout(() => {
                $('#mix-footer').hide();
            }, 500);
        }
        
        // Update progress bars
        function updateProgressBars() {
            const product1Percentage = mixData.product1.percentage;
            const product2Percentage = mixData.product2 ? mixData.product2.percentage : 0;
            
            $('.main-product .progress-fill').css('width', product1Percentage + '%');
            $('.main-product .progress-value').text(product1Percentage + '%');
            
            if (mixData.product2) {
                $('.secondary-product .progress-fill').css('width', product2Percentage + '%');
                $('.secondary-product .progress-value').text(product2Percentage + '%');
            }
        }
        
        // Handle color selection
        $('.color-option').on('click', function() {
            $('.color-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            mixData.options.color = $(this).find('input').val();
            updateFooterSummary();
        });
        
        // Handle scent selection
        $('.scent-option').on('click', function() {
            $('.scent-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            mixData.options.scent = $(this).find('input').val();
            updateFooterSummary();
        });
        
        // Handle quantity selection
        $('.quantity-option').on('click', function() {
            $('.quantity-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            mixData.options.quantity = $(this).find('input').val();
            updateFooterSummary();
        });
        
        // Handle packaging selection
        $('.packaging-option').on('click', function() {
            $('.packaging-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            
            mixData.options.packaging = $(this).find('input').val();
            updateFooterSummary();
        });
        
        // Update footer summary
        function updateFooterSummary() {
            if (!mixData.product2) return;
            
            const quantity = mixData.options.quantity === 'khac' ? 10000 : parseInt(mixData.options.quantity);
            const basePrice = basePrices[mixData.options.quantity] || basePrices['10000'];
            const packagingPrice = packagingPrices[mixData.options.packaging] || 0;
            
            const totalPrice = quantity * (basePrice + packagingPrice);
            const pricePerKg = basePrice + packagingPrice;
            
            // Update quantity display
            let quantityText;
            if (quantity >= 1000) {
                quantityText = (quantity / 1000).toLocaleString('vi-VN') + ' tấn';
            } else {
                quantityText = quantity.toLocaleString('vi-VN') + ' kg';
            }
            
            // Update price display
            let formattedTotalPrice;
            if (totalPrice >= 1000000000) {
                formattedTotalPrice = Math.round(totalPrice / 1000000000) + ' tỷ';
            } else if (totalPrice >= 1000000) {
                formattedTotalPrice = Math.round(totalPrice / 1000000) + ' triệu';
            } else {
                formattedTotalPrice = formatPrice(totalPrice) + ' đ';
            }
            
            // Update footer values
            $('#footer-total-quantity').text(quantityText);
            $('#footer-estimated-price').text(formattedTotalPrice);
            $('#footer-price-per-kg').text(formatPrice(pricePerKg) + ' đ/kg');
        }
        
        // Format price with thousands separator
        function formatPrice(price) {
            return Math.round(price).toLocaleString('vi-VN');
        }
        
        // Handle add buttons (for future percentage adjustment)
        $('.add-btn').on('click', function(e) {
            e.preventDefault();
            const productNum = $(this).data('product');
            
            // Future: Open modal to adjust percentage
            showPercentageModal(productNum);
        });
        
        // Show percentage adjustment modal (placeholder)
        function showPercentageModal(productNum) {
            // Create a simple modal for percentage adjustment
            const currentProduct = productNum === '1' ? mixData.product1 : mixData.product2;
            const otherProduct = productNum === '1' ? mixData.product2 : mixData.product1;
            
            if (!currentProduct || !otherProduct) {
                alert('Vui lòng chọn đủ 2 sản phẩm trước khi điều chỉnh tỷ lệ.');
                return;
            }
            
            const newPercentage = prompt(
                `Nhập tỷ lệ % cho ${currentProduct.name} (hiện tại: ${currentProduct.percentage}%).\n` +
                `${otherProduct.name} sẽ tự động điều chỉnh thành ${100 - currentProduct.percentage}%`,
                currentProduct.percentage
            );
            
            if (newPercentage !== null && !isNaN(newPercentage)) {
                const percentage = Math.max(10, Math.min(90, parseInt(newPercentage)));
                
                if (productNum === '1') {
                    mixData.product1.percentage = percentage;
                    mixData.product2.percentage = 100 - percentage;
                } else {
                    mixData.product2.percentage = percentage;
                    mixData.product1.percentage = 100 - percentage;
                }
                
                updateProgressBars();
                updateFooterSummary();
            }
        }
        
        // Handle next step button
        $('#next-step-button').on('click', function(e) {
            e.preventDefault();
            
            if (!mixData.product2) {
                showMessage('Vui lòng chọn sản phẩm thứ 2 để tiếp tục.', 'error');
                return;
            }
            
            // Show loading state
            showLoading($(this));
            
            // Calculate totals for order data
            const quantity = mixData.options.quantity === 'khac' ? 10000 : parseInt(mixData.options.quantity);
            const basePrice = basePrices[mixData.options.quantity] || basePrices['10000'];
            const packagingPrice = packagingPrices[mixData.options.packaging] || 0;
            const totalPrice = quantity * (basePrice + packagingPrice);
            
            // Store mix data in session storage for next page
            const orderData = {
                type: 'mix',
                product1: mixData.product1,
                product2: mixData.product2,
                options: mixData.options,
                quantity_kg: quantity,
                base_price_per_kg: basePrice,
                packaging_price_per_kg: packagingPrice,
                total_price: totalPrice
            };
            
            sessionStorage.setItem('vinapet_mix_data', JSON.stringify(orderData));
            
            // Simulate processing
            setTimeout(() => {
                hideLoading($(this), 'Qua bước tiếp theo <span class="arrow-icon">→</span>');
                
                // Redirect to checkout page
                window.location.href = '/checkout';
            }, 1500);
        });
        
        // Handle "View bag details" link click
        $('.view-details-link').on('click', function(e) {
            e.preventDefault();
            showMessage('Tính năng xem minh họa các loại túi sẽ được cập nhật sớm.', 'info');
        });
        
        // Initialize with default values if both products are selected
        if (mixData.product1 && mixData.product2) {
            updateProgressBars();
            updateFooterSummary();
        }
        
        // Handle keyboard navigation for accessibility
        $(document).on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                const focused = $(document.activeElement);
                if (focused.hasClass('color-option') || 
                    focused.hasClass('scent-option') || 
                    focused.hasClass('quantity-option') ||
                    focused.hasClass('packaging-option')) {
                    e.preventDefault();
                    focused.click();
                }
            }
        });
        
        // Make options focusable for accessibility
        $('.color-option, .scent-option, .quantity-option, .packaging-option').attr('tabindex', '0');
        
        // Auto-select main product if passed from URL
        const urlParams = new URLSearchParams(window.location.search);
        const mainProduct = urlParams.get('product');
        const selectedVariant = urlParams.get('variant');
        
        if (mainProduct) {
            mixData.product1.code = mainProduct.toUpperCase();
            // Update main product display if needed
        }
        
        // Smooth scrolling when options appear
        function scrollToOptions() {
            if ($('#mix-options').is(':visible')) {
                $('html, body').animate({
                    scrollTop: $('#mix-options').offset().top - 100
                }, 800);
            }
        }
        
        // Trigger scroll when second product is selected
        $('#second-product-select').on('change', function() {
            if ($(this).val()) {
                setTimeout(scrollToOptions, 300);
            }
        });
        
        // Add loading states
        function showLoading(element) {
            element.prop('disabled', true).html(`
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Đang xử lý...
            `);
        }
        
        function hideLoading(element, originalText) {
            element.prop('disabled', false).html(originalText);
        }
        
        // Show message to user
        function showMessage(message, type = 'info') {
            // Remove existing messages
            $('.message-popup').remove();
            
            const messageClass = type === 'success' ? 'success' : type === 'error' ? 'error' : 'info';
            const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
            
            const popup = $(`
                <div class="message-popup ${messageClass}">
                    <span class="message-icon">${icon}</span>
                    <span class="message-text">${message}</span>
                </div>
            `);
            
            $('body').append(popup);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                popup.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Reset mix data function (for testing)
        window.resetMixData = function() {
            mixData.product2 = null;
            hideSecondProductContent();
            hideMixOptions();
            hideFooterSummary();
            $('.secondary-product').removeClass('has-selection');
            $('#second-product-select').val('').prop('disabled', false);
            $('.product-dropdown-container').removeClass('locked');
        };
        
        // Debug function (for development)
        window.getMixData = function() {
            console.log('Current Mix Data:', mixData);
            return mixData;
        };
        
        // Add CSS for message popup and animations if not exists
        if (!$('#mix-loading-styles').length) {
            $('head').append(`
                <style id="mix-loading-styles">
                    .animate-spin {
                        animation: spin 1s linear infinite;
                    }
                    
                    @keyframes spin {
                        from {
                            transform: rotate(0deg);
                        }
                        to {
                            transform: rotate(360deg);
                        }
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
                    
                    .message-popup.success {
                        background: #10B981;
                        color: white;
                    }
                    
                    .message-popup.error {
                        background: #EF4444;
                        color: white;
                    }
                    
                    .message-popup.info {
                        background: #3B82F6;
                        color: white;
                    }
                    
                    .message-icon {
                        font-weight: bold;
                        font-size: 16px;
                    }
                    
                    @keyframes slideInRight {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                </style>
            `);
        }
        
        // Initialize progress bars as hidden on page load
        $('.progress-section').removeClass('show');
        
        console.log('Mix Products page initialized successfully');
    });
})(jQuery);