<?php

if (!defined('ABSPATH')) {
    exit;
}

$pages = [
    'cp-dashboard' => ['label' => __('Dashboard', 'client-portal'), 'icon' => 'bi-grid-1x2-fill', 'capability' => 'read'],
    'cp-articles' => ['label' => __('Articles', 'client-portal'), 'icon' => 'bi-journal-text', 'capability' => 'edit_posts'],
    'cp-categories' => ['label' => __('Categories', 'client-portal'), 'icon' => 'bi-tags-fill', 'capability' => 'manage_categories'],
    'cp-users' => ['label' => __('Users', 'client-portal'), 'icon' => 'bi-people-fill', 'capability' => 'list_users'],
    'cp-analytics' => ['label' => __('Analytics', 'client-portal'), 'icon' => 'bi-bar-chart-fill', 'capability' => 'read'],
    'cp-settings' => ['label' => __('Settings', 'client-portal'), 'icon' => 'bi-sliders', 'capability' => 'manage_options'],
];
?>
<div class="cp-sidebar-brand">
    <span class="cp-brand-mark" aria-hidden="true">E</span>
    <span>
        <strong><?php esc_html_e('Enterprise1979', 'client-portal'); ?></strong>
        <small><?php esc_html_e('Publisher Portal', 'client-portal'); ?></small>
    </span>
    <button class="cp-sidebar-close" type="button" data-cp-sidebar-close aria-label="<?php esc_attr_e('Close navigation', 'client-portal'); ?>">
        <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
</div>
<p class="cp-nav-label"><?php esc_html_e('Workspace', 'client-portal'); ?></p>
<nav class="cp-sidebar-nav">
    <?php foreach ($pages as $slug => $item) : ?>
        <?php if (current_user_can($item['capability'])) : ?>
            <a class="cp-nav-link <?php echo esc_attr(cp_is_active_page($slug)); ?>" href="<?php echo esc_url(cp_admin_url($slug)); ?>" aria-current="<?php echo esc_attr(cp_is_active_page($slug) ? 'page' : 'false'); ?>">
                <i class="bi <?php echo esc_attr($item['icon']); ?>" aria-hidden="true"></i>
                <span><?php echo esc_html($item['label']); ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<div class="cp-sidebar-foot">
    <i class="bi bi-shield-check" aria-hidden="true"></i>
    <span><?php esc_html_e('Secured by WordPress', 'client-portal'); ?></span>
</div>
