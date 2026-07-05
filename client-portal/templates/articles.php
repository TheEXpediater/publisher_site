<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="cp-page-heading">
    <div><p class="cp-eyebrow"><?php esc_html_e('Content', 'client-portal'); ?></p><h2><?php esc_html_e('Articles', 'client-portal'); ?></h2><p><?php esc_html_e('Search, review, and manage articles from one workspace.', 'client-portal'); ?></p></div>
    <a class="btn btn-primary" href="<?php echo esc_url(cp_admin_url('cp-article-create')); ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> <?php esc_html_e('Create Article', 'client-portal'); ?></a>
</div>
<?php cp_render_notice($notice); ?>
<section class="cp-card cp-filter-card">
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
        <input type="hidden" name="page" value="cp-articles">
        <div class="cp-filter-grid">
            <div><label class="form-label" for="cp-article-search"><?php esc_html_e('Search articles', 'client-portal'); ?></label><div class="cp-input-icon"><i class="bi bi-search"></i><input class="form-control" id="cp-article-search" name="article_search" value="<?php echo esc_attr($filters['search']); ?>" placeholder="<?php esc_attr_e('Search by title or content', 'client-portal'); ?>"></div></div>
            <div><label class="form-label" for="cp-status-filter"><?php esc_html_e('Status', 'client-portal'); ?></label><select class="form-select" id="cp-status-filter" name="status"><option value=""><?php esc_html_e('All statuses', 'client-portal'); ?></option><option value="draft" <?php selected($filters['status'], 'draft'); ?>><?php esc_html_e('Draft', 'client-portal'); ?></option><option value="publish" <?php selected($filters['status'], 'publish'); ?>><?php esc_html_e('Published', 'client-portal'); ?></option><option value="private" <?php selected($filters['status'], 'private'); ?>><?php esc_html_e('Private', 'client-portal'); ?></option></select></div>
            <div><label class="form-label" for="cp-category-filter"><?php esc_html_e('Category', 'client-portal'); ?></label><select class="form-select" id="cp-category-filter" name="category"><option value="0"><?php esc_html_e('All categories', 'client-portal'); ?></option><?php foreach ($categories as $category) : ?><option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($filters['category'], $category->term_id); ?>><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></div>
            <div class="cp-filter-actions"><button class="btn btn-primary" type="submit"><?php esc_html_e('Apply Filters', 'client-portal'); ?></button><a class="btn btn-outline-secondary" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><?php esc_html_e('Reset', 'client-portal'); ?></a></div>
        </div>
    </form>
</section>
<section class="cp-card cp-table-card">
    <div class="cp-card-header"><div><h3><?php esc_html_e('Article Library', 'client-portal'); ?></h3><p><?php echo esc_html(sprintf(_n('%s article shown', '%s articles shown', count($articles), 'client-portal'), number_format_i18n(count($articles)))); ?></p></div></div>
    <div class="table-responsive"><table class="cp-table table"><thead><tr><th><?php esc_html_e('Title', 'client-portal'); ?></th><th><?php esc_html_e('Status', 'client-portal'); ?></th><th><?php esc_html_e('Author', 'client-portal'); ?></th><th><?php esc_html_e('Category', 'client-portal'); ?></th><th><?php esc_html_e('Date', 'client-portal'); ?></th><th><?php esc_html_e('Actions', 'client-portal'); ?></th></tr></thead><tbody>
    <?php if ($articles) : foreach ($articles as $article) : $article_categories = get_the_category($article->ID); ?>
        <tr><td><strong><?php echo esc_html($article->post_title ?: __('Untitled', 'client-portal')); ?></strong></td><td><span class="cp-badge cp-badge-<?php echo esc_attr(cp_status_badge_class($article->post_status)); ?>"><?php echo esc_html(ucfirst($article->post_status)); ?></span></td><td><?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></td><td><?php echo esc_html($article_categories ? $article_categories[0]->name : __('Uncategorized', 'client-portal')); ?></td><td><?php echo esc_html(get_the_date('', $article)); ?></td><td><div class="cp-actions">
            <?php if (current_user_can('edit_post', $article->ID)) : ?><a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url(cp_admin_url('cp-article-edit', ['id' => $article->ID])); ?>"><i class="bi bi-pencil"></i><span><?php esc_html_e('Edit', 'client-portal'); ?></span></a><?php endif; ?>
            <?php if ('publish' === $article->post_status) : ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo esc_url(get_permalink($article)); ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i><span><?php esc_html_e('View', 'client-portal'); ?></span></a><?php endif; ?>
            <?php if (current_user_can('delete_post', $article->ID)) : ?><a class="btn btn-sm btn-outline-danger" data-cp-confirm="<?php echo esc_attr__('Delete this article permanently?', 'client-portal'); ?>" href="<?php echo esc_url(wp_nonce_url(cp_admin_url('cp-articles', ['action' => 'delete', 'id' => $article->ID]), 'cp_delete_article_' . $article->ID)); ?>"><i class="bi bi-trash"></i><span><?php esc_html_e('Delete', 'client-portal'); ?></span></a><?php endif; ?>
        </div></td></tr>
    <?php endforeach; else : ?><tr><td colspan="6" class="cp-empty-state"><i class="bi bi-file-earmark-text"></i><strong><?php esc_html_e('No articles found', 'client-portal'); ?></strong><span><?php esc_html_e('Try adjusting the filters or create your first article.', 'client-portal'); ?></span></td></tr><?php endif; ?>
    </tbody></table></div>
</section>
