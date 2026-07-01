<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Shortcode
|--------------------------------------------------------------------------
*/

add_shortcode('cp_login', 'cp_login_shortcode');

/*
|--------------------------------------------------------------------------
| Login Handler
|--------------------------------------------------------------------------
*/

add_action('init', 'cp_login');

function cp_login()
{
    if (!isset($_POST['cp_login'])) {
        return;
    }

    $credentials = array(
        'user_login'    => sanitize_text_field($_POST['username']),
        'user_password' => $_POST['password'],
        'remember'      => true
    );

    $user = wp_signon($credentials);

    if (!is_wp_error($user)) {

        wp_safe_redirect(cp_dashboard_url());

        exit;

    }

    set_transient(
        'cp_login_error',
        'Invalid username or password.',
        10
    );

    wp_safe_redirect(cp_login_url());

    exit;
}

/*
|--------------------------------------------------------------------------
| Login Shortcode
|--------------------------------------------------------------------------
*/

function cp_login_shortcode()
{
    if (is_user_logged_in()) {

        wp_safe_redirect(cp_dashboard_url());

        exit;

    }

    ob_start();

    include CP_PATH . 'templates/login.php';

    return ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function cp_require_login()
{
    if (!is_user_logged_in()) {

        wp_safe_redirect(cp_login_url());

        exit;

    }
}

function cp_current_user()
{
    return wp_get_current_user();
}

function cp_logout()
{
    wp_logout();

    wp_safe_redirect(cp_login_url());

    exit;
}

/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

function cp_is_super_admin()
{
    return current_user_can('administrator');
}