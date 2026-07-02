<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$page_title = isset($page_title) ? $page_title : __('Portal', 'client-portal');

?>
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="h4 mb-0"><?php echo esc_html($page_title); ?></h1>
        <p class="text-muted small mb-0">Managed from the WordPress admin.</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <?php echo get_avatar($current_user->ID, 32, '', '', ['class' => 'rounded-circle']); ?>
            <div>
                <div class="fw-semibold small"><?php echo esc_html($current_user->display_name); ?></div>
                <div class="text-muted small"><?php echo esc_html($current_user->user_email); ?></div>
            </div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>">
            <i class="bi bi-box-arrow-right me-1"></i>
            <?php esc_html_e('Logout', 'client-portal'); ?>
        </a>
    </div>
</div>
