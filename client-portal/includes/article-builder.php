<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_article_block_error($message)
{
    return new WP_Error('cp_invalid_article_blocks', $message);
}

function cp_article_block_value($block, $key, $default = '')
{
    return isset($block[$key]) && is_scalar($block[$key]) ? (string) $block[$key] : $default;
}

function cp_sanitize_article_blocks($blocks, $require_content = true)
{
    if (!is_array($blocks)) {
        return cp_article_block_error(__('Article blocks must be submitted as a list.', 'client-portal'));
    }

    if (count($blocks) > 100) {
        return cp_article_block_error(__('An article cannot contain more than 100 blocks.', 'client-portal'));
    }

    $sanitized = [];
    foreach ($blocks as $index => $block) {
        if (!is_array($block)) {
            return cp_article_block_error(__('One of the submitted blocks is invalid.', 'client-portal'));
        }

        $type = sanitize_key(cp_article_block_value($block, 'type'));
        if (!in_array($type, ['heading', 'paragraph', 'image', 'video'], true)) {
            return cp_article_block_error(__('An unsupported article block was submitted.', 'client-portal'));
        }

        if ('heading' === $type) {
            $content = sanitize_text_field(cp_article_block_value($block, 'content'));
            $level = absint(cp_article_block_value($block, 'level', '2'));
            if ($level < 1 || $level > 6 || ($require_content && '' === $content)) {
                return cp_article_block_error(sprintf(__('Heading block %d is incomplete.', 'client-portal'), $index + 1));
            }
            $sanitized[] = ['type' => 'heading', 'level' => $level, 'content' => $content];
            continue;
        }

        if ('paragraph' === $type) {
            $content = sanitize_textarea_field(cp_article_block_value($block, 'content'));
            if ($require_content && '' === $content) {
                return cp_article_block_error(sprintf(__('Paragraph block %d is empty.', 'client-portal'), $index + 1));
            }
            $sanitized[] = ['type' => 'paragraph', 'content' => $content];
            continue;
        }

        if ('image' === $type) {
            $source = 'url' === sanitize_key(cp_article_block_value($block, 'source')) ? 'url' : 'media';
            $image = [
                'type' => 'image',
                'source' => $source,
                'attachment_id' => absint(cp_article_block_value($block, 'attachment_id')),
                'url' => esc_url_raw(cp_article_block_value($block, 'url')),
                'alt' => sanitize_text_field(cp_article_block_value($block, 'alt')),
                'caption' => sanitize_text_field(cp_article_block_value($block, 'caption')),
            ];

            if ('media' === $source && $require_content && (!$image['attachment_id'] || !wp_attachment_is_image($image['attachment_id']))) {
                return cp_article_block_error(sprintf(__('Image block %d needs a Media Library image.', 'client-portal'), $index + 1));
            }
            if ('url' === $source && $require_content && ('' === $image['url'] || false === wp_http_validate_url($image['url']))) {
                return cp_article_block_error(sprintf(__('Image block %d needs a valid image URL.', 'client-portal'), $index + 1));
            }
            $sanitized[] = $image;
            continue;
        }

        $url = esc_url_raw(cp_article_block_value($block, 'url'));
        if ($require_content && ('' === $url || false === wp_http_validate_url($url))) {
            return cp_article_block_error(sprintf(__('Video URL block %d needs a valid URL.', 'client-portal'), $index + 1));
        }
        $sanitized[] = [
            'type' => 'video',
            'url' => $url,
            'caption' => sanitize_text_field(cp_article_block_value($block, 'caption')),
        ];
    }

    if ($require_content && empty($sanitized)) {
        return cp_article_block_error(__('Add at least one content block before saving.', 'client-portal'));
    }

    return $sanitized;
}

function cp_decode_article_blocks($json, $require_content = true)
{
    if (!is_string($json) || '' === trim($json)) {
        return $require_content ? cp_article_block_error(__('Add at least one content block before saving.', 'client-portal')) : [];
    }

    $blocks = json_decode($json, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        return cp_article_block_error(__('The article blocks could not be read. Please try again.', 'client-portal'));
    }

    return cp_sanitize_article_blocks($blocks, $require_content);
}

function cp_get_article_blocks_for_editor($post = null)
{
    if (!$post instanceof WP_Post) {
        return [];
    }

    $blocks = get_post_meta($post->ID, '_cp_article_blocks', true);
    if (is_array($blocks)) {
        $sanitized = cp_sanitize_article_blocks($blocks, false);
        if (!is_wp_error($sanitized) && !empty($sanitized)) {
            return $sanitized;
        }
    }

    if ('' !== trim($post->post_content)) {
        return [[
            'type' => 'paragraph',
            'content' => wp_strip_all_tags($post->post_content, true),
        ]];
    }

    return [];
}

function cp_get_article_hero_image($post = null)
{
    $hero = [
        'source' => 'none',
        'attachment_id' => 0,
        'url' => '',
        'preview_url' => '',
    ];

    if (!$post instanceof WP_Post) {
        return $hero;
    }

    $attachment_id = get_post_thumbnail_id($post->ID);
    if ($attachment_id && wp_attachment_is_image($attachment_id)) {
        $hero['source'] = 'media';
        $hero['attachment_id'] = $attachment_id;
        $hero['preview_url'] = (string) wp_get_attachment_image_url($attachment_id, 'medium_large');
        return $hero;
    }

    $hero_url = esc_url_raw((string) get_post_meta($post->ID, '_cp_article_hero_image_url', true));
    if ($hero_url && wp_http_validate_url($hero_url)) {
        $hero['source'] = 'url';
        $hero['url'] = $hero_url;
        $hero['preview_url'] = $hero_url;
    }

    return $hero;
}

function cp_sanitize_article_hero_image()
{
    $source = sanitize_key(cp_post_value('hero_image_source', 'none'));
    if (!in_array($source, ['media', 'url', 'none'], true)) {
        $source = 'none';
    }

    $attachment_id = absint(cp_post_value('hero_attachment_id'));
    $url = esc_url_raw(cp_post_value('hero_image_url'));

    if ('media' === $source && (!$attachment_id || !wp_attachment_is_image($attachment_id))) {
        $source = 'none';
        $attachment_id = 0;
    }

    if ('url' === $source && (!$url || !wp_http_validate_url($url))) {
        $source = 'none';
        $url = '';
    }

    return [
        'source' => $source,
        'attachment_id' => $attachment_id,
        'url' => $url,
    ];
}

function cp_save_article_hero_image($post_id, $hero)
{
    $post_id = absint($post_id);
    if (!$post_id || !is_array($hero)) {
        return;
    }

    if ('media' === $hero['source']) {
        set_post_thumbnail($post_id, absint($hero['attachment_id']));
        delete_post_meta($post_id, '_cp_article_hero_image_url');
        return;
    }

    if ('url' === $hero['source']) {
        delete_post_thumbnail($post_id);
        update_post_meta($post_id, '_cp_article_hero_image_url', esc_url_raw($hero['url']));
        return;
    }

    delete_post_thumbnail($post_id);
    delete_post_meta($post_id, '_cp_article_hero_image_url');
}

function cp_article_block_label($type)
{
    $labels = [
        'heading' => __('Heading', 'client-portal'),
        'paragraph' => __('Paragraph', 'client-portal'),
        'image' => __('Image', 'client-portal'),
        'video' => __('Video URL', 'client-portal'),
    ];

    return isset($labels[$type]) ? $labels[$type] : __('Content Block', 'client-portal');
}

function cp_render_article_block_editor($block, $index)
{
    $type = isset($block['type']) ? sanitize_key($block['type']) : 'paragraph';
    $block_id = 'cp-block-' . absint($index) . '-' . wp_rand(1000, 9999);
    ?>
    <article class="cp-builder-block" data-cp-block data-block-type="<?php echo esc_attr($type); ?>">
        <div class="cp-builder-block-head">
            <div class="cp-builder-block-title"><span class="cp-block-number"><?php echo esc_html($index + 1); ?></span><div><strong><?php echo esc_html(cp_article_block_label($type)); ?></strong><small><?php esc_html_e('Content block', 'client-portal'); ?></small></div></div>
            <div class="cp-builder-block-actions">
                <button type="button" class="cp-icon-button" data-cp-move-up title="<?php esc_attr_e('Move Up', 'client-portal'); ?>"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="cp-icon-button" data-cp-move-down title="<?php esc_attr_e('Move Down', 'client-portal'); ?>"><i class="bi bi-arrow-down"></i></button>
                <button type="button" class="cp-icon-button" data-cp-duplicate title="<?php esc_attr_e('Duplicate', 'client-portal'); ?>"><i class="bi bi-copy"></i></button>
                <button type="button" class="cp-icon-button cp-icon-button-danger" data-cp-remove title="<?php esc_attr_e('Remove', 'client-portal'); ?>"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="cp-builder-block-body">
            <?php if ('heading' === $type) : ?>
                <div class="row g-3"><div class="col-md-3"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-level"><?php esc_html_e('Heading level', 'client-portal'); ?></label><select class="form-select" id="<?php echo esc_attr($block_id); ?>-level" data-cp-field="level"><?php for ($level = 1; $level <= 6; $level++) : ?><option value="<?php echo esc_attr($level); ?>" <?php selected(isset($block['level']) ? $block['level'] : 2, $level); ?>><?php echo esc_html('H' . $level); ?></option><?php endfor; ?></select></div><div class="col-md-9"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-content"><?php esc_html_e('Heading text', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-content" data-cp-field="content" value="<?php echo esc_attr(isset($block['content']) ? $block['content'] : ''); ?>" required></div></div>
            <?php elseif ('paragraph' === $type) : ?>
                <label class="form-label" for="<?php echo esc_attr($block_id); ?>-content"><?php esc_html_e('Paragraph content', 'client-portal'); ?></label><textarea class="form-control" id="<?php echo esc_attr($block_id); ?>-content" data-cp-field="content" rows="6" required><?php echo esc_textarea(isset($block['content']) ? $block['content'] : ''); ?></textarea>
            <?php elseif ('image' === $type) : cp_render_article_image_editor_fields($block, $block_id); ?>
            <?php elseif ('video' === $type) : ?>
                <div class="mb-3"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-url"><?php esc_html_e('Video URL', 'client-portal'); ?></label><input type="url" class="form-control" id="<?php echo esc_attr($block_id); ?>-url" data-cp-field="url" value="<?php echo esc_attr(isset($block['url']) ? $block['url'] : ''); ?>" required><div class="form-text"><?php esc_html_e('Paste a video URL from YouTube, Vimeo, Facebook, TikTok, or another supported oEmbed provider.', 'client-portal'); ?></div></div><div><label class="form-label" for="<?php echo esc_attr($block_id); ?>-caption"><?php esc_html_e('Caption (optional)', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-caption" data-cp-field="caption" value="<?php echo esc_attr(isset($block['caption']) ? $block['caption'] : ''); ?>"></div>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_render_article_image_editor_fields($block, $block_id)
{
    $source = isset($block['source']) && 'url' === $block['source'] ? 'url' : 'media';
    $attachment_id = isset($block['attachment_id']) ? absint($block['attachment_id']) : 0;
    $preview_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
    ?>
    <div class="mb-3"><label class="form-label"><?php esc_html_e('Image Source', 'client-portal'); ?></label><div class="cp-source-selector"><label><input type="radio" name="<?php echo esc_attr($block_id); ?>-source" data-cp-image-source value="media" <?php checked($source, 'media'); ?>> <span><?php esc_html_e('Upload / Media Library', 'client-portal'); ?></span></label><label><input type="radio" name="<?php echo esc_attr($block_id); ?>-source" data-cp-image-source value="url" <?php checked($source, 'url'); ?>> <span><?php esc_html_e('Image URL', 'client-portal'); ?></span></label></div></div>
    <input type="hidden" data-cp-field="source" value="<?php echo esc_attr($source); ?>"><input type="hidden" data-cp-field="attachment_id" value="<?php echo esc_attr($attachment_id); ?>">
    <div data-cp-media-fields<?php if ('media' !== $source) : ?> hidden<?php endif; ?>><button type="button" class="btn btn-outline-primary" data-cp-select-image><i class="bi bi-images"></i> <?php esc_html_e('Select Image', 'client-portal'); ?></button><div class="cp-image-preview" data-cp-image-preview<?php if (!$preview_url) : ?> hidden<?php endif; ?>><?php if ($preview_url) : ?><img src="<?php echo esc_url($preview_url); ?>" alt=""><?php endif; ?></div></div>
    <div data-cp-url-fields<?php if ('url' !== $source) : ?> hidden<?php endif; ?>><label class="form-label" for="<?php echo esc_attr($block_id); ?>-url"><?php esc_html_e('Image URL', 'client-portal'); ?></label><input type="url" class="form-control" id="<?php echo esc_attr($block_id); ?>-url" data-cp-field="url" value="<?php echo esc_attr(isset($block['url']) ? $block['url'] : ''); ?>"></div>
    <div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-alt"><?php esc_html_e('Alt text', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-alt" data-cp-field="alt" value="<?php echo esc_attr(isset($block['alt']) ? $block['alt'] : ''); ?>"></div><div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-caption"><?php esc_html_e('Caption (optional)', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-caption" data-cp-field="caption" value="<?php echo esc_attr(isset($block['caption']) ? $block['caption'] : ''); ?>"></div></div>
    <?php
}
