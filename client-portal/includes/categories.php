<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cp_categories', 'cp_categories_shortcode');

function cp_categories_shortcode()
{
    cp_require_login();

    ob_start();

    cp_render('categories');

    return ob_get_clean();
}