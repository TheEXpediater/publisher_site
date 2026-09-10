<?php

if (!defined('ABSPATH')) {
    exit;
}

$builder_mode = 'create';
cp_render_template('page-builder', [
    'builder_mode' => $builder_mode,
    'page_post' => $page_post,
    'page_data' => $page_data,
    'blocks' => $blocks,
    'notice' => $notice,
]);
