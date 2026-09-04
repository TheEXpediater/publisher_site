<?php

if (!defined('ABSPATH')) {
    exit;
}

define('CP_NAV_MAX_VISIBLE_CATEGORIES', 8);

/**
 * Categories for the plugin-owned primary navigation.
 * Source and order match the existing admin Categories screen
 * (native WordPress "category" taxonomy, orderby=name/order=ASC in
 * cp_categories_page()), so a category created in the admin appears here
 * automatically with no manual menu editing. Categories marked Inactive in
 * the admin (cp_category_is_active()) are excluded non-destructively - the
 * term, its articles, and its Page are untouched, it just isn't linked.
 */
function cp_get_primary_navigation_categories()
{
    $categories = get_categories([
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => true,
    ]);

    $categories = is_array($categories) ? $categories : [];

    return array_values(array_filter($categories, 'cp_category_is_active'));
}

function cp_get_about_us_url()
{
    $about_page = get_page_by_path('about-us');
    if ($about_page instanceof WP_Post) {
        $permalink = get_permalink($about_page);
        if ($permalink) {
            return $permalink;
        }
    }

    return home_url('/about-us/');
}

/**
 * Escape a category name for the nav label text.
 * Astra pipes the markup returned by cp_replace_primary_wp_nav_menu()
 * through do_shortcode() (class-astra-header-menu-component.php), so a
 * category literally named e.g. "[gallery]" must not leave live shortcode
 * brackets in the output alongside the usual esc_html() escaping.
 */
function cp_nav_label($text)
{
    return str_replace(['[', ']'], ['&#91;', '&#93;'], esc_html($text));
}

function cp_render_primary_navigation_markup()
{
    $categories = cp_get_primary_navigation_categories();
    $visible_categories = array_slice($categories, 0, CP_NAV_MAX_VISIBLE_CATEGORIES);
    $overflow_categories = array_slice($categories, CP_NAV_MAX_VISIBLE_CATEGORIES);
    $about_us_url = cp_get_about_us_url();

    ob_start();
    ?>
    <nav id="cp-primary-navigation" class="cp-primary-nav" aria-label="<?php esc_attr_e('Primary', 'client-portal'); ?>">
        <div class="cp-primary-nav-inner">
            <button
                type="button"
                class="cp-primary-nav-toggle"
                aria-expanded="false"
                aria-controls="cp-primary-nav-menu"
            >
                <span class="cp-primary-nav-toggle-bars" aria-hidden="true"></span>
                <span class="cp-primary-nav-toggle-label"><?php esc_html_e('Menu', 'client-portal'); ?></span>
            </button>

            <ul id="cp-primary-nav-menu" class="cp-primary-nav-menu">
                <?php foreach ($visible_categories as $category) : ?>
                    <?php $category_url = cp_get_category_page_url($category); ?>
                    <?php if ('' === $category_url) : continue; endif; ?>
                    <li class="cp-primary-nav-item">
                        <a href="<?php echo esc_url($category_url); ?>"><?php echo cp_nav_label($category->name); ?></a>
                    </li>
                <?php endforeach; ?>

                <?php if (!empty($overflow_categories)) : ?>
                    <li class="cp-primary-nav-item cp-primary-nav-more">
                        <button
                            type="button"
                            class="cp-primary-nav-more-toggle"
                            aria-expanded="false"
                            aria-controls="cp-primary-nav-more-menu"
                        >
                            <?php esc_html_e('More', 'client-portal'); ?>
                        </button>
                        <ul id="cp-primary-nav-more-menu" class="cp-primary-nav-more-menu">
                            <?php foreach ($overflow_categories as $category) : ?>
                                <?php $category_url = cp_get_category_page_url($category); ?>
                                <?php if ('' === $category_url) : continue; endif; ?>
                                <li>
                                    <a href="<?php echo esc_url($category_url); ?>"><?php echo cp_nav_label($category->name); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="cp-primary-nav-item cp-primary-nav-about">
                    <a href="<?php echo esc_url($about_us_url); ?>"><?php esc_html_e('About Us', 'client-portal'); ?></a>
                </li>
            </ul>
        </div>
    </nav>
    <?php
    return trim((string) ob_get_clean());
}

/**
 * Replace the theme's primary navigation output at the WordPress core level.
 * Astra (both its classic header markup and the modern Header/Footer
 * Builder) always renders the site's main menu via wp_nav_menu() with
 * theme_location 'primary' (registered in astra_register_menu_locations()).
 * Short-circuiting wp_nav_menu() here via pre_wp_nav_menu replaces exactly
 * that single call - and only that call - with no theme files touched, so
 * Astra's surrounding header/branding/footer markup is untouched and no
 * second navigation is introduced.
 */
function cp_replace_primary_wp_nav_menu($output, $args)
{
    $theme_location = (is_object($args) && isset($args->theme_location)) ? $args->theme_location : '';

    if ('primary' !== $theme_location) {
        return $output;
    }

    return cp_render_primary_navigation_markup();
}
add_filter('pre_wp_nav_menu', 'cp_replace_primary_wp_nav_menu', 10, 2);

function cp_enqueue_primary_navigation_assets()
{
    if (is_admin()) {
        return;
    }

    wp_enqueue_style('cp-frontend-navigation', cp_url('assets/css/frontend-navigation.css'), [], CP_VERSION);
    wp_enqueue_script('cp-frontend-navigation', cp_url('assets/js/frontend-navigation.js'), [], CP_VERSION, true);
}
add_action('wp_enqueue_scripts', 'cp_enqueue_primary_navigation_assets');
