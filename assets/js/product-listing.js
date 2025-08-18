/**
 * VinaPet Product Listing JavaScript
 * Handles search, sorting, and product interactions
 */

(function($) {
    'use strict';

    class VinaPetProductListing {
        constructor() {
            this.searchInput = $('#product-search');
            this.sortSelect = $('#sort-select');
            this.productsContainer = $('#products-container');
            this.searchTimeout = null;
            this.isLoading = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.initProductCards();
            this.initKeyboardNavigation();
            this.preloadImages();
        }

        bindEvents() {
            // Search functionality with debounce
            this.searchInput.on('input', (e) => {
                this.handleSearch(e);
            });

            // Sort functionality
            this.sortSelect.on('change', (e) => {
                this.handleSort(e);
            });

            // Product card interactions
            $(document).on('click', '.product-card', (e) => {
                this.handleProductClick(e);
            });

            // Keyboard navigation
            $(document).on('keydown', '.product-card', (e) => {
                this.handleProductKeydown(e);
            });

            // Handle browser back/forward
            $(window).on('popstate', () => {
                this.updateFiltersFromURL();
            });

            // Form submission prevention
            $(document).on('keypress', '.search-input', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.handleSearch(e);
                }
            });
        }

        handleSearch(e) {
            const searchValue = $(e.target).val().trim();
            
            // Clear previous timeout
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // Debounce search
            this.searchTimeout = setTimeout(() => {
                this.updateURL({ s: searchValue });
            }, 500);

            // Visual feedback
            this.addSearchingState();
        }

        handleSort(e) {
            const sortValue = $(e.target).val();
            this.updateURL({ sort: sortValue });
        }

        handleProductClick(e) {
            e.preventDefault();
            
            const $card = $(e.currentTarget);
            const url = $card.attr('onclick')?.match(/window\.location\.href='([^']+)'/)?.[1];
            
            if (url) {
                // Add loading state
                this.addCardLoadingState($card);
                
                // Navigate with smooth transition
                setTimeout(() => {
                    window.location.href = url;
                }, 150);
            }
        }

        handleProductKeydown(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                this.handleProductClick(e);
            }
        }

        updateURL(params) {
            if (this.isLoading) return;
            
            const currentParams = new URLSearchParams(window.location.search);
            
            // Update parameters
            Object.keys(params).forEach(key => {
                if (params[key] && params[key] !== 'default') {
                    currentParams.set(key, params[key]);
                } else {
                    currentParams.delete(key);
                }
            });

            // Remove paged parameter when searching/sorting
            if (params.s !== undefined || params.sort !== undefined) {
                currentParams.delete('paged');
            }

            // Build new URL
            const newURL = window.location.pathname + 
                         (currentParams.toString() ? '?' + currentParams.toString() : '');
            
            // Add loading state
            this.setLoadingState(true);
            
            // Navigate to new URL
            window.location.href = newURL;
        }

        updateFiltersFromURL() {
            const params = new URLSearchParams(window.location.search);
            
            // Update search input
            const searchValue = params.get('s') || '';
            this.searchInput.val(searchValue);
            
            // Update sort select
            const sortValue = params.get('sort') || 'default';
            this.sortSelect.val(sortValue);
        }

        initProductCards() {
            const $cards = $('.product-card');
            
            // Add tabindex for keyboard navigation
            $cards.attr('tabindex', '0');
            
            // Add ARIA attributes
            $cards.attr('role', 'button');
            $cards.attr('aria-label', function() {
                const title = $(this).find('.product-title').text();
                const description = $(this).find('.product-description').text();
                return `Xem sản phẩm: ${title}. ${description}`;
            });

            // Stagger animation for initial load
            $cards.each((index, card) => {
                $(card).css({
                    'animation-delay': `${index * 0.1}s`,
                    'animation': 'fadeInUp 0.6s ease forwards'
                });
            });

            // Intersection Observer for lazy loading
            if ('IntersectionObserver' in window) {
                this.initLazyLoading();
            }
        }

        initLazyLoading() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const $card = $(entry.target);
                        this.loadCardImage($card);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            $('.product-card').each((index, card) => {
                observer.observe(card);
            });
        }

        loadCardImage($card) {
            const $image = $card.find('.product-image');
            const imageSrc = $image.css('background-image');
            
            if (imageSrc && imageSrc !== 'none') {
                const img = new Image();
                const url = imageSrc.match(/url\(['"]?([^'")]+)['"]?\)/)?.[1];
                
                if (url) {
                    img.onload = () => {
                        $image.addClass('loaded');
                    };
                    img.src = url;
                }
            }
        }

        initKeyboardNavigation() {
            // Arrow key navigation between product cards
            $(document).on('keydown', '.product-card', (e) => {
                const $cards = $('.product-card');
                const currentIndex = $cards.index(e.target);
                let nextIndex;

                switch(e.which) {
                    case 37: // Left arrow
                        nextIndex = currentIndex > 0 ? currentIndex - 1 : $cards.length - 1;
                        break;
                    case 39: // Right arrow
                        nextIndex = currentIndex < $cards.length - 1 ? currentIndex + 1 : 0;
                        break;
                    case 38: // Up arrow
                        nextIndex = currentIndex - 4; // 4 columns
                        if (nextIndex < 0) nextIndex = currentIndex;
                        break;
                    case 40: // Down arrow
                        nextIndex = currentIndex + 4; // 4 columns
                        if (nextIndex >= $cards.length) nextIndex = currentIndex;
                        break;
                    default:
                        return;
                }

                if (nextIndex !== undefined && nextIndex !== currentIndex) {
                    e.preventDefault();
                    $cards.eq(nextIndex).focus();
                }
            });
        }

        preloadImages() {
            // Preload images for better performance
            const imageUrls = [];
            $('.product-image').each(function() {
                const bgImage = $(this).css('background-image');
                const url = bgImage.match(/url\(['"]?([^'")]+)['"]?\)/)?.[1];
                if (url && imageUrls.indexOf(url) === -1) {
                    imageUrls.push(url);
                }
            });

            imageUrls.forEach(url => {
                const img = new Image();
                img.src = url;
            });
        }

        addSearchingState() {
            this.searchInput.addClass('searching');
            
            setTimeout(() => {
                this.searchInput.removeClass('searching');
            }, 1000);
        }

        addCardLoadingState($card) {
            $card.addClass('loading');
        }

        setLoadingState(loading) {
            this.isLoading = loading;
            
            if (loading) {
                $('body').addClass('page-loading');
                $('.search-input, .sort-dropdown').prop('disabled', true);
            } else {
                $('body').removeClass('page-loading');
                $('.search-input, .sort-dropdown').prop('disabled', false);
            }
        }

        // Utility methods
        debounce(func, wait) {
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

        // Public methods for external access
        refresh() {
            this.updateFiltersFromURL();
            this.initProductCards();
        }

        search(query) {
            this.searchInput.val(query);
            this.updateURL({ s: query });
        }

        sort(sortBy) {
            this.sortSelect.val(sortBy);
            this.updateURL({ sort: sortBy });
        }
    }

    // Animation styles
    const animationCSS = `
        <style>
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .product-card {
                opacity: 0;
            }
            
            .product-card.loading {
                opacity: 0.7;
                pointer-events: none;
            }
            
            .search-input.searching {
                border-color: #2E86AB;
                box-shadow: 0 0 0 3px rgba(46, 134, 171, 0.1);
            }
            
            .product-image.loaded {
                transition: all 0.3s ease;
            }
            
            body.page-loading {
                cursor: wait;
            }
            
            body.page-loading * {
                pointer-events: none;
            }
            
            .product-card:focus {
                outline: 2px solid #2E86AB;
                outline-offset: 2px;
            }
        </style>
    `;

    // Initialize when document is ready
    $(document).ready(function() {
        // Add animation styles
        $('head').append(animationCSS);
        
        // Initialize product listing
        window.VinaPetProductListing = new VinaPetProductListing();
        
        // Expose to global scope for debugging
        if (window.console && typeof window.console.log === 'function') {
            console.log('VinaPet Product Listing initialized');
        }
    });

    // Handle page visibility change
    $(document).on('visibilitychange', function() {
        if (!document.hidden && window.VinaPetProductListing) {
            window.VinaPetProductListing.refresh();
        }
    });

})(jQuery);