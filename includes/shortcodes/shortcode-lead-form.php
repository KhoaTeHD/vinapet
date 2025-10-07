<?php

/**
 * File: includes/shortcodes/shortcode-lead-form.php
 * VinaPet Lead Form Shortcode
 * 
 * @package VinaPet
 */

if (!defined('ABSPATH')) {
    exit;
}

function custom_contact_form_shortcode()
{
    ob_start();
?>
    <div class="lh-contact-form">
        <form id="lh-vinapet-lead-form">
            <div class="lh-form-group">
                <label class="lh-form-label">Họ tên của bạn <span class="lh-required">*</span></label>
                <input type="text" name="contact_name" class="lh-form-input" placeholder="Nhập họ tên" required maxlength="100">
                <span class="lh-error-message" data-field="contact_name"></span>
            </div>

            <div class="lh-form-group">
                <label class="lh-form-label">Email liên hệ <span class="lh-required">*</span></label>
                <input type="email" name="email" class="lh-form-input" placeholder="Nhập email" required maxlength="100">
                <span class="lh-error-message" data-field="email"></span>
            </div>

            <div class="lh-form-group">
                <label class="lh-form-label">Điện thoại liên hệ <span class="lh-required">*</span></label>
                <input type="tel" name="phone" class="lh-form-input" placeholder="Nhập số điện thoại" required maxlength="20">
                <span class="lh-error-message" data-field="phone"></span>
            </div>

            <div class="lh-form-group">
                <label class="lh-form-label">Địa chỉ</label>
                <input type="text" name="address" class="lh-form-input" placeholder="Địa chỉ của bạn (tùy chọn)" maxlength="500">
                <span class="lh-error-message" data-field="address"></span>
            </div>

            <div class="lh-form-group">
                <label class="lh-form-label">Nội dung mong muốn hợp tác <span class="lh-required">*</span></label>
                <textarea name="needs" class="lh-form-textarea" placeholder="Điền nội dung của bạn" required minlength="10" maxlength="1000"></textarea>
                <span class="lh-error-message" data-field="needs"></span>
                <small class="lh-char-counter">0/1000 ký tự</small>
            </div>

            <button type="submit" class="lh-submit-btn">
                <span class="lh-btn-text">Gửi thông tin</span>
                <svg width="22px" height="22px" viewBox="0 0 24 24" style="margin-left: 5px;" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path d="M20.33 3.66996C20.1408 3.48213 19.9035 3.35008 19.6442 3.28833C19.3849 3.22659 19.1135 3.23753 18.86 3.31996L4.23 8.19996C3.95867 8.28593 3.71891 8.45039 3.54099 8.67255C3.36307 8.89471 3.25498 9.16462 3.23037 9.44818C3.20576 9.73174 3.26573 10.0162 3.40271 10.2657C3.5397 10.5152 3.74754 10.7185 4 10.85L10.07 13.85L13.07 19.94C13.1906 20.1783 13.3751 20.3785 13.6029 20.518C13.8307 20.6575 14.0929 20.7309 14.36 20.73H14.46C14.7461 20.7089 15.0192 20.6023 15.2439 20.4239C15.4686 20.2456 15.6345 20.0038 15.72 19.73L20.67 5.13996C20.7584 4.88789 20.7734 4.6159 20.7132 4.35565C20.653 4.09541 20.5201 3.85762 20.33 3.66996ZM4.85 9.57996L17.62 5.31996L10.53 12.41L4.85 9.57996ZM14.43 19.15L11.59 13.47L18.68 6.37996L14.43 19.15Z" fill="#19457B"></path>
                    </g>
                </svg>
                <span class="lh-btn-loading" style="display: none;">
                    <span class="lh-spinner"></span> Đang gửi...
                </span>
            </button>

            <div class="lh-form-messages">
                <div class="lh-success-message" style="display: none;"></div>
                <div class="lh-error-message-general" style="display: none;"></div>
            </div>
        </form>
    </div>

    <style>
        .lh-contact-form {
            max-width: 100%;
            padding: 0 0 30px 0px;
            border-radius: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .lh-contact-form * {
            box-sizing: border-box;
        }

        .lh-contact-form .lh-form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .lh-contact-form .lh-form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .lh-contact-form .lh-required {
            color: #e74c3c;
            font-weight: bold;
        }

        .lh-contact-form .lh-form-input,
        .lh-contact-form .lh-form-textarea {
            width: 100% !important;
            padding: 12px 16px;
            border: none;
            background-color: #f0f0f0;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
            transition: background-color 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
            line-height: 1.4;
        }

        .lh-contact-form .lh-form-textarea {
            min-height: 120px;
            resize: vertical;
            overflow-y: auto;
        }

        .lh-contact-form .lh-form-input:focus,
        .lh-contact-form .lh-form-textarea:focus {
            outline: none !important;
            background-color: #e8e8e8;
            box-shadow: none;
        }

        .lh-contact-form .lh-form-input.lh-error,
        .lh-contact-form .lh-form-textarea.lh-error {
            background-color: #fee;
            border: 1px solid #e74c3c;
        }

        .lh-contact-form .lh-form-input::placeholder,
        .lh-contact-form .lh-form-textarea::placeholder {
            color: #999;
            font-size: 14px;
            opacity: 1;
        }

        .lh-contact-form .lh-submit-btn {
            width: 100% !important;
            padding: 14px 20px;
            background: white;
            box-shadow: -3px -3px 8px 0px #79E9E4B2 inset;
            border: 1.2px solid #B4EFFD;
            border-radius: 6px;
            color: #19457B !important;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            justify-content: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .lh-contact-form .lh-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .lh-contact-form .lh-submit-btn:active {
            transform: translateY(0);
        }

        .lh-contact-form .lh-submit-btn:focus {
            outline: 2px solid rgba(56, 189, 248, 0.5);
            outline-offset: 2px;
        }

        /* Error và Success Messages */
        .lh-contact-form .lh-error-message {
            display: block;
            color: #e74c3c;
            font-size: 12px;
            margin-top: 4px;
            min-height: 16px;
        }

        .lh-contact-form .lh-char-counter {
            display: block;
            color: #666;
            font-size: 12px;
            text-align: right;
            margin-top: 4px;
        }

        .lh-contact-form .lh-form-messages {
            margin-top: 15px;
        }

        .lh-contact-form .lh-success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #c3e6cb;
            margin-bottom: 15px;
        }

        .lh-contact-form .lh-error-message-general {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px;
        }

        /* Loading Spinner */
        .lh-contact-form .lh-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #19457B;
            border-radius: 50%;
            animation: lh-spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes lh-spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .lh-contact-form {
                margin: 10px;
                padding: 20px;
            }

            .lh-contact-form .lh-form-input,
            .lh-contact-form .lh-form-textarea {
                font-size: 16px;
                /* Prevent zoom on iOS */
            }
        }

        @media (max-width: 480px) {
            .lh-contact-form {
                padding: 15px;
                margin: 5px;
            }

            .lh-contact-form .lh-submit-btn {
                font-size: 14px;
                padding: 12px 16px;
            }

            .lh-contact-form .lh-form-group {
                margin-bottom: 15px;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            const form = $('#lh-vinapet-lead-form');
            const submitBtn = form.find('.lh-submit-btn');
            const btnText = submitBtn.find('.lh-btn-text');
            const btnLoading = submitBtn.find('.lh-btn-loading');

            // Character counter for textarea
            const textarea = form.find('textarea[name="needs"]');
            const charCounter = form.find('.lh-char-counter');

            textarea.on('input', function() {
                const length = $(this).val().length;
                charCounter.text(length + '/1000 ký tự');

                if (length > 1000) {
                    charCounter.css('color', '#e74c3c');
                } else {
                    charCounter.css('color', '#666');
                }
            });

            // Form submission
            form.on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.lh-error-message').text('');
                $('.lh-form-input, .lh-form-textarea').removeClass('lh-error');
                $('.lh-form-messages > div').hide();

                // Get form data
                const formData = {
                    action: 'create_lead_submission',
                    nonce: vinapet_lead_ajax.nonce,
                    contact_name: form.find('input[name="contact_name"]').val().trim(),
                    email: form.find('input[name="email"]').val().trim(),
                    phone: form.find('input[name="phone"]').val().trim(),
                    address: form.find('input[name="address"]').val().trim(),
                    needs: form.find('textarea[name="needs"]').val().trim()
                };

                // Client-side validation
                let hasError = false;

                if (!formData.contact_name) {
                    showFieldError('contact_name', 'Vui lòng nhập họ tên.');
                    hasError = true;
                }

                if (!formData.email) {
                    showFieldError('email', 'Vui lòng nhập email.');
                    hasError = true;
                } else if (!isValidEmail(formData.email)) {
                    showFieldError('email', 'Email không hợp lệ.');
                    hasError = true;
                }

                if (!formData.phone) {
                    showFieldError('phone', 'Vui lòng nhập số điện thoại.');
                    hasError = true;
                }

                if (!formData.needs) {
                    showFieldError('needs', 'Vui lòng nhập nội dung mong muốn hợp tác.');
                    hasError = true;
                } else if (formData.needs.length < 10) {
                    showFieldError('needs', 'Nội dung phải có ít nhất 10 ký tự.');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                // Show loading state
                submitBtn.prop('disabled', true);
                btnText.hide();
                btnLoading.show();

                // Submit via AJAX
                $.ajax({
                    url: vinapet_lead_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('.lh-success-message').html(response.data.message).show();
                            console.log(response);
                            form[0].reset();
                            charCounter.text('0/1000 ký tự');
                        } else {
                            $('.lh-error-message-general').html(response.data.message || 'Có lỗi xảy ra, vui lòng thử lại.').show();
                        }
                    },
                    error: function() {
                        $('.lh-error-message-general').html('Có lỗi kết nối, vui lòng thử lại.').show();
                    },
                    complete: function() {
                        // Hide loading state
                        submitBtn.prop('disabled', false);
                        btnText.show();
                        btnLoading.hide();
                    }
                });
            });

            function showFieldError(fieldName, message) {
                const field = form.find('[name="' + fieldName + '"]');
                const errorSpan = form.find('.lh-error-message[data-field="' + fieldName + '"]');

                field.addClass('lh-error');
                errorSpan.text(message);
            }

            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
        });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('form_lien_he', 'custom_contact_form_shortcode');
