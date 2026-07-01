<?php
/*
Plugin Name: Client Portal
Description: Enterprise 1979 Portal
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CP_PATH', plugin_dir_path(__FILE__));
define('CP_URL', plugin_dir_url(__FILE__));

require_once CP_PATH . 'includes/router.php';
require_once CP_PATH . 'includes/auth.php';
require_once CP_PATH . 'includes/users.php';