<?php

if (!defined('ABSPATH')) {
    exit;
}

$page_title = isset($page_title) ? $page_title : __('Publisher Portal', 'client-portal');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($page_title); ?></title>
    <?php wp_head(); ?>
</head>
<body class="cp-admin-body">
<div class="cp-admin-shell">
    <aside class="cp-sidebar">
        <?php include CP_PATH . 'templates/sidebar.php'; ?>
    </aside>
    <main class="cp-main">
        <header class="cp-topbar">
            <?php include CP_PATH . 'templates/topbar.php'; ?>
        </header>
        <section class="cp-content">
            <?php echo $content; ?>
        </section>
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>