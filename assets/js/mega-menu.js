/**
 * VinaPet Advanced Mega Menu JavaScript
 * Xử lý toàn diện cho mega menu với animation và responsive
 */

class VinaPetMegaMenu {
    constructor() {
        this.megaMenuItems = document.querySelectorAll('.has-mega-menu');
        this.mobileBreakpoint = 768;
        this.hoverTimeout = null;
        this.init();
    }

    init() {
        this.setupMegaMenus();
        this.setupMobileHandling();
        this.setupKeyboardNavigation();
        this.setupResizeHandler();
        this.setupClickOutside();
    }

    /**
     * Thiết lập mega menu cho desktop
     */
    setupMegaMenus() {
        this.megaMenuItems.forEach(item => {
            const megaMenu = item.querySelector('.mega-menu');
            if (!megaMenu) return;

            // Xử lý hover events
            item.addEventListener('mouseenter', () => {
                this.showMegaMenu(megaMenu, item);
            });

            item.addEventListener('mouseleave', () => {
                this.hideMegaMenu(megaMenu);
            });

            // Xử lý focus events cho accessibility
            const link = item.querySelector('a');
            if (link) {
                link.addEventListener('focus', () => {
                    this.showMegaMenu(megaMenu, item);
                });
            }

            // Auto-hide khi click vào menu item
            const menuItems = megaMenu.querySelectorAll('.menu-item a');
            menuItems.forEach(menuItem => {
                menuItem.addEventListener('click', () => {
                    this.hideMegaMenu(megaMenu);
                });
            });
        });
    }

    /**
     * Hiển thị mega menu với animation
     */
    showMegaMenu(megaMenu, parentItem) {
        if (window.innerWidth <= this.mobileBreakpoint) return;

        clearTimeout(this.hoverTimeout);
        
        // Hide other mega menus
        this.hideAllMegaMenus();
        
        // Position mega menu
        this.positionMegaMenu(megaMenu, parentItem);
        
        // Show with animation
        megaMenu.style.display = 'grid';
        requestAnimationFrame(() => {
            megaMenu.style.opacity = '1';
            megaMenu.style.visibility = 'visible';
            megaMenu.style.marginTop = '5px';
        });

        // Add active class to parent
        parentItem.classList.add('mega-menu-active');
    }

    /**
     * Ẩn mega menu với animation
     */
    hideMegaMenu(megaMenu) {
        if (window.innerWidth <= this.mobileBreakpoint) return;

        this.hoverTimeout = setTimeout(() => {
            megaMenu.style.opacity = '0';
            megaMenu.style.visibility = 'hidden';
            megaMenu.style.marginTop = '15px';
            
            setTimeout(() => {
                if (megaMenu.style.opacity === '0') {
                    megaMenu.style.display = 'none';
                }
            }, 300);

            // Remove active class from parent
            const parentItem = megaMenu.closest('.has-mega-menu');
            if (parentItem) {
                parentItem.classList.remove('mega-menu-active');
            }
        }, 100);
    }

    /**
     * Ẩn tất cả mega menu
     */
    hideAllMegaMenus() {
        this.megaMenuItems.forEach(item => {
            const megaMenu = item.querySelector('.mega-menu');
            if (megaMenu) {
                this.hideMegaMenu(megaMenu);
            }
        });
    }

    /**
     * Định vị mega menu để không bị tràn khỏi viewport
     */
    positionMegaMenu(megaMenu, parentItem) {
        const rect = parentItem.getBoundingClientRect();
        const menuRect = megaMenu.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        
        // Reset positioning
        megaMenu.style.left = '50%';
        megaMenu.style.right = 'auto';
        megaMenu.style.transform = 'translateX(-50%)';

        // Check if menu overflows right edge
        const menuLeft = rect.left + rect.width / 2 - menuRect.width / 2;
        const menuRight = menuLeft + menuRect.width;

        if (menuRight > viewportWidth - 20) {
            // Align to right edge
            megaMenu.style.left = 'auto';
            megaMenu.style.right = '0';
            megaMenu.style.transform = 'none';
        } else if (menuLeft < 20) {
            // Align to left edge
            megaMenu.style.left = '0';
            megaMenu.style.transform = 'none';
        }
    }

    /**
     * Xử lý mega menu trên mobile
     */
    setupMobileHandling() {
        // Toggle mega menu trên mobile
        this.megaMenuItems.forEach(item => {
            const link = item.querySelector('a');
            const megaMenu = item.querySelector('.mega-menu');
            
            if (link && megaMenu) {
                link.addEventListener('click', (e) => {
                    if (window.innerWidth <= this.mobileBreakpoint) {
                        e.preventDefault();
                        
                        const isOpen = item.classList.contains('mobile-mega-open');
                        
                        // Close all other mega menus
                        this.megaMenuItems.forEach(otherItem => {
                            otherItem.classList.remove('mobile-mega-open');
                        });
                        
                        // Toggle current mega menu
                        if (!isOpen) {
                            item.classList.add('mobile-mega-open');
                            this.showMobileMegaMenu(megaMenu);
                        }
                    }
                });
            }
        });
    }

    /**
     * Hiển thị mega menu trên mobile
     */
    showMobileMegaMenu(megaMenu) {
        megaMenu.style.display = 'flex';
        megaMenu.style.opacity = '1';
        megaMenu.style.visibility = 'visible';
        
        // Smooth scroll to menu
        megaMenu.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
        });
    }

    /**
     * Xử lý keyboard navigation
     */
    setupKeyboardNavigation() {
        this.megaMenuItems.forEach(item => {
            const menuItems = item.querySelectorAll('.mega-menu .menu-item a');
            
            menuItems.forEach((menuItem, index) => {
                menuItem.addEventListener('keydown', (e) => {
                    switch (e.key) {
                        case 'ArrowDown':
                        case 'ArrowRight':
                            e.preventDefault();
                            const nextItem = menuItems[index + 1];
                            if (nextItem) nextItem.focus();
                            break;
                            
                        case 'ArrowUp':
                        case 'ArrowLeft':
                            e.preventDefault();
                            const prevItem = menuItems[index - 1];
                            if (prevItem) prevItem.focus();
                            break;
                            
                        case 'Escape':
                            e.preventDefault();
                            const parentLink = item.querySelector('a');
                            if (parentLink) parentLink.focus();
                            this.hideAllMegaMenus();
                            break;
                    }
                });
            });
        });
    }

    /**
     * Xử lý resize window
     */
    setupResizeHandler() {
        let resizeTimeout;
        
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                if (window.innerWidth > this.mobileBreakpoint) {
                    // Cleanup mobile states
                    this.megaMenuItems.forEach(item => {
                        item.classList.remove('mobile-mega-open');
                    });
                }
                this.hideAllMegaMenus();
            }, 250);
        });
    }

    /**
     * Click outside để đóng mega menu
     */
    setupClickOutside() {
        document.addEventListener('click', (e) => {
            let clickedInside = false;
            
            this.megaMenuItems.forEach(item => {
                if (item.contains(e.target)) {
                    clickedInside = true;
                }
            });
            
            if (!clickedInside) {
                this.hideAllMegaMenus();
                // Remove mobile states
                this.megaMenuItems.forEach(item => {
                    item.classList.remove('mobile-mega-open');
                });
            }
        });
    }
}

// Initialize khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    new VinaPetMegaMenu();
});

// Export cho sử dụng global
window.VinaPetMegaMenu = VinaPetMegaMenu;