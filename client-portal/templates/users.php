<?php

if (!defined('ABSPATH')) {
    exit;
}

$users = isset($users) ? $users : [];
$editing_user = isset($editing_user) ? $editing_user : null;
$available_roles = ['administrator' => 'Administrator', 'editor' => 'Editor', 'author' => 'Author'];

?>
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="h4 mb-1"><?php esc_html_e('User Management', 'client-portal'); ?></h2>
        <p class="text-muted mb-0">Create, update, and remove portal users without leaving WordPress admin.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cpUserModal">
        <i class="bi bi-person-plus me-1"></i>
        <?php esc_html_e('Add User', 'client-portal'); ?>
    </button>
</div>

<?php if (!empty($notice)) : ?>
    <div class="alert alert-<?php echo esc_attr($notice['type']); ?>"><?php echo esc_html($notice['message']); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Avatar', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Username', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Email', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Role', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Status', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)) : ?>
                        <?php foreach ($users as $user) : ?>
                            <tr>
                                <td><?php echo get_avatar($user->ID, 40, '', '', ['class' => 'rounded-circle']); ?></td>
                                <td><?php echo esc_html($user->user_login); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td><?php echo esc_html(ucfirst($user->roles[0] ?? 'subscriber')); ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?php echo esc_html($user->user_status === 0 ? __('Active', 'client-portal') : __('Pending', 'client-portal')); ?></span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-users', 'action' => 'edit', 'id' => $user->ID], admin_url('admin.php')), 'cp_edit_user_' . $user->ID)); ?>"><?php esc_html_e('Edit', 'client-portal'); ?></a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-users', 'action' => 'delete', 'id' => $user->ID], admin_url('admin.php')), 'cp_delete_user_' . $user->ID)); ?>"><?php esc_html_e('Delete', 'client-portal'); ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="text-muted py-4 text-center"><?php esc_html_e('No users found.', 'client-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cpUserModal" tabindex="-1" aria-labelledby="cpUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?php wp_nonce_field('cp_users_action', 'cp_users_nonce'); ?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($editing_user ? $editing_user->ID : ''); ?>" />
                <div class="modal-header">
                    <h5 class="modal-title" id="cpUserModalLabel"><?php echo $editing_user ? esc_html__('Edit User', 'client-portal') : esc_html__('Add User', 'client-portal'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="cp-user-name"><?php esc_html_e('Full Name', 'client-portal'); ?></label>
                        <input type="text" class="form-control" id="cp-user-name" name="name" value="<?php echo esc_attr($editing_user ? $editing_user->display_name : ''); ?>" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cp-user-username"><?php esc_html_e('Username', 'client-portal'); ?></label>
                        <input type="text" class="form-control" id="cp-user-username" name="username" value="<?php echo esc_attr($editing_user ? $editing_user->user_login : ''); ?>" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cp-user-email"><?php esc_html_e('Email', 'client-portal'); ?></label>
                        <input type="email" class="form-control" id="cp-user-email" name="email" value="<?php echo esc_attr($editing_user ? $editing_user->user_email : ''); ?>" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cp-user-role"><?php esc_html_e('Role', 'client-portal'); ?></label>
                        <select class="form-select" id="cp-user-role" name="role">
                            <?php foreach ($available_roles as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($editing_user ? ($editing_user->roles[0] ?? 'subscriber') : 'author', $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!$editing_user) : ?>
                        <div class="mb-3">
                            <label class="form-label" for="cp-user-password"><?php esc_html_e('Password', 'client-portal'); ?></label>
                            <input type="password" class="form-control" id="cp-user-password" name="password" required />
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                    <button type="submit" class="btn btn-primary" name="cp_users_submit" value="1"><?php echo $editing_user ? esc_html__('Update User', 'client-portal') : esc_html__('Create User', 'client-portal'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>