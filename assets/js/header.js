(function ($) {
  $(document).ready(function () {
    // =============================================================================
    // STICKY HEADER ON SCROLL
    // =============================================================================

    const header = $(".site-header");
    const headerHeight = header.outerHeight();
    let lastScrollTop = 0;
    let isScrolling = false;

    $(window).on("scroll", function () {
      if (!isScrolling) {
        window.requestAnimationFrame(function () {
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
        header.addClass("scrolled");
      } else {
        header.removeClass("scrolled");
      }

      lastScrollTop = scrollTop;
    }

    // =============================================================================
    // MOBILE MENU WITH HAMBURGER ANIMATION - THAY THẾ CODE CŨ
    // =============================================================================

    const mobileMenu = $("#mobile-menu");
    const mobileMenuToggle = $("#mobile-menu-toggle"); // Sử dụng ID cũ nhưng class mới

    // Open mobile menu - Cập nhật từ code cũ
    if (mobileMenuToggle.length) {
      mobileMenuToggle.on("click", function (e) {
        e.preventDefault();
        toggleMobileMenu();
      });
    }

    /**
     * Toggle Mobile Menu - Cập nhật với hamburger animation
     */
    function toggleMobileMenu() {
      if (mobileMenuToggle.hasClass("active")) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    }

    /**
     * Open Mobile Menu
     */
    function openMobileMenu() {
      mobileMenuToggle.addClass("active").attr("aria-expanded", "true");
      mobileMenu.addClass("active").attr("aria-hidden", "false");
      $("body").addClass("mobile-menu-open");
    }

    /**
     * Close Mobile Menu
     */
    function closeMobileMenu() {
      mobileMenuToggle.removeClass("active").attr("aria-expanded", "false");
      mobileMenu.removeClass("active").attr("aria-hidden", "true");
      $("body").removeClass("mobile-menu-open");
    }

    // Close mobile menu when clicking outside
    $(document).on("click", function (e) {
      if (
        mobileMenuToggle.hasClass("active") &&
        !$(e.target).closest(".mobile-menu").length &&
        !$(e.target).closest(".hamburger-btn").length
      ) {
        closeMobileMenu();
      }
    });

    // Close mobile menu on ESC key
    $(document).on("keydown", function (e) {
      if (e.keyCode === 27 && mobileMenuToggle.hasClass("active")) {
        closeMobileMenu();
      }
    });

    // =============================================================================
    // MEGA MENU FUNCTIONALITY
    // =============================================================================

    // Add mega menu support for menu items with children
    $(".nav-list .menu-item-has-children").each(function () {
      const $menuItem = $(this);
      const $megaMenu = $menuItem.find(".mega-menu");

      if ($megaMenu.length) {
        let hoverTimeout;

        $menuItem.on("mouseenter", function () {
          clearTimeout(hoverTimeout);
          $(".nav-list .mega-menu").removeClass("active");
          $megaMenu.addClass("active");
        });

        $menuItem.on("mouseleave", function () {
          hoverTimeout = setTimeout(() => {
            $megaMenu.removeClass("active");
          }, 150);
        });
      }
    });

    // Close mega menu when clicking outside
    $(document).on("click", function (e) {
      if (!$(e.target).closest(".nav-list").length) {
        $(".mega-menu").removeClass("active");
      }
    });

    // =============================================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // =============================================================================

    $('a[href^="#"]').on("click", function (e) {
      const target = $(this.getAttribute("href"));

      if (target.length) {
        e.preventDefault();
        const offset = headerHeight + 20;

        $("html, body").animate(
          {
            scrollTop: target.offset().top - offset,
          },
          800
        );
      }
    });

    // =============================================================================
    // KEYBOARD NAVIGATION
    // =============================================================================

    // Keyboard navigation for menu
    $(".nav-list a, .search-btn, .cart-btn").on("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        $(this).click();
      }
    });

    // Tab navigation for mega menu
    $(".nav-list .menu-item-has-children a").on("focus", function () {
      $(this).closest(".menu-item-has-children").addClass("focused");
    });

    $(".nav-list .menu-item-has-children a").on("blur", function () {
      setTimeout(() => {
        if (!$(this).closest(".menu-item-has-children").find(":focus").length) {
          $(this).closest(".menu-item-has-children").removeClass("focused");
        }
      }, 100);
    });

    // =============================================================================
    // PERFORMANCE OPTIMIZATIONS
    // =============================================================================

    // Debounce resize handler
    let resizeTimeout;
    $(window).on("resize", function () {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        handleResize();
      }, 150);
    });

    function handleResize() {
      const windowWidth = $(window).width();

      // Close mobile menu on resize to desktop
      if (windowWidth > 768 && mobileMenu.hasClass("active")) {
        closeMobileMenu();
      }
    }
    // Initialize mega menu positioning
    $(".mega-menu").each(function () {
      const $menu = $(this);
      const $parent = $menu.closest(".menu-item-has-children");

      if ($parent.length) {
        // Center mega menu under parent item
        const parentWidth = $parent.outerWidth();
        const menuWidth = $menu.outerWidth();
        const offset = (menuWidth - parentWidth) / 2;

        $menu.css({
          "margin-left": -offset + "px",
        });
      }
    });
  });

  // =============================================================================
  // WINDOW LOAD EVENT HANDLERS
  // =============================================================================

  $(window).on("load", function () {
    // Final adjustments after all content loaded
    $(".site-header").addClass("loaded");

    // Adjust body padding for exact header height
    const exactHeaderHeight = $(".site-header").outerHeight();
    if (exactHeaderHeight > 0) {
      $("body").css("padding-top", exactHeaderHeight + "px");
    }
  });

  /**
   * Header Dropdown Functionality
   * Xử lý dropdown menu cho cả desktop và mobile
   */

  document.addEventListener("DOMContentLoaded", function () {
    initDropdownMenu();
    console.log("Dropdown menu initialized");
  });

  function initDropdownMenu() {
    // Mobile dropdown toggle
    const mobileDropdownToggles = document.querySelectorAll(
      ".mobile-dropdown-toggle"
    );

    mobileDropdownToggles.forEach((toggle) => {
      toggle.addEventListener("click", function (e) {
        e.preventDefault(); // Ngăn hành vi mặc định

        const parent = this.closest(".mobile-menu-item-has-children");
        const submenu = parent.querySelector(".mobile-submenu");

        if (submenu) {
          toggleMobileSubmenu(parent);
        }
      });
    });

    function toggleMobileSubmenu(parent) {
      const isActive = parent.classList.contains("active");

      // Đóng tất cả submenu khác
      const allMobileParents = document.querySelectorAll(
        ".mobile-menu-item-has-children"
      );
      allMobileParents.forEach((item) => {
        if (item !== parent) {
          item.classList.remove("active");
        }
      });

      // Toggle submenu hiện tại
      parent.classList.toggle("active", !isActive);
    }
  }

  function toggleMobileSubmenu(parent) {
    const isActive = parent.classList.contains("active");

    // Đóng tất cả submenu khác
    const allMobileParents = document.querySelectorAll(
      ".mobile-menu-item-has-children"
    );
    allMobileParents.forEach((item) => {
      if (item !== parent) {
        item.classList.remove("active");
      }
    });

    // Toggle submenu hiện tại
    if (isActive) {
      parent.classList.remove("active");
    } else {
      parent.classList.add("active");
    }
  }

  function handleTouchEvents() {
    // Xử lý touch events cho mobile để cải thiện UX
    const dropdownParents = document.querySelectorAll(
      ".menu-item-has-children"
    );

    dropdownParents.forEach((parent) => {
      let touchStartY = 0;

      parent.addEventListener("touchstart", function (e) {
        touchStartY = e.touches[0].clientY;
      });

      parent.addEventListener("touchend", function (e) {
        const touchEndY = e.changedTouches[0].clientY;
        const touchDiff = touchStartY - touchEndY;

        // Nếu swipe xuống nhẹ, mở dropdown
        if (touchDiff < -10) {
          const dropdown = this.querySelector(".dropdown-menu");
          if (dropdown && window.innerWidth > 768) {
            dropdown.style.opacity = "1";
            dropdown.style.visibility = "visible";
            dropdown.style.transform = "translateY(0)";
          }
        }
      });
    });
  }

  // Utility function để check mobile
  function isMobile() {
    return window.innerWidth <= 768;
  }

  // Xử lý resize window
  window.addEventListener("resize", function () {
    // Đóng tất cả dropdown khi resize
    const allDropdowns = document.querySelectorAll(".dropdown-menu");
    const allMobileParents = document.querySelectorAll(
      ".mobile-menu-item-has-children"
    );

    allDropdowns.forEach((dropdown) => {
      dropdown.style.opacity = "0";
      dropdown.style.visibility = "hidden";
      dropdown.style.transform = "translateY(-10px)";
    });

    allMobileParents.forEach((parent) => {
      parent.classList.remove("active");
    });
  });
})(jQuery);

// =============================================================================
// ACCOUNT PAGE FUNCTIONALITY
// =============================================================================

// Account button click handler
$(".account-btn").on("click", function (e) {
  // Check if account URL is available
  if (typeof vinapet_account_url !== "undefined") {
    window.location.href = vinapet_account_url;
  } else {
    // Fallback URL
    window.location.href = "/tai-khoan/";
  }
});

// Update user actions for logged in users
window.updateUserActions = function () {
  // This function can be called after login/logout to update the header
  location.reload(); // Simple refresh for now
};

// Login success callback - redirect to account page
window.onLoginSuccess = function (response) {
  if (response && response.success) {
    showNotification("Đăng nhập thành công!", "success");

    // Small delay before redirect
    setTimeout(() => {
      if (typeof vinapet_account_url !== "undefined") {
        window.location.href = vinapet_account_url;
      } else {
        window.location.href = "/tai-khoan/";
      }
    }, 1000);
  }
};

// Notification helper function (if not already exists)
if (typeof showNotification === "undefined") {
  window.showNotification = function (message, type = "info") {
    // Create notification element
    const notification = $(`
            <div class="notification notification-${type}">
                <span class="notification-text">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `);

    // Add to page
    $("body").append(notification);

    // Show with animation
    setTimeout(() => {
      notification.addClass("show");
    }, 100);

    // Auto hide after 3 seconds
    setTimeout(() => {
      notification.removeClass("show");
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, 3000);

    // Manual close
    notification.find(".notification-close").on("click", () => {
      notification.removeClass("show");
      setTimeout(() => {
        notification.remove();
      }, 300);
    });
  };
}
