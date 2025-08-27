(function($) {
    $(document).ready(function() {
        
        // =============================================================================
        // STICKY HEADER ON SCROLL
        // =============================================================================
        
        const header = $('.site-header');
        const headerHeight = header.outerHeight();
        let lastScrollTop = 0;
        let isScrolling = false;
        
        $(window).on('scroll', function() {
            if (!isScrolling) {
                window.requestAnimationFrame(function() {
                    handleHeaderScroll();
                    isScrolling = false;
                });
                isScrolling = true;
            }
        });
        
        function handleHeaderScroll() {
            const scrollTop = $(window).scrollTop();
            
            // Add scrolled class when scrolling down
            if (scrollTop > 50) {
                header.addClass('scrolled');
            } else {
                header.removeClass('scrolled');
            }
            
            lastScrollTop = scrollTop;
        }
        
        // =============================================================================
        // MOBILE MENU
        // =============================================================================
        
        const mobileMenu = $('#mobile-menu');
        const mobileMenuToggle = $('#mobile-menu-toggle');
        const mobileMenuClose = $('#mobile-menu-close');
        const mobileMenuOverlay = $('#mobile-menu-overlay');
        
        // Open mobile menu
        if (mobileMenuToggle.length) {
            mobileMenuToggle.on('click', function(e) {
                e.preventDefault();
                openMobileMenu();
            });
        }
        
        // Close mobile menu
        if (mobileMenuClose.length) {
            mobileMenuClose.on('click', function(e) {
                e.preventDefault();
                closeMobileMenu();
            });
        }
        
        // Close mobile menu on overlay click
        if (mobileMenuOverlay.length) {
            mobileMenuOverlay.on('click', function() {
                closeMobileMenu();
            });
        }
        
        // Close mobile menu on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.hasClass('active')) {
                closeMobileMenu();
            }
        });
        
        function openMobileMenu() {
            if (mobileMenu.length) {
                mobileMenu.addClass('active');
                mobileMenuOverlay.addClass('active');
                mobileMenuToggle.addClass('active');
                $('body').addClass('mobile-menu-active');
            }
        }
        
        function closeMobileMenu() {
            if (mobileMenu.length) {
                mobileMenu.removeClass('active');
                mobileMenuOverlay.removeClass('active');
                mobileMenuToggle.removeClass('active');
                $('body').removeClass('mobile-menu-active');
            }
        }
        
        // =============================================================================
        // MEGA MENU FUNCTIONALITY
        // =============================================================================
        
        // Add mega menu support for menu items with children
        $('.nav-list .menu-item-has-children').each(function() {
            const $menuItem = $(this);
            const $megaMenu = $menuItem.find('.mega-menu');
            
            if ($megaMenu.length) {
                let hoverTimeout;
                
                $menuItem.on('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    $('.nav-list .mega-menu').removeClass('active');
                    $megaMenu.addClass('active');
                });
                
                $menuItem.on('mouseleave', function() {
                    hoverTimeout = setTimeout(() => {
                        $megaMenu.removeClass('active');
                    }, 150);
                });
            }
        });
        
        // Close mega menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-list').length) {
                $('.mega-menu').removeClass('active');
            }
        });
        
        // =============================================================================
        // SMOOTH SCROLL FOR ANCHOR LINKS
        // =============================================================================
        
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                const offset = headerHeight + 20;
                
                $('html, body').animate({
                    scrollTop: target.offset().top - offset
                }, 800);
            }
        });
        
        // =============================================================================
        // KEYBOARD NAVIGATION
        // =============================================================================
        
        // Keyboard navigation for menu
        $('.nav-list a, .search-btn, .cart-btn').on('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Tab navigation for mega menu
        $('.nav-list .menu-item-has-children a').on('focus', function() {
            $(this).closest('.menu-item-has-children').addClass('focused');
        });
        
        $('.nav-list .menu-item-has-children a').on('blur', function() {
            setTimeout(() => {
                if (!$(this).closest('.menu-item-has-children').find(':focus').length) {
                    $(this).closest('.menu-item-has-children').removeClass('focused');
                }
            }, 100);
        });
        
        // =============================================================================
        // CART FUNCTIONALITY
        // =============================================================================
        
        // Cart button click handler
        $('.cart-btn').on('click', function(e) {
            e.preventDefault();
            // Redirect to cart page or open cart sidebar
            showNotification('Tính năng giỏ hàng đang phát triển', 'info');
        });
        
        // Function to update cart count (can be called from other scripts)
        window.updateCartCount = function(count) {
            const cartCountElement = $('.cart-count');
            cartCountElement.text(count);
            
            if (count > 0) {
                cartCountElement.show();
                // Add bounce animation
                cartCountElement.addClass('bounce');
                setTimeout(() => {
                    cartCountElement.removeClass('bounce');
                }, 600);
            } else {
                cartCountElement.hide();
            }
        };
        
        // =============================================================================
        // AJAX FUNCTIONALITY
        // =============================================================================
        
        // Add to cart function (can be called from other scripts)
        window.addToCart = function(productCode, quantity = 1) {
            if (typeof vinapet_data === 'undefined') {
                showNotification('Không thể kết nối đến server', 'error');
                return;
            }
            
            $.ajax({
                url: vinapet_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_cart',
                    product_code: productCode,
                    quantity: quantity,
                    nonce: vinapet_data.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateCartCount(response.data.cart_count);
                        showNotification(response.data.message || 'Đã thêm sản phẩm vào giỏ hàng', 'success');
                    } else {
                        showNotification(response.data || 'Có lỗi xảy ra', 'error');
                    }
                },
                error: function() {
                    showNotification('Không thể kết nối đến server', 'error');
                }
            });
        };
        
        // =============================================================================
        // NOTIFICATION SYSTEM
        // =============================================================================
        
        // Show notification function
        window.showNotification = function(message, type = 'info') {
            // Remove existing notifications
            $('.header-notification').remove();
            
            const notificationClass = type === 'success' ? 'success' : 
                                    type === 'error' ? 'error' : 
                                    type === 'warning' ? 'warning' : 'info';
            
            const icon = type === 'success' ? '✓' : 
                        type === 'error' ? '✗' : 
                        type === 'warning' ? '⚠' : 'ℹ';
            
            const notification = $(`
                <div class="header-notification ${notificationClass}">
                    <span class="notification-icon">${icon}</span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
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
        };
        
        // =============================================================================
        // PERFORMANCE OPTIMIZATIONS
        // =============================================================================
        
        // Debounce resize handler
        let resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                handleResize();
            }, 150);
        });
        
        function handleResize() {
            const windowWidth = $(window).width();
            
            // Close mobile menu on resize to desktop
            if (windowWidth > 768 && mobileMenu.hasClass('active')) {
                closeMobileMenu();
            }
        }
        
        // =============================================================================
        // INITIALIZATION
        // =============================================================================
        
        // Set initial cart count
        const initialCartCount = parseInt($('.cart-count').text()) || 0;
        if (initialCartCount === 0) {
            $('.cart-count').hide();
        }
        
        // Preload mobile menu for smooth animation
        if (mobileMenu.length) {
            setTimeout(() => {
                mobileMenu.addClass('preloaded');
            }, 500);
        }
        
        // Initialize mega menu positioning
        $('.mega-menu').each(function() {
            const $menu = $(this);
            const $parent = $menu.closest('.menu-item-has-children');
            
            if ($parent.length) {
                // Center mega menu under parent item
                const parentWidth = $parent.outerWidth();
                const menuWidth = $menu.outerWidth();
                const offset = (menuWidth - parentWidth) / 2;
                
                $menu.css({
                    'margin-left': -offset + 'px'
                });
            }
        });
        
        console.log('VinaPet Header initialized successfully');
    });
    
    // =============================================================================
    // WINDOW LOAD EVENT HANDLERS
    // =============================================================================
    
    $(window).on('load', function() {
        // Final adjustments after all content loaded
        $('.site-header').addClass('loaded');
        
        // Adjust body padding for exact header height
        const exactHeaderHeight = $('.site-header').outerHeight();
        if (exactHeaderHeight > 0) {
            $('body').css('padding-top', exactHeaderHeight + 'px');
        }
    });
    
})(jQuery);

// =============================================================================
// DYNAMIC CSS INJECTION FOR ANIMATIONS
// =============================================================================

// Add CSS for animations and notifications if not already present
if (!document.getElementById('header-animation-styles')) {
    const animationStyles = document.createElement('style');
    animationStyles.id = 'header-animation-styles';
    animationStyles.textContent = `
        /* Header Animation Styles */
        .site-header {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .site-header.scrolled {
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .site-header.header-hidden {
            transform: translateY(-100%);
        }
        
        /* Mobile Menu Styles */
        body.mobile-menu-active {
            overflow: hidden;
        }
        
        .mobile-menu.preloaded {
            transition: right 0.3s ease;
        }
        
        /* Mega Menu Animations */
        .mega-menu {
            opacity: 0;
            visibility: hidden;
            margin-top: -10px;
            transition: opacity 0.3s ease, visibility 0.3s ease, margin-top 0.3s ease;
        }
        
        .mega-menu.active,
        .menu-item-has-children.focused .mega-menu {
            opacity: 1 !important;
            visibility: visible !important;
            margin-top: 0 !important;
        }
        
        /* Cart Count Animation */
        .cart-count {
            transition: all 0.3s ease;
        }
        
        .cart-count.bounce {
            animation: cartBounce 0.6s ease;
        }
        
        @keyframes cartBounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-8px);
            }
            60% {
                transform: translateY(-4px);
            }
        }
        
        /* Notification Styles */
        .header-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            max-width: 350px;
        }
        
        .header-notification.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .header-notification.success {
            background: #10B981;
            color: white;
        }
        
        .header-notification.error {
            background: #EF4444;
            color: white;
        }
        
        .header-notification.warning {
            background: #F59E0B;
            color: white;
        }
        
        .header-notification.info {
            background: #3B82F6;
            color: white;
        }
        
        .notification-icon {
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .notification-message {
            flex: 1;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        .notification-close:hover {
            opacity: 1;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-notification {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    `;
    document.head.appendChild(animationStyles);
}

/**
 * Thêm vào cuối file assets/js/header.js
 * 
 * Account Page Integration
 */

// =============================================================================
// ACCOUNT PAGE FUNCTIONALITY
// =============================================================================

// Account button click handler
$('.account-btn').on('click', function(e) {
    // Check if account URL is available
    if (typeof vinapet_account_url !== 'undefined') {
        window.location.href = vinapet_account_url;
    } else {
        // Fallback URL
        window.location.href = '/tai-khoan/';
    }
});

// Update user actions for logged in users
window.updateUserActions = function() {
    // This function can be called after login/logout to update the header
    location.reload(); // Simple refresh for now
};

// Login success callback - redirect to account page
window.onLoginSuccess = function(response) {
    if (response && response.success) {
        showNotification('Đăng nhập thành công!', 'success');
        
        // Small delay before redirect
        setTimeout(() => {
            if (typeof vinapet_account_url !== 'undefined') {
                window.location.href = vinapet_account_url;
            } else {
                window.location.href = '/tai-khoan/';
            }
        }, 1000);
    }
};

// Notification helper function (if not already exists)
if (typeof showNotification === 'undefined') {
    window.showNotification = function(message, type = 'info') {
        // Create notification element
        const notification = $(`
            <div class="notification notification-${type}">
                <span class="notification-text">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);
        
        // Add to page
        $('body').append(notification);
        
        // Show with animation
        setTimeout(() => {
            notification.addClass('show');
        }, 100);
        
        // Auto hide after 3 seconds
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
        
        // Manual close
        notification.find('.notification-close').on('click', () => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    };
}

// CSS for notifications (add to header.css)
const notificationCSS = `
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 10001;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 300px;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left: 4px solid #28a745;
}

.notification-error {
    border-left: 4px solid #dc3545;
}

.notification-info {
    border-left: 4px solid #17a2b8;
}

.notification-text {
    flex: 1;
    font-size: 14px;
    color: #333;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: #333;
}
</style>
`;

// Add notification CSS if not exists
if (!$('#notification-css').length) {
    $('head').append(notificationCSS);
}