(function($) {
    $(document).ready(function() {
        let orderData = {};
        let mixData = {};
        let checkoutData = {};
        let isFromMix = false;
        
        // Load order data from previous page
        loadOrderData();
        
        // Initialize form handlers
        initializeFormHandlers();
        
        // Populate order summary
        populateOrderSummary();
        
        /**
         * Load order data from sessionStorage - Xử lý cả mix và order thông thường
         */
        function loadOrderData() {
            // Kiểm tra xem có dữ liệu mix không
            const storedMixData = sessionStorage.getItem('vinapet_mix_data');
            const storedOrderData = sessionStorage.getItem('vinapet_order_data');
            
            if (storedMixData) {
                // Đây là mix order
                mixData = JSON.parse(storedMixData);
                isFromMix = true;
                console.log('Loaded mix data:', mixData);
            } else if (storedOrderData) {
                // Đây là order thông thường
                orderData = JSON.parse(storedOrderData);
                isFromMix = false;
                console.log('Loaded order data:', orderData);
            } else {
                // Fallback data for testing
                orderData = {
                    variant: 'com',
                    quantity: '4000',
                    packaging: 'bao_dua',
                    quantity_kg: 4000,
                    base_price_per_kg: 42000,
                    packaging_price_per_kg: 160,
                    total_base_price: 168000000,
                    total_packaging_fee: 640000,
                    total_price: 171800000,
                    product_code: 'CAT-TRE-001'
                };
                isFromMix = false;
                console.log('Using fallback order data');
            }
        }
        
        /**
         * Populate order summary section - Xử lý cả mix và order thông thường
         */
        function populateOrderSummary() {
            // Cập nhật title dựa trên loại đơn hàng
            const titleText = isFromMix ? 'Đơn hàng tùy chỉnh' : 'Đơn hàng';
            $('.summary-title').text(titleText);
            
            // Generate order items HTML
            const orderItems = isFromMix ? generateMixOrderItemsHTML() : generateOrderItemsHTML();
            $('#order-items-list').html(orderItems);
            
            // Update totals
            updateOrderTotals();
        }
        
        /**
         * Generate HTML for mix order items - Hiển thị theo tỷ lệ %
         */
        function generateMixOrderItemsHTML() {
            const variantNames = {
                'com': 'Cát tre: Mùi cốm - Màu xanh non',
                'sua': 'Cát tre: Mùi sữa - Màu tự nhiên', 
                'cafe': 'Cát tre: Mùi cà phê - Màu nâu',
                'sen': 'Cát tre: Mùi sen - Màu hồng',
                'vanilla': 'Cát tre: Mùi vanilla - Màu vàng'
            };
            
            const colorNames = {
                'xanh_non': 'Màu xanh non',
                'hong_nhat': 'Màu hồng nhạt',
                'vang_dat': 'Màu vàng đất',
                'do_gach': 'Màu đỏ gạch',
                'be_nhat': 'Màu be nhạt',
                'den': 'Màu đen'
            };
            
            const scentNames = {
                'com': 'Mùi cốm',
                'tro_xanh': 'Mùi trà xanh',
                'ca_phe': 'Mùi cà phê',
                'sen': 'Mùi sen',
                'sua': 'Mùi sữa',
                'chanh': 'Mùi chanh'
            };
            
            const packagingNames = {
                'tui_jumbo_500': 'Túi Jumbo 500 kg',
                'tui_jumbo_1000': 'Túi Jumbo 1 tấn'
            };
            
            let itemsHTML = '';
            
            // Hiển thị từng sản phẩm trong mix
            const activeProducts = ['products.product1', 'products.product2', 'products.product3'];
            
            activeProducts.forEach(productPath => {
                const product = getNestedProperty(mixData, productPath);
                if (product && product.name) {
                    itemsHTML += `
                        <div class="order-item">
                            <div class="item-header">
                                <div class="item-name">${product.name}</div>
                                <div class="item-quantity">${product.percentage}%</div>
                            </div>
                            <div class="item-details">
                                <div class="item-detail">• ${colorNames[mixData.options.color] || 'Màu xanh non'}</div>
                                <div class="item-detail">• ${scentNames[mixData.options.scent] || 'Mùi trà xanh'}</div>
                                <div class="item-detail">• ${packagingNames[mixData.options.packaging] || 'Túi Jumbo 1 tấn'}</div>
                            </div>
                        </div>
                    `;
                }
            });
            
            return itemsHTML;
        }
        
        /**
         * Generate HTML for regular order items - Hiển thị theo số lượng
         */
        function generateOrderItemsHTML() {
            const variantNames = {
                'com': 'Cát tre: Mùi cốm - Màu xanh non',
                'sua': 'Cát tre: Mùi sữa - Màu tự nhiên', 
                'cafe': 'Cát tre: Mùi cà phê - Màu nâu',
                'sen': 'Cát tre: Mùi sen - Màu hồng',
                'vanilla': 'Cát tre: Mùi vanilla - Màu vàng'
            };
            
            const packagingNames = {
                'pa_pe_thuong': 'Túi 8 Biên PA / PE Hút Chân Không',
                'pa_pe_khong': 'Túi 8 Biên PA / PE Hút Chân Không',
                'pa_pe_decal': 'Túi PA / PE Trong + Decal',
                'bao_dua': 'Bao Tải Dừa + Lót 1 lớp PE',
                'tui_jumbo': 'Túi Jumbo'
            };
            
            // Generate items HTML matching the design (2 items with specific quantities)
            let itemsHTML = '';
            
            // First item - 1000kg
            itemsHTML += `
                <div class="order-item">
                    <div class="item-header">
                        <div class="item-name">${variantNames['com']}</div>
                        <div class="item-quantity">x1000 kg</div>
                    </div>
                    <div class="item-details">
                        <div class="item-detail">• Túi 8 Biên PA / PE Hút Chân Không</div>
                    </div>
                </div>
            `;
            
            // Second item - 3000kg
            itemsHTML += `
                <div class="order-item">
                    <div class="item-header">
                        <div class="item-name">${variantNames['sen']}</div>
                        <div class="item-quantity">x3000 kg</div>
                    </div>
                    <div class="item-details">
                        <div class="item-detail">• Bao Tải Dừa + Lót 1 lớp PE</div>
                    </div>
                </div>
            `;
            
            return itemsHTML;
        }
        
        /**
         * Helper function to get nested property safely
         */
        function getNestedProperty(obj, path) {
            return path.split('.').reduce((current, key) => current && current[key], obj);
        }
        
        /**
         * Update order totals display - Xử lý cả mix và order thông thường
         */
        function updateOrderTotals() {
            if (isFromMix) {
                // Hiển thị thông tin cho mix order
                const totalQuantity = mixData.quantity_kg || 10000;
                let quantityText = totalQuantity.toLocaleString('vi-VN') + ' kg';

                $('#summary-total-quantity').text(quantityText);
                
                // Update total price
                const totalPrice = mixData.total_price || 340000000;
                let formattedTotalPrice;
                if (totalPrice >= 1000000000) {
                    formattedTotalPrice = Math.round(totalPrice / 1000000000) + ' tỷ';
                } else if (totalPrice >= 1000000) {
                    formattedTotalPrice = Math.round(totalPrice / 1000000) + ' triệu';
                } else {
                    formattedTotalPrice = formatPrice(totalPrice) + ' đ';
                }
                
                const pricePerKg = (mixData.base_price_per_kg || 34000) + (mixData.packaging_price_per_kg || 0);
                
                $('#summary-total-price').text(formattedTotalPrice);
                $('#summary-price-per-kg').text(formatPrice(pricePerKg) + ' đ/kg');
                
            } else {
                // Hiển thị thông tin cho order thông thường
                $('#summary-total-quantity').text('4000 kg');
                $('#summary-total-price').text('171,800,000 đ');
                $('#summary-price-per-kg').text('42,950 đ/kg');
            }
            
            // Update packaging info
            const packagingText = checkoutData.packaging_design ? 
                getPackagingDisplayText(checkoutData.packaging_design) : 
                'Vui lòng chọn';
            $('#summary-packaging').text(packagingText);
            $('#summary-packaging').toggleClass('highlight-text', !checkoutData.packaging_design);
            
            // Update delivery time
            const deliveryText = checkoutData.delivery_timeline ? 
                getDeliveryDisplayText(checkoutData.delivery_timeline) : 
                'Vui lòng chọn';
            $('#summary-delivery').text(deliveryText);
            $('#summary-delivery').toggleClass('highlight-text', !checkoutData.delivery_timeline);
            
            // Update shipping
            const shippingText = checkoutData.shipping_method ? 
                getShippingDisplayText(checkoutData.shipping_method) : 
                'Vui lòng chọn';
            $('#summary-shipping').text(shippingText);
            $('#summary-shipping').toggleClass('highlight-text', !checkoutData.shipping_method);
        }
        
        /**
         * Get display text for packaging design
         */
        function getPackagingDisplayText(value) {
            const texts = {
                'company_design': 'Thiết kế của nhà máy',
                'custom_file': 'File thiết kế riêng'
            };
            return texts[value] || value;
        }
        
        /**
         * Get display text for delivery timeline
         */
        function getDeliveryDisplayText(value) {
            const texts = {
                'urgent': 'Gấp (< 15 ngày)',
                'normal': 'Trung bình (15-30 ngày)',
                'flexible': 'Linh hoạt (> 30 ngày)'
            };
            return texts[value] || value;
        }
        
        /**
         * Get display text for shipping method
         */
        function getShippingDisplayText(value) {
            const texts = {
                'road_transport': 'Đường bộ',
                'sea_transport': 'Đường biển', 
                'air_transport': 'Đường hàng không'
            };
            return texts[value] || value;
        }
        
        /**
         * Initialize form event handlers
         */
        function initializeFormHandlers() {
            // Packaging design change
            $('input[name="packaging_design"]').on('change', function() {
                checkoutData.packaging_design = $(this).val();
                updateOrderTotals();
            });
            
            // Delivery timeline change
            $('input[name="delivery_timeline"]').on('change', function() {
                checkoutData.delivery_timeline = $(this).val();
                updateOrderTotals();
            });
            
            // Shipping method change
            $('input[name="shipping_method"]').on('change', function() {
                checkoutData.shipping_method = $(this).val();
                updateOrderTotals();
            });
            
            // Design request text change
            $('#design-request').on('input', function() {
                checkoutData.design_request = $(this).val();
            });
            
            // Additional support text change
            $('#additional-support').on('input', function() {
                checkoutData.additional_support = $(this).val();
            });
            
            // Add to cart button
            $('.add-to-cart-btn').on('click', function(e) {
                e.preventDefault();
                handleAddToCart();
            });
            
            // Submit request button
            $('.submit-request-btn').on('click', function(e) {
                e.preventDefault();
                handleSubmitRequest();
            });
        }
        
        /**
         * Handle add to cart action - Xử lý cả mix và order thông thường
         */
        function handleAddToCart() {
            if (!validateForm()) {
                return;
            }
            
            // Combine data based on order type
            const cartData = isFromMix ? 
                { ...mixData, ...checkoutData, action_type: 'add_to_cart' } :
                { ...orderData, ...checkoutData, action_type: 'add_to_cart' };
            
            // Store in sessionStorage for cart processing
            sessionStorage.setItem('vinapet_cart_item', JSON.stringify(cartData));
            
            // Show success message
            showMessage('Đã thêm vào giỏ hàng thành công!', 'success');
        }
        
        /**
         * Handle submit request action - Xử lý cả mix và order thông thường
         */
        function handleSubmitRequest() {
            if (!validateForm()) {
                return;
            }
            
            // Combine data based on order type
            const requestData = isFromMix ?
                { ...mixData, ...checkoutData, action_type: 'submit_request' } :
                { ...orderData, ...checkoutData, action_type: 'submit_request' };
            
            // Here you would typically send to server
            console.log('Submitting request:', requestData);
            
            // Show loading state
            $('.submit-request-btn').prop('disabled', true).html(`
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Đang gửi yêu cầu...
            `);
            
            // Simulate API call
            setTimeout(() => {
                $('.submit-request-btn').prop('disabled', false).html(`
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92V19.92C22 20.4728 21.5523 20.92 21 20.92H18C8.059 20.92 0 12.861 0 2.92V2.92C0 1.36772 1.34772 0.02 3 0.02H6L8 4.02L6.5 5.52C7.5 7.52 9.48 9.5 11.48 10.5L13 9.02L17 11.02V14.02C17 15.5728 15.5523 17.02 14 17.02H13C12.4477 17.02 12 16.5728 12 16.02V14.52C10.34 13.85 8.15 12.17 7.48 10.51H6C5.44772 10.51 5 10.0628 5 9.51V8.02C5 7.46772 5.44772 7.02 6 7.02H7.5C8.88 4.64 11.12 3.02 14 3.02C16.21 3.02 18 4.81 18 7.02V8.52C18 9.07228 17.5523 9.52 17 9.52H15.5C14.83 11.18 13.15 12.86 11.49 13.53V15.02C11.49 15.5728 11.9377 16.02 12.49 16.02H14C15.5523 16.02 17 14.5728 17 13.02V11.02L22 16.92Z"/>
                    </svg>
                    Gửi yêu cầu
                `);
                
                const successMessage = isFromMix ? 
                    'Yêu cầu đơn hàng tùy chỉnh đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.' :
                    'Yêu cầu đã được gửi thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.';
                
                showMessage(successMessage, 'success');
                
                // Clear form data
                if (isFromMix) {
                    sessionStorage.removeItem('vinapet_mix_data');
                } else {
                    sessionStorage.removeItem('vinapet_order_data');
                }
            }, 2000);
        }
        
        /**
         * Validate form before submission
         */
        function validateForm() {
            const requiredFields = [
                'packaging_design',
                'delivery_timeline', 
                'shipping_method'
            ];
            
            for (let field of requiredFields) {
                if (!checkoutData[field]) {
                    showMessage('Vui lòng điền đầy đủ thông tin bắt buộc.', 'error');
                    return false;
                }
            }
            
            return true;
        }
        
        /**
         * Show message to user
         */
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
        
        /**
         * Format price with thousands separator
         */
        function formatPrice(price) {
            return Math.round(price).toLocaleString('vi-VN');
        }
        
        // Add CSS for message popup and animations
        if (!$('#message-popup-styles').length) {
            $('head').append(`
                <style id="message-popup-styles">
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
                </style>
            `);
        }
    });
})(jQuery);