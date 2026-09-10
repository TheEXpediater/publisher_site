<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Idempotent seed/migration for the initial managed Pages (About Us,
 * Staff, Join the Publication). Safe to run more than once: each Page is
 * identified by its known slug first (an existing About Us page from
 * before this feature already exists on this site), and once a Page
 * already carries CP_PAGE_MANAGED_META this routine never overwrites its
 * blocks again - only an administrator editing it through the Page
 * Builder does that. Content is sourced verbatim from the project's
 * authoritative "ABOUT US - THE ENTERPRISE.docx" and "MASTERLIST WITH
 * PORTRAITS OF ENTENG.docx" documents; nothing here is invented.
 *
 * Gated to run at most once per plugin version via CP_PAGES_SEED_OPTION,
 * rather than on every admin_init.
 */

define('CP_PAGES_SEED_VERSION', 2);
define('CP_PAGES_SEED_OPTION', 'cp_pages_seed_version');

function cp_maybe_run_page_seed()
{
    if (!function_exists('cp_can_manage_pages')) {
        return;
    }

    $current = absint(get_option(CP_PAGES_SEED_OPTION, 0));
    if ($current >= CP_PAGES_SEED_VERSION) {
        return;
    }

    cp_run_page_seed();
    update_option(CP_PAGES_SEED_OPTION, CP_PAGES_SEED_VERSION, true);
}
add_action('admin_init', 'cp_maybe_run_page_seed', 5);

function cp_run_page_seed()
{
    $about_us_id = cp_seed_about_us_page();
    if ($about_us_id) {
        update_option(CP_ABOUT_US_PAGE_ID_OPTION, $about_us_id, true);
    }

    cp_seed_join_page();
    cp_seed_staff_page();
}

/**
 * A managed Page's mapped-to-a-category Elementor/page-builder-template
 * meta (site-content-layout, _elementor_*) is stripped only the first time
 * this seed takes the page over - i.e. only when it does not yet carry
 * CP_PAGE_MANAGED_META - so it never touches a page an administrator has
 * since configured through some other builder on purpose.
 */
function cp_seed_release_other_builder_meta($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return;
    }

    $keys = [
        '_elementor_edit_mode',
        '_elementor_template_type',
        '_elementor_data',
        '_elementor_page_settings',
        '_elementor_element_cache',
        '_elementor_css',
        '_elementor_version',
        '_elementor_page_assets',
        'site-content-layout',
        'ast-site-content-layout',
    ];

    foreach ($keys as $key) {
        delete_post_meta($post_id, $key);
    }
}

/**
 * Finds an existing managed Page by slug, or creates one. Returns the Page
 * ID, or 0 on failure. Never creates a duplicate on repeat runs.
 */
function cp_seed_find_or_create_page($slug, $title)
{
    $existing = get_page_by_path($slug);
    if ($existing instanceof WP_Post) {
        return $existing->ID;
    }

    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'publish',
        'post_content' => '',
    ], true);

    return is_wp_error($page_id) ? 0 : absint($page_id);
}

function cp_seed_about_us_page()
{
    $page_id = cp_seed_find_or_create_page('about-us', __('About Us', 'client-portal'));
    if (!$page_id) {
        return 0;
    }

    // Applied unconditionally, even on a repeat run past the content-seed
    // guard below, so a Page already migrated by an earlier seed version
    // still picks up a title-suppression fix added in a later one.
    cp_suppress_theme_page_title($page_id);

    if ('1' === get_post_meta($page_id, CP_PAGE_MANAGED_META, true)) {
        // Already migrated in a previous run - leave any admin edits alone.
        return $page_id;
    }

    cp_seed_release_other_builder_meta($page_id);

    $join_url = home_url('/join-the-publication/');
    $blocks = [
        ['type' => 'heading', 'level' => 1, 'content' => __('About Us', 'client-portal')],
        ['type' => 'heading', 'level' => 2, 'content' => __('About the Publication', 'client-portal')],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html(
            '<p>' . __('The Enterprise is the official student publication of the School of Business and Accountancy in Holy Angel University, with the mission of delivering quality and relevant information to the student body.', 'client-portal') . '</p>'
            . '<p>' . __('The publication takes utmost pride in providing service to the community through responsible and unbiased journalism that adheres to the principles of fairness, accuracy, and fact-based reportage to help the students form enlightened opinions and wise decisions. In unity with the community, it aims to highlight societal matters as a way to permeate an elevated sense of conscientiousness.', 'client-portal') . '</p>'
            . '<p>' . __('The Enterprise is committed to widening the Holy Angel community’s artistic consciousness by providing free rein to creative expressions that uphold aesthetic tenets, Kapampangan Culture and Literature, and universal human values.', 'client-portal') . '</p>'
            . '<p>' . __('The members devote themselves to the ideals of true democracy and independence, founded on equal rights and social justice in the manner of free speech and expression to serve and protect the rights of the students—to act where we must, and to speak when we are needed.', 'client-portal') . '</p>'
        )],
        ['type' => 'divider', 'size' => 'medium'],
        ['type' => 'heading', 'level' => 2, 'content' => __('Brief History', 'client-portal')],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html(
            '<p>' . __('The Enterprise was established in 1979 under the name “BSBA Tabloid”. This was changed to “The Tabloid” in 1988 and eventually became “The Enterprise”, as the name was made to be more specific to the college it serves. It has carried the same values it was built on for the past four decades with utmost objectivity and fairness, striving to offer “A Fair Deal”.', 'client-portal') . '</p>'
        )],
        ['type' => 'divider', 'size' => 'medium'],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html(
            '<p>' . __('Interested in joining The Enterprise?', 'client-portal') . '</p>'
        )],
        ['type' => 'button', 'label' => __('Join the Publication', 'client-portal'), 'url' => esc_url_raw($join_url), 'style' => 'primary'],
    ];

    cp_save_page_blocks($page_id, $blocks);
    update_post_meta($page_id, CP_PAGE_MANAGED_META, '1');

    wp_update_post([
        'ID' => $page_id,
        'post_status' => 'publish',
        'post_content' => __('The Enterprise is the official student publication of the School of Business and Accountancy in Holy Angel University.', 'client-portal'),
    ]);

    return $page_id;
}

function cp_seed_join_page()
{
    $page_id = cp_seed_find_or_create_page('join-the-publication', __('Join the Publication', 'client-portal'));
    if (!$page_id) {
        return 0;
    }

    cp_suppress_theme_page_title($page_id);

    if ('1' === get_post_meta($page_id, CP_PAGE_MANAGED_META, true)) {
        return $page_id;
    }

    cp_seed_release_other_builder_meta($page_id);

    $forms_url = 'https://docs.google.com/forms/d/e/1FAIpQLSfiEBI3hyGbQhCWSKoLCl-APZm4y3w3gkyaPXRn_6C-plSpoQ/viewform?usp=sharing&ouid=106864529802085452903';

    $blocks = [
        ['type' => 'heading', 'level' => 1, 'content' => __('Join the Publication', 'client-portal')],
        ['type' => 'heading', 'level' => 2, 'content' => __('Membership Requirements and Procedures', 'client-portal')],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html(
            '<p>' . __('Membership in The Enterprise is open to any bona fide student of the School of Business and Accountancy enrolled in the current semester or term. Applicants must meet the qualifications and standards set by The Enterprise and must maintain a satisfactory academic standing.', 'client-portal') . '</p>'
            . '<p>' . __('Aspiring members of the publication shall undergo a series of examinations and evaluations administered by The Enterprise. These include at the minimum a reportorial examination and an interview with the editors of the publication. The Enterprise may, at its discretion, require additional assessments as part of the application process as it deems fit. Those who pass the evaluations shall be admitted as reportorial staff.', 'client-portal') . '</p>'
        )],
        ['type' => 'divider', 'size' => 'medium'],
        ['type' => 'heading', 'level' => 2, 'content' => __('Join the Publication', 'client-portal')],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html('<p>' . __('Be part of our family. Seal the deal with us through this link:', 'client-portal') . '</p>')],
        ['type' => 'button', 'label' => __('Open the Application Form', 'client-portal'), 'url' => esc_url_raw($forms_url), 'style' => 'primary'],
    ];

    cp_save_page_blocks($page_id, $blocks);
    update_post_meta($page_id, CP_PAGE_MANAGED_META, '1');
    update_post_meta($page_id, CP_PAGE_ABOUT_NAV_META, '1');
    cp_append_about_nav_order($page_id);

    wp_update_post([
        'ID' => $page_id,
        'post_status' => 'publish',
        'post_content' => __('Membership in The Enterprise is open to any bona fide student of the School of Business and Accountancy.', 'client-portal'),
    ]);

    return $page_id;
}

/**
 * The authoritative staff roster (MASTERLIST WITH PORTRAITS OF ENTENG.docx,
 * cross-referenced against CLAUDE.md 11.1's explicit grouping - the docx
 * itself is an ungrouped flat name/position table with no conflicting
 * grouping of its own, so CLAUDE.md's grouping is used as-is). No
 * portraits are seeded (none were supplied) - every card renders with the
 * neutral placeholder in cp_render_staff_portrait() until an administrator
 * uploads a real one through the Page Builder.
 */
function cp_seed_staff_roster()
{
    $roster = [
        'Editorial Board' => [
            ['Maryiel N. Jimenez', 'Editor-in-Chief'],
            ['Cyra Joyce G. Aguilar', 'Associate Editor - Internal Affairs'],
            ['Jan Nicole A. Mallari', 'Associate Editor - External Affairs'],
            ['Paullete Irys G. De Leon', 'Managing Editor'],
        ],
        'Editorial Staff' => [
            ['Alexa G. Soriano', 'Senior Editor'],
            ['Angel Bethany T. Timbol', 'Senior Layout Editor'],
            ['Sean Naegel V. Beltran', 'Junior Layout Editor'],
            ['Merella Jesmine I. Gumabon', 'Literary Editor'],
            ['Razel Iommi Q. Tiongson', 'Head Cartoonist'],
            ['Boris Sebastian C. Lontabo', 'Head Photojournalist'],
            ['Mikaella C. de Leon', 'Executive Secretary'],
            ['Samantha C. Ruelo', 'Events Manager'],
            ['Margareth Lois R. Moleño', 'Circulation Manager'],
            ['Shelyca Franshane M. Simeon', 'Online Content Manager'],
            ['Kristine Charsi T. Lusanez', 'Online Content Manager'],
        ],
        'Reportorial Staff' => [
            ['Rinka Akisha M. Muldong', 'Reportorial Staff'],
            ['Pauleene P. Cabigting', 'Reportorial Staff'],
            ['Hydelyn Mae C. De Roxas', 'Reportorial Staff'],
            ['Allison Louise T. Dulatre', 'Reportorial Staff'],
            ['Eugene P. Lee', 'Reportorial Staff'],
            ['Yzabel Euri O. Enriquez', 'Reportorial Staff'],
            ['Marizz O. Estrella', 'Reportorial Staff'],
            ['Shannon Beatriz P. Dela Vega', 'Reportorial Staff'],
            ['Juliana Cloe S. Cosme', 'Reportorial Staff'],
            ['Keona Alexis M. Arceo', 'Reportorial Staff'],
            ['Ferdina Faye M. Bacani', 'Reportorial Staff'],
        ],
    ];

    $people = [];
    foreach ($roster as $group => $members) {
        foreach ($members as $member) {
            $people[] = [
                'name' => $member[0],
                'position' => $member[1],
                'group' => $group,
                'attachment_id' => 0,
                'alt' => $member[0],
            ];
        }
    }

    return $people;
}

function cp_seed_staff_page()
{
    $page_id = cp_seed_find_or_create_page('staff', __('Staff', 'client-portal'));
    if (!$page_id) {
        return 0;
    }

    cp_suppress_theme_page_title($page_id);

    if ('1' === get_post_meta($page_id, CP_PAGE_MANAGED_META, true)) {
        return $page_id;
    }

    cp_seed_release_other_builder_meta($page_id);

    $blocks = [
        ['type' => 'heading', 'level' => 1, 'content' => __('Our Staff', 'client-portal')],
        ['type' => 'richtext', 'content' => cp_sanitize_rich_paragraph_html('<p>' . __('Meet the editorial board, editorial staff, and reportorial staff of The Enterprise.', 'client-portal') . '</p>')],
        ['type' => 'staff_grid', 'people' => cp_seed_staff_roster()],
    ];

    cp_save_page_blocks($page_id, $blocks);
    update_post_meta($page_id, CP_PAGE_MANAGED_META, '1');
    update_post_meta($page_id, CP_PAGE_ABOUT_NAV_META, '1');
    cp_append_about_nav_order($page_id);

    wp_update_post([
        'ID' => $page_id,
        'post_status' => 'publish',
        'post_content' => __('Meet the editorial board, editorial staff, and reportorial staff of The Enterprise.', 'client-portal'),
    ]);

    return $page_id;
}
