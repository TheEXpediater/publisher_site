<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cp_articles', 'cp_articles_shortcode');

function cp_articles_shortcode()
{
    cp_require_login();

    ob_start();

    cp_render('articles');

    return ob_get_clean();
}