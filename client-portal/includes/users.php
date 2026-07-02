<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_users_page()
{
    if (!cp_is_developer() && !current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'client-portal'));
    }

    $notice = null;
    if (isset($_POST['cp_users_submit']) && isset($_POST['cp_users_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cp_users_nonce'])), 'cp_users_action')) {
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $name = sanitize_text_field(wp_unslash($_POST['name']));
        $username = sanitize_user(wp_unslash($_POST['username']));
        $email = sanitize_email(wp_unslash($_POST['email']));
        $role = sanitize_text_field(wp_unslash($_POST['role']));
        $password = isset($_POST['password']) ? wp_unslash($_POST['password']) : '';

        if ($user_id) {
            $update = wp_update_user([
                'ID' => $user_id,
                'display_name' => $name,
                'user_login' => $username,
                'user_email' => $email,
            ]);

            if (!is_wp_error($update)) {
                $user = new WP_User($user_id);
                $user->set_role($role);
                $notice = ['type' => 'success', 'message' => __('User updated successfully.', 'client-portal')];
            } else {
                $notice = ['type' => 'danger', 'message' => $update->get_error_message()];
            }
        } else {
            if (username_exists($username) || email_exists($email)) {
                $notice = ['type' => 'danger', 'message' => __('A user with that username or email already exists.', 'client-portal')];
            } else {
                $created = wp_create_user($username, $password, $email);
                if (!is_wp_error($created)) {
                    wp_update_user([
                        'ID' => $created,
                        'display_name' => $name,
                    ]);
                    $user = new WP_User($created);
                    $user->set_role($role);
                    $notice = ['type' => 'success', 'message' => __('User created successfully.', 'client-portal')];
                } else {
                    $notice = ['type' => 'danger', 'message' => $created->get_error_message()];
                }
            }
        }
    }

    $editing_user = null;
    if (isset($_GET['action'], $_GET['id']) && 'edit' === sanitize_text_field(wp_unslash($_GET['action'])) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_edit_user_' . absint($_GET['id']))) {
        $editing_user = get_user_by('id', absint($_GET['id']));
    } elseif (isset($_GET['action'], $_GET['id']) && 'delete' === sanitize_text_field(wp_unslash($_GET['action'])) && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cp_delete_user_' . absint($_GET['id']))) {
        wp_delete_user(absint($_GET['id']));
    }

    $users = get_users(['fields' => ['ID', 'display_name', 'user_login', 'user_email', 'roles', 'user_status']]);

    cp_render_page('users', [
        'page_title' => __('Users', 'client-portal'),
        'users' => $users,
        'editing_user' => $editing_user,
        'notice' => $notice,
    ]);
}