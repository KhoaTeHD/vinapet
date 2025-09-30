<?php

/**
 * Template Name: Tài khoản
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

// Kiểm tra xem user đã đăng nhập chưa
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

// Lấy thông tin user hiện tại
$current_user = wp_get_current_user();
?>

<div class="account-page">
    <div class="container">
        <div class="account-wrapper">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?php echo home_url(); ?>">Trang chủ</a>
                <span class="separator">></span>
                <span class="current">Tài khoản</span>
            </div>

            <!-- Account Header -->
            <div class="account-header">
                <h1 class="account-title">Tài khoản</h1>
            </div>

            <div class="account-content">
                <!-- Sidebar -->
                <div class="account-sidebar">
                    <div class="sidebar-menu">
                        <div class="menu-item active" data-tab="profile">
                            <span>Hồ sơ cá nhân</span>
                        </div>
                        <div class="menu-item" data-tab="orders">
                            <span>Đơn hàng</span>
                        </div>
                        <div class="menu-item logout" id="logout-btn">
                            <span>Đăng xuất</span>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="account-main">
                    <!-- Profile Tab -->
                    <div class="tab-content active" id="profile-tab">
                        <div class="profile-tabs">
                            <div class="tab-nav">
                                <button class="tab-button active" data-target="info-tab">Thông tin của tôi</button>
                                <button class="tab-button" data-target="password-tab">Thay đổi mật khẩu</button>
                            </div>

                            <!-- Thông tin của tôi -->
                            <div class="tab-pane active" id="info-tab">
                                <form class="profile-form" id="profile-info-form">
                                    <?php wp_nonce_field('update_profile_info', 'profile_info_nonce'); ?>

                                    <div class="form-group">
                                        <label for="display_name">Họ và tên</label>
                                        <input type="text"
                                            id="display_name"
                                            name="display_name"
                                            value="<?php echo esc_attr($current_user->display_name); ?>"
                                            placeholder="<?php echo esc_attr($current_user->display_name ?: 'Lê Nguyễn'); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="user_phone">Số điện thoại</label>
                                        <input type="tel"
                                            id="user_phone"
                                            name="user_phone"
                                            value="<?php echo esc_attr(get_user_meta($current_user->ID, 'phone', true)); ?>"
                                            placeholder="+84.793617115">
                                    </div>

                                    <div class="form-group">
                                        <label for="user_email">Email</label>
                                        <input type="email"
                                            id="user_email"
                                            name="user_email"
                                            value="<?php echo esc_attr($current_user->user_email); ?>"
                                            placeholder="lenguyen@gmail.com">
                                    </div>

                                    <!-- THÊM FIELD ADDRESS -->
                                    <div class="form-group">
                                        <label for="user_address">Địa chỉ</label>
                                        <textarea
                                            id="user_address"
                                            name="user_address"
                                            rows="3"
                                            placeholder="Nhập địa chỉ đầy đủ"><?php echo esc_textarea(get_user_meta($current_user->ID, 'user_address', true)); ?></textarea>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn-save">
                                            <span class="btn-text">Lưu thay đổi</span>
                                            <span class="btn-icon">💾</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Thay đổi mật khẩu -->
                            <div class="tab-pane" id="password-tab">
                                <form class="profile-form" id="change-password-form">
                                    <?php wp_nonce_field('change_password', 'change_password_nonce'); ?>

                                    <div class="form-group">
                                        <label for="current_password">Mật khẩu hiện tại</label>
                                        <input type="password"
                                            id="current_password"
                                            name="current_password"
                                            placeholder="Nhập mật khẩu hiện tại">
                                    </div>

                                    <div class="form-group">
                                        <label for="new_password">Mật khẩu mới</label>
                                        <input type="password"
                                            id="new_password"
                                            name="new_password"
                                            placeholder="Nhập mật khẩu mới">
                                    </div>

                                    <div class="form-group">
                                        <label for="confirm_password">Xác nhận mật khẩu mới</label>
                                        <input type="password"
                                            id="confirm_password"
                                            name="confirm_password"
                                            placeholder="Nhập mật khẩu mới">
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn-change-password">
                                            <span class="btn-text">Đổi mật khẩu</span>
                                            <span class="btn-icon">🔐</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-content" id="orders-tab">
                        <div class="orders-section">
                            <!-- Orders Tab Navigation -->
                            <div class="orders-tabs">
                                <div class="orders-tab-nav">
                                    <button class="orders-tab-button active" data-target="sent-request">Đã gửi yêu cầu</button>
                                    <button class="orders-tab-button" data-target="vinapet-quote">Vinapet báo giá</button>
                                    <button class="orders-tab-button" data-target="completed">Hoàn thành</button>
                                    <button class="orders-tab-button" data-target="cancelled">Đã hủy</button>
                                </div>

                                <!-- Other tabs (placeholders) -->
                                <div class="orders-tab-pane active" id="sent-request">
                                    <div class="orders-list" id="sent-request-orders">

                                        <!-- Orders will be loaded here -->
                                    </div>
                                </div>

                                <div class="orders-tab-pane" id="vinapet-quote">
                                    <div class="empty-orders">
                                        <p>Chưa có đơn hàng được báo giá</p>
                                    </div>
                                </div>

                                <div class="orders-tab-pane" id="completed">
                                    <div class="empty-orders">
                                        <p>Chưa có đơn hàng hoàn thành</p>
                                    </div>
                                </div>

                                <div class="orders-tab-pane" id="cancelled">
                                    <div class="empty-orders">
                                        <p>Chưa có đơn hàng bị hủy</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- Success/Error Messages -->
<div class="message-overlay" id="message-overlay">
    <div class="message-content">
        <span class="message-text"></span>
        <button class="message-close">&times;</button>
    </div>
</div>

<?php get_footer(); ?>