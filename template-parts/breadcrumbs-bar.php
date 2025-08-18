<?php
/**
 * Template part for displaying breadcrumbs
 *
 * @package VinaPet
 */

global $breadcrumb_data;

if (empty($breadcrumb_data)) {
    return;
}
?>

<div class="breadcrumbs-bar">
    <ul class="breadcrumbs-list">
        <?php foreach ($breadcrumb_data as $index => $item): ?>
            <li class="breadcrumb-item">
                <?php if (!empty($item['url'])): ?>
                    <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['name']); ?></a>
                <?php else: ?>
                    <span><?php echo esc_html($item['name']); ?></span>
                <?php endif; ?>
                
                <?php if ($index < count($breadcrumb_data) - 1): ?>
                    <span class="breadcrumb-separator">/</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>