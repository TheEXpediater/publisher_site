<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_path($path = '')
{
    return CP_PATH . ltrim($path, '/\\');
}

function cp_url($path = '')
{
    return CP_URL . ltrim($path, '/\\');
}

function cp_get_publication_name()
{
    $site_name = trim(wp_strip_all_tags((string) get_bloginfo('name')));
    if ('' !== $site_name) {
        return $site_name;
    }

    $settings = function_exists('cp_settings') ? cp_settings() : [];
    $portal_title = isset($settings['portal_title']) ? trim(wp_strip_all_tags((string) $settings['portal_title'])) : '';

    return '' !== $portal_title ? $portal_title : __('Enterprise1979', 'client-portal');
}

function cp_debug_log($message, $context = [])
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $scrub = static function ($value, $key = '') use (&$scrub) {
        if (preg_match('/pass(word)?|pwd|nonce/i', (string) $key)) {
            return '[redacted]';
        }
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $item_key => $item_value) {
                $clean[$item_key] = $scrub($item_value, $item_key);
            }
            return $clean;
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return is_scalar($value) || null === $value ? $value : gettype($value);
    };

    $entry = '[Client Portal] ' . sanitize_text_field((string) $message);
    if (!empty($context) && is_array($context)) {
        $encoded = wp_json_encode($scrub($context));
        if ($encoded) {
            $entry .= ' ' . $encoded;
        }
    }

    error_log($entry); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

function cp_admin_url($page, $args = [])
{
    return add_query_arg(array_merge(['page' => sanitize_key($page)], $args), admin_url('admin.php'));
}

function cp_get_value($key, $default = '')
{
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? wp_unslash((string) $_GET[$key]) : $default;
}

function cp_post_value($key, $default = '')
{
    return isset($_POST[$key]) && is_scalar($_POST[$key]) ? wp_unslash((string) $_POST[$key]) : $default;
}

function cp_current_page()
{
    return sanitize_key(cp_get_value('page'));
}

function cp_is_portal_page($page = '')
{
    $current_page = cp_current_page();

    if ('' !== $page) {
        return sanitize_key($page) === $current_page;
    }

    return in_array($current_page, cp_portal_pages(), true);
}

function cp_is_active_page($slug)
{
    if ('cp-articles' === $slug && in_array(cp_current_page(), ['cp-article-create', 'cp-article-edit'], true)) {
        return 'active';
    }

    return cp_is_portal_page($slug) ? 'active' : '';
}

function cp_portal_pages()
{
    return ['cp-dashboard', 'cp-articles', 'cp-article-create', 'cp-article-edit', 'cp-categories', 'cp-pages', 'cp-page-create', 'cp-page-edit', 'cp-users', 'cp-analytics', 'cp-settings'];
}

function cp_wordpress_access_email()
{
    $email = defined('CP_WORDPRESS_ACCESS_EMAIL') ? CP_WORDPRESS_ACCESS_EMAIL : 'enterpriseenteng@gmail.com';
    $email = apply_filters('cp_wordpress_access_email', $email);

    return strtolower(trim(sanitize_email((string) $email)));
}

function cp_is_wordpress_access_user($user = null)
{
    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    $wordpress_email = cp_wordpress_access_email();
    $user_email = strtolower(trim((string) $user->user_email));

    return '' !== $wordpress_email && $wordpress_email === $user_email;
}

function cp_is_portal_only_user($user = null)
{
    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    /*
     * Only the designated owner account can see the native WordPress admin.
     * Every other logged-in account stays inside the full-screen portal,
     * including other users with administrator capabilities.
     */
    return !cp_is_wordpress_access_user($user);
}

function cp_is_developer($user = null)
{
    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    return $user instanceof WP_User
        && $user->exists()
        && $user->has_cap('manage_options')
        && cp_is_wordpress_access_user($user);
}

function cp_restrict_portal_admin_access()
{
    if (!cp_is_portal_only_user() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    global $pagenow;

    $allowed_endpoints = [
        'admin-ajax.php',
        'admin-post.php',
        'async-upload.php',
        'options.php',
    ];

    if (in_array($pagenow, $allowed_endpoints, true)) {
        return;
    }

    if ('admin.php' === $pagenow && cp_is_portal_page()) {
        return;
    }

    wp_safe_redirect(cp_admin_url('cp-dashboard'));
    exit;
}

function cp_restrict_portal_admin_bar($wp_admin_bar)
{
    if (!cp_is_portal_only_user() || !is_object($wp_admin_bar)) {
        return;
    }

    foreach ((array) $wp_admin_bar->get_nodes() as $node) {
        if (isset($node->id)) {
            $wp_admin_bar->remove_node($node->id);
        }
    }
}

function cp_white_label_admin_bar($wp_admin_bar)
{
    if (is_object($wp_admin_bar)) {
        $wp_admin_bar->remove_node('wp-logo');
    }
}

function cp_portal_admin_body_class($classes)
{
    /*
     * Hide the native WordPress admin chrome (adminbar, adminmenu) on every
     * client-portal page for every user, not just portal-only users. The
     * owner account (cp_is_wordpress_access_user()) is deliberately allowed
     * to use native wp-admin elsewhere, but on a portal page it must still
     * show only the custom .cp-app shell - otherwise the native adminmenu
     * (which already lists this plugin's own Dashboard/Articles/Categories/
     * Users/Analytics/Settings submenu items) renders alongside the custom
     * sidebar's own copy of the same items, reading as duplicated navigation.
     */
    if (cp_is_portal_page()) {
        $classes .= ' cp-portal-only-shell';
    }

    return trim($classes);
}

function cp_portal_show_admin_bar($show)
{
    return cp_is_portal_only_user() ? false : $show;
}

function cp_portal_login_redirect($redirect_to, $requested_redirect_to, $user)
{
    if ($user instanceof WP_User && cp_is_portal_only_user($user)) {
        return cp_admin_url('cp-dashboard');
    }

    return $redirect_to;
}

function cp_portal_admin_title($admin_title, $title)
{
    if (!cp_is_portal_only_user() || !cp_is_portal_page()) {
        return $admin_title;
    }

    $clean_title = trim(wp_strip_all_tags((string) $title));
    $portal_name = sprintf(
        /* translators: %s: Publication name. */
        __('%s Publisher Portal', 'client-portal'),
        cp_get_publication_name()
    );

    return '' !== $clean_title
        ? $clean_title . ' | ' . $portal_name
        : $portal_name;
}

function cp_portal_shell_admin_head()
{
    // Scoped to cp_is_portal_page() only - see cp_portal_admin_body_class()
    // for why this must not also require cp_is_portal_only_user().
    if (!cp_is_portal_page()) {
        return;
    }
    ?>
    <style id="cp-portal-only-shell-chrome">
        html.wp-toolbar { padding-top: 0 !important; }
        body.cp-portal-only-shell #wpadminbar,
        body.cp-portal-only-shell #adminmenumain,
        body.cp-portal-only-shell #screen-meta,
        body.cp-portal-only-shell #screen-meta-links,
        body.cp-portal-only-shell #wpfooter,
        body.cp-portal-only-shell #wpbody-content > .notice,
        body.cp-portal-only-shell #wpbody-content > .error,
        body.cp-portal-only-shell #wpbody-content > .updated,
        body.cp-portal-only-shell #wpbody-content > .update-nag { display: none !important; }
        body.cp-portal-only-shell #wpbody-content > :not(.cp-app):not(.clear) { display: none !important; }
        body.cp-portal-only-shell #wpcontent { margin-left: 0 !important; padding-left: 0 !important; }
        body.cp-portal-only-shell #wpbody { padding-top: 0 !important; }
        body.cp-portal-only-shell #wpbody-content { min-height: 100vh; padding-bottom: 0 !important; float: none !important; }
    </style>
    <?php
}

function cp_white_label_admin_footer_text($footer_text)
{
    if (!cp_is_portal_page() && !cp_is_portal_only_user()) {
        return $footer_text;
    }

    return sprintf(
        /* translators: %s: Publication name. */
        esc_html__('%s editorial dashboard', 'client-portal'),
        esc_html(cp_get_publication_name())
    );
}

function cp_white_label_admin_footer_version($version_text)
{
    if (!cp_is_portal_page() && !cp_is_portal_only_user()) {
        return $version_text;
    }

    return '';
}

function cp_remove_wordpress_dashboard_news_widget()
{
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
}

function cp_render_template($template, $data = [])
{
    $template = sanitize_file_name($template);
    $file = cp_path('templates/' . $template . '.php');

    if (!is_readable($file)) {
        wp_die(esc_html__('The requested portal template is unavailable.', 'client-portal'));
    }

    if (!empty($data)) {
        extract($data, EXTR_SKIP);
    }

    include $file;
}

function cp_render_page($template, $data = [])
{
    ob_start();
    cp_render_template($template, $data);
    $content = ob_get_clean();
    $data['content'] = $content;
    cp_render_template('layout', $data);
}

function cp_render_admin_notice($notice)
{
    $notices = cp_normalize_notices($notice);
    if (empty($notices)) {
        return;
    }
    ?>
    <div class="cp-toast-region" aria-label="<?php esc_attr_e('Portal notifications', 'client-portal'); ?>"<?php if ('' !== cp_get_value('cp_notice')) : ?> data-cp-clean-url-param="cp_notice"<?php endif; ?>>
        <?php foreach ($notices as $notice_item) : ?>
            <?php
            $type = cp_notice_type($notice_item);
            $is_error = 'danger' === $type;
            $delay = cp_notice_delay($type);
            ?>
            <div class="cp-toast cp-toast-<?php echo esc_attr($type); ?>" role="<?php echo esc_attr($is_error ? 'alert' : 'status'); ?>" aria-live="<?php echo esc_attr($is_error ? 'assertive' : 'polite'); ?>" aria-atomic="true" data-cp-toast data-cp-toast-delay="<?php echo esc_attr($delay); ?>">
                <span class="cp-toast-icon" aria-hidden="true"><i class="<?php echo esc_attr(cp_notice_icon($type)); ?>"></i></span>
                <div class="cp-toast-message"><?php echo esc_html($notice_item['message']); ?></div>
                <button type="button" class="cp-toast-close" data-cp-toast-close aria-label="<?php esc_attr_e('Dismiss notification', 'client-portal'); ?>"><i class="bi bi-x" aria-hidden="true"></i></button>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

function cp_render_notice($notice)
{
    cp_render_admin_notice($notice);
}

function cp_normalize_notices($notice)
{
    if (empty($notice)) {
        return [];
    }

    $notices = isset($notice['message']) ? [$notice] : (array) $notice;
    $normalized = [];

    foreach ($notices as $notice_item) {
        if (!is_array($notice_item) || empty($notice_item['message']) || !is_scalar($notice_item['message'])) {
            continue;
        }

        $normalized[] = [
            'type' => cp_notice_type($notice_item),
            'message' => sanitize_text_field($notice_item['message']),
        ];
    }

    return $normalized;
}

function cp_notice_type($notice)
{
    $allowed_types = ['success', 'danger', 'warning', 'info'];
    $type = isset($notice['type']) && is_scalar($notice['type']) ? sanitize_key($notice['type']) : 'info';

    return in_array($type, $allowed_types, true) ? $type : 'info';
}

function cp_notice_delay($type)
{
    $delays = [
        'success' => 4000,
        'danger' => 7000,
        'warning' => 6000,
        'info' => 5000,
    ];

    return isset($delays[$type]) ? $delays[$type] : 5000;
}

function cp_notice_icon($type)
{
    $icons = [
        'success' => 'bi bi-check-circle-fill',
        'danger' => 'bi bi-exclamation-triangle-fill',
        'warning' => 'bi bi-exclamation-circle-fill',
        'info' => 'bi bi-info-circle-fill',
    ];

    return isset($icons[$type]) ? $icons[$type] : $icons['info'];
}

function cp_settings_errors_as_notices($setting)
{
    $errors = get_settings_errors($setting);
    foreach (get_settings_errors('general') as $general_error) {
        if (isset($general_error['code']) && 'settings_updated' === $general_error['code']) {
            $errors[] = $general_error;
        }
    }

    $notices = [];
    $seen_codes = [];

    foreach ($errors as $error) {
        if (empty($error['message']) || !is_scalar($error['message'])) {
            continue;
        }

        $code = isset($error['code']) ? sanitize_key($error['code']) : md5((string) $error['message']);
        if (isset($seen_codes[$code])) {
            continue;
        }
        $seen_codes[$code] = true;

        $type = isset($error['type']) && 'updated' === $error['type'] ? 'success' : 'danger';
        if (isset($error['type']) && in_array($error['type'], ['success', 'danger', 'warning', 'info'], true)) {
            $type = $error['type'];
        }

        $notices[] = [
            'type' => $type,
            'message' => sanitize_text_field($error['message']),
        ];
    }

    return $notices;
}

function cp_get_notice_message($code)
{
    $notices = [
        'article_created' => ['type' => 'success', 'message' => __('Article created successfully.', 'client-portal')],
        'article_updated' => ['type' => 'success', 'message' => __('Article updated successfully.', 'client-portal')],
        'article_deleted' => ['type' => 'success', 'message' => __('Article deleted successfully.', 'client-portal')],
        'article_published' => ['type' => 'success', 'message' => __('Article published successfully.', 'client-portal')],
        'publish_not_allowed' => ['type' => 'danger', 'message' => __('You are not allowed to publish articles directly. Please contact an administrator or save the article as draft.', 'client-portal')],
        'article_publish_failed' => ['type' => 'danger', 'message' => __('The article could not be published. Please try again.', 'client-portal')],
        'homepage_feature_updated' => ['type' => 'success', 'message' => __('Homepage Featured Article updated successfully.', 'client-portal')],
        'homepage_feature_cleared' => ['type' => 'success', 'message' => __('Homepage Featured Article cleared successfully.', 'client-portal')],
        'homepage_feature_not_allowed' => ['type' => 'danger', 'message' => __('Only editors and administrators may select the homepage featured article.', 'client-portal')],
        'homepage_feature_requires_publish' => ['type' => 'warning', 'message' => __('The article was saved, but only published articles may appear as the homepage feature.', 'client-portal')],
        'homepage_feature_invalid_article' => ['type' => 'danger', 'message' => __('The selected homepage feature must be a published Enterprise article.', 'client-portal')],
        'category_created' => ['type' => 'success', 'message' => __('Category created successfully.', 'client-portal')],
        'category_updated' => ['type' => 'success', 'message' => __('Category updated successfully.', 'client-portal')],
        'category_updated_active' => ['type' => 'success', 'message' => __('Category updated successfully. The category is now active.', 'client-portal')],
        'category_updated_inactive' => ['type' => 'success', 'message' => __('Category updated successfully. The category is now inactive.', 'client-portal')],
        'category_deleted' => ['type' => 'success', 'message' => __('Category deleted successfully.', 'client-portal')],
        'page_created' => ['type' => 'success', 'message' => __('Page created successfully.', 'client-portal')],
        'page_updated' => ['type' => 'success', 'message' => __('Page updated successfully.', 'client-portal')],
        'page_trashed' => ['type' => 'success', 'message' => __('Page moved to Trash.', 'client-portal')],
        'page_order_updated' => ['type' => 'success', 'message' => __('Page order updated.', 'client-portal')],
        'settings_saved' => ['type' => 'success', 'message' => __('Settings saved successfully.', 'client-portal')],
        'user-created' => ['type' => 'success', 'message' => __('User created successfully.', 'client-portal')],
        'user-updated' => ['type' => 'success', 'message' => __('User updated successfully.', 'client-portal')],
        'user-created-with-photo' => ['type' => 'success', 'message' => __('User created successfully. Profile photo uploaded.', 'client-portal')],
        'user-updated-with-photo' => ['type' => 'success', 'message' => __('User updated successfully. Profile photo uploaded.', 'client-portal')],
        'user-deleted' => ['type' => 'success', 'message' => __('User deleted successfully.', 'client-portal')],
    ];

    return isset($notices[$code]) ? $notices[$code] : null;
}

function cp_notice($code)
{
    return cp_get_notice_message($code);
}

function cp_set_temporary_notice($type, $message)
{
    if (!is_user_logged_in()) {
        return;
    }

    $allowed_types = ['success', 'danger', 'warning', 'info'];
    $notice = [
        'type' => in_array($type, $allowed_types, true) ? $type : 'info',
        'message' => sanitize_text_field($message),
    ];
    set_transient('cp_portal_notice_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS);
}

function cp_pull_temporary_notice()
{
    if (!is_user_logged_in()) {
        return null;
    }

    $key = 'cp_portal_notice_' . get_current_user_id();
    $notice = get_transient($key);
    delete_transient($key);

    return is_array($notice) ? $notice : null;
}

function cp_request_notice()
{
    $code = sanitize_key(cp_get_value('cp_notice'));
    $temporary_notice = cp_pull_temporary_notice();
    return cp_get_notice_message($code) ?: $temporary_notice;
}

function cp_redirect($page, $args = [])
{
    $target = cp_admin_url($page, $args);
    cp_debug_log('Portal redirect', ['target' => $target]);
    wp_safe_redirect($target);
    exit;
}

function cp_article_counts()
{
    $counts = wp_count_posts('post');
    $published = isset($counts->publish) ? (int) $counts->publish : 0;
    $drafts = isset($counts->draft) ? (int) $counts->draft : 0;
    $private = isset($counts->private) ? (int) $counts->private : 0;

    return [
        'total' => $published + $drafts + $private,
        'published' => $published,
        'drafts' => $drafts,
        'private' => $private,
    ];
}

function cp_dashboard_statistics()
{
    $counts = cp_article_counts();
    $user_counts = count_users();
    $category_count = wp_count_terms(['taxonomy' => 'category', 'hide_empty' => false]);

    return [
        'total_articles' => $counts['total'],
        'published' => $counts['published'],
        'drafts' => $counts['drafts'],
        'users' => isset($user_counts['total_users']) ? (int) $user_counts['total_users'] : 0,
        'categories' => is_wp_error($category_count) ? 0 : (int) $category_count,
    ];
}

function cp_sanitize_status($status, $fallback = 'draft')
{
    $status = sanitize_key($status);
    return in_array($status, ['draft', 'publish', 'private'], true) ? $status : $fallback;
}

function cp_is_author_portal_user($user = null)
{
    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    $roles = (array) $user->roles;

    // Higher editorial roles keep their normal Article Library permissions.
    if (array_intersect(['administrator', 'editor'], $roles)) {
        return false;
    }

    return in_array('author', $roles, true);
}

function cp_allowed_roles()
{
    $roles = [
        'administrator' => __('Administrator', 'client-portal'),
        'editor' => __('Editor', 'client-portal'),
        'author' => __('Author', 'client-portal'),
    ];

    if (!current_user_can('manage_options')) {
        unset($roles['administrator']);
    }

    return $roles;
}

function cp_require_capability($capability, ...$args)
{
    if (!current_user_can($capability, ...$args)) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'), '', ['response' => 403]);
    }
}

function cp_settings()
{
    return wp_parse_args(
        get_option('cp_portal_settings', []),
        [
            'portal_title' => 'Enterprise1979 Publisher Portal',
            'default_status' => 'draft',
            'items_per_page' => 20,
            'allow_authors_publish' => 0,
        ]
    );
}

function cp_can_publish_directly()
{
    if (!current_user_can('publish_posts')) {
        return false;
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    if (array_intersect(['administrator', 'editor'], $roles)) {
        return true;
    }

    if (in_array('author', $roles, true) && empty(cp_settings()['allow_authors_publish'])) {
        return false;
    }

    return true;
}

function cp_can_manage_homepage_feature()
{
    if (!is_user_logged_in() || !current_user_can('publish_posts') || !current_user_can('edit_others_posts')) {
        return false;
    }

    $user = wp_get_current_user();
    $roles = $user instanceof WP_User ? (array) $user->roles : [];

    return (bool) array_intersect(['administrator', 'editor'], $roles);
}

function cp_homepage_feature_option_key()
{
    return defined('CP_HOMEPAGE_FEATURED_ARTICLE_OPTION')
        ? CP_HOMEPAGE_FEATURED_ARTICLE_OPTION
        : 'cp_homepage_featured_article_id';
}

function cp_is_valid_homepage_feature_article($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id || !function_exists('cp_is_enterprise_article')) {
        return false;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post || 'post' !== $post->post_type || 'publish' !== $post->post_status) {
        return false;
    }

    return cp_is_enterprise_article($post);
}

function cp_get_homepage_featured_article_id()
{
    $post_id = absint(get_option(cp_homepage_feature_option_key(), 0));
    if (!$post_id) {
        return 0;
    }

    if (!function_exists('cp_is_enterprise_article')) {
        return 0;
    }

    if (!cp_is_valid_homepage_feature_article($post_id)) {
        delete_option(cp_homepage_feature_option_key());
        return 0;
    }

    return $post_id;
}

function cp_get_homepage_featured_article()
{
    $post_id = cp_get_homepage_featured_article_id();
    if (!$post_id) {
        return null;
    }

    $post = get_post($post_id);
    return $post instanceof WP_Post ? $post : null;
}

function cp_set_homepage_featured_article($post_id)
{
    $post_id = absint($post_id);
    if (!cp_is_valid_homepage_feature_article($post_id)) {
        return false;
    }

    update_option(cp_homepage_feature_option_key(), $post_id, false);

    return absint(get_option(cp_homepage_feature_option_key(), 0)) === $post_id;
}

function cp_clear_homepage_featured_article()
{
    delete_option(cp_homepage_feature_option_key());
}

function cp_is_homepage_featured_article($post_id)
{
    $post_id = absint($post_id);
    return $post_id && $post_id === cp_get_homepage_featured_article_id();
}

function cp_clear_homepage_feature_on_status_change($new_status, $old_status, $post)
{
    if (!$post instanceof WP_Post || 'post' !== $post->post_type || 'publish' === $new_status) {
        return;
    }

    if (!function_exists('cp_is_enterprise_article') || !cp_is_enterprise_article($post)) {
        return;
    }

    if (absint(get_option(cp_homepage_feature_option_key(), 0)) === absint($post->ID)) {
        cp_clear_homepage_featured_article();
    }
}

function cp_clear_homepage_feature_on_post_removed($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id || absint(get_option(cp_homepage_feature_option_key(), 0)) !== $post_id) {
        return;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post || 'post' !== $post->post_type) {
        return;
    }

    if (function_exists('cp_is_enterprise_article') && !cp_is_enterprise_article($post)) {
        return;
    }

    cp_clear_homepage_featured_article();
}

function cp_article_custom_author_meta_key()
{
    return '_cp_article_custom_author';
}

function cp_can_assign_article_author()
{
    if (!is_user_logged_in() || !current_user_can('edit_others_posts')) {
        return false;
    }

    $user = wp_get_current_user();
    return $user instanceof WP_User && (bool) array_intersect(['administrator', 'editor'], (array) $user->roles);
}

function cp_sanitize_article_custom_author($value)
{
    $value = sanitize_text_field((string) $value);
    $value = preg_replace('/\s+/u', ' ', trim($value));

    if (!is_string($value)) {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, 200);
    }

    return substr($value, 0, 200);
}

function cp_get_article_custom_author($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return '';
    }

    return cp_sanitize_article_custom_author(get_post_meta($post_id, cp_article_custom_author_meta_key(), true));
}

function cp_get_article_display_author($post)
{
    $post = get_post($post);
    if (!$post instanceof WP_Post) {
        return '';
    }

    $custom_author = cp_get_article_custom_author($post->ID);
    if ('' !== $custom_author) {
        return $custom_author;
    }

    $account_name = get_the_author_meta('display_name', $post->post_author);
    return $account_name ? sanitize_text_field($account_name) : __('Unknown author', 'client-portal');
}

function cp_article_author_role_label($user)
{
    if (!$user instanceof WP_User) {
        return '';
    }

    $roles = wp_roles();
    foreach ((array) $user->roles as $role) {
        if (isset($roles->roles[$role]['name'])) {
            return translate_user_role($roles->roles[$role]['name']);
        }
    }

    return __('User', 'client-portal');
}

function cp_is_valid_article_author_account($user_id)
{
    $user = get_user_by('id', absint($user_id));
    if (!$user instanceof WP_User) {
        return false;
    }

    return (bool) array_intersect(array_keys(cp_allowed_roles()), (array) $user->roles);
}

function cp_get_article_author_accounts()
{
    if (!cp_can_assign_article_author()) {
        return [];
    }

    $accounts = [];
    $allowed_roles = array_keys(cp_allowed_roles());
    $users = get_users([
        'role__in' => $allowed_roles,
        'orderby' => 'display_name',
        'order' => 'ASC',
    ]);

    foreach ($users as $user) {
        if (!$user instanceof WP_User || !array_intersect($allowed_roles, (array) $user->roles)) {
            continue;
        }

        $accounts[] = [
            'id' => absint($user->ID),
            'name' => sanitize_text_field($user->display_name ?: $user->user_login),
            'role' => cp_article_author_role_label($user),
        ];
    }

    return $accounts;
}

function cp_get_article_author_editor_data($post = null)
{
    if (!$post instanceof WP_Post) {
        $post = $post ? get_post($post) : null;
    }
    $current_user = wp_get_current_user();
    $owner_id = $post instanceof WP_Post ? absint($post->post_author) : absint($current_user->ID);
    $owner = $owner_id ? get_user_by('id', $owner_id) : null;

    if (!$owner instanceof WP_User) {
        $owner = $current_user;
        $owner_id = absint($current_user->ID);
    }

    $custom_author = $post instanceof WP_Post ? cp_get_article_custom_author($post->ID) : '';
    $mode = '' !== $custom_author ? 'custom' : 'account';
    $display_name = 'custom' === $mode
        ? $custom_author
        : sanitize_text_field($owner->display_name ?: $owner->user_login);

    return [
        'mode' => $mode,
        'user_id' => $owner_id,
        'custom' => $custom_author,
        'display_name' => $display_name,
        'can_edit' => cp_can_assign_article_author(),
    ];
}

function cp_status_badge_class($status)
{
    $classes = ['publish' => 'success', 'draft' => 'warning', 'private' => 'secondary'];
    return isset($classes[$status]) ? $classes[$status] : 'secondary';
}

/**
 * Version string for a local client-portal CSS/JS asset. Uses the file's
 * own modification time rather than the static CP_VERSION constant, so
 * editing a portal CSS/JS file automatically changes its enqueued URL
 * (?ver=...) and forces browsers/CDNs to fetch the new version instead of
 * continuing to serve a stale cached copy under an unchanged version string.
 * Falls back to CP_VERSION only if the file can't be found on disk.
 */
function cp_asset_version($relative_path)
{
    $absolute_path = cp_path($relative_path);
    $mtime = file_exists($absolute_path) ? filemtime($absolute_path) : false;

    return $mtime ? (string) $mtime : CP_VERSION;
}

function cp_enqueue_admin_assets()
{
    if (!cp_is_portal_page()) {
        return;
    }

    wp_enqueue_style('cp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_style('cp-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', [], '1.11.3');
    wp_enqueue_style('cp-style', cp_url('assets/css/style.css'), ['cp-bootstrap'], cp_asset_version('assets/css/style.css'));
    wp_enqueue_style('cp-dashboard', cp_url('assets/css/dashboard.css'), ['cp-style'], cp_asset_version('assets/css/dashboard.css'));
    wp_enqueue_script('cp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], '5.3.3', true);
    wp_enqueue_script('cp-app', cp_url('assets/js/app.js'), ['cp-bootstrap'], cp_asset_version('assets/js/app.js'), true);
    wp_enqueue_script('cp-dashboard', cp_url('assets/js/dashboard.js'), ['cp-app'], cp_asset_version('assets/js/dashboard.js'), true);

    if ('cp-categories' === cp_current_page()) {
        wp_enqueue_style('cp-frontend-navigation', cp_url('assets/css/frontend-navigation.css'), ['cp-style'], cp_asset_version('assets/css/frontend-navigation.css'));
        wp_enqueue_style('cp-category-menu-editor', cp_url('assets/css/category-menu-editor.css'), ['cp-frontend-navigation'], cp_asset_version('assets/css/category-menu-editor.css'));
        wp_enqueue_script('cp-categories', cp_url('assets/js/categories.js'), ['cp-app'], cp_asset_version('assets/js/categories.js'), true);
    }

    if ('cp-users' === cp_current_page()) {
        wp_enqueue_script('cp-users', cp_url('assets/js/users.js'), ['cp-app'], cp_asset_version('assets/js/users.js'), true);
    }

    if ('cp-analytics' === cp_current_page()) {
        wp_enqueue_script('cp-analytics', cp_url('assets/js/analytics.js'), ['cp-app'], cp_asset_version('assets/js/analytics.js'), true);
        wp_localize_script(
            'cp-analytics',
            'cpAnalyticsGoogleData',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cp_analytics_google_data'),
                'defaultRange' => cp_analytics_default_date_range(),
                'strings' => [
                    'loading' => __('Loading...', 'client-portal'),
                    'noData' => __('No data is available for this period.', 'client-portal'),
                    'error' => __('Website analytics are temporarily unavailable.', 'client-portal'),
                    'refreshLabel' => __('Refresh Data', 'client-portal'),
                    'refreshing' => __('Refreshing...', 'client-portal'),
                    'refreshed' => __('Analytics refreshed successfully.', 'client-portal'),
                    'lastUpdated' => __('Last updated:', 'client-portal'),
                    'visitors' => __('Visitors', 'client-portal'),
                    'pageViews' => __('Page Views', 'client-portal'),
                ],
            ]
        );
    }

    if ('cp-dashboard' === cp_current_page()) {
        wp_localize_script(
            'cp-dashboard',
            'cpDashboardFeaturePicker',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cp_homepage_feature_picker'),
                'canManage' => cp_can_manage_homepage_feature(),
                'perPage' => 20,
                'publicationName' => cp_get_publication_name(),
                'strings' => [
                    'loading' => __('Loading articles...', 'client-portal'),
                    'empty' => __('No published Enterprise articles match your filters.', 'client-portal'),
                    'error' => __('Articles could not be loaded. Please try again.', 'client-portal'),
                    'selectArticle' => __('Select article', 'client-portal'),
                    'currentFeatured' => __('Current Featured', 'client-portal'),
                    'noImage' => __('No Image', 'client-portal'),
                    'showing' => __('Showing %1$s to %2$s of %3$s articles', 'client-portal'),
                    'featureConfirm' => __('Feature "%s" on the homepage?', 'client-portal'),
                    'replaceCurrent' => __('This will replace "%s" as the homepage featured article.', 'client-portal'),
                    'clearConfirm' => __('Remove "%s" from the homepage featured position?', 'client-portal'),
                    'fallback' => __('The newest published Enterprise article will appear until another article is selected.', 'client-portal'),
                    'choose' => __('Choose Featured Article', 'client-portal'),
                    'change' => __('Change Featured Article', 'client-portal'),
                    'viewArticle' => __('View Article', 'client-portal'),
                    'editArticle' => __('Edit Article', 'client-portal'),
                ],
            ]
        );
    }

    if (in_array(cp_current_page(), ['cp-article-create', 'cp-article-edit'], true)) {
        wp_enqueue_media();
        wp_enqueue_editor();
        wp_enqueue_style('cp-article-builder', cp_url('assets/css/article-builder.css'), ['cp-style'], cp_asset_version('assets/css/article-builder.css'));
        wp_enqueue_script('cp-article-builder', cp_url('assets/js/article-builder.js'), ['cp-app', 'media-editor', 'wp-editor'], cp_asset_version('assets/js/article-builder.js'), true);
    }

    if (in_array(cp_current_page(), ['cp-page-create', 'cp-page-edit'], true)) {
        wp_enqueue_media();
        wp_enqueue_editor();
        // Reuses the Article Builder's own block-canvas chrome
        // (.cp-builder-block, .cp-icon-button, .cp-add-block, etc.) rather
        // than restyling the same shapes a second time - see
        // assets/css/page-builder.css for the page-specific additions only.
        wp_enqueue_style('cp-article-builder', cp_url('assets/css/article-builder.css'), ['cp-style'], cp_asset_version('assets/css/article-builder.css'));
        wp_enqueue_style('cp-page-builder', cp_url('assets/css/page-builder.css'), ['cp-article-builder'], cp_asset_version('assets/css/page-builder.css'));
        wp_enqueue_script('cp-page-builder', cp_url('assets/js/page-builder.js'), ['cp-app', 'media-editor', 'wp-editor'], cp_asset_version('assets/js/page-builder.js'), true);
        // Trusted bundled Staff artwork (key => URL) so Staff cards added
        // client-side get the same Bundled Artwork choices as saved ones.
        wp_localize_script('cp-page-builder', 'cpPageBuilder', [
            'staffArtwork' => cp_staff_bundled_artwork(),
            'strings' => [
                'editorFailed' => __('The visual editor could not load, so this block is showing its underlying HTML. Your saved content is safe. Reload the page to try again, or edit carefully below.', 'client-portal'),
                'mediaUnavailable' => __('The Media Library is unavailable on this page. Reload the page and try again.', 'client-portal'),
                'deleteTitle' => __('Delete Block', 'client-portal'),
                'deleteMessage' => __('Remove this block from the page? The change is only kept if you save the page.', 'client-portal'),
                'deleteLabel' => __('Delete Block', 'client-portal'),
                'fullScreen' => __('Full Screen', 'client-portal'),
                'exitFullScreen' => __('Exit Full Screen', 'client-portal'),
                'selectImage' => __('Select Image', 'client-portal'),
                'replaceImage' => __('Replace Image', 'client-portal'),
                'insertAbove' => __('Insert block above', 'client-portal'),
                'insertBelow' => __('Insert block below', 'client-portal'),
                'alignLeft' => __('Align left', 'client-portal'),
                'alignCenter' => __('Align center', 'client-portal'),
                'alignRight' => __('Align right', 'client-portal'),
            ],
        ]);
    }

    if ('cp-pages' === cp_current_page()) {
        // The About Us Navigation settings modal's Page Order list reuses
        // the Category Menu Editor's own drag/move-button item chrome
        // (.cp-menu-editor-item, .cp-menu-editor-move-btn, etc. - see
        // assets/css/category-menu-editor.css) rather than a second copy of
        // the same interaction pattern - see assets/css/page-manager.css
        // for the Pages-screen-specific additions only (the gear button,
        // the small dropdown-preview mock).
        wp_enqueue_style('cp-frontend-navigation', cp_url('assets/css/frontend-navigation.css'), ['cp-style'], cp_asset_version('assets/css/frontend-navigation.css'));
        wp_enqueue_style('cp-category-menu-editor', cp_url('assets/css/category-menu-editor.css'), ['cp-frontend-navigation'], cp_asset_version('assets/css/category-menu-editor.css'));
        wp_enqueue_style('cp-page-manager', cp_url('assets/css/page-manager.css'), ['cp-category-menu-editor'], cp_asset_version('assets/css/page-manager.css'));
        wp_enqueue_script('cp-page-manager', cp_url('assets/js/page-manager.js'), ['cp-app'], cp_asset_version('assets/js/page-manager.js'), true);
    }
}
