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
$homepage_feature = isset($homepage_feature) && $homepage_feature instanceof WP_Post ? $homepage_feature : null;
$homepage_feature_categories = isset($homepage_feature_categories) && is_array($homepage_feature_categories) ? $homepage_feature_categories : [];
$can_manage_homepage_feature = !empty($can_manage_homepage_feature);
$homepage_feature_id = $homepage_feature instanceof WP_Post ? absint($homepage_feature->ID) : 0;
$publication_name = cp_get_publication_name();
?>
<?php cp_render_admin_notice(isset($notice) ? $notice : null); ?>
<div class="cp-page-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Overview', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Publishing at a glance', 'client-portal'); ?></h2>
        <p><?php esc_html_e('A live summary of your publication workspace.', 'client-portal'); ?></p>
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
<section class="cp-card cp-homepage-feature-card" data-cp-feature-card data-current-feature-id="<?php echo esc_attr($homepage_feature_id); ?>">
    <div class="cp-card-header">
        <div>
            <h3><?php esc_html_e('Homepage Featured Article', 'client-portal'); ?></h3>
            <p><?php esc_html_e('The selected article appears as the large lead story on the public homepage.', 'client-portal'); ?></p>
        </div>
    </div>
    <div class="cp-homepage-feature-inline-notice" data-cp-feature-notice role="status" aria-live="polite" hidden></div>
    <div class="cp-homepage-feature-body">
        <div class="cp-homepage-feature-current">
            <div class="cp-homepage-feature-thumb" data-cp-feature-thumb>
                <?php if ($homepage_feature_id && has_post_thumbnail($homepage_feature_id)) : ?>
                    <?php echo wp_kses_post(get_the_post_thumbnail($homepage_feature_id, 'thumbnail', ['loading' => 'lazy', 'decoding' => 'async'])); ?>
                <?php else : ?>
                    <span><?php esc_html_e('No Image', 'client-portal'); ?></span>
                <?php endif; ?>
            </div>
            <div class="cp-homepage-feature-summary">
                <?php if ($homepage_feature instanceof WP_Post) : ?>
                    <span class="cp-homepage-feature-kicker" data-cp-feature-category><?php echo esc_html(function_exists('cp_frontend_post_category') ? cp_frontend_post_category($homepage_feature->ID) : __('General', 'client-portal')); ?></span>
                    <h4 data-cp-feature-title><?php echo esc_html(get_the_title($homepage_feature)); ?></h4>
                    <p data-cp-feature-meta>
                        <span data-cp-feature-author><?php echo esc_html(get_the_author_meta('display_name', $homepage_feature->post_author)); ?></span>
                        <span aria-hidden="true">&middot;</span>
                        <time data-cp-feature-date datetime="<?php echo esc_attr(get_the_date('c', $homepage_feature)); ?>"><?php echo esc_html(get_the_date('', $homepage_feature)); ?></time>
                    </p>
                    <div class="cp-homepage-feature-links" data-cp-feature-links>
                        <a href="<?php echo esc_url(get_permalink($homepage_feature)); ?>" target="_blank" rel="noopener"><?php esc_html_e('View Article', 'client-portal'); ?></a>
                        <?php if (current_user_can('edit_post', $homepage_feature->ID)) : ?>
                            <a href="<?php echo esc_url(cp_admin_url('cp-article-edit', ['id' => $homepage_feature->ID])); ?>"><?php esc_html_e('Edit Article', 'client-portal'); ?></a>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <span class="cp-homepage-feature-kicker" data-cp-feature-category hidden></span>
                    <h4 data-cp-feature-title><?php esc_html_e('No manual homepage feature selected', 'client-portal'); ?></h4>
                    <p data-cp-feature-meta><?php esc_html_e('The homepage can temporarily show the newest published Enterprise article until an editor or administrator selects one.', 'client-portal'); ?></p>
                    <div class="cp-homepage-feature-links" data-cp-feature-links hidden></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($can_manage_homepage_feature) : ?>
            <div class="cp-homepage-feature-controls">
                <button type="button" class="btn btn-primary" data-cp-feature-picker-open><?php echo esc_html($homepage_feature_id ? __('Change Featured Article', 'client-portal') : __('Choose Featured Article', 'client-portal')); ?></button>
                <button type="button" class="btn btn-outline-danger" data-cp-feature-clear-open<?php if (!$homepage_feature_id) : ?> hidden<?php endif; ?>><?php esc_html_e('Clear Featured Article', 'client-portal'); ?></button>
            </div>
        <?php else : ?>
            <p class="cp-homepage-feature-permission"><?php esc_html_e('Only editors and administrators may select the homepage featured article.', 'client-portal'); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php if ($can_manage_homepage_feature) : ?>
    <div class="enterprise-feature-picker" data-cp-feature-picker hidden>
        <div class="enterprise-feature-picker-dialog" role="dialog" aria-modal="true" aria-labelledby="enterprise-feature-picker-title">
            <div class="enterprise-feature-picker-header">
                <div>
                    <p class="cp-eyebrow"><?php echo esc_html($publication_name); ?></p>
                    <h2 id="enterprise-feature-picker-title"><?php esc_html_e('Select Homepage Featured Article', 'client-portal'); ?></h2>
                </div>
                <button type="button" class="enterprise-feature-picker-close" data-cp-feature-picker-close aria-label="<?php esc_attr_e('Close article picker', 'client-portal'); ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="enterprise-feature-picker-filters">
                <div class="enterprise-feature-picker-search">
                    <label class="form-label" for="enterprise-feature-picker-search"><?php esc_html_e('Search field', 'client-portal'); ?></label>
                    <div class="enterprise-feature-picker-search-control">
                        <input class="form-control" id="enterprise-feature-picker-search" type="search" data-cp-feature-search placeholder="<?php esc_attr_e('Search articles by title', 'client-portal'); ?>" autocomplete="off">
                        <button type="button" data-cp-feature-search-clear hidden><?php esc_html_e('Clear', 'client-portal'); ?></button>
                    </div>
                </div>
                <div class="enterprise-feature-picker-category">
                    <label class="form-label" for="enterprise-feature-picker-category"><?php esc_html_e('Category filter', 'client-portal'); ?></label>
                    <select class="form-select" id="enterprise-feature-picker-category" data-cp-feature-category-filter>
                        <option value="0"><?php esc_html_e('All Categories', 'client-portal'); ?></option>
                        <?php foreach ($homepage_feature_categories as $feature_category) : ?>
                            <?php if ($feature_category instanceof WP_Term) : ?>
                                <option value="<?php echo esc_attr($feature_category->term_id); ?>"><?php echo esc_html($feature_category->name); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="enterprise-feature-picker-status" data-cp-feature-result-count aria-live="polite"></div>
            <div class="enterprise-feature-picker-results" data-cp-feature-results tabindex="-1"></div>
            <div class="enterprise-feature-picker-state" data-cp-feature-loading hidden><?php esc_html_e('Loading articles...', 'client-portal'); ?></div>
            <div class="enterprise-feature-picker-state" data-cp-feature-empty hidden><?php esc_html_e('No published Enterprise articles match your filters.', 'client-portal'); ?></div>
            <div class="enterprise-feature-picker-state enterprise-feature-picker-error" data-cp-feature-error hidden></div>
            <div class="enterprise-feature-picker-pagination">
                <button type="button" class="btn btn-outline-secondary" data-cp-feature-prev disabled><?php esc_html_e('Previous', 'client-portal'); ?></button>
                <span data-cp-feature-page-status></span>
                <button type="button" class="btn btn-outline-secondary" data-cp-feature-next disabled><?php esc_html_e('Next', 'client-portal'); ?></button>
            </div>
            <div class="enterprise-feature-picker-actions">
                <button type="button" class="btn btn-outline-secondary" data-cp-feature-picker-cancel><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                <button type="button" class="btn btn-primary" data-cp-feature-continue disabled><?php esc_html_e('Continue', 'client-portal'); ?></button>
            </div>
        </div>
    </div>

    <div class="enterprise-feature-picker enterprise-feature-confirmation" data-cp-feature-confirm hidden>
        <div class="enterprise-feature-picker-dialog enterprise-feature-confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="enterprise-feature-confirm-title">
            <div class="enterprise-feature-picker-header">
                <div>
                    <p class="cp-eyebrow"><?php echo esc_html($publication_name); ?></p>
                    <h2 id="enterprise-feature-confirm-title"><?php esc_html_e('Confirm Homepage Featured Article', 'client-portal'); ?></h2>
                </div>
                <button type="button" class="enterprise-feature-picker-close" data-cp-feature-confirm-close aria-label="<?php esc_attr_e('Close confirmation', 'client-portal'); ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="enterprise-feature-confirmation-body">
                <div class="enterprise-feature-confirmation-card" data-cp-feature-confirm-card></div>
                <p class="enterprise-feature-confirmation-replace" data-cp-feature-confirm-replace hidden></p>
                <p class="enterprise-feature-confirmation-message" data-cp-feature-confirm-message></p>
                <div class="enterprise-feature-picker-state enterprise-feature-picker-error" data-cp-feature-confirm-error hidden></div>
            </div>
            <div class="enterprise-feature-picker-actions">
                <button type="button" class="btn btn-outline-secondary" data-cp-feature-confirm-back><?php esc_html_e('Back', 'client-portal'); ?></button>
                <button type="button" class="btn btn-primary" data-cp-feature-confirm-save><?php esc_html_e('Confirm Selection', 'client-portal'); ?></button>
            </div>
        </div>
    </div>

    <div class="enterprise-feature-picker enterprise-feature-confirmation" data-cp-feature-clear-confirm hidden>
        <div class="enterprise-feature-picker-dialog enterprise-feature-confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="enterprise-feature-clear-title">
            <div class="enterprise-feature-picker-header">
                <div>
                    <p class="cp-eyebrow"><?php echo esc_html($publication_name); ?></p>
                    <h2 id="enterprise-feature-clear-title"><?php esc_html_e('Clear Homepage Featured Article', 'client-portal'); ?></h2>
                </div>
                <button type="button" class="enterprise-feature-picker-close" data-cp-feature-clear-close aria-label="<?php esc_attr_e('Close clear confirmation', 'client-portal'); ?>"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="enterprise-feature-confirmation-body">
                <p class="enterprise-feature-confirmation-message" data-cp-feature-clear-message></p>
                <p class="enterprise-feature-confirmation-replace"><?php esc_html_e('The newest published Enterprise article will appear until another article is selected.', 'client-portal'); ?></p>
                <div class="enterprise-feature-picker-state enterprise-feature-picker-error" data-cp-feature-clear-error hidden></div>
            </div>
            <div class="enterprise-feature-picker-actions">
                <button type="button" class="btn btn-outline-secondary" data-cp-feature-clear-cancel><?php esc_html_e('Cancel', 'client-portal'); ?></button>
                <button type="button" class="btn btn-danger" data-cp-feature-clear-confirm-action><?php esc_html_e('Clear Feature', 'client-portal'); ?></button>
            </div>
        </div>
    </div>
<?php endif; ?>
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
