<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_admin_menu()
{
    add_menu_page(
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        'edit_posts',
        'cp-dashboard',
        'cp_dashboard_page',
        'dashicons-admin-site',
        58
    );

    add_submenu_page(
        'cp-dashboard',
        __('Dashboard', 'client-portal'),
        __('Dashboard', 'client-portal'),
        'edit_posts',
        'cp-dashboard',
        'cp_dashboard_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Articles', 'client-portal'),
        __('Articles', 'client-portal'),
        'edit_posts',
        'cp-articles',
        'cp_articles_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Categories', 'client-portal'),
        __('Categories', 'client-portal'),
        'edit_posts',
        'cp-categories',
        'cp_categories_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Users', 'client-portal'),
        __('Users', 'client-portal'),
        'manage_options',
        'cp-users',
        'cp_users_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Analytics', 'client-portal'),
        __('Analytics', 'client-portal'),
        'edit_posts',
        'cp-analytics',
        'cp_analytics_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Settings', 'client-portal'),
        __('Settings', 'client-portal'),
        'manage_options',
        'cp-settings',
        'cp_settings_page'
    );
}
