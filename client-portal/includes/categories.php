<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_current_category_page()
{
    return max(1, absint(cp_get_value('category_page')));
}

function cp_category_page_args($category_page = 1)
{
    $category_page = max(1, absint($category_page));
    return $category_page > 1 ? ['category_page' => $category_page] : [];
}

/**
 * Category active/inactive state.
 *
 * Native WordPress categories have no such concept, so this is stored as
 * term meta. Absence of the meta (every pre-existing category, before this
 * feature shipped) is treated as active, so this change cannot silently
 * hide anything already on the live site.
 */
function cp_category_active_meta_key()
{
    return '_cp_category_active';
}

function cp_category_is_active($category)
{
    $category = $category instanceof WP_Term ? $category : get_term($category, 'category');
    if (!$category instanceof WP_Term) {
        return true;
    }

    $meta = get_term_meta($category->term_id, cp_category_active_meta_key(), true);

    return '' === $meta || '1' === $meta;
}

function cp_set_category_active($term_id, $active)
{
    update_term_meta(absint($term_id), cp_category_active_meta_key(), $active ? '1' : '0');
}

/**
 * About Us is now exclusively a WordPress Page (see includes/pages.php) -
 * "about-us" is its own reserved slug identity, never a category. A
 * pre-Pages-feature install may still carry a legacy "About Us" category
 * term left over from before that migration; this identifies it by its
 * exact reserved slug (never by display name alone, so an unrelated
 * category is never mistaken for it) so it can be kept out of the Category
 * Manager/navigation and so the identity can never be recreated.
 */
function cp_reserved_about_us_category_slug()
{
    return 'about-us';
}

function cp_is_reserved_about_us_category($category)
{
    $category = $category instanceof WP_Term ? $category : get_term($category, 'category');
    if (!$category instanceof WP_Term) {
        return false;
    }

    return cp_reserved_about_us_category_slug() === $category->slug;
}

function cp_get_reserved_about_us_category_term_id()
{
    $term = get_term_by('slug', cp_reserved_about_us_category_slug(), 'category');

    return $term instanceof WP_Term ? absint($term->term_id) : 0;
}

/**
 * True when the submitted name/slug for a new or renamed category would
 * collide with the reserved About Us Page identity - checked against both
 * the explicit slug field and the slug WordPress would derive from the
 * name, so a submission that leaves the slug blank (letting WordPress
 * auto-generate it from the name) is caught the same way.
 */
function cp_category_identity_conflicts_with_about_us($name, $slug)
{
    $reserved = cp_reserved_about_us_category_slug();

    return sanitize_title($slug) === $reserved || sanitize_title($name) === $reserved;
}

/**
 * Resolve what a save would actually change, so the caller can skip the
 * mutation entirely (and show "No changes detected." instead of a normal
 * success notice) when the submitted values match the stored category.
 */
function cp_category_save_has_changes($category_id, $name, $slug, $description, $active)
{
    if (!$category_id) {
        return true;
    }

    $category = get_term($category_id, 'category');
    if (!$category instanceof WP_Term) {
        return true;
    }

    if ($name !== $category->name) {
        return true;
    }

    if ('' !== $slug && $slug !== $category->slug) {
        return true;
    }

    if ($description !== (string) $category->description) {
        return true;
    }

    if ($active !== cp_category_is_active($category)) {
        return true;
    }

    return false;
}

function cp_handle_category_save()
{
    if (!isset($_POST['cp_category_action'])) {
        return;
    }

    cp_require_capability('manage_categories');
    check_admin_referer('cp_save_category', 'cp_category_nonce');

    $category_page = max(1, absint(cp_post_value('category_page')));
    $category_id = absint(cp_post_value('category_id'));

    /*
     * The submitted "mode" is the canonical, form-level statement of intent
     * (rendered server-side into a hidden field, mirrored by JS - see
     * templates/categories.php / assets/js/categories.js) - it is not
     * inferred solely from category_id, and category_id is never trusted
     * blindly either: an "edit" submission must resolve to a real,
     * currently-existing category term, or the save is rejected outright
     * rather than silently falling through to creating a new category.
     */
    $mode = sanitize_key(cp_post_value('mode', $category_id ? 'edit' : 'create'));
    if (!in_array($mode, ['create', 'edit'], true)) {
        $mode = $category_id ? 'edit' : 'create';
    }

    $existing_category = null;
    if ($category_id) {
        $term = get_term($category_id, 'category');
        $existing_category = $term instanceof WP_Term ? $term : null;
    }

    if ('edit' === $mode && !$existing_category) {
        return [
            'type' => 'danger',
            'message' => __('Unable to update category because the category could not be identified. Please reload the page and try again.', 'client-portal'),
            'category_page' => $category_page,
        ];
    }

    $name = sanitize_text_field(cp_post_value('name'));
    $slug = sanitize_title(cp_post_value('slug'));
    $description = sanitize_textarea_field(cp_post_value('description'));
    // Absent means the checkbox was unchecked (unchecked checkboxes are not
    // submitted at all), so treat a missing field as inactive, not as
    // "keep default" - the toggle must work in both directions on create.
    $active = '1' === cp_post_value('active', '');
    $previous_active = $existing_category ? cp_category_is_active($existing_category) : null;

    if (cp_category_identity_conflicts_with_about_us($name, $slug)) {
        return [
            'type' => 'danger',
            'message' => __('About Us is managed as a Page and cannot be created as an article category.', 'client-portal'),
            'category_page' => $category_page,
        ];
    }

    if ('edit' === $mode && !cp_category_save_has_changes($category_id, $name, $slug, $description, $active)) {
        return [
            'type' => 'info',
            'message' => __('No changes detected.', 'client-portal'),
            'category_page' => $category_page,
            'no_changes' => true,
        ];
    }

    $args = [
        'slug' => $slug,
        'description' => $description,
    ];

    $result = 'edit' === $mode
        ? wp_update_term($category_id, 'category', array_merge(['name' => $name], $args))
        : wp_insert_term($name, 'category', $args);

    if (is_wp_error($result)) {
        return [
            'type' => 'danger',
            'message' => $result->get_error_message(),
            'category_page' => $category_page,
        ];
    }

    $saved_term_id = 'edit' === $mode ? $category_id : absint($result['term_id']);
    cp_set_category_active($saved_term_id, $active);

    /*
     * A renamed category's mapped frontend Page (see cp_get_category_page()
     * below) keeps its own separate post_title, set once when the Page was
     * first provisioned. The frontend heading and nav labels already
     * resolve the term's current name dynamically (get_category_by_slug()
     * et al.), so they were never stale - but WordPress's own Page-title-
     * driven output (the document <title>, and the theme's page header/
     * breadcrumb, which read the Page object directly rather than the
     * term) is not something this plugin controls or bypasses, and stayed
     * on the old name unless this is kept in sync. This is the same
     * scenario a category that started life as WordPress's default
     * "Uncategorized" category (retaining its term ID and its Page) hits
     * the moment it's renamed - the term ID and article associations are
     * untouched by any of this, only the Page's title field.
     */
    if ('edit' === $mode && $existing_category instanceof WP_Term && $existing_category->name !== $name) {
        cp_sync_category_page_title($saved_term_id, $name);
    }

    // Verify the status actually persisted before claiming success.
    if (cp_category_is_active($saved_term_id) !== $active) {
        return [
            'type' => 'danger',
            'message' => __('The category was saved, but its status could not be updated. Please try again.', 'client-portal'),
            'category_page' => $category_page,
        ];
    }

    if ('create' === $mode) {
        $notice_code = 'category_created';
    } elseif (null !== $previous_active && $previous_active !== $active) {
        $notice_code = $active ? 'category_updated_active' : 'category_updated_inactive';
    } else {
        $notice_code = 'category_updated';
    }

    cp_redirect(
        'cp-categories',
        array_merge(
            cp_category_page_args($category_page),
            ['cp_notice' => $notice_code]
        )
    );
}

function cp_process_category_admin_actions()
{
    if ('cp-categories' !== cp_current_page()) {
        return;
    }

    if ('save' === sanitize_key(cp_post_value('cp_category_action'))) {
        $notice = cp_handle_category_save();
        if (is_array($notice) && !empty($notice['message'])) {
            cp_set_temporary_notice(isset($notice['type']) ? $notice['type'] : 'danger', $notice['message']);
            cp_redirect('cp-categories', cp_category_page_args(isset($notice['category_page']) ? $notice['category_page'] : 1));
        }
    }

    if ('delete' === sanitize_key(cp_get_value('action'))) {
        cp_handle_category_request();
    }
}

function cp_handle_category_request()
{
    $action = sanitize_key(cp_get_value('action'));
    $category_id = absint(cp_get_value('id'));
    $category_page = cp_current_category_page();

    if (!$category_id || !in_array($action, ['edit', 'delete'], true)) {
        return null;
    }

    cp_require_capability('manage_categories');
    check_admin_referer('cp_' . $action . '_category_' . $category_id);

    if ('edit' === $action) {
        $term = get_term($category_id, 'category');
        if (is_wp_error($term) || !$term instanceof WP_Term || cp_is_reserved_about_us_category($term)) {
            return null;
        }

        return $term;
    }

    $result = wp_delete_term($category_id, 'category');
    if (is_wp_error($result) || !$result) {
        $message = is_wp_error($result) ? $result->get_error_message() : __('The category could not be deleted.', 'client-portal');
        cp_set_temporary_notice('danger', $message);
        cp_redirect('cp-categories', cp_category_page_args($category_page));
    }

    cp_redirect(
        'cp-categories',
        array_merge(cp_category_page_args($category_page), ['cp_notice' => 'category_deleted'])
    );
}

function cp_categories_page()
{
    cp_require_capability('manage_categories');
    $editing_category = cp_handle_category_request();
    $category_page = cp_current_category_page();
    $category_per_page = 10;
    // Excludes the reserved About Us identity (see
    // cp_get_reserved_about_us_category_term_id()) so a leftover legacy
    // category term from before the Pages feature - or one somehow
    // recreated - never appears in the Category Manager. About Us is a
    // WordPress Page now, never a category.
    $reserved_category_id = cp_get_reserved_about_us_category_term_id();
    $exclude_category_ids = $reserved_category_id ? [$reserved_category_id] : [];

    $category_total = wp_count_terms([
        'taxonomy' => 'category',
        'hide_empty' => false,
        'exclude' => $exclude_category_ids,
    ]);

    if (is_wp_error($category_total)) {
        $category_total = 0;
    }

    $category_total = absint($category_total);
    $category_max_pages = max(1, (int) ceil($category_total / $category_per_page));

    if ($category_page > $category_max_pages) {
        cp_redirect('cp-categories', cp_category_page_args($category_max_pages));
    }

    $categories = get_categories([
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
        'number' => $category_per_page,
        'offset' => ($category_page - 1) * $category_per_page,
        'exclude' => $exclude_category_ids,
    ]);

    $category_count = is_array($categories) ? count($categories) : 0;
    $category_start = $category_total > 0 ? (($category_page - 1) * $category_per_page) + 1 : 0;
    $category_end = $category_total > 0 ? min($category_start + $category_count - 1, $category_total) : 0;

    $nav_category_list = static function ($surface) {
        return array_map(
            static function ($category) {
                return [
                    'id' => absint($category->term_id),
                    'name' => (string) $category->name,
                    'url' => (string) cp_get_category_page_url($category),
                ];
            },
            cp_get_ordered_navigation_categories($surface)
        );
    };

    cp_render_page('categories', [
        'page_title' => __('Categories', 'client-portal'),
        'categories' => is_array($categories) ? $categories : [],
        'editing_category' => $editing_category,
        'category_page' => $category_page,
        'category_per_page' => $category_per_page,
        'category_total' => $category_total,
        'category_start' => $category_start,
        'category_end' => $category_end,
        'category_max_pages' => $category_max_pages,
        'notice' => cp_request_notice(),
        'nav_header_categories' => $nav_category_list('header'),
        'nav_footer_categories' => $nav_category_list('footer'),
        'nav_about_us_url' => cp_get_about_us_url(),
        'nav_header_typography' => cp_get_nav_typography('header'),
        'nav_footer_typography' => cp_get_nav_typography('footer'),
        'nav_font_families' => cp_nav_font_family_choices(),
        'nav_font_sizes' => cp_nav_font_size_choices(),
        'nav_max_visible' => CP_NAV_MAX_VISIBLE_CATEGORIES,
    ]);
}

/**
 * Category-to-Page routing.
 *
 * The publication's frontend category destination is a real WordPress Page
 * running [enterprise_category_posts category="{slug}"] (e.g. /news/), not
 * the native "category" taxonomy archive (e.g. /category/news/, which
 * Astra/WordPress render with the theme's generic default template). This
 * resolves a category term to that Page - reusing an existing one whenever
 * possible - and provisions one only when genuinely missing.
 *
 * The association is stored as term meta keyed by term_id (not by slug), so
 * renaming a category's slug later does not lose the link to its Page.
 */

define('CP_CATEGORY_PAGE_TERM_META_KEY', '_cp_category_page_id');

function cp_page_targets_category_slug($page, $slug)
{
    $page = get_post($page);
    $slug = sanitize_title($slug);
    if (!$page instanceof WP_Post || '' === $slug || !has_shortcode((string) $page->post_content, 'enterprise_category_posts')) {
        return false;
    }

    if (!preg_match_all('/' . get_shortcode_regex(['enterprise_category_posts']) . '/', (string) $page->post_content, $matches)) {
        return false;
    }

    foreach ($matches[3] as $shortcode_attributes) {
        $atts = shortcode_parse_atts($shortcode_attributes);
        if (is_array($atts) && isset($atts['category']) && sanitize_title($atts['category']) === $slug) {
            return true;
        }
    }

    return false;
}

/**
 * Keep a category's mapped Page title in sync with the term's current name
 * after a rename. Only touches post_title - never post_name/slug (the
 * Page's URL), so an existing renamed category's frontend URL never
 * changes as a side effect of a name edit. A no-op (not an error) if the
 * category has no mapped Page yet; one will simply be provisioned with the
 * correct title whenever it's first needed.
 */
function cp_sync_category_page_title($category_id, $new_name)
{
    $page = cp_get_category_page($category_id);
    if (!$page instanceof WP_Post) {
        return;
    }

    if ($page->post_title === $new_name) {
        return;
    }

    wp_update_post([
        'ID' => $page->ID,
        'post_title' => $new_name,
    ]);
}

function cp_get_category_page($category)
{
    $category = $category instanceof WP_Term ? $category : get_term($category, 'category');
    if (!$category instanceof WP_Term) {
        return null;
    }

    $stored_page_id = absint(get_term_meta($category->term_id, CP_CATEGORY_PAGE_TERM_META_KEY, true));
    if ($stored_page_id) {
        $page = get_post($stored_page_id);
        if ($page instanceof WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status) {
            return $page;
        }

        // The stored association is stale (page deleted/unpublished); re-resolve below.
        delete_term_meta($category->term_id, CP_CATEGORY_PAGE_TERM_META_KEY);
    }

    $page_by_slug = get_page_by_path($category->slug);
    if ($page_by_slug instanceof WP_Post && 'publish' === $page_by_slug->post_status) {
        update_term_meta($category->term_id, CP_CATEGORY_PAGE_TERM_META_KEY, $page_by_slug->ID);
        return $page_by_slug;
    }

    $candidate_ids = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => 50,
        's' => 'enterprise_category_posts',
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    foreach ($candidate_ids as $candidate_id) {
        if (cp_page_targets_category_slug($candidate_id, $category->slug)) {
            update_term_meta($category->term_id, CP_CATEGORY_PAGE_TERM_META_KEY, $candidate_id);
            return get_post($candidate_id);
        }
    }

    return null;
}

function cp_provision_category_page($category)
{
    $category = $category instanceof WP_Term ? $category : get_term($category, 'category');
    if (!$category instanceof WP_Term) {
        return null;
    }

    $existing = cp_get_category_page($category);
    if ($existing instanceof WP_Post) {
        return $existing;
    }

    $page_id = wp_insert_post(
        [
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => $category->name,
            'post_name' => $category->slug,
            'post_content' => '[enterprise_category_posts category="' . $category->slug . '"]',
        ],
        true
    );

    if (is_wp_error($page_id) || !$page_id) {
        cp_debug_log('Failed to provision category page', ['term_id' => $category->term_id, 'slug' => $category->slug]);
        return null;
    }

    update_term_meta($category->term_id, CP_CATEGORY_PAGE_TERM_META_KEY, $page_id);

    return get_post($page_id);
}

/**
 * Resolve a category's public frontend URL, provisioning its Page on first
 * use if one doesn't exist yet. Falls back to the native taxonomy archive
 * URL only if a Page genuinely cannot be resolved or created, so navigation
 * links never break.
 */
function cp_get_category_page_url($category)
{
    $category = $category instanceof WP_Term ? $category : get_term($category, 'category');
    if (!$category instanceof WP_Term) {
        return '';
    }

    $page = cp_get_category_page($category);
    if (!$page instanceof WP_Post) {
        $page = cp_provision_category_page($category);
    }

    if ($page instanceof WP_Post) {
        $permalink = get_permalink($page);
        if ($permalink) {
            return $permalink;
        }
    }

    $fallback = get_category_link($category);
    return is_wp_error($fallback) ? '' : $fallback;
}

function cp_provision_category_page_on_create($term_id, $tt_id, $taxonomy = 'category')
{
    if ('category' !== $taxonomy) {
        return;
    }

    $term = get_term($term_id, 'category');
    if ($term instanceof WP_Term) {
        cp_provision_category_page($term);
    }
}
add_action('created_category', 'cp_provision_category_page_on_create', 10, 3);

/**
 * Frontend-only redirect: send visitors who land on the native taxonomy
 * archive (/category/{slug}/) to the publication's real category Page
 * (/{slug}/) instead. Scoped to category archive GET requests only; skips
 * admin, AJAX, cron, REST, feeds, and previews. Only redirects when the
 * resolved destination is actually a different URL than the archive itself,
 * so a category whose Page cannot be resolved/created (falls back to
 * get_category_link()) never loops.
 */
function cp_redirect_category_archive_to_category_page()
{
    if (
        is_admin()
        || wp_doing_ajax()
        || wp_doing_cron()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || (function_exists('wp_is_json_request') && wp_is_json_request())
        || is_feed()
        || is_preview()
        || !is_category()
    ) {
        return;
    }

    $category = get_queried_object();
    if (!$category instanceof WP_Term) {
        return;
    }

    $archive_url = get_category_link($category);
    if (is_wp_error($archive_url)) {
        return;
    }

    $target_url = cp_get_category_page_url($category);
    if ('' === $target_url || untrailingslashit($target_url) === untrailingslashit($archive_url)) {
        return;
    }

    wp_safe_redirect($target_url, 301);
    exit;
}
add_action('template_redirect', 'cp_redirect_category_archive_to_category_page', 5);
