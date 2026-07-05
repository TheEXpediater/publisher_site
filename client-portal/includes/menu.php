<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_admin_menu()
{
    add_menu_page(
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        __('Enterprise1979 Publisher Portal', 'client-portal'),
        'read',
        'cp-dashboard',
        'cp_dashboard_page',
        'dashicons-media-document',
        58
    );

    add_submenu_page('cp-dashboard', __('Dashboard', 'client-portal'), __('Dashboard', 'client-portal'), 'read', 'cp-dashboard', 'cp_dashboard_page');
    add_submenu_page('cp-dashboard', __('Articles', 'client-portal'), __('Articles', 'client-portal'), 'edit_posts', 'cp-articles', 'cp_articles_page');
    add_submenu_page(null, __('Create Article', 'client-portal'), __('Create Article', 'client-portal'), 'edit_posts', 'cp-article-create', 'cp_article_create_page');
    add_submenu_page(null, __('Edit Article', 'client-portal'), __('Edit Article', 'client-portal'), 'edit_posts', 'cp-article-edit', 'cp_article_edit_page');
    add_submenu_page('cp-dashboard', __('Categories', 'client-portal'), __('Categories', 'client-portal'), 'manage_categories', 'cp-categories', 'cp_categories_page');
    add_submenu_page('cp-dashboard', __('Users', 'client-portal'), __('Users', 'client-portal'), 'list_users', 'cp-users', 'cp_users_page');
    add_submenu_page('cp-dashboard', __('Analytics', 'client-portal'), __('Analytics', 'client-portal'), 'read', 'cp-analytics', 'cp_analytics_page');
    add_submenu_page('cp-dashboard', __('Settings', 'client-portal'), __('Settings', 'client-portal'), 'manage_options', 'cp-settings', 'cp_settings_page');
}

function cp_hide_default_admin_menus()
{
    if (!cp_is_portal_only_user()) {
        return;
    }

    global $menu;

    foreach ((array) $menu as $item) {
        $slug = isset($item[2]) ? $item[2] : '';
        if ('cp-dashboard' !== $slug) {
            remove_menu_page($slug);
        }
    }
}

function cp_portal_parent_menu($parent_file)
{
    if (in_array(cp_current_page(), ['cp-article-create', 'cp-article-edit'], true)) {
        return 'cp-dashboard';
    }

    return $parent_file;
}

function cp_portal_submenu_highlight($submenu_file)
{
    if (in_array(cp_current_page(), ['cp-article-create', 'cp-article-edit'], true)) {
        return 'cp-articles';
    }

    return $submenu_file;
}
