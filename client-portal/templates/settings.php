<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="cp-page-heading"><div><p class="cp-eyebrow"><?php esc_html_e('Configuration', 'client-portal'); ?></p><h2><?php esc_html_e('Portal Settings', 'client-portal'); ?></h2><p><?php esc_html_e('Configure branding and publishing workflow defaults.', 'client-portal'); ?></p></div></div>
<?php settings_errors('cp_portal_settings_group'); ?>
<section class="cp-card cp-settings-card"><form method="post" action="options.php">
    <?php settings_fields('cp_portal_settings_group'); ?>
    <?php do_settings_sections('cp-settings'); ?>
    <?php submit_button(__('Save Settings', 'client-portal'), 'primary', 'submit', false); ?>
</form></section>
