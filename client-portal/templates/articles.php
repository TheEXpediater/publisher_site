<?php

if (!defined('ABSPATH')) {
    exit;
}

$editing_post = isset($editing_post) ? $editing_post : null;
$categories = isset($categories) ? $categories : [];
$articles = isset($articles) ? $articles : [];
$selected_category = isset($selected_category) ? $selected_category : 0;

?>
<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h2 class="h4 mb-1"><?php esc_html_e('Article Manager', 'client-portal'); ?></h2>
        <p class="text-muted mb-0">Create, edit, and publish articles from the admin interface.</p>
    </div>
    <a class="btn btn-primary" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>">
        <i class="bi bi-plus-lg me-1"></i>
        <?php esc_html_e('Create Article', 'client-portal'); ?>
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="post">
            <?php wp_nonce_field('cp_articles_action', 'cp_articles_nonce'); ?>
            <input type="hidden" name="article_id" value="<?php echo esc_attr($editing_post ? $editing_post->ID : ''); ?>" />
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="cp-article-title"><?php esc_html_e('Title', 'client-portal'); ?></label>
                    <input type="text" class="form-control" id="cp-article-title" name="title" value="<?php echo esc_attr($editing_post ? $editing_post->post_title : ''); ?>" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cp-article-status"><?php esc_html_e('Status', 'client-portal'); ?></label>
                    <select class="form-select" id="cp-article-status" name="status">
                        <option value="draft" <?php selected($editing_post ? $editing_post->post_status : 'draft', 'draft'); ?>><?php esc_html_e('Draft', 'client-portal'); ?></option>
                        <option value="publish" <?php selected($editing_post ? $editing_post->post_status : 'publish', 'publish'); ?>><?php esc_html_e('Published', 'client-portal'); ?></option>
                        <option value="private" <?php selected($editing_post ? $editing_post->post_status : 'private', 'private'); ?>><?php esc_html_e('Private', 'client-portal'); ?></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cp-article-category"><?php esc_html_e('Category', 'client-portal'); ?></label>
                    <select class="form-select" id="cp-article-category" name="category">
                        <option value="0"><?php esc_html_e('No Category', 'client-portal'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($selected_category, $category->term_id); ?>><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cp-article-excerpt"><?php esc_html_e('Excerpt', 'client-portal'); ?></label>
                    <input type="text" class="form-control" id="cp-article-excerpt" name="excerpt" value="<?php echo esc_attr($editing_post ? $editing_post->post_excerpt : ''); ?>" />
                </div>
                <div class="col-12">
                    <label class="form-label" for="cp-article-content"><?php esc_html_e('Content', 'client-portal'); ?></label>
                    <textarea class="form-control" id="cp-article-content" name="content" rows="8" required><?php echo esc_textarea($editing_post ? $editing_post->post_content : ''); ?></textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" name="cp_articles_submit" value="1">
                        <?php echo $editing_post ? esc_html__('Update Article', 'client-portal') : esc_html__('Save Article', 'client-portal'); ?>
                    </button>
                    <?php if ($editing_post) : ?>
                        <a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><?php esc_html_e('Cancel', 'client-portal'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Status', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Author', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Category', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Date', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Actions', 'client-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($articles)) : ?>
                        <?php foreach ($articles as $article) : ?>
                            <?php $article_categories = wp_get_post_categories($article->ID, ['fields' => 'all']); ?>
                            <tr>
                                <td><?php echo esc_html($article->post_title); ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?php echo esc_html(ucfirst($article->post_status)); ?></span></td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></td>
                                <td><?php echo esc_html(!empty($article_categories) ? $article_categories[0]->name : __('Uncategorized', 'client-portal')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($article->post_date))); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-articles', 'action' => 'edit', 'id' => $article->ID], admin_url('admin.php')), 'cp_edit_article_' . $article->ID)); ?>"><?php esc_html_e('Edit', 'client-portal'); ?></a>
                                        <a class="btn btn-sm btn-outline-danger" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'cp-articles', 'action' => 'delete', 'id' => $article->ID], admin_url('admin.php')), 'cp_delete_article_' . $article->ID)); ?>"><?php esc_html_e('Delete', 'client-portal'); ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="text-muted py-4 text-center"><?php esc_html_e('No articles found.', 'client-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>