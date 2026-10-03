<?php

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = 'edit' === $builder_mode;
$form_args = $is_edit && $page_post ? ['id' => $page_post->ID] : [];
$form_page = $is_edit ? 'cp-page-edit' : 'cp-page-create';
$save_label = $is_edit ? __('Save Changes', 'client-portal') : __('Create Page', 'client-portal');
$confirm_title = $is_edit ? __('Update Page', 'client-portal') : __('Create Page', 'client-portal');
$confirm_message = $is_edit ? __('Save these page changes?', 'client-portal') : __('Create this page?', 'client-portal');
$is_home = !empty($page_data['is_home']);
$is_about_parent = !empty($page_data['is_about_us_parent']);
?>
<?php cp_render_admin_notice($notice); ?>
<div class="cp-page-heading cp-builder-page-heading">
    <div>
        <a class="cp-back-link" href="<?php echo esc_url(cp_admin_url('cp-pages')); ?>"><i class="bi bi-arrow-left"></i> <?php esc_html_e('Back to Pages', 'client-portal'); ?></a>
        <p class="cp-eyebrow"><?php esc_html_e('Enterprise1979 Page Builder', 'client-portal'); ?></p>
        <h2><?php echo $is_edit ? esc_html__('Edit Page', 'client-portal') : esc_html__('Add Page', 'client-portal'); ?></h2>
        <p><?php esc_html_e('Build a standalone institutional page one block at a time.', 'client-portal'); ?></p>
    </div>
</div>

<form class="cp-page-builder" id="cp-page-builder-form" method="post" action="<?php echo esc_url(cp_admin_url($form_page, $form_args)); ?>" data-builder-mode="<?php echo esc_attr($builder_mode); ?>">
    <?php wp_nonce_field('cp_save_page', 'cp_page_nonce'); ?>
    <input type="hidden" name="cp_page_builder_action" value="save">
    <input type="hidden" name="blocks_json" id="cp-page-blocks" value="">

    <section class="cp-card cp-builder-details">
        <div class="cp-card-header">
            <div><span class="cp-section-number">1</span><h3><?php esc_html_e('Page Details', 'client-portal'); ?></h3><p><?php esc_html_e('Set the title, URL, and visibility for this page.', 'client-portal'); ?></p></div>
        </div>
        <div class="cp-builder-section-body">
            <div class="row g-4">
                <div class="col-lg-8">
                    <label class="form-label" for="cp-page-title"><?php esc_html_e('Page Name / Title', 'client-portal'); ?></label>
                    <input class="form-control form-control-lg" id="cp-page-title" name="title" value="<?php echo esc_attr($page_data['title']); ?>" data-cp-page-title required>
                </div>
                <div class="col-lg-4">
                    <label class="form-label" for="cp-page-status"><?php esc_html_e('Status', 'client-portal'); ?></label>
                    <select class="form-select form-select-lg" id="cp-page-status" name="status" <?php disabled($is_home); ?>>
                        <option value="active" <?php selected($page_data['status'], 'active'); ?>><?php esc_html_e('Active', 'client-portal'); ?></option>
                        <option value="inactive" <?php selected($page_data['status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'client-portal'); ?></option>
                    </select>
                    <?php if ($is_home) : ?><div class="form-text"><?php esc_html_e('The Home page is always active.', 'client-portal'); ?></div><?php endif; ?>
                </div>
                <?php if (!$is_home) : ?>
                <div class="col-lg-8">
                    <label class="form-label" for="cp-page-slug"><?php esc_html_e('URL Slug', 'client-portal'); ?></label>
                    <input class="form-control" id="cp-page-slug" name="slug" value="<?php echo esc_attr($page_data['slug']); ?>" data-cp-page-slug placeholder="<?php esc_attr_e('auto-generated-from-title', 'client-portal'); ?>">
                    <div class="form-text"><?php esc_html_e('Leave blank to generate the slug from the page title.', 'client-portal'); ?></div>
                </div>
                <div class="col-lg-4">
                    <label class="form-label"><?php esc_html_e('Page URL', 'client-portal'); ?></label>
                    <div class="cp-permalink-preview" data-cp-permalink-preview><?php echo esc_html($page_data['permalink'] ?: cp_generate_page_permalink_preview($page_data['slug'] ?: $page_data['title'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!$is_home && !$is_about_parent) : ?>
                <div class="col-12">
                    <label class="cp-setting-toggle" for="cp-page-in-about-nav">
                        <input type="hidden" name="in_about_nav" value="0">
                        <input type="checkbox" role="switch" id="cp-page-in-about-nav" name="in_about_nav" value="1" <?php checked(!empty($page_data['in_about_nav'])); ?>>
                        <span class="cp-setting-toggle-track" aria-hidden="true"><span class="cp-setting-toggle-knob"></span></span>
                        <span class="cp-setting-toggle-copy">
                            <strong><?php esc_html_e('Show in About Us dropdown', 'client-portal'); ?></strong>
                            <small><?php esc_html_e('Only appears publicly while this page is Active.', 'client-portal'); ?></small>
                        </span>
                    </label>
                </div>
                <?php elseif ($is_about_parent) : ?>
                    <div class="col-12"><p class="form-text"><i class="bi bi-signpost-2-fill" aria-hidden="true"></i> <?php esc_html_e('This is the About Us navigation parent page.', 'client-portal'); ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="cp-card cp-builder-content-section">
        <div class="cp-card-header">
            <div><span class="cp-section-number">2</span><h3><?php esc_html_e('Page Content', 'client-portal'); ?></h3><p><?php esc_html_e('Arrange headings, rich text, images, staff grids, and buttons in reading order.', 'client-portal'); ?></p></div>
            <span class="cp-block-count"><strong data-cp-block-count><?php echo esc_html(count($blocks)); ?></strong> <?php esc_html_e('blocks', 'client-portal'); ?></span>
        </div>
        <div class="cp-builder-section-body">
            <div class="cp-builder-empty" data-cp-builder-empty<?php if ($blocks) : ?> hidden<?php endif; ?>>
                <i class="bi bi-layout-text-window-reverse"></i>
                <strong><?php esc_html_e('This page is ready for its first block', 'client-portal'); ?></strong>
                <span><?php esc_html_e('Choose a content type below to begin.', 'client-portal'); ?></span>
            </div>
            <div class="cp-builder-blocks" data-cp-block-list>
                <?php foreach ($blocks as $index => $block) : cp_render_page_block_editor($block, $index); endforeach; ?>
            </div>
            <div class="cp-add-block" data-cp-add-block>
                <button type="button" class="cp-add-block-trigger" data-cp-add-toggle aria-expanded="false" aria-controls="cp-add-block-options"><span class="cp-add-block-plus" aria-hidden="true"><i class="bi bi-plus-lg"></i></span><strong><?php esc_html_e('Add Block', 'client-portal'); ?></strong><small><?php esc_html_e('Choose a content type to add at the end of the page', 'client-portal'); ?></small></button>
                <div class="cp-add-block-options" id="cp-add-block-options" data-cp-add-options role="group" aria-label="<?php esc_attr_e('Block types', 'client-portal'); ?>">
                    <?php cp_render_page_block_choices(); ?>
                </div>
            </div>
        </div>
    </section>

    <div class="cp-builder-action-bar">
        <div><strong><?php echo esc_html($save_label); ?></strong><span><?php esc_html_e('Review your page before saving.', 'client-portal'); ?></span></div>
        <div>
            <a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-pages')); ?>"><?php esc_html_e('Cancel', 'client-portal'); ?></a>
            <button
                class="btn btn-primary btn-lg"
                type="submit"
                data-cp-confirm="<?php echo esc_attr($confirm_message); ?>"
                data-cp-confirm-title="<?php echo esc_attr($confirm_title); ?>"
                data-cp-confirm-label="<?php echo esc_attr($save_label); ?>"
            ><i class="bi bi-check2-circle"></i> <?php echo esc_html($save_label); ?></button>
        </div>
    </div>
</form>

<div class="cp-block-chooser" data-cp-block-chooser role="dialog" aria-labelledby="cp-block-chooser-title" hidden>
    <div class="cp-block-chooser-head">
        <strong id="cp-block-chooser-title" data-cp-chooser-title><?php esc_html_e('Insert block', 'client-portal'); ?></strong>
        <button type="button" class="cp-icon-button" data-cp-chooser-close title="<?php esc_attr_e('Close', 'client-portal'); ?>" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="cp-block-chooser-options"><?php cp_render_page_block_choices(); ?></div>
</div>

<div class="cp-block-menu" data-cp-block-menu role="menu" aria-label="<?php esc_attr_e('Block actions', 'client-portal'); ?>" hidden>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="focus"><i class="bi bi-cursor-text" aria-hidden="true"></i><span><?php esc_html_e('Edit / Focus', 'client-portal'); ?></span></button>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="fullscreen"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i><span data-cp-menu-fullscreen-label><?php esc_html_e('Full Screen', 'client-portal'); ?></span></button>
    <div class="cp-block-menu-separator" role="separator"></div>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="insert-above"><i class="bi bi-arrow-bar-up" aria-hidden="true"></i><span><?php esc_html_e('Insert Block Above', 'client-portal'); ?></span></button>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="insert-below"><i class="bi bi-arrow-bar-down" aria-hidden="true"></i><span><?php esc_html_e('Insert Block Below', 'client-portal'); ?></span></button>
    <div class="cp-block-menu-separator" role="separator"></div>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="duplicate"><i class="bi bi-copy" aria-hidden="true"></i><span><?php esc_html_e('Duplicate', 'client-portal'); ?></span></button>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="move-up"><i class="bi bi-arrow-up" aria-hidden="true"></i><span><?php esc_html_e('Move Up', 'client-portal'); ?></span></button>
    <button type="button" role="menuitem" tabindex="-1" data-cp-menu-action="move-down"><i class="bi bi-arrow-down" aria-hidden="true"></i><span><?php esc_html_e('Move Down', 'client-portal'); ?></span></button>
    <div class="cp-block-menu-separator" role="separator"></div>
    <button type="button" role="menuitem" tabindex="-1" class="is-danger" data-cp-menu-action="delete"><i class="bi bi-trash" aria-hidden="true"></i><span><?php esc_html_e('Delete', 'client-portal'); ?></span></button>
</div>

<?php // Blank copies of each block (and of one Staff card) rendered by the same PHP as saved blocks, so blocks added in the browser match them exactly. ?>
<?php foreach (array_keys(cp_page_block_types()) as $template_type) : ?>
<template data-cp-block-template="<?php echo esc_attr($template_type); ?>"><?php cp_render_page_block_editor(cp_page_default_block($template_type), -1, '__CPID__'); ?></template>
<?php endforeach; ?>
<template data-cp-staff-person-template><?php cp_render_page_staff_member_editor([], 0); ?></template>
