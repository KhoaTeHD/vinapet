<?php
/**
 * Header User Actions - Login/Cart/Account buttons
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
?>

<div class="header-user-actions">
    <?php if ($is_logged_in): ?>
        <!-- Cart Button (Hidden on mobile per JSON config) -->
        <a href="<?php echo home_url('/gio-hang'); ?>" class="user-action-btn cart-btn">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V17C17 17.5 17.4 18 18 18S19 17.5 19 17V13M9 19.5C9.8 19.5 10.5 20.2 10.5 21S9.8 22.5 9 22.5 7.5 21.8 7.5 21 8.2 19.5 9 19.5ZM20 19.5C20.8 19.5 21.5 20.2 21.5 21S20.8 22.5 20 22.5 18.5 21.8 18.5 21 19.2 19.5 20 19.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Giỏ hàng
        </a>

        <!-- User Account Button -->
        <a href="<?php echo home_url('/tai-khoan'); ?>" class="user-action-btn account-btn">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 21V19C20 17.9 19.1 16 17 16H7C4.9 16 4 17.9 4 19V21M16 7C16 9.2 14.2 11 12 11S8 9.2 8 7 9.8 3 12 3 16 4.8 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    <?php else: ?>
        <!-- Nút đăng nhập gốc - giữ nguyên code hiện có -->
        <a href="#" class="login-btn" onclick="VinaPetAuth.open()">
            Đăng nhập
        </a>
    <?php endif; ?>
</div>