<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_activity_log_table_name()
{
    global $wpdb;

    return $wpdb->prefix . 'cp_activity_log';
}

function cp_install_activity_log_table()
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table_name = cp_activity_log_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL DEFAULT 0,
        user_name varchar(190) NOT NULL DEFAULT '',
        user_email varchar(190) NOT NULL DEFAULT '',
        action varchar(100) NOT NULL,
        object_type varchar(50) NOT NULL DEFAULT '',
        object_id bigint(20) unsigned NOT NULL DEFAULT 0,
        object_label varchar(255) NOT NULL DEFAULT '',
        details longtext NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY created_at (created_at),
        KEY user_id (user_id),
        KEY action (action),
        KEY object_type (object_type)
    ) {$charset_collate};";

    dbDelta($sql);
    update_option('cp_activity_log_db_version', '1.0.0', false);
}

function cp_maybe_install_activity_log_table()
{
    if ('1.0.0' !== get_option('cp_activity_log_db_version')) {
        cp_install_activity_log_table();
    }
}

function cp_activity_log_clean_value($value, $key = '')
{
    if (preg_match('/pass(word)?|pwd|nonce|token|secret/i', (string) $key)) {
        return '[redacted]';
    }

    if (is_bool($value)) {
        return $value ? __('Yes', 'client-portal') : __('No', 'client-portal');
    }

    if (is_array($value)) {
        $clean = [];
        foreach ($value as $item_key => $item_value) {
            $clean_value = cp_activity_log_clean_value($item_value, $item_key);
            if (is_array($clean_value)) {
                $clean_value = implode(', ', array_map('strval', $clean_value));
            }
            $clean[] = (string) $clean_value;
        }
        return implode(', ', array_filter($clean, 'strlen'));
    }

    if (is_object($value)) {
        return get_class($value);
    }

    if (null === $value) {
        return '';
    }

    return sanitize_text_field(wp_strip_all_tags((string) $value));
}

function cp_activity_log_clean_details($details)
{
    $details = is_array($details) ? $details : [];
    $clean = [];

    foreach ($details as $key => $value) {
        $label = sanitize_text_field((string) $key);
        if ('' === $label) {
            continue;
        }

        $clean[$label] = cp_activity_log_clean_value($value, $label);
    }

    return $clean;
}

function cp_activity_log_event($action, $details = [], $object_type = '', $object_id = 0, $object_label = '', $user = null)
{
    global $wpdb;

    $action = sanitize_key($action);
    if ('' === $action) {
        return false;
    }

    if (!$user instanceof WP_User) {
        $user = wp_get_current_user();
    }

    $user_id = $user instanceof WP_User && $user->exists() ? absint($user->ID) : 0;
    $user_name = $user_id ? sanitize_text_field($user->display_name ?: $user->user_login) : __('System', 'client-portal');
    $user_email = $user_id ? sanitize_email($user->user_email) : '';
    $details = cp_activity_log_clean_details($details);

    return false !== $wpdb->insert(
        cp_activity_log_table_name(),
        [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'action' => $action,
            'object_type' => sanitize_key($object_type),
            'object_id' => absint($object_id),
            'object_label' => sanitize_text_field($object_label),
            'details' => !empty($details) ? wp_json_encode($details) : '',
            'created_at' => current_time('mysql', true),
        ],
        ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
    );
}

function cp_activity_log_action_labels()
{
    return [
        'login' => __('Logged in', 'client-portal'),
        'logout' => __('Logged out', 'client-portal'),
        'article_created' => __('Created article', 'client-portal'),
        'article_updated' => __('Updated article', 'client-portal'),
        'article_published' => __('Published article', 'client-portal'),
        'article_status_changed' => __('Changed article status', 'client-portal'),
        'article_trashed' => __('Moved article to trash', 'client-portal'),
        'article_restored' => __('Restored article', 'client-portal'),
        'article_deleted' => __('Deleted article', 'client-portal'),
        'category_created' => __('Created category', 'client-portal'),
        'category_updated' => __('Updated category', 'client-portal'),
        'category_deleted' => __('Deleted category', 'client-portal'),
        'user_created' => __('Created user', 'client-portal'),
        'user_updated' => __('Updated user', 'client-portal'),
        'user_deleted' => __('Deleted user', 'client-portal'),
        'settings_updated' => __('Updated settings', 'client-portal'),
        'homepage_feature_updated' => __('Updated homepage feature', 'client-portal'),
    ];
}

function cp_activity_log_action_label($action)
{
    $labels = cp_activity_log_action_labels();
    $action = sanitize_key($action);

    return isset($labels[$action]) ? $labels[$action] : ucwords(str_replace('_', ' ', $action));
}

function cp_activity_log_action_badge_class($action)
{
    $action = sanitize_key($action);

    if (in_array($action, ['login', 'article_created', 'category_created', 'user_created', 'article_published'], true)) {
        return 'success';
    }

    if (in_array($action, ['logout', 'article_deleted', 'category_deleted', 'user_deleted'], true)) {
        return 'danger';
    }

    if (in_array($action, ['article_trashed', 'article_status_changed'], true)) {
        return 'warning';
    }

    return 'secondary';
}

function cp_activity_log_decode_details($details)
{
    if (!is_string($details) || '' === $details) {
        return [];
    }

    $decoded = json_decode($details, true);
    return is_array($decoded) ? $decoded : [];
}

function cp_activity_log_local_date_to_gmt($date, $end_of_day = false)
{
    $date = sanitize_text_field((string) $date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return '';
    }

    try {
        $time = $end_of_day ? '23:59:59' : '00:00:00';
        $local = new DateTimeImmutable($date . ' ' . $time, wp_timezone());
        return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Exception $exception) {
        return '';
    }
}

function cp_activity_log_filters()
{
    $action = sanitize_key(cp_get_value('log_action'));
    if (!isset(cp_activity_log_action_labels()[$action])) {
        $action = '';
    }

    return [
        'date_from' => sanitize_text_field(cp_get_value('log_date_from')),
        'date_to' => sanitize_text_field(cp_get_value('log_date_to')),
        'action' => $action,
    ];
}

function cp_activity_log_query($filters = [], $page = 1, $per_page = 10)
{
    global $wpdb;

    $filters = wp_parse_args($filters, ['date_from' => '', 'date_to' => '', 'action' => '']);
    $page = max(1, absint($page));
    $per_page = min(100, max(10, absint($per_page)));
    $where = ['1=1'];
    $params = [];

    $date_from = cp_activity_log_local_date_to_gmt($filters['date_from']);
    if ('' !== $date_from) {
        $where[] = 'created_at >= %s';
        $params[] = $date_from;
    }

    $date_to = cp_activity_log_local_date_to_gmt($filters['date_to'], true);
    if ('' !== $date_to) {
        $where[] = 'created_at <= %s';
        $params[] = $date_to;
    }

    if (!empty($filters['action'])) {
        $where[] = 'action = %s';
        $params[] = sanitize_key($filters['action']);
    }

    $table_name = cp_activity_log_table_name();
    $where_sql = implode(' AND ', $where);
    $count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
    if (!empty($params)) {
        $count_sql = $wpdb->prepare($count_sql, $params);
    }

    $total = absint($wpdb->get_var($count_sql));
    $offset = ($page - 1) * $per_page;
    $rows_sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d";
    $row_params = array_merge($params, [$per_page, $offset]);
    $rows_sql = $wpdb->prepare($rows_sql, $row_params);
    $rows = $wpdb->get_results($rows_sql);

    return [
        'rows' => is_array($rows) ? $rows : [],
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'max_pages' => max(1, (int) ceil($total / $per_page)),
    ];
}

function cp_activity_log_login($user_login, $user)
{
    if (!$user instanceof WP_User) {
        return;
    }

    cp_activity_log_event('login', [
        __('Account', 'client-portal') => $user->user_login,
    ], 'user', $user->ID, $user->display_name, $user);
}

function cp_activity_log_logout($user_id = 0)
{
    $user = $user_id ? get_user_by('id', absint($user_id)) : wp_get_current_user();
    if (!$user instanceof WP_User || !$user->exists()) {
        return;
    }

    cp_activity_log_event('logout', [
        __('Account', 'client-portal') => $user->user_login,
    ], 'user', $user->ID, $user->display_name, $user);
}

function cp_activity_log_article_saved($post_id, $post, $update, $post_before)
{
    if (!$post instanceof WP_Post || 'post' !== $post->post_type || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (in_array($post->post_status, ['auto-draft', 'inherit'], true)) {
        return;
    }

    $status_object = get_post_status_object($post->post_status);
    $author = get_user_by('id', absint($post->post_author));
    $action = $update ? 'article_updated' : 'article_created';
    $details = [
        __('Article', 'client-portal') => $post->post_title,
        __('Status', 'client-portal') => $status_object ? $status_object->label : $post->post_status,
        __('Author', 'client-portal') => $author instanceof WP_User ? $author->display_name : sprintf(__('User #%d', 'client-portal'), absint($post->post_author)),
    ];

    if ($update && $post_before instanceof WP_Post) {
        if ($post_before->post_title !== $post->post_title) {
            $details[__('Previous title', 'client-portal')] = $post_before->post_title;
            $details[__('New title', 'client-portal')] = $post->post_title;
        }

        if ($post_before->post_status !== $post->post_status) {
            $old_status_object = get_post_status_object($post_before->post_status);
            $details[__('Previous status', 'client-portal')] = $old_status_object ? $old_status_object->label : $post_before->post_status;

            if ('publish' === $post->post_status) {
                $action = 'article_published';
            } elseif ('trash' === $post->post_status) {
                $action = 'article_trashed';
            } elseif ('trash' === $post_before->post_status) {
                $action = 'article_restored';
            } else {
                $action = 'article_status_changed';
            }
        }

        if ($post_before->post_content !== $post->post_content) {
            $details[__('Content', 'client-portal')] = __('Updated', 'client-portal');
        }

        if ($post_before->post_excerpt !== $post->post_excerpt) {
            $details[__('Excerpt', 'client-portal')] = __('Updated', 'client-portal');
        }

        if (absint($post_before->post_author) !== absint($post->post_author)) {
            $old_author = get_user_by('id', absint($post_before->post_author));
            $details[__('Previous author', 'client-portal')] = $old_author instanceof WP_User ? $old_author->display_name : sprintf(__('User #%d', 'client-portal'), absint($post_before->post_author));
        }
    }

    cp_activity_log_event($action, $details, 'article', $post_id, $post->post_title);
}

function cp_activity_log_article_deleted($post_id, $post = null)
{
    if (!$post instanceof WP_Post || 'post' !== $post->post_type || wp_is_post_revision($post_id)) {
        return;
    }

    cp_activity_log_event('article_deleted', [
        __('Article', 'client-portal') => $post->post_title,
        __('Status', 'client-portal') => $post->post_status,
    ], 'article', $post_id, $post->post_title);
}

function cp_activity_log_category_created($term_id)
{
    $term = get_term($term_id, 'category');
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return;
    }

    cp_activity_log_event('category_created', [
        __('Category', 'client-portal') => $term->name,
        __('Slug', 'client-portal') => $term->slug,
    ], 'category', $term_id, $term->name);
}

function cp_activity_log_category_updated($term_id)
{
    $term = get_term($term_id, 'category');
    if (!$term instanceof WP_Term || is_wp_error($term)) {
        return;
    }

    cp_activity_log_event('category_updated', [
        __('Category', 'client-portal') => $term->name,
        __('Slug', 'client-portal') => $term->slug,
    ], 'category', $term_id, $term->name);
}

function cp_activity_log_category_deleted($term_id, $tt_id = 0, $deleted_term = null)
{
    $term_name = $deleted_term instanceof WP_Term ? $deleted_term->name : sprintf(__('Category #%d', 'client-portal'), absint($term_id));
    $term_slug = $deleted_term instanceof WP_Term ? $deleted_term->slug : '';

    cp_activity_log_event('category_deleted', [
        __('Category', 'client-portal') => $term_name,
        __('Slug', 'client-portal') => $term_slug,
    ], 'category', $term_id, $term_name);
}

function cp_activity_log_user_registered($user_id)
{
    if (!isset($GLOBALS['cp_activity_log_new_users']) || !is_array($GLOBALS['cp_activity_log_new_users'])) {
        $GLOBALS['cp_activity_log_new_users'] = [];
    }

    $GLOBALS['cp_activity_log_new_users'][absint($user_id)] = true;

    if (empty($GLOBALS['cp_activity_log_new_users_shutdown_registered'])) {
        $GLOBALS['cp_activity_log_new_users_shutdown_registered'] = true;
        add_action('shutdown', 'cp_activity_log_flush_new_users', 999);
    }
}

function cp_activity_log_flush_new_users()
{
    $user_ids = isset($GLOBALS['cp_activity_log_new_users']) && is_array($GLOBALS['cp_activity_log_new_users'])
        ? array_keys($GLOBALS['cp_activity_log_new_users'])
        : [];

    foreach ($user_ids as $user_id) {
        $user = get_user_by('id', absint($user_id));
        if (!$user instanceof WP_User) {
            continue;
        }

        cp_activity_log_event('user_created', [
            __('User', 'client-portal') => $user->display_name,
            __('Email', 'client-portal') => $user->user_email,
            __('Role', 'client-portal') => implode(', ', array_map('ucfirst', (array) $user->roles)),
        ], 'user', $user->ID, $user->display_name);
    }
}

function cp_activity_log_user_updated($user_id, $old_user_data = null)
{
    if (!empty($GLOBALS['cp_activity_log_new_users'][absint($user_id)])) {
        return;
    }

    $user = get_user_by('id', absint($user_id));
    if (!$user instanceof WP_User) {
        return;
    }

    $details = [
        __('User', 'client-portal') => $user->display_name,
        __('Email', 'client-portal') => $user->user_email,
        __('Role', 'client-portal') => implode(', ', array_map('ucfirst', (array) $user->roles)),
    ];

    if ($old_user_data instanceof WP_User) {
        if ($old_user_data->display_name !== $user->display_name) {
            $details[__('Previous name', 'client-portal')] = $old_user_data->display_name;
        }
        if ($old_user_data->user_email !== $user->user_email) {
            $details[__('Previous email', 'client-portal')] = $old_user_data->user_email;
        }
        if ((array) $old_user_data->roles !== (array) $user->roles) {
            $details[__('Previous role', 'client-portal')] = implode(', ', array_map('ucfirst', (array) $old_user_data->roles));
        }
    }

    cp_activity_log_event('user_updated', $details, 'user', $user->ID, $user->display_name);
}

function cp_activity_log_user_deleted($user_id, $reassign = null, $user = null)
{
    if (!$user instanceof WP_User) {
        $user = get_user_by('id', absint($user_id));
    }

    $label = $user instanceof WP_User ? $user->display_name : sprintf(__('User #%d', 'client-portal'), absint($user_id));
    $details = [
        __('User', 'client-portal') => $label,
    ];

    if ($user instanceof WP_User) {
        $details[__('Email', 'client-portal')] = $user->user_email;
        $details[__('Role', 'client-portal')] = implode(', ', array_map('ucfirst', (array) $user->roles));
    }

    cp_activity_log_event('user_deleted', $details, 'user', $user_id, $label);
}

function cp_activity_log_settings_updated($old_value, $value)
{
    $old_value = is_array($old_value) ? $old_value : [];
    $value = is_array($value) ? $value : [];
    $changed = [];
    $labels = [
        'portal_title' => __('Portal title', 'client-portal'),
        'default_status' => __('Default article status', 'client-portal'),
        'items_per_page' => __('Items per page', 'client-portal'),
        'allow_authors_publish' => __('Author publishing', 'client-portal'),
    ];

    foreach ($labels as $key => $label) {
        $old = isset($old_value[$key]) ? $old_value[$key] : '';
        $new = isset($value[$key]) ? $value[$key] : '';
        if ((string) $old === (string) $new) {
            continue;
        }

        if ('allow_authors_publish' === $key) {
            $old = empty($old) ? __('Disabled', 'client-portal') : __('Enabled', 'client-portal');
            $new = empty($new) ? __('Disabled', 'client-portal') : __('Enabled', 'client-portal');
        }

        $changed[$label] = sprintf(
            /* translators: 1: Previous value. 2: New value. */
            __('%1$s to %2$s', 'client-portal'),
            cp_activity_log_clean_value($old, $key),
            cp_activity_log_clean_value($new, $key)
        );
    }

    if (!empty($changed)) {
        cp_activity_log_event('settings_updated', $changed, 'settings', 0, __('Portal settings', 'client-portal'));
    }
}

function cp_activity_log_homepage_feature_updated($old_value, $value)
{
    $old_id = absint($old_value);
    $new_id = absint($value);
    if ($old_id === $new_id) {
        return;
    }

    $old_title = $old_id ? get_the_title($old_id) : __('None', 'client-portal');
    $new_title = $new_id ? get_the_title($new_id) : __('None', 'client-portal');

    cp_activity_log_event('homepage_feature_updated', [
        __('Previous article', 'client-portal') => $old_title ?: sprintf(__('Article #%d', 'client-portal'), $old_id),
        __('New article', 'client-portal') => $new_title ?: sprintf(__('Article #%d', 'client-portal'), $new_id),
    ], 'homepage_feature', $new_id, $new_title);
}


function cp_activity_log_option_added($option, $value)
{
    if (CP_HOMEPAGE_FEATURED_ARTICLE_OPTION !== $option) {
        return;
    }

    cp_activity_log_homepage_feature_updated(0, $value);
}

function cp_activity_log_option_deleting($option)
{
    if (CP_HOMEPAGE_FEATURED_ARTICLE_OPTION !== $option) {
        return;
    }

    $old_value = get_option($option, 0);
    cp_activity_log_homepage_feature_updated($old_value, 0);
}


add_action('wp_login', 'cp_activity_log_login', 10, 2);
add_action('wp_logout', 'cp_activity_log_logout', 10, 1);
add_action('wp_after_insert_post', 'cp_activity_log_article_saved', 20, 4);
add_action('before_delete_post', 'cp_activity_log_article_deleted', 10, 2);
add_action('created_category', 'cp_activity_log_category_created', 10, 1);
add_action('edited_category', 'cp_activity_log_category_updated', 10, 1);
add_action('delete_category', 'cp_activity_log_category_deleted', 10, 3);
add_action('user_register', 'cp_activity_log_user_registered', 10, 1);
add_action('profile_update', 'cp_activity_log_user_updated', 10, 2);
add_action('delete_user', 'cp_activity_log_user_deleted', 10, 3);
add_action('update_option_cp_portal_settings', 'cp_activity_log_settings_updated', 10, 2);
add_action('update_option_cp_homepage_featured_article_id', 'cp_activity_log_homepage_feature_updated', 10, 2);
add_action('added_option', 'cp_activity_log_option_added', 10, 2);
add_action('delete_option', 'cp_activity_log_option_deleting', 10, 1);
