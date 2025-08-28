<?php
/**
 * Header User Actions - Clean version
 */

$is_logged_in = is_user_logged_in();
?>

<div class="header-user-actions">
    <!-- Language Switcher - hiển thị cho cả logged in và chưa logged in -->
    <div class="language-switcher">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
        </svg>
        <span class="lang-text-full">Tiếng Việt</span>
        <span class="lang-text-short">VI</span>
    </div>
    <?php if ($is_logged_in): ?>
        <!-- Đã đăng nhập: Hiển thị tài khoản + giỏ hàng -->

        <!-- Nút giỏ hàng - chỉ hiển thị icon trên mobile -->
        <a href="<?php echo home_url('/gio-hang'); ?>" class="user-btn cart-btn">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
            <span class="btn-text">Giỏ hàng</span>
        </a>
        
        <!-- Nút tài khoản -->
        <a href="<?php echo home_url('/tai-khoan'); ?>" class="user-btn account-btn">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </a>
        
    <?php else: ?>
        <!-- Chưa đăng nhập: Giữ nguyên nút đăng nhập hiện có -->
        <a href="#" class="login-btn" data-auth-modal="open">
            <span class="btn-text">Đăng nhập</span>
        </a>
    <?php endif; ?>
</div>