<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Render Layout
|--------------------------------------------------------------------------
*/

function cp_render($template, $data = [])
{
    cp_require_login();

    if (!empty($data)) {
        extract($data);
    }

    ob_start();

    include CP_PATH . 'templates/' . $template . '.php';

    $content = ob_get_clean();

    include CP_PATH . 'templates/layout.php';
}