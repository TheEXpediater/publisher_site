<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_frontend_shortcodes()
{
    add_shortcode('enterprise_category_posts', 'cp_category_posts_shortcode');
    add_shortcode('enterprise_latest_articles', 'cp_latest_articles_shortcode');
    add_shortcode('enterprise_category_cards', 'cp_category_cards_shortcode');
    add_shortcode('enterprise_article_search', 'cp_article_search_shortcode');
    add_shortcode('enterprise_homepage_featured', 'cp_homepage_featured_article_shortcode');
}

function cp_enterprise_facebook_url()
{
    return 'https://www.facebook.com/theenterprisehau';
}

function cp_rewrite_facebook_links_in_html($html)
{
    $html = is_string($html) ? $html : '';
    if ('' === trim($html) || false === stripos($html, '<a')) {
        return $html;
    }

    if (class_exists('WP_HTML_Tag_Processor')) {
        $processor = new WP_HTML_Tag_Processor($html);
        while ($processor->next_tag('a')) {
            $href = (string) $processor->get_attribute('href');
            $class = (string) $processor->get_attribute('class');
            $label = (string) $processor->get_attribute('aria-label');
            $title = (string) $processor->get_attribute('title');
            $facebook_marker = implode(' ', [$href, $class, $label, $title]);
            if (false !== stripos($facebook_marker, 'facebook') || false !== stripos($class, 'fa-facebook') || false !== stripos($class, 'bi-facebook')) {
                $processor->set_attribute('href', cp_enterprise_facebook_url());
            }
        }
        $html = $processor->get_updated_html();
    }

    return preg_replace_callback(
        '/<a\b([^>]*)>(.*?)<\/a>/is',
        static function ($matches) {
            $anchor = $matches[0];
            if (false === stripos($anchor, 'facebook') && false === stripos($anchor, 'fa-facebook') && false === stripos($anchor, 'bi-facebook')) {
                return $anchor;
            }
            if (preg_match('/\bhref=(["\']).*?\1/i', $anchor)) {
                return preg_replace('/\bhref=(["\']).*?\1/i', 'href="' . esc_url(cp_enterprise_facebook_url()) . '"', $anchor, 1);
            }
            return preg_replace('/^<a\b/i', '<a href="' . esc_url(cp_enterprise_facebook_url()) . '"', $anchor, 1);
        },
        $html
    );
}

function cp_rewrite_about_us_facebook_content($content)
{
    if (is_admin() || !is_singular('page')) {
        return $content;
    }

    $post = get_post();
    if (!$post instanceof WP_Post) {
        return $content;
    }

    $is_about_page = 'about-us' === sanitize_title($post->post_name) || 'about-us' === sanitize_title($post->post_title);
    return $is_about_page ? cp_rewrite_facebook_links_in_html($content) : $content;
}

add_action('init', 'cp_register_frontend_shortcodes');
add_action('wp_enqueue_scripts', 'cp_enqueue_frontend_styles_for_publication_context');
add_action('template_redirect', 'cp_redirect_standard_search_to_enterprise_search_page', 1);
add_action('save_post_page', 'cp_refresh_enterprise_search_page_cache', 10, 3);
add_action('deleted_post', 'cp_clear_enterprise_search_page_cache_on_delete');
add_filter('the_content', 'cp_rewrite_about_us_facebook_content', 9);
add_filter('the_content', 'cp_render_single_article_content', 20);
add_filter('body_class', 'cp_single_article_body_class');
add_filter('post_class', 'cp_single_article_post_class', 10, 3);
add_filter('get_search_form', 'cp_route_search_form_to_enterprise_page', 20, 2);
add_filter('render_block', 'cp_suppress_single_article_theme_header_blocks', 10, 3);
add_filter('render_block', 'cp_route_search_block_to_enterprise_page', 20, 3);
add_filter('post_thumbnail_html', 'cp_suppress_single_article_theme_thumbnail_html', 10, 5);
add_filter('previous_post_link', 'cp_suppress_single_article_adjacent_post_link', 10, 5);
add_filter('next_post_link', 'cp_suppress_single_article_adjacent_post_link', 10, 5);

function cp_enqueue_frontend_styles()
{
    wp_enqueue_style('cp-frontend', cp_url('assets/css/frontend.css'), [], CP_VERSION);

    if (cp_is_publication_homepage_request()) {
        wp_enqueue_script('cp-frontend-layout', cp_url('assets/js/frontend-layout.js'), [], CP_VERSION, true);
    }
}

function cp_is_single_enterprise_article_request()
{
    if (is_admin() || !is_singular('post') || !function_exists('cp_is_enterprise_article')) {
        return false;
    }

    $post = get_queried_object();
    return $post instanceof WP_Post && cp_is_enterprise_article($post);
}

function cp_is_rendering_single_article_layout($rendering = null)
{
    static $is_rendering = false;

    if (null !== $rendering) {
        $is_rendering = (bool) $rendering;
    }

    return $is_rendering;
}

function cp_suppress_single_article_theme_header_blocks($block_content, $block, $block_instance = null)
{
    if (!cp_is_single_enterprise_article_request() || cp_is_rendering_single_article_layout() || empty($block['blockName'])) {
        return $block_content;
    }

    $queried_post = get_queried_object();
    if (!$queried_post instanceof WP_Post) {
        return $block_content;
    }

    $block_post_id = 0;
    if (is_object($block_instance) && !empty($block_instance->context['postId'])) {
        $block_post_id = absint($block_instance->context['postId']);
    } elseif (!empty($block['attrs']['postId'])) {
        $block_post_id = absint($block['attrs']['postId']);
    }

    if (!$block_post_id || absint($queried_post->ID) !== $block_post_id) {
        return $block_content;
    }

    $theme_header_blocks = [
        'core/post-featured-image',
        'core/post-title',
        'core/post-terms',
        'core/post-author',
        'core/post-author-name',
        'core/post-date',
        'core/post-comments-link',
        'core/post-navigation-link',
    ];

    // Enterprise articles render a complete custom header inside the_content().
    // Removing these theme blocks prevents duplicate title, metadata, hero media,
    // and previous/next links from appearing outside the custom layout.
    if (in_array($block['blockName'], $theme_header_blocks, true)) {
        return '';
    }

    return $block_content;
}

function cp_suppress_single_article_theme_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr)
{
    if (!cp_is_single_enterprise_article_request() || cp_is_rendering_single_article_layout()) {
        return $html;
    }

    $queried_post = get_queried_object();
    if (!$queried_post instanceof WP_Post || absint($post_id) !== absint($queried_post->ID)) {
        return $html;
    }

    // Keep the Featured Image stored for cards, archives, related stories, and social previews,
    // but prevent the active theme from printing it above the custom article layout.
    return '';
}

function cp_suppress_single_article_adjacent_post_link($output, $format, $link, $post, $adjacent)
{
    if (!cp_is_single_enterprise_article_request() || cp_is_rendering_single_article_layout()) {
        return $output;
    }

    // The custom Other Stories section replaces theme previous/next post links
    // only on Client Portal article pages.
    return '';
}

function cp_enqueue_frontend_styles_for_publication_context()
{
    if (!is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }

    $shortcodes = [
        'enterprise_category_posts',
        'enterprise_latest_articles',
        'enterprise_category_cards',
        'enterprise_article_search',
        'enterprise_homepage_featured',
    ];

    foreach ($shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            cp_enqueue_frontend_styles();
            return;
        }
    }

    if (is_singular('post') && cp_is_enterprise_article($post)) {
        cp_enqueue_frontend_styles();
    }
}

function cp_frontend_current_page()
{
    $get_page = (isset($_GET['paged']) && is_scalar($_GET['paged'])) ? absint(wp_unslash($_GET['paged'])) : 0;
    $search_page = (isset($_GET['enterprise_search_page']) && is_scalar($_GET['enterprise_search_page'])) ? absint(wp_unslash($_GET['enterprise_search_page'])) : 0;

    return max(1, absint(get_query_var('paged')), absint(get_query_var('page')), $get_page, $search_page);
}

function cp_frontend_pagination_query_args($exclude = [])
{
    $exclude = array_merge(['paged', 'page'], array_map('sanitize_key', (array) $exclude));
    $query_args = [];

    foreach ($_GET as $key => $value) {
        if (!is_scalar($value)) {
            continue;
        }

        $key = sanitize_key($key);
        if ('' === $key || in_array($key, $exclude, true)) {
            continue;
        }

        $query_args[$key] = sanitize_text_field(wp_unslash((string) $value));
    }

    return $query_args;
}

function cp_enterprise_article_meta_query()
{
    return [
        'relation' => 'OR',
        [
            'key' => '_cp_article_blocks',
            'compare' => 'EXISTS',
        ],
        [
            'key' => '_cp_article_hero_image_url',
            'compare' => 'EXISTS',
        ],
    ];
}

function cp_frontend_attribute($value, $default = '')
{
    return is_scalar($value) ? (string) $value : $default;
}

function cp_frontend_boolean_attribute($value, $default = 'yes')
{
    $value = sanitize_key(cp_frontend_attribute($value, $default));
    return 'yes' === $value || 'true' === $value || '1' === $value;
}

function cp_frontend_columns($columns)
{
    return min(4, max(1, absint(cp_frontend_attribute($columns, '2'))));
}

function cp_frontend_category_name($slug, $fallback = '')
{
    $term = $slug ? get_category_by_slug($slug) : null;
    if ($term instanceof WP_Term) {
        return $term->name;
    }

    if ('' !== $fallback) {
        return $fallback;
    }

    return $slug ? ucwords(str_replace(['-', '_'], ' ', $slug)) : __('Articles', 'client-portal');
}

function cp_frontend_primary_category_term($post_id)
{
    $categories = get_the_category($post_id);
    if (!empty($categories) && $categories[0] instanceof WP_Term) {
        return $categories[0];
    }

    return null;
}

function cp_frontend_post_category($post_id, $fallback = '')
{
    $term = cp_frontend_primary_category_term($post_id);
    if ($term instanceof WP_Term) {
        return $term->name;
    }

    return $fallback ?: __('General', 'client-portal');
}

function cp_frontend_post_author($post)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return '';
    }

    return cp_get_article_display_author($post);
}

function cp_frontend_post_image($post_id, $image_size, $class_name)
{
    if ($post_id && has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail(
            $post_id,
            $image_size,
            [
                'class' => $class_name,
                'loading' => 'lazy',
                'decoding' => 'async',
            ]
        );
    }

    if ($post_id) {
        $hero_url = esc_url_raw((string) get_post_meta($post_id, '_cp_article_hero_image_url', true));
        if ($hero_url && wp_http_validate_url($hero_url)) {
            return sprintf(
                '<img class="%1$s" src="%2$s" alt="%3$s" loading="lazy" decoding="async">',
                esc_attr($class_name),
                esc_url($hero_url),
                esc_attr(get_the_title($post_id))
            );
        }
    }

    return '<div class="enterprise-placeholder-image" aria-hidden="true"><span>Enterprise1979</span></div>';
}

function cp_frontend_excerpt($post, $words = 34, $plain_ellipsis = false)
{
    $excerpt = wp_strip_all_tags(get_the_excerpt($post));

    if ($plain_ellipsis) {
        $charset = get_bloginfo('charset');
        $excerpt = html_entity_decode($excerpt, ENT_QUOTES | ENT_HTML5, $charset ?: 'UTF-8');
        $excerpt = preg_replace('/\s*\[\s*(?:\.{3}|…)\s*\]\s*$/u', '...', $excerpt);
        $excerpt = preg_replace('/\s*…\s*$/u', '...', $excerpt);
    }

    return wp_trim_words($excerpt, absint($words), '...');
}

function cp_render_frontend_empty_state($message)
{
    ?>
    <div class="enterprise-empty-state">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

function cp_render_frontend_section_header($title, $subtitle = '', $label = '')
{
    $title = trim((string) $title);
    $subtitle = trim((string) $subtitle);
    $label = trim((string) $label);

    if ('' === $title && '' === $subtitle) {
        return;
    }
    ?>
    <header class="enterprise-section-heading">
        <?php if ('' !== $label) : ?><span class="enterprise-section-kicker"><?php echo esc_html($label); ?></span><?php endif; ?>
        <?php if ('' !== $title) : ?><h2><?php echo esc_html($title); ?></h2><?php endif; ?>
        <?php if ('' !== $subtitle) : ?><p><?php echo nl2br(esc_html($subtitle)); ?></p><?php endif; ?>
    </header>
    <?php
}

function cp_render_frontend_archive_post($post_id, $image_size, $show_excerpt, $show_read_more, $category_name)
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }

    $excerpt = cp_frontend_excerpt($post);
    ?>
    <article class="enterprise-post-item">
        <a class="enterprise-post-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
            <?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-post-thumbnail')); ?>
        </a>
        <div class="enterprise-post-content">
            <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID, $category_name)); ?></span>
            <h3 class="enterprise-post-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <div class="enterprise-post-meta">
                <span><?php echo esc_html(cp_frontend_post_author($post)); ?></span>
                <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            </div>
            <?php if ($show_excerpt && '' !== $excerpt) : ?><p class="enterprise-post-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
            <?php if ($show_read_more) : ?><a class="enterprise-read-more" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_render_frontend_article_card($post_id, $image_size = 'medium_large', $show_excerpt = true, $show_read_more = true, $extra_class = '', $show_category = true, $excerpt_words = 24, $plain_ellipsis = false)
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }

    $excerpt = cp_frontend_excerpt($post, max(1, absint($excerpt_words)), (bool) $plain_ellipsis);
    $card_class = trim('enterprise-article-card ' . sanitize_html_class($extra_class));
    ?>
    <article class="<?php echo esc_attr($card_class); ?>">
        <a class="enterprise-card-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
            <?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-card-thumbnail')); ?>
        </a>
        <div class="enterprise-card-content">
            <?php if ($show_category) : ?><span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span><?php endif; ?>
            <h3 class="enterprise-card-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <div class="enterprise-card-meta">
                <span><?php echo esc_html(cp_frontend_post_author($post)); ?></span>
                <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            </div>
            <?php if ($show_excerpt && '' !== $excerpt) : ?><p class="enterprise-card-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
            <?php if ($show_read_more) : ?><a class="enterprise-read-more" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_render_frontend_lead_article($post_id, $image_size = 'large', $show_excerpt = true, $show_read_more = true, $kicker = '')
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }

    $excerpt = cp_frontend_excerpt($post, 46);
    $kicker = '' !== $kicker ? $kicker : __('Latest Article', 'client-portal');
    ?>
    <article class="enterprise-latest-lead">
        <a class="enterprise-latest-lead-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
            <?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-latest-lead-thumbnail')); ?>
        </a>
        <div class="enterprise-latest-lead-content">
            <span class="enterprise-section-kicker"><?php echo esc_html($kicker); ?></span>
            <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span>
            <h2 class="enterprise-latest-lead-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2>
            <div class="enterprise-card-meta">
                <span><?php echo esc_html(cp_frontend_post_author($post)); ?></span>
                <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            </div>
            <?php if ($show_excerpt && '' !== $excerpt) : ?><p class="enterprise-latest-lead-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
            <?php if ($show_read_more) : ?><a class="enterprise-read-more" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_homepage_contains_shortcode($shortcode)
{
    $post = get_queried_object();
    return $post instanceof WP_Post && has_shortcode($post->post_content, sanitize_key($shortcode));
}

function cp_post_contains_enterprise_shortcode($post, $shortcodes)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post || '' === trim((string) $post->post_content)) {
        return false;
    }

    foreach ((array) $shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, sanitize_key($shortcode))) {
            return true;
        }
    }

    return false;
}

function cp_publication_homepage_shortcodes()
{
    return [
        'enterprise_homepage_featured',
        'enterprise_category_cards',
        'enterprise_latest_articles',
    ];
}

function cp_is_publication_homepage_request()
{
    if (is_admin() || !(is_front_page() || is_home())) {
        return false;
    }

    return cp_post_contains_enterprise_shortcode(get_queried_object(), cp_publication_homepage_shortcodes());
}

function cp_homepage_contains_feature_shortcode()
{
    return cp_homepage_contains_shortcode('enterprise_homepage_featured');
}

function cp_homepage_should_reserve_featured_article()
{
    return cp_homepage_contains_feature_shortcode() || cp_homepage_contains_shortcode('enterprise_latest_articles');
}

function cp_frontend_publication_classes($classes = [])
{
    $classes = array_merge(['enterprise-publication'], (array) $classes);

    if (cp_is_publication_homepage_request()) {
        $classes[] = 'alignwide';
    }

    $classes = array_values(array_unique(array_filter(array_map('sanitize_html_class', $classes))));
    return implode(' ', $classes);
}

function cp_get_homepage_featured_fallback_article()
{
    static $fallback = false;

    if (false !== $fallback) {
        return $fallback;
    }

    $fallback = null;
    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 8,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ]);

    foreach ($query->posts as $candidate) {
        if ($candidate instanceof WP_Post && cp_is_enterprise_article($candidate)) {
            $fallback = $candidate;
            break;
        }
    }

    wp_reset_postdata();

    return $fallback;
}

function cp_get_homepage_featured_display_article()
{
    static $article = false;

    if (false !== $article) {
        return $article;
    }

    $article = function_exists('cp_get_homepage_featured_article') ? cp_get_homepage_featured_article() : null;
    if (!$article instanceof WP_Post) {
        $article = cp_get_homepage_featured_fallback_article();
    }

    return $article instanceof WP_Post ? $article : null;
}

function cp_homepage_featured_image($post_id, $image_size = 'large')
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return '<div class="enterprise-placeholder-image" aria-hidden="true"><span>Enterprise1979</span></div>';
    }

    /* translators: %s: Article title. */
    $alt = sprintf(__('%s featured image', 'client-portal'), get_the_title($post_id));

    if (has_post_thumbnail($post_id)) {
        $attachment_id = get_post_thumbnail_id($post_id);
        $attachment_alt = $attachment_id ? trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true)) : '';
        if ('' !== $attachment_alt) {
            $alt = $attachment_alt;
        }

        return get_the_post_thumbnail(
            $post_id,
            $image_size,
            [
                'class' => 'enterprise-homepage-feature-thumbnail',
                'loading' => 'eager',
                'fetchpriority' => 'high',
                'decoding' => 'async',
                'alt' => $alt,
            ]
        );
    }

    $hero_url = esc_url_raw((string) get_post_meta($post_id, '_cp_article_hero_image_url', true));
    if ($hero_url && wp_http_validate_url($hero_url)) {
        return sprintf(
            '<img class="enterprise-homepage-feature-thumbnail" src="%1$s" alt="%2$s" loading="eager" fetchpriority="high" decoding="async">',
            esc_url($hero_url),
            esc_attr($alt)
        );
    }

    return '<div class="enterprise-placeholder-image" aria-hidden="true"><span>Enterprise1979</span></div>';
}

function cp_render_homepage_featured_article($post, $content = '', $image_size = 'large', $show_excerpt = true, $show_read_more = true)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return '';
    }

    $about_content = is_string($content) ? trim(do_shortcode(shortcode_unautop($content))) : '';
    $about_content = cp_rewrite_facebook_links_in_html($about_content);
    $excerpt_words = (is_front_page() || is_home()) ? 100 : 42;
    $excerpt = cp_frontend_excerpt($post, $excerpt_words, is_front_page() || is_home());
    $heading_id = 'enterprise-homepage-feature-title-' . absint($post->ID);

    ob_start();
    ?>
    <section class="<?php echo esc_attr(cp_frontend_publication_classes(['enterprise-homepage-feature'])); ?>" aria-labelledby="<?php echo esc_attr($heading_id); ?>">
        <div class="enterprise-homepage-feature-grid <?php echo esc_attr('' !== $about_content ? 'enterprise-homepage-feature-grid-has-about' : 'enterprise-homepage-feature-grid-no-about'); ?>">
            <article class="enterprise-homepage-feature-main">
                <div class="enterprise-homepage-feature-kicker"><?php esc_html_e('Featured Article', 'client-portal'); ?></div>
                <div class="enterprise-homepage-feature-rule" aria-hidden="true"></div>
                <div class="enterprise-homepage-feature-story">
                    <a class="enterprise-homepage-feature-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
                        <?php echo cp_homepage_featured_image($post->ID, $image_size); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                    <div class="enterprise-homepage-feature-copy">
                        <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span>
                        <h2 class="enterprise-homepage-feature-title" id="<?php echo esc_attr($heading_id); ?>"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2>
                        <div class="enterprise-homepage-feature-meta">
                            <span><?php echo esc_html(cp_frontend_post_author($post)); ?></span>
                            <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
                        </div>
                        <?php if ($show_excerpt && '' !== $excerpt) : ?><p class="enterprise-homepage-feature-excerpt"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
                        <?php if ($show_read_more) : ?><a class="enterprise-homepage-feature-link" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
                    </div>
                </div>
            </article>
            <?php if ('' !== $about_content) : ?>
                <aside class="enterprise-homepage-about">
                    <?php echo wp_kses_post($about_content); ?>
                </aside>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function cp_homepage_featured_article_shortcode($attributes, $content = null)
{
    $attributes = shortcode_atts(
        [
            'image_size' => 'large',
            'show_excerpt' => 'yes',
            'show_read_more' => 'yes',
        ],
        $attributes,
        'enterprise_homepage_featured'
    );

    cp_enqueue_frontend_styles();

    $post = cp_get_homepage_featured_display_article();
    if (!$post instanceof WP_Post) {
        return '<section class="' . esc_attr(cp_frontend_publication_classes(['enterprise-homepage-feature'])) . '"><div class="enterprise-empty-state"><p>' . esc_html__('No published Enterprise articles are available yet.', 'client-portal') . '</p></div></section>';
    }

    $image_size = sanitize_key(cp_frontend_attribute($attributes['image_size'], 'large')) ?: 'large';
    $show_excerpt = cp_frontend_boolean_attribute($attributes['show_excerpt'], 'yes');
    $show_read_more = cp_frontend_boolean_attribute($attributes['show_read_more'], 'yes');

    return cp_render_homepage_featured_article($post, (string) $content, $image_size, $show_excerpt, $show_read_more);
}

function cp_enterprise_search_page_option_key()
{
    return 'cp_enterprise_search_page_id';
}

function cp_page_has_enterprise_search_shortcode($post)
{
    return cp_post_contains_enterprise_shortcode($post, ['enterprise_article_search']);
}

function cp_validate_enterprise_search_page_id($page_id)
{
    $page_id = absint($page_id);
    if (!$page_id) {
        return 0;
    }

    $page = get_post($page_id);
    if (!$page instanceof WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status) {
        return 0;
    }

    return cp_page_has_enterprise_search_shortcode($page) ? $page_id : 0;
}

function cp_get_enterprise_search_page_id()
{
    static $page_id = null;

    if (null !== $page_id) {
        return $page_id;
    }

    $filtered_page_id = absint(apply_filters('cp_enterprise_search_page_id', 0));
    if ($filtered_page_id) {
        $page_id = cp_validate_enterprise_search_page_id($filtered_page_id);
        return $page_id;
    }

    $stored_page_id = cp_validate_enterprise_search_page_id(get_option(cp_enterprise_search_page_option_key(), 0));
    if ($stored_page_id) {
        $page_id = $stored_page_id;
        return $page_id;
    }

    $page_id = 0;
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
        's' => 'enterprise_article_search',
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    foreach ($pages as $candidate_id) {
        $candidate_id = cp_validate_enterprise_search_page_id($candidate_id);
        if ($candidate_id) {
            $page_id = $candidate_id;
            update_option(cp_enterprise_search_page_option_key(), $page_id, false);
            break;
        }
    }

    if (!$page_id) {
        delete_option(cp_enterprise_search_page_option_key());
    }

    return $page_id;
}

function cp_get_enterprise_search_page_url()
{
    $page_id = cp_get_enterprise_search_page_id();
    if (!$page_id) {
        return '';
    }

    $url = get_permalink($page_id);
    return $url ? $url : '';
}

function cp_refresh_enterprise_search_page_cache($post_id, $post, $update)
{
    $post_id = absint($post_id);
    if (!$post_id || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) || !$post instanceof WP_Post) {
        return;
    }

    $stored_page_id = absint(get_option(cp_enterprise_search_page_option_key(), 0));
    if ('publish' === $post->post_status && cp_page_has_enterprise_search_shortcode($post)) {
        update_option(cp_enterprise_search_page_option_key(), $post_id, false);
        return;
    }

    if ($stored_page_id === $post_id) {
        delete_option(cp_enterprise_search_page_option_key());
    }
}

function cp_clear_enterprise_search_page_cache_on_delete($post_id)
{
    $post_id = absint($post_id);
    if ($post_id && absint(get_option(cp_enterprise_search_page_option_key(), 0)) === $post_id) {
        delete_option(cp_enterprise_search_page_option_key());
    }
}

function cp_is_enterprise_search_page_request()
{
    if (!is_singular('page')) {
        return false;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return false;
    }

    $search_page_id = cp_get_enterprise_search_page_id();
    return ($search_page_id && absint($post->ID) === $search_page_id) || cp_page_has_enterprise_search_shortcode($post);
}

function cp_get_public_search_keyword($key = 's')
{
    if (!isset($_GET[$key]) || !is_scalar($_GET[$key])) {
        return '';
    }

    return trim(sanitize_text_field(wp_unslash((string) $_GET[$key])));
}

function cp_rewrite_search_form_markup($form)
{
    $search_url = cp_get_enterprise_search_page_url();
    if ('' === $search_url || !is_string($form) || false === stripos($form, '<form')) {
        return $form;
    }

    $search_term = cp_get_public_search_keyword('enterprise_search');
    if ('' === $search_term) {
        $search_term = cp_get_public_search_keyword('s');
    }

    $form = preg_replace('/(<form\b[^>]*\baction=)(["\'])(.*?)\2/i', '$1$2' . esc_url($search_url) . '$2', $form, 1);
    if (null === $form) {
        return '';
    }

    if (!preg_match('/<form\b[^>]*\baction=/i', $form)) {
        $form = preg_replace('/<form\b/i', '<form action="' . esc_url($search_url) . '"', $form, 1);
    }

    $form = preg_replace('/\bname=(["\'])s\1/i', 'name=$1enterprise_search$1', $form);
    $form = preg_replace('/\bid=(["\'])s\1/i', 'id=$1enterprise_search$1', $form);

    if ('' !== $search_term && preg_match('/<input\b[^>]*\btype=(["\'])search\1[^>]*>/i', $form, $matches)) {
        $input = $matches[0];
        if (preg_match('/\bvalue=(["\'])(.*?)\1/i', $input)) {
            $updated_input = preg_replace('/\bvalue=(["\'])(.*?)\1/i', 'value=$1' . esc_attr($search_term) . '$1', $input, 1);
        } else {
            $updated_input = preg_replace('/\/?>$/', ' value="' . esc_attr($search_term) . '">', $input, 1);
        }

        if (is_string($updated_input)) {
            $form = str_replace($input, $updated_input, $form);
        }
    }

    return $form;
}

function cp_route_search_form_to_enterprise_page($form, $args = [])
{
    if (is_admin() || wp_doing_ajax()) {
        return $form;
    }

    return cp_rewrite_search_form_markup($form);
}

function cp_route_search_block_to_enterprise_page($block_content, $block, $block_instance = null)
{
    if (is_admin() || empty($block['blockName']) || 'core/search' !== $block['blockName']) {
        return $block_content;
    }

    return cp_rewrite_search_form_markup($block_content);
}

function cp_redirect_standard_search_to_enterprise_search_page()
{
    if (
        is_admin()
        || wp_doing_ajax()
        || wp_doing_cron()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (function_exists('wp_is_json_request') && wp_is_json_request())
        || is_feed()
        || is_preview()
        || !is_search()
    ) {
        return;
    }

    $search_term = cp_get_public_search_keyword('s');
    if ('' === $search_term) {
        return;
    }

    $search_page_id = cp_get_enterprise_search_page_id();
    $search_url = cp_get_enterprise_search_page_url();
    if (!$search_page_id || '' === $search_url || is_page($search_page_id)) {
        return;
    }

    wp_safe_redirect(add_query_arg('enterprise_search', $search_term, $search_url), 302);
    exit;
}

function cp_get_homepage_category_highlights($limit = 5, $excluded_post_ids = [])
{
    $limit = min(12, max(1, absint($limit)));
    $excluded_post_ids = array_values(array_unique(array_filter(array_map('absint', (array) $excluded_post_ids))));
    $pool_size = min(60, max(40, $limit * 12));

    $query_args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $pool_size,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ];

    if (!empty($excluded_post_ids)) {
        $query_args['post__not_in'] = $excluded_post_ids;
    }

    $query = new WP_Query($query_args);
    $highlights = [];
    $used_categories = [];
    $used_posts = $excluded_post_ids;

    foreach ($query->posts as $candidate) {
        if (!$candidate instanceof WP_Post || in_array(absint($candidate->ID), $used_posts, true) || !cp_is_enterprise_article($candidate)) {
            continue;
        }

        $category = cp_frontend_primary_category_term($candidate->ID);
        if (!$category instanceof WP_Term || in_array(absint($category->term_id), $used_categories, true)) {
            continue;
        }

        $highlights[] = [
            'post' => $candidate,
            'category' => $category,
        ];
        $used_categories[] = absint($category->term_id);
        $used_posts[] = absint($candidate->ID);

        if (count($highlights) >= $limit) {
            break;
        }
    }

    wp_reset_postdata();

    return $highlights;
}

function cp_render_frontend_search_card($post_id, $image_size = 'medium_large')
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }
    ?>
    <article class="enterprise-search-card">
        <a class="enterprise-search-card-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
            <?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-search-card-thumbnail')); ?>
        </a>
        <div class="enterprise-search-card-content">
            <span class="enterprise-search-card-category"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span>
            <h3 class="enterprise-search-card-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <div class="enterprise-search-card-author"><?php echo esc_html(cp_frontend_post_author($post)); ?></div>
            <time class="enterprise-search-card-date" datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
        </div>
    </article>
    <?php
}

function cp_get_other_stories($current_post_id, $limit = 4)
{
    $current_post_id = absint($current_post_id);
    $limit = min(4, max(1, absint($limit)));

    if (!$current_post_id) {
        return [];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => max(24, $limit * 12),
        'post__not_in' => [$current_post_id],
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ]);

    if (!$query->have_posts()) {
        wp_reset_postdata();
        return [];
    }

    $candidates = [];
    foreach ($query->posts as $candidate) {
        if ($candidate instanceof WP_Post && cp_is_enterprise_article($candidate)) {
            $candidates[] = $candidate;
        }
    }

    wp_reset_postdata();

    $selected = [];
    $selected_ids = [];
    $seen_categories = [];

    foreach ($candidates as $candidate) {
        $category = cp_frontend_primary_category_term($candidate->ID);
        $category_key = $category instanceof WP_Term ? (int) $category->term_id : 0;

        if (isset($seen_categories[$category_key])) {
            continue;
        }

        $selected[] = $candidate;
        $selected_ids[$candidate->ID] = true;
        $seen_categories[$category_key] = true;

        if (count($selected) >= $limit) {
            return $selected;
        }
    }

    foreach ($candidates as $candidate) {
        if (isset($selected_ids[$candidate->ID])) {
            continue;
        }

        $selected[] = $candidate;
        $selected_ids[$candidate->ID] = true;

        if (count($selected) >= $limit) {
            break;
        }
    }

    return $selected;
}

function cp_render_other_story_card($post)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return;
    }
    ?>
    <article class="enterprise-other-story-card-wrap">
        <a class="enterprise-other-story-card" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr(get_the_title($post)); ?>">
            <span class="enterprise-other-story-image">
                <?php echo wp_kses_post(cp_frontend_post_image($post->ID, 'medium_large', 'enterprise-other-story-thumbnail')); ?>
            </span>
            <span class="enterprise-other-story-copy">
                <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span>
                <h3 class="enterprise-other-story-title"><?php echo esc_html(get_the_title($post)); ?></h3>
                <time class="enterprise-other-story-date" datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            </span>
        </a>
    </article>
    <?php
}

function cp_render_other_stories($current_post_id)
{
    $stories = cp_get_other_stories($current_post_id, 4);
    if (empty($stories)) {
        return '';
    }

    ob_start();
    ?>
    <section class="enterprise-other-stories" aria-labelledby="enterprise-other-stories-title">
        <div class="enterprise-other-stories-heading">
            <h2 id="enterprise-other-stories-title"><?php esc_html_e('OTHER STORIES', 'client-portal'); ?></h2>
        </div>

        <div class="enterprise-other-stories-grid">
            <?php foreach ($stories as $story) : ?>
                <?php cp_render_other_story_card($story); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function cp_category_posts_shortcode($attributes)
{
    $attributes = shortcode_atts(
        [
            'category' => '',
            'posts_per_page' => 10,
            'title' => '',
            'subtitle' => '',
            'show_header' => 'yes',
            'show_excerpt' => 'yes',
            'show_read_more' => 'yes',
            'image_size' => 'large',
        ],
        $attributes,
        'enterprise_category_posts'
    );

    cp_enqueue_frontend_styles();

    $category_slug = sanitize_title(cp_frontend_attribute($attributes['category']));
    if (!$category_slug) {
        return '<div class="enterprise-shortcode-notice">' . esc_html__('Please select a category for this article archive.', 'client-portal') . '</div>';
    }

    $category_term = get_category_by_slug($category_slug);
    $custom_title = sanitize_text_field(cp_frontend_attribute($attributes['title']));
    $category_name = $custom_title ?: cp_frontend_category_name($category_slug);
    $subtitle_override = sanitize_textarea_field(cp_frontend_attribute($attributes['subtitle']));
    $category_description = $category_term instanceof WP_Term ? sanitize_textarea_field($category_term->description) : '';
    $subtitle = '' !== $subtitle_override ? $subtitle_override : $category_description;
    $posts_per_page = 10;
    $show_header = cp_frontend_boolean_attribute($attributes['show_header'], 'yes');
    $show_excerpt = cp_frontend_boolean_attribute($attributes['show_excerpt'], 'yes');
    $show_read_more = cp_frontend_boolean_attribute($attributes['show_read_more'], 'yes');
    $image_size = sanitize_key(cp_frontend_attribute($attributes['image_size'], 'large')) ?: 'large';
    $paged = cp_frontend_current_page();
    $query_args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ];

    if ($category_slug) {
        $query_args['category_name'] = $category_slug;
    }

    $query = new WP_Query($query_args);

    ob_start();
    ?>
    <section class="enterprise-publication enterprise-category-archive">
        <?php if ($show_header) : ?>
            <header class="enterprise-category-hero">
                <div class="enterprise-section-bar"><span><?php echo esc_html($category_name); ?></span></div>
                <div class="enterprise-category-hero-copy">
                    <span class="enterprise-section-kicker"><?php esc_html_e('Enterprise1979 Publication', 'client-portal'); ?></span>
                    <h1><?php echo esc_html($category_name); ?></h1>
                    <?php if ($subtitle) : ?><p><?php echo nl2br(esc_html($subtitle)); ?></p><?php endif; ?>
                </div>
            </header>
        <?php endif; ?>

        <div class="enterprise-post-list-wrap">
            <div class="enterprise-post-list">
                <?php if ($query->have_posts()) : ?>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <?php cp_render_frontend_archive_post(get_the_ID(), $image_size, $show_excerpt, $show_read_more, $category_name); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <?php cp_render_frontend_empty_state(__('No published articles are available in this category yet.', 'client-portal')); ?>
                <?php endif; ?>
            </div>

            <?php if ($query->max_num_pages > 1) : ?>
                <?php
                $large_number = 999999999;
                $pagination = paginate_links([
                    'base' => str_replace($large_number, '%#%', get_pagenum_link($large_number)),
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => (int) $query->max_num_pages,
                    'mid_size' => 2,
                    'end_size' => 1,
                    'type' => 'list',
                    'prev_text' => __('Previous Page', 'client-portal'),
                    'next_text' => __('Next Page', 'client-portal'),
                    'add_args' => cp_frontend_pagination_query_args(),
                ]);
                ?>
                <?php if ($pagination) : ?><nav class="enterprise-pagination" aria-label="<?php esc_attr_e('Article pagination', 'client-portal'); ?>"><?php echo wp_kses_post($pagination); ?></nav><?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

function cp_latest_articles_shortcode($attributes)
{
    $attributes = shortcode_atts(
        [
            'count' => 4,
            'category' => '',
            'columns' => 2,
            'title' => '',
            'subtitle' => '',
            'show_header' => 'no',
            'show_excerpt' => 'yes',
            'show_read_more' => 'yes',
            'image_size' => 'large',
        ],
        $attributes,
        'enterprise_latest_articles'
    );

    cp_enqueue_frontend_styles();

    $count = min(12, max(1, absint(cp_frontend_attribute($attributes['count'], '4'))));
    $category_slug = sanitize_title(cp_frontend_attribute($attributes['category']));
    $columns = cp_frontend_columns($attributes['columns']);
    $title = sanitize_text_field(cp_frontend_attribute($attributes['title']));
    $subtitle = sanitize_textarea_field(cp_frontend_attribute($attributes['subtitle']));
    $show_header = cp_frontend_boolean_attribute($attributes['show_header'], 'no');
    $show_excerpt = cp_frontend_boolean_attribute($attributes['show_excerpt'], 'yes');
    $show_read_more = cp_frontend_boolean_attribute($attributes['show_read_more'], 'yes');
    $image_size = sanitize_key(cp_frontend_attribute($attributes['image_size'], 'large')) ?: 'large';
    $homepage_feature = null;
    $use_homepage_feature_as_lead = false;
    $excluded_post_ids = [];

    if (!$category_slug && (is_front_page() || is_home())) {
        $homepage_feature = cp_get_homepage_featured_display_article();
        if ($homepage_feature instanceof WP_Post) {
            $excluded_post_ids[] = absint($homepage_feature->ID);
            $use_homepage_feature_as_lead = !cp_homepage_contains_feature_shortcode();
        }
    }

    $query_count = $use_homepage_feature_as_lead ? max(0, $count - 1) : $count;

    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => max(1, $query_count),
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ];

    if ($category_slug) {
        $args['category_name'] = $category_slug;
    }

    if (!empty($excluded_post_ids)) {
        $args['post__not_in'] = array_values(array_unique(array_map('absint', $excluded_post_ids)));
    }

    $query = $query_count > 0 ? new WP_Query($args) : null;
    $has_articles = $use_homepage_feature_as_lead || ($query instanceof WP_Query && $query->have_posts());

    ob_start();
    ?>
    <section class="<?php echo esc_attr(cp_frontend_publication_classes(['enterprise-latest-articles'])); ?>">
        <?php if ($show_header) : ?>
            <?php cp_render_frontend_section_header($title ?: cp_frontend_category_name($category_slug, __('Latest Articles', 'client-portal')), $subtitle, __('Latest', 'client-portal')); ?>
        <?php endif; ?>

        <?php if ($has_articles) : ?>
            <div class="enterprise-latest-layout">
                <?php if ($use_homepage_feature_as_lead && $homepage_feature instanceof WP_Post) : ?>
                    <?php cp_render_frontend_lead_article($homepage_feature->ID, $image_size, $show_excerpt, $show_read_more, __('Homepage Feature', 'client-portal')); ?>
                <?php elseif ($query instanceof WP_Query && $query->have_posts()) : ?>
                    <?php $query->the_post(); ?>
                    <?php cp_render_frontend_lead_article(get_the_ID(), $image_size, $show_excerpt, $show_read_more); ?>
                <?php endif; ?>

                <?php if ($query instanceof WP_Query && $query->have_posts()) : ?>
                    <div class="enterprise-card-grid enterprise-latest-support enterprise-columns-<?php echo esc_attr($columns); ?>">
                        <?php while ($query->have_posts()) : $query->the_post(); ?>
                            <?php cp_render_frontend_article_card(get_the_ID(), $image_size, $show_excerpt, $show_read_more); ?>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php cp_render_frontend_empty_state(__('No published articles are available yet.', 'client-portal')); ?>
        <?php endif; ?>
    </section>
    <?php
    if ($query instanceof WP_Query) {
        wp_reset_postdata();
    }
    return ob_get_clean();
}

function cp_category_cards_shortcode($attributes)
{
    $attributes = shortcode_atts(
        [
            'categories' => 'news,sports,literary,features',
            'count_per_category' => 1,
            'columns' => 2,
            'selection' => 'manual',
            'section_count' => '',
            'show_empty' => '',
            'title' => '',
            'subtitle' => '',
            'show_header' => 'no',
            'show_excerpt' => 'yes',
            'show_read_more' => 'yes',
            'image_size' => 'medium_large',
        ],
        $attributes,
        'enterprise_category_cards'
    );

    cp_enqueue_frontend_styles();

    $selection = sanitize_key(cp_frontend_attribute($attributes['selection'], 'manual'));
    if (!in_array($selection, ['manual', 'auto'], true)) {
        $selection = 'manual';
    }
    $raw_categories = array_filter(array_map('trim', explode(',', sanitize_text_field(cp_frontend_attribute($attributes['categories'])))));
    $categories = array_slice(array_values(array_unique(array_map('sanitize_title', $raw_categories))), 0, 12);
    $count = min(4, max(1, absint(cp_frontend_attribute($attributes['count_per_category'], '1'))));
    $columns = cp_frontend_columns($attributes['columns']);
    $section_count = '' === cp_frontend_attribute($attributes['section_count'], '') ? ('auto' === $selection ? 5 : count($categories)) : absint(cp_frontend_attribute($attributes['section_count']));
    $section_count = min(12, max(1, $section_count));
    $default_show_empty = (is_front_page() || is_home()) ? 'no' : 'yes';
    $show_empty_value = cp_frontend_attribute($attributes['show_empty'], '');
    $show_empty = cp_frontend_boolean_attribute('' === $show_empty_value ? $default_show_empty : $show_empty_value, $default_show_empty);
    $title = sanitize_text_field(cp_frontend_attribute($attributes['title']));
    $subtitle = sanitize_textarea_field(cp_frontend_attribute($attributes['subtitle']));
    $show_header = cp_frontend_boolean_attribute($attributes['show_header'], 'no');
    $show_excerpt = cp_frontend_boolean_attribute($attributes['show_excerpt'], 'yes');
    $show_read_more = cp_frontend_boolean_attribute($attributes['show_read_more'], 'yes');
    $image_size = sanitize_key(cp_frontend_attribute($attributes['image_size'], 'medium_large')) ?: 'medium_large';
    $excluded_post_ids = [];

    if ((is_front_page() || is_home()) && cp_homepage_should_reserve_featured_article()) {
        $homepage_feature = cp_get_homepage_featured_display_article();
        if ($homepage_feature instanceof WP_Post) {
            $excluded_post_ids[] = absint($homepage_feature->ID);
        }
    }

    $groups = [];
    $used_post_ids = $excluded_post_ids;

    if ('auto' === $selection) {
        foreach (cp_get_homepage_category_highlights($section_count, $excluded_post_ids) as $highlight) {
            if (empty($highlight['post']) || empty($highlight['category']) || !$highlight['post'] instanceof WP_Post || !$highlight['category'] instanceof WP_Term) {
                continue;
            }

            $groups[] = [
                'category_name' => $highlight['category']->name,
                'post_ids' => [absint($highlight['post']->ID)],
            ];
        }
    } else {
        foreach ($categories as $category_slug) {
            $category_name = cp_frontend_category_name($category_slug);
            $query_args = [
                'post_type' => 'post',
                'post_status' => 'publish',
                'category_name' => $category_slug,
                'posts_per_page' => $count,
                'orderby' => 'date',
                'order' => 'DESC',
                'ignore_sticky_posts' => true,
                'no_found_rows' => true,
                'meta_query' => cp_enterprise_article_meta_query(),
            ];

            if (!empty($used_post_ids)) {
                $query_args['post__not_in'] = array_values(array_unique(array_map('absint', $used_post_ids)));
            }

            $query = new WP_Query($query_args);
            $post_ids = [];
            foreach ($query->posts as $candidate) {
                if ($candidate instanceof WP_Post && cp_is_enterprise_article($candidate) && !in_array(absint($candidate->ID), $used_post_ids, true)) {
                    $post_ids[] = absint($candidate->ID);
                    $used_post_ids[] = absint($candidate->ID);
                }
            }
            wp_reset_postdata();

            if (empty($post_ids) && !$show_empty) {
                continue;
            }

            $groups[] = [
                'category_name' => $category_name,
                'post_ids' => $post_ids,
            ];
        }
    }

    $grid_classes = [
        'enterprise-category-group-grid',
        'enterprise-columns-' . $columns,
        'enterprise-category-group-grid-count-' . count($groups),
    ];

    // Use the centered five-section magazine layout only when the shortcode
    // requests at least three columns. Applying the six-track placement rules
    // to a two-column grid creates implicit columns and uneven card widths.
    if (5 === count($groups) && $columns >= 3) {
        $grid_classes[] = 'enterprise-category-group-grid-five';
    }

    $category_card_classes = ['enterprise-category-cards'];
    $is_landing_page_cards = is_front_page() || is_home();
    if ($is_landing_page_cards) {
        $category_card_classes[] = 'enterprise-landing-category-cards';
    }

    ob_start();
    ?>
    <section class="<?php echo esc_attr(cp_frontend_publication_classes($category_card_classes)); ?>">
        <?php if ($show_header) : ?>
            <?php cp_render_frontend_section_header($title, $subtitle, __('Sections', 'client-portal')); ?>
        <?php endif; ?>

        <?php if (!empty($groups)) : ?>
        <div class="<?php echo esc_attr(implode(' ', array_map('sanitize_html_class', $grid_classes))); ?>">
            <?php foreach ($groups as $group) : ?>
                <section class="enterprise-section-group">
                    <div class="enterprise-section-bar"><span><?php echo esc_html($group['category_name']); ?></span></div>
                    <div class="enterprise-section-list">
                        <?php if (!empty($group['post_ids'])) : ?>
                            <?php foreach ($group['post_ids'] as $post_id) : ?>
                                <?php cp_render_frontend_article_card($post_id, $image_size, $show_excerpt, $show_read_more, '', !$is_landing_page_cards, $is_landing_page_cards ? 80 : 24, $is_landing_page_cards); ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php cp_render_frontend_empty_state(__('No published articles in this section yet.', 'client-portal')); ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
        <?php elseif ($show_empty) : ?>
            <?php cp_render_frontend_empty_state(__('No published articles are available yet.', 'client-portal')); ?>
        <?php endif; ?>
    </section>
    <?php
    return ob_get_clean();
}

function cp_is_enterprise_article($post = null)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post || 'post' !== $post->post_type) {
        return false;
    }

    return metadata_exists('post', $post->ID, '_cp_article_blocks')
        || metadata_exists('post', $post->ID, '_cp_article_hero_image_url');
}

function cp_single_article_body_class($classes)
{
    if (is_singular('post') && cp_is_enterprise_article(get_queried_object())) {
        $classes[] = 'enterprise-single-article-page';
    }

    if (cp_is_publication_homepage_request()) {
        $classes[] = 'enterprise-publication-homepage';
    }

    if (cp_is_enterprise_search_page_request()) {
        $classes[] = 'enterprise-article-search-page';
    }

    return $classes;
}

function cp_single_article_post_class($classes, $class = '', $post_id = 0)
{
    if (is_singular('post') && cp_is_enterprise_article($post_id)) {
        $classes[] = 'enterprise-single-article-post';
    }

    return $classes;
}

function cp_render_single_article_content($content)
{
    if (is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $post = get_post();
    if (!$post instanceof WP_Post || !cp_is_enterprise_article($post)) {
        return $content;
    }

    if (cp_is_rendering_single_article_layout()) {
        return $content;
    }

    cp_is_rendering_single_article_layout(true);
    $layout = cp_render_single_article_layout($post, $content);
    cp_is_rendering_single_article_layout(false);

    return $layout;
}

function cp_render_single_article_layout($post, $content)
{
    $category = cp_frontend_primary_category_term($post->ID);
    $category_name = $category instanceof WP_Term ? $category->name : __('General', 'client-portal');
    $category_url = $category instanceof WP_Term ? get_category_link($category) : '';
    $excerpt = trim(wp_strip_all_tags((string) $post->post_excerpt));
    $rich_title = function_exists('cp_get_article_rich_title') ? cp_get_article_rich_title($post) : '';
    $rich_excerpt = function_exists('cp_get_article_rich_excerpt') ? cp_get_article_rich_excerpt($post) : '';
    $related = cp_render_related_articles($post->ID);
    $has_related = '' !== trim($related);

    ob_start();
    ?>
    <article class="enterprise-single-article">
        <nav class="enterprise-single-breadcrumb" aria-label="<?php esc_attr_e('Article breadcrumb', 'client-portal'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'client-portal'); ?></a>
            <span aria-hidden="true">/</span>
            <?php if ($category_url && !is_wp_error($category_url)) : ?>
                <a href="<?php echo esc_url($category_url); ?>"><?php echo esc_html($category_name); ?></a>
            <?php else : ?>
                <span><?php echo esc_html($category_name); ?></span>
            <?php endif; ?>
        </nav>

        <header class="enterprise-single-header">
            <h1><?php echo '' !== $rich_title ? wp_kses($rich_title, cp_rich_heading_allowed_html()) : esc_html(get_the_title($post)); ?></h1>
            <?php if ('' !== $rich_excerpt) : ?><div class="enterprise-single-deck enterprise-single-deck-rich"><?php echo wp_kses($rich_excerpt, cp_rich_summary_allowed_html()); ?></div><?php elseif ('' !== $excerpt) : ?><p class="enterprise-single-deck"><?php echo esc_html($excerpt); ?></p><?php endif; ?>
            <div class="enterprise-single-meta">
                <span class="enterprise-category-label"><?php echo esc_html($category_name); ?></span>
                <span><?php echo esc_html(cp_frontend_post_author($post)); ?></span>
                <time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            </div>
        </header>

        <div class="enterprise-single-grid <?php echo esc_attr($has_related ? 'enterprise-single-grid-has-related' : 'enterprise-single-grid-no-related'); ?>">
            <div class="enterprise-single-main">
                <figure class="enterprise-single-hero-media">
                    <?php echo wp_kses_post(cp_frontend_post_image($post->ID, 'full', 'enterprise-single-hero-image')); ?>
                </figure>

                <div class="enterprise-article-body">
                    <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            </div>

            <?php if ($has_related) : ?>
                <?php echo $related; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </div>

        <?php echo cp_render_other_stories($post->ID); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </article>
    <?php
    return ob_get_clean();
}

function cp_render_related_articles($post_id)
{
    $post_id = absint($post_id);
    $category_ids = wp_get_post_categories($post_id);
    if (!$post_id || empty($category_ids)) {
        return '';
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 4,
        'cat' => absint($category_ids[0]),
        'post__not_in' => [$post_id],
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
    ]);

    if (!$query->have_posts()) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    ?>
    <aside class="enterprise-related-articles" aria-labelledby="enterprise-related-heading">
        <h2 id="enterprise-related-heading"><?php esc_html_e('Related Stories', 'client-portal'); ?></h2>
        <div class="enterprise-related-list">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <a class="enterprise-related-card" href="<?php echo esc_url(get_permalink()); ?>">
                    <span class="enterprise-related-card-row">
                        <span class="enterprise-related-image"><?php echo wp_kses_post(cp_frontend_post_image(get_the_ID(), 'thumbnail', 'enterprise-related-thumbnail')); ?></span>
                        <span class="enterprise-related-copy">
                            <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category(get_the_ID())); ?></span>
                            <span class="enterprise-related-title"><?php echo esc_html(get_the_title()); ?></span>
                        </span>
                    </span>
                    <span class="enterprise-related-meta"><?php echo esc_html(cp_frontend_post_author(get_post())); ?> &middot; <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time></span>
                </a>
            <?php endwhile; ?>
        </div>
    </aside>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

function cp_article_search_shortcode($attributes)
{
    $attributes = shortcode_atts(
        [
            'posts_per_page' => 15,
            'columns' => 5,
            'rows' => 3,
            'show_search_heading' => 'yes',
            'image_size' => 'medium_large',
        ],
        $attributes,
        'enterprise_article_search'
    );

    cp_enqueue_frontend_styles();

    $columns = min(6, max(1, absint(cp_frontend_attribute($attributes['columns'], '5'))));
    $rows = min(6, max(1, absint(cp_frontend_attribute($attributes['rows'], '3'))));
    $posts_per_page = min(60, max(1, absint(cp_frontend_attribute($attributes['posts_per_page'], '15'))));
    $posts_per_page = min($posts_per_page, $columns * $rows);
    $show_search_heading = cp_frontend_boolean_attribute($attributes['show_search_heading'], 'yes');
    $image_size = sanitize_key(cp_frontend_attribute($attributes['image_size'], 'medium_large')) ?: 'medium_large';
    $search_term = (isset($_GET['enterprise_search']) && is_scalar($_GET['enterprise_search'])) ? sanitize_text_field(wp_unslash($_GET['enterprise_search'])) : '';
    $paged = cp_frontend_current_page();

    $post_args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
        'meta_query' => cp_enterprise_article_meta_query(),
    ];

    if ('' !== $search_term) {
        $post_args['s'] = $search_term;
    }

    $query = new WP_Query($post_args);
    $search_post_ids = [];
    foreach ($query->posts as $search_post) {
        if ($search_post instanceof WP_Post && cp_is_enterprise_article($search_post)) {
            $search_post_ids[] = absint($search_post->ID);
        }
    }
    $form_action = get_permalink();
    if (!$form_action) {
        $request_path = isset($GLOBALS['wp']->request) ? (string) $GLOBALS['wp']->request : '';
        $form_action = home_url('/' . ltrim($request_path, '/'));
    }

    ob_start();
    ?>
    <section class="enterprise-search-page enterprise-search-columns-<?php echo esc_attr($columns); ?>" style="<?php echo esc_attr('--enterprise-search-columns: ' . $columns . ';'); ?>">
        <form class="enterprise-search-form" method="get" action="<?php echo esc_url($form_action); ?>" role="search">
            <label class="screen-reader-text" for="enterprise-search-input"><?php esc_html_e('Search articles', 'client-portal'); ?></label>
            <input id="enterprise-search-input" type="search" name="enterprise_search" placeholder="<?php echo esc_attr__('Search articles...', 'client-portal'); ?>" value="<?php echo esc_attr($search_term); ?>">
            <button type="submit" aria-label="<?php echo esc_attr__('Search', 'client-portal'); ?>">
                <span aria-hidden="true">&#128269;</span>
                <span class="enterprise-search-button-text"><?php esc_html_e('Search', 'client-portal'); ?></span>
            </button>
        </form>

        <?php if ($show_search_heading) : ?>
            <div class="enterprise-search-header">
                <h2>
                    <?php
                    if ('' !== $search_term) {
                        echo esc_html__('Search Results For:', 'client-portal') . ' &ldquo;' . esc_html($search_term) . '&rdquo;';
                    } else {
                        esc_html_e('All Articles', 'client-portal');
                    }
                    ?>
                </h2>
            </div>
        <?php endif; ?>

        <?php if (!empty($search_post_ids)) : ?>
            <div class="enterprise-search-grid">
                <?php foreach ($search_post_ids as $search_post_id) : ?>
                    <?php cp_render_frontend_search_card($search_post_id, $image_size); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="enterprise-search-empty">
                <h3><?php esc_html_e('No articles found.', 'client-portal'); ?></h3>
                <p><?php esc_html_e('Try searching for another keyword or browse the latest articles.', 'client-portal'); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($query->max_num_pages > 1) : ?>
            <?php
            $large_number = 999999999;
            $pagination_args = [
                'base' => str_replace($large_number, '%#%', get_pagenum_link($large_number)),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => (int) $query->max_num_pages,
                'type' => 'list',
                'prev_text' => __('Previous', 'client-portal'),
                'next_text' => __('Next', 'client-portal'),
            ];

            if ('' !== $search_term) {
                $pagination_args['add_args'] = ['enterprise_search' => $search_term];
            }

            $pagination = paginate_links($pagination_args);
            ?>
            <?php if ($pagination) : ?><nav class="enterprise-search-pagination" aria-label="<?php esc_attr_e('Search results pagination', 'client-portal'); ?>"><?php echo wp_kses_post($pagination); ?></nav><?php endif; ?>
        <?php endif; ?>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}
