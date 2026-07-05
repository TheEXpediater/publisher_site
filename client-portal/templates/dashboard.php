<?php

if (!defined('ABSPATH')) {
    exit;
}

$cards = [
    ['label' => __('Total Articles', 'client-portal'), 'value' => $stats['total_articles'], 'icon' => 'bi-files', 'tone' => 'primary'],
    ['label' => __('Published Articles', 'client-portal'), 'value' => $stats['published'], 'icon' => 'bi-check2-circle', 'tone' => 'success'],
    ['label' => __('Draft Articles', 'client-portal'), 'value' => $stats['drafts'], 'icon' => 'bi-pencil-square', 'tone' => 'warning'],
    ['label' => __('Users', 'client-portal'), 'value' => $stats['users'], 'icon' => 'bi-people', 'tone' => 'info'],
    ['label' => __('Categories', 'client-portal'), 'value' => $stats['categories'], 'icon' => 'bi-tags', 'tone' => 'purple'],
];
?>
<div class="cp-page-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Overview', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Publishing at a glance', 'client-portal'); ?></h2>
        <p><?php esc_html_e('A live summary of your WordPress publishing workspace.', 'client-portal'); ?></p>
    </div>
    <?php if (current_user_can('edit_posts')) : ?>
        <a class="btn btn-primary" href="<?php echo esc_url(cp_admin_url('cp-article-create')); ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> <?php esc_html_e('Create Article', 'client-portal'); ?></a>
    <?php endif; ?>
</div>
<div class="cp-stat-grid">
    <?php foreach ($cards as $card) : ?>
        <article class="cp-card cp-stat-card">
            <span class="cp-stat-icon cp-tone-<?php echo esc_attr($card['tone']); ?>"><i class="bi <?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></i></span>
            <div><p><?php echo esc_html($card['label']); ?></p><strong><?php echo esc_html(number_format_i18n($card['value'])); ?></strong></div>
        </article>
    <?php endforeach; ?>
</div>
<section class="cp-card cp-table-card">
    <div class="cp-card-header">
        <div><h3><?php esc_html_e('Recent Articles', 'client-portal'); ?></h3><p><?php esc_html_e('The latest content across the portal.', 'client-portal'); ?></p></div>
        <?php if (current_user_can('edit_posts')) : ?><a href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>"><?php esc_html_e('View all', 'client-portal'); ?> <i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="cp-table table"><thead><tr><th><?php esc_html_e('Title', 'client-portal'); ?></th><th><?php esc_html_e('Status', 'client-portal'); ?></th><th><?php esc_html_e('Author', 'client-portal'); ?></th><th><?php esc_html_e('Date', 'client-portal'); ?></th></tr></thead>
        <tbody>
        <?php if ($stats['recent_articles']) : foreach ($stats['recent_articles'] as $article) : ?>
            <tr><td><strong><?php echo esc_html($article->post_title ?: __('Untitled', 'client-portal')); ?></strong></td><td><span class="cp-badge cp-badge-<?php echo esc_attr(cp_status_badge_class($article->post_status)); ?>"><?php echo esc_html(ucfirst($article->post_status)); ?></span></td><td><?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></td><td><?php echo esc_html(get_the_date('', $article)); ?></td></tr>
        <?php endforeach; else : ?>
            <tr><td colspan="4" class="cp-empty-state"><?php esc_html_e('No articles have been created yet.', 'client-portal'); ?></td></tr>
        <?php endif; ?>
        </tbody></table>
    </div>
</section>
