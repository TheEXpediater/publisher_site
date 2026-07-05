<?php

if (!defined('ABSPATH')) {
    exit;
}

$builder_mode = 'edit';
cp_render_template('article-builder', [
    'builder_mode' => $builder_mode,
    'article' => $article,
    'article_data' => $article_data,
    'blocks' => $blocks,
    'categories' => $categories,
    'notice' => $notice,
]);
