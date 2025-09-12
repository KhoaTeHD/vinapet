<?php
/**
 * Header User Actions - Clean version
 */

$is_logged_in = is_user_logged_in();
?>

<div class="header-user-actions">
    <!-- Language Switcher - hiển thị cho cả logged in và chưa logged in -->
    <div class="language-switcher">
        <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
                <rect width="48" height="48" fill="white" fill-opacity="0.01"></rect>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M24 44C35.0457 44 44 35.0457 44 24C44 12.9543 35.0457 4 24 4C12.9543 4 4 12.9543 4 24C4 35.0457 12.9543 44 24 44Z" stroke="#19457B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M6 18H42" stroke="#19457B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                <path d="M6 30H42" stroke="#19457B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M24 44C28.4183 44 32 35.0457 32 24C32 12.9543 28.4183 4 24 4C19.5817 4 16 12.9543 16 24C16 35.0457 19.5817 44 24 44Z" stroke="#19457B" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></path>
            </g>
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