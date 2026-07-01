<?php

$error = get_transient('cp_login_error');

if ($error) {
    delete_transient('cp_login_error');
}

?>

<div class="container vh-100 d-flex justify-content-center align-items-center">

    <div class="card shadow" style="width:400px;">

        <div class="card-body p-4">

            <div class="text-center mb-4">

                <h2>Enterprise1979</h2>

                <p class="text-muted">
                    Publisher Portal
                </p>

            </div>

            <?php if ($error) : ?>

                <div class="alert alert-danger">

                    <?php echo esc_html($error); ?>

                </div>

            <?php endif; ?>

            <form method="POST" id="cpLoginForm">

                <div class="mb-3">

                    <label class="form-label">

                        Username

                    </label>

                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        required>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Password

                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required>

                </div>

                <button
                    type="submit"
                    name="cp_login"
                    id="loginBtn"
                    class="btn btn-primary w-100">

                    Login

                </button>

            </form>

            <div
                id="loginLoading"
                class="text-center mt-3"
                style="display:none;">

                <div class="spinner-border text-primary"></div>

                <p class="mt-2">

                    Logging in...

                </p>

            </div>

        </div>

    </div>

</div>