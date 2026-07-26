<?php
/**
 * Plugin Name: Enterprise1979 Publisher Portal
 * Description: A custom WordPress admin publishing portal for Enterprise1979.
 * Version: 3.5.4
 * Author: Alvin
 * Text Domain: client-portal
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CP_VERSION', '3.5.4');
define('CP_PATH', plugin_dir_path(__FILE__));
define('CP_URL', plugin_dir_url(__FILE__));
define('CP_HOMEPAGE_FEATURED_ARTICLE_OPTION', 'cp_homepage_featured_article_id');

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
require_once CP_PATH . 'includes/frontend-shortcodes.php';
require_once CP_PATH . 'includes/custom-login.php';

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
add_action('admin_post_cp_publish_article', 'cp_handle_article_publish');
add_action('admin_post_cp_set_homepage_featured_article', 'cp_handle_homepage_feature_set');
add_action('admin_post_cp_clear_homepage_featured_article', 'cp_handle_homepage_feature_clear');
add_action('wp_ajax_cp_search_homepage_feature_articles', 'cp_ajax_search_homepage_feature_articles');
add_action('wp_ajax_cp_set_homepage_featured_article', 'cp_ajax_set_homepage_featured_article');
add_action('wp_ajax_cp_clear_homepage_featured_article', 'cp_ajax_clear_homepage_featured_article');
add_action('admin_bar_menu', 'cp_white_label_admin_bar', 20);
add_action('admin_bar_menu', 'cp_restrict_portal_admin_bar', 9999);
add_action('wp_dashboard_setup', 'cp_remove_wordpress_dashboard_news_widget');
add_action('transition_post_status', 'cp_clear_homepage_feature_on_status_change', 10, 3);
add_action('trashed_post', 'cp_clear_homepage_feature_on_post_removed');
add_action('before_delete_post', 'cp_clear_homepage_feature_on_post_removed');
add_filter('parent_file', 'cp_portal_parent_menu');
add_filter('submenu_file', 'cp_portal_submenu_highlight');
add_filter('admin_footer_text', 'cp_white_label_admin_footer_text');
add_filter('update_footer', 'cp_white_label_admin_footer_version', 999);

register_activation_hook(__FILE__, 'cp_activate_plugin');
register_deactivation_hook(__FILE__, 'cp_deactivate_plugin');
