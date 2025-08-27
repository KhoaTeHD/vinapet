<?php
/**
 * VinaPet Footer Template
 * @package VinaPet
 */
if (!defined('ABSPATH')) exit;

$company_info = get_option('vinapet_company_info', array());
?>

<footer id="colophon" class="site-footer vinapet-footer">
    <div class="footer-main">
        <div class="container">
            <!-- Logo -->
            <div class="footer-logo-section">
                <?php if (has_custom_logo()) : ?>
                    <div class="footer-logo"><?php the_custom_logo(); ?></div>
                <?php endif; ?>
            </div>

            <!-- Content Grid -->
            <div class="footer-content-grid">
                
                <!-- Company Info -->
                <div class="footer-col company-info">
                    <h3 class="company-title"><?php echo esc_html($company_info['name'] ?? 'CÔNG TY CP ĐẦU TƯ VÀ SẢN XUẤT VINAPET'); ?></h3>
                    
                    <div class="contact-list">
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <span class="text"><?php echo esc_html($company_info['factory_address'] ?? 'Nhà máy công nghệ cao Vinapet - Lô CN 3.1 - Khu CN Phú Nghĩa - Chương Mỹ, Hà Nội'); ?></span>
                        </div>
                        
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <span class="text"><?php echo esc_html($company_info['office_address'] ?? 'Văn phòng: Tòa nhà Cung Tri Thức - Số 1 Tôn Thất Thuyết - Cầu Giấy, Hà Nội'); ?></span>
                        </div>
                        
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <span class="text">Mã số thuế: <?php echo esc_html($company_info['tax_code'] ?? '0110064359'); ?></span>
                        </div>
                        
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <span class="text">
                                <a href="tel:<?php echo esc_attr(str_replace(array(' ', '(', ')', '+'), '', $company_info['phone1'] ?? '+84911818518')); ?>">
                                    <?php echo esc_html($company_info['phone1'] ?? '(+84) 911 818 518'); ?>
                                </a>
                                <?php if (!empty($company_info['phone2'])) : ?>
                                    / <a href="tel:<?php echo esc_attr(str_replace(array(' ', '(', ')', '+'), '', $company_info['phone2'])); ?>">
                                        <?php echo esc_html($company_info['phone2']); ?>
                                    </a>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="contact-item">
                            <span class="icon">🌐</span>
                            <span class="text">
                                <a href="mailto:<?php echo esc_attr($company_info['email'] ?? 'support@vinapet.com.vn'); ?>">
                                    <?php echo esc_html($company_info['email'] ?? 'support@vinapet.com.vn'); ?>
                                </a>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Policies -->
                <div class="footer-col policies">
                    <h4 class="footer-title">Chính sách</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo home_url('/chinh-sach-bao-hanh'); ?>">Chính sách bảo hành</a></li>
                        <li><a href="<?php echo home_url('/chinh-sach-doi-tra'); ?>">Chính sách đổi trả</a></li>
                        <li><a href="<?php echo home_url('/chinh-sach-thanh-toan'); ?>">Chính sách thanh toán</a></li>
                        <li><a href="<?php echo home_url('/chinh-sach-bao-mat'); ?>">Chính sách bảo mật</a></li>
                    </ul>
                </div>

                <!-- Sitemap -->
                <div class="footer-col sitemap">
                    <h4 class="footer-title">SITEMAP</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo home_url(); ?>">Trang chủ</a></li>
                        <li><a href="<?php echo home_url('/san-pham'); ?>">Sản phẩm</a></li>
                        <li><a href="<?php echo home_url('/gioi-thieu'); ?>">Giới thiệu</a></li>
                        <li><a href="<?php echo home_url('/tin-tuc'); ?>">Tin tức</a></li>
                        <li><a href="<?php echo home_url('/lien-he'); ?>">Liên hệ</a></li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div class="footer-col social-media">
                    <h4 class="footer-title">FANPAGE</h4>
                    <div class="social-content">
                        <div class="fb-page" 
                             data-href="<?php echo esc_url($company_info['facebook_url'] ?? 'https://www.facebook.com/p/Vinapet-100094599485921/'); ?>" 
                             data-tabs="" 
                             data-width="280" 
                             data-height="50" 
                             data-small-header="false" 
                             data-adapt-container-width="true">
                        </div>
                        
                        <div class="qr-code">
                            <img src="<?php echo VINAPET_THEME_URI; ?>/assets/images/qr-code.png" 
                                 alt="QR Code" loading="lazy">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Copyright -->
    <div class="footer-copyright">
        <div class="container">
            <div class="copyright-content">
                <p>Copyright 2025© Bản quyền thuộc về <?php echo esc_html($company_info['name'] ?? 'Công ty cổ phần đầu tư & phát triển Vinapet'); ?></p>
            </div>
        </div>
    </div>
</footer>

<!-- Facebook SDK -->
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" 
        src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v18.0"></script>

<?php wp_footer(); ?>
</body>
</html>