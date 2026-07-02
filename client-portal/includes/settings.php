<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cp_settings', 'cp_settings_shortcode');

function cp_settings_shortcode()
{
    cp_require_login();

    ob_start();

    cp_render('settings');

    return ob_get_clean();
}