<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_article_oembed_allowed_html()
{
    $allowed_html = wp_kses_allowed_html('post');
    $allowed_html['iframe'] = [
        'allow' => true,
        'allowfullscreen' => true,
        'class' => true,
        'frameborder' => true,
        'height' => true,
        'loading' => true,
        'referrerpolicy' => true,
        'sandbox' => true,
        'src' => true,
        'style' => true,
        'title' => true,
        'width' => true,
    ];

    return $allowed_html;
}

function cp_render_article_blocks($blocks)
{
    $html = [];

    foreach ((array) $blocks as $block) {
        if (empty($block['type'])) {
            continue;
        }

        switch ($block['type']) {
            case 'heading':
                $level = min(6, max(1, absint($block['level'])));
                $html[] = sprintf('<h%1$d>%2$s</h%1$d>', $level, esc_html($block['content']));
                break;

            case 'paragraph':
                $html[] = '<p>' . nl2br(esc_html($block['content'])) . '</p>';
                break;

            case 'image':
                $html[] = cp_render_article_image_block($block);
                break;

            case 'video':
                $html[] = cp_render_article_video_block($block);
                break;
        }
    }

    return implode("\n\n", array_filter($html));
}

function cp_render_article_image_block($block)
{
    $alt = isset($block['alt']) ? $block['alt'] : '';
    $caption = isset($block['caption']) ? $block['caption'] : '';

    if ('media' === $block['source']) {
        $image = wp_get_attachment_image(absint($block['attachment_id']), 'large', false, ['alt' => $alt]);
    } else {
        $image = sprintf('<img src="%1$s" alt="%2$s" loading="lazy">', esc_url($block['url']), esc_attr($alt));
    }

    if (empty($image)) {
        return '';
    }

    $caption_html = '' !== $caption ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
    return '<figure class="cp-article-image">' . $image . $caption_html . '</figure>';
}

function cp_render_article_video_block($block)
{
    $url = $block['url'];
    $caption = isset($block['caption']) ? $block['caption'] : '';
    $embed = wp_oembed_get($url);

    if ($embed) {
        $content = wp_kses($embed, cp_article_oembed_allowed_html());
    } else {
        $content = sprintf(
            '<p><a href="%1$s" rel="noopener noreferrer">%2$s</a></p>',
            esc_url($url),
            esc_html($url)
        );
    }

    $caption_html = '' !== $caption ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
    return '<figure class="cp-article-video">' . $content . $caption_html . '</figure>';
}
