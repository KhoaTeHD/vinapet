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

/**
 * VinaPet Header Mobile/Tablet JavaScript
 * Xử lý responsive header cho mobile và tablet
 */

(function($) {
    'use strict';

    // DOM Ready
    $(document).ready(function() {
        initMobileHeader();
        initResponsiveActions();
        initCartCounter();
    });

    /**
     * Initialize Mobile Header
     */
    function initMobileHeader() {
        const mobileToggle = $('#mobile-menu-toggle');
        const mobileMenu = $('#mobile-menu');
        
        if (mobileToggle.length && mobileMenu.length) {
            // Toggle mobile menu
            mobileToggle.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                $(this).toggleClass('active');
                mobileMenu.toggleClass('active');
                $('body').toggleClass('mobile-menu-open');
                
                // Update aria attributes
                const expanded = $(this).hasClass('active');
                $(this).attr('aria-expanded', expanded);
                mobileMenu.attr('aria-hidden', !expanded);
            });
            
            // Close mobile menu when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#mobile-menu, #mobile-menu-toggle').length) {
                    closeMobileMenu();
                }
            });
            
            // Close mobile menu on escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileMenu();
                }
            });
            
            // Close mobile menu on window resize to desktop
            $(window).on('resize', debounce(function() {
                if ($(window).width() > 768) {
                    closeMobileMenu();
                }
            }, 250));
        }
    }

    /**
     * Close Mobile Menu
     */
    function closeMobileMenu() {
        $('#mobile-menu-toggle').removeClass('active').attr('aria-expanded', 'false');
        $('#mobile-menu').removeClass('active').attr('aria-hidden', 'true');
        $('body').removeClass('mobile-menu-open');
    }

    /**
     * Initialize Responsive Actions
     */
    function initResponsiveActions() {
        // Handle login button clicks
        $(document).on('click', '[data-auth-modal="open"]', function(e) {
            e.preventDefault();
            openAuthModal();
        });
        
        // Handle mobile cart icon positioning
        positionMobileCartIcon();
        
        // Update on window resize
        $(window).on('resize', debounce(function() {
            positionMobileCartIcon();
            updateResponsiveDisplay();
        }, 100));
        
        // Initial responsive display update
        updateResponsiveDisplay();
    }

    /**
     * Position Mobile Cart Icon
     */
    function positionMobileCartIcon() {
        const cartIcon = $('.mobile-cart-icon');
        if (cartIcon.length && $(window).width() <= 768) {
            // Đảm bảo cart icon không bị che bởi hamburger menu
            const hamburgerWidth = 50; // Width of hamburger button + margin
            cartIcon.css('right', hamburgerWidth + 'px');
        }
    }

    /**
     * Update Responsive Display based on screen size
     */
    function updateResponsiveDisplay() {
        const windowWidth = $(window).width();
        
        if (windowWidth <= 768) {
            // Mobile
            handleMobileDisplay();
        } else if (windowWidth <= 1024) {
            // Tablet
            handleTabletDisplay();
        } else {
            // Desktop
            handleDesktopDisplay();
        }
    }

    /**
     * Handle Mobile Display
     */
    function handleMobileDisplay() {
        // Hide desktop elements
        $('.desktop-only, .desktop-tablet-only').hide();
        $('.mobile-only').show();
        
        // Update user actions for mobile
        if (isUserLoggedIn()) {
            // Logged in mobile: show account icon only, cart in mobile menu
            $('.header-user-actions .cart-btn').hide();
            $('.mobile-cart-icon').show();
        } else {
            // Not logged in mobile: show login trigger
            $('.mobile-login-wrapper').show();
        }
    }

    /**
     * Handle Tablet Display  
     */
    function handleTabletDisplay() {
        $('.desktop-only').hide();
        $('.mobile-only').hide();
        $('.tablet-only, .desktop-tablet-only').show();
        
        // On tablet, show both account and cart buttons
        if (isUserLoggedIn()) {
            $('.header-user-actions .cart-btn').show();
            $('.mobile-cart-icon').hide();
        }
    }

    /**
     * Handle Desktop Display
     */
    function handleDesktopDisplay() {
        $('.mobile-only, .tablet-only').hide();
        $('.desktop-only, .desktop-tablet-only').show();
        
        // Desktop: full buttons with text
        $('.mobile-cart-icon').hide();
    }

    /**
     * Initialize Cart Counter
     */
    function initCartCounter() {
        // Get cart count from ERPNext or local storage
        updateCartCount();
        
        // Listen for cart updates
        $(document).on('vinapet_cart_updated', function(e, count) {
            updateCartCount(count);
        });
        
        // Periodic cart count refresh (every 30 seconds)
        setInterval(updateCartCount, 30000);
    }

    /**
     * Update Cart Count Display
     */
    function updateCartCount(count = null) {
        if (count === null) {
            // Fetch from server/local storage
            count = getCartCount();
        }
        
        const cartCountElements = $('.cart-count');
        
        if (count > 0) {
            cartCountElements.text(count).show();
        } else {
            cartCountElements.hide();
        }
        
        // Update aria-label for accessibility
        $('.cart-btn, .mobile-cart-icon a').each(function() {
            const label = count > 0 ? 
                `Giỏ hàng (${count} sản phẩm)` : 
                'Giỏ hàng';
            $(this).attr('aria-label', label);
        });
    }

    /**
     * Get Cart Count from ERPNext/Local Storage
     */
    function getCartCount() {
        // Implementation depends on your ERPNext integration
        // This could be an AJAX call or localStorage
        
        try {
            // Check if user is logged in
            if (!isUserLoggedIn()) {
                return 0;
            }
            
            // Get from local storage first (faster)
            const localCount = localStorage.getItem('vinapet_cart_count');
            if (localCount !== null) {
                return parseInt(localCount, 10);
            }
            
            // Fallback AJAX call to get real count
            fetchCartCountFromServer();
            
            return 0;
        } catch (error) {
            console.warn('Error getting cart count:', error);
            return 0;
        }
    }

    /**
     * Fetch Cart Count from Server
     */
    function fetchCartCountFromServer() {
        if (typeof vinapet_ajax === 'undefined') {
            return;
        }
        
        $.ajax({
            url: vinapet_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'vinapet_get_cart_count',
                nonce: vinapet_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const count = parseInt(response.data.count, 10);
                    updateCartCount(count);
                    
                    // Cache in localStorage
                    localStorage.setItem('vinapet_cart_count', count);
                }
            },
            error: function(xhr, status, error) {
                console.warn('Error fetching cart count:', error);
            }
        });
    }

    /**
     * Check if user is logged in
     */
    function isUserLoggedIn() {
        // Check if user is logged in via WordPress
        return $('body').hasClass('logged-in') || 
               $('.account-btn').length > 0 ||
               (typeof vinapet_ajax !== 'undefined' && vinapet_ajax.is_logged_in);
    }

    /**
     * Open Authentication Modal
     */
    function openAuthModal() {
        // Trigger auth modal (assumes you have auth modal implementation)
        if (typeof window.vinapetAuthModal !== 'undefined') {
            window.vinapetAuthModal.open();
        } else {
            // Fallback: redirect to login page
            window.location.href = '/dang-nhap';
        }
    }

    /**
     * Handle Cart Button Click
     */
    $(document).on('click', '.cart-btn, .mobile-cart-icon a', function(e) {
        e.preventDefault();
        
        // Check if user is logged in
        if (!isUserLoggedIn()) {
            openAuthModal();
            return;
        }
        
        // Navigate to cart page
        window.location.href = $(this).attr('href');
    });

    /**
     * Handle Account Button Click  
     */
    $(document).on('click', '.account-btn', function(e) {
        // Let default behavior happen (navigate to account page)
        // Could add loading state here if needed
    });

    /**
     * Debounce utility function
     */
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    /**
     * Handle Orientation Change (for mobile devices)
     */
    $(window).on('orientationchange', function() {
        setTimeout(function() {
            updateResponsiveDisplay();
            positionMobileCartIcon();
        }, 100);
    });

    /**
     * Add touch support for mobile menu
     */
    if ('ontouchstart' in window) {
        let touchStartY = 0;
        
        $('#mobile-menu').on('touchstart', function(e) {
            touchStartY = e.originalEvent.touches[0].clientY;
        });
        
        $('#mobile-menu').on('touchmove', function(e) {
            const touchY = e.originalEvent.touches[0].clientY;
            const deltaY = touchStartY - touchY;
            
            // If scrolling up at the top, prevent body scroll
            if (deltaY < 0 && $(this).scrollTop() === 0) {
                e.preventDefault();
            }
        });
    }

    /**
     * AJAX Cart Management for ERPNext Integration
     */
    window.VinaPetCart = {
        
        /**
         * Add item to cart
         */
        addToCart: function(productId, quantity = 1) {
            if (!isUserLoggedIn()) {
                openAuthModal();
                return Promise.reject('User not logged in');
            }
            
            return $.ajax({
                url: vinapet_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'vinapet_add_to_cart',
                    product_id: productId,
                    quantity: quantity,
                    nonce: vinapet_ajax.nonce
                },
                beforeSend: function() {
                    // Show loading state
                    $('.cart-btn, .mobile-cart-icon').addClass('loading');
                }
            }).done(function(response) {
                if (response.success) {
                    updateCartCount(response.data.cart_count);
                    
                    // Show success message
                    showCartNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
                } else {
                    showCartNotification(response.data.message || 'Có lỗi xảy ra', 'error');
                }
            }).fail(function() {
                showCartNotification('Không thể thêm sản phẩm vào giỏ hàng', 'error');
            }).always(function() {
                $('.cart-btn, .mobile-cart-icon').removeClass('loading');
            });
        },
        
        /**
         * Remove item from cart
         */
        removeFromCart: function(itemId) {
            return $.ajax({
                url: vinapet_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'vinapet_remove_from_cart',
                    item_id: itemId,
                    nonce: vinapet_ajax.nonce
                }
            }).done(function(response) {
                if (response.success) {
                    updateCartCount(response.data.cart_count);
                }
            });
        },
        
        /**
         * Update cart item quantity
         */
        updateQuantity: function(itemId, quantity) {
            return $.ajax({
                url: vinapet_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'vinapet_update_cart_quantity',
                    item_id: itemId,
                    quantity: quantity,
                    nonce: vinapet_ajax.nonce
                }
            }).done(function(response) {
                if (response.success) {
                    updateCartCount(response.data.cart_count);
                }
            });
        }
    };
    
    /**
     * Show cart notification
     */
    function showCartNotification(message, type = 'info') {
        // Simple notification system
        const notification = $(`
            <div class="cart-notification ${type}">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        // Animate in
        setTimeout(() => notification.addClass('show'), 100);
        
        // Auto remove
        setTimeout(() => {
            notification.removeClass('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Export for use in other scripts
    window.VinaPetMobileHeader = {
        closeMobileMenu: closeMobileMenu,
        updateCartCount: updateCartCount,
        isUserLoggedIn: isUserLoggedIn
    };

})(jQuery);