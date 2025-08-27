/**
 * Account Page JavaScript
 * Handles tab switching, form submissions, and AJAX requests
 */

(function($) {
    'use strict';

    class AccountPage {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initTabs();
        }

        bindEvents() {
            // Sidebar menu items
            $('.menu-item[data-tab]').on('click', (e) => {
                this.switchMainTab($(e.currentTarget).data('tab'));
            });

            // Profile sub-tabs
            $('.tab-button[data-target]').on('click', (e) => {
                this.switchSubTab($(e.currentTarget).data('target'));
            });

            // Logout button
            $('#logout-btn').on('click', () => {
                this.handleLogout();
            });

            // Form submissions
            $('#profile-info-form').on('submit', (e) => {
                e.preventDefault();
                this.updateProfile();
            });

            $('#change-password-form').on('submit', (e) => {
                e.preventDefault();
                this.changePassword();
            });

            // Close message
            $(document).on('click', '.message-close', () => {
                this.hideMessage();
            });
        }

        initTabs() {
            // Ensure first tab is active by default
            $('.menu-item').first().addClass('active');
            $('.tab-content').first().addClass('active');
            $('.tab-button').first().addClass('active');
            $('.tab-pane').first().addClass('active');
        }

        switchMainTab(tabId) {
            // Update sidebar menu
            $('.menu-item').removeClass('active');
            $(`.menu-item[data-tab="${tabId}"]`).addClass('active');

            // Update main content
            $('.tab-content').removeClass('active');
            $(`#${tabId}-tab`).addClass('active');
        }

        switchSubTab(targetId) {
            // Update tab buttons
            $('.tab-button').removeClass('active');
            $(`.tab-button[data-target="${targetId}"]`).addClass('active');

            // Update tab panes
            $('.tab-pane').removeClass('active');
            $(`#${targetId}`).addClass('active');
        }

        updateProfile() {
            const formData = new FormData($('#profile-info-form')[0]);
            formData.append('action', 'update_profile_info');

            this.showLoading();

            $.ajax({
                url: vinapet_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showMessage('Cập nhật thông tin thành công!', 'success');
                    } else {
                        this.showMessage(response.data || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Có lỗi xảy ra khi cập nhật!', 'error');
                }
            });
        }

        changePassword() {
            const currentPassword = $('#current_password').val();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();

            // Validation
            if (!currentPassword || !newPassword || !confirmPassword) {
                this.showMessage('Vui lòng điền đầy đủ thông tin!', 'error');
                return;
            }

            if (newPassword !== confirmPassword) {
                this.showMessage('Mật khẩu mới không khớp!', 'error');
                return;
            }

            if (newPassword.length < 6) {
                this.showMessage('Mật khẩu mới phải có ít nhất 6 ký tự!', 'error');
                return;
            }

            const formData = new FormData($('#change-password-form')[0]);
            formData.append('action', 'change_user_password');

            this.showLoading();

            $.ajax({
                url: vinapet_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showMessage('Đổi mật khẩu thành công!', 'success');
                        $('#change-password-form')[0].reset();
                    } else {
                        this.showMessage(response.data || 'Có lỗi xảy ra!', 'error');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showMessage('Có lỗi xảy ra khi đổi mật khẩu!', 'error');
                }
            });
        }

        handleLogout() {
            if (confirm('Bạn có chắc muốn đăng xuất?')) {
                window.location.href = vinapet_ajax.logout_url || wp_logout_url();
            }
        }

        showLoading() {
            $('#loading-overlay').show();
        }

        hideLoading() {
            $('#loading-overlay').hide();
        }

        showMessage(message, type = 'success') {
            const messageOverlay = $('#message-overlay');
            const messageContent = messageOverlay.find('.message-content');
            const messageText = messageOverlay.find('.message-text');

            messageContent.removeClass('success error').addClass(type);
            messageText.text(message);
            messageOverlay.show();

            // Auto hide after 3 seconds
            setTimeout(() => {
                this.hideMessage();
            }, 3000);
        }

        hideMessage() {
            $('#message-overlay').hide();
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        new AccountPage();
    });

})(jQuery);

// Helper function to get WordPress logout URL
function wp_logout_url() {
    return window.location.origin + '/wp-login.php?action=logout&redirect_to=' + encodeURIComponent(window.location.origin);
}