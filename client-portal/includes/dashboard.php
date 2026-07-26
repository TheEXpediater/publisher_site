<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_dashboard_page()
{
    cp_require_capability('read');

    $stats = cp_dashboard_statistics();
    $stats['recent_articles'] = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 5,
        'post_status' => current_user_can('edit_posts') ? ['publish', 'draft', 'private'] : ['publish'],
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $can_manage_homepage_feature = cp_can_manage_homepage_feature();

    cp_render_page('dashboard', [
        'page_title' => __('Dashboard', 'client-portal'),
        'stats' => $stats,
        'homepage_feature' => cp_get_homepage_featured_article(),
        'homepage_feature_categories' => $can_manage_homepage_feature ? cp_dashboard_homepage_feature_categories() : [],
        'can_manage_homepage_feature' => $can_manage_homepage_feature,
        'notice' => cp_request_notice(),
    ]);
}

function cp_dashboard_homepage_feature_article_data($post)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return null;
    }

    $thumbnail_url = has_post_thumbnail($post->ID) ? get_the_post_thumbnail_url($post->ID, 'thumbnail') : '';
    if (!$thumbnail_url) {
        $hero_url = esc_url_raw((string) get_post_meta($post->ID, '_cp_article_hero_image_url', true));
        $thumbnail_url = $hero_url && wp_http_validate_url($hero_url) ? $hero_url : '';
    }

    return [
        'id' => absint($post->ID),
        'title' => get_the_title($post),
        'category' => function_exists('cp_frontend_post_category') ? cp_frontend_post_category($post->ID) : __('General', 'client-portal'),
        'author' => get_the_author_meta('display_name', $post->post_author),
        'date' => get_the_date('', $post),
        'datetime' => get_the_date('c', $post),
        'permalink' => get_permalink($post),
        'editUrl' => current_user_can('edit_post', $post->ID) ? cp_admin_url('cp-article-edit', ['id' => $post->ID]) : '',
        'thumbnail' => $thumbnail_url,
        'isCurrent' => cp_is_homepage_featured_article($post->ID),
    ];
}

function cp_dashboard_homepage_feature_categories($limit = 500)
{
    $limit = min(500, max(1, absint($limit)));

    if (!function_exists('cp_enterprise_article_meta_query') || !function_exists('cp_is_enterprise_article')) {
        return [];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ]);

    $term_ids = [];
    foreach ($query->posts as $post_id) {
        $post_id = absint($post_id);
        if (!$post_id || !cp_is_enterprise_article($post_id)) {
            continue;
        }

        foreach (wp_get_post_categories($post_id) as $term_id) {
            $term_ids[] = absint($term_id);
        }
    }
    wp_reset_postdata();

    $term_ids = array_values(array_unique(array_filter($term_ids)));
    if (empty($term_ids)) {
        return [];
    }

    $terms = get_terms([
        'taxonomy' => 'category',
        'include' => $term_ids,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    return is_wp_error($terms) ? [] : $terms;
}

function cp_dashboard_homepage_feature_search_title_where($where, $query)
{
    global $wpdb;

    $search_title = $query->get('cp_feature_title_search');
    if (!is_string($search_title) || '' === trim($search_title)) {
        return $where;
    }

    $like = '%' . $wpdb->esc_like(trim($search_title)) . '%';
    return $where . $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s", $like);
}

function cp_dashboard_homepage_feature_query($search = '', $category_id = 0, $paged = 1, $per_page = 20)
{
    $per_page = min(20, max(15, absint($per_page)));
    $paged = max(1, absint($paged));

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ];

    $search = trim(sanitize_text_field((string) $search));
    if ('' !== $search) {
        $args['cp_feature_title_search'] = $search;
    }

    $category_id = absint($category_id);
    if ($category_id) {
        $args['cat'] = $category_id;
    }

    add_filter('posts_where', 'cp_dashboard_homepage_feature_search_title_where', 10, 2);
    $query = new WP_Query($args);
    remove_filter('posts_where', 'cp_dashboard_homepage_feature_search_title_where', 10);

    return $query;
}

function cp_ajax_homepage_feature_error($message, $status_code = 400)
{
    wp_send_json_error(['message' => sanitize_text_field($message)], $status_code);
}

function cp_ajax_require_homepage_feature_permission()
{
    if (!is_user_logged_in()) {
        cp_ajax_homepage_feature_error(__('Please sign in to continue.', 'client-portal'), 401);
    }

    if (!check_ajax_referer('cp_homepage_feature_picker', 'nonce', false)) {
        cp_ajax_homepage_feature_error(__('Your request expired. Please refresh the dashboard and try again.', 'client-portal'), 403);
    }

    if (!cp_can_manage_homepage_feature()) {
        cp_ajax_homepage_feature_error(__('Only editors and administrators may select the homepage featured article.', 'client-portal'), 403);
    }
}

function cp_ajax_search_homepage_feature_articles()
{
    cp_ajax_require_homepage_feature_permission();

    $search = isset($_POST['search']) && is_scalar($_POST['search']) ? sanitize_text_field(wp_unslash((string) $_POST['search'])) : '';
    $category_id = isset($_POST['category']) ? absint(wp_unslash((string) $_POST['category'])) : 0;
    $paged = isset($_POST['paged']) ? absint(wp_unslash((string) $_POST['paged'])) : 1;
    $per_page = isset($_POST['perPage']) ? absint(wp_unslash((string) $_POST['perPage'])) : 20;

    $query = cp_dashboard_homepage_feature_query($search, $category_id, $paged, $per_page);
    $articles = [];
    foreach ($query->posts as $post) {
        if ($post instanceof WP_Post && cp_is_enterprise_article($post)) {
            $article_data = cp_dashboard_homepage_feature_article_data($post);
            if (is_array($article_data)) {
                $articles[] = $article_data;
            }
        }
    }

    $total = (int) $query->found_posts;
    $from = $total > 0 ? (($paged - 1) * $query->query_vars['posts_per_page']) + 1 : 0;
    $to = $total > 0 ? min($from + count($articles) - 1, $total) : 0;
    wp_reset_postdata();

    wp_send_json_success([
        'articles' => $articles,
        'total' => $total,
        'from' => $from,
        'to' => $to,
        'currentPage' => max(1, $paged),
        'maxPages' => max(1, (int) $query->max_num_pages),
        'currentFeaturedId' => cp_get_homepage_featured_article_id(),
    ]);
}

function cp_ajax_set_homepage_featured_article()
{
    cp_ajax_require_homepage_feature_permission();

    $post_id = isset($_POST['postId']) ? absint(wp_unslash((string) $_POST['postId'])) : 0;
    if (!cp_set_homepage_featured_article($post_id)) {
        cp_ajax_homepage_feature_error(__('The selected homepage feature must be a published Enterprise article.', 'client-portal'));
    }

    $post = get_post($post_id);
    $article_data = cp_dashboard_homepage_feature_article_data($post);
    if (!is_array($article_data)) {
        cp_ajax_homepage_feature_error(__('The selected article could not be loaded.', 'client-portal'));
    }

    wp_send_json_success([
        'article' => $article_data,
        'message' => sprintf(
            /* translators: %s: Article title. */
            __('"%s" is now featured on the homepage.', 'client-portal'),
            get_the_title($post)
        ),
    ]);
}

function cp_ajax_clear_homepage_featured_article()
{
    cp_ajax_require_homepage_feature_permission();

    cp_clear_homepage_featured_article();

    wp_send_json_success([
        'message' => __('Homepage Featured Article cleared. The newest published Enterprise article will appear until another article is selected.', 'client-portal'),
    ]);
}

function cp_handle_homepage_feature_set()
{
    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!cp_can_manage_homepage_feature()) {
        cp_redirect('cp-dashboard', ['cp_notice' => 'homepage_feature_not_allowed']);
    }

    check_admin_referer('cp_set_homepage_featured_article', 'cp_homepage_feature_nonce');

    $post_id = absint(cp_post_value('homepage_featured_article_id'));
    if (!cp_set_homepage_featured_article($post_id)) {
        cp_redirect('cp-dashboard', ['cp_notice' => 'homepage_feature_invalid_article']);
    }

    cp_redirect('cp-dashboard', ['cp_notice' => 'homepage_feature_updated']);
}

function cp_handle_homepage_feature_clear()
{
    if (!is_user_logged_in()) {
        auth_redirect();
    }

    if (!cp_can_manage_homepage_feature()) {
        cp_redirect('cp-dashboard', ['cp_notice' => 'homepage_feature_not_allowed']);
    }

    check_admin_referer('cp_clear_homepage_featured_article', 'cp_homepage_feature_nonce');
    cp_clear_homepage_featured_article();
    cp_redirect('cp-dashboard', ['cp_notice' => 'homepage_feature_cleared']);
}
