<?php
/**
 * File: template-parts/modal-auth.php
 * VinaPet Authentication Modal Template
 * 
 * @package VinaPet
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Auth Modal -->
<div class="modal-overlay" id="authModalOverlay">
    <div class="modal-container">
        <!-- Modal Header -->
        <div class="modal-header">
            <button class="modal-close" id="authModalClose">×</button>
            <h2 class="modal-title" id="modalTitle">Đăng nhập</h2>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <!-- Login Form -->
            <form class="auth-form" id="loginForm">
                <?php wp_nonce_field('vinapet_login_action', 'vinapet_login_nonce'); ?>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input 
                        type="email" 
                        class="form-input" 
                        name="user_email" 
                        placeholder="Nhập email"
                        required
                        autocomplete="email"
                    >
                    <div class="error-message" id="loginEmailError">
                        ⚠ Email không hợp lệ
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <input 
                        type="password" 
                        class="form-input" 
                        name="user_password" 
                        placeholder="Nhập mật khẩu"
                        required
                        autocomplete="current-password"
                    >
                    <div class="error-message" id="loginPasswordError">
                        ⚠ Mật khẩu không được để trống
                    </div>
                </div>

                <div class="form-row">
                    <div class="checkbox-group">
                        <input type="checkbox" class="checkbox-input" id="rememberMe" name="remember">
                        <label class="checkbox-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                    </div>
                    <a href="#" class="forgot-password" id="forgotPasswordLink">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-primary" id="loginSubmit">
                    <span class="btn-text">Đăng nhập</span>
                </button>

                <?php
                // Kiểm tra xem có plugin Nextend Social Login không
                if (function_exists('NextendSocialLogin') || shortcode_exists('nextend_social_login')):
                ?>
                    <!-- Nextend Social Login Integration -->
                    <div class="social-login-divider">
                        <div class="divider"><span>hoặc</span></div>
                    </div>
                    
                    <div class="social-login-container">
                        <?php 
                        // Sử dụng shortcode của Nextend Social Login
                        echo do_shortcode('[nextend_social_login provider="google" align="center" style="fullwidth"]');
                        ?>
                    </div>
                <?php else: ?>
                    <!-- Fallback Google Button khi chưa có plugin -->
                    <div class="divider"><span>hoặc</span></div>
                    
                    <button type="button" class="btn-google" id="googleLoginBtn">
                        <svg class="google-icon" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Đăng nhập bằng Google
                    </button>
                <?php endif; ?>

                <div class="form-switch">
                    Bạn chưa có tài khoản? <a href="#" class="switch-link" id="switchToRegister">Đăng ký ngay!</a>
                </div>
            </form>

            <!-- Register Form -->
            <form class="auth-form" id="registerForm" style="display: none;">
                <?php wp_nonce_field('vinapet_register_action', 'vinapet_register_nonce'); ?>
                
                <div class="form-group">
                    <label class="form-label">Họ và tên</label>
                    <input 
                        type="text" 
                        class="form-input" 
                        name="user_name" 
                        placeholder="Nhập họ và tên"
                        required
                        autocomplete="name"
                    >
                    <div class="error-message" id="registerNameError">
                        ⚠ Vui lòng nhập họ và tên
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input 
                        type="email" 
                        class="form-input" 
                        name="user_email" 
                        placeholder="Nhập email"
                        required
                        autocomplete="email"
                    >
                    <div class="error-message" id="registerEmailError">
                        ⚠ Email không hợp lệ
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <input 
                        type="tel" 
                        class="form-input" 
                        name="user_phone" 
                        placeholder="Nhập số điện thoại"
                        required
                        autocomplete="tel"
                    >
                    <div class="error-message" id="registerPhoneError">
                        ⚠ Số điện thoại không hợp lệ
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <input 
                        type="password" 
                        class="form-input" 
                        name="user_password" 
                        placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                        required
                        autocomplete="new-password"
                    >
                    <div class="error-message" id="registerPasswordError">
                        ⚠ Mật khẩu phải có ít nhất 6 ký tự
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input 
                        type="password" 
                        class="form-input" 
                        name="user_password_confirm" 
                        placeholder="Nhập lại mật khẩu"
                        required
                        autocomplete="new-password"
                    >
                    <div class="error-message" id="registerPasswordConfirmError">
                        ⚠ Mật khẩu xác nhận không khớp
                    </div>
                </div>

                <div class="checkbox-group" style="align-items: flex-start; margin-bottom: 20px;">
                    <input type="checkbox" class="checkbox-input" id="agreeTerms" name="agree_terms" required>
                    <label class="checkbox-label" for="agreeTerms">
                        Tôi đồng ý với 
                        <a href="<?php echo home_url('/dieu-khoan-su-dung'); ?>" class="terms-link" target="_blank">Điều khoản sử dụng</a> 
                        và 
                        <a href="<?php echo home_url('/chinh-sach-bao-mat'); ?>" class="terms-link" target="_blank">Chính sách bảo mật</a>
                    </label>
                </div>

                <button type="submit" class="btn-primary" id="registerSubmit">
                    <span class="btn-text">Đăng ký</span>
                </button>

                <?php if (function_exists('NextendSocialLogin') || shortcode_exists('nextend_social_login')): ?>
                    <!-- Nextend Social Login for Register -->
                    <div class="social-login-divider">
                        <div class="divider"><span>hoặc</span></div>
                    </div>
                    
                    <div class="social-login-container">
                        <?php echo do_shortcode('[nextend_social_login provider="google" align="center" style="fullwidth"]'); ?>
                    </div>
                <?php else: ?>
                    <!-- Fallback Google Button -->
                    <div class="divider"><span>hoặc</span></div>
                    
                    <button type="button" class="btn-google" id="googleRegisterBtn">
                        <svg class="google-icon" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Đăng ký bằng Google
                    </button>
                <?php endif; ?>

                <div class="form-switch">
                    Đã có tài khoản? <a href="#" class="switch-link" id="switchToLogin">Đăng nhập</a>
                </div>
            </form>
        </div>
    </div>
</div>