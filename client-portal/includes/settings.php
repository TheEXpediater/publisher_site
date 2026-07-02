<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_settings_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    if (isset($_POST['cp_settings_submit']) && isset($_POST['cp_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cp_settings_nonce'])), 'cp_settings_action')) {
        $settings = [
            'portal_title' => sanitize_text_field(wp_unslash($_POST['portal_title'])),
            'default_status' => sanitize_text_field(wp_unslash($_POST['default_status'])),
        ];

        update_option('cp_portal_settings', $settings);
    }

    cp_render_page('settings', [
        'page_title' => __('Settings', 'client-portal'),
    ]);
}