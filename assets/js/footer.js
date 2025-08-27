/**
 * VinaPet Footer JavaScript
 * @package VinaPet
 */

(function($) {
    'use strict';

    const VinapetFooter = {
        
        init: function() {
            this.initLazyLoad();
            this.initSmoothScroll();
            this.initContactLinks();
            this.initFacebookPlugin();
            this.initAnalytics();
            this.handleResponsive();
            this.enhanceAccessibility();
        },

        // Lazy loading
        initLazyLoad: function() {
            if ('IntersectionObserver' in window) {
                const footerImages = document.querySelectorAll('.vinapet-footer img[data-src]');
                
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                footerImages.forEach(img => imageObserver.observe(img));
            }
        },

        // Smooth scroll
        initSmoothScroll: function() {
            $('.footer-links a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });
        },

        // Contact tracking
        initContactLinks: function() {
            // Phone tracking
            $('.contact-item a[href^="tel:"]').on('click', function() {
                const phoneNumber = $(this).attr('href').replace('tel:', '');
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'Footer',
                        event_label: 'Phone Call',
                        value: phoneNumber
                    });
                }
                
                VinapetFooter.trackERPNextEvent('phone_call', {
                    phone_number: phoneNumber,
                    timestamp: new Date().toISOString()
                });
            });

            // Email tracking
            $('.contact-item a[href^="mailto:"]').on('click', function() {
                const email = $(this).attr('href').replace('mailto:', '');
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'Footer',
                        event_label: 'Email',
                        value: email
                    });
                }
                
                VinapetFooter.trackERPNextEvent('email_click', {
                    email: email,
                    timestamp: new Date().toISOString()
                });
            });

            // Social tracking
            $('.social-content a, .fb-page').on('click', function() {
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'Footer',
                        event_label: 'Social Media',
                        value: 'Facebook'
                    });
                }
                
                VinapetFooter.trackERPNextEvent('social_click', {
                    platform: 'Facebook',
                    timestamp: new Date().toISOString()
                });
            });
        },

        // Facebook plugin
        initFacebookPlugin: function() {
            const fbContainer = document.querySelector('.fb-page');
            if (!fbContainer) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        VinapetFooter.loadFacebookSDK();
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '100px'
            });

            observer.observe(fbContainer);
        },

        // Load FB SDK
        loadFacebookSDK: function() {
            if (document.getElementById('facebook-jssdk')) return;

            const js = document.createElement('script');
            js.id = 'facebook-jssdk';
            js.src = 'https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v18.0';
            js.async = true;
            js.defer = true;
            
            const firstScript = document.getElementsByTagName('script')[0];
            firstScript.parentNode.insertBefore(js, firstScript);
        },

        // Analytics
        initAnalytics: function() {
            const footer = document.querySelector('.vinapet-footer');
            if (!footer) return;

            const footerObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'scroll', {
                                event_category: 'Footer',
                                event_label: 'Footer Viewed'
                            });
                        }
                        
                        VinapetFooter.trackERPNextEvent('footer_viewed', {
                            timestamp: new Date().toISOString(),
                            page_url: window.location.href
                        });
                        
                        footerObserver.unobserve(footer);
                    }
                });
            });

            footerObserver.observe(footer);
        },

        // ERPNext tracking
        trackERPNextEvent: function(eventType, data) {
            if (typeof vinapet_ajax_object === 'undefined') return;

            $.ajax({
                url: vinapet_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'vinapet_track_footer_event',
                    nonce: vinapet_ajax_object.nonce,
                    event_type: eventType,
                    event_data: JSON.stringify(data)
                },
                success: function(response) {
                    // Success
                },
                error: function() {
                    // Handle error
                }
            });
        },

        // Responsive
        handleResponsive: function() {
            const footer = $('.vinapet-footer');
            const windowWidth = $(window).width();
            
            if (windowWidth <= 768) {
                footer.addClass('footer-mobile');
                
                // Mobile collapse
                $('.footer-title').on('click', function() {
                    if (windowWidth <= 480) {
                        $(this).next('.footer-links').slideToggle();
                        $(this).toggleClass('expanded');
                    }
                });
            } else {
                footer.removeClass('footer-mobile');
                $('.footer-links').show();
            }

            // Debounce resize
            let resizeTimeout;
            $(window).on('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    VinapetFooter.handleResponsive();
                }, 250);
            });
        },

        // Accessibility
        enhanceAccessibility: function() {
            // ARIA labels
            $('.footer-links a').each(function() {
                if (!$(this).attr('aria-label')) {
                    $(this).attr('aria-label', $(this).text());
                }
            });

            // Keyboard navigation
            $('.footer-links a').on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    $(this)[0].click();
                }
            });

            // Skip link
            if (!$('#skip-to-footer').length) {
                $('body').prepend('<a href="#colophon" id="skip-to-footer" class="screen-reader-text">Chuyển đến footer</a>');
            }
        }
    };

    // Initialize
    $(document).ready(function() {
        VinapetFooter.init();
    });

    // Window load
    $(window).on('load', function() {
        setTimeout(() => {
            VinapetFooter.initFacebookPlugin();
        }, 1000);
    });

    // Export
    window.VinapetFooter = VinapetFooter;

})(jQuery);