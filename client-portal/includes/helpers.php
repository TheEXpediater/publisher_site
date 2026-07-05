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
    return ['cp-dashboard', 'cp-articles', 'cp-article-create', 'cp-article-edit', 'cp-categories', 'cp-users', 'cp-analytics', 'cp-settings'];
}

function cp_is_developer()
{
    $user = wp_get_current_user();

    return $user instanceof WP_User
        && 'enterpriseenteng@gmail.com' === strtolower(trim($user->user_email));
}

function cp_is_portal_only_user()
{
    return is_user_logged_in() && !cp_is_developer();
}

function cp_restrict_portal_admin_access()
{
    if (!cp_is_portal_only_user() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    global $pagenow;

    $allowed_endpoints = ['admin-ajax.php', 'async-upload.php', 'media-upload.php', 'options.php', 'admin-post.php'];
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

    $allowed_nodes = ['top-secondary', 'my-account', 'user-actions', 'user-info', 'edit-profile', 'logout'];
    foreach ((array) $wp_admin_bar->get_nodes() as $node) {
        if (isset($node->id) && !in_array($node->id, $allowed_nodes, true)) {
            $wp_admin_bar->remove_node($node->id);
        }
    }
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
    if (empty($notice['message'])) {
        return;
    }

    $allowed_types = ['success', 'danger', 'warning', 'info'];
    $type = isset($notice['type']) && in_array($notice['type'], $allowed_types, true) ? $notice['type'] : 'info';
    ?>
    <div class="cp-notice alert alert-<?php echo esc_attr($type); ?> alert-dismissible fade show" role="alert">
        <?php echo esc_html($notice['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
    </div>
    <?php
}

function cp_render_notice($notice)
{
    cp_render_admin_notice($notice);
}

function cp_get_notice_message($code)
{
    $notices = [
        'article_created' => ['type' => 'success', 'message' => __('Article created successfully.', 'client-portal')],
        'article_updated' => ['type' => 'success', 'message' => __('Article updated successfully.', 'client-portal')],
        'article_deleted' => ['type' => 'success', 'message' => __('Article deleted successfully.', 'client-portal')],
        'category_created' => ['type' => 'success', 'message' => __('Category created successfully.', 'client-portal')],
        'category_updated' => ['type' => 'success', 'message' => __('Category updated successfully.', 'client-portal')],
        'category_deleted' => ['type' => 'success', 'message' => __('Category deleted successfully.', 'client-portal')],
        'settings_saved' => ['type' => 'success', 'message' => __('Settings saved successfully.', 'client-portal')],
        'user-created' => ['type' => 'success', 'message' => __('User created successfully.', 'client-portal')],
        'user-updated' => ['type' => 'success', 'message' => __('User updated successfully.', 'client-portal')],
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
    wp_safe_redirect(cp_admin_url($page, $args));
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
    if (in_array('author', (array) $user->roles, true) && empty(cp_settings()['allow_authors_publish'])) {
        return false;
    }

    return true;
}

function cp_status_badge_class($status)
{
    $classes = ['publish' => 'success', 'draft' => 'warning', 'private' => 'secondary'];
    return isset($classes[$status]) ? $classes[$status] : 'secondary';
}

function cp_enqueue_admin_assets()
{
    if (!cp_is_portal_page()) {
        return;
    }

    wp_enqueue_style('cp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_style('cp-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css', [], '1.11.3');
    wp_enqueue_style('cp-style', cp_url('assets/css/style.css'), ['cp-bootstrap'], CP_VERSION);
    wp_enqueue_style('cp-dashboard', cp_url('assets/css/dashboard.css'), ['cp-style'], CP_VERSION);
    wp_enqueue_script('cp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], '5.3.3', true);
    wp_enqueue_script('cp-app', cp_url('assets/js/app.js'), ['cp-bootstrap'], CP_VERSION, true);
    wp_enqueue_script('cp-dashboard', cp_url('assets/js/dashboard.js'), ['cp-app'], CP_VERSION, true);

    if (in_array(cp_current_page(), ['cp-article-create', 'cp-article-edit'], true)) {
        wp_enqueue_media();
        wp_enqueue_style('cp-article-builder', cp_url('assets/css/article-builder.css'), ['cp-style'], CP_VERSION);
        wp_enqueue_script('cp-article-builder', cp_url('assets/js/article-builder.js'), ['cp-app', 'media-editor'], CP_VERSION, true);
    }
}
