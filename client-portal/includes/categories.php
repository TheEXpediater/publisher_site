<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_categories_page()
{
    if (!cp_is_developer() && !current_user_can('read')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    if (isset($_POST['cp_categories_submit']) && isset($_POST['cp_categories_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cp_categories_nonce'])), 'cp_categories_action')) {
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $name = sanitize_text_field(wp_unslash($_POST['name']));
        $slug = sanitize_title(wp_unslash($_POST['slug']));

        $args = [
            'name' => $name,
            'slug' => $slug,
        ];

        if ($category_id) {
            wp_update_term($category_id, 'category', $args);
        } else {
            wp_insert_term($name, 'category', $args);
        }
    }

    $editing_category = null;
    if (isset($_GET['action'], $_GET['id']) && 'edit' === sanitize_text_field(wp_unslash($_GET['action'])) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_edit_category_' . absint($_GET['id']))) {
        $editing_category = get_term(absint($_GET['id']), 'category');
    } elseif (isset($_GET['action'], $_GET['id']) && 'delete' === sanitize_text_field(wp_unslash($_GET['action'])) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_delete_category_' . absint($_GET['id']))) {
        wp_delete_term(absint($_GET['id']), 'category');
    }

    $categories = get_categories(['hide_empty' => false]);

    cp_render_page('categories', [
        'page_title' => __('Categories', 'client-portal'),
        'categories' => $categories,
        'editing_category' => $editing_category,
    ]);
}