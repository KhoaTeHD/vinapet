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
                <!-- Hamburger Button - Thay thế mobile-menu-toggle cũ -->
                <button class="hamburger-btn" id="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

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

                <!-- Header Actions (22%) -->
                <div class="nav-actions">

                    <!-- Login Button -->
                    <?php get_template_part('template-parts/header/user-actions'); ?>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobile-menu">
            <div class="mobile-menu-content">
                <?php
                // Menu mobile đơn giản - chỉ có navigation menu
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

                <!-- Language Switcher cho Mobile - Ở DƯỚI CÙNG -->
                <div class="mobile-language-switcher">
                    <?php echo do_shortcode('[gtranslate]'); ?>
                </div>
            </div>
        </div>
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

        // Menu Giới thiệu với dropdown
        echo '<li class="menu-item-has-children has-dropdown">';
        echo '<a href="' . home_url('/gioi-thieu') . '">Giới thiệu <span class="dropdown-arrow"><svg width="12px" height="12px" viewBox="0 -4.5 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>arrow_down [#338]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-220.000000, -6684.000000)" fill="#000000"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M164.292308,6524.36583 L164.292308,6524.36583 C163.902564,6524.77071 163.902564,6525.42619 164.292308,6525.83004 L172.555873,6534.39267 C173.33636,6535.20244 174.602528,6535.20244 175.383014,6534.39267 L183.70754,6525.76791 C184.093286,6525.36716 184.098283,6524.71997 183.717533,6524.31405 C183.328789,6523.89985 182.68821,6523.89467 182.29347,6524.30266 L174.676479,6532.19636 C174.285736,6532.60124 173.653152,6532.60124 173.262409,6532.19636 L165.705379,6524.36583 C165.315635,6523.96094 164.683051,6523.96094 164.292308,6524.36583" id="arrow_down-[#338]"> </path> </g> </g> </g> </g></svg></span></a>';
        echo '<ul class="dropdown-menu">';
        echo '<li><a href="' . home_url('/gioi-thieu-cong-ty') . '">Giới thiệu về công ty</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu-nha-may') . '">Giới thiệu về nhà máy</a></li>';
        echo '</ul>';
        echo '</li>';

        echo '<li><a href="' . home_url('/tin-tuc') . '">Tin tức</a></li>';
        echo '<li><a href="' . home_url('/faq') . '">FAQ & Hướng dẫn</a></li>';
        echo '<li><a href="' . home_url('/lien-he') . '">Liên hệ</a></li>';
        echo '</ul>';
    }

    function vinapet_fallback_mobile_menu()
    {
        echo '<ul class="mobile-nav-list">';
        echo '<li><a href="' . home_url('/san-pham') . '">Sản phẩm</a></li>';

        // Mobile menu với accordion cho Giới thiệu
        echo '<li class="mobile-menu-item-has-children">';
        echo '<button type="button" class="mobile-dropdown-toggle">Giới thiệu <span class="mobile-arrow">+</span></button>';
        echo '<ul class="mobile-submenu">';
        echo '<li><a href="' . home_url('/gioi-thieu-cong-ty') . '">Giới thiệu về công ty</a></li>';
        echo '<li><a href="' . home_url('/gioi-thieu-nha-may') . '">Giới thiệu về nhà máy</a></li>';
        echo '</ul>';
        echo '</li>';

        echo '<li><a href="' . home_url('/tin-tuc') . '">Tin tức</a></li>';
        echo '<li><a href="' . home_url('/faq') . '">FAQ & Hướng dẫn</a></li>';
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
                $output .= '<a href="' . esc_attr($item->url) . '">' . apply_filters('the_title', $item->title, $item->ID) . '</a>';
            } else {
                // Menu item bình thường
                $output .= $indent . '<li' . $id . $class_names . '>';

                $attributes = ! empty($item->attr_title) ? ' title="'  . esc_attr($item->attr_title) . '"' : '';
                $attributes .= ! empty($item->target)     ? ' target="' . esc_attr($item->target) . '"' : '';
                $attributes .= ! empty($item->xfn)        ? ' rel="'    . esc_attr($item->xfn) . '"' : '';
                $attributes .= ! empty($item->url)        ? ' href="'   . esc_attr($item->url) . '"' : '';

                $item_output = isset($args->before) ? $args->before : '';
                $item_output .= '<a' . $attributes . '>';
                $item_output .= (isset($args->link_before) ? $args->link_before : '') . apply_filters('the_title', $item->title, $item->ID) . (isset($args->link_after) ? $args->link_after : '');
                $item_output .= '</a>';
                $item_output .= isset($args->after) ? $args->after : '';

                $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
            }
        }

        function end_el(&$output, $item, $depth = 0, $args = null)
        {
            $output .= "</li>\n";
        }
    }
    ?>