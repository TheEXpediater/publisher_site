<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_register_login_route()
{
    add_rewrite_rule('^publisher-login/?$', 'index.php?cp_publisher_login=1', 'top');
}

function cp_register_login_query_var($query_vars)
{
    $query_vars[] = 'cp_publisher_login';
    return $query_vars;
}

function cp_activate_plugin()
{
    cp_register_login_route();
    cp_install_activity_log_table();
    flush_rewrite_rules();
}

function cp_deactivate_plugin()
{
    flush_rewrite_rules();
}

function cp_login_url($redirect_to = '')
{
    $url = home_url('/publisher-login/', cp_login_url_scheme());
    $redirect_to = cp_validate_login_redirect($redirect_to);

    if ('' !== $redirect_to && cp_admin_url('cp-dashboard') !== $redirect_to) {
        $url = add_query_arg('redirect_to', $redirect_to, $url);
    }

    return $url;
}

/**
 * Return the portal-branded password recovery URL.
 * The actual reset token generation and password reset remain handled by
 * WordPress core.
 */
function cp_lostpassword_url($redirect_to = '')
{
    $redirect_to = cp_validate_login_redirect($redirect_to);
    $url = add_query_arg('action', 'lostpassword', cp_login_url($redirect_to));

    return $url;
}

/**
 * Process a password recovery request without exposing whether an account
 * exists for the submitted username or email address.
 */
function cp_process_custom_lostpassword()
{
    $result = [
        'error' => '',
        'success' => false,
    ];

    if ('POST' !== strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '')) {
        return $result;
    }

    $submitted_nonce = isset($_POST['cp_lostpassword_nonce']) && is_scalar($_POST['cp_lostpassword_nonce'])
        ? sanitize_text_field(wp_unslash((string) $_POST['cp_lostpassword_nonce']))
        : '';

    if (!$submitted_nonce || !wp_verify_nonce($submitted_nonce, 'cp_portal_lostpassword')) {
        $result['error'] = __('Your password reset request expired. Please try again.', 'client-portal');
        return $result;
    }

    $user_login = isset($_POST['user_login']) && is_scalar($_POST['user_login'])
        ? sanitize_text_field(wp_unslash((string) $_POST['user_login']))
        : '';

    if ('' === $user_login) {
        $result['error'] = __('Enter your username or email address.', 'client-portal');
        return $result;
    }

    $reset_result = retrieve_password($user_login);

    if (is_wp_error($reset_result)) {
        $non_enumerating_errors = [
            'invalidcombo',
            'invalid_email',
            'invalid_username',
        ];

        if (!in_array($reset_result->get_error_code(), $non_enumerating_errors, true)) {
            $result['error'] = __('The reset email could not be sent right now. Please try again later.', 'client-portal');
            return $result;
        }
    }

    // Use the same confirmation for valid and unknown accounts to reduce
    // username/email enumeration from the public recovery form.
    $result['success'] = true;
    return $result;
}

function cp_login_url_scheme()
{
    return cp_login_uses_secure_cookie() ? 'https' : null;
}

/**
 * Determine whether portal authentication cookies must use the Secure flag.
 * This also covers WordPress installations running behind an HTTPS proxy where
 * is_ssl() alone may not reflect the public site URL.
 */
function cp_login_uses_secure_cookie()
{
    if (is_ssl()) {
        return true;
    }

    if (function_exists('wp_is_using_https') && wp_is_using_https()) {
        return true;
    }

    return 'https' === wp_parse_url(home_url('/'), PHP_URL_SCHEME);
}

function cp_validate_login_redirect($redirect_to = '')
{
    $default = cp_admin_url('cp-dashboard');
    $redirect_to = is_scalar($redirect_to) ? trim((string) $redirect_to) : '';

    if ('' === $redirect_to) {
        return $default;
    }

    return wp_validate_redirect(esc_url_raw($redirect_to), $default);
}

function cp_redirect_targets_portal($redirect_to)
{
    $redirect_to = is_scalar($redirect_to) ? html_entity_decode((string) $redirect_to, ENT_QUOTES) : '';
    if ('' === $redirect_to) {
        return false;
    }

    $parts = wp_parse_url($redirect_to);
    if (empty($parts['query'])) {
        return false;
    }

    parse_str($parts['query'], $query_args);
    $page = isset($query_args['page']) ? sanitize_key($query_args['page']) : '';

    return in_array($page, cp_portal_pages(), true);
}

function cp_portal_login_url_filter($login_url, $redirect_to, $force_reauth)
{
    if (!cp_redirect_targets_portal($redirect_to)) {
        return $login_url;
    }

    $url = cp_login_url($redirect_to);
    if ($force_reauth) {
        $url = add_query_arg('reauth', '1', $url);
    }

    return $url;
}

function cp_process_custom_login($redirect_to)
{
    $error_message = '';

    if ('POST' !== strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '')) {
        return $error_message;
    }

    $submitted_nonce = isset($_POST['cp_login_nonce']) && is_scalar($_POST['cp_login_nonce'])
        ? sanitize_text_field(wp_unslash((string) $_POST['cp_login_nonce']))
        : '';

    if (!$submitted_nonce || !wp_verify_nonce($submitted_nonce, 'cp_portal_login')) {
        return __('Your login request expired. Please try again.', 'client-portal');
    }

    $login = isset($_POST['log']) && is_scalar($_POST['log'])
        ? sanitize_text_field(wp_unslash((string) $_POST['log']))
        : '';
    $password = isset($_POST['pwd']) && is_scalar($_POST['pwd'])
        ? wp_unslash((string) $_POST['pwd'])
        : '';
    $remember = isset($_POST['rememberme']) && is_scalar($_POST['rememberme']) && '1' === sanitize_text_field(wp_unslash((string) $_POST['rememberme']));

    if ('' === $login || '' === $password) {
        return __('The username, email address, or password is incorrect.', 'client-portal');
    }

    $user = wp_signon(
        [
            'user_login' => $login,
            'user_password' => $password,
            'remember' => $remember,
        ],
        cp_login_uses_secure_cookie()
    );

    if (is_wp_error($user)) {
        return __('The username, email address, or password is incorrect.', 'client-portal');
    }

    wp_safe_redirect($redirect_to);
    exit;
}

function cp_render_custom_login()
{
    if (!get_query_var('cp_publisher_login')) {
        return;
    }

    $redirect_to = isset($_REQUEST['redirect_to']) && is_scalar($_REQUEST['redirect_to'])
        ? wp_unslash((string) $_REQUEST['redirect_to'])
        : '';
    $redirect_to = cp_validate_login_redirect($redirect_to);

    if (!is_ssl() && 'https' === cp_login_url_scheme()) {
        wp_safe_redirect(cp_login_url($redirect_to), 301);
        exit;
    }

    if (is_user_logged_in()) {
        wp_safe_redirect($redirect_to);
        exit;
    }

    $action = isset($_REQUEST['action']) && is_scalar($_REQUEST['action'])
        ? sanitize_key(wp_unslash((string) $_REQUEST['action']))
        : '';

    if ('lostpassword' === $action) {
        $recovery = cp_process_custom_lostpassword();

        status_header(200);
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);

        wp_enqueue_style('cp-custom-login', cp_url('assets/css/custom-login.css'), [], CP_VERSION);

        cp_render_template(
            'lost-password',
            [
                'error_message' => isset($recovery['error']) ? (string) $recovery['error'] : '',
                'success' => !empty($recovery['success']),
                'redirect_to' => $redirect_to,
            ]
        );
        exit;
    }

    $remember_checked = isset($_POST['rememberme'])
        && is_scalar($_POST['rememberme'])
        && '1' === sanitize_text_field(wp_unslash((string) $_POST['rememberme']));
    $error_message = cp_process_custom_login($redirect_to);

    status_header(200);
    nocache_headers();
    header('X-Robots-Tag: noindex, nofollow', true);

    wp_enqueue_style('cp-custom-login', cp_url('assets/css/custom-login.css'), [], CP_VERSION);
    wp_enqueue_script('cp-custom-login', cp_url('assets/js/custom-login.js'), [], CP_VERSION, true);

    cp_render_template(
        'custom-login',
        [
            'error_message' => $error_message,
            'redirect_to' => $redirect_to,
            'remember_checked' => $remember_checked,
        ]
    );
    exit;
}

function cp_enqueue_native_login_branding()
{
    wp_enqueue_style('cp-custom-login', cp_url('assets/css/custom-login.css'), [], CP_VERSION);
}

function cp_native_login_header_url()
{
    return home_url('/');
}

function cp_native_login_header_text()
{
    return cp_get_publication_name();
}

function cp_native_login_message($message)
{
    if (!is_string($message) || '' === $message) {
        return $message;
    }

    $publication_name = cp_get_publication_name();

    return str_replace(
        [
            'WordPress account',
            'WordPress login',
            'WordPress password',
            'WordPress user',
            'WordPress dashboard',
            'Powered by WordPress',
        ],
        [
            'publication account',
            'publication login',
            'account password',
            'portal account',
            'editorial dashboard',
            $publication_name,
        ],
        $message
    );
}

function cp_native_login_errors($errors)
{
    if (!is_string($errors) || '' === $errors) {
        return $errors;
    }

    return cp_native_login_message($errors);
}

add_action('init', 'cp_register_login_route');
add_filter('query_vars', 'cp_register_login_query_var');
add_filter('login_url', 'cp_portal_login_url_filter', 10, 3);
add_action('template_redirect', 'cp_render_custom_login', 0);
add_action('login_enqueue_scripts', 'cp_enqueue_native_login_branding');
add_filter('login_headerurl', 'cp_native_login_header_url');
add_filter('login_headertext', 'cp_native_login_header_text');
add_filter('login_message', 'cp_native_login_message');
add_filter('login_errors', 'cp_native_login_errors');
