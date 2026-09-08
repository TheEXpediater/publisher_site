<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Maximum categories shown directly in the header before the rest move
 * into the About Us dropdown. About Us itself is the final top-level item,
 * so this is 7, not 8 - "8 top-level items total" (7 categories + About
 * Us), not "8 categories plus About Us" (9 total). This is the single
 * shared source for the limit; every header renderer (public site, admin
 * preview, menu editor) reads this constant rather than each hardcoding
 * their own number.
 */
define('CP_NAV_MAX_VISIBLE_CATEGORIES', 7);

/**
 * Categories for the plugin-owned primary navigation (header and footer).
 * Source and order match the existing admin Categories screen (native
 * WordPress "category" taxonomy), refined by the saved menu order from
 * includes/nav-menu.php, so a category created in the admin appears here
 * automatically with no manual menu editing. Categories marked Inactive in
 * the admin (cp_category_is_active()) are excluded non-destructively - the
 * term, its articles, and its Page are untouched, it just isn't linked.
 */
function cp_get_primary_navigation_categories()
{
    return cp_get_ordered_navigation_categories('header');
}

function cp_get_footer_navigation_categories()
{
    return cp_get_ordered_navigation_categories('footer');
}

function cp_get_about_us_page()
{
    static $page = false;

    if (false === $page) {
        $found = get_page_by_path('about-us');
        $page = $found instanceof WP_Post ? $found : null;
    }

    return $page;
}

function cp_get_about_us_url()
{
    $about_page = cp_get_about_us_page();
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

/**
 * Which navigation item (if any) represents the page currently being
 * viewed, resolved server-side from WordPress's own query context rather
 * than guessed client-side or inferred from list position:
 *  - About Us is current only on the actual configured About Us page.
 *  - A category is current on its own frontend Page (categories route to a
 *    real Page running [enterprise_category_posts], not the taxonomy
 *    archive - see includes/categories.php), on the native taxonomy
 *    archive itself (defensive - normally redirected before nav renders),
 *    and on a single article via its established "primary category"
 *    (get_the_category()[0], the same concept already used for the
 *    single-article breadcrumb in includes/frontend-shortcodes.php).
 * Returns ['type' => 'about'|'category'|'', 'term_id' => int].
 */
function cp_get_nav_active_context()
{
    static $context = null;

    if (null !== $context) {
        return $context;
    }

    $context = ['type' => '', 'term_id' => 0];

    if (is_admin()) {
        return $context;
    }

    if (is_page()) {
        $queried_id = get_queried_object_id();
        $about_page = cp_get_about_us_page();

        if ($about_page instanceof WP_Post && $queried_id === $about_page->ID) {
            $context = ['type' => 'about', 'term_id' => 0];
            return $context;
        }

        $matching_terms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
            'meta_key' => CP_CATEGORY_PAGE_TERM_META_KEY,
            'meta_value' => $queried_id,
            'number' => 1,
            'fields' => 'ids',
        ]);

        if (is_array($matching_terms) && !empty($matching_terms)) {
            $context = ['type' => 'category', 'term_id' => absint($matching_terms[0])];
            return $context;
        }

        return $context;
    }

    if (is_category()) {
        $queried_object = get_queried_object();
        if ($queried_object instanceof WP_Term) {
            $context = ['type' => 'category', 'term_id' => absint($queried_object->term_id)];
        }
        return $context;
    }

    if (is_singular('post')) {
        $primary_term = function_exists('cp_frontend_primary_category_term')
            ? cp_frontend_primary_category_term(get_queried_object_id())
            : null;
        if ($primary_term instanceof WP_Term) {
            $context = ['type' => 'category', 'term_id' => absint($primary_term->term_id)];
        }
        return $context;
    }

    return $context;
}

function cp_nav_item_is_current($type, $term_id = 0)
{
    $active = cp_get_nav_active_context();
    return $active['type'] === $type && absint($active['term_id']) === absint($term_id);
}

function cp_render_primary_navigation_markup()
{
    $categories = cp_get_primary_navigation_categories();
    $visible_categories = array_slice($categories, 0, CP_NAV_MAX_VISIBLE_CATEGORIES);
    $overflow_categories = array_slice($categories, CP_NAV_MAX_VISIBLE_CATEGORIES);
    $about_us_url = cp_get_about_us_url();
    $about_is_current = cp_nav_item_is_current('about');
    $has_active_overflow_child = false;
    foreach ($overflow_categories as $overflow_category) {
        if (cp_nav_item_is_current('category', $overflow_category->term_id)) {
            $has_active_overflow_child = true;
            break;
        }
    }

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
                    <?php
                    $category_url = cp_get_category_page_url($category);
                    if ('' === $category_url) {
                        continue;
                    }
                    $is_current = cp_nav_item_is_current('category', $category->term_id);
                    ?>
                    <li class="cp-primary-nav-item<?php echo $is_current ? ' is-current' : ''; ?>">
                        <a href="<?php echo esc_url($category_url); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo cp_nav_label($category->name); ?></a>
                    </li>
                <?php endforeach; ?>

                <li class="cp-primary-nav-item cp-primary-nav-about<?php echo $about_is_current ? ' is-current' : ''; ?>">
                    <a
                        class="cp-primary-nav-about-link"
                        href="<?php echo esc_url($about_us_url); ?>"
                        <?php echo $about_is_current ? ' aria-current="page"' : ''; ?>
                    ><?php esc_html_e('About Us', 'client-portal'); ?></a>
                    <?php if (!empty($overflow_categories)) : ?>
                        <button
                            type="button"
                            class="cp-primary-nav-about-toggle<?php echo $has_active_overflow_child ? ' has-active-child' : ''; ?>"
                            aria-expanded="false"
                            aria-controls="cp-primary-nav-about-menu"
                            aria-label="<?php echo $has_active_overflow_child ? esc_attr__('Show more sections (current section inside)', 'client-portal') : esc_attr__('Show more sections', 'client-portal'); ?>"
                        >
                            <span aria-hidden="true"></span>
                        </button>
                        <ul id="cp-primary-nav-about-menu" class="cp-primary-nav-about-menu">
                            <?php foreach ($overflow_categories as $category) : ?>
                                <?php
                                $category_url = cp_get_category_page_url($category);
                                if ('' === $category_url) {
                                    continue;
                                }
                                $is_current = cp_nav_item_is_current('category', $category->term_id);
                                ?>
                                <li class="<?php echo $is_current ? 'is-current' : ''; ?>">
                                    <a href="<?php echo esc_url($category_url); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo cp_nav_label($category->name); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>
    <?php
    return trim((string) ob_get_clean());
}

/**
 * Footer navigation. Same underlying "category" taxonomy as the header,
 * and updates automatically the same way (a category created, renamed,
 * reordered, or deleted in the Category Manager never needs separate
 * footer maintenance) - but its own order and typography (see
 * includes/nav-menu.php's per-surface options) so the Menu Editor's Header
 * and Footer tabs can be reordered/styled independently. Presentation is a
 * flat list of every active category rather than the header's 7-item cap
 * plus overflow dropdown, since a footer conventionally lists everything
 * and has no similar horizontal space constraint - the direct-category
 * limit is a header-navigation rule, not shared by the footer.
 */
function cp_render_footer_navigation_markup()
{
    $categories = cp_get_footer_navigation_categories();
    $about_us_url = cp_get_about_us_url();
    $about_is_current = cp_nav_item_is_current('about');

    ob_start();
    ?>
    <nav id="cp-footer-navigation" class="cp-footer-nav" aria-label="<?php esc_attr_e('Footer', 'client-portal'); ?>">
        <ul class="cp-footer-nav-menu">
            <?php foreach ($categories as $category) : ?>
                <?php
                $category_url = cp_get_category_page_url($category);
                if ('' === $category_url) {
                    continue;
                }
                $is_current = cp_nav_item_is_current('category', $category->term_id);
                ?>
                <li class="cp-footer-nav-item<?php echo $is_current ? ' is-current' : ''; ?>">
                    <a href="<?php echo esc_url($category_url); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo cp_nav_label($category->name); ?></a>
                </li>
            <?php endforeach; ?>
            <li class="cp-footer-nav-item cp-footer-nav-about<?php echo $about_is_current ? ' is-current' : ''; ?>">
                <a href="<?php echo esc_url($about_us_url); ?>"<?php echo $about_is_current ? ' aria-current="page"' : ''; ?>><?php esc_html_e('About Us', 'client-portal'); ?></a>
            </li>
        </ul>
    </nav>
    <?php
    return trim((string) ob_get_clean());
}

/**
 * Replace the theme's primary and footer navigation output at the
 * WordPress core level. Astra (both its classic header markup and the
 * modern Header/Footer Builder) renders the site's main menu via
 * wp_nav_menu() with theme_location 'primary' (astra_register_menu_locations())
 * and its footer "Menu" builder component via theme_location 'footer_menu'
 * (class-astra-footer-menu-component.php) - see includes/nav-menu.php's
 * cp_ensure_footer_menu_location_assigned() for why the footer location
 * needs a menu assigned before Astra will even call wp_nav_menu() for it.
 * Short-circuiting wp_nav_menu() here via pre_wp_nav_menu replaces exactly
 * those two calls - and only those calls - with no theme files touched, so
 * Astra's surrounding header/branding/footer markup is untouched and no
 * second navigation is introduced.
 */
function cp_replace_primary_wp_nav_menu($output, $args)
{
    $theme_location = (is_object($args) && isset($args->theme_location)) ? $args->theme_location : '';

    if ('primary' === $theme_location) {
        return cp_render_primary_navigation_markup();
    }

    if ('footer_menu' === $theme_location) {
        return cp_render_footer_navigation_markup();
    }

    return $output;
}
add_filter('pre_wp_nav_menu', 'cp_replace_primary_wp_nav_menu', 10, 2);

/**
 * Prints the saved header and footer nav typography as CSS custom
 * properties, scoped to their own selector so the Menu Editor's Header and
 * Footer tabs can carry different fonts. The values come only from
 * cp_get_nav_typography_css_vars() - a hardcoded font-family whitelist
 * (cp_nav_font_family_choices()) plus a validated whitelisted pixel size,
 * never arbitrary/user-supplied CSS - so this deliberately does not run
 * them through esc_html(): entity-encoding the quotes that multi-word font
 * names like "Segoe UI" require would corrupt the CSS (style-tag content
 * isn't HTML-entity-decoded by CSS parsers). Angle brackets are stripped
 * as cheap defense-in-depth against the value ever accidentally breaking
 * out of the style tag.
 */
function cp_primary_navigation_typography_style()
{
    $sanitize = static function ($value) {
        return str_replace(['<', '>'], '', (string) $value);
    };

    $declarations_for = static function ($surface) use ($sanitize) {
        $declarations = '';
        foreach (cp_get_nav_typography_css_vars($surface) as $name => $value) {
            $declarations .= $sanitize($name) . ':' . $sanitize($value) . ';';
        }
        return $declarations;
    };

    $css = '.cp-primary-nav{' . $declarations_for('header') . '}.cp-footer-nav{' . $declarations_for('footer') . '}';

    echo '<style id="cp-nav-typography">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('wp_head', 'cp_primary_navigation_typography_style', 20);

function cp_enqueue_primary_navigation_assets()
{
    if (is_admin()) {
        return;
    }

    wp_enqueue_style('cp-frontend-navigation', cp_url('assets/css/frontend-navigation.css'), [], CP_VERSION);
    wp_enqueue_script('cp-frontend-navigation', cp_url('assets/js/frontend-navigation.js'), [], CP_VERSION, true);
}
add_action('wp_enqueue_scripts', 'cp_enqueue_primary_navigation_assets');
