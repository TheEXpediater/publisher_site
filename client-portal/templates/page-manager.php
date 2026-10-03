<?php

if (!defined('ABSPATH')) {
    exit;
}

$pages = isset($pages) && is_array($pages) ? $pages : [];
$about_nav_pages = isset($about_nav_pages) && is_array($about_nav_pages) ? $about_nav_pages : [];
$about_us_active = !empty($about_us_active);

$about_nav_active_pages = [];
$about_nav_inactive_pages = [];
foreach ($about_nav_pages as $about_nav_page) {
    if (!$about_nav_page instanceof WP_Post) {
        continue;
    }
    $entry = [
        'id' => $about_nav_page->ID,
        'title' => $about_nav_page->post_title,
    ];
    if ('publish' === $about_nav_page->post_status) {
        $about_nav_active_pages[] = $entry;
    } else {
        $about_nav_inactive_pages[] = $entry;
    }
}

$about_nav_editor_data = wp_json_encode([
    'activePages' => $about_nav_active_pages,
    'inactivePages' => $about_nav_inactive_pages,
    'aboutUsActive' => $about_us_active,
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('cp_save_about_nav_order'),
]);
?>
<div class="cp-page-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Publication Sections', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Pages', 'client-portal'); ?></h2>
        <p><?php esc_html_e('Manage standalone institutional pages such as About Us, Staff, and Join the Publication.', 'client-portal'); ?></p>
    </div>
    <div class="cp-page-heading-actions">
        <a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-categories')); ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <?php esc_html_e('Back to Categories', 'client-portal'); ?>
        </a>
        <a class="btn btn-primary" href="<?php echo esc_url(cp_admin_url('cp-page-create')); ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <?php esc_html_e('Add Page', 'client-portal'); ?>
        </a>
    </div>
</div>

<?php cp_render_admin_notice($notice); ?>

<section class="cp-card cp-table-card">
    <div class="cp-card-header">
        <div>
            <h3><?php esc_html_e('Managed Pages', 'client-portal'); ?></h3>
            <p><?php echo esc_html(sprintf(_n('%s page', '%s pages', count($pages), 'client-portal'), number_format_i18n(count($pages)))); ?></p>
        </div>
        <button
            type="button"
            class="btn btn-outline-secondary cp-icon-only-btn"
            data-bs-toggle="modal"
            data-bs-target="#cp-about-nav-modal"
            aria-label="<?php esc_attr_e('About Us Navigation Settings', 'client-portal'); ?>"
            title="<?php esc_attr_e('About Us Navigation Settings', 'client-portal'); ?>"
        >
            <i class="bi bi-gear-fill" aria-hidden="true"></i>
        </button>
    </div>

    <div class="table-responsive">
        <table class="cp-table table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Page', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Status', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Link', 'client-portal'); ?></th>
                    <th><?php esc_html_e('About Us Nav', 'client-portal'); ?></th>
                    <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pages) : ?>
                    <?php foreach ($pages as $page_post) : ?>
                        <?php
                        $is_home = cp_page_is_home($page_post);
                        $is_about_parent = cp_page_is_about_us_parent($page_post);
                        $is_protected = cp_page_is_protected($page_post);
                        $in_about_nav = '1' === get_post_meta($page_post->ID, CP_PAGE_ABOUT_NAV_META, true);
                        $permalink = get_permalink($page_post);
                        $status_active = 'publish' === $page_post->post_status;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($page_post->post_title); ?></strong>
                                <?php if ($is_home) : ?>
                                    <span class="cp-badge cp-badge-secondary"><i class="bi bi-house-fill" aria-hidden="true"></i> <?php esc_html_e('Home', 'client-portal'); ?></span>
                                <?php elseif ($is_about_parent) : ?>
                                    <span class="cp-badge cp-badge-secondary"><i class="bi bi-signpost-2-fill" aria-hidden="true"></i> <?php esc_html_e('About Us Parent', 'client-portal'); ?></span>
                                <?php endif; ?>
                                <br><small class="cp-table-subtitle"><?php echo esc_html('/' . $page_post->post_name . '/'); ?></small>
                            </td>
                            <td>
                                <span class="cp-badge cp-badge-<?php echo $status_active ? 'success' : 'secondary'; ?>">
                                    <?php echo $status_active ? esc_html__('Active', 'client-portal') : esc_html__('Inactive', 'client-portal'); ?>
                                </span>
                            </td>
                            <td>
                                <a class="cp-page-link" href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr($permalink); ?>">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> <?php esc_html_e('View', 'client-portal'); ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($is_home || $is_about_parent) : ?>
                                    <span class="cp-table-subtitle">&mdash;</span>
                                <?php elseif ($in_about_nav) : ?>
                                    <span class="cp-badge cp-badge-success"><?php esc_html_e('In dropdown', 'client-portal'); ?></span>
                                    <div class="cp-actions cp-actions-tight">
                                        <a class="cp-icon-button" href="<?php echo esc_url(wp_nonce_url(cp_admin_url('cp-pages', ['move_about_nav' => 1, 'id' => $page_post->ID, 'direction' => 'up']), 'cp_move_about_nav_' . $page_post->ID)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Move %s up', 'client-portal'), $page_post->post_title)); ?>"><i class="bi bi-arrow-up" aria-hidden="true"></i></a>
                                        <a class="cp-icon-button" href="<?php echo esc_url(wp_nonce_url(cp_admin_url('cp-pages', ['move_about_nav' => 1, 'id' => $page_post->ID, 'direction' => 'down']), 'cp_move_about_nav_' . $page_post->ID)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Move %s down', 'client-portal'), $page_post->post_title)); ?>"><i class="bi bi-arrow-down" aria-hidden="true"></i></a>
                                    </div>
                                <?php else : ?>
                                    <span class="cp-table-subtitle"><?php esc_html_e('Not shown', 'client-portal'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="cp-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url(cp_admin_url('cp-page-edit', ['id' => $page_post->ID])); ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span><?php esc_html_e('Edit', 'client-portal'); ?></span>
                                    </a>
                                    <?php if (!$is_protected) : ?>
                                        <a
                                            class="btn btn-sm btn-outline-danger"
                                            data-cp-confirm="<?php echo esc_attr__('Move this page to Trash?', 'client-portal'); ?>"
                                            data-cp-confirm-title="<?php echo esc_attr__('Delete Page', 'client-portal'); ?>"
                                            data-cp-confirm-label="<?php echo esc_attr__('Delete Page', 'client-portal'); ?>"
                                            data-cp-confirm-tone="danger"
                                            href="<?php echo esc_url(wp_nonce_url(cp_admin_url('cp-pages', ['action' => 'delete', 'id' => $page_post->ID]), 'cp_delete_page_' . $page_post->ID)); ?>"
                                        >
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                            <span><?php esc_html_e('Delete', 'client-portal'); ?></span>
                                        </a>
                                    <?php else : ?>
                                        <span class="cp-badge cp-badge-secondary" title="<?php esc_attr_e('This page is protected and cannot be deleted from the Publisher Portal.', 'client-portal'); ?>"><i class="bi bi-shield-lock" aria-hidden="true"></i> <?php esc_html_e('Protected', 'client-portal'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="cp-empty-state"><?php esc_html_e('No pages found.', 'client-portal'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div
    class="modal fade cp-modal cp-about-nav-modal"
    id="cp-about-nav-modal"
    tabindex="-1"
    aria-labelledby="cp-about-nav-modal-title"
    aria-hidden="true"
    data-cp-about-nav-editor="<?php echo esc_attr($about_nav_editor_data); ?>"
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="cp-eyebrow mb-1"><?php esc_html_e('Publication Sections', 'client-portal'); ?></p>
                    <h2 class="modal-title" id="cp-about-nav-modal-title"><?php esc_html_e('About Us Navigation', 'client-portal'); ?></h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button>
            </div>
            <div class="modal-body">
                <div class="cp-menu-editor-feedback" data-cp-about-nav-feedback hidden role="status" aria-live="polite"></div>

                <?php if (!$about_us_active) : ?>
                    <div class="cp-menu-editor-feedback cp-menu-editor-feedback-danger" role="status">
                        <?php esc_html_e('About Us is currently inactive. These links are not visible in the public navigation.', 'client-portal'); ?>
                    </div>
                <?php endif; ?>

                <p class="cp-menu-editor-body-help"><?php esc_html_e('Arrange the Pages shown under About Us.', 'client-portal'); ?></p>

                <h3 class="cp-menu-editor-section-title"><?php esc_html_e('About Us Dropdown Preview', 'client-portal'); ?></h3>
                <div class="cp-menu-editor-preview cp-about-nav-preview" data-cp-about-nav-preview aria-label="<?php esc_attr_e('About Us dropdown preview', 'client-portal'); ?>"></div>

                <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Page Order', 'client-portal'); ?></h4>
                <p class="cp-menu-editor-body-help">
                    <?php esc_html_e('Press and hold a page to drag it, or use the arrow buttons.', 'client-portal'); ?>
                </p>
                <ul class="cp-menu-editor-list" data-cp-about-nav-list role="list"></ul>
                <p class="cp-menu-editor-empty" data-cp-about-nav-empty hidden><?php esc_html_e('No pages are currently set to appear in the About Us dropdown.', 'client-portal'); ?></p>

                <div data-cp-about-nav-inactive-section hidden>
                    <h4 class="cp-menu-editor-subsection-title"><?php esc_html_e('Inactive (not shown publicly)', 'client-portal'); ?></h4>
                    <ul class="cp-menu-editor-list cp-menu-editor-list-inactive" data-cp-about-nav-inactive-list role="list"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?php esc_html_e('Cancel', 'client-portal'); ?>
                </button>
                <button type="button" class="btn btn-primary" data-cp-about-nav-save>
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    <?php esc_html_e('Save Changes', 'client-portal'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
