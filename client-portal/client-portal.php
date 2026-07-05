<?php
/**
 * Plugin Name: Enterprise1979 Publisher Portal
 * Description: A custom WordPress admin publishing portal for Enterprise1979.
 * Version: 3.0.2
 * Author: Alvin
 * Text Domain: client-portal
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CP_VERSION', '3.0.2');
define('CP_PATH', plugin_dir_path(__FILE__));
define('CP_URL', plugin_dir_url(__FILE__));

require_once CP_PATH . 'includes/helpers.php';
require_once CP_PATH . 'includes/menu.php';
require_once CP_PATH . 'includes/dashboard.php';
require_once CP_PATH . 'includes/article-renderer.php';
require_once CP_PATH . 'includes/article-builder.php';
require_once CP_PATH . 'includes/articles.php';
require_once CP_PATH . 'includes/categories.php';
require_once CP_PATH . 'includes/users.php';
require_once CP_PATH . 'includes/analytics.php';
require_once CP_PATH . 'includes/settings.php';

function cp_initialize_plugin()
{
    load_plugin_textdomain('client-portal', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('plugins_loaded', 'cp_initialize_plugin');
add_action('admin_menu', 'cp_register_admin_menu');
add_action('admin_menu', 'cp_hide_default_admin_menus', 9999);
add_action('admin_enqueue_scripts', 'cp_enqueue_admin_assets');
add_action('admin_init', 'cp_register_settings');
add_action('admin_init', 'cp_process_article_admin_actions', 20);
add_action('admin_init', 'cp_process_category_admin_actions', 20);
add_action('admin_init', 'cp_process_user_admin_actions', 20);
add_action('admin_init', 'cp_restrict_portal_admin_access', 999);
add_action('admin_bar_menu', 'cp_restrict_portal_admin_bar', 9999);
add_filter('parent_file', 'cp_portal_parent_menu');
add_filter('submenu_file', 'cp_portal_submenu_highlight');
