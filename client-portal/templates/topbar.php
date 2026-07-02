<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$page_title = isset($page_title) ? $page_title : __('Portal', 'client-portal');

?>
<div class="cp-topbar-inner">
    <div class="cp-topbar-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Search" aria-label="Search" />
    </div>
    <div class="cp-topbar-actions">
        <div class="cp-user-chip">
            <?php echo get_avatar($current_user->ID, 36, '', '', ['class' => 'rounded-circle']); ?>
            <div>
                <div class="fw-semibold small"><?php echo esc_html($current_user->display_name); ?></div>
                <div class="text-muted small"><?php echo esc_html($current_user->user_email); ?></div>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted"><?php echo esc_html($current_user->display_name); ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>"><?php esc_html_e('Logout', 'client-portal'); ?></a></li>
            </ul>
        </div>
    </div>
</div>
