<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cp_dashboard', 'cp_dashboard_shortcode');

function cp_dashboard_shortcode()
{
    cp_require_login();

    ob_start();

    cp_render('dashboard');

    return ob_get_clean();
}