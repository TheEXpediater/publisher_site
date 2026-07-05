<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$page_title = isset($page_title) ? $page_title : __('Publisher Portal', 'client-portal');
$portal_settings = cp_settings();
?>
<div class="cp-topbar-inner">
    <div class="d-flex align-items-center gap-3">
        <button class="cp-menu-toggle" type="button" data-cp-sidebar-open aria-controls="cp-sidebar" aria-label="<?php esc_attr_e('Open navigation', 'client-portal'); ?>">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="cp-topbar-title">
            <span><?php echo esc_html($portal_settings['portal_title']); ?></span>
            <h1><?php echo esc_html($page_title); ?></h1>
        </div>
    </div>
    <div class="cp-user-menu">
        <div class="cp-user-meta">
            <strong><?php echo esc_html($current_user->display_name); ?></strong>
            <small><?php echo esc_html($current_user->user_email); ?></small>
        </div>
        <div class="cp-user-avatar"><?php echo wp_kses_post(get_avatar($current_user->ID, 42, '', $current_user->display_name)); ?></div>
        <a class="cp-logout-button" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span><?php esc_html_e('Logout', 'client-portal'); ?></span>
        </a>
    </div>
</div>
