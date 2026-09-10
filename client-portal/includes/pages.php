<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Page Manager: administers real WordPress Pages (Home reference, About
 * Us, Staff, Join the Publication, and future institutional pages) as a
 * feature reached from the Category Manager - see templates/categories.php's
 * "Pages" button - rather than a separate top-level admin menu item.
 * Categories/articles remain entirely untouched by this file.
 */

define('CP_ABOUT_US_PAGE_ID_OPTION', 'cp_about_us_page_id');
define('CP_ABOUT_PAGES_ORDER_OPTION', 'cp_about_pages_order');

/**
 * Administrator-level capability gate, per CLAUDE.md's instruction to
 * prefer a real WordPress capability over a literal role-name string.
 * Page management (institutional/publication-wide content, including the
 * site's About Us navigation parent) is scoped to manage_options rather
 * than the broader manage_categories used for day-to-day category upkeep.
 */
function cp_can_manage_pages()
{
    return current_user_can('manage_options');
}

function cp_require_pages_capability()
{
    if (!cp_can_manage_pages()) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'), '', ['response' => 403]);
    }
}

/**
 * Suppresses Astra's own theme-generated page title/heading for a managed
 * Page. The Page Builder (includes/page-builder.php) already renders its
 * own heading as the first content block, so the theme's default title
 * output above it is always redundant for a plugin-managed Page - never
 * something an administrator should have to remember to disable by hand
 * per page. "site-post-title" is Astra's own documented per-post meta key
 * for its "Disable Title" option (wp-content/themes/astra/inc/metabox/
 * class-astra-meta-boxes.php on this install); setting it directly is the
 * narrowest available integration - it needs no Astra filter override, no
 * Elementor, and never touches theme source. Scoped to a single Page ID at
 * a time, so it only ever affects pages this function is actually called
 * for (every managed-Page create/update path, and the idempotent seed
 * migration for pages that already existed before this fix) - never any
 * unrelated WordPress Page.
 */
function cp_suppress_theme_page_title($page_id)
{
    update_post_meta(absint($page_id), 'site-post-title', 'disabled');
}

/**
 * The site's actual configured homepage Page, or null if the site isn't
 * using a static Page for its front page (e.g. still on "Your latest
 * posts") - see CLAUDE.md 6.2, which explicitly allows for that case
 * rather than forcing a conversion.
 */
function cp_get_home_page()
{
    if ('page' !== get_option('show_on_front')) {
        return null;
    }

    $front_id = absint(get_option('page_on_front'));
    if (!$front_id) {
        return null;
    }

    $page = get_post($front_id);
    return $page instanceof WP_Post && 'page' === $page->post_type ? $page : null;
}

function cp_page_is_home($post)
{
    $post = get_post($post);
    $home = cp_get_home_page();
    return $post instanceof WP_Post && $home instanceof WP_Post && $post->ID === $home->ID;
}

function cp_get_about_us_page_id()
{
    return absint(get_option(CP_ABOUT_US_PAGE_ID_OPTION, 0));
}

function cp_page_is_about_us_parent($post)
{
    $post = get_post($post);
    return $post instanceof WP_Post && $post->ID === cp_get_about_us_page_id();
}

/**
 * Home and the configured About Us parent are protected from deletion by
 * this screen (see CLAUDE.md 6.2/6.3/15) - Home unconditionally, About Us
 * only while it remains the configured navigation parent.
 */
function cp_page_is_protected($post)
{
    return cp_page_is_home($post) || cp_page_is_about_us_parent($post);
}

function cp_page_status_label($post)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return __('Inactive', 'client-portal');
    }

    return 'publish' === $post->post_status ? __('Active', 'client-portal') : __('Inactive', 'client-portal');
}

/**
 * Every Page this plugin manages (CP_PAGE_MANAGED_META), any status, plus
 * the Home page synthesized as the first entry (Home is a normal Page on
 * this site but isn't itself "managed" content in the Page Builder sense,
 * so it doesn't carry the meta - it's still shown per CLAUDE.md 6.2's "the
 * first row must be the actual site's Home Page").
 */
function cp_get_pages_for_manager()
{
    $managed = get_posts([
        'post_type' => 'page',
        'post_status' => ['publish', 'draft'],
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_key' => CP_PAGE_MANAGED_META,
        'meta_value' => '1',
        'no_found_rows' => true,
    ]);

    $home = cp_get_home_page();
    $ordered = [];

    if ($home instanceof WP_Post) {
        $ordered[] = $home;
    }

    foreach ($managed as $page) {
        if ($home instanceof WP_Post && $page->ID === $home->ID) {
            continue;
        }
        $ordered[] = $page;
    }

    return $ordered;
}

/**
 * Ordered, published, About-Us-dropdown-eligible managed Pages (Staff,
 * Join the Publication, future ones) - Home and the About Us parent itself
 * are always excluded per CLAUDE.md 12.2. Order is a saved list of Page
 * IDs, never titles, so a rename never requires re-ordering and a
 * trashed/deleted Page is simply skipped rather than erroring.
 */
function cp_get_about_nav_pages()
{
    return cp_get_about_nav_eligible_pages(['publish']);
}

/**
 * Same eligibility/order resolution as cp_get_about_nav_pages(), but also
 * includes draft (Inactive) managed Pages flagged for the About Us
 * dropdown - never shown to the public (cp_get_about_nav_pages() alone
 * remains the sole source for that), but needed so the About Us
 * Navigation settings screen can display an inactive Page for management
 * context (CLAUDE.md hotfix pass, "About-Us arrangement" requirement 12)
 * without ever implying it is currently live.
 */
function cp_get_about_nav_pages_for_manager()
{
    return cp_get_about_nav_eligible_pages(['publish', 'draft']);
}

function cp_get_about_nav_eligible_pages($post_statuses)
{
    $eligible = get_posts([
        'post_type' => 'page',
        'post_status' => $post_statuses,
        'posts_per_page' => -1,
        'no_found_rows' => true,
        'meta_query' => [
            ['key' => CP_PAGE_MANAGED_META, 'value' => '1'],
            ['key' => CP_PAGE_ABOUT_NAV_META, 'value' => '1'],
        ],
    ]);

    $about_us_id = cp_get_about_us_page_id();
    $home = cp_get_home_page();
    $by_id = [];
    foreach ($eligible as $page) {
        if ($about_us_id === $page->ID) {
            continue;
        }
        if ($home instanceof WP_Post && $home->ID === $page->ID) {
            continue;
        }
        $by_id[$page->ID] = $page;
    }

    $stored_order = get_option(CP_ABOUT_PAGES_ORDER_OPTION, []);
    $stored_order = is_array($stored_order) ? array_map('absint', $stored_order) : [];

    $ordered = [];
    foreach ($stored_order as $page_id) {
        if (isset($by_id[$page_id])) {
            $ordered[] = $by_id[$page_id];
            unset($by_id[$page_id]);
        }
    }
    foreach ($by_id as $page) {
        $ordered[] = $page;
    }

    return $ordered;
}

function cp_append_about_nav_order($page_id)
{
    $page_id = absint($page_id);
    if (!$page_id) {
        return;
    }

    $stored_order = get_option(CP_ABOUT_PAGES_ORDER_OPTION, []);
    $stored_order = is_array($stored_order) ? array_map('absint', $stored_order) : [];

    if (!in_array($page_id, $stored_order, true)) {
        $stored_order[] = $page_id;
        update_option(CP_ABOUT_PAGES_ORDER_OPTION, $stored_order, false);
    }
}

function cp_remove_about_nav_order($page_id)
{
    $page_id = absint($page_id);
    $stored_order = get_option(CP_ABOUT_PAGES_ORDER_OPTION, []);
    if (!is_array($stored_order)) {
        return;
    }

    $filtered = array_values(array_filter($stored_order, static function ($id) use ($page_id) {
        return absint($id) !== $page_id;
    }));

    if ($filtered !== $stored_order) {
        update_option(CP_ABOUT_PAGES_ORDER_OPTION, $filtered, false);
    }
}

function cp_move_about_nav_order($page_id, $direction)
{
    $page_id = absint($page_id);
    $pages = cp_get_about_nav_pages();
    $ids = wp_list_pluck($pages, 'ID');
    $index = array_search($page_id, $ids, true);

    if (false === $index) {
        return;
    }

    $target = $index + ('up' === $direction ? -1 : 1);
    if ($target < 0 || $target >= count($ids)) {
        return;
    }

    $moved = $ids[$index];
    array_splice($ids, $index, 1);
    array_splice($ids, $target, 0, [$moved]);

    update_option(CP_ABOUT_PAGES_ORDER_OPTION, array_map('absint', $ids), false);
}

/**
 * Persist a new About Us dropdown order from the "About Us Navigation"
 * settings modal (templates/page-manager.php / assets/js/page-manager.js).
 * Mirrors cp_save_nav_menu_order()'s (includes/nav-menu.php) validation
 * shape: every submitted ID is re-checked server-side against the real
 * eligibility rule (a real page post, still managed, still flagged for the
 * About Us dropdown) rather than trusted blindly, and any currently
 * eligible Page missing from the submission (e.g. a currently-Inactive
 * Page, which the modal never lets the administrator drag, or a
 * client-side race with a just-flagged Page) is appended after rather than
 * silently dropped, so its stored position survives a save made while it
 * wasn't part of the draggable list.
 */
function cp_save_about_nav_order($page_ids)
{
    $page_ids = is_array($page_ids) ? array_map('absint', $page_ids) : [];
    $page_ids = array_values(array_unique(array_filter($page_ids)));

    $eligible_ids = wp_list_pluck(cp_get_about_nav_pages_for_manager(), 'ID');

    $valid_ids = [];
    foreach ($page_ids as $page_id) {
        if (in_array($page_id, $eligible_ids, true)) {
            $valid_ids[] = $page_id;
        }
    }

    foreach ($eligible_ids as $page_id) {
        if (!in_array($page_id, $valid_ids, true)) {
            $valid_ids[] = $page_id;
        }
    }

    update_option(CP_ABOUT_PAGES_ORDER_OPTION, $valid_ids, false);

    return $valid_ids;
}

/**
 * AJAX: save the About Us Navigation settings modal's pending page order.
 * Shape mirrors cp_ajax_save_nav_menu() (includes/nav-menu.php).
 */
function cp_ajax_save_about_nav_order()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Please sign in to continue.', 'client-portal')], 401);
    }

    if (!check_ajax_referer('cp_save_about_nav_order', 'nonce', false)) {
        wp_send_json_error(['message' => __('Your session expired. Please reload the page and try again.', 'client-portal')], 403);
    }

    if (!cp_can_manage_pages()) {
        wp_send_json_error(['message' => __('You do not have permission to edit the About Us navigation.', 'client-portal')], 403);
    }

    $raw_order = isset($_POST['page_order']) ? wp_unslash($_POST['page_order']) : [];
    $page_ids = [];
    if (is_array($raw_order)) {
        foreach ($raw_order as $raw_id) {
            if (is_scalar($raw_id)) {
                $page_ids[] = absint($raw_id);
            }
        }
    }

    $saved_order = cp_save_about_nav_order($page_ids);

    wp_send_json_success([
        'message' => __('About Us navigation updated successfully.', 'client-portal'),
        'order' => $saved_order,
    ]);
}
add_action('wp_ajax_cp_save_about_nav_order', 'cp_ajax_save_about_nav_order');

/**
 * Clean up navigation state when a managed Page is trashed/deleted, so it
 * never leaves a dangling About-nav entry or an About-Us-parent option
 * pointing at a gone Page.
 */
function cp_cleanup_page_nav_state($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return;
    }

    cp_remove_about_nav_order($post_id);

    if (cp_get_about_us_page_id() === $post_id) {
        delete_option(CP_ABOUT_US_PAGE_ID_OPTION);
    }
}
add_action('wp_trash_post', 'cp_cleanup_page_nav_state');
add_action('before_delete_post', 'cp_cleanup_page_nav_state');

/* -------------------------------------------------------------------- */
/* Slug / permalink helpers                                              */
/* -------------------------------------------------------------------- */

function cp_generate_page_permalink_preview($slug)
{
    $slug = sanitize_title($slug);
    return '' !== $slug ? home_url('/' . $slug . '/') : home_url('/');
}

/* -------------------------------------------------------------------- */
/* Save / status / trash handlers                                        */
/* -------------------------------------------------------------------- */

/**
 * Create or update a managed Page. Mirrors cp_save_article_builder_post()'s
 * shape (includes/articles.php): returns the saved WP_Post on success, a
 * WP_Error otherwise, so the caller can redirect with an appropriate
 * notice either way.
 */
function cp_save_page_builder_post($existing_post = null)
{
    check_admin_referer('cp_save_page', 'cp_page_nonce');
    cp_require_pages_capability();

    $title = sanitize_text_field(cp_post_value('title'));
    if ('' === $title) {
        return new WP_Error('cp_page_missing_title', __('Enter a page title.', 'client-portal'));
    }

    $requested_slug = sanitize_title(cp_post_value('slug'));
    $status = 'inactive' === sanitize_key(cp_post_value('status', 'active')) ? 'draft' : 'publish';
    $in_about_nav = '1' === cp_post_value('in_about_nav', '');

    $blocks = cp_decode_page_blocks(cp_post_value('blocks_json'), false);
    if (is_wp_error($blocks)) {
        return $blocks;
    }

    if ($existing_post instanceof WP_Post) {
        if (cp_page_is_home($existing_post)) {
            // The Home page keeps its existing architecture (CLAUDE.md 6.2) -
            // this screen may still update its title, but never its
            // status/slug/managed-page rendering takeover.
            $result = wp_update_post(['ID' => $existing_post->ID, 'post_title' => $title], true);
            return is_wp_error($result) ? $result : get_post($existing_post->ID);
        }

        $post_data = ['ID' => $existing_post->ID, 'post_title' => $title, 'post_status' => $status];
        if ('' !== $requested_slug) {
            $post_data['post_name'] = $requested_slug;
        }
        $result = wp_update_post($post_data, true);
        if (is_wp_error($result)) {
            return $result;
        }
        $post_id = $existing_post->ID;
    } else {
        $post_data = [
            'post_type' => 'page',
            'post_title' => $title,
            'post_status' => $status,
            'post_content' => '',
        ];
        if ('' !== $requested_slug) {
            $post_data['post_name'] = $requested_slug;
        }
        $post_id = wp_insert_post($post_data, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
    }

    update_post_meta($post_id, CP_PAGE_MANAGED_META, '1');
    cp_suppress_theme_page_title($post_id);
    cp_save_page_blocks($post_id, $blocks);

    if ($in_about_nav) {
        update_post_meta($post_id, CP_PAGE_ABOUT_NAV_META, '1');
        cp_append_about_nav_order($post_id);
    } else {
        delete_post_meta($post_id, CP_PAGE_ABOUT_NAV_META);
        cp_remove_about_nav_order($post_id);
    }

    return get_post($post_id);
}

function cp_handle_page_trash_request()
{
    $page_id = absint(cp_get_value('id'));
    if (!$page_id || 'delete' !== sanitize_key(cp_get_value('action'))) {
        return;
    }

    cp_require_pages_capability();
    check_admin_referer('cp_delete_page_' . $page_id);

    $post = get_post($page_id);
    if (!$post instanceof WP_Post || 'page' !== $post->post_type) {
        cp_redirect('cp-pages');
    }

    if (cp_page_is_protected($post)) {
        cp_set_temporary_notice('danger', __('This page is protected and cannot be deleted from the Publisher Portal.', 'client-portal'));
        cp_redirect('cp-pages');
    }

    wp_trash_post($page_id);
    cp_redirect('cp-pages', ['cp_notice' => 'page_trashed']);
}

function cp_process_page_admin_actions()
{
    $page = cp_current_page();

    if ('cp-pages' === $page) {
        cp_handle_page_trash_request();
        return;
    }

    if (!in_array($page, ['cp-page-create', 'cp-page-edit'], true) || 'save' !== sanitize_key(cp_post_value('cp_page_builder_action'))) {
        return;
    }

    $post = null;
    if ('cp-page-edit' === $page) {
        $page_id = absint(cp_get_value('id'));
        $post = $page_id ? get_post($page_id) : null;
        if (!$post || 'page' !== $post->post_type) {
            cp_set_temporary_notice('danger', __('The page could not be found.', 'client-portal'));
            cp_redirect('cp-pages');
        }
    }

    $result = cp_save_page_builder_post($post);
    if (is_wp_error($result)) {
        cp_set_temporary_notice('danger', $result->get_error_message());
        cp_redirect($page, $post ? ['id' => $post->ID] : []);
    }

    cp_redirect('cp-pages', ['cp_notice' => $post ? 'page_updated' : 'page_created']);
}

/* -------------------------------------------------------------------- */
/* Admin page callbacks                                                  */
/* -------------------------------------------------------------------- */

function cp_pages_page()
{
    cp_require_pages_capability();

    if ('1' === cp_get_value('move_about_nav')) {
        $move_id = absint(cp_get_value('id'));
        $direction = 'up' === cp_get_value('direction') ? 'up' : 'down';
        check_admin_referer('cp_move_about_nav_' . $move_id);
        cp_move_about_nav_order($move_id, $direction);
        cp_redirect('cp-pages', ['cp_notice' => 'page_order_updated']);
    }

    // Template name deliberately NOT "pages": WordPress core's
    // sanitize_file_name() (called by cp_render_template()) treats a bare
    // "pages" as colliding with the registered "application/vnd.apple.pages"
    // MIME extension and silently rewrites it to "unnamed-file.pages",
    // which cp_render_template() then can't find - this was the actual
    // cause of the blank Pages admin screen, confirmed live via
    // sanitize_file_name('pages') === 'unnamed-file.pages' on this exact
    // WordPress install. "page-manager" (hyphenated) does not collide.
    cp_render_page('page-manager', [
        'page_title' => __('Pages', 'client-portal'),
        'pages' => cp_get_pages_for_manager(),
        'notice' => cp_request_notice(),
        'about_nav_pages' => cp_get_about_nav_pages_for_manager(),
        'about_us_active' => cp_about_us_is_publicly_visible(),
    ]);
}

function cp_page_create_page()
{
    cp_render_page_builder_page('create');
}

function cp_page_edit_page()
{
    cp_render_page_builder_page('edit');
}

function cp_render_page_builder_page($mode)
{
    cp_require_pages_capability();
    $is_edit = 'edit' === $mode;
    $page_id = absint(cp_get_value('id'));
    $post = $is_edit && $page_id ? get_post($page_id) : null;

    if ($is_edit && (!$post || 'page' !== $post->post_type)) {
        cp_redirect('cp-pages');
    }

    $blocks = cp_get_page_blocks_for_editor($post);
    $page_data = [
        'title' => $post ? $post->post_title : '',
        'slug' => $post ? $post->post_name : '',
        'status' => $post && 'publish' === $post->post_status ? 'active' : ($post ? 'inactive' : 'active'),
        'in_about_nav' => $post ? ('1' === get_post_meta($post->ID, CP_PAGE_ABOUT_NAV_META, true)) : false,
        'is_home' => $post ? cp_page_is_home($post) : false,
        'is_about_us_parent' => $post ? cp_page_is_about_us_parent($post) : false,
        'permalink' => $post ? get_permalink($post) : '',
    ];

    cp_render_page($is_edit ? 'page-edit' : 'page-create', [
        'page_title' => $is_edit ? __('Edit Page', 'client-portal') : __('Add Page', 'client-portal'),
        'builder_mode' => $mode,
        'page_post' => $post,
        'page_data' => $page_data,
        'blocks' => $blocks,
        'notice' => cp_request_notice(),
    ]);
}
