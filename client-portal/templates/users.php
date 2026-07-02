<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2>Users</h2>

        <button
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#addUserModal">

            Add User

        </button>

    </div>

    <?php cp_handle_create_user(); ?>

    <table class="table table-bordered">

        <thead>

            <tr>

                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>

            </tr>

        </thead>

        <tbody>

            <?php

            $users = get_users();

            foreach ($users as $user) :

            ?>

                <tr>

                    <td><?php echo esc_html($user->display_name); ?></td>

                    <td><?php echo esc_html($user->user_login); ?></td>

                    <td><?php echo esc_html($user->user_email); ?></td>

                    <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<div class="modal fade" id="addUserModal">

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5>Add User</h5>

                </div>

                <div class="modal-body">

                    <input
                        class="form-control mb-3"
                        name="name"
                        placeholder="Full Name"
                        required>

                    <input
                        class="form-control mb-3"
                        name="username"
                        placeholder="Username"
                        required>

                    <input
                        class="form-control mb-3"
                        type="email"
                        name="email"
                        placeholder="Email"
                        required>

                    <input
                        class="form-control mb-3"
                        type="password"
                        name="password"
                        placeholder="Password"
                        required>

                </div>

                <div class="modal-footer">

                    <button
                        class="btn btn-success"
                        name="cp_create_user">

                        Create

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>