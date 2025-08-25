/**
 * VinaPet Modal Authentication Handler
 * ERPNext Integration with Nextend Social Login
 */

(function($) {
    'use strict';

    // State management
    let isProcessing = false;
    let currentForm = 'login';
    let socialLoginWindow = null;

    // Initialize when document is ready
    $(document).ready(function() {
        initAuthModal();
    });

    // =============================================================================
    // INITIALIZATION
    // =============================================================================

    function initAuthModal() {
        // Check if required data is available
        if (typeof vinapet_auth_data === 'undefined') {
            console.warn('VinaPet Auth: Configuration data not found');
            return;
        }

        bindModalEvents();
        bindFormEvents();
        bindValidationEvents();
        bindSocialLoginEvents();
        setupAccessibility();

        // Auto-redirect logged users
        if (vinapet_auth_data.is_user_logged_in) {
            handleLoggedInUser();
        }
    }

    function bindModalEvents() {
        // Open modal buttons
        $(document).on('click', '[data-auth-modal="open"], .auth-modal-trigger, .login-trigger', function(e) {
            e.preventDefault();
            openAuthModal();
        });

        // Close modal
        $('#modalClose, .modal-overlay').on('click', function(e) {
            if (e.target === this) {
                closeAuthModal();
            }
        });

        // Form switching
        $('#switchToRegister').on('click', function(e) {
            e.preventDefault();
            switchToRegister();
        });

        $('#switchToLogin').on('click', function(e) {
            e.preventDefault();
            switchToLogin();
        });

        // Keyboard navigation
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#authModalOverlay').hasClass('active')) {
                closeAuthModal();
            }
        });
    }

    function bindSocialLoginEvents() {
        // Google Login - Login form
        $('#googleLoginBtn').on('click', function(e) {
            e.preventDefault();
            handleGoogleAuth('login');
        });

        // Google Login - Register form
        $('#googleRegisterBtn').on('click', function(e) {
            e.preventDefault();
            handleGoogleAuth('register');
        });

        // Listen for social login completion
        $(window).on('message', function(e) {
            handleSocialLoginCallback(e.originalEvent);
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

        // Password confirmation validation
        $('#registerPasswordConfirm').on('input', function() {
            validatePasswordMatch();
        });

        $('#registerPassword').on('input', function() {
            if ($('#registerPasswordConfirm').val()) {
                validatePasswordMatch();
            }
        });
    }

    // =============================================================================
    // MODAL MANAGEMENT
    // =============================================================================

    function openAuthModal() {
        $('#authModalOverlay').addClass('active');
        $('body').addClass('modal-open');
        
        // Focus management
        setTimeout(() => {
            const firstInput = $(`#${currentForm}Form .form-input:first`);
            if (firstInput.length) {
                firstInput.focus();
            }
        }, 300);

        // Analytics tracking
        trackEvent('modal_opened', { form_type: currentForm });
    }

    function closeAuthModal() {
        $('#authModalOverlay').removeClass('active');
        $('body').removeClass('modal-open');
        
        // Reset after animation
        setTimeout(() => {
            resetModal();
        }, 300);
    }

    function resetModal() {
        // Reset forms
        document.getElementById('loginForm').reset();
        document.getElementById('registerForm').reset();
        
        clearAllErrors();
        clearAllNotifications();
        
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
        $('.modal-subtitle').text('Tạo tài khoản mới');
        clearAllErrors();
        clearAllNotifications();
        
        setTimeout(() => {
            $('#registerName').focus();
        }, 100);

        trackEvent('form_switched', { to: 'register' });
    }

    function switchToLogin() {
        currentForm = 'login';
        $('#registerForm').hide();
        $('#loginForm').show().addClass('fade-in');
        $('#modalTitle').text('Đăng nhập');
        $('.modal-subtitle').text('Chào mừng bạn quay lại!');
        clearAllErrors();
        clearAllNotifications();
        
        setTimeout(() => {
            $('#loginEmail').focus();
        }, 100);

        trackEvent('form_switched', { to: 'login' });
    }

    // =============================================================================
    // FORM VALIDATION
    // =============================================================================

    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function validatePhone(phone) {
        const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    function validatePasswordMatch() {
        const password = $('#registerPassword').val();
        const confirm = $('#registerPasswordConfirm').val();
        const $confirmField = $('#registerPasswordConfirm');
        
        if (confirm && password !== confirm) {
            showFieldError($confirmField, 'Mật khẩu xác nhận không khớp');
            return false;
        } else {
            clearFieldError($confirmField);
            return true;
        }
    }

    function validateField($field) {
        const value = $field.val().trim();
        const name = $field.attr('name');
        const type = $field.attr('type');

        let isValid = true;
        let message = '';

        switch (name) {
            case 'user_email':
                if (!value) {
                    message = 'Vui lòng nhập email';
                    isValid = false;
                } else if (!validateEmail(value)) {
                    message = 'Email không hợp lệ';
                    isValid = false;
                }
                break;

            case 'user_name':
                if (!value) {
                    message = 'Vui lòng nhập họ và tên';
                    isValid = false;
                } else if (value.length < 2) {
                    message = 'Họ tên phải có ít nhất 2 ký tự';
                    isValid = false;
                }
                break;

            case 'user_phone':
                if (!value) {
                    message = 'Vui lòng nhập số điện thoại';
                    isValid = false;
                } else if (!validatePhone(value)) {
                    message = 'Số điện thoại không hợp lệ';
                    isValid = false;
                }
                break;

            case 'user_password':
                if (!value) {
                    message = 'Vui lòng nhập mật khẩu';
                    isValid = false;
                } else if (currentForm === 'register' && value.length < 6) {
                    message = 'Mật khẩu phải có ít nhất 6 ký tự';
                    isValid = false;
                }
                break;
        }

        if (!isValid) {
            showFieldError($field, message);
        } else {
            clearFieldError($field);
        }

        return isValid;
    }

    function validateLoginForm($form) {
        let isValid = true;
        
        $form.find('.form-input').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });

        return isValid;
    }

    function validateRegisterForm($form) {
        let isValid = true;
        
        // Validate all fields
        $form.find('.form-input').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });

        // Check password match
        if (!validatePasswordMatch()) {
            isValid = false;
        }

        // Check terms agreement
        if (!$('#agreeTerms').is(':checked')) {
            showNotification('Vui lòng đồng ý với điều khoản sử dụng', 'error');
            isValid = false;
        }

        return isValid;
    }

    // =============================================================================
    // ERROR HANDLING
    // =============================================================================

    function showFieldError($field, message) {
        const errorId = $field.attr('aria-describedby');
        if (errorId) {
            $field.addClass('error');
            $(`#${errorId}`).text('⚠ ' + message).show();
        }
    }

    function clearFieldError($field) {
        const errorId = $field.attr('aria-describedby');
        if (errorId) {
            $field.removeClass('error');
            $(`#${errorId}`).hide();
        }
    }

    function clearAllErrors() {
        $('.form-input').removeClass('error');
        $('.error-message').hide();
    }

    // =============================================================================
    // NOTIFICATION SYSTEM
    // =============================================================================

    function showNotification(message, type = 'info', duration = 5000) {
        const $container = $('#notificationContainer');
        const $notification = $(`
            <div class="auth-notification auth-${type}">
                <div class="notification-content">
                    <span class="notification-icon">
                        ${type === 'success' ? '✓' : type === 'error' ? '⚠' : 'ℹ'}
                    </span>
                    <span class="notification-message">${message}</span>
                </div>
                <button class="notification-close" aria-label="Đóng thông báo">×</button>
            </div>
        `);

        $container.empty().append($notification);
        
        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, duration);
        }

        // Manual dismiss
        $notification.find('.notification-close').on('click', function() {
            $notification.fadeOut(() => $notification.remove());
        });
    }

    function clearAllNotifications() {
        $('#notificationContainer').empty();
    }

    // =============================================================================
    // BUTTON STATES
    // =============================================================================

    function showButtonLoading($button, loadingText = 'Đang xử lý...') {
        $button.prop('disabled', true);
        $button.find('.btn-text').hide();
        $button.find('.btn-loading').show().find('span').text(loadingText);
    }

    function hideButtonLoading($button, originalText) {
        $button.prop('disabled', false);
        $button.find('.btn-loading').hide();
        $button.find('.btn-text').show().text(originalText);
    }

    // =============================================================================
    // AUTHENTICATION HANDLERS
    // =============================================================================

    function handleLogin(form) {
        const $form = $(form);
        
        if (!validateLoginForm($form)) {
            return;
        }

        isProcessing = true;
        const $submitBtn = $('#loginSubmit');
        showButtonLoading($submitBtn, 'Đang đăng nhập...');

        const formData = {
            action: 'vinapet_ajax_login',
            nonce: $form.find('input[name="vinapet_login_nonce"]').val(),
            user_email: $form.find('input[name="user_email"]').val().trim(),
            user_password: $form.find('input[name="user_password"]').val(),
            remember: $form.find('#rememberMe').is(':checked')
        };

        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification('Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    
                    // Track successful login
                    trackEvent('login_success', { method: 'email' });
                    
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url || vinapet_auth_data.login_redirect;
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Đăng nhập thất bại', 'error');
                    trackEvent('login_failed', { method: 'email', reason: response.data.message });
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', error);
                showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                trackEvent('login_error', { method: 'email', error: error });
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

        const formData = {
            action: 'vinapet_ajax_register',
            nonce: $form.find('input[name="vinapet_register_nonce"]').val(),
            user_name: $form.find('input[name="user_name"]').val().trim(),
            user_email: $form.find('input[name="user_email"]').val().trim(),
            user_phone: $form.find('input[name="user_phone"]').val().trim(),
            user_password: $form.find('input[name="user_password"]').val(),
            agree_terms: $form.find('#agreeTerms').is(':checked')
        };

        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification('Đăng ký thành công!', 'success');
                    
                    // Track successful registration
                    trackEvent('register_success', { method: 'email' });
                    
                    setTimeout(() => {
                        // Switch to login form and pre-fill email
                        switchToLogin();
                        $('#loginEmail').val(formData.user_email);
                        showNotification('Vui lòng đăng nhập với tài khoản mới', 'info');
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Đăng ký thất bại', 'error');
                    trackEvent('register_failed', { method: 'email', reason: response.data.message });
                }
            },
            error: function(xhr, status, error) {
                console.error('Register error:', error);
                showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                trackEvent('register_error', { method: 'email', error: error });
            },
            complete: function() {
                hideButtonLoading($submitBtn, 'Đăng ký');
                isProcessing = false;
            }
        });
    }

    // =============================================================================
    // SOCIAL LOGIN HANDLERS
    // =============================================================================

    function handleGoogleAuth(type) {
        if (!vinapet_auth_data.has_nextend) {
            showNotification('Google Login không khả dụng', 'error');
            return;
        }

        // Track social login attempt
        trackEvent('social_login_attempt', { provider: 'google', type: type });

        // Get Google login URL
        const googleUrl = getGoogleLoginUrl();
        if (!googleUrl) {
            showNotification('Không thể kết nối với Google', 'error');
            return;
        }

        // Open popup window
        const popupFeatures = 'width=500,height=600,scrollbars=yes,resizable=yes,status=yes,location=yes';
        socialLoginWindow = window.open(googleUrl, 'google_login', popupFeatures);

        if (!socialLoginWindow) {
            showNotification('Vui lòng cho phép popup để đăng nhập với Google', 'error');
            return;
        }

        // Monitor popup window
        const checkClosed = setInterval(() => {
            if (socialLoginWindow.closed) {
                clearInterval(checkClosed);
                // Check if login was successful by checking current user status
                setTimeout(() => {
                    checkLoginStatus();
                }, 1000);
            }
        }, 1000);
    }

    function getGoogleLoginUrl() {
        // Try to get Nextend Social Login URL
        if (vinapet_auth_data.google_login_url) {
            return vinapet_auth_data.google_login_url;
        }

        // Fallback: construct URL
        const baseUrl = vinapet_auth_data.home_url;
        return `${baseUrl}/wp-login.php?loginSocial=google&redirect=${encodeURIComponent(vinapet_auth_data.login_redirect)}`;
    }

    function handleSocialLoginCallback(event) {
        // Handle messages from social login popup
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === 'social_login_success') {
            if (socialLoginWindow) {
                socialLoginWindow.close();
            }
            
            showNotification('Đăng nhập thành công! Đang chuyển hướng...', 'success');
            trackEvent('social_login_success', { provider: 'google' });
            
            setTimeout(() => {
                window.location.href = event.data.redirect_url || vinapet_auth_data.login_redirect;
            }, 1500);
        } else if (event.data && event.data.type === 'social_login_error') {
            showNotification(event.data.message || 'Đăng nhập với Google thất bại', 'error');
            trackEvent('social_login_failed', { provider: 'google', reason: event.data.message });
        }
    }

    function checkLoginStatus() {
        // Check if user is now logged in after social login
        $.ajax({
            url: vinapet_auth_data.ajax_url,
            type: 'POST',
            data: {
                action: 'vinapet_check_login_status',
                nonce: vinapet_auth_data.nonce
            },
            success: function(response) {
                if (response.success && response.data.is_logged_in) {
                    showNotification('Đăng nhập thành công! Đang chuyển hướng...', 'success');
                    trackEvent('social_login_success', { provider: 'google' });
                    
                    setTimeout(() => {
                        window.location.href = response.data.redirect_url || vinapet_auth_data.login_redirect;
                    }, 1500);
                }
            },
            error: function(xhr, status, error) {
                console.error('Status check error:', error);
            }
        });
    }

    // =============================================================================
    // ACCESSIBILITY HELPERS
    // =============================================================================

    function setupAccessibility() {
        // Add keyboard navigation for modal
        $('#authModalOverlay').on('keydown', function(e) {
            if (e.key === 'Tab') {
                trapFocus(e);
            }
        });

        // Announce form switches to screen readers
        $('#modalTitle').attr('aria-live', 'polite');
    }

    function trapFocus(e) {
        const focusableElements = $('#authModalOverlay').find('button, input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])').filter(':visible');
        const firstElement = focusableElements.first();
        const lastElement = focusableElements.last();

        if (e.shiftKey && document.activeElement === firstElement[0]) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement[0]) {
            e.preventDefault();
            firstElement.focus();
        }
    }

    // =============================================================================
    // UTILITY FUNCTIONS
    // =============================================================================

    function handleLoggedInUser() {
        // If user is already logged in, redirect away from login forms
        $(document).on('click', '[data-auth-modal="open"], .auth-modal-trigger, .login-trigger', function(e) {
            e.preventDefault();
            window.location.href = vinapet_auth_data.login_redirect;
        });
    }

    function trackEvent(eventName, parameters = {}) {
        // Analytics tracking - can integrate with Google Analytics, etc.
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'authentication',
                ...parameters
            });
        }

        // Console log for debugging
        console.log('Auth Event:', eventName, parameters);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // =============================================================================
    // PUBLIC API
    // =============================================================================

    // Expose public methods
    window.VinaPetAuth = {
        open: openAuthModal,
        close: closeAuthModal,
        switchToLogin: switchToLogin,
        switchToRegister: switchToRegister,
        showNotification: showNotification
    };

    // =============================================================================
    // ERROR RECOVERY
    // =============================================================================

    // Global error handler
    window.addEventListener('error', function(e) {
        if (e.filename && e.filename.includes('modal-auth.js')) {
            console.error('VinaPet Auth Error:', e.error);
            // Reset processing state
            isProcessing = false;
            // Hide any loading states
            $('.btn-primary').prop('disabled', false);
            $('.btn-loading').hide();
            $('.btn-text').show();
        }
    });

    // Handle network errors gracefully
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (settings.url === vinapet_auth_data.ajax_url && isProcessing) {
            if (xhr.status === 0) {
                showNotification('Mất kết nối mạng. Vui lòng kiểm tra và thử lại.', 'error');
            } else if (xhr.status >= 500) {
                showNotification('Máy chủ đang bảo trì. Vui lòng thử lại sau.', 'error');
            }
        }
    });

})(jQuery);