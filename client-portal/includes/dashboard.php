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

    cp_render_page('dashboard', [
        'page_title' => __('Dashboard', 'client-portal'),
        'stats' => $stats,
    ]);
}
