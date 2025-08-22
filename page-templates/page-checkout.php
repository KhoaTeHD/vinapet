<?php
/**
 * Template Name: Checkout Page
 * Description: Trang thanh toán và hoàn tất đơn hàng
 */

get_header();

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
    ['name' => 'Đặt hàng', 'url' => '']
];
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>
    
    <!-- Back Button -->
    <div class="back-navigation">
        <button onclick="history.back()" class="back-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Quay về Bước 1
        </button>
        <h1 class="page-title">Đặt hàng</h1>
    </div>
    
    <div class="checkout-container">
        <!-- Left Column - Order Summary (40%) -->
        <div class="checkout-left-column">
            <div class="order-summary-card">
                <!-- Dynamic Order Items -->
                <div id="order-items">
                    <!-- Sẽ được populate bằng JavaScript -->
                </div>
                
                <!-- Order Totals -->
                <div class="order-totals">
                    <div class="total-row">
                        <span>Tổng số lượng:</span>
                        <span id="checkout-total-quantity">4000 kg</span>
                    </div>
                    <div class="total-row">
                        <span>Bao bì:</span>
                        <span id="checkout-packaging-info">Vui lòng chọn</span>
                    </div>
                    <div class="total-row">
                        <span>Thời gian nhận hàng:</span>
                        <span id="checkout-delivery-time">Vui lòng chọn</span>
                    </div>
                    <div class="total-row">
                        <span>Vận chuyển:</span>
                        <span id="checkout-shipping">Vui lòng chọn</span>
                    </div>
                    <div class="total-row">
                        <span>Báo giá dự kiến:</span>
                        <span class="price-total" id="checkout-estimated-price">171,800,000 đ</span>
                    </div>
                    <div class="total-row price-note">
                        <small>(Giá cost: 42,950 đ/kg)</small>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="checkout-actions">
                    <button type="button" class="btn btn-secondary add-to-cart-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"/>
                            <circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        Thêm vào giỏ
                    </button>
                    <button type="submit" class="btn btn-primary submit-request-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92V19.92C22 20.4728 21.5523 20.92 21 20.92H18C8.059 20.92 0 12.861 0 2.92V2.92C0 1.36772 1.34772 0.02 3 0.02H6L8 4.02L6.5 5.52C7.5 7.52 9.48 9.5 11.48 10.5L13 9.02L17 11.02V14.02C17 15.5728 15.5523 17.02 14 17.02H13C12.4477 17.02 12 16.5728 12 16.02V14.52C10.34 13.85 8.15 12.17 7.48 10.51H6C5.44772 10.51 5 10.0628 5 9.51V8.02C5 7.46772 5.44772 7.02 6 7.02H7.5C8.88 4.64 11.12 3.02 14 3.02C16.21 3.02 18 4.81 18 7.02V8.52C18 9.07228 17.5523 9.52 17 9.52H15.5C14.83 11.18 13.15 12.86 11.49 13.53V15.02C11.49 15.5728 11.9377 16.02 12.49 16.02H14C15.5523 16.02 17 14.5728 17 13.02V11.02L22 16.92Z"/>
                        </svg>
                        Gửi yêu cầu
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Checkout Form (60%) -->
        <div class="checkout-right-column">
            <form id="checkout-form" class="checkout-form">
                
                <!-- Packaging Design Section -->
                <div class="form-section">
                    <h3 class="section-title">Chọn cách thiết kế bao bì</h3>
                    
                    <div class="packaging-design-options">
                        <label class="design-option">
                            <input type="radio" name="packaging_design" value="company_design">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Nhà máy hỗ trợ thiết kế decal/ túi đơn giản</div>
                                    <div class="option-desc">Miễn phí, thời gian 7 ngày từ lúc nhận yêu cầu, 3 lần sửa</div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="design-option">
                            <input type="radio" name="packaging_design" value="custom_file">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <path d="M12 11v6M9 14l3-3 3 3"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Theo file thiết kế của khách hàng</div>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Design Request Text Area -->
                    <div class="design-request-section">
                        <label for="design-request">Thêm yêu cầu đặc biệt về bao bì</label>
                        <textarea 
                            id="design-request" 
                            name="design_request" 
                            placeholder="Yêu cầu chóng ẩm, tay cầm, khóa kéo..."
                            rows="4"
                        ></textarea>
                        <div class="note-warning">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            Chúng tôi sẽ báo giá thêm dựa trên yêu cầu đặc biệt của khách hàng
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Timeline Section -->
                <div class="form-section">
                    <h3 class="section-title">Chọn thời gian nhận hàng mong muốn</h3>
                    
                    <div class="timeline-options">
                        <label class="timeline-option">
                            <input type="radio" name="delivery_timeline" value="urgent">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Gấp (dưới 15 ngày)</div>
                                    <div class="option-desc">Đặt với đơn hàng sản xuất túi PA/PE đều tiện sở mát từ 20-25 ngày</div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="timeline-option">
                            <input type="radio" name="delivery_timeline" value="normal">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Trung bình (15 - 30 ngày)</div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="timeline-option">
                            <input type="radio" name="delivery_timeline" value="flexible">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12,6 12,12 16,14"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Linh hoạt (trên 30 ngày)</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Shipping Method Section -->
                <div class="form-section">
                    <h3 class="section-title">Chọn cách vận chuyển</h3>
                    
                    <div class="shipping-options">
                        <label class="shipping-option">
                            <input type="radio" name="shipping_method" value="road_transport">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="3" width="15" height="13"/>
                                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                        <circle cx="5.5" cy="18.5" r="2.5"/>
                                        <circle cx="18.5" cy="18.5" r="2.5"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Đường bộ (ô tô tải/container/tàu)</div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="shipping-option">
                            <input type="radio" name="shipping_method" value="sea_transport">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 20a2.4 2.4 0 0 0 2 1 2.4 2.4 0 0 0 2-1 2.4 2.4 0 0 1 4 0 2.4 2.4 0 0 0 4 0 2.4 2.4 0 0 1 4 0 2.4 2.4 0 0 0 2 1 2.4 2.4 0 0 0 2-1"/>
                                        <path d="M8 19V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v12"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Đường biển</div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="shipping-option">
                            <input type="radio" name="shipping_method" value="air_transport">
                            <div class="option-content">
                                <div class="option-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>
                                    </svg>
                                </div>
                                <div class="option-text">
                                    <div class="option-title">Đường hàng không</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Additional Support Section -->
                <div class="form-section">
                    <h3 class="section-title">Yêu cầu hỗ trợ khác</h3>
                    
                    <textarea 
                        id="additional-support" 
                        name="additional_support" 
                        placeholder="Nhập yêu cầu tư vấn kỹ thuật, hỗ trợ Marketing..."
                        rows="6"
                    ></textarea>
                </div>
                
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>