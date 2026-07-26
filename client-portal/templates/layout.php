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
    <div class="modal fade cp-modal cp-action-confirm-modal" id="cp-action-confirm-modal" tabindex="-1" aria-labelledby="cp-action-confirm-title" aria-describedby="cp-action-confirm-message" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <span class="cp-confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle-fill"></i></span>
                        <h2 class="modal-title" id="cp-action-confirm-title"><?php esc_html_e('Confirm action', 'client-portal'); ?></h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="cp-action-confirm-message"><?php esc_html_e('Are you sure you want to continue?', 'client-portal'); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                    <button type="button" class="btn btn-primary" data-cp-confirm-modal-button><?php esc_html_e('Confirm', 'client-portal'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
