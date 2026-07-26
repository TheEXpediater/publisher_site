<?php

if (!defined('ABSPATH')) {
    exit;
}

$error_message = isset($error_message) ? (string) $error_message : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html__('Enterprise1979 Publisher Portal', 'client-portal'); ?></title>
    <?php wp_print_styles(['cp-login']); ?>
</head>
<body class="cp-login-page">
    <main class="cp-login-shell">
        <section class="cp-login-card" aria-labelledby="cp-login-title">
            <div class="cp-login-brand" aria-hidden="true">E79</div>
            <p class="cp-login-eyebrow"><?php esc_html_e('Enterprise1979', 'client-portal'); ?></p>
            <h1 id="cp-login-title"><?php esc_html_e('Publisher Portal', 'client-portal'); ?></h1>
            <p class="cp-login-intro"><?php esc_html_e('Sign in to manage articles and publication content.', 'client-portal'); ?></p>

            <?php if ($error_message) : ?>
                <div class="cp-login-error" role="alert"><?php echo esc_html($error_message); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(home_url('/admin-portal/')); ?>">
                <input type="hidden" name="cp_login_nonce" value="<?php echo esc_attr(wp_create_nonce('cp_portal_login')); ?>">
                <div class="cp-login-field">
                    <label for="cp-login-user"><?php esc_html_e('Username or Email', 'client-portal'); ?></label>
                    <input id="cp-login-user" name="log" type="text" autocomplete="username" required autofocus>
                </div>
                <div class="cp-login-field">
                    <label for="cp-login-password"><?php esc_html_e('Password', 'client-portal'); ?></label>
                    <input id="cp-login-password" name="pwd" type="password" autocomplete="current-password" required>
                </div>
                <label class="cp-login-remember">
                    <input name="rememberme" type="checkbox" value="1">
                    <span><?php esc_html_e('Remember me', 'client-portal'); ?></span>
                </label>
                <button class="cp-login-submit" type="submit"><?php esc_html_e('Log In', 'client-portal'); ?></button>
            </form>

            <p class="cp-login-security"><?php esc_html_e('Protected editorial access.', 'client-portal'); ?></p>
        </section>
    </main>
</body>
</html>
