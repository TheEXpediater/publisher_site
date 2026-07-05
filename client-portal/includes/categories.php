<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_handle_category_save()
{
    if (!isset($_POST['cp_category_action'])) {
        return;
    }

    cp_require_capability('manage_categories');
    check_admin_referer('cp_save_category', 'cp_category_nonce');

    $category_id = absint(cp_post_value('category_id'));
    $name = sanitize_text_field(cp_post_value('name'));
    $args = [
        'slug' => sanitize_title(cp_post_value('slug')),
        'description' => sanitize_textarea_field(cp_post_value('description')),
    ];

    $result = $category_id
        ? wp_update_term($category_id, 'category', array_merge(['name' => $name], $args))
        : wp_insert_term($name, 'category', $args);

    if (is_wp_error($result)) {
        return ['type' => 'danger', 'message' => $result->get_error_message()];
    }

    cp_redirect('cp-categories', ['cp_notice' => $category_id ? 'category_updated' : 'category_created']);
}

function cp_process_category_admin_actions()
{
    if ('cp-categories' !== cp_current_page()) {
        return;
    }

    if ('save' === sanitize_key(cp_post_value('cp_category_action'))) {
        $notice = cp_handle_category_save();
        if (is_array($notice) && !empty($notice['message'])) {
            cp_set_temporary_notice(isset($notice['type']) ? $notice['type'] : 'danger', $notice['message']);
            cp_redirect('cp-categories');
        }
    }

    if ('delete' === sanitize_key(cp_get_value('action'))) {
        cp_handle_category_request();
    }
}

function cp_handle_category_request()
{
    $action = sanitize_key(cp_get_value('action'));
    $category_id = absint(cp_get_value('id'));

    if (!$category_id || !in_array($action, ['edit', 'delete'], true)) {
        return null;
    }

    cp_require_capability('manage_categories');
    check_admin_referer('cp_' . $action . '_category_' . $category_id);

    if ('edit' === $action) {
        $term = get_term($category_id, 'category');
        return is_wp_error($term) ? null : $term;
    }

    $result = wp_delete_term($category_id, 'category');
    if (is_wp_error($result) || !$result) {
        $message = is_wp_error($result) ? $result->get_error_message() : __('The category could not be deleted.', 'client-portal');
        cp_set_temporary_notice('danger', $message);
        cp_redirect('cp-categories');
    }

    cp_redirect('cp-categories', ['cp_notice' => 'category_deleted']);
}

function cp_categories_page()
{
    cp_require_capability('manage_categories');
    $editing_category = cp_handle_category_request();

    cp_render_page('categories', [
        'page_title' => __('Categories', 'client-portal'),
        'categories' => get_categories(['hide_empty' => false, 'orderby' => 'name']),
        'editing_category' => $editing_category,
        'notice' => cp_request_notice(),
    ]);
}
