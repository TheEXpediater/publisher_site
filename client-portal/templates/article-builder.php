<?php

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = 'edit' === $builder_mode;
$form_args = $is_edit && $article ? ['id' => $article->ID] : [];
$form_page = $is_edit ? 'cp-article-edit' : 'cp-article-create';
$save_label = $is_edit ? __('Update Article', 'client-portal') : __('Create Article', 'client-portal');
$hero_image = isset($article_data['hero_image']) && is_array($article_data['hero_image']) ? $article_data['hero_image'] : ['source' => 'none', 'attachment_id' => 0, 'url' => '', 'preview_url' => ''];
$homepage_feature = isset($article_data['homepage_feature']) && is_array($article_data['homepage_feature'])
    ? wp_parse_args(
        $article_data['homepage_feature'],
        [
            'can_manage' => false,
            'is_featured' => false,
            'current_id' => 0,
            'current_title' => '',
        ]
    )
    : [
        'can_manage' => false,
        'is_featured' => false,
        'current_id' => 0,
        'current_title' => '',
    ];
$can_manage_homepage_feature = !empty($homepage_feature['can_manage']);
$is_homepage_featured = !empty($homepage_feature['is_featured']);
$current_homepage_feature_id = absint($homepage_feature['current_id']);
$current_homepage_feature_title = sanitize_text_field((string) $homepage_feature['current_title']);
$author = isset($article_data['author']) && is_array($article_data['author'])
    ? wp_parse_args(
        $article_data['author'],
        [
            'mode' => 'account',
            'user_id' => get_current_user_id(),
            'custom' => '',
            'display_name' => wp_get_current_user()->display_name,
            'can_edit' => false,
        ]
    )
    : cp_get_article_author_editor_data($article);
$author_accounts = isset($author_accounts) && is_array($author_accounts) ? $author_accounts : [];
$author_mode = 'custom' === $author['mode'] ? 'custom' : 'account';
$author_user_id = absint($author['user_id']);
$author_custom = cp_sanitize_article_custom_author($author['custom']);
$author_display_name = sanitize_text_field((string) $author['display_name']);
$can_edit_author = !empty($author['can_edit']);
?>
<?php cp_render_admin_notice($notice); ?>
<div class="cp-page-heading cp-builder-page-heading">
    <div><a class="cp-back-link" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><i class="bi bi-arrow-left"></i> <?php esc_html_e('Back to Articles', 'client-portal'); ?></a><p class="cp-eyebrow"><?php esc_html_e('Enterprise1979 Article Builder', 'client-portal'); ?></p><h2><?php echo $is_edit ? esc_html__('Edit Article', 'client-portal') : esc_html__('Create Article', 'client-portal'); ?></h2><p><?php esc_html_e('Build your story one clear content block at a time.', 'client-portal'); ?></p></div>
</div>
<form class="cp-article-builder" id="cp-article-builder-form" method="post" action="<?php echo esc_url(cp_admin_url($form_page, $form_args)); ?>" data-builder-mode="<?php echo esc_attr($builder_mode); ?>">
    <?php wp_nonce_field('cp_save_article_builder', 'cp_article_builder_nonce'); ?>
    <input type="hidden" name="cp_article_builder_action" value="save">
    <input type="hidden" name="cp_article_blocks" id="cp-article-blocks" value="">
    <textarea name="cp_article_title_rich" data-cp-title-rich hidden><?php echo esc_textarea(isset($article_data['title_rich']) ? $article_data['title_rich'] : ''); ?></textarea>
    <textarea name="cp_article_excerpt_rich" data-cp-excerpt-rich hidden><?php echo esc_textarea(isset($article_data['excerpt_rich']) ? $article_data['excerpt_rich'] : ''); ?></textarea>
    <section class="cp-card cp-builder-details">
        <div class="cp-card-header"><div><span class="cp-section-number">1</span><h3><?php esc_html_e('Article Details', 'client-portal'); ?></h3><p><?php esc_html_e('Set the publishing information for this publication article.', 'client-portal'); ?></p></div><button type="button" class="cp-icon-button cp-details-settings-button" data-cp-details-settings title="<?php esc_attr_e('Open Title and Excerpt Editor', 'client-portal'); ?>" aria-label="<?php esc_attr_e('Open Title and Excerpt Editor', 'client-portal'); ?>"><i class="bi bi-gear"></i></button></div>
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
            <div class="col-12">
                <div class="cp-homepage-feature-field">
                    <?php if ($can_manage_homepage_feature) : ?>
                        <input type="hidden" name="homepage_featured_article" value="0">
                        <label class="cp-feature-toggle" for="cp-homepage-featured-article">
                            <input type="checkbox" role="switch" aria-checked="<?php echo esc_attr($is_homepage_featured ? 'true' : 'false'); ?>" id="cp-homepage-featured-article" name="homepage_featured_article" value="1" data-cp-homepage-feature <?php checked($is_homepage_featured); ?>>
                            <span class="cp-feature-toggle-track" aria-hidden="true"><span class="cp-feature-toggle-knob"></span></span>
                            <span class="cp-feature-toggle-label"><?php esc_html_e('Featured Article on Landing Page', 'client-portal'); ?></span>
                        </label>
                        <p class="cp-homepage-feature-help"><?php esc_html_e('Show this article as the main story on the landing page.', 'client-portal'); ?></p>
                        <?php if ($is_homepage_featured) : ?>
                            <span class="cp-homepage-feature-badge"><?php esc_html_e('Currently featured', 'client-portal'); ?></span>
                        <?php elseif ($current_homepage_feature_id && '' !== $current_homepage_feature_title) : ?>
                            <?php /* translators: %s: Current landing-page featured article title. */ ?>
                            <p class="cp-homepage-feature-current"><?php printf(esc_html__('Current: %s', 'client-portal'), esc_html($current_homepage_feature_title)); ?></p>
                        <?php endif; ?>
                    <?php else : ?>
                        <label class="cp-feature-toggle cp-feature-toggle-disabled" for="cp-homepage-featured-article-disabled">
                            <input type="checkbox" role="switch" aria-checked="<?php echo esc_attr($is_homepage_featured ? 'true' : 'false'); ?>" id="cp-homepage-featured-article-disabled" disabled <?php checked($is_homepage_featured); ?>>
                            <span class="cp-feature-toggle-track" aria-hidden="true"><span class="cp-feature-toggle-knob"></span></span>
                            <span class="cp-feature-toggle-label"><?php esc_html_e('Featured Article on Landing Page', 'client-portal'); ?></span>
                        </label>
                        <p class="cp-homepage-feature-help"><?php esc_html_e('Editors and administrators only.', 'client-portal'); ?></p>
                        <?php if ($is_homepage_featured) : ?><span class="cp-homepage-feature-badge"><?php esc_html_e('Currently featured', 'client-portal'); ?></span><?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <div class="cp-article-author-field" data-cp-author-field>
                    <label class="form-label" for="cp-builder-author"><?php esc_html_e('Author', 'client-portal'); ?></label>
                    <div class="cp-author-input-group">
                        <input type="text" class="form-control" id="cp-builder-author" value="<?php echo esc_attr($author_display_name); ?>" readonly data-cp-author-display>
                        <button type="button" class="btn btn-outline-secondary cp-author-edit-button" data-cp-author-edit <?php disabled(!$can_edit_author); ?> aria-label="<?php esc_attr_e('Edit article author', 'client-portal'); ?>" title="<?php echo esc_attr($can_edit_author ? __('Edit article author', 'client-portal') : __('Only editors and administrators may change the author.', 'client-portal')); ?>">
                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                        </button>
                    </div>
                    <input type="hidden" name="article_author_mode" value="<?php echo esc_attr($author_mode); ?>" data-cp-author-mode>
                    <input type="hidden" name="article_author_user_id" value="<?php echo esc_attr($author_user_id); ?>" data-cp-author-user-id>
                    <input type="hidden" name="article_author_custom" value="<?php echo esc_attr($author_custom); ?>" data-cp-author-custom>
                    <p class="cp-author-help" data-cp-author-help><?php echo esc_html('custom' === $author_mode ? __('Typed author name', 'client-portal') : __('Linked to a WordPress account', 'client-portal')); ?></p>
                </div>
            </div>
        </div></div>
    </section>
    <section class="cp-card cp-builder-content-section">
        <div class="cp-card-header"><div><span class="cp-section-number">2</span><h3><?php esc_html_e('Content Blocks', 'client-portal'); ?></h3><p><?php esc_html_e('Arrange headings, paragraphs, media, and video in reading order. Insert tables directly from the paragraph toolbar.', 'client-portal'); ?></p></div><span class="cp-block-count"><strong data-cp-block-count><?php echo esc_html(count($blocks)); ?></strong> <?php esc_html_e('blocks', 'client-portal'); ?></span></div>
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
<div class="modal fade cp-modal cp-article-details-editor-modal" id="cp-article-details-editor-modal" tabindex="-1" aria-labelledby="cp-article-details-editor-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Article Details', 'client-portal'); ?></p><h2 class="modal-title" id="cp-article-details-editor-title"><?php esc_html_e('Title and Excerpt Editor', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div>
        <div class="modal-body cp-details-editor-body">
            <div class="cp-details-editor-field">
                <label class="form-label" for="cp-details-modal-title"><?php esc_html_e('Article Title', 'client-portal'); ?></label>
                <textarea class="form-control cp-details-title-editor" id="cp-details-modal-title" data-cp-details-title-editor rows="3"></textarea>
            </div>
            <div class="cp-details-editor-field">
                <label class="form-label" for="cp-details-modal-excerpt"><?php esc_html_e('Excerpt', 'client-portal'); ?></label>
                <textarea class="form-control cp-details-excerpt-editor" id="cp-details-modal-excerpt" data-cp-details-excerpt-editor rows="7"></textarea>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button><button type="button" class="btn btn-primary" data-cp-details-apply><i class="bi bi-check2"></i> <?php esc_html_e('Apply to Article', 'client-portal'); ?></button></div>
    </div></div>
</div>
<?php if ($can_edit_author) : ?>
<div class="modal fade cp-modal cp-author-choice-modal" id="cp-author-choice-modal" tabindex="-1" aria-labelledby="cp-author-choice-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Article Author', 'client-portal'); ?></p><h2 class="modal-title" id="cp-author-choice-title"><?php esc_html_e('Choose Author Method', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div>
        <div class="modal-body"><div class="cp-author-choice-grid">
            <button type="button" class="cp-author-choice-card" data-cp-author-open-accounts><i class="bi bi-people" aria-hidden="true"></i><strong><?php esc_html_e('Select from Accounts', 'client-portal'); ?></strong><span><?php esc_html_e('Choose an existing author, editor, or administrator account.', 'client-portal'); ?></span></button>
            <button type="button" class="cp-author-choice-card" data-cp-author-open-custom><i class="bi bi-input-cursor-text" aria-hidden="true"></i><strong><?php esc_html_e('Type Author', 'client-portal'); ?></strong><span><?php esc_html_e('Enter one or more display names as free text.', 'client-portal'); ?></span></button>
        </div></div>
    </div></div>
</div>
<div class="modal fade cp-modal cp-author-accounts-modal" id="cp-author-accounts-modal" tabindex="-1" aria-labelledby="cp-author-accounts-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Account Author', 'client-portal'); ?></p><h2 class="modal-title" id="cp-author-accounts-title"><?php esc_html_e('Select an Account', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div>
        <div class="modal-body"><div class="table-responsive"><table class="table cp-author-account-table"><thead><tr><th><?php esc_html_e('Name', 'client-portal'); ?></th><th><?php esc_html_e('Role', 'client-portal'); ?></th><th class="text-end"><?php esc_html_e('Action', 'client-portal'); ?></th></tr></thead><tbody>
        <?php if ($author_accounts) : foreach ($author_accounts as $author_account) : ?>
            <tr><td><strong><?php echo esc_html($author_account['name']); ?></strong></td><td><?php echo esc_html($author_account['role']); ?></td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary" data-cp-author-select-account data-author-id="<?php echo esc_attr($author_account['id']); ?>" data-author-name="<?php echo esc_attr($author_account['name']); ?>" data-author-role="<?php echo esc_attr($author_account['role']); ?>"><?php esc_html_e('Select', 'client-portal'); ?></button></td></tr>
        <?php endforeach; else : ?>
            <tr><td colspan="3" class="cp-empty-state"><?php esc_html_e('No eligible publishing accounts were found.', 'client-portal'); ?></td></tr>
        <?php endif; ?>
        </tbody></table></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button></div>
    </div></div>
</div>
<div class="modal fade cp-modal cp-author-custom-modal" id="cp-author-custom-modal" tabindex="-1" aria-labelledby="cp-author-custom-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Custom Author', 'client-portal'); ?></p><h2 class="modal-title" id="cp-author-custom-title"><?php esc_html_e('Type Author Name', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div>
        <div class="modal-body">
            <label class="form-label" for="cp-author-custom-input"><?php esc_html_e('Author display name', 'client-portal'); ?></label>
            <input type="text" class="form-control" id="cp-author-custom-input" maxlength="200" value="<?php echo esc_attr($author_custom); ?>" placeholder="<?php esc_attr_e('Example: Alvin Paul Soriano | Liza Asdasd', 'client-portal'); ?>" data-cp-author-custom-input>
            <div class="invalid-feedback" data-cp-author-custom-error><?php esc_html_e('Enter an author name.', 'client-portal'); ?></div>
            <div class="cp-author-warning"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php esc_html_e('A typed author is display-only and may not have a WordPress account, profile, or login access.', 'client-portal'); ?></span></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button><button type="button" class="btn btn-primary" data-cp-author-review-custom><?php esc_html_e('Review Author', 'client-portal'); ?></button></div>
    </div></div>
</div>
<div class="modal fade cp-modal cp-author-confirm-modal" id="cp-author-confirm-modal" tabindex="-1" aria-labelledby="cp-author-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Confirmation', 'client-portal'); ?></p><h2 class="modal-title" id="cp-author-confirm-title"><?php esc_html_e('Confirm Author Change', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div>
        <div class="modal-body"><dl class="cp-confirm-summary"><div><dt><?php esc_html_e('Author type', 'client-portal'); ?></dt><dd data-cp-author-confirm-type></dd></div><div><dt><?php esc_html_e('Author name', 'client-portal'); ?></dt><dd data-cp-author-confirm-name></dd></div><div data-cp-author-confirm-role-row><dt><?php esc_html_e('Account role', 'client-portal'); ?></dt><dd data-cp-author-confirm-role></dd></div></dl><div class="cp-author-warning" data-cp-author-confirm-warning hidden><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span><?php esc_html_e('This name will appear as the author, but it will not create or link a WordPress account.', 'client-portal'); ?></span></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button><button type="button" class="btn btn-primary" data-cp-author-confirm-apply><i class="bi bi-check2" aria-hidden="true"></i> <?php esc_html_e('Confirm Author', 'client-portal'); ?></button></div>
    </div></div>
</div>
<?php endif; ?>
<div class="modal fade cp-modal cp-confirm-article-modal" id="cp-confirm-article-modal" tabindex="-1" aria-labelledby="cp-confirm-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><p class="cp-eyebrow mb-1"><?php esc_html_e('Final Review', 'client-portal'); ?></p><h2 class="modal-title" id="cp-confirm-title"><?php echo $is_edit ? esc_html__('Confirm Article Update', 'client-portal') : esc_html__('Confirm Article Creation', 'client-portal'); ?></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'client-portal'); ?>"></button></div><div class="modal-body"><div class="cp-hero-warning" data-cp-hero-warning hidden><i class="bi bi-exclamation-triangle"></i><span><?php esc_html_e('Warning: This article does not have a hero image. It can still be saved, but article cards will use a placeholder image.', 'client-portal'); ?></span></div><dl class="cp-confirm-summary"><div><dt><?php esc_html_e('Title', 'client-portal'); ?></dt><dd data-cp-summary-title></dd></div><div><dt><?php esc_html_e('Status', 'client-portal'); ?></dt><dd data-cp-summary-status></dd></div><div><dt><?php esc_html_e('Category', 'client-portal'); ?></dt><dd data-cp-summary-category></dd></div><div><dt><?php esc_html_e('Author', 'client-portal'); ?></dt><dd data-cp-summary-author></dd></div><div><dt><?php esc_html_e('Landing Page Feature', 'client-portal'); ?></dt><dd data-cp-summary-homepage-feature><?php esc_html_e('No', 'client-portal'); ?></dd></div><div><dt><?php esc_html_e('Number of blocks', 'client-portal'); ?></dt><dd data-cp-summary-count></dd></div></dl><div class="cp-confirm-blocks"><strong><?php esc_html_e('Block list', 'client-portal'); ?></strong><ol data-cp-summary-blocks></ol></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php esc_html_e('Cancel', 'client-portal'); ?></button><button type="button" class="btn btn-primary" data-cp-confirm-save><i class="bi bi-check2"></i> <?php echo $is_edit ? esc_html__('Confirm and Update', 'client-portal') : esc_html__('Confirm and Create', 'client-portal'); ?></button></div></div></div></div>
