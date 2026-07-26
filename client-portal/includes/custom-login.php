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

function cp_login_url_scheme()
{
    return is_ssl() || (function_exists('wp_is_using_https') && wp_is_using_https()) ? 'https' : null;
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
        is_ssl()
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
