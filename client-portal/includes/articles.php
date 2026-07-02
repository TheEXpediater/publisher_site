<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_articles_page()
{
    if (!cp_is_developer() && !current_user_can('read')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    if (isset($_POST['cp_articles_submit']) && isset($_POST['cp_articles_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cp_articles_nonce'])), 'cp_articles_action')) {
        $article_id = isset($_POST['article_id']) ? absint($_POST['article_id']) : 0;
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $content = wp_kses_post(wp_unslash($_POST['content']));
        $excerpt = sanitize_text_field(wp_unslash($_POST['excerpt']));
        $status = sanitize_text_field(wp_unslash($_POST['status']));
        $category_id = absint($_POST['category']);

        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => $status,
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
        ];

        if ($article_id) {
            $post_data['ID'] = $article_id;
            $saved_id = wp_update_post($post_data, true);
        } else {
            $saved_id = wp_insert_post($post_data, true);
        }

        if (!is_wp_error($saved_id)) {
            if ($category_id) {
                wp_set_post_categories($saved_id, [$category_id], false);
            } else {
                wp_set_post_categories($saved_id, [], false);
            }
        }
    }

    if (isset($_GET['action'], $_GET['id']) && isset($_GET['_wpnonce']) && 'edit' === sanitize_text_field(wp_unslash($_GET['action'])) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_edit_article_' . absint($_GET['id']))) {
        $editing_post = get_post(absint($_GET['id']));
    } elseif (isset($_GET['action'], $_GET['id']) && 'delete' === sanitize_text_field(wp_unslash($_GET['action'])) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_delete_article_' . absint($_GET['id']))) {
        wp_delete_post(absint($_GET['id']), true);
    }

    $editing_post = isset($editing_post) ? $editing_post : null;
    $categories = get_categories(['hide_empty' => false]);
    $articles = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 50,
        'post_status' => ['publish', 'draft', 'private'],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $selected_category = $editing_post ? (wp_get_post_categories($editing_post->ID, ['fields' => 'ids'])[0] ?? 0) : 0;

    cp_render_page('articles', [
        'page_title' => __('Articles', 'client-portal'),
        'editing_post' => $editing_post,
        'categories' => $categories,
        'articles' => $articles,
        'selected_category' => $selected_category,
    ]);
}