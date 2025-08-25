(function($) {
    'use strict';

    // Global variables
    let currentForm = 'login';
    let isProcessing = false;

    $(document).ready(function() {
        initializeAuthModal();
    });

    // =============================================================================
    // MODAL INITIALIZATION
    // =============================================================================

    function initializeAuthModal() {
        bindModalEvents();
        bindFormEvents();
        bindValidationEvents();
        console.log('VinaPet Auth Modal initialized');
    }

    // =============================================================================
    // EVENT BINDING
    // =============================================================================

    function bindModalEvents() {
        // Open modal when login button clicked
        $('.login-btn, .mobile-login-btn').on('click', function(e) {
            e.preventDefault();
            openAuthModal();
        });

        // Close modal events
        $('#authModalClose').on('click', closeAuthModal);
        $(document).on('keydown', handleKeyDown);
        $('#authModalOverlay').on('click', handleOverlayClick);

        // Form switching
        $('#switchToRegister').on('click', function(e) {
            e.preventDefault();
            switchToRegister();
        });

        $('#switchToLogin').on('click', function(e) {
            e.preventDefault();
            switchToLogin();
        });

        // Forgot password
        $('#forgotPasswordLink').on('click', function(e) {
            e.preventDefault();
            handleForgotPassword();
        });

        // Google login buttons
        $('#googleLoginBtn').on('click', function(e) {
            e.preventDefault();
            handleGoogleAuth('login');
        });

        $('#googleRegisterBtn').on('click', function(e) {
            e.preventDefault();
            handleGoogleAuth('register');
        });
    }

    function bindFormEvents() {
        // Login form submission
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            if (!isProcessing) {
                handleLogin(this);
            }
        });

        // Register form submission
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            if (!isProcessing) {
                handleRegister(this);
            }
        });
    }

    function bindValidationEvents() {
        // Real-time validation
        $('.form-input').on('input', function() {
            clearFieldError($(this));
        });

        $('.form-input').on('blur', function() {
            validateField($(this));
        });
    }

    // =============================================================================
    // MODAL MANAGEMENT
    // =============================================================================

    function openAuthModal() {
        $('#authModalOverlay').addClass('active');
        $('body').addClass('modal-open').css('overflow', 'hidden');
        
        // Focus first input after animation
        setTimeout(() => {
            const firstInput = $('#loginForm .form-input:first');
            if (firstInput.length) {
                firstInput.focus();
            }
        }, 300);
    }

    function closeAuthModal() {
        $('#authModalOverlay').removeClass('active');
        $('body').removeClass('modal-open').css('overflow', '');
        
        // Reset modal after animation
        setTimeout(() => {
            resetModal();
        }, 300);
    }

    function resetModal() {
        // Reset forms
        $('#loginForm, #registerForm')[0].reset();
        clearAllErrors();
        
        // Switch back to login
        if (currentForm !== 'login') {
            switchToLogin();
        }
        
        isProcessing = false;
    }

    // =============================================================================
    // FORM SWITCHING
    // =============================================================================

    function switchToRegister() {
        currentForm = 'register';
        $('#loginForm').hide();
        $('#registerForm').show().addClass('fade-in');
        $('#modalTitle').text('Đăng ký');
        clearAllErrors();
        
        setTimeout(() => {
            const firstInput = $('#registerForm .form-input:first');
            if (firstInput.length) {
                firstInput.focus();
            }
        }, 100);
    }

    function switchToLogin() {
        currentForm = 'login';
        $('#registerForm').hide();
        $('#loginForm').show().addClass('fade-in');
        $('#modalTitle').text('Đăng nhập');
        clearAllErrors();
        
        setTimeout(() => {
            const firstInput = $('#loginForm .form-input:first');
            if (firstInput.length) {
                firstInput.focus();
            }
        }, 100);
    }

    // =============================================================================
    // FORM VALIDATION
    // =============================================================================

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function validatePhone(phone) {
        const phoneRegex = /^[0-9]{10,11}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    function validatePassword(password) {
        return password.length >= 6;
    }

    function validateField(input) {
        const value = input.val().trim();
        const name = input.attr('name');
        let errorMessage = '';

        switch (name) {
            case 'user_email':
                if (value && !validateEmail(value)) {
                    errorMessage = 'Email không hợp lệ';
                }
                break;
            case 'user_password':
                if (value && !validatePassword(value)) {
                    errorMessage = 'Mật khẩu phải có ít nhất 6 ký tự';
                }
                break;
            case 'user_phone':
                if (value && !validatePhone(value)) {
                    errorMessage = 'Số điện thoại không hợp lệ';
                }
                break;
            case 'user_password_confirm':
                const originalPassword = $('#registerForm input[name="user_password"]').val();
                if (value && value !== originalPassword) {
                    errorMessage = 'Mật khẩu xác nhận không khớp';
                }
                break;
        }

        if (errorMessage) {
            showFieldError(input, errorMessage);
        } else {
            clearFieldError(input);
        }
    }

    function showFieldError(input, message) {
        const group = input.closest('.form-group');
        const errorElement = group.find('.error-message');
        
        group.addClass('error');
        errorElement.text('⚠ ' + message).addClass('show');
    }

    function clearFieldError(input) {
        const group = input.closest('.form-group');
        const errorElement = group.find('.error-message');
        
        group.removeClass('error');
        errorElement.removeClass('show');
    }

    function clearAllErrors() {
        $('.form-group').removeClass('error');
        $('.error-message').removeClass('show');
    }

    function validateLoginForm(form) {
        let isValid = true;
        const email = form.find('input[name="user_email"]').val().trim();
        const password = form.find('input[name="user_password"]').val();

        if (!email || !validateEmail(email)) {
            showFieldError(form.find('input[name="user_email"]'), 'Email không hợp lệ');
            isValid = false;
        }

        if (!password) {
            showFieldError(form.find('input[name="user_password"]'), 'Vui lòng nhập mật khẩu');
            isValid = false;
        }

        return isValid;
    }

    function validateRegisterForm(form) {
        let isValid = true;
        const name = form.find('input[name="user_name"]').val().trim();
        const email = form.find('input[name="user_email"]').val().trim();
        const phone = form.find('input[name="user_phone"]').val().trim();
        const password = form.find('input[name="user_password"]').val();
        const passwordConfirm = form.find('input[name="user_password_confirm"]').val();
        const agreeTerms = form.find('#agreeTerms').is(':checked');

        // Validate name
        if (!name || name.length < 2) {
            showFieldError(form.find('input[name="user_name"]'), 'Vui lòng nhập họ và tên hợp lệ');
            isValid = false;
        }

        // Validate email
        if (!email || !validateEmail(email)) {
            showFieldError(form.find('input[name="user_email"]'), 'Email không hợp lệ');
            isValid = false;
        }

        // Validate phone
        if (!phone || !validatePhone(phone)) {
            showFieldError(form.find('input[name="user_phone"]'), 'Số điện thoại không hợp lệ');
            isValid = false;
        }

        // Validate password
        if (!password || !validatePassword(password)) {
            showFieldError(form.find('input[name="user_password"]'), 'Mật khẩu phải có ít nhất 6 ký tự');
            isValid = false;
        }

        // Validate password confirmation
        if (password !== passwordConfirm) {
            showFieldError(form.find('input[name="user_password_confirm"]'), 'Mật khẩu xác nhận không khớp');
            isValid = false;
        }

        // Validate terms agreement
        if (!agreeTerms) {
            showNotification('Vui lòng đồng ý với điều khoản sử dụng', 'warning');
            isValid = false;
        }

        return isValid;
    }

    // =============================================================================
    // FORM SUBMISSION HANDLERS
    // =============================================================================

    function handleLogin(form) {
        const $form = $(form);
        
        if (!validateLoginForm($form)) {
            return;
        }

        isProcessing = true;
        const $submitBtn = $('#loginSubmit');
        showButtonLoading($submitBtn, 'Đang đăng nhập...');

        // Prepare form data
        const formData = {
            action: 'vinapet_ajax_login',
            nonce: $form.find('input[name="vinapet_login_nonce"]').val(),
            user_email: $form.find('input[name="user_email"]').val().trim(),
            user_password: $form.find('input[name="user_password"]').val(),
            remember: $form.find('input[name="remember"]').is(':checked')
        };

        // AJAX call to WordPress
        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification('Đăng nhập thành công!', 'success');
                    
                    setTimeout(() => {
                        closeAuthModal();
                        
                        // Redirect or reload based on response
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Đăng nhập thất bại', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', error);
                showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
            },
            complete: function() {
                hideButtonLoading($submitBtn, 'Đăng nhập');
                isProcessing = false;
            }
        });
    }

    function handleRegister(form) {
        const $form = $(form);
        
        if (!validateRegisterForm($form)) {
            return;
        }

        isProcessing = true;
        const $submitBtn = $('#registerSubmit');
        showButtonLoading($submitBtn, 'Đang đăng ký...');

        // Prepare form data
        const formData = {
            action: 'vinapet_ajax_register',
            nonce: $form.find('input[name="vinapet_register_nonce"]').val(),
            user_name: $form.find('input[name="user_name"]').val().trim(),
            user_email: $form.find('input[name="user_email"]').val().trim(),
            user_phone: $form.find('input[name="user_phone"]').val().trim(),
            user_password: $form.find('input[name="user_password"]').val(),
            agree_terms: $form.find('#agreeTerms').is(':checked')
        };

        // AJAX call to WordPress
        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification('Đăng ký thành công!', 'success');
                    
                    setTimeout(() => {
                        // Switch to login form and pre-fill email
                        switchToLogin();
                        $('#loginForm input[name="user_email"]').val(formData.user_email);
                        showNotification('Vui lòng đăng nhập với tài khoản mới', 'info');
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Đăng ký thất bại', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Register error:', error);
                showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
            },
            complete: function() {
                hideButtonLoading($submitBtn, 'Đăng ký');
                isProcessing = false;
            }
        });
    }

    function handleForgotPassword() {
        const email = $('#loginForm input[name="user_email"]').val().trim();
        
        if (!email || !validateEmail(email)) {
            showNotification('Vui lòng nhập email hợp lệ trước', 'warning');
            $('#loginForm input[name="user_email"]').focus();
            return;
        }

        // AJAX call for forgot password
        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vinapet_forgot_password',
                nonce: vinapet_auth_data.nonce,
                user_email: email
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Link khôi phục mật khẩu đã được gửi đến ' + email, 'success');
                } else {
                    showNotification(response.data.message || 'Có lỗi xảy ra', 'error');
                }
            },
            error: function() {
                showNotification('Không thể gửi email khôi phục. Vui lòng thử lại.', 'error');
            }
        });
    }

    function handleGoogleAuth(type) {
        // Check if Nextend Social Login is available
        if (typeof NSL !== 'undefined' && NSL.providers && NSL.providers.google) {
            // Use Nextend Social Login
            try {
                NSL.providers.google.onClick();
            } catch (error) {
                console.error('NSL Google error:', error);
                fallbackGoogleAuth(type);
            }
        } else if (typeof nslRedirect !== 'undefined') {
            // Alternative Nextend method
            try {
                nslRedirect(vinapet_auth_data.google_login_url);
            } catch (error) {
                console.error('NSL redirect error:', error);
                fallbackGoogleAuth(type);
            }
        } else {
            fallbackGoogleAuth(type);
        }
    }

    function fallbackGoogleAuth(type) {
        // Fallback for when plugin is not available
        showNotification('Đang chuyển hướng đến Google...', 'info');
        
        // Redirect to WordPress Google login URL
        const googleUrl = vinapet_auth_data.google_login_url || '#';
        if (googleUrl !== '#') {
            window.location.href = googleUrl;
        } else {
            showNotification('Tính năng đăng nhập Google chưa được cấu hình', 'warning');
        }
    }

    // =============================================================================
    // UI HELPERS
    // =============================================================================

    function showButtonLoading(button, loadingText) {
        button.prop('disabled', true).html(`
            <div class="loading-spinner"></div>
            <span>${loadingText}</span>
        `);
    }

    function hideButtonLoading(button, originalText) {
        button.prop('disabled', false).html(`
            <span class="btn-text">${originalText}</span>
        `);
    }

    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.notification-toast').remove();

        const notification = $(`
            <div class="notification-toast ${type}">
                <span class="notification-icon">${getNotificationIcon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close">×</button>
            </div>
        `);

        $('body').append(notification);

        // Show with animation
        setTimeout(() => {
            notification.addClass('show');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);

        // Manual close
        notification.find('.notification-close').on('click', function() {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }

    function getNotificationIcon(type) {
        switch (type) {
            case 'success': return '✓';
            case 'error': return '✗';
            case 'warning': return '⚠';
            default: return 'ℹ';
        }
    }

    // =============================================================================
    // EVENT HANDLERS
    // =============================================================================

    function handleKeyDown(e) {
        if (e.key === 'Escape' && $('#authModalOverlay').hasClass('active')) {
            closeAuthModal();
        }
    }

    function handleOverlayClick(e) {
        if (e.target.id === 'authModalOverlay') {
            closeAuthModal();
        }
    }

    // =============================================================================
    // GLOBAL FUNCTIONS (accessible from other scripts)
    // =============================================================================

    // Make functions globally available
    window.VinaPetAuth = {
        openModal: openAuthModal,
        closeModal: closeAuthModal,
        switchToLogin: switchToLogin,
        switchToRegister: switchToRegister,
        showNotification: showNotification
    };

    // Legacy support for existing code
    window.openAuthModal = openAuthModal;
    window.closeAuthModal = closeAuthModal;

})(jQuery);