<?php
/**
 * The main template file
 * Template mặc định cho VinaPet Theme
 *
 * @package VinaPet
 * @subpackage Templates
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        
        <?php if (have_posts()): ?>
        
            <!-- Page Header -->
            <div class="page-header">
                <div class="container">
                    <?php
                    if (is_home() && !is_front_page()) {
                        ?>
                        <h1 class="page-title"><?php single_post_title(); ?></h1>
                        <?php
                    } elseif (is_search()) {
                        ?>
                        <h1 class="page-title">
                            <?php printf(esc_html__('Kết quả tìm kiếm cho: %s', 'vinapet'), '<span>' . get_search_query() . '</span>'); ?>
                        </h1>
                        <?php
                    } elseif (is_archive()) {
                        the_archive_title('<h1 class="page-title">', '</h1>');
                        the_archive_description('<div class="archive-description">', '</div>');
                    } else {
                        ?>
                        <h1 class="page-title"><?php esc_html_e('Blog', 'vinapet'); ?></h1>
                        <?php
                    }
                    ?>
                    
                    <?php
                    // Breadcrumbs nếu có
                    
                    ?>
                </div>
            </div>
            
            <!-- Posts Container -->
            <div class="posts-container">
                <div class="container">
                    <div class="posts-grid">
                        <?php
                        while (have_posts()) :
                            the_post();
                            ?>
                            
                            <article id="post-<?php the_ID(); ?>" <?php post_class('post-item'); ?>>
                                
                                <?php if (has_post_thumbnail()): ?>
                                    <div class="post-thumbnail">
                                        <a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
                                            <?php
                                            the_post_thumbnail('medium_large', array(
                                                'alt' => the_title_attribute(array('echo' => false)),
                                                'loading' => 'lazy'
                                            ));
                                            ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-content">
                                    <div class="post-meta">
                                        <span class="post-date">
                                            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                                <?php echo esc_html(get_the_date()); ?>
                                            </time>
                                        </span>
                                        
                                        <?php
                                        $categories = get_the_category();
                                        if (!empty($categories)) {
                                            ?>
                                            <span class="post-categories">
                                                <?php
                                                foreach ($categories as $category) {
                                                    echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" class="post-category">' . esc_html($category->name) . '</a>';
                                                    break; // Chỉ hiển thị category đầu tiên
                                                }
                                                ?>
                                            </span>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    
                                    <h2 class="post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    
                                    <div class="post-excerpt">
                                        <?php
                                        if (has_excerpt()) {
                                            the_excerpt();
                                        } else {
                                            echo wp_trim_words(get_the_content(), 25, '...');
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="post-footer">
                                        <a href="<?php the_permalink(); ?>" class="read-more-btn">
                                            <?php esc_html_e('Đọc thêm', 'vinapet'); ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                                            </svg>
                                        </a>
                                        
                                        <?php
                                      
                                        ?>
                                    </div>
                                </div>
                            </article>
                            
                            <?php
                        endwhile;
                        ?>
                    </div>
                    
                    <?php
                    // Pagination
                    the_posts_pagination(array(
                        'mid_size'  => 2,
                        'prev_text' => sprintf(
                            '%s <span class="nav-prev-text">%s</span>',
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6 1.41-1.41z"/></svg>',
                            __('Trang trước', 'vinapet')
                        ),
                        'next_text' => sprintf(
                            '<span class="nav-next-text">%s</span> %s',
                            __('Trang sau', 'vinapet'),
                            '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>'
                        ),
                    ));
                    ?>
                </div>
            </div>
            
        <?php else: ?>
            
            <!-- No Posts Found -->
            <div class="no-posts-found">
                <div class="container">
                    <div class="no-posts-content">
                        <div class="no-posts-icon">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        
                        <h2 class="no-posts-title">
                            <?php
                            if (is_search()) {
                                esc_html_e('Không tìm thấy kết quả', 'vinapet');
                            } else {
                                esc_html_e('Chưa có bài viết nào', 'vinapet');
                            }
                            ?>
                        </h2>
                        
                        <p class="no-posts-description">
                            <?php
                            if (is_search()) {
                                printf(
                                    esc_html__('Không tìm thấy kết quả nào cho "%s". Hãy thử với từ khóa khác.', 'vinapet'),
                                    '<strong>' . get_search_query() . '</strong>'
                                );
                            } else {
                                esc_html_e('Hiện tại chưa có bài viết nào được xuất bản.', 'vinapet');
                            }
                            ?>
                        </p>
                        
                        <?php if (is_search()): ?>
                            <div class="search-form-wrapper">
                                <?php get_search_form(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="no-posts-actions">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                                <?php esc_html_e('Về trang chủ', 'vinapet'); ?>
                            </a>
                            
                            <?php if (!is_search()): ?>
                                <a href="<?php echo esc_url(home_url('/shop')); ?>" class="btn btn-secondary">
                                    <?php esc_html_e('Xem sản phẩm', 'vinapet'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
        
    </main><!-- #main -->
    
    <?php
    // Sidebar nếu có
    if (is_active_sidebar('blog-sidebar')) {
        ?>
        <aside id="secondary" class="widget-area">
            <?php dynamic_sidebar('blog-sidebar'); ?>
        </aside>
        <?php
    }
    ?>
</div><!-- #primary -->

<?php
get_footer();
?>