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
        <button class="cp-logout-button" type="button" data-bs-toggle="modal" data-bs-target="#cp-logout-modal">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            <span><?php esc_html_e('Logout', 'client-portal'); ?></span>
        </button>
    </div>
</div>
<div class="modal fade cp-modal" id="cp-logout-modal" tabindex="-1" aria-labelledby="cp-logout-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="cp-logout-modal-title"><?php esc_html_e('Confirm Logout', 'client-portal'); ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><?php esc_html_e('Are you sure you want to log out of the Publisher Portal?', 'client-portal'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                <a class="btn btn-primary" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>"><?php esc_html_e('Logout', 'client-portal'); ?></a>
            </div>
        </div>
    </div>
</div>
