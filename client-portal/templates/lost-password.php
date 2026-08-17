<?php

if (!defined('ABSPATH')) {
    exit;
}

$error_message = isset($error_message) ? (string) $error_message : '';
$success = !empty($success);
$redirect_to = isset($redirect_to) ? (string) $redirect_to : cp_admin_url('cp-dashboard');
$login_url = cp_login_url($redirect_to);
$recovery_url = cp_lostpassword_url($redirect_to);
$publication_name = cp_get_publication_name();
$publication_initial = strtoupper(substr($publication_name, 0, 1));
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html(sprintf(__('Reset Password | %s Publisher Portal', 'client-portal'), $publication_name)); ?></title>
    <?php wp_print_styles(['cp-custom-login']); ?>
</head>
<body class="cp-login-page">
    <main class="cp-login-shell">
        <section class="cp-login-card" aria-labelledby="cp-login-title">
            <div class="cp-login-brand">
                <div class="cp-login-mark" aria-hidden="true"><?php echo esc_html($publication_initial); ?></div>
                <div>
                    <strong><?php echo esc_html($publication_name); ?></strong>
                    <span><?php esc_html_e('Publisher Portal', 'client-portal'); ?></span>
                </div>
            </div>

            <p class="cp-login-eyebrow"><?php esc_html_e('Account Recovery', 'client-portal'); ?></p>
            <h1 id="cp-login-title"><?php esc_html_e('Forgot your password?', 'client-portal'); ?></h1>
            <p class="cp-login-intro"><?php esc_html_e('Enter your username or email address. If an account matches, WordPress will email a secure password reset link.', 'client-portal'); ?></p>

            <?php if ($error_message) : ?>
                <div class="cp-login-error" role="alert">
                    <span aria-hidden="true">!</span>
                    <p><?php echo esc_html($error_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success) : ?>
                <div class="cp-login-success" role="status">
                    <span aria-hidden="true">✓</span>
                    <p><?php esc_html_e('If an account matches those details, a password reset email has been sent. Check your inbox and spam folder.', 'client-portal'); ?></p>
                </div>
            <?php else : ?>
                <form class="cp-login-form" method="post" action="<?php echo esc_url($recovery_url); ?>" novalidate>
                    <?php wp_nonce_field('cp_portal_lostpassword', 'cp_lostpassword_nonce'); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                    <div class="cp-login-field">
                        <label for="cp-recovery-user"><?php esc_html_e('Username or email address', 'client-portal'); ?></label>
                        <div class="cp-login-input-wrap">
                            <span class="cp-login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4 1.79-4 4 1.79 4 4 4Zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4Z"/></svg>
                            </span>
                            <input id="cp-recovery-user" name="user_login" type="text" autocomplete="username" placeholder="<?php esc_attr_e('Enter username or email', 'client-portal'); ?>" required autofocus>
                        </div>
                    </div>

                    <button class="cp-login-submit" type="submit"><?php esc_html_e('Send Reset Link', 'client-portal'); ?></button>
                </form>
            <?php endif; ?>

            <p class="cp-login-back"><a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Back to sign in', 'client-portal'); ?></a></p>
            <p class="cp-login-security"><?php esc_html_e('Password reset links are generated and validated by WordPress.', 'client-portal'); ?></p>
        </section>
    </main>
</body>
</html>
