<?php

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = 'edit' === $builder_mode;
$form_args = $is_edit && $article ? ['id' => $article->ID] : [];
$form_page = $is_edit ? 'cp-article-edit' : 'cp-article-create';
$save_label = $is_edit ? __('Update Article', 'client-portal') : __('Create Article', 'client-portal');
$hero_image = isset($article_data['hero_image']) && is_array($article_data['hero_image']) ? $article_data['hero_image'] : ['source' => 'none', 'attachment_id' => 0, 'url' => '', 'preview_url' => ''];
?>
<?php cp_render_admin_notice($notice); ?>
<div class="cp-page-heading cp-builder-page-heading">
    <div><a class="cp-back-link" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><i class="bi bi-arrow-left"></i> <?php esc_html_e('Back to Articles', 'client-portal'); ?></a><p class="cp-eyebrow"><?php esc_html_e('Enterprise1979 Article Builder', 'client-portal'); ?></p><h2><?php echo $is_edit ? esc_html__('Edit Article', 'client-portal') : esc_html__('Create Article', 'client-portal'); ?></h2><p><?php esc_html_e('Build your story one clear content block at a time.', 'client-portal'); ?></p></div>
</div>
<form class="cp-article-builder" id="cp-article-builder-form" method="post" action="<?php echo esc_url(cp_admin_url($form_page, $form_args)); ?>" data-builder-mode="<?php echo esc_attr($builder_mode); ?>">
    <?php wp_nonce_field('cp_save_article_builder', 'cp_article_builder_nonce'); ?>
    <input type="hidden" name="cp_article_builder_action" value="save">
    <input type="hidden" name="cp_article_blocks" id="cp-article-blocks" value="">
    <section class="cp-card cp-builder-details">
        <div class="cp-card-header"><div><span class="cp-section-number">1</span><h3><?php esc_html_e('Article Details', 'client-portal'); ?></h3><p><?php esc_html_e('Set the publishing information for this WordPress post.', 'client-portal'); ?></p></div></div>
        <div class="cp-builder-section-body"><div class="row g-4">
            <div class="col-lg-8"><label class="form-label" for="cp-builder-title"><?php esc_html_e('Title', 'client-portal'); ?></label><input class="form-control form-control-lg" id="cp-builder-title" name="title" value="<?php echo esc_attr($article_data['title']); ?>" required></div>
            <div class="col-lg-4"><label class="form-label" for="cp-builder-status"><?php esc_html_e('Status', 'client-portal'); ?></label><select class="form-select form-select-lg" id="cp-builder-status" name="status"><option value="draft" <?php selected($article_data['status'], 'draft'); ?>><?php esc_html_e('Draft', 'client-portal'); ?></option><?php if (cp_can_publish_directly()) : ?><option value="publish" <?php selected($article_data['status'], 'publish'); ?>><?php esc_html_e('Published', 'client-portal'); ?></option><?php endif; ?><option value="private" <?php selected($article_data['status'], 'private'); ?>><?php esc_html_e('Private', 'client-portal'); ?></option></select></div>
            <div class="col-lg-8"><label class="form-label" for="cp-builder-excerpt"><?php esc_html_e('Excerpt', 'client-portal'); ?></label><textarea class="form-control" id="cp-builder-excerpt" name="excerpt" rows="3"><?php echo esc_textarea($article_data['excerpt']); ?></textarea></div>
            <div class="col-lg-4"><label class="form-label" for="cp-builder-category"><?php esc_html_e('Category', 'client-portal'); ?></label><select class="form-select" id="cp-builder-category" name="category"><option value="0"><?php esc_html_e('Uncategorized', 'client-portal'); ?></option><?php foreach ($categories as $category) : ?><option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($article_data['category'], $category->term_id); ?>><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><div class="cp-hero-image-field" data-cp-hero-image>
                <div class="cp-hero-image-heading"><div><label class="form-label"><?php esc_html_e('Hero Image', 'client-portal'); ?></label><p><?php esc_html_e('This image will be used as the main image for article cards and category listings.', 'client-portal'); ?></p></div></div>
                <div class="cp-source-selector cp-hero-source-selector">
                    <label><input type="radio" name="hero_image_source" value="media" data-cp-hero-source <?php checked($hero_image['source'], 'media'); ?>> <span><?php esc_html_e('Media Library', 'client-portal'); ?></span></label>
                    <label><input type="radio" name="hero_image_source" value="url" data-cp-hero-source <?php checked($hero_image['source'], 'url'); ?>> <span><?php esc_html_e('Image URL', 'client-portal'); ?></span></label>
                    <label><input type="radio" name="hero_image_source" value="none" data-cp-hero-source <?php checked($hero_image['source'], 'none'); ?>> <span><?php esc_html_e('No Image', 'client-portal'); ?></span></label>
                </div>
                <input type="hidden" name="hero_attachment_id" value="<?php echo esc_attr($hero_image['attachment_id']); ?>" data-cp-hero-attachment>
                <div class="cp-hero-source-panel" data-cp-hero-media<?php if ('media' !== $hero_image['source']) : ?> hidden<?php endif; ?>>
                    <button type="button" class="btn btn-outline-primary" data-cp-hero-select><i class="bi bi-images"></i> <?php esc_html_e('Select Image', 'client-portal'); ?></button>
                    <div class="cp-hero-preview" data-cp-hero-media-preview<?php if (!$hero_image['preview_url'] || 'media' !== $hero_image['source']) : ?> hidden<?php endif; ?>><?php if ($hero_image['preview_url'] && 'media' === $hero_image['source']) : ?><img src="<?php echo esc_url($hero_image['preview_url']); ?>" alt=""><?php endif; ?></div>
                </div>
                <div class="cp-hero-source-panel" data-cp-hero-url-panel<?php if ('url' !== $hero_image['source']) : ?> hidden<?php endif; ?>>
                    <label class="form-label" for="cp-hero-image-url"><?php esc_html_e('Image URL', 'client-portal'); ?></label>
                    <input type="url" class="form-control" id="cp-hero-image-url" name="hero_image_url" value="<?php echo esc_attr($hero_image['url']); ?>" data-cp-hero-url>
                    <div class="cp-hero-preview" data-cp-hero-url-preview<?php if (!$hero_image['preview_url'] || 'url' !== $hero_image['source']) : ?> hidden<?php endif; ?>><?php if ($hero_image['preview_url'] && 'url' === $hero_image['source']) : ?><img src="<?php echo esc_url($hero_image['preview_url']); ?>" alt=""><?php endif; ?></div>
                </div>
            </div></div>
        </div></div>
    </section>
    <section class="cp-card cp-builder-content-section">
        <div class="cp-card-header"><div><span class="cp-section-number">2</span><h3><?php esc_html_e('Content Blocks', 'client-portal'); ?></h3><p><?php esc_html_e('Arrange headings, paragraphs, media, and video in reading order.', 'client-portal'); ?></p></div><span class="cp-block-count"><strong data-cp-block-count><?php echo esc_html(count($blocks)); ?></strong> <?php esc_html_e('blocks', 'client-portal'); ?></span></div>
        <div class="cp-builder-section-body">
            <div class="cp-builder-empty" data-cp-builder-empty<?php if ($blocks) : ?> hidden<?php endif; ?>><i class="bi bi-layout-text-window-reverse"></i><strong><?php esc_html_e('Your article is ready for its first block', 'client-portal'); ?></strong><span><?php esc_html_e('Choose a content type below to begin.', 'client-portal'); ?></span></div>
            <div class="cp-builder-blocks" data-cp-block-list><?php foreach ($blocks as $index => $block) : cp_render_article_block_editor($block, $index); endforeach; ?></div>
            <div class="cp-add-block" data-cp-add-block>
                <button type="button" class="cp-add-block-trigger" data-cp-add-toggle><span class="cp-add-block-plus"><i class="bi bi-plus-lg"></i></span><strong><?php esc_html_e('+ Add Block', 'client-portal'); ?></strong><small><?php esc_html_e('Choose content type to insert', 'client-portal'); ?></small></button>
                <div class="cp-add-block-options" data-cp-add-options>
                    <button type="button" data-cp-add-type="heading"><i class="bi bi-type-h1"></i><span><?php esc_html_e('Heading', 'client-portal'); ?></span></button>
                    <button type="button" data-cp-add-type="paragraph"><i class="bi bi-text-paragraph"></i><span><?php esc_html_e('Paragraph', 'client-portal'); ?></span></button>
                    <button type="button" data-cp-add-type="image"><i class="bi bi-image"></i><span><?php esc_html_e('Image', 'client-portal'); ?></span></button>
                    <button type="button" data-cp-add-type="video"><i class="bi bi-play-btn"></i><span><?php esc_html_e('Video URL', 'client-portal'); ?></span></button>
                </div>
            </div>
        </div>
    </section>
    <div class="cp-builder-action-bar"><div><strong><?php echo esc_html($save_label); ?></strong><span><?php esc_html_e('Review your summary before the article is saved.', 'client-portal'); ?></span></div><div><a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><?php esc_html_e('Cancel', 'client-portal'); ?></a><button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-check2-circle"></i> <?php echo esc_html($save_label); ?></button></div></div>
</form>
<div class="modal fade cp-modal cp-confirm-article-modal" id="cp-confirm-article-modal" tabindex="-1" aria-labelledby="cp-confirm-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Final Review', 'client-portal'); ?></p><h2 class="modal-title" id="cp-confirm-title"><?php echo $is_edit ? esc_html__('Confirm Article Update', 'client-portal') : esc_html__('Confirm Article Creation', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div><div class="modal-body"><div class="cp-hero-warning" data-cp-hero-warning hidden><i class="bi bi-exclamation-triangle"></i><span><?php esc_html_e('Warning: This article does not have a hero image. It can still be saved, but article cards will use a placeholder image.', 'client-portal'); ?></span></div><dl class="cp-confirm-summary"><div><dt><?php esc_html_e('Title', 'client-portal'); ?></dt><dd data-cp-summary-title></dd></div><div><dt><?php esc_html_e('Status', 'client-portal'); ?></dt><dd data-cp-summary-status></dd></div><div><dt><?php esc_html_e('Category', 'client-portal'); ?></dt><dd data-cp-summary-category></dd></div><div><dt><?php esc_html_e('Number of blocks', 'client-portal'); ?></dt><dd data-cp-summary-count></dd></div></dl><div class="cp-confirm-blocks"><strong><?php esc_html_e('Block list', 'client-portal'); ?></strong><ol data-cp-summary-blocks></ol></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button><button type="button" class="btn btn-primary" data-cp-confirm-save><i class="bi bi-check2"></i> <?php echo $is_edit ? esc_html__('Confirm and Update', 'client-portal') : esc_html__('Confirm and Create', 'client-portal'); ?></button></div></div></div></div>
