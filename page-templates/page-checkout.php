<?php

/**
 * Template Name: Checkout Page
 * Description: Trang thanh toán và hoàn tất đơn hàng
 */

// Load order session và get checkout data
require_once get_template_directory() . '/includes/helpers/class-order-session.php';
$session = VinaPet_Order_Session::get_instance();
$checkout_data = $session->get_checkout_data();

// Redirect nếu không có data
if (!$checkout_data) {
    wp_redirect(home_url('/san-pham'));
    exit;
}

get_header();

// Breadcrumb data
global $breadcrumb_data;
$breadcrumb_data = [
    ['name' => 'Trang chủ', 'url' => home_url()],
    ['name' => 'Sản phẩm', 'url' => home_url('/san-pham')],
    ['name' => 'Đặt hàng', 'url' => '']
];

error_log('Checkout Data from checkout page: ' . print_r($checkout_data, true));
?>

<div class="container">
    <!-- Breadcrumb -->
    <?php get_template_part('template-parts/breadcrumbs', 'bar'); ?>

    <div class="checkout-container">
        <!-- Left Column - Order Summary (40%) -->
        <div class="checkout-left-column">
            <div class="order-summary-card">
                <!-- Back Button and Title -->
                <div class="summary-header">
                    <button onclick="history.back()" class="back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7" />
                        </svg>
                        Quay về Bước 1
                    </button>
                    <h2 class="summary-title <?php echo $checkout_data['type']; ?>-title">
                        <?php echo esc_html($checkout_data['title']); ?>
                    </h2>
                </div>

                <!-- Order Items List -->
                <div class="order-items-list" id="order-items-list">
                     <?php if ($checkout_data['type'] === 'mix'): ?>
                        <!-- Mix Products Display -->
                        <?php foreach ($checkout_data['products'] as $key => $product): ?>
                            <div class="order-item mix-item">
                                <div class="item-header">
                                    <div class="item-name"><?php echo esc_html($product['name']); ?></div>
                                    <div class="item-quantity percentage">
                                        <?php echo number_format($product['percentage'], 0); ?>%
                                    </div>
                                </div>
                                <?php if (!empty($checkout_data['details']) && $key === array_key_last($checkout_data['products'])): ?>
                                    <div class="item-details">
                                        <?php foreach ($checkout_data['details'] as $key => $detail): ?>
                                            <?php if ($key !== 'quantity'): ?>
                                                <div class="item-detail"><?php echo esc_html(vinapet_get_mix_option_name($key, $detail)); ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Normal Product Display -->
                        <div class="order-item normal-item">
                            <div class="item-header">
                                <div class="item-name"><?php echo esc_html($checkout_data['product_name']); ?></div>
                                <div class="item-quantity">
                                    x<?php echo number_format($checkout_data['quantity']); ?> kg
                                </div>
                            </div>
                            <div class="item-details">
                                <div class="item-detail">SKU: <?php echo esc_html($checkout_data['variant']); ?></div>
                                <div class="item-detail">Túi: <?php echo esc_html(vinapet_get_mix_option_name('packaging',$checkout_data['packaging'])); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Summary Table -->
                <div class="order-summary-table">
                    <div class="summary-row">
                        <span class="summary-label">Tổng số lượng:</span>
                        <span class="summary-value" id="summary-total-quantity">
                            <?php echo number_format($checkout_data['total_quantity']); ?> kg
                        </span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Bao bì:</span>
                        <span class="summary-value highlight-text" id="summary-packaging">Vui lòng chọn</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Thời gian nhận hàng:</span>
                        <span class="summary-value highlight-text" id="summary-delivery">Vui lòng chọn</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Vận chuyển:</span>
                        <span class="summary-value highlight-text" id="summary-shipping">Vui lòng chọn</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Báo giá dự kiến:</span>
                        <div class="total-price-section">
                            <span class="total-price" id="summary-total-price">
                                <?php echo number_format($checkout_data['estimated_price']); ?> đ
                            </span>
                            <span class="price-note">
                                (Giá cost: 
                                <span id="summary-price-per-kg">
                                <?php echo number_format($checkout_data['price_per_kg']); ?> đ/kg
                                </span>
                                )
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="checkout-actions">
                    <button type="button" class="btn btn-secondary add-to-cart-btn">
                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#19457B">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path d="M6.29977 5H21L19 12H7.37671M20 16H8L6 3H3M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20ZM20 20C20 20.5523 19.5523 21 19 21C18.4477 21 18 20.5523 18 20C18 19.4477 18.4477 19 19 19C19.5523 19 20 19.4477 20 20Z" stroke="#19457B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </g>
                        </svg>
                        Thêm vào giỏ
                    </button>
                    <button type="submit" class="btn btn-primary submit-request-btn">
                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C11.3953 1.25 10.8384 1.40029 10.2288 1.65242C9.64008 1.89588 8.95633 2.25471 8.1049 2.70153L6.03739 3.78651C4.99242 4.33487 4.15616 4.77371 3.51047 5.20491C2.84154 5.65164 2.32632 6.12201 1.95112 6.75918C1.57718 7.39421 1.40896 8.08184 1.32829 8.90072C1.24999 9.69558 1.24999 10.6731 1.25 11.9026V12.0974C1.24999 13.3268 1.24999 14.3044 1.32829 15.0993C1.40896 15.9182 1.57718 16.6058 1.95112 17.2408C2.32632 17.878 2.84154 18.3484 3.51047 18.7951C4.15616 19.2263 4.99241 19.6651 6.03737 20.2135L8.10481 21.2984C8.95628 21.7453 9.64006 22.1041 10.2288 22.3476C10.8384 22.5997 11.3953 22.75 12 22.75C12.6047 22.75 13.1616 22.5997 13.7712 22.3476C14.3599 22.1041 15.0437 21.7453 15.8951 21.2985L17.9626 20.2135C19.0076 19.6651 19.8438 19.2263 20.4895 18.7951C21.1585 18.3484 21.6737 17.878 22.0489 17.2408C22.4228 16.6058 22.591 15.9182 22.6717 15.0993C22.75 14.3044 22.75 13.3269 22.75 12.0975V11.9025C22.75 10.6731 22.75 9.69557 22.6717 8.90072C22.591 8.08184 22.4228 7.39421 22.0489 6.75918C21.6737 6.12201 21.1585 5.65164 20.4895 5.20491C19.8438 4.77371 19.0076 4.33487 17.9626 3.7865L15.8951 2.70154C15.0437 2.25472 14.3599 1.89589 13.7712 1.65242C13.1616 1.40029 12.6047 1.25 12 1.25ZM8.7708 4.04608C9.66052 3.57917 10.284 3.2528 10.802 3.03856C11.3062 2.83004 11.6605 2.75 12 2.75C12.3395 2.75 12.6938 2.83004 13.198 3.03856C13.716 3.2528 14.3395 3.57917 15.2292 4.04608L17.2292 5.09563C18.3189 5.66748 19.0845 6.07032 19.6565 6.45232C19.9387 6.64078 20.1604 6.81578 20.3395 6.99174L17.0088 8.65708L8.50895 4.18349L8.7708 4.04608ZM6.94466 5.00439L6.7708 5.09563C5.68111 5.66747 4.91553 6.07032 4.34352 6.45232C4.06131 6.64078 3.83956 6.81578 3.66054 6.99174L12 11.1615L15.3572 9.48289L7.15069 5.16369C7.07096 5.12173 7.00191 5.06743 6.94466 5.00439ZM2.93768 8.30737C2.88718 8.52125 2.84901 8.76413 2.82106 9.04778C2.75084 9.7606 2.75 10.6644 2.75 11.9415V12.0585C2.75 13.3356 2.75084 14.2394 2.82106 14.9522C2.88974 15.6494 3.02022 16.1002 3.24367 16.4797C3.46587 16.857 3.78727 17.1762 4.34352 17.5477C4.91553 17.9297 5.68111 18.3325 6.7708 18.9044L8.7708 19.9539C9.66052 20.4208 10.284 20.7472 10.802 20.9614C10.9656 21.0291 11.1134 21.0832 11.25 21.1255V12.4635L2.93768 8.30737ZM12.75 21.1255C12.8866 21.0832 13.0344 21.0291 13.198 20.9614C13.716 20.7472 14.3395 20.4208 15.2292 19.9539L17.2292 18.9044C18.3189 18.3325 19.0845 17.9297 19.6565 17.5477C20.2127 17.1762 20.5341 16.857 20.7563 16.4797C20.9798 16.1002 21.1103 15.6494 21.1789 14.9522C21.2492 14.2394 21.25 13.3356 21.25 12.0585V11.9415C21.25 10.6644 21.2492 9.7606 21.1789 9.04778C21.151 8.76412 21.1128 8.52125 21.0623 8.30736L17.75 9.96352V13C17.75 13.4142 17.4142 13.75 17 13.75C16.5858 13.75 16.25 13.4142 16.25 13V10.7135L12.75 12.4635V21.1255Z" fill="#ffffff"></path>
                            </g>
                        </svg>
                        Gửi yêu cầu
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column - Single Checkout Form Card (60%) -->
        <div class="checkout-right-column">
            <div class="checkout-form-card">
                <form id="checkout-form" class="checkout-form">

                    <!-- Packaging Design Section -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn cách thiết kế bao bì</h3>

                        <div class="packaging-design-options">
                            <label class="design-option">
                                <div class="option-content">
                                    <input type="radio" name="packaging_design" value="company_design">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M15.8787 3.70705C17.0503 2.53547 18.9498 2.53548 20.1213 3.70705L20.2929 3.87862C21.4645 5.05019 21.4645 6.94969 20.2929 8.12126L18.5556 9.85857L8.70713 19.7071C8.57897 19.8352 8.41839 19.9261 8.24256 19.9701L4.24256 20.9701C3.90178 21.0553 3.54129 20.9554 3.29291 20.7071C3.04453 20.4587 2.94468 20.0982 3.02988 19.7574L4.02988 15.7574C4.07384 15.5816 4.16476 15.421 4.29291 15.2928L14.1989 5.38685L15.8787 3.70705ZM18.7071 5.12126C18.3166 4.73074 17.6834 4.73074 17.2929 5.12126L16.3068 6.10738L17.8622 7.72357L18.8787 6.70705C19.2692 6.31653 19.2692 5.68336 18.8787 5.29283L18.7071 5.12126ZM16.4477 9.13804L14.8923 7.52185L5.90299 16.5112L5.37439 18.6256L7.48877 18.097L16.4477 9.13804Z" fill="#000000"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Nhà máy hỗ trợ thiết kế decal/ túi đơn giản</div>
                                            <span class="option-price grey-text">Miễn phí</span>
                                        </div>
                                        <div class="option-desc">Miễn phí, thời gian 7 ngày từ lúc nhận yêu cầu, 3 lần sửa</div>
                                    </div>
                                </div>
                            </label>

                            <label class="design-option">
                                <div class="option-content">
                                    <input type="radio" name="packaging_design" value="custom_file">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M21.1213 2.70705C19.9497 1.53548 18.0503 1.53547 16.8787 2.70705L15.1989 4.38685L7.29289 12.2928C7.16473 12.421 7.07382 12.5816 7.02986 12.7574L6.02986 16.7574C5.94466 17.0982 6.04451 17.4587 6.29289 17.707C6.54127 17.9554 6.90176 18.0553 7.24254 17.9701L11.2425 16.9701C11.4184 16.9261 11.5789 16.8352 11.7071 16.707L19.5556 8.85857L21.2929 7.12126C22.4645 5.94969 22.4645 4.05019 21.2929 2.87862L21.1213 2.70705ZM18.2929 4.12126C18.6834 3.73074 19.3166 3.73074 19.7071 4.12126L19.8787 4.29283C20.2692 4.68336 20.2692 5.31653 19.8787 5.70705L18.8622 6.72357L17.3068 5.10738L18.2929 4.12126ZM15.8923 6.52185L17.4477 8.13804L10.4888 15.097L8.37437 15.6256L8.90296 13.5112L15.8923 6.52185ZM4 7.99994C4 7.44766 4.44772 6.99994 5 6.99994H10C10.5523 6.99994 11 6.55223 11 5.99994C11 5.44766 10.5523 4.99994 10 4.99994H5C3.34315 4.99994 2 6.34309 2 7.99994V18.9999C2 20.6568 3.34315 21.9999 5 21.9999H16C17.6569 21.9999 19 20.6568 19 18.9999V13.9999C19 13.4477 18.5523 12.9999 18 12.9999C17.4477 12.9999 17 13.4477 17 13.9999V18.9999C17 19.5522 16.5523 19.9999 16 19.9999H5C4.44772 19.9999 4 19.5522 4 18.9999V7.99994Z" fill="#000000"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Theo file thiết kế của khách hàng</div>
                                            <span class="option-price golden-text">Báo giá sau</span>
                                        </div>
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
                                rows="4"></textarea>
                            <div class="note-warning">
                                <svg width="18px" height="18px" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="#B96904">
                                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                    <g id="SVGRepo_iconCarrier">
                                        <title>information-circle-solid</title>
                                        <g id="Layer_2" data-name="Layer 2">
                                            <g id="invisible_box" data-name="invisible box">
                                                <rect width="48" height="48" fill="none"></rect>
                                            </g>
                                            <g id="icons_Q2" data-name="icons Q2">
                                                <path d="M24,2A22,22,0,1,0,46,24,21.9,21.9,0,0,0,24,2Zm2,32a2,2,0,0,1-4,0V22a2,2,0,0,1,4,0ZM24,16a2,2,0,1,1,2-2A2,2,0,0,1,24,16Z"></path>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                                Chúng tôi sẽ báo giá thêm dựa trên yêu cầu đặc biệt của khách hàng
                            </div>
                        </div>
                    </div>

                    <div class="separator"></div>

                    <!-- Delivery Timeline Section -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn thời gian nhận hàng mong muốn</h3>

                        <div class="timeline-options">
                            <label class="timeline-option">
                                <div class="option-content">
                                    <input type="radio" name="delivery_timeline" value="urgent">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" id="Line_Color" data-name="Line Color" xmlns="http://www.w3.org/2000/svg" fill="#000000">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path id="primary" d="M12,21c-3.9,0-7-2-7-7s5-5,5-11c3,2,4.37,4.1,5,8a5,5,0,0,0,2-3c1,1,2,4,2,6C19,17.14,17.72,21,12,21Z" style="fill:none;stroke:#000000;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Gấp (dưới 15 ngày)</div>
                                            <span class="option-price">+ 100,000 đ</span>
                                        </div>
                                        <div class="option-desc">Đặt với đơn hàng sản xuất túi PA/PE đều tiện sở mát từ 20-25 ngày</div>
                                    </div>
                                </div>
                            </label>

                            <label class="timeline-option">
                                <div class="option-content">
                                    <input type="radio" name="delivery_timeline" value="normal">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M4 12C4 7.58172 7.58172 4 12 4C12.5523 4 13 3.55228 13 3C13 2.44772 12.5523 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C14.7611 22 17.2625 20.8796 19.0711 19.0711C19.4616 18.6805 19.4616 18.0474 19.0711 17.6569C18.6805 17.2663 18.0474 17.2663 17.6569 17.6569C16.208 19.1057 14.2094 20 12 20C7.58172 20 4 16.4183 4 12ZM13 6C13 5.44772 12.5523 5 12 5C11.4477 5 11 5.44772 11 6V12C11 12.2652 11.1054 12.5196 11.2929 12.7071L14.2929 15.7071C14.6834 16.0976 15.3166 16.0976 15.7071 15.7071C16.0976 15.3166 16.0976 14.6834 15.7071 14.2929L13 11.5858V6ZM21.7483 15.1674C21.535 15.824 20.8298 16.1833 20.1732 15.97C19.5167 15.7566 19.1574 15.0514 19.3707 14.3949C19.584 13.7383 20.2892 13.379 20.9458 13.5923C21.6023 13.8057 21.9617 14.5108 21.7483 15.1674ZM21.0847 11.8267C21.7666 11.7187 22.2318 11.0784 22.1238 10.3966C22.0158 9.71471 21.3755 9.2495 20.6937 9.3575C20.0118 9.46549 19.5466 10.1058 19.6546 10.7877C19.7626 11.4695 20.4029 11.9347 21.0847 11.8267ZM20.2924 5.97522C20.6982 6.53373 20.5744 7.31544 20.0159 7.72122C19.4574 8.127 18.6757 8.00319 18.2699 7.44468C17.8641 6.88617 17.9879 6.10446 18.5464 5.69868C19.1049 5.2929 19.8867 5.41671 20.2924 5.97522ZM17.1997 4.54844C17.5131 3.93333 17.2685 3.18061 16.6534 2.86719C16.0383 2.55378 15.2856 2.79835 14.9722 3.41346C14.6588 4.02858 14.9033 4.78129 15.5185 5.09471C16.1336 5.40812 16.8863 5.16355 17.1997 4.54844Z" fill="#000000"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Trung bình (15 - 30 ngày)</div>
                                            <span class="option-price">+ 20,000 đ</span>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="timeline-option">
                                <div class="option-content">
                                    <input type="radio" name="delivery_timeline" value="flexible">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7 1C6.44772 1 6 1.44772 6 2V3H5C3.34315 3 2 4.34315 2 6V20C2 21.6569 3.34315 23 5 23H13.101C12.5151 22.4259 12.0297 21.7496 11.6736 21H5C4.44772 21 4 20.5523 4 20V11H20V11.2899C20.7224 11.5049 21.396 11.8334 22 12.2547V6C22 4.34315 20.6569 3 19 3H18V2C18 1.44772 17.5523 1 17 1C16.4477 1 16 1.44772 16 2V3H8V2C8 1.44772 7.55228 1 7 1ZM16 6V5H8V6C8 6.55228 7.55228 7 7 7C6.44772 7 6 6.55228 6 6V5H5C4.44772 5 4 5.44772 4 6V9H20V6C20 5.44772 19.5523 5 19 5H18V6C18 6.55228 17.5523 7 17 7C16.4477 7 16 6.55228 16 6Z" fill="#0F0F0F"></path>
                                                <path d="M17 16C17 15.4477 17.4477 15 18 15C18.5523 15 19 15.4477 19 16V17.703L19.8801 18.583C20.2706 18.9736 20.2706 19.6067 19.8801 19.9973C19.4896 20.3878 18.8564 20.3878 18.4659 19.9973L17.2929 18.8243C17.0828 18.6142 16.9857 18.3338 17.0017 18.0588C17.0006 18.0393 17 18.0197 17 18V16Z" fill="#0F0F0F"></path>
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M24 18C24 21.3137 21.3137 24 18 24C14.6863 24 12 21.3137 12 18C12 14.6863 14.6863 12 18 12C21.3137 12 24 14.6863 24 18ZM13.9819 18C13.9819 20.2191 15.7809 22.0181 18 22.0181C20.2191 22.0181 22.0181 20.2191 22.0181 18C22.0181 15.7809 20.2191 13.9819 18 13.9819C15.7809 13.9819 13.9819 15.7809 13.9819 18Z" fill="#0F0F0F"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Linh hoạt (trên 30 ngày)</div>
                                            <span class="option-price grey-text">Miễn phí</span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="separator"></div>

                    <!-- Shipping Method Section -->
                    <div class="form-section">
                        <h3 class="section-title">Chọn cách vận chuyển</h3>

                        <div class="shipping-options">
                            <label class="shipping-option">
                                <div class="option-content">
                                    <input type="radio" name="shipping_method" value="factory_support">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M8.52832 16.826C8.53464 17.7132 8.01843 18.5166 7.22106 18.8607C6.42369 19.2047 5.50274 19.0213 4.88882 18.3962C4.27491 17.7712 4.08935 16.828 4.41891 16.0077C4.74847 15.1873 5.52803 14.652 6.39307 14.652C6.95731 14.6499 7.49925 14.8777 7.89969 15.2854C8.30013 15.6931 8.52625 16.2473 8.52832 16.826V16.826Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M18.7015 16.826C18.7078 17.7132 18.1916 18.5166 17.3942 18.8607C16.5969 19.2047 15.6759 19.0213 15.062 18.3962C14.4481 17.7712 14.2625 16.828 14.5921 16.0077C14.9216 15.1873 15.7012 14.652 16.5662 14.652C17.1305 14.6499 17.6724 14.8777 18.0728 15.2854C18.4733 15.6931 18.6994 16.2473 18.7015 16.826Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                <path d="M14.1804 17.576C14.5946 17.576 14.9304 17.2403 14.9304 16.826C14.9304 16.4118 14.5946 16.076 14.1804 16.076V17.576ZM8.5254 16.076C8.11119 16.076 7.7754 16.4118 7.7754 16.826C7.7754 17.2403 8.11119 17.576 8.5254 17.576V16.076ZM13.4304 16.826C13.4304 17.2403 13.7662 17.576 14.1804 17.576C14.5946 17.576 14.9304 17.2403 14.9304 16.826H13.4304ZM14.9304 11.559C14.9304 11.1448 14.5946 10.809 14.1804 10.809C13.7662 10.809 13.4304 11.1448 13.4304 11.559H14.9304ZM14.1804 16.076C13.7662 16.076 13.4304 16.4118 13.4304 16.826C13.4304 17.2403 13.7662 17.576 14.1804 17.576V16.076ZM14.43 17.576C14.8442 17.576 15.18 17.2403 15.18 16.826C15.18 16.4118 14.8442 16.076 14.43 16.076V17.576ZM18.6972 16.0761C18.283 16.0779 17.9487 16.4151 17.9505 16.8293C17.9523 17.2435 18.2896 17.5779 18.7038 17.576L18.6972 16.0761ZM20.9625 14.485L21.7125 14.4816C21.7123 14.4384 21.7084 14.3954 21.7008 14.3529L20.9625 14.485ZM21.1772 11.4269C21.1042 11.0192 20.7146 10.7478 20.3068 10.8208C19.8991 10.8937 19.6277 11.2834 19.7007 11.6912L21.1772 11.4269ZM14.1794 6.12705C13.7652 6.12705 13.4294 6.46283 13.4294 6.87705C13.4294 7.29126 13.7652 7.62705 14.1794 7.62705V6.12705ZM17.7587 6.87705V7.62705C17.7637 7.62705 17.7688 7.627 17.7739 7.62689L17.7587 6.87705ZM19.3783 7.55055L19.9178 7.02951L19.9178 7.02951L19.3783 7.55055ZM20.0197 9.21805L19.27 9.19669C19.2685 9.24804 19.2723 9.2994 19.2814 9.34996L20.0197 9.21805ZM19.6996 11.691C19.7725 12.0987 20.1621 12.3702 20.5699 12.2974C20.9776 12.2245 21.2491 11.8349 21.1763 11.4271L19.6996 11.691ZM14.9284 6.87705C14.9284 6.46283 14.5927 6.12705 14.1784 6.12705C13.7642 6.12705 13.4284 6.46283 13.4284 6.87705H14.9284ZM13.4284 11.559C13.4284 11.9733 13.7642 12.309 14.1784 12.309C14.5927 12.309 14.9284 11.9733 14.9284 11.559H13.4284ZM13.4284 6.87705C13.4284 7.29126 13.7642 7.62705 14.1784 7.62705C14.5927 7.62705 14.9284 7.29126 14.9284 6.87705H13.4284ZM14.1784 6.07705L14.9285 6.07705L14.9284 6.07167L14.1784 6.07705ZM13.1137 5.00005L13.1137 5.75006L13.1187 5.75003L13.1137 5.00005ZM3.50512 5.00005L3.498 5.75005H3.50512V5.00005ZM2.75423 5.31075L2.22207 4.78225L2.22207 4.78225L2.75423 5.31075ZM2.4375 6.07505L1.6875 6.06834V6.07505H2.4375ZM2.4375 15.75L1.68747 15.75L1.68753 15.7568L2.4375 15.75ZM2.75423 16.5143L3.28638 15.9858L3.28638 15.9858L2.75423 16.5143ZM3.50512 16.825L3.50512 16.075L3.498 16.0751L3.50512 16.825ZM4.25783 17.575C4.67204 17.575 5.00783 17.2393 5.00783 16.825C5.00783 16.4108 4.67204 16.075 4.25783 16.075V17.575ZM14.1804 10.809C13.7662 10.809 13.4304 11.1448 13.4304 11.559C13.4304 11.9733 13.7662 12.309 14.1804 12.309V10.809ZM20.4399 12.309C20.8541 12.309 21.1899 11.9733 21.1899 11.559C21.1899 11.1448 20.8541 10.809 20.4399 10.809V12.309ZM14.1804 16.076H8.5254V17.576H14.1804V16.076ZM14.9304 16.826V11.559H13.4304V16.826H14.9304ZM14.1804 17.576H14.43V16.076H14.1804V17.576ZM18.7038 17.576C19.5117 17.5725 20.281 17.2397 20.8437 16.6573L19.765 15.615C19.4792 15.9108 19.0947 16.0743 18.6972 16.0761L18.7038 17.576ZM20.8437 16.6573C21.4058 16.0756 21.7162 15.2926 21.7125 14.4816L20.2125 14.4885C20.2145 14.9137 20.0514 15.3186 19.765 15.615L20.8437 16.6573ZM21.7008 14.3529L21.1772 11.4269L19.7007 11.6912L20.2242 14.6172L21.7008 14.3529ZM14.1794 7.62705H17.7587V6.12705H14.1794V7.62705ZM17.7739 7.62689C18.1691 7.61888 18.5544 7.7771 18.8389 8.07158L19.9178 7.02951C19.3477 6.43922 18.5622 6.1106 17.7434 6.1272L17.7739 7.62689ZM18.8389 8.07158C19.124 8.36679 19.282 8.77315 19.27 9.19669L20.7694 9.2394C20.7928 8.41808 20.4872 7.61907 19.9178 7.02951L18.8389 8.07158ZM19.2814 9.34996L19.6996 11.691L21.1763 11.4271L20.758 9.08613L19.2814 9.34996ZM13.4284 6.87705V11.559H14.9284V6.87705H13.4284ZM14.9284 6.87705V6.07705H13.4284V6.87705H14.9284ZM14.9284 6.07167C14.9213 5.07677 14.1245 4.24331 13.1088 4.25006L13.1187 5.75003C13.2708 5.74902 13.427 5.87963 13.4285 6.08242L14.9284 6.07167ZM13.1137 4.25005H3.50512V5.75005H13.1137V4.25005ZM3.51225 4.25008C3.02654 4.24547 2.5628 4.43917 2.22207 4.78225L3.28638 5.83925C3.3461 5.77912 3.42257 5.7493 3.498 5.75001L3.51225 4.25008ZM2.22207 4.78225C1.88199 5.12468 1.69183 5.58769 1.68753 6.06834L3.18747 6.08175C3.18832 5.98687 3.22602 5.90003 3.28638 5.83925L2.22207 4.78225ZM1.6875 6.07505V15.75H3.1875V6.07505H1.6875ZM1.68753 15.7568C1.69183 16.2374 1.88199 16.7004 2.22207 17.0428L3.28638 15.9858C3.22602 15.9251 3.18832 15.8382 3.18747 15.7433L1.68753 15.7568ZM2.22207 17.0428C2.5628 17.3859 3.02654 17.5796 3.51225 17.575L3.498 16.0751C3.42257 16.0758 3.3461 16.046 3.28638 15.9858L2.22207 17.0428ZM3.50512 17.575H4.25783V16.075H3.50512V17.575ZM14.1804 12.309H20.4399V10.809H14.1804V12.309Z" fill="#000000"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Nhà máy hỗ trợ vận chuyển</div>
                                            <span class="option-price">+ 300,000 đ/tấn</span>
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="shipping-option">
                                <div class="option-content">
                                    <input type="radio" name="shipping_method" value="self_pickup">
                                    <div class="option-icon">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M4 21V18.5C4 15.4624 6.46243 13 9.5 13H13.5M8 21V18M16 6.5C16 8.70914 14.2091 10.5 12 10.5C9.79086 10.5 8 8.70914 8 6.5C8 4.29086 9.79086 2.5 12 2.5C14.2091 2.5 16 4.29086 16 6.5ZM22 15.5C22 17.9853 17.5 22 17.5 22C17.5 22 13 17.9853 13 15.5C13 13.0147 15.0147 11 17.5 11C19.9853 11 22 13.0147 22 15.5ZM19 15.5C19 16.3284 18.3284 17 17.5 17C16.6716 17 16 16.3284 16 15.5C16 14.6716 16.6716 14 17.5 14C18.3284 14 19 14.6716 19 15.5Z" stroke="#000000" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4"></path>
                                            </g>
                                        </svg>
                                    </div>
                                    <div class="option-text">
                                        <div class="option-header">
                                            <div class="option-title">Tự vận chuyển</div>
                                            <span class="option-price grey-text">Miễn phí</span>
                                        </div>
                                        <div class="option-desc">Khách hàng đến nhà xưởng để lấy hàng khi hoàn tất</div>
                                    </div>

                                </div>
                        </div>
                        </label>
                    </div>

                    <div class="separator"></div>

                    <!-- Additional Support Section -->
                    <div class="form-section">
                        <h3 class="section-title">Yêu cầu hỗ trợ khác</h3>

                        <textarea
                            id="additional-support"
                            name="additional_support"
                            placeholder="Nhập yêu cầu tư vấn kỹ thuật, hỗ trợ Marketing..."
                            rows="6"></textarea>
                    </div>

            </div>



            </form>
        </div>
    </div>
</div>
</div>

<!-- Hidden data for JavaScript -->
<script type="text/javascript">
    // Pass PHP data to JavaScript for interactions
    window.vinapet_checkout_data = <?php echo json_encode($checkout_data); ?>;
    console.log(window.vinapet_checkout_data);
</script>

<?php get_footer(); ?>