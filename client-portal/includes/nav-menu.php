<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared header/footer navigation model.
 *
 * Categories themselves stay native WordPress "category" terms managed by
 * the existing Category Manager CRUD (includes/categories.php) - this file
 * only adds small, additive pieces of state on top of that taxonomy: a
 * saved display order (term IDs) and a saved typography choice, each kept
 * per surface ('header' or 'footer') so the Menu Editor's Header/Footer
 * tabs can be reordered and styled independently while both still resolve
 * every item through the same live category terms - never a copy of the
 * category data itself, only an ordered list of term IDs.
 *
 * Compatibility/migration: the option names used before per-surface
 * settings existed (CP_NAV_ORDER_OPTION / CP_NAV_TYPOGRAPHY_OPTION) are now
 * the HEADER options, unchanged - so an existing header configuration is
 * never reset by this. The FOOTER falls back to reading those same header
 * options for as long as no footer-specific option has ever been saved
 * (cp_get_nav_order_option_name() / cp_get_nav_typography_option_name()),
 * matching what the footer already visually showed before this file
 * supported separate settings. The moment a footer save happens, its own
 * option is written and the fallback stops applying to it.
 */

define('CP_NAV_ORDER_OPTION', 'cp_nav_menu_order');
define('CP_NAV_TYPOGRAPHY_OPTION', 'cp_nav_menu_typography');
define('CP_NAV_FOOTER_ORDER_OPTION', 'cp_nav_footer_menu_order');
define('CP_NAV_FOOTER_TYPOGRAPHY_OPTION', 'cp_nav_footer_menu_typography');

function cp_nav_surface($surface)
{
    return 'footer' === $surface ? 'footer' : 'header';
}

function cp_get_nav_order_option_name($surface)
{
    return 'footer' === cp_nav_surface($surface) ? CP_NAV_FOOTER_ORDER_OPTION : CP_NAV_ORDER_OPTION;
}

function cp_get_nav_typography_option_name($surface)
{
    return 'footer' === cp_nav_surface($surface) ? CP_NAV_FOOTER_TYPOGRAPHY_OPTION : CP_NAV_TYPOGRAPHY_OPTION;
}

/**
 * Font choices are intentionally the same whitelist already trusted for
 * article content (assets/js/article-builder.js font_formats), so the menu
 * editor never introduces a second, independently-maintained font system.
 * Keys are the only values ever persisted; the CSS value is resolved from
 * this table at render time, never taken from stored/user input directly.
 */
function cp_nav_font_family_choices()
{
    return [
        'default' => ['label' => __('Default', 'client-portal'), 'css' => '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif'],
        'arial' => ['label' => __('Arial', 'client-portal'), 'css' => 'Arial,Helvetica,sans-serif'],
        'arial-black' => ['label' => __('Arial Black', 'client-portal'), 'css' => '"Arial Black",Arial,sans-serif'],
        'georgia' => ['label' => __('Georgia', 'client-portal'), 'css' => 'Georgia,serif'],
        'times' => ['label' => __('Times New Roman', 'client-portal'), 'css' => '"Times New Roman",Times,serif'],
        'verdana' => ['label' => __('Verdana', 'client-portal'), 'css' => 'Verdana,Geneva,sans-serif'],
        'tahoma' => ['label' => __('Tahoma', 'client-portal'), 'css' => 'Tahoma,Geneva,sans-serif'],
        'trebuchet' => ['label' => __('Trebuchet MS', 'client-portal'), 'css' => '"Trebuchet MS",Helvetica,sans-serif'],
        'courier' => ['label' => __('Courier New', 'client-portal'), 'css' => '"Courier New",Courier,monospace'],
    ];
}

/**
 * A conservative subset of the article builder's own font-size whitelist,
 * capped well below its upper range so an administrator cannot pick a size
 * that breaks the compact header/footer bars on mobile.
 */
function cp_nav_font_size_choices()
{
    return [11, 12, 13, 14, 15, 16, 18, 20];
}

function cp_nav_default_typography()
{
    return ['font_family' => 'default', 'font_size' => 13];
}

function cp_get_nav_typography($surface = 'header')
{
    $surface = cp_nav_surface($surface);
    $stored = get_option(cp_get_nav_typography_option_name($surface), false);

    if (false === $stored && 'footer' === $surface) {
        // Migration fallback: no footer-specific typography saved yet -
        // read the header's, matching what the footer already rendered
        // with before per-surface typography existed.
        $stored = get_option(CP_NAV_TYPOGRAPHY_OPTION, []);
    }

    $stored = is_array($stored) ? $stored : [];
    $defaults = cp_nav_default_typography();

    $font_family = isset($stored['font_family']) ? sanitize_key($stored['font_family']) : $defaults['font_family'];
    if (!isset(cp_nav_font_family_choices()[$font_family])) {
        $font_family = $defaults['font_family'];
    }

    $font_size = isset($stored['font_size']) ? absint($stored['font_size']) : $defaults['font_size'];
    if (!in_array($font_size, cp_nav_font_size_choices(), true)) {
        $font_size = $defaults['font_size'];
    }

    return ['font_family' => $font_family, 'font_size' => $font_size];
}

function cp_get_nav_typography_css_vars($surface = 'header')
{
    $typography = cp_get_nav_typography($surface);
    $families = cp_nav_font_family_choices();
    $font_family_css = isset($families[$typography['font_family']]) ? $families[$typography['font_family']]['css'] : $families['default']['css'];

    return [
        '--cp-nav-font-family' => $font_family_css,
        '--cp-nav-font-size' => $typography['font_size'] . 'px',
    ];
}

/**
 * All active (nav-eligible) categories, ordered per the saved menu order
 * for the given surface ('header' or 'footer'). Categories never
 * explicitly ordered yet (new, or saved before this feature existed) are
 * appended deterministically by name so nothing is ever silently hidden
 * from the menu just because it hasn't been dragged into place.
 */
function cp_get_ordered_navigation_categories($surface = 'header')
{
    $surface = cp_nav_surface($surface);
    $categories = get_categories([
        'orderby' => 'name',
        'order' => 'ASC',
        'hide_empty' => true,
    ]);
    $categories = is_array($categories) ? array_values(array_filter($categories, 'cp_category_is_active')) : [];
    // About Us is a WordPress Page, never a category (see
    // cp_is_reserved_about_us_category()) - excluded explicitly here rather
    // than relying only on hide_empty, so this stays correct even if a
    // legacy/reserved-slug term ever ends up with an article attached.
    $categories = array_values(array_filter($categories, static function ($category) {
        return !cp_is_reserved_about_us_category($category);
    }));

    $by_id = [];
    foreach ($categories as $category) {
        $by_id[$category->term_id] = $category;
    }

    $stored_order = get_option(cp_get_nav_order_option_name($surface), false);
    if (false === $stored_order && 'footer' === $surface) {
        // Migration fallback - see file-level docblock.
        $stored_order = get_option(CP_NAV_ORDER_OPTION, []);
    }
    $stored_order = is_array($stored_order) ? array_map('absint', $stored_order) : [];

    $ordered = [];
    foreach ($stored_order as $term_id) {
        if (isset($by_id[$term_id])) {
            $ordered[] = $by_id[$term_id];
            unset($by_id[$term_id]);
        }
    }

    // Anything left in $by_id is either brand-new or was never part of a
    // saved order - append in the existing name-ascending order.
    foreach ($by_id as $category) {
        $ordered[] = $category;
    }

    return $ordered;
}

/**
 * Persist a new category order for the given surface. Every submitted ID
 * is validated against the real "category" taxonomy before being trusted;
 * unknown/foreign IDs are dropped rather than stored. Categories that
 * exist but weren't included in $term_ids (e.g. a client-side race with a
 * just-created category) are appended after the submitted ones so nothing
 * silently falls out of the menu because of a stale edit session.
 */
function cp_save_nav_menu_order($term_ids, $surface = 'header')
{
    $surface = cp_nav_surface($surface);
    $term_ids = is_array($term_ids) ? array_map('absint', $term_ids) : [];
    $term_ids = array_values(array_unique(array_filter($term_ids)));

    $valid_ids = [];
    foreach ($term_ids as $term_id) {
        $term = get_term($term_id, 'category');
        if ($term instanceof WP_Term) {
            $valid_ids[] = $term_id;
        }
    }

    $all_active_ids = wp_list_pluck(cp_get_ordered_navigation_categories($surface), 'term_id');
    foreach ($all_active_ids as $term_id) {
        if (!in_array($term_id, $valid_ids, true)) {
            $valid_ids[] = $term_id;
        }
    }

    update_option(cp_get_nav_order_option_name($surface), $valid_ids, false);

    return $valid_ids;
}

function cp_save_nav_typography($font_family, $font_size, $surface = 'header')
{
    $surface = cp_nav_surface($surface);
    $font_family = sanitize_key($font_family);
    if (!isset(cp_nav_font_family_choices()[$font_family])) {
        $font_family = cp_nav_default_typography()['font_family'];
    }

    $font_size = absint($font_size);
    if (!in_array($font_size, cp_nav_font_size_choices(), true)) {
        $font_size = cp_nav_default_typography()['font_size'];
    }

    update_option(cp_get_nav_typography_option_name($surface), ['font_family' => $font_family, 'font_size' => $font_size], false);

    return ['font_family' => $font_family, 'font_size' => $font_size];
}

/**
 * Drop a deleted category's ID from both the header and footer saved
 * orders so it can never reappear (e.g. if later recreated the term gets a
 * new ID) or leave a dangling reference behind. Hooked to the
 * taxonomy-specific "delete_category" action (see
 * includes/activity-log.php's cp_activity_log_category_deleted() for the
 * same signature already in use here), which - unlike the generic
 * "delete_term" action - does not pass the taxonomy as an argument since
 * it's implied by the hook name itself.
 */
function cp_prune_nav_menu_order_on_delete($term_id, $tt_id = 0, $deleted_term = null)
{
    foreach ([CP_NAV_ORDER_OPTION, CP_NAV_FOOTER_ORDER_OPTION] as $option_name) {
        $stored_order = get_option($option_name, []);
        if (!is_array($stored_order) || empty($stored_order)) {
            continue;
        }

        $filtered = array_values(array_filter($stored_order, static function ($stored_id) use ($term_id) {
            return absint($stored_id) !== absint($term_id);
        }));

        if ($filtered !== $stored_order) {
            update_option($option_name, $filtered, false);
        }
    }
}
add_action('delete_category', 'cp_prune_nav_menu_order_on_delete', 10, 3);

/**
 * AJAX: save the Menu Editor's pending Header + Footer state (order and
 * typography for both) from one Save action. Mirrors the existing
 * homepage-feature AJAX handlers (includes/dashboard.php) for
 * nonce/capability/response shape.
 */
function cp_ajax_save_nav_menu()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Please sign in to continue.', 'client-portal')], 401);
    }

    if (!check_ajax_referer('cp_save_nav_menu', 'nonce', false)) {
        wp_send_json_error(['message' => __('Your session expired. Please reload the page and try again.', 'client-portal')], 403);
    }

    if (!current_user_can('manage_categories')) {
        wp_send_json_error(['message' => __('You do not have permission to edit the navigation menu.', 'client-portal')], 403);
    }

    $parse_ids = static function ($raw) {
        $ids = [];
        if (is_array($raw)) {
            foreach ($raw as $raw_id) {
                if (is_scalar($raw_id)) {
                    $ids[] = absint($raw_id);
                }
            }
        }
        return $ids;
    };

    $header_order = $parse_ids(isset($_POST['header_order']) ? wp_unslash($_POST['header_order']) : []);
    $footer_order = $parse_ids(isset($_POST['footer_order']) ? wp_unslash($_POST['footer_order']) : []);

    $header_font_family = isset($_POST['header_font_family']) && is_scalar($_POST['header_font_family']) ? sanitize_key(wp_unslash((string) $_POST['header_font_family'])) : '';
    $header_font_size = isset($_POST['header_font_size']) ? absint(wp_unslash((string) $_POST['header_font_size'])) : 0;
    $footer_font_family = isset($_POST['footer_font_family']) && is_scalar($_POST['footer_font_family']) ? sanitize_key(wp_unslash((string) $_POST['footer_font_family'])) : '';
    $footer_font_size = isset($_POST['footer_font_size']) ? absint(wp_unslash((string) $_POST['footer_font_size'])) : 0;

    $saved_header_order = cp_save_nav_menu_order($header_order, 'header');
    $saved_footer_order = cp_save_nav_menu_order($footer_order, 'footer');
    $saved_header_typography = cp_save_nav_typography($header_font_family, $header_font_size, 'header');
    $saved_footer_typography = cp_save_nav_typography($footer_font_family, $footer_font_size, 'footer');

    wp_send_json_success([
        'message' => __('Navigation menu saved.', 'client-portal'),
        'header' => ['order' => $saved_header_order, 'typography' => $saved_header_typography],
        'footer' => ['order' => $saved_footer_order, 'typography' => $saved_footer_typography],
    ]);
}
add_action('wp_ajax_cp_save_nav_menu', 'cp_ajax_save_nav_menu');

/**
 * Astra's footer "Menu" builder component only calls wp_nav_menu() - and
 * therefore only becomes interceptable by cp_replace_primary_wp_nav_menu()
 * - when has_nav_menu('footer_menu') is true, i.e. when some WP nav menu is
 * assigned to that theme location. Rather than requiring a manual,
 * easy-to-undo Appearance > Menus step, make sure a (visually irrelevant -
 * its items are never actually rendered, cp_replace_primary_wp_nav_menu()
 * replaces the entire output) placeholder menu is always assigned there, so
 * the footer category menu is wired up purely through code.
 */
function cp_ensure_footer_menu_location_assigned()
{
    if (!current_theme_supports('menus')) {
        return;
    }

    $registered_locations = get_registered_nav_menus();
    if (!isset($registered_locations['footer_menu'])) {
        return;
    }

    $locations = get_nav_menu_locations();
    if (!empty($locations['footer_menu']) && get_term($locations['footer_menu'], 'nav_menu') instanceof WP_Term) {
        return;
    }

    $menu_name = __('Publisher Portal Footer (auto-managed)', 'client-portal');
    $existing_menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $existing_menu instanceof WP_Term ? $existing_menu->term_id : wp_create_nav_menu($menu_name);

    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    $locations['footer_menu'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('after_setup_theme', 'cp_ensure_footer_menu_location_assigned', 20);

/**
 * Undo cp_ensure_footer_menu_location_assigned()'s theme_mod change on
 * plugin deactivation, so a deactivated plugin doesn't leave the site
 * pointed at an empty "footer_menu" location (which - with this plugin's
 * own pre_wp_nav_menu override gone too - would render a visibly empty
 * footer nav wrapper instead of reverting to whatever the theme did before
 * this plugin was ever active). Only touches the location if it's still
 * pointing at the specific auto-managed placeholder menu this plugin
 * created; a site owner who has since reassigned that location to a real
 * menu of their own is left untouched. The empty placeholder menu term
 * itself is left in place rather than deleted - it's harmless (zero items,
 * unassigned), and reactivating the plugin later reuses it by name instead
 * of creating a second one.
 */
function cp_release_footer_menu_location_on_deactivate()
{
    $menu_name = __('Publisher Portal Footer (auto-managed)', 'client-portal');
    $placeholder_menu = wp_get_nav_menu_object($menu_name);
    if (!$placeholder_menu instanceof WP_Term) {
        return;
    }

    $locations = get_nav_menu_locations();
    if (empty($locations['footer_menu']) || absint($locations['footer_menu']) !== absint($placeholder_menu->term_id)) {
        return;
    }

    unset($locations['footer_menu']);
    set_theme_mod('nav_menu_locations', $locations);
}

/**
 * Same placeholder-menu-assignment need as cp_ensure_footer_menu_location_assigned()
 * above, for Astra's separate small-screen header row (theme_location
 * "mobile_menu" - class-astra-mobile-menu-component.php). Without a real
 * WP nav menu assigned there, Astra's has_nav_menu('mobile_menu') check
 * never calls wp_nav_menu() at all, so cp_replace_primary_wp_nav_menu()
 * never gets a chance to intercept it - Astra instead silently falls back
 * to a raw wp_page_menu() listing of every published Page (which is what
 * was actually rendering to real phones: an unstyled, flat list with no
 * About Us submenu, entirely unrelated to this plugin's own carefully
 * styled/accessible mobile treatment of cp_render_primary_navigation_markup(),
 * which was reachable in the DOM but never used for this slot). This
 * placeholder's items are likewise never rendered - the whole output is
 * replaced - so its own item list permanently stays empty.
 */
function cp_ensure_mobile_menu_location_assigned()
{
    if (!current_theme_supports('menus')) {
        return;
    }

    $registered_locations = get_registered_nav_menus();
    if (!isset($registered_locations['mobile_menu'])) {
        return;
    }

    $locations = get_nav_menu_locations();
    if (!empty($locations['mobile_menu']) && get_term($locations['mobile_menu'], 'nav_menu') instanceof WP_Term) {
        return;
    }

    $menu_name = __('Publisher Portal Mobile (auto-managed)', 'client-portal');
    $existing_menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $existing_menu instanceof WP_Term ? $existing_menu->term_id : wp_create_nav_menu($menu_name);

    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    $locations['mobile_menu'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}
add_action('after_setup_theme', 'cp_ensure_mobile_menu_location_assigned', 20);

/**
 * Mirrors cp_release_footer_menu_location_on_deactivate() for the
 * "mobile_menu" location's own auto-managed placeholder.
 */
function cp_release_mobile_menu_location_on_deactivate()
{
    $menu_name = __('Publisher Portal Mobile (auto-managed)', 'client-portal');
    $placeholder_menu = wp_get_nav_menu_object($menu_name);
    if (!$placeholder_menu instanceof WP_Term) {
        return;
    }

    $locations = get_nav_menu_locations();
    if (empty($locations['mobile_menu']) || absint($locations['mobile_menu']) !== absint($placeholder_menu->term_id)) {
        return;
    }

    unset($locations['mobile_menu']);
    set_theme_mod('nav_menu_locations', $locations);
}
