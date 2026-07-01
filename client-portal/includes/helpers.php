<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| URLs
|--------------------------------------------------------------------------
*/

function cp_url($path = '')
{
    return plugin_dir_url(dirname(__FILE__)) . ltrim($path, '/');
}

function cp_path($path = '')
{
    return plugin_dir_path(dirname(__FILE__)) . ltrim($path, '/');
}

/*
|--------------------------------------------------------------------------
| Page URLs
|--------------------------------------------------------------------------
*/

function cp_dashboard_url()
{
    return site_url('/dashboard');
}

function cp_users_url()
{
    return site_url('/users');
}

function cp_articles_url()
{
    return site_url('/articles');
}

function cp_categories_url()
{
    return site_url('/categories');
}

function cp_settings_url()
{
    return site_url('/settings');
}

function cp_login_url()
{
    return site_url('/login');
}

/*
|--------------------------------------------------------------------------
| Active Sidebar
|--------------------------------------------------------------------------
*/

function cp_active($slug)
{
    global $post;

    if (!$post) {
        return '';
    }

    return ($post->post_name === $slug) ? 'active' : '';
}