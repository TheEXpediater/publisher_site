<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_current_category_page()
{
    return max(1, absint(cp_get_value('category_page')));
}

function cp_category_page_args($category_page = 1)
{
    $category_page = max(1, absint($category_page));
    return $category_page > 1 ? ['category_page' => $category_page] : [];
}

function cp_handle_category_save()
{
    if (!isset($_POST['cp_category_action'])) {
        return;
    }

    cp_require_capability('manage_categories');
    check_admin_referer('cp_save_category', 'cp_category_nonce');

    $category_page = max(1, absint(cp_post_value('category_page')));
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
        return [
            'type' => 'danger',
            'message' => $result->get_error_message(),
            'category_page' => $category_page,
        ];
    }

    cp_redirect(
        'cp-categories',
        array_merge(
            cp_category_page_args($category_page),
            ['cp_notice' => $category_id ? 'category_updated' : 'category_created']
        )
    );
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
            cp_redirect('cp-categories', cp_category_page_args(isset($notice['category_page']) ? $notice['category_page'] : 1));
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
    $category_page = cp_current_category_page();

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
        cp_redirect('cp-categories', cp_category_page_args($category_page));
    }

    cp_redirect(
        'cp-categories',
        array_merge(cp_category_page_args($category_page), ['cp_notice' => 'category_deleted'])
    );
}

function cp_categories_page()
{
    cp_require_capability('manage_categories');
    $editing_category = cp_handle_category_request();
    $category_page = cp_current_category_page();
    $category_per_page = 10;
    $category_total = wp_count_terms([
        'taxonomy' => 'category',
        'hide_empty' => false,
    ]);

    if (is_wp_error($category_total)) {
        $category_total = 0;
    }

    $category_total = absint($category_total);
    $category_max_pages = max(1, (int) ceil($category_total / $category_per_page));

    if ($category_page > $category_max_pages) {
        cp_redirect('cp-categories', cp_category_page_args($category_max_pages));
    }

    $categories = get_categories([
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'number' => $category_per_page,
        'offset' => ($category_page - 1) * $category_per_page,
    ]);

    $category_count = is_array($categories) ? count($categories) : 0;
    $category_start = $category_total > 0 ? (($category_page - 1) * $category_per_page) + 1 : 0;
    $category_end = $category_total > 0 ? min($category_start + $category_count - 1, $category_total) : 0;

    cp_render_page('categories', [
        'page_title' => __('Categories', 'client-portal'),
        'categories' => is_array($categories) ? $categories : [],
        'editing_category' => $editing_category,
        'category_page' => $category_page,
        'category_per_page' => $category_per_page,
        'category_total' => $category_total,
        'category_start' => $category_start,
        'category_end' => $category_end,
        'category_max_pages' => $category_max_pages,
        'notice' => cp_request_notice(),
    ]);
}
