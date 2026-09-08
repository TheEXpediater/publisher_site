<?php

if (!defined('ABSPATH')) {
    exit;
}

$error_message = isset($error_message) ? (string) $error_message : '';
$locked_until = isset($locked_until) ? absint($locked_until) : 0;
$redirect_to = isset($redirect_to) ? (string) $redirect_to : cp_admin_url('cp-dashboard');
$remember_checked = !empty($remember_checked);
$login_url = cp_login_url($redirect_to);
$publication_name = cp_get_publication_name();
$publication_initial = strtoupper(substr($publication_name, 0, 1));
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html(sprintf(__('%s Publisher Portal Login', 'client-portal'), $publication_name)); ?></title>
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

            <p class="cp-login-eyebrow"><?php esc_html_e('Admin Portal', 'client-portal'); ?></p>
            <h1 id="cp-login-title"><?php esc_html_e('Sign in to continue', 'client-portal'); ?></h1>
            <p class="cp-login-intro"><?php esc_html_e('Use your publication account to manage articles and publication content.', 'client-portal'); ?></p>

            <?php if ($error_message) : ?>
                <div class="cp-login-error" role="alert" <?php if ($locked_until) : ?>data-cp-lockout-until="<?php echo esc_attr($locked_until); ?>" data-cp-lockout-message="<?php echo esc_attr($error_message); ?>"<?php endif; ?>>
                    <span aria-hidden="true">!</span>
                    <p data-cp-lockout-text><?php echo esc_html($error_message); ?></p>
                </div>
            <?php endif; ?>

            <form class="cp-login-form" method="post" action="<?php echo esc_url($login_url); ?>" novalidate>
                <?php wp_nonce_field('cp_portal_login', 'cp_login_nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">

                <div class="cp-login-field">
                    <label for="cp-login-user"><?php esc_html_e('Username or email address', 'client-portal'); ?></label>
                    <div class="cp-login-input-wrap">
                        <span class="cp-login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm0 2c-3.31 0-6 1.79-6 4v1h12v-1c0-2.21-2.69-4-6-4Z"/></svg>
                        </span>
                        <input id="cp-login-user" name="log" type="text" autocomplete="username" placeholder="<?php esc_attr_e('Enter username or email', 'client-portal'); ?>" required autofocus>
                    </div>
                </div>

                <div class="cp-login-field">
                    <label for="cp-login-password"><?php esc_html_e('Password', 'client-portal'); ?></label>
                    <div class="cp-login-input-wrap">
                        <span class="cp-login-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2h-4V7Z"/></svg>
                        </span>
                        <input id="cp-login-password" name="pwd" type="password" autocomplete="current-password" placeholder="<?php esc_attr_e('Enter password', 'client-portal'); ?>" required>
                        <button class="cp-login-password-toggle" type="button" data-cp-toggle-password aria-controls="cp-login-password" aria-label="<?php esc_attr_e('Show password', 'client-portal'); ?>" data-show-label="<?php echo esc_attr__('Show password', 'client-portal'); ?>" data-hide-label="<?php echo esc_attr__('Hide password', 'client-portal'); ?>">
                            <svg class="cp-login-eye" viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 5c5 0 8.5 4.3 9.6 5.9a2 2 0 0 1 0 2.2C20.5 14.7 17 19 12 19s-8.5-4.3-9.6-5.9a2 2 0 0 1 0-2.2C3.5 9.3 7 5 12 5Zm0 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"/></svg>
                        </button>
                    </div>
                </div>

                <div class="cp-login-row">
                    <label class="cp-login-remember" for="cp-login-rememberme">
                        <input id="cp-login-rememberme" name="rememberme" type="checkbox" value="1" <?php checked($remember_checked); ?>>
                        <span><?php esc_html_e('Remember me', 'client-portal'); ?></span>
                    </label>
                    <a href="<?php echo esc_url(cp_lostpassword_url($redirect_to)); ?>"><?php esc_html_e('Forgot password?', 'client-portal'); ?></a>
                </div>

                <button class="cp-login-submit" type="submit" <?php disabled($locked_until > 0); ?>><?php esc_html_e('Sign In', 'client-portal'); ?></button>
            </form>

            <p class="cp-login-security"><?php esc_html_e('Protected editorial access.', 'client-portal'); ?></p>
        </section>
    </main>
    <?php wp_print_footer_scripts(); ?>
</body>
</html>
