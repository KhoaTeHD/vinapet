/**
 * VinaPet Modal Authentication Handler
 * ERPNext Integration with Nextend Social Login
 */

(function ($) {
  "use strict";

  // State management
  let isProcessing = false;
  let currentForm = "login";
  let socialLoginWindow = null;

  // Initialize when document is ready
  $(document).ready(function () {
    initAuthModal();
  });

  // =============================================================================
  // INITIALIZATION
  // =============================================================================

  function initAuthModal() {
    // Check if required data is available
    if (typeof vinapet_auth_data === "undefined") {
      console.warn("VinaPet Auth: Configuration data not found");
      return;
    }

    bindModalEvents();
    bindFormEvents();
    bindValidationEvents();
    bindSocialLoginEvents();
    setupAccessibility();

    // THÊM MESSAGE LISTENER VÀO ĐÂY
    bindPopupMessageListener();

    // Auto-redirect logged users
    if (vinapet_auth_data.is_user_logged_in) {
      handleLoggedInUser();
    }
  }

  function bindPopupMessageListener() {
    // Listen for messages from Google OAuth popup
    window.addEventListener("message", function (event) {

      // Kiểm tra message type
      if (event.data && event.data.type === "google_register_required") {
        console.log(
          "✅ Received Google register required message:",
          event.data
        );

        // Đóng popup nếu còn reference
        if (socialLoginWindow && !socialLoginWindow.closed) {
          socialLoginWindow.close();
          socialLoginWindow = null;
          console.log("✅ Popup closed");
        }

        // Mở modal Google register với data từ popup
        setTimeout(function () {
          console.log("✅ Opening modal...");
          openAuthModal(); // BÂY GIỜ FUNCTION NÀY CÓ THỂ ACCESS ĐƯỢC
          setTimeout(function () {
            console.log("✅ Switching to Google register form...");
            switchToGoogleRegister(event.data.email, event.data.name);
          }, 300);
        }, 500);
      } else {
      }
    });

  }

  function bindModalEvents() {
    // Open modal buttons
    $(document).on(
      "click",
      '[data-auth-modal="open"], .auth-modal-trigger, .login-trigger',
      function (e) {
        e.preventDefault();
        openAuthModal();
      }
    );

    // Close modal
    $("#modalClose, .modal-overlay").on("click", function (e) {
      if (e.target === this) {
        closeAuthModal();
      }
    });

    // Form switching
    $("#switchToRegister").on("click", function (e) {
      e.preventDefault();
      switchToRegister();
    });

    $("#switchToLogin").on("click", function (e) {
      e.preventDefault();
      switchToLogin();
    });

    // Keyboard navigation
    $(document).on("keydown", function (e) {
      if (e.key === "Escape" && $("#authModalOverlay").hasClass("active")) {
        closeAuthModal();
      }
    });
  }

  function bindSocialLoginEvents() {
    // Google Login - Login form
    $("#googleLoginBtn").on("click", function (e) {
      e.preventDefault();
      handleGoogleAuth("login");
    });

    // Google Login - Register form
    $("#googleRegisterBtn").on("click", function (e) {
      e.preventDefault();
      handleGoogleAuth("register");
    });

    // Listen for social login completion
    $(window).on("message", function (e) {
      handleSocialLoginCallback(e.originalEvent);
    });
  }

  function bindFormEvents() {
    // Login form submission
    $("#loginForm").on("submit", function (e) {
      e.preventDefault();
      if (!isProcessing) {
        handleLogin(this);
      }
    });

    // Register form submission
    $("#registerForm").on("submit", function (e) {
      e.preventDefault();
      if (!isProcessing) {
        handleRegister(this);
      }
    });

    // Google Register form submission
    $("#googleRegisterForm").on("submit", function (e) {
      e.preventDefault();
      if (!isProcessing) {
        handleGoogleRegister(this);
      }
    });
  }

  function bindValidationEvents() {
    // Real-time validation
    $(".form-input").on("input", function () {
      clearFieldError($(this));
    });

    $(".form-input").on("blur", function () {
      validateField($(this));
    });

    // Password confirmation validation
    $("#registerPasswordConfirm").on("input", function () {
      validatePasswordMatch();
    });

    $("#registerPassword").on("input", function () {
      if ($("#registerPasswordConfirm").val()) {
        validatePasswordMatch();
      }
    });
  }

  function setupAccessibility() {
    // Add keyboard navigation for modal
    $("#authModalOverlay").on("keydown", function (e) {
      if (e.key === "Tab") {
        trapFocus(e);
      }
    });

    // Announce form switches to screen readers
    $("#modalTitle").attr("aria-live", "polite");
  }

  function trapFocus(e) {
    const focusableElements = $("#authModalOverlay")
      .find(
        'button, input, select, textarea, a[href], [tabindex]:not([tabindex="-1"])'
      )
      .filter(":visible");
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
  // MODAL MANAGEMENT
  // =============================================================================

  function openAuthModal() {
    $("#authModalOverlay").addClass("active");
    $("body").addClass("modal-open");

    // Focus management
    setTimeout(() => {
      const firstInput = $(`#${currentForm}Form .form-input:first`);
      if (firstInput.length) {
        firstInput.focus();
      }
    }, 300);

    // Analytics tracking
    trackEvent("modal_opened", { form_type: currentForm });
  }

  function closeAuthModal() {
    $("#authModalOverlay").removeClass("active");
    $("body").removeClass("modal-open");

    // Reset after animation
    setTimeout(() => {
      resetModal();
    }, 300);
  }

  function resetModal() {
    // Reset forms
    document.getElementById("loginForm").reset();
    document.getElementById("registerForm").reset();
    document.getElementById("googleRegisterForm").reset();

    clearAllErrors();
    clearAllNotifications();

    // Switch back to login
    if (currentForm !== "login") {
      switchToLogin();
    }

    isProcessing = false;
  }

  // =============================================================================
  // FORM SWITCHING
  // =============================================================================

  function switchToRegister() {
    currentForm = "register";
    $("#loginForm").hide();
    $("#registerForm").show().addClass("fade-in");
    $("#modalTitle").text("Đăng ký");
    $(".modal-subtitle").text("Tạo tài khoản mới");
    clearAllErrors();
    clearAllNotifications();

    setTimeout(() => {
      $("#registerName").focus();
    }, 100);

    trackEvent("form_switched", { to: "register" });
  }

  function switchToLogin() {
    currentForm = "login";
    $("#registerForm, #googleRegisterForm").hide();
    $("#loginForm").show().addClass("fade-in");
    $("#modalTitle").text("Đăng nhập");
    $(".modal-subtitle").text("Chào mừng bạn quay lại!");
    clearAllErrors();
    clearAllNotifications();

    setTimeout(() => {
      $("#loginEmail").focus();
    }, 100);

    trackEvent("form_switched", { to: "login" });
  }

  function switchToGoogleRegister(email, name = "") {
    currentForm = "google_register";
    $("#loginForm, #registerForm").hide();
    $("#googleRegisterForm").show().addClass("fade-in");
    $("#modalTitle").text("Đăng ký tài khoản");
    $(".modal-subtitle").text("Tạo tài khoản với Google");

    // Pre-fill email và name
    $("#googleRegisterEmail").val(email);
    if (name) {
      $("#googleRegisterName").val(name);
    }

    clearAllErrors();
    clearAllNotifications();

    setTimeout(() => {
      if (!name) {
        $("#googleRegisterName").focus();
      } else {
        $("#googleRegisterAddress").focus();
      }
    }, 100);

    trackEvent("form_switched", { to: "google_register" });
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
    return phoneRegex.test(phone.replace(/\s/g, ""));
  }

  function validatePasswordMatch() {
    const password = $("#registerPassword").val();
    const confirm = $("#registerPasswordConfirm").val();
    const $confirmField = $("#registerPasswordConfirm");

    if (confirm && password !== confirm) {
      showFieldError($confirmField, "Mật khẩu xác nhận không khớp");
      return false;
    } else {
      clearFieldError($confirmField);
      return true;
    }
  }

  function validateField($field) {
    const value = $field.val().trim();
    const name = $field.attr("name");
    const type = $field.attr("type");

    let isValid = true;
    let message = "";

    switch (name) {
      case "user_email":
        if (!value) {
          message = "Vui lòng nhập email";
          isValid = false;
        } else if (!validateEmail(value)) {
          message = "Email không hợp lệ";
          isValid = false;
        }
        break;

      case "user_name":
        if (!value) {
          message = "Vui lòng nhập họ và tên";
          isValid = false;
        } else if (value.length < 2) {
          message = "Họ tên phải có ít nhất 2 ký tự";
          isValid = false;
        }
        break;

      case "user_address":
        if (!value || value.length < 10) {
          message = "Vui lòng nhập địa chỉ đầy đủ (ít nhất 10 ký tự)";
          isValid = false;
        }
        break;

      case "user_password":
        if (!value) {
          message = "Vui lòng nhập mật khẩu";
          isValid = false;
        } else if (value.length < 6) {
          message = "Mật khẩu phải có ít nhất 6 ký tự";
          isValid = false;
        }
        break;

      case "user_phone":
        if (value && !validatePhone(value)) {
          message = "Số điện thoại không hợp lệ";
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

    const formData = getFormData($form);

    // Basic validation
    if (!formData.user_email || !validateEmail(formData.user_email)) {
      showFieldError(
        $form.find('input[name="user_email"]'),
        "Email không hợp lệ"
      );
      isValid = false;
    }

    if (!formData.user_password) {
      showFieldError(
        $form.find('input[name="user_password"]'),
        "Vui lòng nhập mật khẩu"
      );
      isValid = false;
    }

    return isValid;
  }

  function validateRegisterForm($form) {
    let isValid = true;

    // Validate each field
    $form.find(".form-input").each(function () {
      if (!validateField($(this))) {
        isValid = false;
      }
    });

    // Check password match
    if (!validatePasswordMatch()) {
      isValid = false;
    }

    // Check terms agreement
    if (!$("#agreeTerms").is(":checked")) {
      showNotification("Vui lòng đồng ý với điều khoản sử dụng", "error");
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
    const $submitBtn = $("#loginSubmit");
    showButtonLoading($submitBtn, "Đang đăng nhập...");

    const formData = getFormData($form);
    formData.action = "vinapet_ajax_login";
    formData.nonce = vinapet_auth_data.login_nonce;

    $.ajax({
      url: vinapet_auth_data.ajax_url,
      type: "POST",
      data: formData,
      timeout: 30000,
      success: function (response) {
        if (response.success) {
          showNotification(
            "Đăng nhập thành công! Đang chuyển hướng...",
            "success"
          );

          // Track successful login
          trackEvent("login_success", { method: "email" });

          setTimeout(() => {
            window.location.href =
              response.data.redirect_url || vinapet_auth_data.login_redirect;
          }, 1500);
        } else {
          showNotification(
            response.data.message || "Đăng nhập thất bại",
            "error"
          );
          trackEvent("login_failed", {
            method: "email",
            reason: response.data.message,
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Login error:", error);
        showNotification("Có lỗi xảy ra. Vui lòng thử lại.", "error");
        trackEvent("login_error", { method: "email", error: error });
      },
      complete: function () {
        hideButtonLoading($submitBtn, "Đăng nhập");
        isProcessing = false;
      },
    });
  }

  function handleRegister(form) {
    const $form = $(form);

    if (!validateRegisterForm($form)) {
      return;
    }

    isProcessing = true;
    const $submitBtn = $("#registerSubmit");
    showButtonLoading($submitBtn, "Đang đăng ký...");

    const formData = {
      action: "vinapet_ajax_register",
      nonce: vinapet_auth_data.register_nonce,
      user_name: $form.find('input[name="user_name"]').val().trim(),
      user_address: $form.find('textarea[name="user_address"]').val().trim(),
      user_email: $form.find('input[name="user_email"]').val().trim(),
      user_phone: $form.find('input[name="user_phone"]').val().trim(),
      user_password: $form.find('input[name="user_password"]').val(),
      agree_terms: $form.find("#agreeTerms").is(":checked"),
    };

    $.ajax({
      url: vinapet_auth_data.ajax_url,
      type: "POST",
      data: formData,
      timeout: 30000,
      success: function (response) {
        if (response.success) {
          showNotification(
            "Đăng ký thành công! Thông tin đã được đồng bộ với hệ thống.",
            "success"
          );

          // Track successful registration
          trackEvent("register_success", { method: "email" });

          setTimeout(() => {
            // Switch to login form and pre-fill email
            switchToLogin();
            $("#loginEmail").val(formData.user_email);
            showNotification("Vui lòng đăng nhập với tài khoản mới", "info");
          }, 1500);
        } else {
          showNotification(
            response.data.message || "Đăng ký thất bại",
            "error"
          );
          trackEvent("register_failed", {
            method: "email",
            reason: response.data.message,
          });
        }
      },
      error: function (xhr, status, error) {
        console.log("Register error:", error);
        showNotification("Có lỗi xảy ra. Vui lòng thử lại.", "error");
        trackEvent("register_failed", {
          method: "email",
          reason: "network_error",
        });
      },
      complete: function () {
        hideButtonLoading($submitBtn, "Đăng ký");
        isProcessing = false;
      },
    });
  }

  // Function xử lý Google Register
  function handleGoogleRegister(form) {
    const $form = $(form);

    if (!validateGoogleRegisterForm($form)) {
      return;
    }

    isProcessing = true;
    const $submitBtn = $("#googleRegisterSubmit");
    showButtonLoading($submitBtn, "Đang tạo tài khoản...");

    const formData = {
      action: "vinapet_ajax_google_register",
      nonce: vinapet_auth_data.register_nonce,
      user_name: $form.find('input[name="user_name"]').val().trim(),
      user_address: $form.find('textarea[name="user_address"]').val().trim(),
      user_email: $form.find('input[name="user_email"]').val().trim(),
      user_phone: $form.find('input[name="user_phone"]').val().trim(),
      registration_type: "google",
      agree_terms: $form.find("#googleAgreeTerms").is(":checked"),
    };

    $.ajax({
      url: vinapet_auth_data.ajax_url,
      type: "POST",
      data: formData,
      timeout: 30000,
      success: function (response) {
        if (response.success) {
          showNotification(
            "Tạo tài khoản thành công! Đang đăng nhập...",
            "success"
          );

          trackEvent("register_success", { method: "google" });

          setTimeout(() => {
            window.location.href =
              response.data.redirect_url || vinapet_auth_data.login_redirect;
          }, 1500);
        } else {
          showNotification(
            response.data.message || "Đăng ký thất bại",
            "error"
          );
          trackEvent("register_failed", {
            method: "google",
            reason: response.data.message,
          });
        }
      },
      error: function (xhr, status, error) {
        console.error("Google register error:", error);
        showNotification("Có lỗi xảy ra. Vui lòng thử lại.", "error");
        trackEvent("register_error", { method: "google", error: error });
      },
      complete: function () {
        hideButtonLoading($submitBtn, "Hoàn tất đăng ký");
        isProcessing = false;
      },
    });
  }

  // Validation cho Google Register form
  function validateGoogleRegisterForm($form) {
    let isValid = true;

    // Validate required fields
    $form.find(".form-input").each(function () {
      const $field = $(this);
      if ($field.attr("required") && !$field.prop("disabled")) {
        if (!validateField($field)) {
          isValid = false;
        }
      }
    });

    // Check terms agreement
    if (!$("#googleAgreeTerms").is(":checked")) {
      showNotification("Vui lòng đồng ý với điều khoản sử dụng", "error");
      isValid = false;
    }

    return isValid;
  }

  // =============================================================================
  // SOCIAL LOGIN HANDLERS
  // =============================================================================

  function handleGoogleAuth(type) {
    if (!vinapet_auth_data.has_nextend) {
      showNotification("Social login không khả dụng", "error");
      return;
    }

    trackEvent("social_login_attempt", { provider: "google", type: type });

    // Open popup window for Google auth
    const authUrl =
      vinapet_auth_data.home_url + "/wp-login.php?loginSocial=google";
    const popup = window.open(
      authUrl,
      "googleAuth",
      "width=500,height=600,scrollbars=yes,resizable=yes,menubar=no,toolbar=no,status=yes"
    );

    socialLoginWindow = popup;

    // Check if popup is closed manually
    const checkClosed = setInterval(() => {
      if (popup.closed) {
        clearInterval(checkClosed);
        socialLoginWindow = null;
      }
    }, 1000);
  }

  function handleSocialLoginCallback(event) {
    if (event.data && event.data.type === "social_login_success") {
      if (socialLoginWindow) {
        socialLoginWindow.close();
        socialLoginWindow = null;
      }

      showNotification("Đăng nhập thành công! Đang chuyển hướng...", "success");
      trackEvent("social_login_success", { provider: "google" });

      setTimeout(() => {
        window.location.href = vinapet_auth_data.login_redirect;
      }, 1000);
    } else if (event.data && event.data.type === "social_login_error") {
      showNotification("Đăng nhập mạng xã hội thất bại", "error");
      trackEvent("social_login_failed", { provider: "google" });
    }
  }

  function checkLoginStatus() {
    // Check if user is now logged in after social login
    $.ajax({
      url: vinapet_auth_data.ajax_url,
      type: "POST",
      data: {
        action: "vinapet_check_login_status",
        nonce: vinapet_auth_data.nonce,
      },
      success: function (response) {
        if (response.success && response.data.is_logged_in) {
          showNotification(
            "Đăng nhập thành công! Đang chuyển hướng...",
            "success"
          );
          trackEvent("social_login_success", { provider: "google" });

          setTimeout(() => {
            window.location.href =
              response.data.redirect_url || vinapet_auth_data.login_redirect;
          }, 1500);
        }
      },
      error: function (xhr, status, error) {
        console.error("Status check error:", error);
      },
    });
  }

  // =============================================================================
  // ERROR HANDLING
  // =============================================================================

  function showFieldError($field, message) {
    const errorId = $field.attr("aria-describedby");
    if (errorId) {
      $field.addClass("error");
      $(`#${errorId}`)
        .text("⚠ " + message)
        .show();
    }
  }

  function clearFieldError($field) {
    const errorId = $field.attr("aria-describedby");
    if (errorId) {
      $field.removeClass("error");
      $(`#${errorId}`).hide();
    }
  }

  function clearAllErrors() {
    $(".form-input").removeClass("error");
    $(".error-message").hide();
  }

  // =============================================================================
  // NOTIFICATION SYSTEM
  // =============================================================================

  function showNotification(message, type = "info", duration = 5000) {
    const $container = $("#notificationContainer");
    const $notification = $(`
            <div class="auth-notification auth-${type}">
                <div class="notification-content">
                    <span class="notification-icon">
                          ${
                            type === "success"
                              ? "✓"
                              : type === "error"
                              ? "⚠"
                              : "ℹ"
                          }
                    </span>
                    <span class="notification-message">${message}</span>
                    <button type="button" class="notification-close" aria-label="Đóng thông báo">
                        ×
                    </button>
                </div>
            </div>
        `);

    // Add notification to container
    if ($container.length) {
      $container.append($notification);
    } else {
      $(".modal-content").prepend($notification);
    }

    // Show notification
    $notification.addClass("show");

    // Auto-hide after specified duration
    setTimeout(() => {
      $notification.removeClass("show");
      setTimeout(() => $notification.remove(), 300);
    }, duration);

    // Manual close
    $notification.find(".notification-close").on("click", () => {
      $notification.removeClass("show");
      setTimeout(() => $notification.remove(), 300);
    });

    return $notification;
  }

  function clearAllNotifications() {
    $(".auth-notification").removeClass("show");
    setTimeout(() => $(".auth-notification").remove(), 300);
  }

  // =============================================================================
  // UI HELPERS
  // =============================================================================

  function showButtonLoading($button, text) {
    $button.data("original-text", $button.html());
    $button
      .html(`<span class="btn-loading"></span> ${text}`)
      .prop("disabled", true);
  }

  function hideButtonLoading($button, defaultText) {
    const originalText = $button.data("original-text") || defaultText;
    $button.html(originalText).prop("disabled", false);
  }

  function getFormData($form) {
    const formData = {};
    $form.find("input, textarea, select").each(function () {
      const $field = $(this);
      const name = $field.attr("name");
      const type = $field.attr("type");

      if (name) {
        if (type === "checkbox") {
          formData[name] = $field.is(":checked");
        } else if (type === "radio") {
          if ($field.is(":checked")) {
            formData[name] = $field.val();
          }
        } else {
          formData[name] = $field.val();
        }
      }
    });
    return formData;
  }

  // =============================================================================
  // UTILITY FUNCTIONS
  // =============================================================================

  function handleLoggedInUser() {
    // If user is already logged in, redirect away from login forms
    $(document).on(
      "click",
      '[data-auth-modal="open"], .auth-modal-trigger, .login-trigger',
      function (e) {
        e.preventDefault();
        window.location.href = vinapet_auth_data.login_redirect;
      }
    );
  }

  function trackEvent(eventName, parameters = {}) {
    // Analytics tracking - can integrate with Google Analytics, etc.
    if (typeof gtag !== "undefined") {
      gtag("event", eventName, {
        event_category: "authentication",
        ...parameters,
      });
    }

    // Console log for debugging
    console.log("Auth Event:", eventName, parameters);
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
    showNotification: showNotification,
  };

  // =============================================================================
  // ERROR RECOVERY
  // =============================================================================

  // Global error handler
  window.addEventListener("error", function (e) {
    if (e.filename && e.filename.includes("modal-auth.js")) {
      console.error("VinaPet Auth Error:", e.error);
      // Reset processing state
      isProcessing = false;
      // Hide any loading states
      $(".btn-primary").prop("disabled", false);
      $(".btn-loading").hide();
      $(".btn-text").show();
    }
  });

  // Handle network errors gracefully
  $(document).ajaxError(function (event, xhr, settings, thrownError) {
    if (settings.url === vinapet_auth_data.ajax_url && isProcessing) {
      if (xhr.status === 0) {
        showNotification(
          "Mất kết nối mạng. Vui lòng kiểm tra và thử lại.",
          "error"
        );
      } else if (xhr.status >= 500) {
        showNotification(
          "Máy chủ đang bảo trì. Vui lòng thử lại sau.",
          "error"
        );
      }
    }
  });
})(jQuery);
