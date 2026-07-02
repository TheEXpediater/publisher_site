<?php

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('cp_users', 'cp_users_shortcode');

function cp_users_shortcode()
{
    cp_require_login();

    ob_start();

    cp_render('users');

    return ob_get_clean();
}

function cp_handle_create_user()
{
    if (!isset($_POST['cp_create_user'])) {
        return;
    }

    $name = sanitize_text_field($_POST['name']);
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    if (username_exists($username)) {

        echo '<div class="alert alert-danger">Username already exists.</div>';

        return;
    }

    if (email_exists($email)) {

        echo '<div class="alert alert-danger">Email already exists.</div>';

        return;
    }

    $user_id = wp_create_user(
        $username,
        $password,
        $email
    );

    if (is_wp_error($user_id)) {

        echo '<div class="alert alert-danger">'
            . esc_html($user_id->get_error_message())
            . '</div>';

        return;
    }

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
    ]);

    $user = new WP_User($user_id);

    $user->set_role('subscriber');

    echo '<div class="alert alert-success">
            User created successfully.
          </div>';
}