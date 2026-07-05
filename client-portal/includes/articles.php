<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_handle_article_delete()
{
    $action = sanitize_key(cp_get_value('action'));
    $article_id = absint(cp_get_value('id'));

    if ('delete' !== $action || !$article_id) {
        return;
    }

    $post = get_post($article_id);
    if (!$post || 'post' !== $post->post_type) {
        cp_redirect('cp-articles');
    }

    cp_require_capability('delete_post', $article_id);
    check_admin_referer('cp_delete_article_' . $article_id);
    $deleted = wp_delete_post($article_id, true);
    cp_redirect('cp-articles', $deleted ? ['cp_notice' => 'article-deleted'] : []);
}

function cp_article_list_filters()
{
    $status = cp_sanitize_status(cp_get_value('status'), '');

    return [
        'search' => sanitize_text_field(cp_get_value('article_search')),
        'status' => $status,
        'category' => absint(cp_get_value('category')),
    ];
}

function cp_articles_page()
{
    cp_require_capability('edit_posts');
    cp_handle_article_delete();

    $settings = cp_settings();
    $filters = cp_article_list_filters();
    $query = [
        'post_type' => 'post',
        'posts_per_page' => absint($settings['items_per_page']),
        'post_status' => $filters['status'] ? $filters['status'] : ['publish', 'draft', 'private'],
        'orderby' => 'date',
        'order' => 'DESC',
        's' => $filters['search'],
    ];

    if ($filters['category']) {
        $query['cat'] = $filters['category'];
    }
    if (!current_user_can('edit_others_posts')) {
        $query['author'] = get_current_user_id();
    }

    cp_render_page('articles', [
        'page_title' => __('Articles', 'client-portal'),
        'categories' => get_categories(['hide_empty' => false]),
        'articles' => get_posts($query),
        'filters' => $filters,
        'notice' => cp_request_notice(),
    ]);
}

function cp_article_create_page()
{
    cp_render_article_builder_page('create');
}

function cp_article_edit_page()
{
    cp_render_article_builder_page('edit');
}

function cp_render_article_builder_page($mode)
{
    cp_require_capability('edit_posts');
    $is_edit = 'edit' === $mode;
    $article_id = absint(cp_get_value('id'));
    $post = $is_edit && $article_id ? get_post($article_id) : null;

    if ($is_edit && (!$post || 'post' !== $post->post_type)) {
        cp_redirect('cp-articles');
    }
    if ($post) {
        cp_require_capability('edit_post', $post->ID);
    }

    $notice = null;
    if ('save' === sanitize_key(cp_post_value('cp_article_builder_action'))) {
        $result = cp_save_article_builder_post($post);
        if (is_wp_error($result)) {
            $notice = ['type' => 'danger', 'message' => $result->get_error_message()];
        }
    }

    $blocks = cp_get_article_blocks_for_editor($post);
    if ($notice && '' !== cp_post_value('cp_article_blocks')) {
        $posted_blocks = cp_decode_article_blocks(cp_post_value('cp_article_blocks'), false);
        if (!is_wp_error($posted_blocks)) {
            $blocks = $posted_blocks;
        }
    }

    $selected_categories = $post ? wp_get_post_categories($post->ID) : [];
    $settings = cp_settings();
    $article_data = [
        'title' => $post ? $post->post_title : '',
        'excerpt' => $post ? $post->post_excerpt : '',
        'status' => $post ? $post->post_status : cp_sanitize_status($settings['default_status']),
        'category' => !empty($selected_categories) ? (int) $selected_categories[0] : 0,
    ];

    if ($notice) {
        $article_data = [
            'title' => sanitize_text_field(cp_post_value('title')),
            'excerpt' => sanitize_textarea_field(cp_post_value('excerpt')),
            'status' => cp_sanitize_status(cp_post_value('status'), 'draft'),
            'category' => absint(cp_post_value('category')),
        ];
    }

    $template = $is_edit ? 'article-edit' : 'article-create';
    cp_render_page($template, [
        'page_title' => $is_edit ? __('Edit Article', 'client-portal') : __('Create Article', 'client-portal'),
        'builder_mode' => $mode,
        'article' => $post,
        'article_data' => $article_data,
        'blocks' => $blocks,
        'categories' => get_categories(['hide_empty' => false]),
        'notice' => $notice,
    ]);
}

function cp_save_article_builder_post($post = null)
{
    cp_require_capability('edit_posts');
    check_admin_referer('cp_save_article_builder', 'cp_article_builder_nonce');

    if ($post) {
        cp_require_capability('edit_post', $post->ID);
    }

    $title = sanitize_text_field(cp_post_value('title'));
    if ('' === $title) {
        return new WP_Error('cp_article_title_required', __('Article title is required.', 'client-portal'));
    }

    $blocks_json = cp_post_value('cp_article_blocks');
    $blocks = cp_decode_article_blocks($blocks_json, true);
    if (is_wp_error($blocks)) {
        return $blocks;
    }

    $status = cp_sanitize_status(cp_post_value('status'), 'draft');
    if ('publish' === $status && !cp_can_publish_directly()) {
        $status = 'draft';
    }

    $post_data = [
        'post_type' => 'post',
        'post_title' => $title,
        'post_excerpt' => sanitize_textarea_field(cp_post_value('excerpt')),
        'post_status' => $status,
        'post_content' => cp_render_article_blocks($blocks),
    ];

    if ($post) {
        $post_data['ID'] = $post->ID;
        $saved_id = wp_update_post(wp_slash($post_data), true);
        $notice_code = 'article-updated';
    } else {
        $post_data['post_author'] = get_current_user_id();
        $saved_id = wp_insert_post(wp_slash($post_data), true);
        $notice_code = 'article-created';
    }

    if (is_wp_error($saved_id)) {
        return $saved_id;
    }

    $category_id = absint(cp_post_value('category'));
    wp_set_post_categories($saved_id, $category_id ? [$category_id] : []);
    update_post_meta($saved_id, '_cp_article_blocks', wp_slash($blocks));
    cp_redirect('cp-articles', ['cp_notice' => $notice_code]);
}
