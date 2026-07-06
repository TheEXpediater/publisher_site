<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_frontend_shortcodes()
{
    add_shortcode('enterprise_category_posts', 'cp_category_posts_shortcode');
    add_shortcode('enterprise_latest_articles', 'cp_latest_articles_shortcode');
    add_shortcode('enterprise_category_cards', 'cp_category_cards_shortcode');
}

add_action('init', 'cp_register_frontend_shortcodes');

function cp_enqueue_frontend_styles()
{
    wp_enqueue_style('cp-frontend', cp_url('assets/css/frontend.css'), [], CP_VERSION);
}

function cp_frontend_current_page()
{
    return max(1, absint(get_query_var('paged')), absint(get_query_var('page')));
}

function cp_frontend_attribute($value, $default = '')
{
    return is_scalar($value) ? (string) $value : $default;
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

function cp_frontend_post_category($post_id, $fallback = '')
{
    $categories = get_the_category($post_id);
    if (!empty($categories)) {
        return $categories[0]->name;
    }

    return $fallback ?: __('General', 'client-portal');
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

function cp_frontend_excerpt($post, $words = 34)
{
    $excerpt = get_the_excerpt($post);
    return wp_trim_words(wp_strip_all_tags($excerpt), absint($words), '…');
}

function cp_frontend_fallback_articles($category_name, $count = 3)
{
    $category_name = sanitize_text_field($category_name);
    $titles = [
        sprintf(__('Sample %s Article Headline', 'client-portal'), $category_name),
        sprintf(__('Latest Updates from %s', 'client-portal'), $category_name),
        sprintf(__('Stories and Perspectives: %s', 'client-portal'), $category_name),
        sprintf(__('Inside the %s Community', 'client-portal'), $category_name),
    ];
    $fallbacks = [];
    $count = min(12, max(1, absint($count)));

    for ($index = 0; $index < $count; $index++) {
        $fallbacks[] = [
            'title' => $titles[$index % count($titles)],
            'category' => $category_name,
            'date' => date_i18n(get_option('date_format'), current_time('timestamp') - (DAY_IN_SECONDS * ($index + 1))),
            'excerpt' => __('Published stories will appear here automatically. Create and publish an article in the Enterprise1979 Publisher Portal to replace this preview.', 'client-portal'),
        ];
    }

    return $fallbacks;
}

function cp_render_frontend_archive_post($post_id, $image_size, $show_excerpt, $show_read_more, $category_name)
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }
    ?>
    <article class="enterprise-post-item">
        <a class="enterprise-post-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>">
            <?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-post-thumbnail')); ?>
        </a>
        <div class="enterprise-post-content">
            <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID, $category_name)); ?></span>
            <h3 class="enterprise-post-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <time class="enterprise-post-date" datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time>
            <?php if ($show_excerpt) : ?><p class="enterprise-post-excerpt"><?php echo esc_html(cp_frontend_excerpt($post)); ?></p><?php endif; ?>
            <?php if ($show_read_more) : ?><a class="enterprise-read-more" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_render_frontend_fallback_row($article, $show_excerpt, $show_read_more)
{
    ?>
    <article class="enterprise-post-item enterprise-post-item-fallback">
        <div class="enterprise-post-image"><?php echo wp_kses_post(cp_frontend_post_image(0, 'large', 'enterprise-post-thumbnail')); ?></div>
        <div class="enterprise-post-content">
            <span class="enterprise-category-label"><?php echo esc_html($article['category']); ?></span>
            <h3 class="enterprise-post-title"><?php echo esc_html($article['title']); ?></h3>
            <span class="enterprise-post-date"><?php echo esc_html($article['date']); ?></span>
            <?php if ($show_excerpt) : ?><p class="enterprise-post-excerpt"><?php echo esc_html($article['excerpt']); ?></p><?php endif; ?>
            <?php if ($show_read_more) : ?><span class="enterprise-read-more enterprise-read-more-disabled"><?php esc_html_e('Coming Soon', 'client-portal'); ?></span><?php endif; ?>
        </div>
    </article>
    <?php
}

function cp_category_posts_shortcode($attributes)
{
    $attributes = shortcode_atts(
        [
            'category' => '',
            'posts_per_page' => 6,
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
    $posts_per_page = min(24, max(1, absint(cp_frontend_attribute($attributes['posts_per_page'], '6'))));
    $show_header = 'yes' === sanitize_key(cp_frontend_attribute($attributes['show_header'], 'yes'));
    $show_excerpt = 'yes' === sanitize_key(cp_frontend_attribute($attributes['show_excerpt'], 'yes'));
    $show_read_more = 'yes' === sanitize_key(cp_frontend_attribute($attributes['show_read_more'], 'yes'));
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
    ];
    if ($category_slug) {
        $query_args['category_name'] = $category_slug;
    }
    $query = new WP_Query($query_args);

    ob_start();
    ?>
    <section class="enterprise-category-archive">
        <?php if ($show_header) : ?>
            <header class="enterprise-category-hero">
                <div class="enterprise-category-hero-inner<?php echo esc_attr($subtitle ? '' : ' enterprise-category-hero-inner-single'); ?>">
                    <div class="enterprise-category-title-area"><span class="enterprise-category-kicker"><?php esc_html_e('Enterprise1979 Publication', 'client-portal'); ?></span><h1><?php echo esc_html($category_name); ?></h1></div>
                    <?php if ($subtitle) : ?><div class="enterprise-category-description"><p><?php echo nl2br(esc_html($subtitle)); ?></p></div><?php endif; ?>
                </div>
            </header>
        <?php endif; ?>
        <div class="enterprise-post-list">
            <?php if ($query->have_posts()) : ?>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php cp_render_frontend_archive_post(get_the_ID(), $image_size, $show_excerpt, $show_read_more, $category_name); ?>
                <?php endwhile; ?>
            <?php else : ?>
                <?php foreach (cp_frontend_fallback_articles($category_name, 3) as $fallback) : ?>
                    <?php cp_render_frontend_fallback_row($fallback, $show_excerpt, $show_read_more); ?>
                <?php endforeach; ?>
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
                'type' => 'list',
                'prev_text' => __('Previous', 'client-portal'),
                'next_text' => __('Next', 'client-portal'),
            ]);
            ?>
            <?php if ($pagination) : ?><nav class="enterprise-post-pagination" aria-label="<?php esc_attr_e('Article pagination', 'client-portal'); ?>"><?php echo wp_kses_post($pagination); ?></nav><?php endif; ?>
        <?php endif; ?>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

function cp_render_frontend_article_card($post_id, $image_size = 'large')
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return;
    }
    ?>
    <article class="enterprise-article-card">
        <a class="enterprise-card-image" href="<?php echo esc_url(get_permalink($post)); ?>" aria-label="<?php echo esc_attr($post->post_title); ?>"><?php echo wp_kses_post(cp_frontend_post_image($post->ID, $image_size, 'enterprise-card-thumbnail')); ?></a>
        <div class="enterprise-card-content">
            <span class="enterprise-category-label"><?php echo esc_html(cp_frontend_post_category($post->ID)); ?></span>
            <h3 class="enterprise-card-title"><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h3>
            <div class="enterprise-card-meta"><span><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></span><time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('', $post)); ?></time></div>
            <p class="enterprise-card-excerpt"><?php echo esc_html(cp_frontend_excerpt($post, 24)); ?></p>
            <a class="enterprise-read-more" href="<?php echo esc_url(get_permalink($post)); ?>"><?php esc_html_e('Read More', 'client-portal'); ?> <span aria-hidden="true">&rarr;</span></a>
        </div>
    </article>
    <?php
}

function cp_render_frontend_fallback_card($fallback)
{
    if (!is_array($fallback)) {
        $fallback = cp_frontend_fallback_articles(sanitize_text_field($fallback), 1)[0];
    }
    ?>
    <article class="enterprise-article-card enterprise-card-fallback">
        <div class="enterprise-card-image"><?php echo wp_kses_post(cp_frontend_post_image(0, 'large', 'enterprise-card-thumbnail')); ?></div>
        <div class="enterprise-card-content"><span class="enterprise-category-label"><?php echo esc_html($fallback['category']); ?></span><h3 class="enterprise-card-title"><?php echo esc_html($fallback['title']); ?></h3><div class="enterprise-card-meta"><span><?php esc_html_e('Enterprise1979', 'client-portal'); ?></span><span><?php echo esc_html($fallback['date']); ?></span></div><p class="enterprise-card-excerpt"><?php echo esc_html($fallback['excerpt']); ?></p><span class="enterprise-read-more enterprise-read-more-disabled"><?php esc_html_e('Coming Soon', 'client-portal'); ?></span></div>
    </article>
    <?php
}

function cp_latest_articles_shortcode($attributes)
{
    $attributes = shortcode_atts(['count' => 4, 'category' => '', 'columns' => 2], $attributes, 'enterprise_latest_articles');
    cp_enqueue_frontend_styles();
    $count = min(12, max(1, absint(cp_frontend_attribute($attributes['count'], '4'))));
    $category_slug = sanitize_title(cp_frontend_attribute($attributes['category']));
    $columns = cp_frontend_columns($attributes['columns']);
    $args = ['post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $count, 'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true];
    if ($category_slug) {
        $args['category_name'] = $category_slug;
    }
    $query = new WP_Query($args);
    $category_name = cp_frontend_category_name($category_slug, __('Latest Articles', 'client-portal'));

    ob_start();
    ?><section class="enterprise-latest-articles"><div class="enterprise-card-grid enterprise-columns-<?php echo esc_attr($columns); ?>">
        <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); cp_render_frontend_article_card(get_the_ID()); endwhile; else : foreach (cp_frontend_fallback_articles($category_name, min(3, $count)) as $fallback) : cp_render_frontend_fallback_card($fallback); endforeach; endif; ?>
    </div></section><?php
    wp_reset_postdata();
    return ob_get_clean();
}

function cp_category_cards_shortcode($attributes)
{
    $attributes = shortcode_atts(['categories' => 'news,sports,literary,features', 'count_per_category' => 1, 'columns' => 2], $attributes, 'enterprise_category_cards');
    cp_enqueue_frontend_styles();
    $raw_categories = array_filter(array_map('trim', explode(',', sanitize_text_field(cp_frontend_attribute($attributes['categories'])))));
    $categories = array_slice(array_values(array_unique(array_map('sanitize_title', $raw_categories))), 0, 12);
    $count = min(4, max(1, absint(cp_frontend_attribute($attributes['count_per_category'], '1'))));
    $columns = cp_frontend_columns($attributes['columns']);

    ob_start();
    ?><section class="enterprise-category-cards"><div class="enterprise-card-grid enterprise-columns-<?php echo esc_attr($columns); ?>"><?php
    foreach ($categories as $category_slug) {
        $category_name = cp_frontend_category_name($category_slug);
        $query = new WP_Query(['post_type' => 'post', 'post_status' => 'publish', 'category_name' => $category_slug, 'posts_per_page' => $count, 'orderby' => 'date', 'order' => 'DESC', 'ignore_sticky_posts' => true]);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                cp_render_frontend_article_card(get_the_ID());
            }
        } else {
            cp_render_frontend_fallback_card($category_name);
        }
        wp_reset_postdata();
    }
    ?></div></section><?php
    return ob_get_clean();
}
