<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right');
            bloginfo('name'); ?></title>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <!-- Header -->
    <header class="site-header vinapet-header" id="site-header">
        <nav class="main-navigation">
            <div class="nav-container">
                <!-- Logo Section (20%) -->
                <div class="site-branding">
                    <a href="<?php echo home_url(); ?>" class="site-logo">
                        <?php
                        // Sử dụng custom logo từ WordPress Customizer
                        if (has_custom_logo()) {
                            the_custom_logo();
                        } else {
                            // Fallback về logo mặc định
                            $logo_path = get_template_directory_uri() . '/assets/images/L-min.png';
                            echo '<img src="' . esc_url($logo_path) . '" alt="' . get_bloginfo('name') . '" class="header-logo-img">';
                        }
                        ?>
                    </a>
                </div>

                <!-- Navigation Menu (Center - Flexible) -->
                <div class="nav-menu">
                    <?php
                    // Hiển thị menu chính từ WordPress
                    if (has_nav_menu('primary')) {
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'menu_class' => 'nav-list',
                            'container' => false,
                            'walker' => class_exists('VinaPet_Walker_Nav_Menu') ? new VinaPet_Walker_Nav_Menu() : '',
                        ));
                    } else {
                        // Fallback menu khi chưa thiết lập menu
                        vinapet_fallback_menu();
                    }
                    ?>
                </div>

                <!-- Header Actions (17%) -->
                <div class="nav-actions">
                    <!-- Login Button -->
                    <?php if (is_user_logged_in()):
                        $current_user = wp_get_current_user();
                    ?>
                        <div class="user-menu">
                            <button class="user-menu-btn" onclick="toggleUserMenu()">
                                <span class="user-name"><?php echo esc_html($current_user->display_name); ?></span>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" />
                                </svg>
                            </button>
                            <div class="user-dropdown" id="userDropdown">
                                <a href="<?php echo home_url('/tai-khoan'); ?>">Tài khoản</a>
                                <a href="<?php echo home_url('/don-hang'); ?>">Đơn hàng</a>
                                <a href="<?php echo wp_logout_url(home_url()); ?>">Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="#" class="login-btn" onclick="VinaPetAuth.open()">
                            Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
        </nav>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobile-menu">
            <div class="mobile-menu-header">
                <div class="mobile-logo">
                    <a href="<?php echo home_url(); ?>">
                        <?php
                        $custom_logo_id = get_theme_mod('custom_logo');
                        if ($custom_logo_id) {
                            $logo_url = wp_get_attachment_image_src($custom_logo_id, 'full')[0];
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . get_bloginfo('name') . '">';
                        } else {
                            bloginfo('name');
                        }
                        ?>
                    </a>
                </div>
                <button class="mobile-menu-close" id="mobile-menu-close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <div class="mobile-menu-content">
                <?php
                // Menu mobile có thể chỉnh sửa từ WordPress Admin
                if (has_nav_menu('primary')) {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_class' => 'mobile-nav-list',
                        'container' => false,
                    ));
                } else {
                    // Fallback menu cho mobile
                    vinapet_fallback_mobile_menu();
                }
                ?>

                <div class="mobile-menu-actions">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo home_url('/tai-khoan'); ?>" class="mobile-login-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" />
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                            </svg>
                            Tài khoản
                        </a>
                    <?php else: ?>
                        <a href="#" class="mobile-login-btn" onclick="VinaPetAuth.open()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" />
                                <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" />
                            </svg>
                            Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
    </header>

    <?php
    /**
     * Fallback menu khi chưa có menu được thiết lập
     */
    function vinapet_fallback_menu()
    {
        echo '<ul class="nav-list">';
        echo '<li><a href="' . home_url() . '">Trang chủ</a></li>';
        echo '<li><a href="' . home_url('/san-pham') . '">Sản phẩm</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu') . '">Giới thiệu</a></li>';
        echo '<li><a href="' . home_url('/lien-he') . '">Liên hệ</a></li>';
        echo '</ul>';
    }

    function vinapet_fallback_mobile_menu()
    {
        echo '<ul class="mobile-nav-list">';
        echo '<li><a href="' . home_url() . '">Trang chủ</a></li>';
        echo '<li><a href="' . home_url('/san-pham') . '">Sản phẩm</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu') . '">Giới thiệu</a></li>';
        echo '<li><a href="' . home_url('/lien-he') . '">Liên hệ</a></li>';
        echo '</ul>';
    }

    /**
     * Custom Walker for Mega Menu support
     */
    class VinaPet_Walker_Nav_Menu extends Walker_Nav_Menu
    {

        function start_lvl(&$output, $depth = 0, $args = null)
        {
            $indent = str_repeat("\t", $depth);

            if ($depth === 0) {
                // Mega menu cho cấp đầu tiên
                $output .= "\n$indent<div class=\"mega-menu\">\n";
            } else {
                // Sub menu bình thường
                $output .= "\n$indent<ul class=\"sub-menu\">\n";
            }
        }

        function end_lvl(&$output, $depth = 0, $args = null)
        {
            $indent = str_repeat("\t", $depth);

            if ($depth === 0) {
                $output .= "$indent</div>\n";
            } else {
                $output .= "$indent</ul>\n";
            }
        }

        function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
        {
            $indent = ($depth) ? str_repeat("\t", $depth) : '';

            $classes = empty($item->classes) ? array() : (array) $item->classes;
            $classes[] = 'menu-item-' . $item->ID;

            // Thêm class cho mega menu
            if ($depth === 0 && in_array('menu-item-has-children', $classes)) {
                $classes[] = 'has-mega-menu';
            }

            $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
            $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

            $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
            $id = $id ? ' id="' . esc_attr($id) . '"' : '';

            if ($depth === 0 && in_array('has-mega-menu', $classes)) {
                // Mega menu item
                $output .= $indent . '<li' . $id . $class_names . '>';
                $output .= '<a href="' . esc_attr($item->url) . '">';
                $output .= esc_html($item->title);
                $output .= '<svg class="dropdown-icon" width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                $output .= '</a>';
            } else {
                // Menu item bình thường
                $output .= $indent . '<li' . $id . $class_names . '>';
                $output .= '<a href="' . esc_attr($item->url) . '">' . esc_html($item->title) . '</a>';
            }
        }
    }
    ?>