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

    cp_redirect('cp-categories', ['cp_notice' => $category_id ? 'category-updated' : 'category-created']);
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
    cp_redirect('cp-categories', !is_wp_error($result) && $result ? ['cp_notice' => 'category-deleted'] : []);
}

function cp_categories_page()
{
    cp_require_capability('manage_categories');
    $notice = cp_handle_category_save();
    $editing_category = cp_handle_category_request();

    cp_render_page('categories', [
        'page_title' => __('Categories', 'client-portal'),
        'categories' => get_categories(['hide_empty' => false, 'orderby' => 'name']),
        'editing_category' => $editing_category,
        'notice' => $notice ?: cp_request_notice(),
    ]);
}
