<?php

if (!defined('ABSPATH')) {
    exit;
}

$metrics = [
    [__('Total Users', 'client-portal'), $analytics['total_users'], 'bi-people', 'primary'],
    [__('Total Posts', 'client-portal'), $analytics['total_posts'], 'bi-files', 'purple'],
    [__('Published Posts', 'client-portal'), $analytics['published'], 'bi-check-circle', 'success'],
    [__('Draft Posts', 'client-portal'), $analytics['drafts'], 'bi-pencil-square', 'warning'],
    [__('Categories', 'client-portal'), $analytics['categories'], 'bi-tags', 'info'],
];
?>
<div class="cp-page-heading"><div><p class="cp-eyebrow"><?php esc_html_e('Insights', 'client-portal'); ?></p><h2><?php esc_html_e('Publishing Analytics', 'client-portal'); ?></h2><p><?php esc_html_e('Real-time WordPress content and account totals.', 'client-portal'); ?></p></div></div>
<div class="cp-stat-grid"><?php foreach ($metrics as $metric) : ?><article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-<?php echo esc_attr($metric[3]); ?>"><i class="bi <?php echo esc_attr($metric[2]); ?>"></i></span><div><p><?php echo esc_html($metric[0]); ?></p><strong><?php echo esc_html(number_format_i18n($metric[1])); ?></strong></div></article><?php endforeach; ?></div>
<section class="cp-card cp-table-card"><div class="cp-card-header"><div><h3><?php esc_html_e('Latest Articles', 'client-portal'); ?></h3><p><?php esc_html_e('Most recently created WordPress posts.', 'client-portal'); ?></p></div></div><div class="table-responsive"><table class="cp-table table"><thead><tr><th><?php esc_html_e('Title', 'client-portal'); ?></th><th><?php esc_html_e('Status', 'client-portal'); ?></th><th><?php esc_html_e('Author', 'client-portal'); ?></th><th><?php esc_html_e('Date', 'client-portal'); ?></th></tr></thead><tbody>
<?php if ($analytics['latest_posts']) : foreach ($analytics['latest_posts'] as $article) : ?><tr><td><strong><?php echo esc_html($article->post_title ?: __('Untitled', 'client-portal')); ?></strong></td><td><span class="cp-badge cp-badge-<?php echo esc_attr(cp_status_badge_class($article->post_status)); ?>"><?php echo esc_html(ucfirst($article->post_status)); ?></span></td><td><?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></td><td><?php echo esc_html(get_the_date('', $article)); ?></td></tr><?php endforeach; else : ?><tr><td colspan="4" class="cp-empty-state"><?php esc_html_e('No articles found.', 'client-portal'); ?></td></tr><?php endif; ?>
</tbody></table></div></section>
