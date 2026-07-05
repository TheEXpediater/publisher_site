<?php

if (!defined('ABSPATH')) {
    exit;
}

$content = isset($content) ? $content : '';
$page_title = isset($page_title) ? $page_title : __('Publisher Portal', 'client-portal');
?>
<div class="cp-app">
    <div class="cp-mobile-backdrop" data-cp-sidebar-close></div>
    <aside class="cp-sidebar" id="cp-sidebar" aria-label="<?php esc_attr_e('Portal navigation', 'client-portal'); ?>">
        <?php cp_render_template('sidebar'); ?>
    </aside>
    <div class="cp-main">
        <header class="cp-topbar">
            <?php cp_render_template('topbar', ['page_title' => $page_title]); ?>
        </header>
        <main class="cp-content">
            <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped within the page template. ?>
        </main>
    </div>
</div>
