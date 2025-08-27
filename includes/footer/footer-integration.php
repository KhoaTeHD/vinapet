<?php
/**
 * VinaPet Footer Integration
 * Thêm vào functions.php của theme
 * 
 * @package VinaPet
 */

/**
 * Enqueue footer assets
 */
function vinapet_footer_assets() {
    // Footer CSS
    wp_enqueue_style(
        'vinapet-footer',
        VINAPET_THEME_URI . '/assets/css/footer.css',
        array(),
        VINAPET_VERSION
    );
    
    // Footer JavaScript (if needed)
    wp_enqueue_script(
        'vinapet-footer',
        VINAPET_THEME_URI . '/assets/js/footer.js',
        array('jquery'),
        VINAPET_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'vinapet_footer_assets');

/**
 * Company information settings
 */
function vinapet_init_company_info() {
    $default_company_info = array(
        'name' => 'CÔNG TY CP ĐẦU TƯ VÀ SẢN XUẤT VINAPET',
        'factory_address' => 'Nhà máy công nghệ cao Vinapet - Lô CN 3.1 - Khu CN Phú Nghĩa - Chương Mỹ, Hà Nội',
        'office_address' => 'Văn phòng: Tòa nhà Cung Tri Thức - Số 1 Tôn Thất Thuyết - Cầu Giấy, Hà Nội',
        'tax_code' => '0110064359',
        'phone1' => '+84 911 818 518',
        'phone2' => '+84 2471008333',
        'email' => 'support@vinapet.com.vn',
        'facebook_url' => 'https://www.facebook.com/p/Vinapet-100094599485921/',
        'tagline' => 'Nhà cung cấp giải pháp bao bì hàng đầu Việt Nam'
    );
    
    if (!get_option('vinapet_company_info')) {
        add_option('vinapet_company_info', $default_company_info);
    }
}
add_action('init', 'vinapet_init_company_info', 5);

/**
 * Add admin menu for footer settings
 */
function vinapet_footer_admin_menu() {
    add_submenu_page(
        'vinapet-settings',
        'Cài đặt Footer',
        'Footer',
        'manage_options',
        'vinapet-footer-settings',
        'vinapet_footer_settings_page'
    );
}
add_action('admin_menu', 'vinapet_footer_admin_menu');

/**
 * Footer settings page
 */
function vinapet_footer_settings_page() {
    if (isset($_POST['submit'])) {
        $company_info = array(
            'name' => sanitize_text_field($_POST['company_name']),
            'factory_address' => sanitize_textarea_field($_POST['factory_address']),
            'office_address' => sanitize_textarea_field($_POST['office_address']),
            'tax_code' => sanitize_text_field($_POST['tax_code']),
            'phone1' => sanitize_text_field($_POST['phone1']),
            'phone2' => sanitize_text_field($_POST['phone2']),
            'email' => sanitize_email($_POST['email']),
            'facebook_url' => esc_url_raw($_POST['facebook_url']),
            'tagline' => sanitize_text_field($_POST['tagline'])
        );
        
        update_option('vinapet_company_info', $company_info);
        echo '<div class="notice notice-success"><p>Cài đặt đã được lưu!</p></div>';
    }
    
    $company_info = get_option('vinapet_company_info', array());
    ?>
    <div class="wrap">
        <h1>Cài đặt Footer VinaPet</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Tên công ty</th>
                    <td><input type="text" name="company_name" value="<?php echo esc_attr($company_info['name'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Địa chỉ nhà máy</th>
                    <td><textarea name="factory_address" rows="3" cols="50"><?php echo esc_textarea($company_info['factory_address'] ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Địa chỉ văn phòng</th>
                    <td><textarea name="office_address" rows="3" cols="50"><?php echo esc_textarea($company_info['office_address'] ?? ''); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Mã số thuế</th>
                    <td><input type="text" name="tax_code" value="<?php echo esc_attr($company_info['tax_code'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Số điện thoại 1</th>
                    <td><input type="text" name="phone1" value="<?php echo esc_attr($company_info['phone1'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Số điện thoại 2</th>
                    <td><input type="text" name="phone2" value="<?php echo esc_attr($company_info['phone2'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Email</th>
                    <td><input type="email" name="email" value="<?php echo esc_attr($company_info['email'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Facebook URL</th>
                    <td><input type="url" name="facebook_url" value="<?php echo esc_attr($company_info['facebook_url'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Tagline</th>
                    <td><input type="text" name="tagline" value="<?php echo esc_attr($company_info['tagline'] ?? ''); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button('Lưu cài đặt'); ?>
        </form>
    </div>
    <?php
}

/**
 * Custom footer widgets area
 */
function vinapet_footer_widgets_init() {
    register_sidebar(array(
        'name'          => 'Footer Widget Area',
        'id'            => 'footer-widget-area',
        'description'   => 'Widget area for footer',
        'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="footer-widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'vinapet_footer_widgets_init');

/**
 * Footer structured data (Schema.org)
 */
function vinapet_footer_structured_data() {
    $company_info = get_option('vinapet_company_info', array());
    
    $structured_data = array(
        "@context" => "https://schema.org",
        "@type" => "Corporation",
        "name" => $company_info['name'] ?? get_bloginfo('name'),
        "url" => home_url(),
        "logo" => has_custom_logo() ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
        "contactPoint" => array(
            "@type" => "ContactPoint",
            "telephone" => $company_info['phone1'] ?? '',
            "email" => $company_info['email'] ?? '',
            "contactType" => "customer service"
        ),
        "address" => array(
            "@type" => "PostalAddress",
            "addressLocality" => "Hà Nội",
            "addressCountry" => "VN",
            "streetAddress" => $company_info['office_address'] ?? ''
        ),
        "sameAs" => array(
            $company_info['facebook_url'] ?? ''
        )
    );
    
    echo '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
add_action('wp_footer', 'vinapet_footer_structured_data');

/**
 * Add footer menu location
 */
function vinapet_register_footer_menus() {
    register_nav_menus(array(
        'footer-policies' => 'Menu Chính sách Footer',
        'footer-sitemap' => 'Menu Sitemap Footer',
    ));
}
add_action('init', 'vinapet_register_footer_menus');

/**
 * Get footer menu with fallback
 */
function vinapet_get_footer_menu($location, $fallback_links = array()) {
    if (has_nav_menu($location)) {
        wp_nav_menu(array(
            'theme_location' => $location,
            'container' => false,
            'menu_class' => 'footer-links',
            'depth' => 1,
        ));
    } else {
        if (!empty($fallback_links)) {
            echo '<ul class="footer-links">';
            foreach ($fallback_links as $title => $url) {
                echo '<li><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></li>';
            }
            echo '</ul>';
        }
    }
}

/**
 * Optimize footer performance
 */
function vinapet_footer_optimization() {
    // Lazy load footer images
    add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
        if (is_admin()) return $attr;
        
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
        return $attr;
    }, 10, 3);
    
    // Preconnect to external domains
    add_action('wp_head', function() {
        echo '<link rel="preconnect" href="https://connect.facebook.net">' . "\n";
        echo '<link rel="dns-prefetch" href="//connect.facebook.net">' . "\n";
    });
}
add_action('init', 'vinapet_footer_optimization');

/**
 * ERPNext Integration for footer data
 */
function vinapet_sync_footer_data_with_erpnext() {
    if (!vinapet_is_erpnext_enabled()) {
        return;
    }
    
    $erpnext_settings = vinapet_get_erpnext_settings();
    
    // Sync company information from ERPNext if needed
    $company_data = vinapet_get_company_data_from_erpnext();
    if ($company_data) {
        $company_info = get_option('vinapet_company_info', array());
        $company_info = array_merge($company_info, $company_data);
        update_option('vinapet_company_info', $company_info);
    }
}

/**
 * Get company data from ERPNext
 */
function vinapet_get_company_data_from_erpnext() {
    // Implementation depends on your ERPNext setup
    // This is a placeholder for ERPNext integration
    return false;
}