<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_get_analytics_data()
{
    $stats = cp_dashboard_statistics();

    return [
        'total_users' => $stats['users'],
        'total_posts' => $stats['total_articles'],
        'published' => $stats['published'],
        'drafts' => $stats['drafts'],
        'categories' => $stats['categories'],
        'latest_posts' => get_posts([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post_status' => current_user_can('edit_posts') ? ['publish', 'draft', 'private'] : ['publish'],
            'orderby' => 'date',
            'order' => 'DESC',
        ]),
    ];
}

function cp_analytics_page()
{
    cp_require_capability('read');

    cp_render_page('analytics', [
        'page_title' => __('Analytics', 'client-portal'),
        'analytics' => cp_get_analytics_data(),
    ]);
}
