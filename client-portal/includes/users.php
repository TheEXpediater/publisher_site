<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_can_assign_role($role)
{
    return isset(cp_allowed_roles()[$role]) && current_user_can('promote_users');
}

function cp_handle_user_save()
{
    if (!isset($_POST['cp_user_action'])) {
        return null;
    }

    check_admin_referer('cp_save_user', 'cp_user_nonce');
    $user_id = absint(cp_post_value('user_id'));
    $role = sanitize_key(cp_post_value('role', 'author'));

    if (!cp_can_assign_role($role)) {
        return ['type' => 'danger', 'message' => __('You cannot assign the selected role.', 'client-portal')];
    }

    $email = sanitize_email(cp_post_value('email'));
    $display_name = sanitize_text_field(cp_post_value('display_name'));
    $password = cp_post_value('password');

    if ($user_id) {
        cp_require_capability('edit_user', $user_id);
        $target = get_userdata($user_id);
        if (!$target) {
            return ['type' => 'danger', 'message' => __('The selected user no longer exists.', 'client-portal')];
        }
        if (in_array('administrator', (array) $target->roles, true) && !current_user_can('manage_options')) {
            return ['type' => 'danger', 'message' => __('Only administrators can edit administrator accounts.', 'client-portal')];
        }

        $user_data = ['ID' => $user_id, 'display_name' => $display_name, 'user_email' => $email, 'role' => $role];
        if ('' !== $password) {
            $user_data['user_pass'] = $password;
        }
        $result = wp_update_user($user_data);
        $notice_code = 'user-updated';
    } else {
        cp_require_capability('create_users');
        $username = sanitize_user(cp_post_value('username'), true);
        if ('' === $password) {
            return ['type' => 'danger', 'message' => __('A password is required for new users.', 'client-portal')];
        }
        $result = wp_create_user($username, $password, $email);
        if (!is_wp_error($result)) {
            $result = wp_update_user(['ID' => $result, 'display_name' => $display_name, 'role' => $role]);
        }
        $notice_code = 'user-created';
    }

    if (is_wp_error($result)) {
        return ['type' => 'danger', 'message' => $result->get_error_message()];
    }

    cp_redirect('cp-users', ['cp_notice' => $notice_code]);
}

function cp_handle_user_request()
{
    $action = sanitize_key(cp_get_value('action'));
    $user_id = absint(cp_get_value('id'));

    if (!$user_id || !in_array($action, ['edit', 'delete'], true)) {
        return null;
    }

    check_admin_referer('cp_' . $action . '_user_' . $user_id);
    $target = get_userdata($user_id);
    if (!$target) {
        cp_redirect('cp-users');
    }

    if ('edit' === $action) {
        cp_require_capability('edit_user', $user_id);
        return $target;
    }

    cp_require_capability('delete_user', $user_id);
    if ($user_id === get_current_user_id()) {
        cp_redirect('cp-users', ['cp_error' => 'self-delete']);
    }
    if (in_array('administrator', (array) $target->roles, true) && !current_user_can('manage_options')) {
        cp_redirect('cp-users', ['cp_error' => 'admin-delete']);
    }

    require_once ABSPATH . 'wp-admin/includes/user.php';
    $deleted = wp_delete_user($user_id);
    cp_redirect('cp-users', $deleted ? ['cp_notice' => 'user-deleted'] : []);
}

function cp_user_request_error()
{
    $error = sanitize_key(cp_get_value('cp_error'));
    $messages = [
        'self-delete' => __('You cannot delete your own account.', 'client-portal'),
        'admin-delete' => __('Only administrators can delete administrator accounts.', 'client-portal'),
    ];

    return isset($messages[$error]) ? ['type' => 'danger', 'message' => $messages[$error]] : null;
}

function cp_users_page()
{
    cp_require_capability('list_users');
    $notice = cp_handle_user_save();
    $editing_user = cp_handle_user_request();

    cp_render_page('users', [
        'page_title' => __('Users', 'client-portal'),
        'users' => get_users(['orderby' => 'display_name', 'order' => 'ASC']),
        'editing_user' => $editing_user,
        'available_roles' => cp_allowed_roles(),
        'notice' => $notice ?: (cp_user_request_error() ?: cp_request_notice()),
    ]);
}
