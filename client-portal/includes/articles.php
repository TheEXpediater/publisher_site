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
    if (!$deleted) {
        cp_set_temporary_notice('danger', __('The article could not be deleted.', 'client-portal'));
        cp_redirect('cp-articles', cp_article_library_redirect_args(cp_article_list_filters(), cp_current_article_page()));
    }

    $filters = cp_article_list_filters();
    $article_page = cp_current_article_page();
    $redirect_args = cp_article_library_redirect_args($filters, $article_page);
    $redirect_args['cp_notice'] = 'article_deleted';
    cp_redirect('cp-articles', $redirect_args);
}

function cp_handle_article_publish()
{
    if ('cp_publish_article' !== sanitize_key(cp_get_value('action'))) {
        cp_debug_log('Article publish failed', ['reason' => 'invalid_action']);
        cp_redirect('cp-articles', ['cp_notice' => 'article_publish_failed']);
    }

    $article_id = absint(cp_get_value('id'));
    if (!$article_id) {
        cp_debug_log('Article publish failed', ['reason' => 'invalid_article_id']);
        cp_redirect('cp-articles', ['cp_notice' => 'article_publish_failed']);
    }

    $nonce = sanitize_text_field(cp_get_value('_wpnonce'));
    if (!$nonce || !wp_verify_nonce($nonce, 'cp_publish_article_' . $article_id)) {
        cp_debug_log('Article publish failed', ['article_id' => $article_id, 'reason' => 'invalid_nonce']);
        cp_redirect('cp-articles', ['cp_notice' => 'article_publish_failed']);
    }
    $post = get_post($article_id);

    if (!$post instanceof WP_Post || 'post' !== $post->post_type || 'draft' !== $post->post_status) {
        cp_debug_log('Article publish failed', ['article_id' => $article_id, 'reason' => 'invalid_post_or_status']);
        cp_redirect('cp-articles', ['cp_notice' => 'article_publish_failed']);
    }

    if (!current_user_can('edit_post', $article_id) || !cp_can_publish_directly()) {
        cp_debug_log('Article publish action not allowed', ['article_id' => $article_id, 'user_id' => get_current_user_id()]);
        cp_redirect('cp-articles', ['cp_notice' => 'publish_not_allowed']);
    }

    $result = wp_update_post([
        'ID' => $article_id,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($result) || !$result) {
        cp_debug_log('Article publish failed', [
            'article_id' => $article_id,
            'error' => is_wp_error($result) ? $result->get_error_message() : 'wp_update_post returned an empty result',
        ]);
        cp_redirect('cp-articles', ['cp_notice' => 'article_publish_failed']);
    }

    cp_debug_log('Article published', ['article_id' => $article_id, 'user_id' => get_current_user_id()]);
    cp_redirect('cp-articles', ['cp_notice' => 'article_published']);
}

function cp_process_article_admin_actions()
{
    $page = cp_current_page();

    if ('cp-articles' === $page) {
        cp_handle_article_delete();
        return;
    }

    if (!in_array($page, ['cp-article-create', 'cp-article-edit'], true) || 'save' !== sanitize_key(cp_post_value('cp_article_builder_action'))) {
        return;
    }

    $post = null;
    if ('cp-article-edit' === $page) {
        $article_id = absint(cp_get_value('id'));
        $post = $article_id ? get_post($article_id) : null;
        if (!$post || 'post' !== $post->post_type) {
            cp_set_temporary_notice('danger', __('The article could not be found.', 'client-portal'));
            cp_redirect('cp-articles');
        }
    }

    $result = cp_save_article_builder_post($post);
    if (is_wp_error($result)) {
        cp_debug_log('Article save ended with an error', [
            'article_id' => $post instanceof WP_Post ? $post->ID : 0,
            'error' => $result->get_error_message(),
        ]);
        cp_set_temporary_notice('danger', $result->get_error_message());
        cp_redirect($page, $post ? ['id' => $post->ID] : []);
    }
}

function cp_article_list_filters()
{
    $status = cp_sanitize_status(cp_get_value('status'), '');

    return [
        'search' => sanitize_text_field(trim(cp_get_value('article_search'))),
        'status' => $status,
        'category' => absint(cp_get_value('category')),
    ];
}

function cp_current_article_page()
{
    $article_page = absint(cp_get_value('article_page'));
    return max(1, $article_page);
}

function cp_article_library_filter_args($filters)
{
    $args = [];

    if (!empty($filters['search'])) {
        $args['article_search'] = sanitize_text_field($filters['search']);
    }

    if (!empty($filters['status'])) {
        $args['status'] = cp_sanitize_status($filters['status'], '');
    }

    if (!empty($filters['category'])) {
        $args['category'] = absint($filters['category']);
    }

    return $args;
}

function cp_article_library_redirect_args($filters, $article_page = 1)
{
    $args = cp_article_library_filter_args($filters);
    $article_page = max(1, absint($article_page));

    $query = new WP_Query(cp_article_library_query_args($filters, $article_page));
    $max_pages = max(1, (int) $query->max_num_pages);
    wp_reset_postdata();

    if ($article_page > $max_pages) {
        $article_page = $max_pages;
    }

    if ($article_page > 1) {
        $args['article_page'] = $article_page;
    }

    return $args;
}

function cp_article_library_query_args($filters, $article_page = 1)
{
    $query_args = [
        'post_type' => 'post',
        'posts_per_page' => 10,
        'paged' => max(1, absint($article_page)),
        'post_status' => !empty($filters['status']) ? $filters['status'] : ['publish', 'draft', 'private'],
        'orderby' => 'date',
        'order' => 'DESC',
        's' => !empty($filters['search']) ? $filters['search'] : '',
        'ignore_sticky_posts' => true,
    ];

    if (!empty($filters['category'])) {
        $query_args['cat'] = absint($filters['category']);
    }

    if (!current_user_can('edit_others_posts')) {
        $query_args['author'] = get_current_user_id();
    }

    return $query_args;
}

function cp_articles_page()
{
    cp_require_capability('edit_posts');

    $filters = cp_article_list_filters();
    $article_page = cp_current_article_page();
    $article_query = new WP_Query(cp_article_library_query_args($filters, $article_page));
    $max_pages = max(1, (int) $article_query->max_num_pages);

    if ($article_page > $max_pages) {
        wp_reset_postdata();
        $redirect_args = cp_article_library_filter_args($filters);
        if ($max_pages > 1) {
            $redirect_args['article_page'] = $max_pages;
        }
        cp_redirect('cp-articles', $redirect_args);
    }

    $total_articles = (int) $article_query->found_posts;
    $article_count = count($article_query->posts);
    $article_start = $total_articles > 0 ? (($article_page - 1) * 10) + 1 : 0;
    $article_end = $total_articles > 0 ? min($article_start + $article_count - 1, $total_articles) : 0;

    cp_render_page('articles', [
        'page_title' => __('Articles', 'client-portal'),
        'categories' => get_categories(['hide_empty' => false]),
        'articles' => $article_query->posts,
        'filters' => $filters,
        'article_page' => $article_page,
        'article_per_page' => 10,
        'article_total' => $total_articles,
        'article_start' => $article_start,
        'article_end' => $article_end,
        'article_max_pages' => $max_pages,
        'notice' => cp_request_notice(),
    ]);
    wp_reset_postdata();
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

    $blocks = cp_get_article_blocks_for_editor($post);
    $hero_image = cp_get_article_hero_image($post);
    if (!$post) {
        $hero_image['source'] = 'media';
    }

    $selected_categories = $post ? wp_get_post_categories($post->ID) : [];
    $settings = cp_settings();
    $homepage_feature_id = cp_get_homepage_featured_article_id();
    $article_data = [
        'title' => $post ? $post->post_title : '',
        'excerpt' => $post ? $post->post_excerpt : '',
        'status' => $post ? $post->post_status : cp_sanitize_status($settings['default_status']),
        'category' => !empty($selected_categories) ? (int) $selected_categories[0] : 0,
        'hero_image' => $hero_image,
        'homepage_feature' => [
            'can_manage' => cp_can_manage_homepage_feature(),
            'is_featured' => $post instanceof WP_Post && $homepage_feature_id === absint($post->ID),
            'current_id' => $homepage_feature_id,
            'current_title' => $homepage_feature_id ? get_the_title($homepage_feature_id) : '',
        ],
    ];

    $template = $is_edit ? 'article-edit' : 'article-create';
    cp_render_page($template, [
        'page_title' => $is_edit ? __('Edit Article', 'client-portal') : __('Create Article', 'client-portal'),
        'builder_mode' => $mode,
        'article' => $post,
        'article_data' => $article_data,
        'blocks' => $blocks,
        'categories' => get_categories(['hide_empty' => false]),
        'notice' => cp_request_notice(),
    ]);
}

function cp_save_article_builder_post($post = null)
{
    cp_require_capability('edit_posts');
    check_admin_referer('cp_save_article_builder', 'cp_article_builder_nonce');

    if ($post) {
        cp_require_capability('edit_post', $post->ID);
    }

    cp_debug_log('Article save started', [
        'article_id' => $post instanceof WP_Post ? $post->ID : 0,
        'operation' => $post instanceof WP_Post ? 'update' : 'insert',
        'user_id' => get_current_user_id(),
    ]);

    $title = sanitize_text_field(cp_post_value('title'));
    if ('' === $title) {
        return new WP_Error('cp_article_title_required', __('Article title is required.', 'client-portal'));
    }

    $blocks_json = cp_post_value('cp_article_blocks');
    $blocks = cp_decode_article_blocks($blocks_json, true);
    if (is_wp_error($blocks)) {
        return $blocks;
    }
    $hero_image = cp_sanitize_article_hero_image();
    $can_manage_homepage_feature = cp_can_manage_homepage_feature();
    $homepage_feature_value = cp_post_value('homepage_featured_article', null);
    $homepage_feature_submitted = null !== $homepage_feature_value;
    $homepage_feature_enabled = $homepage_feature_submitted && '1' === sanitize_key((string) $homepage_feature_value);
    $homepage_feature_notice = '';

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
        $notice_code = 'article_updated';
    } else {
        $post_data['post_author'] = get_current_user_id();
        $saved_id = wp_insert_post(wp_slash($post_data), true);
        $notice_code = 'article_created';
    }

    if (is_wp_error($saved_id) || !$saved_id) {
        cp_debug_log('Article save failed', [
            'article_id' => $post instanceof WP_Post ? $post->ID : 0,
            'error' => is_wp_error($saved_id) ? $saved_id->get_error_message() : 'The portal returned an empty post ID',
        ]);
        return is_wp_error($saved_id)
            ? $saved_id
            : new WP_Error('cp_article_save_failed', __('The portal could not save the article.', 'client-portal'));
    }

    $category_id = absint(cp_post_value('category'));
    $category_result = wp_set_post_categories($saved_id, $category_id ? [$category_id] : []);
    if (is_wp_error($category_result)) {
        cp_debug_log('Article category save failed', ['article_id' => $saved_id, 'error' => $category_result->get_error_message()]);
    }
    update_post_meta($saved_id, '_cp_article_blocks', wp_slash($blocks));
    cp_save_article_hero_image($saved_id, $hero_image);

    if (!$can_manage_homepage_feature && $homepage_feature_submitted) {
        $homepage_feature_notice = 'homepage_feature_not_allowed';
    }

    if ($can_manage_homepage_feature) {
        if ($homepage_feature_enabled) {
            if ('publish' !== get_post_status($saved_id)) {
                $homepage_feature_notice = 'homepage_feature_requires_publish';
            } elseif (cp_is_homepage_featured_article($saved_id)) {
                $homepage_feature_notice = '';
            } elseif (cp_set_homepage_featured_article($saved_id)) {
                $homepage_feature_notice = 'homepage_feature_updated';
            } else {
                $homepage_feature_notice = 'homepage_feature_invalid_article';
            }
        } elseif (cp_is_homepage_featured_article($saved_id)) {
            cp_clear_homepage_featured_article();
            $homepage_feature_notice = 'homepage_feature_cleared';
        }
    }

    cp_debug_log('Article save completed', ['article_id' => $saved_id, 'status' => $status]);
    cp_redirect('cp-articles', ['cp_notice' => $homepage_feature_notice ?: $notice_code]);
}
