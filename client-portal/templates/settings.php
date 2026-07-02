<?php

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('cp_portal_settings', []);

?>
<div class="cp-page-header mb-4">
    <h2 class="h3 mb-1"><?php esc_html_e('Portal Settings', 'client-portal'); ?></h2>
    <p class="text-muted mb-0">Configure the portal branding and publishing defaults.</p>
</div>

<div class="card border-0 shadow-sm cp-card">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('cp_settings_action', 'cp_settings_nonce'); ?>
            <div class="mb-3">
                <label class="form-label" for="cp-portal-title"><?php esc_html_e('Portal Title', 'client-portal'); ?></label>
                <input type="text" class="form-control" id="cp-portal-title" name="portal_title" value="<?php echo esc_attr($options['portal_title'] ?? 'Enterprise1979 Publisher Portal'); ?>" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="cp-default-status"><?php esc_html_e('Default Article Status', 'client-portal'); ?></label>
                <select class="form-select" id="cp-default-status" name="default_status">
                    <option value="draft" <?php selected($options['default_status'] ?? 'draft', 'draft'); ?>><?php esc_html_e('Draft', 'client-portal'); ?></option>
                    <option value="publish" <?php selected($options['default_status'] ?? 'draft', 'publish'); ?>><?php esc_html_e('Published', 'client-portal'); ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" name="cp_settings_submit" value="1"><?php esc_html_e('Save Settings', 'client-portal'); ?></button>
        </form>
    </div>
</div>