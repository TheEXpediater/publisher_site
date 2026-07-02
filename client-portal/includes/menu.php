<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_admin_menu()
{
    if (cp_is_developer()) {
        return;
    }

    remove_menu_page('index.php');
    remove_menu_page('edit.php');
    remove_menu_page('upload.php');
    remove_menu_page('edit.php?post_type=page');
    remove_menu_page('edit-comments.php');
    remove_menu_page('themes.php');
    remove_menu_page('plugins.php');
    remove_menu_page('users.php');
    remove_menu_page('tools.php');
    remove_menu_page('options-general.php');
    remove_menu_page('profile.php');
    remove_menu_page('update-core.php');

    add_menu_page(
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        'read',
        'cp-dashboard',
        'cp_dashboard_page',
        'dashicons-admin-site',
        58
    );

    add_submenu_page(
        'cp-dashboard',
        __('Dashboard', 'client-portal'),
        __('Dashboard', 'client-portal'),
        'read',
        'cp-dashboard',
        'cp_dashboard_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Articles', 'client-portal'),
        __('Articles', 'client-portal'),
        'read',
        'cp-articles',
        'cp_articles_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Categories', 'client-portal'),
        __('Categories', 'client-portal'),
        'read',
        'cp-categories',
        'cp_categories_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Users', 'client-portal'),
        __('Users', 'client-portal'),
        'read',
        'cp-users',
        'cp_users_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Analytics', 'client-portal'),
        __('Analytics', 'client-portal'),
        'read',
        'cp-analytics',
        'cp_analytics_page'
    );

    add_submenu_page(
        'cp-dashboard',
        __('Settings', 'client-portal'),
        __('Settings', 'client-portal'),
        'read',
        'cp-settings',
        'cp_settings_page'
    );
}
