<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_url($path = '')
{
    return plugin_dir_url(dirname(__FILE__)) . ltrim($path, '/');
}

function cp_path($path = '')
{
    return plugin_dir_path(dirname(__FILE__)) . ltrim($path, '/');
}

function cp_admin_url($page)
{
    return admin_url('admin.php?page=' . $page);
}

function cp_is_active_page($slug)
{
    $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

    return $current_page === $slug ? 'active' : '';
}

function cp_render_page($template, $data = [])
{
    if (!empty($data)) {
        extract($data);
    }

    ob_start();
    include CP_PATH . 'templates/' . $template . '.php';
    $content = ob_get_clean();

    include CP_PATH . 'templates/layout.php';
}

function cp_enqueue_admin_assets($hook)
{
    if (strpos($hook, 'cp-') === false && 'toplevel_page_cp-dashboard' !== $hook) {
        return;
    }

    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
    wp_enqueue_style('cp-style', CP_URL . 'assets/css/style.css', [], '1.0');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], null, true);
    wp_enqueue_script('cp-app', CP_URL . 'assets/js/app.js', [], '1.0', true);
}

function cp_bootstrap_plugin()
{
    require_once CP_PATH . 'includes/menu.php';
    require_once CP_PATH . 'includes/dashboard.php';
    require_once CP_PATH . 'includes/articles.php';
    require_once CP_PATH . 'includes/categories.php';
    require_once CP_PATH . 'includes/users.php';
    require_once CP_PATH . 'includes/analytics.php';
    require_once CP_PATH . 'includes/settings.php';

    add_action('admin_menu', 'cp_register_admin_menu');
    add_action('admin_enqueue_scripts', 'cp_enqueue_admin_assets');
}

add_action('plugins_loaded', 'cp_bootstrap_plugin');