<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_settings()
{
    register_setting('cp_portal_settings_group', 'cp_portal_settings', [
        'type' => 'array',
        'sanitize_callback' => 'cp_sanitize_settings',
        'default' => cp_settings(),
    ]);

    add_settings_section(
        'cp_portal_general',
        __('Publishing preferences', 'client-portal'),
        'cp_settings_section_callback',
        'cp-settings'
    );

    add_settings_field('cp_portal_title', __('Portal title', 'client-portal'), 'cp_portal_title_field', 'cp-settings', 'cp_portal_general');
    add_settings_field('cp_default_status', __('Default article status', 'client-portal'), 'cp_default_status_field', 'cp-settings', 'cp_portal_general');
    add_settings_field('cp_items_per_page', __('Items per page', 'client-portal'), 'cp_items_per_page_field', 'cp-settings', 'cp_portal_general');
    add_settings_field('cp_allow_authors_publish', __('Author publishing', 'client-portal'), 'cp_allow_authors_publish_field', 'cp-settings', 'cp_portal_general');
}

function cp_sanitize_settings($input)
{
    $input = is_array($input) ? $input : [];
    $items_per_page = isset($input['items_per_page']) ? absint($input['items_per_page']) : 20;

    return [
        'portal_title' => isset($input['portal_title']) ? sanitize_text_field($input['portal_title']) : 'Enterprise1979 Publisher Portal',
        'default_status' => isset($input['default_status']) ? cp_sanitize_status($input['default_status']) : 'draft',
        'items_per_page' => min(100, max(5, $items_per_page)),
        'allow_authors_publish' => empty($input['allow_authors_publish']) ? 0 : 1,
    ];
}

function cp_settings_section_callback()
{
    echo '<p class="cp-settings-description">' . esc_html__('Control portal branding and the defaults used by the custom article manager.', 'client-portal') . '</p>';
}

function cp_portal_title_field()
{
    $settings = cp_settings();
    ?>
    <input class="form-control" type="text" id="cp-portal-title" name="cp_portal_settings[portal_title]" value="<?php echo esc_attr($settings['portal_title']); ?>" required>
    <?php
}

function cp_default_status_field()
{
    $settings = cp_settings();
    ?>
    <select class="form-select" id="cp-default-status" name="cp_portal_settings[default_status]">
        <option value="draft" <?php selected($settings['default_status'], 'draft'); ?>><?php esc_html_e('Draft', 'client-portal'); ?></option>
        <option value="publish" <?php selected($settings['default_status'], 'publish'); ?>><?php esc_html_e('Published', 'client-portal'); ?></option>
        <option value="private" <?php selected($settings['default_status'], 'private'); ?>><?php esc_html_e('Private', 'client-portal'); ?></option>
    </select>
    <?php
}

function cp_items_per_page_field()
{
    $settings = cp_settings();
    ?>
    <input class="form-control" type="number" id="cp-items-per-page" name="cp_portal_settings[items_per_page]" value="<?php echo esc_attr($settings['items_per_page']); ?>" min="5" max="100" step="1">
    <?php
}

function cp_allow_authors_publish_field()
{
    $settings = cp_settings();
    ?>
    <div class="form-check form-switch">
        <input type="hidden" name="cp_portal_settings[allow_authors_publish]" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="cp-allow-authors-publish" name="cp_portal_settings[allow_authors_publish]" value="1" <?php checked($settings['allow_authors_publish'], 1); ?>>
        <label class="form-check-label" for="cp-allow-authors-publish"><?php esc_html_e('Allow authors to publish directly', 'client-portal'); ?></label>
    </div>
    <?php
}

function cp_settings_page()
{
    cp_require_capability('manage_options');
    cp_render_page('settings', ['page_title' => __('Settings', 'client-portal')]);
}
