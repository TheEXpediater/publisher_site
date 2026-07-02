<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_analytics_page()
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    $post_counts = wp_count_posts('post');
    $analytics = [
        'total_users' => count_users()['total_users'],
        'total_posts' => (int) $post_counts->publish + (int) $post_counts->draft + (int) $post_counts->private,
        'published' => (int) $post_counts->publish,
        'drafts' => (int) $post_counts->draft,
        'categories' => wp_count_terms('category'),
        'latest_posts' => get_posts([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post_status' => ['publish', 'draft', 'private'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]),
    ];

    cp_render_page('analytics', [
        'page_title' => __('Analytics', 'client-portal'),
        'analytics' => $analytics,
    ]);
}
