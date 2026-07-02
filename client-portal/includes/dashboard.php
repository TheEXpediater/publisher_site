<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_dashboard_page()
{
    if (!cp_is_developer() && !current_user_can('read')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    $post_counts = wp_count_posts('post');
    $stats = [
        'total_articles' => (int) $post_counts->publish + (int) $post_counts->draft + (int) $post_counts->private,
        'published' => (int) $post_counts->publish,
        'drafts' => (int) $post_counts->draft,
        'users' => count_users()['total_users'],
        'categories' => wp_count_terms('category'),
        'recent_articles' => get_posts([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]),
    ];

    cp_render_page('dashboard', [
        'page_title' => __('Dashboard', 'client-portal'),
        'stats' => $stats,
    ]);
}