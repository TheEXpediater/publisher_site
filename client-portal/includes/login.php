<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_add_login_rewrite_rule()
{
    add_rewrite_rule('^admin-portal/?$', 'index.php?cp_admin_portal_login=1', 'top');
}

function cp_register_login_query_var($query_vars)
{
    $query_vars[] = 'cp_admin_portal_login';
    return $query_vars;
}

function cp_activate_plugin()
{
    cp_add_login_rewrite_rule();
    flush_rewrite_rules();
}

function cp_deactivate_plugin()
{
    flush_rewrite_rules();
}

function cp_render_custom_login_page()
{
    if (!get_query_var('cp_admin_portal_login')) {
        return;
    }

    if (is_user_logged_in()) {
        wp_safe_redirect(cp_admin_url('cp-dashboard'));
        exit;
    }

    $error_message = '';
    if ('POST' === strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '')) {
        $submitted_nonce = isset($_POST['cp_login_nonce']) && is_scalar($_POST['cp_login_nonce'])
            ? sanitize_text_field(wp_unslash((string) $_POST['cp_login_nonce']))
            : '';

        if (!$submitted_nonce || !wp_verify_nonce($submitted_nonce, 'cp_portal_login')) {
            $error_message = __('Your login request expired. Please try again.', 'client-portal');
        } else {
            $raw_login = cp_post_value('log');
            $login = is_email($raw_login) ? sanitize_email($raw_login) : sanitize_user($raw_login, true);
            $password = isset($_POST['pwd']) && is_scalar($_POST['pwd']) ? wp_unslash((string) $_POST['pwd']) : '';
            $remember = '1' === cp_post_value('rememberme');

            if ('' !== $login && '' !== $password) {
                $user = wp_signon([
                    'user_login' => $login,
                    'user_password' => $password,
                    'remember' => $remember,
                ], is_ssl());

                if (!is_wp_error($user)) {
                    wp_safe_redirect(cp_admin_url('cp-dashboard'));
                    exit;
                }
            }

            $error_message = __('Invalid username/email or password.', 'client-portal');
        }
    }

    status_header(200);
    nocache_headers();
    wp_enqueue_style('cp-login', cp_url('assets/css/login.css'), [], CP_VERSION);
    cp_render_template('login', ['error_message' => $error_message]);
    exit;
}

add_action('init', 'cp_add_login_rewrite_rule');
add_filter('query_vars', 'cp_register_login_query_var');
add_action('template_redirect', 'cp_render_custom_login_page', 0);
