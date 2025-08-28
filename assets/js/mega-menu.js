/**
 * VinaPet Simple Mega Menu JavaScript
 * Version đơn giản, dễ hiểu
 */

jQuery(document).ready(function($) {
    
    // =============================================================================
    // DROPDOWN MENU HANDLING
    // =============================================================================
    
    // Desktop dropdown
    $('.has-dropdown').hover(
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeIn(300);
        },
        function() {
            $(this).find('.dropdown-menu').stop(true, true).fadeOut(200);
        }
    );
    
    // =============================================================================
    // MOBILE MENU HANDLING  
    // =============================================================================
    
    // Mobile menu toggle
    $('.mobile-menu-toggle').click(function() {
        $('.mobile-menu').addClass('active');
        $('body').addClass('mobile-menu-open');
    });
    
    // Close mobile menu
    $('.mobile-menu-close, .mobile-menu-overlay').click(function() {
        $('.mobile-menu').removeClass('active');
        $('body').removeClass('mobile-menu-open');
    });
    
    // Close on menu item click
    $('.mobile-nav-list a').click(function() {
        $('.mobile-menu').removeClass('active');
        $('body').removeClass('mobile-menu-open');
    });
    
    // =============================================================================
    // ACCESSIBILITY
    // =============================================================================
    
    // Keyboard navigation
    $('.has-dropdown > a').keydown(function(e) {
        if (e.keyCode === 13) { // Enter key
            e.preventDefault();
            $(this).parent().toggleClass('keyboard-open');
        }
    });
    
    // Close dropdown on Escape
    $(document).keydown(function(e) {
        if (e.keyCode === 27) { // Escape key
            $('.dropdown-menu').fadeOut(200);
            $('.keyboard-open').removeClass('keyboard-open');
        }
    });
    
    // Click outside to close
    $(document).click(function(e) {
        if (!$(e.target).closest('.has-dropdown').length) {
            $('.dropdown-menu').fadeOut(200);
            $('.keyboard-open').removeClass('keyboard-open');
        }
    });
});

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

// Public function để refresh menu (sau AJAX calls)
window.refreshMenu = function() {
    // Simple refresh - reload dropdown handlers
    jQuery('.dropdown-menu').hide();
    console.log('Menu refreshed');
};