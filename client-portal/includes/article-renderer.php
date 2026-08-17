<?php

if (!defined('ABSPATH')) {
    exit;
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
                $level = min(6, max(1, absint(isset($block['level']) ? $block['level'] : 2)));
                $heading_content = function_exists('cp_sanitize_rich_heading_html')
                    ? cp_sanitize_rich_heading_html(isset($block['content']) ? $block['content'] : '')
                    : esc_html(isset($block['content']) ? $block['content'] : '');
                $allowed_heading_html = function_exists('cp_rich_heading_allowed_html') ? cp_rich_heading_allowed_html() : wp_kses_allowed_html('post');
                $html[] = sprintf('<h%1$d>%2$s</h%1$d>', $level, wp_kses($heading_content, $allowed_heading_html));
                break;

            case 'paragraph':
                $html[] = cp_render_article_paragraph_block(isset($block['content']) ? $block['content'] : '');
                break;

            case 'image':
                $html[] = cp_render_article_image_block($block);
                break;

            case 'video':
                $html[] = cp_render_article_video_block($block);
                break;

            case 'table':
                $html[] = cp_render_article_table_block($block);
                break;
        }
    }

    return implode("\n\n", array_filter($html));
}

function cp_render_article_paragraph_block($content)
{
    $content = is_scalar($content) ? (string) $content : '';
    $clean_content = function_exists('cp_sanitize_rich_paragraph_html')
        ? cp_sanitize_rich_paragraph_html($content)
        : wp_kses_post($content);

    if ('' === trim($clean_content)) {
        return '';
    }

    if (false === strpos($clean_content, '<')) {
        return wpautop(esc_html($clean_content));
    }

    $allowed_html = function_exists('cp_rich_paragraph_allowed_html') ? cp_rich_paragraph_allowed_html() : wp_kses_allowed_html('post');

    if (preg_match('/<(?:p|blockquote|ul|ol|table)\b/i', $clean_content)) {
        return wp_kses($clean_content, $allowed_html);
    }

    return '<p>' . wp_kses($clean_content, $allowed_html) . '</p>';
}

function cp_render_article_image_block($block)
{
    $alt = isset($block['alt']) ? $block['alt'] : '';
    $caption = isset($block['caption']) ? $block['caption'] : '';
    $source = isset($block['source']) ? $block['source'] : 'media';

    if ('media' === $source) {
        $image = wp_get_attachment_image(absint(isset($block['attachment_id']) ? $block['attachment_id'] : 0), 'large', false, ['alt' => $alt]);
    } else {
        $image = sprintf('<img src="%1$s" alt="%2$s" loading="lazy">', esc_url(isset($block['url']) ? $block['url'] : ''), esc_attr($alt));
    }

    if (empty($image)) {
        return '';
    }

    $caption_html = '' !== $caption ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
    return '<figure class="cp-article-image">' . $image . $caption_html . '</figure>';
}

function cp_render_article_video_block($block)
{
    $url = isset($block['url']) ? $block['url'] : '';
    $caption = isset($block['caption']) ? $block['caption'] : '';
    // WordPress resolves this core embed shortcode while rendering content. Keeping
    // the URL unresolved here prevents remote oEmbed requests during article saves.
    $content = '[embed]' . esc_url_raw($url) . '[/embed]';

    $caption_html = '' !== $caption ? '<figcaption>' . esc_html($caption) . '</figcaption>' : '';
    return '<figure class="cp-article-video">' . $content . $caption_html . '</figure>';
}

function cp_render_article_table_block($block)
{
    $sanitized = function_exists('cp_sanitize_table_block') ? cp_sanitize_table_block($block, 0, false) : $block;
    if (is_wp_error($sanitized) || empty($sanitized['rows']) || !is_array($sanitized['rows'])) {
        return '';
    }

    $rows = array_values($sanitized['rows']);
    $caption = isset($sanitized['caption']) ? $sanitized['caption'] : '';
    $has_header = !empty($sanitized['has_header']);
    $header_row = $has_header ? array_shift($rows) : [];

    ob_start();
    ?>
    <figure class="cp-article-table">
        <div class="cp-article-table-scroll" tabindex="0" role="region" aria-label="<?php echo esc_attr($caption ?: __('Article table', 'client-portal')); ?>">
            <table>
                <?php if ('' !== $caption) : ?><caption><?php echo esc_html($caption); ?></caption><?php endif; ?>
                <?php if ($has_header && !empty($header_row)) : ?>
                    <thead><tr><?php foreach ($header_row as $cell) : ?><th scope="col"><?php echo esc_html($cell); ?></th><?php endforeach; ?></tr></thead>
                <?php endif; ?>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr><?php foreach ((array) $row as $cell) : ?><td><?php echo esc_html($cell); ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </figure>
    <?php
    return ob_get_clean();
}
