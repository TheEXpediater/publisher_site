<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Page Builder: a small, structured content system for plugin-managed
 * WordPress Pages (About Us, Staff, Join the Publication, and future
 * institutional pages) - never a general-purpose page builder, and
 * deliberately not a competitor to Elementor for the rest of the site.
 *
 * Reuses the Article Builder's own rich-text sanitizers (includes/article-builder.php,
 * required before this file) for the Heading/Rich Text blocks rather than
 * inventing a second HTML-sanitization stack, and follows the same
 * block-array/JSON-meta shape article blocks already use.
 */

define('CP_PAGE_MANAGED_META', '_cp_managed_page');
define('CP_PAGE_BLOCKS_META', '_cp_page_blocks');
define('CP_PAGE_ABOUT_NAV_META', '_cp_page_about_nav');

function cp_page_block_value($block, $key, $default = '')
{
    return isset($block[$key]) && is_scalar($block[$key]) ? (string) $block[$key] : $default;
}

function cp_page_is_managed($post)
{
    $post = get_post($post);
    return $post instanceof WP_Post && '1' === get_post_meta($post->ID, CP_PAGE_MANAGED_META, true);
}

/**
 * The one list of Page block types: label, Bootstrap icon, and the short
 * description shown in the Add Block / Insert Block choosers.
 */
function cp_page_block_types()
{
    return [
        'heading' => ['label' => __('Heading', 'client-portal'), 'icon' => 'bi-type-h1', 'description' => __('Add a section title', 'client-portal')],
        'richtext' => ['label' => __('Rich Text', 'client-portal'), 'icon' => 'bi-text-paragraph', 'description' => __('Write paragraphs, links, and lists', 'client-portal')],
        'image' => ['label' => __('Image', 'client-portal'), 'icon' => 'bi-image', 'description' => __('Add an image from the Media Library', 'client-portal')],
        'staff_grid' => ['label' => __('Staff Grid', 'client-portal'), 'icon' => 'bi-people', 'description' => __('Add and manage publication staff', 'client-portal')],
        'button' => ['label' => __('Button / CTA', 'client-portal'), 'icon' => 'bi-hand-index', 'description' => __('Add a clickable call-to-action', 'client-portal')],
        'divider' => ['label' => __('Divider / Spacer', 'client-portal'), 'icon' => 'bi-distribute-vertical', 'description' => __('Separate page sections', 'client-portal')],
    ];
}

function cp_page_block_label($type)
{
    $types = cp_page_block_types();
    return isset($types[$type]) ? $types[$type]['label'] : __('Content Block', 'client-portal');
}

/**
 * Empty starting data for a newly inserted block - also what the editor's
 * client-side <template> copies are rendered from (templates/page-builder.php).
 */
function cp_page_default_block($type)
{
    $defaults = [
        'heading' => ['type' => 'heading', 'level' => 2, 'content' => ''],
        'richtext' => ['type' => 'richtext', 'content' => ''],
        'image' => ['type' => 'image', 'attachment_id' => 0, 'alt' => '', 'caption' => ''],
        'staff_grid' => ['type' => 'staff_grid', 'people' => []],
        'button' => ['type' => 'button', 'label' => '', 'url' => '', 'style' => 'primary'],
        'divider' => ['type' => 'divider', 'size' => 'medium'],
    ];

    return isset($defaults[$type]) ? $defaults[$type] : $defaults['richtext'];
}

/**
 * Heading alignment is a whitelisted block attribute (rendered as the same
 * cp-align-* class the rich-text sanitizer already allows), not markup inside
 * the heading - heading HTML stays inline-only (cp_rich_heading_allowed_html()).
 */
function cp_page_heading_align_choices()
{
    return [
        'left' => __('Left', 'client-portal'),
        'center' => __('Center', 'client-portal'),
        'right' => __('Right', 'client-portal'),
    ];
}

function cp_page_button_style_choices()
{
    return [
        'primary' => __('Primary (filled)', 'client-portal'),
        'outline' => __('Outline', 'client-portal'),
    ];
}

function cp_page_divider_size_choices()
{
    return [
        'small' => __('Small', 'client-portal'),
        'medium' => __('Medium', 'client-portal'),
        'large' => __('Large', 'client-portal'),
    ];
}

define('CP_STAFF_ARTWORK_DIR', 'assets/images/staff/');

/**
 * Trusted Staff artwork shipped inside the plugin (assets/images/staff/),
 * keyed by its file slug, e.g. "maryiel-n-jimenez" => URL. These are
 * complete, pre-inspected static cards (portrait + name + position baked
 * in) - referenced by key only, so a saved Staff record can never point at
 * an arbitrary browser-supplied path, and WordPress's own SVG upload
 * restrictions stay untouched.
 */
function cp_staff_bundled_artwork()
{
    static $artwork = null;
    if (null !== $artwork) {
        return $artwork;
    }

    $artwork = [];
    $files = glob(cp_path(CP_STAFF_ARTWORK_DIR . '*.svg'));
    foreach (is_array($files) ? $files : [] as $file) {
        $key = basename($file, '.svg');
        if (preg_match('/^[a-z0-9-]+$/', $key)) {
            $artwork[$key] = cp_url(CP_STAFF_ARTWORK_DIR . $key . '.svg');
        }
    }
    ksort($artwork);

    return $artwork;
}

function cp_staff_bundled_artwork_url($key)
{
    $artwork = cp_staff_bundled_artwork();
    return is_string($key) && isset($artwork[$key]) ? $artwork[$key] : '';
}

/**
 * Default/fallback alt text built from the structured record ("Maryiel N.
 * Jimenez, Editor-in-Chief") - the public card is image-only, so this is
 * what carries the person's identity for assistive technology.
 */
function cp_staff_default_alt($name, $position)
{
    $name = trim((string) $name);
    $position = trim((string) $position);
    return '' !== $position ? $name . ', ' . $position : $name;
}

function cp_staff_member_alt($member)
{
    $alt = isset($member['alt']) ? trim((string) $member['alt']) : '';
    if ('' !== $alt) {
        return $alt;
    }

    return cp_staff_default_alt(isset($member['name']) ? $member['name'] : '', isset($member['position']) ? $member['position'] : '');
}

/**
 * One staff card. Image resolution (uploaded attachment -> bundled artwork
 * -> placeholder) happens at render time, never stored as a fake
 * attachment - see cp_render_staff_portrait().
 */
function cp_sanitize_staff_member($member, $index)
{
    if (!is_array($member)) {
        return cp_article_block_error(sprintf(__('Staff card %d is invalid.', 'client-portal'), $index + 1));
    }

    $name = sanitize_text_field(cp_page_block_value($member, 'name'));
    $position = sanitize_text_field(cp_page_block_value($member, 'position'));
    $group = sanitize_text_field(cp_page_block_value($member, 'group'));
    $attachment_id = absint(cp_page_block_value($member, 'attachment_id'));
    $bundled = sanitize_key(cp_page_block_value($member, 'bundled'));
    $alt = sanitize_text_field(cp_page_block_value($member, 'alt'));

    if ($attachment_id && !wp_attachment_is_image($attachment_id)) {
        $attachment_id = 0;
    }

    if ('' === cp_staff_bundled_artwork_url($bundled)) {
        $bundled = '';
    }

    if ('' === $name) {
        return cp_article_block_error(sprintf(__('Staff card %d needs a name.', 'client-portal'), $index + 1));
    }

    return [
        'name' => $name,
        'position' => $position,
        'group' => $group,
        'attachment_id' => $attachment_id,
        'bundled' => $bundled,
        'alt' => '' !== $alt ? $alt : cp_staff_default_alt($name, $position),
    ];
}

function cp_sanitize_page_blocks($blocks, $require_content = true)
{
    if (!is_array($blocks)) {
        return cp_article_block_error(__('Page blocks must be submitted as a list.', 'client-portal'));
    }

    if (count($blocks) > 60) {
        return cp_article_block_error(__('A page cannot contain more than 60 blocks.', 'client-portal'));
    }

    $sanitized = [];
    foreach ($blocks as $index => $block) {
        if (!is_array($block)) {
            return cp_article_block_error(__('One of the submitted blocks is invalid.', 'client-portal'));
        }

        $type = sanitize_key(cp_page_block_value($block, 'type'));
        if (!in_array($type, ['heading', 'richtext', 'image', 'staff_grid', 'button', 'divider'], true)) {
            return cp_article_block_error(__('An unsupported page block was submitted.', 'client-portal'));
        }

        if ('heading' === $type) {
            $content = cp_sanitize_rich_heading_html(cp_page_block_value($block, 'content'));
            $level = absint(cp_page_block_value($block, 'level', '2'));
            if ($level < 1 || $level > 6 || ($require_content && !cp_rich_heading_has_content($content))) {
                return cp_article_block_error(sprintf(__('Heading block %d is incomplete.', 'client-portal'), $index + 1));
            }
            $heading = ['type' => 'heading', 'level' => $level, 'content' => $content];
            // Stored only when chosen, so headings saved before alignment
            // existed keep their exact shape (and their level's default).
            $align = sanitize_key(cp_page_block_value($block, 'align'));
            if (isset(cp_page_heading_align_choices()[$align])) {
                $heading['align'] = $align;
            }
            $sanitized[] = $heading;
            continue;
        }

        if ('richtext' === $type) {
            $content = cp_sanitize_rich_paragraph_html(cp_page_block_value($block, 'content'));
            if ($require_content && !cp_rich_paragraph_has_content($content)) {
                return cp_article_block_error(sprintf(__('Rich Text block %d is empty.', 'client-portal'), $index + 1));
            }
            $sanitized[] = ['type' => 'richtext', 'content' => $content];
            continue;
        }

        if ('image' === $type) {
            $attachment_id = absint(cp_page_block_value($block, 'attachment_id'));
            $alt = sanitize_text_field(cp_page_block_value($block, 'alt'));
            $caption = sanitize_text_field(cp_page_block_value($block, 'caption'));

            if ($require_content && (!$attachment_id || !wp_attachment_is_image($attachment_id))) {
                return cp_article_block_error(sprintf(__('Image block %d needs a Media Library image.', 'client-portal'), $index + 1));
            }
            if ($attachment_id && !wp_attachment_is_image($attachment_id)) {
                $attachment_id = 0;
            }
            $sanitized[] = ['type' => 'image', 'attachment_id' => $attachment_id, 'alt' => $alt, 'caption' => $caption];
            continue;
        }

        if ('staff_grid' === $type) {
            $people = isset($block['people']) && is_array($block['people']) ? $block['people'] : [];
            if (count($people) > 80) {
                return cp_article_block_error(__('A staff grid cannot contain more than 80 people.', 'client-portal'));
            }
            $sanitized_people = [];
            foreach ($people as $person_index => $member) {
                $clean_member = cp_sanitize_staff_member($member, $person_index);
                if (is_wp_error($clean_member)) {
                    return $clean_member;
                }
                $sanitized_people[] = $clean_member;
            }
            if ($require_content && empty($sanitized_people)) {
                return cp_article_block_error(sprintf(__('Staff Grid block %d needs at least one person.', 'client-portal'), $index + 1));
            }
            $sanitized[] = ['type' => 'staff_grid', 'people' => $sanitized_people];
            continue;
        }

        if ('button' === $type) {
            $label = sanitize_text_field(cp_page_block_value($block, 'label'));
            $url = esc_url_raw(cp_page_block_value($block, 'url'));
            $style = sanitize_key(cp_page_block_value($block, 'style', 'primary'));
            if (!isset(cp_page_button_style_choices()[$style])) {
                $style = 'primary';
            }
            if ($require_content && ('' === $label || '' === $url || false === wp_http_validate_url($url))) {
                return cp_article_block_error(sprintf(__('Button block %d needs a label and a valid URL.', 'client-portal'), $index + 1));
            }
            $sanitized[] = ['type' => 'button', 'label' => $label, 'url' => $url, 'style' => $style];
            continue;
        }

        // divider
        $size = sanitize_key(cp_page_block_value($block, 'size', 'medium'));
        if (!isset(cp_page_divider_size_choices()[$size])) {
            $size = 'medium';
        }
        $sanitized[] = ['type' => 'divider', 'size' => $size];
    }

    if ($require_content && empty($sanitized)) {
        return cp_article_block_error(__('Add at least one content block before saving.', 'client-portal'));
    }

    return $sanitized;
}

function cp_decode_page_blocks($json, $require_content = true)
{
    if (!is_string($json) || '' === trim($json)) {
        return $require_content ? cp_article_block_error(__('Add at least one content block before saving.', 'client-portal')) : [];
    }

    $blocks = json_decode($json, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
        return cp_article_block_error(__('The page blocks could not be read. Please try again.', 'client-portal'));
    }

    return cp_sanitize_page_blocks($blocks, $require_content);
}

function cp_get_page_blocks_for_editor($post = null)
{
    if (!$post instanceof WP_Post) {
        return [];
    }

    $blocks = get_post_meta($post->ID, CP_PAGE_BLOCKS_META, true);
    if (is_array($blocks)) {
        $sanitized = cp_sanitize_page_blocks($blocks, false);
        if (!is_wp_error($sanitized)) {
            return $sanitized;
        }
    }

    return [];
}

function cp_save_page_blocks($post_id, $blocks)
{
    $post_id = absint($post_id);
    if (!$post_id || !is_array($blocks)) {
        return;
    }

    update_post_meta($post_id, CP_PAGE_BLOCKS_META, $blocks);
}

/* -------------------------------------------------------------------- */
/* Admin block editor rendering                                          */
/* -------------------------------------------------------------------- */

/**
 * Icon-only block toolbar button: tooltip + accessible name from one label.
 */
function cp_render_page_block_icon_button($attribute, $icon, $label, $extra_class = '')
{
    printf(
        '<button type="button" class="cp-icon-button%1$s" %2$s title="%3$s" aria-label="%3$s"><i class="bi %4$s" aria-hidden="true"></i></button>',
        $extra_class ? ' ' . esc_attr($extra_class) : '',
        $attribute, // Static data-* attribute string from the callers below.
        esc_attr($label),
        esc_attr($icon)
    );
}

/**
 * One block in the Page Builder canvas. $index < 0 renders an unnumbered
 * copy for the client-side <template> (new blocks); $block_id lets that
 * template carry a placeholder the script swaps for a unique ID.
 */
function cp_render_page_block_editor($block, $index, $block_id = '')
{
    $types = cp_page_block_types();
    $type = isset($block['type']) ? sanitize_key($block['type']) : 'richtext';
    $type = isset($types[$type]) ? $type : 'richtext';
    if ('' === $block_id) {
        $block_id = 'cp-page-block-' . absint($index) . '-' . wp_rand(1000, 9999);
    }
    $is_text = in_array($type, ['heading', 'richtext'], true);
    ?>
    <article class="cp-builder-block" data-cp-block data-block-type="<?php echo esc_attr($type); ?>">
        <div class="cp-builder-block-head" data-cp-block-chrome>
            <div class="cp-builder-block-title">
                <span class="cp-block-drag-handle" data-cp-drag-handle title="<?php esc_attr_e('Drag to reorder', 'client-portal'); ?>" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                <span class="cp-block-number"><?php echo $index >= 0 ? esc_html($index + 1) : ''; ?></span>
                <span class="cp-block-type-icon" aria-hidden="true"><i class="bi <?php echo esc_attr($types[$type]['icon']); ?>"></i></span>
                <div><strong><?php echo esc_html($types[$type]['label']); ?></strong><small><?php echo esc_html($types[$type]['description']); ?></small></div>
            </div>
            <div class="cp-builder-block-actions" role="group" aria-label="<?php esc_attr_e('Block actions', 'client-portal'); ?>">
                <?php
                cp_render_page_block_icon_button('data-cp-move-up', 'bi-arrow-up', __('Move Up', 'client-portal'));
                cp_render_page_block_icon_button('data-cp-move-down', 'bi-arrow-down', __('Move Down', 'client-portal'));
                cp_render_page_block_icon_button('data-cp-duplicate', 'bi-copy', __('Duplicate', 'client-portal'));
                if ($is_text) {
                    cp_render_page_block_icon_button('data-cp-fullscreen', 'bi-arrows-fullscreen', __('Full Screen', 'client-portal'));
                }
                cp_render_page_block_icon_button('data-cp-block-menu-toggle aria-haspopup="menu" aria-expanded="false"', 'bi-three-dots', __('More actions', 'client-portal'));
                cp_render_page_block_icon_button('data-cp-remove', 'bi-trash', __('Delete', 'client-portal'), 'cp-icon-button-danger');
                ?>
            </div>
        </div>
        <div class="cp-builder-block-body">
            <?php
            if ('heading' === $type) {
                cp_render_page_heading_editor_fields($block, $block_id);
            } elseif ('richtext' === $type) {
                cp_render_page_richtext_editor_fields($block, $block_id);
            } elseif ('image' === $type) {
                cp_render_page_image_editor_fields($block, $block_id);
            } elseif ('staff_grid' === $type) {
                cp_render_page_staff_grid_editor_fields($block, $block_id);
            } elseif ('button' === $type) {
                cp_render_page_button_editor_fields($block, $block_id);
            } elseif ('divider' === $type) {
                cp_render_page_divider_editor_fields($block, $block_id);
            }
            ?>
        </div>
        <button type="button" class="cp-block-insert-below" data-cp-insert-below title="<?php esc_attr_e('Insert block below', 'client-portal'); ?>" aria-label="<?php esc_attr_e('Insert block below', 'client-portal'); ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
    </article>
    <?php
}

/**
 * Block-type buttons for the Add Block panel and the Insert Block chooser:
 * icon + name + short description.
 */
function cp_render_page_block_choices()
{
    foreach (cp_page_block_types() as $type => $info) {
        printf(
            '<button type="button" class="cp-block-choice" data-cp-add-type="%1$s"><i class="bi %2$s" aria-hidden="true"></i><span><strong>%3$s</strong><small>%4$s</small></span></button>',
            esc_attr($type),
            esc_attr($info['icon']),
            esc_html($info['label']),
            esc_html($info['description'])
        );
    }
}

/**
 * Heading text is edited visually (TinyMCE, see assets/js/page-builder.js);
 * the level select is the semantic H1-H6 and the hidden align field is set
 * by the editor's own alignment buttons.
 */
function cp_render_page_heading_editor_fields($block, $block_id)
{
    $align = isset($block['align']) && isset(cp_page_heading_align_choices()[$block['align']]) ? $block['align'] : '';
    ?>
    <div class="cp-heading-field">
        <div class="cp-heading-level-bar">
            <label for="<?php echo esc_attr($block_id); ?>-level"><?php esc_html_e('Heading Level', 'client-portal'); ?></label>
            <select class="form-select form-select-sm" id="<?php echo esc_attr($block_id); ?>-level" data-cp-field="level">
                <?php for ($level = 1; $level <= 6; $level++) : ?>
                    <option value="<?php echo esc_attr($level); ?>" <?php selected(isset($block['level']) ? $block['level'] : 2, $level); ?>><?php echo esc_html('H' . $level); ?></option>
                <?php endfor; ?>
            </select>
            <span><?php esc_html_e('H1 is the page title; use H2 for main sections and H3 for subsections.', 'client-portal'); ?></span>
        </div>
        <input type="hidden" data-cp-field="align" value="<?php echo esc_attr($align); ?>">
        <label class="form-label cp-visually-hidden-label" for="<?php echo esc_attr($block_id); ?>-content"><?php esc_html_e('Heading text', 'client-portal'); ?></label>
        <textarea class="form-control cp-heading-editor" id="<?php echo esc_attr($block_id); ?>-content" data-cp-field="content" data-cp-heading-editor rows="2"><?php echo esc_textarea(isset($block['content']) ? $block['content'] : ''); ?></textarea>
    </div>
    <?php
}

function cp_render_page_richtext_editor_fields($block, $block_id)
{
    ?>
    <div class="cp-paragraph-field">
        <label class="form-label" for="<?php echo esc_attr($block_id); ?>-content"><?php esc_html_e('Content', 'client-portal'); ?></label>
        <textarea class="form-control cp-paragraph-editor" id="<?php echo esc_attr($block_id); ?>-content" data-cp-field="content" data-cp-paragraph-editor rows="8"><?php echo esc_textarea(isset($block['content']) ? $block['content'] : ''); ?></textarea>
    </div>
    <?php
}

function cp_render_page_image_editor_fields($block, $block_id)
{
    $attachment_id = isset($block['attachment_id']) ? absint($block['attachment_id']) : 0;
    $preview_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium_large') : '';
    ?>
    <input type="hidden" data-cp-field="attachment_id" value="<?php echo esc_attr($attachment_id); ?>">
    <div class="cp-page-image-field">
        <div class="cp-page-image-stage">
            <div class="cp-image-preview" data-cp-image-preview<?php if (!$preview_url) : ?> hidden<?php endif; ?>><?php if ($preview_url) : ?><img src="<?php echo esc_url($preview_url); ?>" alt=""><?php endif; ?></div>
            <div class="cp-page-image-empty" data-cp-image-empty<?php if ($preview_url) : ?> hidden<?php endif; ?>><i class="bi bi-image" aria-hidden="true"></i><span><?php esc_html_e('No image selected yet', 'client-portal'); ?></span></div>
        </div>
        <div class="cp-page-image-actions">
            <button type="button" class="btn btn-outline-primary btn-sm" data-cp-select-image><i class="bi bi-images" aria-hidden="true"></i> <span data-cp-select-image-label><?php echo $preview_url ? esc_html__('Replace Image', 'client-portal') : esc_html__('Select Image', 'client-portal'); ?></span></button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-cp-remove-image<?php if (!$attachment_id) : ?> hidden<?php endif; ?>><i class="bi bi-x-lg" aria-hidden="true"></i> <?php esc_html_e('Remove', 'client-portal'); ?></button>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-alt"><?php esc_html_e('Alt text', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-alt" data-cp-field="alt" value="<?php echo esc_attr(isset($block['alt']) ? $block['alt'] : ''); ?>"><div class="form-text"><?php esc_html_e('Describe the image for visitors who cannot see it.', 'client-portal'); ?></div></div>
        <div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-caption"><?php esc_html_e('Caption (optional)', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-caption" data-cp-field="caption" value="<?php echo esc_attr(isset($block['caption']) ? $block['caption'] : ''); ?>"><div class="form-text"><?php esc_html_e('Shown under the image on the page.', 'client-portal'); ?></div></div>
    </div>
    <?php
}

function cp_render_page_button_editor_fields($block, $block_id)
{
    $style = isset($block['style']) && isset(cp_page_button_style_choices()[$block['style']]) ? $block['style'] : 'primary';
    $label = isset($block['label']) ? $block['label'] : '';
    ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-label"><?php esc_html_e('Button Label', 'client-portal'); ?></label><input class="form-control" id="<?php echo esc_attr($block_id); ?>-label" data-cp-field="label" value="<?php echo esc_attr($label); ?>" placeholder="<?php esc_attr_e('e.g. Join the Publication', 'client-portal'); ?>"></div>
        <div class="col-md-6"><label class="form-label" for="<?php echo esc_attr($block_id); ?>-url"><?php esc_html_e('Link URL', 'client-portal'); ?></label><input type="url" class="form-control" id="<?php echo esc_attr($block_id); ?>-url" data-cp-field="url" value="<?php echo esc_attr(isset($block['url']) ? $block['url'] : ''); ?>" placeholder="https://" inputmode="url"><div class="form-text"><?php esc_html_e('Paste the full web address, starting with https:// (for example a Google Form or another page on this site). Opens in a new tab.', 'client-portal'); ?></div></div>
        <div class="col-md-6">
            <label class="form-label" for="<?php echo esc_attr($block_id); ?>-style"><?php esc_html_e('Style', 'client-portal'); ?></label>
            <select class="form-select" id="<?php echo esc_attr($block_id); ?>-style" data-cp-field="style">
                <?php foreach (cp_page_button_style_choices() as $style_key => $style_label) : ?>
                    <option value="<?php echo esc_attr($style_key); ?>" <?php selected($style, $style_key); ?>><?php echo esc_html($style_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <span class="form-label d-block"><?php esc_html_e('Preview', 'client-portal'); ?></span>
            <div class="cp-page-button-preview"><span class="cp-page-button-preview-cta is-<?php echo esc_attr($style); ?>" data-cp-button-preview data-placeholder="<?php esc_attr_e('Button label', 'client-portal'); ?>"><?php echo esc_html('' !== $label ? $label : __('Button label', 'client-portal')); ?></span></div>
        </div>
    </div>
    <?php
}

/**
 * Spacer size as a visual Small/Medium/Large choice; the hidden field is
 * what gets serialized.
 */
function cp_render_page_divider_editor_fields($block, $block_id)
{
    $size = isset($block['size']) && isset(cp_page_divider_size_choices()[$block['size']]) ? $block['size'] : 'medium';
    ?>
    <input type="hidden" data-cp-field="size" value="<?php echo esc_attr($size); ?>">
    <div class="cp-divider-options" role="group" aria-labelledby="<?php echo esc_attr($block_id); ?>-size-label">
        <span class="form-label" id="<?php echo esc_attr($block_id); ?>-size-label"><?php esc_html_e('Spacing', 'client-portal'); ?></span>
        <div class="cp-divider-choices">
            <?php foreach (cp_page_divider_size_choices() as $size_key => $size_label) : ?>
                <button type="button" class="cp-divider-choice" data-cp-divider-size="<?php echo esc_attr($size_key); ?>" aria-pressed="<?php echo $size_key === $size ? 'true' : 'false'; ?>">
                    <span class="cp-divider-sample cp-divider-sample-<?php echo esc_attr($size_key); ?>" aria-hidden="true"></span>
                    <span><?php echo esc_html($size_label); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function cp_render_page_staff_grid_editor_fields($block, $block_id)
{
    $people = isset($block['people']) && is_array($block['people']) ? $block['people'] : [];
    ?>
    <div class="cp-staff-editor" data-cp-staff-editor>
        <div class="cp-staff-editor-list" data-cp-staff-list>
            <?php foreach ($people as $person_index => $member) : cp_render_page_staff_member_editor($member, $person_index); endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" data-cp-staff-add>
            <i class="bi bi-person-plus" aria-hidden="true"></i> <?php esc_html_e('Add Person', 'client-portal'); ?>
        </button>
    </div>
    <?php
}

function cp_render_page_staff_member_editor($member, $index)
{
    $member = is_array($member) ? $member : [];
    $attachment_id = isset($member['attachment_id']) ? absint($member['attachment_id']) : 0;
    $bundled = isset($member['bundled']) ? (string) $member['bundled'] : '';
    $attachment_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
    $bundled_url = cp_staff_bundled_artwork_url($bundled);
    $preview_url = $attachment_url ? $attachment_url : $bundled_url;
    if ($attachment_url) {
        $source_label = __('Uploaded image', 'client-portal');
    } elseif ($bundled_url) {
        $source_label = __('Bundled artwork', 'client-portal');
    } else {
        $source_label = __('Placeholder', 'client-portal');
    }
    ?>
    <div class="cp-staff-editor-card" data-cp-staff-person>
        <div class="cp-staff-editor-portrait">
            <div class="cp-staff-editor-portrait-preview" data-cp-staff-portrait-preview<?php if (!$preview_url) : ?> data-empty<?php endif; ?>>
                <?php if ($preview_url) : ?><img src="<?php echo esc_url($preview_url); ?>" alt="" loading="lazy" decoding="async"><?php else : ?><?php echo cp_render_staff_portrait_placeholder(); // Static trusted markup. ?><?php endif; ?>
            </div>
            <small class="cp-staff-editor-source" data-cp-staff-source><?php echo esc_html($source_label); ?></small>
            <input type="hidden" data-cp-staff-field="attachment_id" value="<?php echo esc_attr($attachment_id); ?>"<?php if ($attachment_url) : ?> data-preview-url="<?php echo esc_url($attachment_url); ?>"<?php endif; ?>>
            <div class="cp-staff-editor-portrait-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-cp-staff-select-portrait><?php esc_html_e('Upload / Select', 'client-portal'); ?></button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-cp-staff-remove-portrait<?php if (!$attachment_id) : ?> hidden<?php endif; ?>><?php esc_html_e('Remove', 'client-portal'); ?></button>
            </div>
        </div>
        <div class="cp-staff-editor-fields">
            <div class="row g-2">
                <div class="col-md-6"><label class="form-label"><?php esc_html_e('Name', 'client-portal'); ?></label><input class="form-control form-control-sm" data-cp-staff-field="name" value="<?php echo esc_attr(isset($member['name']) ? $member['name'] : ''); ?>"></div>
                <div class="col-md-6"><label class="form-label"><?php esc_html_e('Position', 'client-portal'); ?></label><input class="form-control form-control-sm" data-cp-staff-field="position" value="<?php echo esc_attr(isset($member['position']) ? $member['position'] : ''); ?>"></div>
                <div class="col-md-6"><label class="form-label"><?php esc_html_e('Section / Group', 'client-portal'); ?></label><input class="form-control form-control-sm" data-cp-staff-field="group" value="<?php echo esc_attr(isset($member['group']) ? $member['group'] : ''); ?>" placeholder="<?php esc_attr_e('e.g. Editorial Board', 'client-portal'); ?>"></div>
                <div class="col-md-6"><label class="form-label"><?php esc_html_e('Alt text', 'client-portal'); ?></label><input class="form-control form-control-sm" data-cp-staff-field="alt" value="<?php echo esc_attr(isset($member['alt']) ? $member['alt'] : ''); ?>"></div>
                <div class="col-md-6">
                    <label class="form-label"><?php esc_html_e('Bundled Artwork', 'client-portal'); ?></label>
                    <select class="form-select form-select-sm" data-cp-staff-field="bundled">
                        <option value=""><?php esc_html_e('None', 'client-portal'); ?></option>
                        <?php foreach (array_keys(cp_staff_bundled_artwork()) as $artwork_key) : ?>
                            <option value="<?php echo esc_attr($artwork_key); ?>" <?php selected($bundled, $artwork_key); ?>><?php echo esc_html($artwork_key . '.svg'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?php esc_html_e('Used when no uploaded image is selected.', 'client-portal'); ?></div>
                </div>
            </div>
        </div>
        <div class="cp-staff-editor-actions">
            <button type="button" class="cp-icon-button" data-cp-staff-move-up title="<?php esc_attr_e('Move Up', 'client-portal'); ?>"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="cp-icon-button" data-cp-staff-move-down title="<?php esc_attr_e('Move Down', 'client-portal'); ?>"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="cp-icon-button cp-icon-button-danger" data-cp-staff-remove title="<?php esc_attr_e('Remove Person', 'client-portal'); ?>"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <?php
}

/* -------------------------------------------------------------------- */
/* Frontend rendering                                                    */
/* -------------------------------------------------------------------- */

/**
 * A neutral person-silhouette placeholder - never a stock photo - for a
 * staff card with neither an uploaded image nor bundled artwork. Static,
 * trusted markup: callers echo it directly (wp_kses_post() would strip the
 * <svg>). The icon itself is decorative; on the public card the wrapper
 * gets role="img" + the person's alt text instead (see
 * cp_render_staff_portrait()).
 */
function cp_render_staff_portrait_placeholder()
{
    return '<span class="cp-staff-portrait-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5Z"/></svg></span>';
}

/**
 * The complete public Staff card visual, image only (CLAUDE.md 32.3/32.5):
 * administrator-selected attachment, then bundled trusted artwork, then the
 * neutral placeholder. Every branch carries the person's identity via alt
 * text / aria-label, since no visible name/position is printed beside it.
 * All dynamic values are escaped here; the result is echoed as-is.
 */
function cp_render_staff_portrait($member)
{
    $alt = cp_staff_member_alt($member);
    $attachment_id = isset($member['attachment_id']) ? absint($member['attachment_id']) : 0;

    if ($attachment_id) {
        $image = wp_get_attachment_image(
            $attachment_id,
            'large',
            false,
            [
                'class' => 'cp-staff-artwork-image',
                'alt' => $alt,
                'loading' => 'lazy',
                'decoding' => 'async',
                'sizes' => '(max-width: 640px) 90vw, 360px',
            ]
        );
        if ($image) {
            return $image;
        }
    }

    $bundled_url = cp_staff_bundled_artwork_url(isset($member['bundled']) ? $member['bundled'] : '');
    if ('' !== $bundled_url) {
        // Natural 1:1 size of the supplied cards (viewBox 224.88 x 225,
        // exported at 300 x 300) so the browser reserves the right box
        // before the file arrives - CSS scales it, never crops it.
        return '<img class="cp-staff-artwork-image" src="' . esc_url($bundled_url) . '" alt="' . esc_attr($alt) . '" width="300" height="300" loading="lazy" decoding="async">';
    }

    return '<span class="cp-staff-placeholder-card" role="img" aria-label="' . esc_attr($alt) . '">' . cp_render_staff_portrait_placeholder() . '</span>';
}

/**
 * Renders one staff section (a name/group of people, e.g. "Editorial
 * Board") as rows of up to six cards, the last incomplete row centered.
 * Each row carries its own cp-staff-row-{N} class so card size follows
 * THAT row's count (fewer people = larger cards) - see
 * assets/css/frontend-pages.css. Grouping and row chunking are entirely
 * data-driven from the saved people list; nothing here is hardcoded to
 * specific names or counts.
 */
function cp_render_staff_section($group_label, $people)
{
    if (empty($people)) {
        return '';
    }

    $rows = array_chunk($people, 6);
    $row_count = count($rows);

    ob_start();
    ?>
    <div class="cp-staff-section">
        <?php if ('' !== $group_label) : ?>
            <h3 class="cp-staff-section-title"><?php echo esc_html($group_label); ?></h3>
        <?php endif; ?>
        <?php foreach ($rows as $row_index => $row) : ?>
            <?php
            $is_last_row = $row_index === $row_count - 1;
            $row_size = count($row);
            $row_classes = ['cp-staff-row', 'cp-staff-row-' . $row_size];
            if ($is_last_row && $row_size < 6) {
                $row_classes[] = 'cp-staff-row-partial';
            }
            ?>
            <div class="<?php echo esc_attr(implode(' ', $row_classes)); ?>">
                <?php foreach ($row as $member) : ?>
                    <?php // Image-only by design: name/position stay in the record (alt text, admin), not printed again under artwork that already shows them. ?>
                    <div class="cp-staff-card"><?php echo cp_render_staff_portrait($member); // Escaped inside cp_render_staff_portrait(). ?></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return (string) ob_get_clean();
}

function cp_render_staff_grid_block($block)
{
    $people = isset($block['people']) && is_array($block['people']) ? $block['people'] : [];
    if (empty($people)) {
        return '';
    }

    $groups = [];
    $group_order = [];
    foreach ($people as $member) {
        $group = isset($member['group']) && '' !== $member['group'] ? $member['group'] : __('Staff', 'client-portal');
        if (!isset($groups[$group])) {
            $groups[$group] = [];
            $group_order[] = $group;
        }
        $groups[$group][] = $member;
    }

    $html = '<div class="cp-staff-grid">';
    foreach ($group_order as $group) {
        $html .= cp_render_staff_section($group, $groups[$group]);
    }
    $html .= '</div>';

    return $html;
}

function cp_render_page_block($block)
{
    $type = isset($block['type']) ? sanitize_key($block['type']) : '';

    if ('heading' === $type) {
        $level = isset($block['level']) ? max(1, min(6, absint($block['level']))) : 2;
        $content = wp_kses_post(isset($block['content']) ? $block['content'] : '');
        $classes = 'cp-page-heading-block';
        if (isset($block['align']) && isset(cp_page_heading_align_choices()[$block['align']])) {
            $classes .= ' cp-align-' . $block['align'];
        }
        return '<h' . $level . ' class="' . esc_attr($classes) . '">' . $content . '</h' . $level . '>';
    }

    if ('richtext' === $type) {
        $content = wp_kses_post(isset($block['content']) ? $block['content'] : '');
        return '<div class="cp-page-richtext-block">' . $content . '</div>';
    }

    if ('image' === $type) {
        $attachment_id = isset($block['attachment_id']) ? absint($block['attachment_id']) : 0;
        if (!$attachment_id) {
            return '';
        }
        $image = wp_get_attachment_image($attachment_id, 'large', false, ['class' => 'cp-page-image-block-img']);
        if (!$image) {
            return '';
        }
        $caption = !empty($block['caption']) ? '<figcaption>' . esc_html($block['caption']) . '</figcaption>' : '';
        return '<figure class="cp-page-image-block">' . $image . $caption . '</figure>';
    }

    if ('staff_grid' === $type) {
        return cp_render_staff_grid_block($block);
    }

    if ('button' === $type) {
        $label = isset($block['label']) ? sanitize_text_field($block['label']) : '';
        $url = isset($block['url']) ? esc_url($block['url']) : '';
        if ('' === $label || '' === $url) {
            return '';
        }
        $style = isset($block['style']) && 'outline' === $block['style'] ? 'outline' : 'primary';
        return '<div class="cp-page-button-block"><a class="cp-page-cta cp-page-cta-' . esc_attr($style) . '" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a></div>';
    }

    if ('divider' === $type) {
        $size = isset($block['size']) ? sanitize_key($block['size']) : 'medium';
        if (!isset(cp_page_divider_size_choices()[$size])) {
            $size = 'medium';
        }
        return '<div class="cp-page-divider-block cp-page-divider-' . esc_attr($size) . '" role="separator"></div>';
    }

    return '';
}

function cp_render_managed_page_blocks($blocks)
{
    if (!is_array($blocks) || empty($blocks)) {
        return '';
    }

    $html = '';
    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }
        $html .= cp_render_page_block($block);
    }

    return $html;
}

/**
 * Scoped the_content override: only fires for the specific WordPress Pages
 * this plugin manages (CP_PAGE_MANAGED_META), on the main query in the
 * loop, at normal priority - every other Page, post, Elementor page, and
 * the article-driven homepage render exactly as before. Falls back to the
 * Page's normal post_content if no blocks are saved yet, so a newly
 * created managed Page is never blank before its first save.
 */
function cp_filter_managed_page_content($content)
{
    if (!in_the_loop() || !is_main_query() || !is_page()) {
        return $content;
    }

    $post = get_post();
    if (!$post instanceof WP_Post || !cp_page_is_managed($post)) {
        return $content;
    }

    $blocks = cp_get_page_blocks_for_editor($post);
    if (empty($blocks)) {
        return $content;
    }

    // "enterprise-publication" is the site's existing shared design-token
    // wrapper (assets/css/frontend.css: --enterprise-brand/-ink/-serif/etc.,
    // the dark navy/white editorial identity) - reused here rather than
    // redefining those tokens a second time for managed Pages.
    return '<div class="enterprise-publication cp-managed-page">' . cp_render_managed_page_blocks($blocks) . '</div>';
}
add_filter('the_content', 'cp_filter_managed_page_content', 20);

/**
 * frontend-pages.css deliberately styles everything through the
 * --enterprise-brand/-ink/-serif/-sans custom properties (assets/css/frontend.css)
 * rather than redefining the site's visual identity a second time - but
 * those properties are only ever DEFINED by frontend.css itself, scoped to
 * .enterprise-publication/.enterprise-single-article/.enterprise-search-page.
 * frontend.css was previously only enqueued for the category-archive/article
 * shortcodes (includes/frontend-shortcodes.php), never for a plain managed
 * Page, so every var(--enterprise-*) reference here silently resolved to
 * nothing and fell back to browser/theme defaults - confirmed live (the
 * Join CTA background/text colors were invisible because of exactly this,
 * not a specificity conflict). frontend.css is now enqueued as an explicit
 * dependency so the custom properties are actually available.
 */
function cp_enqueue_frontend_page_styles()
{
    wp_enqueue_style('cp-frontend', cp_url('assets/css/frontend.css'), [], cp_asset_version('assets/css/frontend.css'));
    wp_enqueue_style('cp-frontend-pages', cp_url('assets/css/frontend-pages.css'), ['cp-frontend'], CP_VERSION);
}

/**
 * Enqueues frontend-pages.css early enough to actually reach <head> via the
 * normal wp_head -> wp_print_styles cycle. Calling wp_enqueue_style() from
 * inside the_content (cp_filter_managed_page_content(), above) would be too
 * late - wp_head has already fired by the time the loop/content filter
 * runs - so eligibility is instead pre-checked here against the queried
 * Page, the same way includes/frontend-shortcodes.php's
 * cp_enqueue_frontend_styles_for_publication_context() already does for
 * the category-archive/article shortcodes.
 */
function cp_enqueue_frontend_page_styles_for_managed_pages()
{
    if (!is_page()) {
        return;
    }

    if (cp_page_is_managed(get_queried_object_id())) {
        cp_enqueue_frontend_page_styles();
    }
}
add_action('wp_enqueue_scripts', 'cp_enqueue_frontend_page_styles_for_managed_pages');
