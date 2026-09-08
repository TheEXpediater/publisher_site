<?php

if (!defined('ABSPATH')) {
    exit;
}

function cp_can_assign_role($role)
{
    return isset(cp_allowed_roles()[$role]) && current_user_can('promote_users');
}

/**
 * The protected publication-owner account (the designated WordPress-access
 * user, cp_is_wordpress_access_user()) can never be edited, deleted, or
 * otherwise modified from the custom client-portal Users screen - this is a
 * semantic alias over that same canonical check, kept as its own named
 * helper per its use across the Users handlers/templates.
 */
function cp_is_protected_owner_user($user = null)
{
    return cp_is_wordpress_access_user($user);
}

/**
 * User profile photo.
 *
 * Uses a direct file upload (never the WordPress Media Library browser, so
 * article images and other users' photos are never exposed through this
 * picker) validated and stored via wp_handle_upload()/wp_insert_attachment()
 * - the same WordPress-safe upload path the article hero image uses - with
 * the resulting attachment ID kept in user meta.
 */
function cp_profile_image_meta_key()
{
    return '_cp_profile_image_id';
}

function cp_get_user_profile_image_id($user_id)
{
    return absint(get_user_meta(absint($user_id), cp_profile_image_meta_key(), true));
}

/**
 * Clears a user's custom profile photo (user meta + the attachment itself),
 * falling back to normal get_avatar() behavior - the same fallback
 * cp_get_user_avatar_html() already uses whenever no custom image is set.
 */
function cp_remove_user_profile_image($user_id)
{
    $user_id = absint($user_id);
    $attachment_id = cp_get_user_profile_image_id($user_id);

    delete_user_meta($user_id, cp_profile_image_meta_key());

    if ($attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }

    return true;
}

function cp_get_user_avatar_html($user_id, $size = 42)
{
    $user_id = absint($user_id);
    $attachment_id = cp_get_user_profile_image_id($user_id);

    if ($attachment_id) {
        $image = wp_get_attachment_image(
            $attachment_id,
            [$size, $size],
            false,
            ['class' => 'cp-user-avatar-image', 'alt' => '']
        );
        if ($image) {
            return $image;
        }
    }

    $user = get_userdata($user_id);
    return get_avatar($user_id, $size, '', $user ? $user->display_name : '');
}

function cp_profile_image_allowed_mimes($mimes)
{
    return [
        'jpg|jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];
}

function cp_validate_profile_image_upload($file)
{
    if (empty($file) || !is_array($file) || !isset($file['error']) || UPLOAD_ERR_NO_FILE === $file['error']) {
        return null;
    }

    if (UPLOAD_ERR_OK !== $file['error']) {
        return new WP_Error('cp_profile_image_upload_error', __('The profile photo could not be uploaded. Please try again.', 'client-portal'));
    }

    if (absint($file['size']) > 3 * MB_IN_BYTES) {
        return new WP_Error('cp_profile_image_too_large', __('The profile photo must be smaller than 3MB.', 'client-portal'));
    }

    $allowed_mimes = cp_profile_image_allowed_mimes();
    $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
    if (empty($filetype['ext']) || empty($filetype['type']) || !in_array($filetype['type'], $allowed_mimes, true)) {
        return new WP_Error('cp_profile_image_invalid_type', __('Profile photos must be a JPG, PNG, or WebP image.', 'client-portal'));
    }

    if (!function_exists('getimagesize') || !@getimagesize($file['tmp_name'])) {
        return new WP_Error('cp_profile_image_invalid_image', __('The uploaded file is not a valid image.', 'client-portal'));
    }

    return true;
}

/**
 * Upload and attach a new profile photo for a user, replacing any previous
 * one. Returns the new attachment ID, null when no file was submitted (not
 * an error - the user simply didn't change their photo), or a WP_Error.
 */
/**
 * The actual upload/attachment/metadata work below is wrapped in a
 * try/catch(\Throwable) - not just the WP_Error checks already inline -
 * because a PHP fatal here (e.g. a TypeError from an unexpected value
 * reaching wp_generate_attachment_metadata(), or an environment missing an
 * image-processing extension in a way a specific host's WP_Image_Editor
 * implementation doesn't itself catch) would otherwise surface as
 * WordPress's white-screen "critical error" instead of a normal Publisher
 * Portal notice. Since PHP 7, most former fatals are catchable Error/
 * TypeError instances implementing \Throwable, so this reliably converts
 * them into the same graceful WP_Error path as every other failure mode
 * here, without masking or suppressing what actually happened (it's logged
 * via cp_debug_log(), which already redacts sensitive keys).
 */
function cp_handle_profile_image_upload($user_id)
{
    $file = isset($_FILES['profile_image']) ? $_FILES['profile_image'] : null;
    $validation = cp_validate_profile_image_upload($file);

    if (null === $validation || is_wp_error($validation)) {
        return $validation;
    }

    try {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        add_filter('upload_mimes', 'cp_profile_image_allowed_mimes');
        $moved = wp_handle_upload($file, ['test_form' => false]);
        remove_filter('upload_mimes', 'cp_profile_image_allowed_mimes');

        if (empty($moved['file']) || !is_string($moved['file']) || !file_exists($moved['file']) || !empty($moved['error'])) {
            return new WP_Error(
                'cp_profile_image_upload_failed',
                !empty($moved['error']) ? $moved['error'] : __('The profile photo could not be saved.', 'client-portal')
            );
        }

        $attachment_id = wp_insert_attachment(
            [
                'post_mime_type' => $moved['type'],
                'post_title' => sanitize_file_name(pathinfo($moved['file'], PATHINFO_FILENAME)),
                'post_content' => '',
                'post_status' => 'inherit',
            ],
            $moved['file']
        );

        if (is_wp_error($attachment_id) || !$attachment_id) {
            return new WP_Error('cp_profile_image_attachment_failed', __('The profile photo could not be saved.', 'client-portal'));
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $moved['file']);
        wp_update_attachment_metadata($attachment_id, is_array($metadata) ? $metadata : []);

        $previous_attachment_id = cp_get_user_profile_image_id($user_id);
        update_user_meta($user_id, cp_profile_image_meta_key(), $attachment_id);

        if ($previous_attachment_id && $previous_attachment_id !== $attachment_id) {
            wp_delete_attachment($previous_attachment_id, true);
        }

        return $attachment_id;
    } catch (\Throwable $exception) {
        remove_filter('upload_mimes', 'cp_profile_image_allowed_mimes');
        cp_debug_log('Profile photo upload failed with an unexpected error', [
            'user_id' => $user_id,
            'exception' => get_class($exception) . ': ' . $exception->getMessage(),
        ]);

        return new WP_Error('cp_profile_image_unexpected_error', __('The profile photo could not be uploaded due to an unexpected error. Please try again.', 'client-portal'));
    }
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

    $profile_image_file = isset($_FILES['profile_image']) ? $_FILES['profile_image'] : null;
    $profile_image_validation = cp_validate_profile_image_upload($profile_image_file);
    if (is_wp_error($profile_image_validation)) {
        return ['type' => 'danger', 'message' => $profile_image_validation->get_error_message()];
    }
    // A newly chosen file always takes priority over a Remove Photo request
    // submitted in the same save (e.g. the user clicked Remove, then changed
    // their mind and picked a different photo before saving).
    $remove_profile_image = null === $profile_image_validation && '1' === cp_post_value('remove_profile_image', '');

    $email = sanitize_email(cp_post_value('email'));
    $display_name = sanitize_text_field(cp_post_value('display_name'));
    $password = cp_post_value('password');

    if ($user_id) {
        cp_require_capability('edit_user', $user_id);
        $target = get_userdata($user_id);
        if (!$target) {
            return ['type' => 'danger', 'message' => __('The selected user no longer exists.', 'client-portal')];
        }
        if (cp_is_protected_owner_user($target)) {
            return ['type' => 'danger', 'message' => __('This owner account is protected and cannot be modified from the publisher portal.', 'client-portal')];
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

    $saved_user_id = absint($result);
    $photo_uploaded = false;

    if (null !== $profile_image_validation) {
        try {
            $photo_result = cp_handle_profile_image_upload($saved_user_id);
        } catch (\Throwable $exception) {
            cp_debug_log('Profile photo upload failed with an unexpected error', [
                'user_id' => $saved_user_id,
                'exception' => get_class($exception) . ': ' . $exception->getMessage(),
            ]);
            $photo_result = new WP_Error('cp_profile_image_unexpected_error', __('The profile photo could not be uploaded due to an unexpected error. Please try again.', 'client-portal'));
        }
        if (is_wp_error($photo_result)) {
            cp_set_temporary_notice(
                'warning',
                sprintf(
                    /* translators: %s: profile photo error message. */
                    __('The account was saved, but the profile photo could not be uploaded: %s', 'client-portal'),
                    $photo_result->get_error_message()
                )
            );
            cp_redirect('cp-users');
        }
        $photo_uploaded = null !== $photo_result;
    } elseif ($remove_profile_image) {
        try {
            cp_remove_user_profile_image($saved_user_id);
        } catch (\Throwable $exception) {
            cp_debug_log('Profile photo removal failed with an unexpected error', [
                'user_id' => $saved_user_id,
                'exception' => get_class($exception) . ': ' . $exception->getMessage(),
            ]);
            cp_set_temporary_notice('warning', __('The account was saved, but the profile photo could not be removed. Please try again.', 'client-portal'));
            cp_redirect('cp-users');
        }
    }

    cp_redirect('cp-users', ['cp_notice' => $photo_uploaded ? $notice_code . '-with-photo' : $notice_code]);
}

function cp_process_user_admin_actions()
{
    if ('cp-users' !== cp_current_page()) {
        return;
    }

    if ('save' === sanitize_key(cp_post_value('cp_user_action'))) {
        $notice = cp_handle_user_save();
        if (is_array($notice) && !empty($notice['message'])) {
            cp_set_temporary_notice(isset($notice['type']) ? $notice['type'] : 'danger', $notice['message']);
            cp_redirect('cp-users');
        }
    }

    if ('delete' === sanitize_key(cp_get_value('action'))) {
        cp_handle_user_request();
    }
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

    if (cp_is_protected_owner_user($target)) {
        cp_redirect('cp-users', ['cp_error' => 'owner-protected']);
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
        'owner-protected' => __('This owner account is protected and cannot be modified from the publisher portal.', 'client-portal'),
    ];

    return isset($messages[$error]) ? ['type' => 'danger', 'message' => $messages[$error]] : null;
}

function cp_users_page()
{
    cp_require_capability('list_users');
    $editing_user = cp_handle_user_request();

    cp_render_page('users', [
        'page_title' => __('Users', 'client-portal'),
        'users' => get_users(['orderby' => 'display_name', 'order' => 'ASC']),
        'editing_user' => $editing_user,
        'available_roles' => cp_allowed_roles(),
        'notice' => cp_user_request_error() ?: cp_request_notice(),
    ]);
}
