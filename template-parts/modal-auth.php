<?php

/**
 * Modal Authentication Template
 * VinaPet Theme - ERPNext Integration
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Authentication Modal -->
<div class="modal-overlay" id="authModalOverlay">
    <div class="modal-container" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
        <button class="modal-close" id="modalClose" aria-label="Đóng modal">
            <svg id="modalClose" width="30" height="30" viewBox="0 0 24 24" fill="none">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
        </button>

        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Đăng nhập</h2>
            </div>

            <div class="modal-body">
                <!-- Notification Area -->
                <div class="notification-container" id="notificationContainer"></div>

                <!-- Login Form -->
                <form class="auth-form" id="loginForm">
                    <?php wp_nonce_field('vinapet_login_action', 'vinapet_login_nonce'); ?>

                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Email</label>
                        <input
                            type="email"
                            class="form-input"
                            id="loginEmail"
                            name="user_email"
                            placeholder="Nhập email của bạn"
                            required
                            autocomplete="email"
                            aria-describedby="loginEmailError">
                        <div class="error-message" id="loginEmailError" role="alert">
                            ⚠ Vui lòng nhập email hợp lệ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginPassword">Mật khẩu</label>
                        <input
                            type="password"
                            class="form-input"
                            id="loginPassword"
                            name="user_password"
                            placeholder="Nhập mật khẩu"
                            required
                            autocomplete="current-password"
                            aria-describedby="loginPasswordError">
                        <div class="error-message" id="loginPasswordError" role="alert">
                            ⚠ Mật khẩu không được để trống
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="checkbox-group">
                            <input type="checkbox" class="checkbox-input" id="rememberMe" name="remember">
                            <label class="checkbox-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                        </div>
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password" target="_blank">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="btn-primary" id="loginSubmit">
                        <span class="btn-text">Đăng nhập</span>
                        <div class="btn-loading" style="display: none;">
                            <div class="spinner"></div>
                            <span>Đang xử lý...</span>
                        </div>
                    </button>

                    <div class="auth-switch">
                        <span>Bạn chưa có tài khoản?</span>
                        <a href="#" class="switch-link" id="switchToRegister">Đăng ký ngay!</a>
                    </div>

                    <?php
                    // Kiểm tra Nextend Social Login
                    $has_nextend = class_exists('NextendSocialLogin') || shortcode_exists('nextend_social_login');
                    if ($has_nextend):
                    ?>
                        <!-- Social Login Divider -->
                        <!-- <div class="social-login-divider">
                            <div class="divider"><span>hoặc</span></div>
                        </div> -->

                        <!-- Google Login Button -->
                        <div class="social-login-container">
                            <button type="button" class="btn-google" id="googleLoginBtn">
                                <svg class="google-icon" viewBox="0 0 24 24" width="20" height="20">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                                <span>Đăng nhập bằng Google</span>
                            </button>
                        </div>
                    <?php endif; ?>


                </form>

                <!-- Register Form -->
                <form class="auth-form" id="registerForm" style="display: none;">
                    <?php wp_nonce_field('vinapet_register_action', 'vinapet_register_nonce'); ?>
                    <!-- Notice -->
                    <div class="register-notice">
                        <svg fill="#0172CB" width="18px" height="18px" viewBox="0 0 36 36" version="1.1" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <title>info-standard-solid</title>
                                <path class="clr-i-solid clr-i-solid-path-1" d="M18,2.1a16,16,0,1,0,16,16A16,16,0,0,0,18,2.1Zm-.1,5.28a2,2,0,1,1-2,2A2,2,0,0,1,17.9,7.38Zm3.6,21.25h-7a1.4,1.4,0,1,1,0-2.8h2.1v-9.2H15a1.4,1.4,0,1,1,0-2.8h4.4v12h2.1a1.4,1.4,0,1,1,0,2.8Z"></path>
                                <rect x="0" y="0" width="36" height="36" fill-opacity="0"></rect>
                            </g>
                        </svg>
                        Vui lòng điền các thông tin bên dưới để hoàn tất việc đăng ký
                    </div>
                    <!-- inputs -->
                    <div class="form-group">
                        <label class="form-label" for="registerName">Họ và tên</label>
                        <input
                            type="text"
                            class="form-input"
                            id="registerName"
                            name="user_name"
                            placeholder="Nhập họ và tên"
                            required
                            autocomplete="name"
                            aria-describedby="registerNameError">
                        <div class="error-message" id="registerNameError" role="alert">
                            ⚠ Vui lòng nhập họ và tên
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerAddress">Địa chỉ</label>
                        <textarea
                            class="form-input"
                            id="registerAddress"
                            name="user_address"
                            placeholder="Nhập địa chỉ đầy đủ"
                            required
                            autocomplete="street-address"
                            aria-describedby="registerAddressError"
                            rows="3"
                            style="resize: vertical; min-height: 80px;"></textarea>
                        <div class="error-message" id="registerAddressError" role="alert">
                            ⚠ Vui lòng nhập địa chỉ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerEmail">Email</label>
                        <input
                            type="email"
                            class="form-input"
                            id="registerEmail"
                            name="user_email"
                            placeholder="Nhập email"
                            required
                            autocomplete="email"
                            aria-describedby="registerEmailError">
                        <div class="error-message" id="registerEmailError" role="alert">
                            ⚠ Email không hợp lệ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerPhone">Số điện thoại</label>
                        <input
                            type="tel"
                            class="form-input"
                            id="registerPhone"
                            name="user_phone"
                            placeholder="Nhập số điện thoại"
                            required
                            autocomplete="tel"
                            aria-describedby="registerPhoneError">
                        <div class="error-message" id="registerPhoneError" role="alert">
                            ⚠ Số điện thoại không hợp lệ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerPassword">Mật khẩu</label>
                        <input
                            type="password"
                            class="form-input"
                            id="registerPassword"
                            name="user_password"
                            placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                            required
                            autocomplete="new-password"
                            minlength="6"
                            aria-describedby="registerPasswordError">
                        <div class="error-message" id="registerPasswordError" role="alert">
                            ⚠ Mật khẩu phải có ít nhất 6 ký tự
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerPasswordConfirm">Xác nhận mật khẩu</label>
                        <input
                            type="password"
                            class="form-input"
                            id="registerPasswordConfirm"
                            name="user_password_confirm"
                            placeholder="Nhập lại mật khẩu"
                            required
                            autocomplete="new-password"
                            aria-describedby="registerPasswordConfirmError">
                        <div class="error-message" id="registerPasswordConfirmError" role="alert">
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
                        <div class="btn-loading" style="display: none;">
                            <div class="spinner"></div>
                            <span>Đang xử lý...</span>
                        </div>
                    </button>

                    <div class="auth-switch">
                        <span>Đã có tài khoản?</span>
                        <a href="#" class="switch-link" id="switchToLogin">Đăng nhập ngay!</a>
                    </div>
                </form>

                <!-- Google Register Form -->
                <form class="auth-form" id="googleRegisterForm" style="display: none;">
                    <?php wp_nonce_field('vinapet_register_action', 'vinapet_google_register_nonce'); ?>

                    <div class="register-notice">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0172CB" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span>Hoàn tất thông tin để tạo tài khoản với Google</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="googleRegisterName">Họ và tên</label>
                        <input
                            type="text"
                            class="form-input"
                            id="googleRegisterName"
                            name="user_name"
                            placeholder="Nhập họ và tên"
                            required
                            autocomplete="name"
                            aria-describedby="googleRegisterNameError">
                        <div class="error-message" id="googleRegisterNameError" role="alert">
                            ⚠ Vui lòng nhập họ và tên
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="googleRegisterAddress">Địa chỉ</label>
                        <textarea
                            class="form-input"
                            id="googleRegisterAddress"
                            name="user_address"
                            placeholder="Nhập địa chỉ đầy đủ"
                            required
                            autocomplete="street-address"
                            aria-describedby="googleRegisterAddressError"
                            rows="3"
                            style="resize: vertical; min-height: 80px;"></textarea>
                        <div class="error-message" id="googleRegisterAddressError" role="alert">
                            ⚠ Vui lòng nhập địa chỉ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="googleRegisterEmail">Email</label>
                        <input
                            type="email"
                            class="form-input"
                            id="googleRegisterEmail"
                            name="user_email"
                            placeholder="Email từ Google"
                            required
                            disabled
                            style="background-color: #f5f5f5; cursor: not-allowed;"
                            aria-describedby="googleRegisterEmailError">
                        <div class="error-message" id="googleRegisterEmailError" role="alert">
                            ⚠ Email không hợp lệ
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="googleRegisterPhone">Số điện thoại</label>
                        <input
                            type="tel"
                            class="form-input"
                            id="googleRegisterPhone"
                            name="user_phone"
                            placeholder="Nhập số điện thoại"
                            required
                            autocomplete="tel"
                            aria-describedby="googleRegisterPhoneError">
                        <div class="error-message" id="googleRegisterPhoneError" role="alert">
                            ⚠ Số điện thoại không hợp lệ
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" class="checkbox-input" id="googleAgreeTerms" name="agree_terms" required>
                            <label class="checkbox-label" for="googleAgreeTerms">
                                Tôi đồng ý với
                                <a href="<?php echo home_url('/dieu-khoan-su-dung'); ?>" target="_blank" class="terms-link">Điều khoản sử dụng</a>
                                và
                                <a href="<?php echo home_url('/chinh-sach-bao-mat'); ?>" target="_blank" class="terms-link">Chính sách bảo mật</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="googleRegisterSubmit">
                        <span class="btn-text">Hoàn tất đăng ký</span>
                        <div class="btn-loading" style="display: none;">
                            <div class="spinner"></div>
                            <span>Đang xử lý...</span>
                        </div>
                    </button>

                    <div class="auth-switch">
                        <span>Đã có tài khoản?</span>
                        <a href="#" class="switch-link" id="switchToLogin">Đăng nhập ngay!</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Loading animation for buttons */
    .btn-loading {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .spinner {
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>