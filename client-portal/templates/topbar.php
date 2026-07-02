<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$page_title = isset($page_title) ? $page_title : __('Portal', 'client-portal');

?>
<div class="cp-topbar-inner">
    <div class="cp-topbar-title">
        <h1><?php echo esc_html($page_title); ?></h1>
    </div>
    <div class="cp-topbar-actions">
        <div class="cp-user-meta">
            <span class="cp-user-name"><?php echo esc_html($current_user->display_name); ?></span>
            <span class="cp-user-email"><?php echo esc_html($current_user->user_email); ?></span>
        </div>
        <a class="cp-logout-button" href="<?php echo esc_url(wp_logout_url(admin_url())); ?>">
            <?php esc_html_e('Logout', 'client-portal'); ?>
        </a>
    </div>
</div>
