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
                    <?php get_template_part('template-parts/header/user-actions'); ?>
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
        echo '<li><a href="' . home_url('/san-pham') . '">Sản phẩm</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu') . '">Giới thiệu</a></li>';
        echo '<li><a href="' . home_url('/tin-tuc') . '">Tin tức</a></li>';
        echo '<li><a href="' . home_url('/faq') . '">FAQ & Hướng dẫn</a></li>';
        echo '<li><a href="' . home_url('/lien-he') . '">Liên hệ</a></li>';
        echo '</ul>';
    }

    function vinapet_fallback_mobile_menu()
    {
        echo '<ul class="mobile-nav-list">';
        echo '<li><a href="' . home_url('/san-pham') . '">Sản phẩm</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu') . '">Giới thiệu</a></li>';
        echo '<li><a href="' . home_url('/tin-tuc') . '">Tin tức</a></li>';
        echo '<li><a href="' . home_url('/faq') . '">FAQ & Hướng dẫn</a></li>';
        echo '<li><a href="' . home_url('/lien-he') . '">Liên hệ</a></li>';
        echo '</ul>';
    }

    /**
     * Custom Walker for Advanced Mega Menu support
     */
    class VinaPet_Walker_Nav_Menu extends Walker_Nav_Menu
    {
        // Lưu trữ menu items để xử lý mega menu
        private $mega_menu_items = array();

        function start_lvl(&$output, $depth = 0, $args = null)
        {
            $indent = str_repeat("\t", $depth);

            if ($depth === 0) {
                // Mega menu cho cấp đầu tiên
                $output .= "\n$indent<div class=\"mega-menu\">\n";
            } else {
                // Sub menu bình thường cho cấp sâu hơn
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

            // Kiểm tra xem item này có children không
            $has_children = in_array('menu-item-has-children', $classes);

            // Thêm class cho mega menu ở level 0 nếu có children
            if ($depth === 0 && $has_children) {
                $classes[] = 'has-mega-menu';
            }

            // Thêm class đặc biệt cho các menu items có nhiều sub items (>= 4 items)
            if ($depth === 0 && $has_children) {
                $sub_items_count = $this->count_sub_items($item->ID, $args);
                if ($sub_items_count >= 4) {
                    $classes[] = 'mega-menu-large';
                } elseif ($sub_items_count >= 2) {
                    $classes[] = 'mega-menu-medium';
                } else {
                    $classes[] = 'mega-menu-small';
                }
            }

            $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
            $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

            $id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args);
            $id = $id ? ' id="' . esc_attr($id) . '"' : '';

            // Xử lý link và attributes
            $attributes = !empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) . '"' : '';
            $attributes .= !empty($item->target) ? ' target="' . esc_attr($item->target) . '"' : '';
            $attributes .= !empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) . '"' : '';
            $attributes .= !empty($item->url) ? ' href="' . esc_attr($item->url) . '"' : '';

            if ($depth === 0) {
                // Menu items cấp đầu tiên
                $item_output = isset($args->before) ? $args->before : '';

                if ($has_children) {
                    // Menu item có children - thêm dropdown icon
                    $item_output .= '<a' . $attributes . '>';
                    $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
                    $item_output .= '<svg class="dropdown-icon" width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                    $item_output .= '</a>';
                } else {
                    // Menu item không có children
                    $item_output .= '<a' . $attributes . '>';
                    $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
                    $item_output .= '</a>';
                }

                $item_output .= isset($args->after) ? $args->after : '';
            } else {
                // Menu items trong mega menu hoặc sub menu
                $item_output = isset($args->before) ? $args->before : '';
                $item_output .= '<a' . $attributes . '>';
                $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
                $item_output .= '</a>';
                $item_output .= isset($args->after) ? $args->after : '';
            }

            $output .= $indent . '<li' . $id . $class_names . '>';
            $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
        }

        function end_el(&$output, $item, $depth = 0, $args = null)
        {
            $output .= "</li>\n";
        }

        /**
         * Đếm số lượng sub items của một menu item
         */
        private function count_sub_items($parent_id, $args)
        {
            if (!isset($args->menu)) {
                return 0;
            }

            $menu_items = wp_get_nav_menu_items($args->menu);
            $count = 0;

            if ($menu_items) {
                foreach ($menu_items as $menu_item) {
                    if ($menu_item->menu_item_parent == $parent_id) {
                        $count++;
                    }
                }
            }

            return $count;
        }
    }
    ?>