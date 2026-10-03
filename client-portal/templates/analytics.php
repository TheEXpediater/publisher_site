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
<div class="cp-page-heading"><div><p class="cp-eyebrow"><?php esc_html_e('Insights', 'client-portal'); ?></p><h2><?php esc_html_e('Publishing Analytics', 'client-portal'); ?></h2><p><?php esc_html_e('Real-time publication content and account totals.', 'client-portal'); ?></p></div></div>
<div class="cp-stat-grid"><?php foreach ($metrics as $metric) : ?><article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-<?php echo esc_attr($metric[3]); ?>"><i class="bi <?php echo esc_attr($metric[2]); ?>"></i></span><div><p><?php echo esc_html($metric[0]); ?></p><strong><?php echo esc_html(number_format_i18n($metric[1])); ?></strong></div></article><?php endforeach; ?></div>

<div class="cp-page-heading cp-analytics-google-heading" id="cp-analytics-google">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Google Insights', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Website Traffic', 'client-portal'); ?></h2>
        <p><?php esc_html_e('From the Google Analytics property already connected through Site Kit.', 'client-portal'); ?></p>
    </div>
    <div class="cp-analytics-toolbar">
        <div class="cp-date-range-group" role="group" aria-label="<?php esc_attr_e('Date range', 'client-portal'); ?>" data-cp-analytics-range-group>
            <?php foreach (cp_analytics_date_ranges() as $range_key => $range_label) : ?>
                <button type="button" class="cp-date-range-btn" data-cp-range="<?php echo esc_attr($range_key); ?>" aria-pressed="false"><?php echo esc_html($range_label); ?></button>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline-secondary cp-refresh-button" data-cp-analytics-refresh>
            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
            <span data-cp-refresh-label><?php esc_html_e('Refresh Data', 'client-portal'); ?></span>
        </button>
    </div>
</div>
<p class="cp-analytics-updated" data-cp-analytics-updated hidden></p>

<section class="cp-card cp-analytics-section" data-cp-analytics-section="traffic">
    <div class="cp-card-header"><div><h3><?php esc_html_e('Traffic Overview', 'client-portal'); ?></h3><p><?php esc_html_e('Visitors, page views, and sessions for the selected period.', 'client-portal'); ?></p></div></div>
    <div class="cp-analytics-body">
        <div class="cp-stat-grid cp-stat-grid-compact">
            <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-primary"><i class="bi bi-people" aria-hidden="true"></i></span><div><p><?php esc_html_e('Visitors', 'client-portal'); ?></p><strong data-cp-metric="visitors">&mdash;</strong></div></article>
            <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-purple"><i class="bi bi-eye" aria-hidden="true"></i></span><div><p><?php esc_html_e('Page Views', 'client-portal'); ?></p><strong data-cp-metric="page_views">&mdash;</strong></div></article>
            <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-info"><i class="bi bi-graph-up" aria-hidden="true"></i></span><div><p><?php esc_html_e('Sessions', 'client-portal'); ?></p><strong data-cp-metric="sessions">&mdash;</strong></div></article>
        </div>
        <div class="cp-analytics-chart-wrap">
            <h4><?php esc_html_e('Traffic Over Time', 'client-portal'); ?></h4>
            <div class="cp-analytics-chart" data-cp-chart></div>
        </div>
    </div>
    <p class="cp-analytics-state" data-cp-state hidden></p>
</section>

<section class="cp-card cp-table-card" data-cp-analytics-section="top_content">
    <div class="cp-card-header"><div><h3><?php esc_html_e('Top Content', 'client-portal'); ?></h3><p><?php esc_html_e('Most-viewed articles and pages.', 'client-portal'); ?></p></div></div>
    <div class="table-responsive"><table class="cp-table table"><thead><tr><th><?php esc_html_e('Article / Page', 'client-portal'); ?></th><th><?php esc_html_e('Views', 'client-portal'); ?></th><th><?php esc_html_e('Users', 'client-portal'); ?></th></tr></thead><tbody data-cp-rows><tr><td colspan="3" class="cp-empty-state"><?php esc_html_e('Loading...', 'client-portal'); ?></td></tr></tbody></table></div>
    <p class="cp-analytics-state" data-cp-state hidden></p>
</section>

<div class="cp-page-heading cp-analytics-google-heading">
    <div>
        <p class="cp-eyebrow"><?php esc_html_e('Google Insights', 'client-portal'); ?></p>
        <h2><?php esc_html_e('Google Search Performance', 'client-portal'); ?></h2>
        <p><?php esc_html_e('From the Search Console property already connected through Site Kit.', 'client-portal'); ?></p>
    </div>
</div>

<section class="cp-card cp-analytics-section" data-cp-analytics-section="search_summary">
    <div class="cp-card-header"><div><h3><?php esc_html_e('Search Summary', 'client-portal'); ?></h3><p><?php esc_html_e('How the site performed in Google Search for the selected period.', 'client-portal'); ?></p></div></div>
    <div class="cp-stat-grid cp-stat-grid-compact">
        <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-primary"><i class="bi bi-cursor" aria-hidden="true"></i></span><div><p><?php esc_html_e('Clicks', 'client-portal'); ?></p><strong data-cp-metric="clicks">&mdash;</strong></div></article>
        <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-purple"><i class="bi bi-binoculars" aria-hidden="true"></i></span><div><p><?php esc_html_e('Impressions', 'client-portal'); ?></p><strong data-cp-metric="impressions">&mdash;</strong></div></article>
        <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-success"><i class="bi bi-percent" aria-hidden="true"></i></span><div><p><?php esc_html_e('CTR', 'client-portal'); ?></p><strong data-cp-metric="ctr">&mdash;</strong></div></article>
        <article class="cp-card cp-stat-card"><span class="cp-stat-icon cp-tone-info"><i class="bi bi-signpost-2" aria-hidden="true"></i></span><div><p><?php esc_html_e('Average Position', 'client-portal'); ?></p><strong data-cp-metric="position">&mdash;</strong></div></article>
    </div>
    <p class="cp-analytics-state" data-cp-state hidden></p>
</section>

<section class="cp-card cp-table-card" data-cp-analytics-section="top_queries">
    <div class="cp-card-header"><div><h3><?php esc_html_e('Top Search Queries', 'client-portal'); ?></h3><p><?php esc_html_e('Queries that led to this site in Google Search.', 'client-portal'); ?></p></div></div>
    <div class="table-responsive"><table class="cp-table table"><thead><tr><th><?php esc_html_e('Query', 'client-portal'); ?></th><th><?php esc_html_e('Clicks', 'client-portal'); ?></th><th><?php esc_html_e('Impressions', 'client-portal'); ?></th><th><?php esc_html_e('CTR', 'client-portal'); ?></th><th><?php esc_html_e('Position', 'client-portal'); ?></th></tr></thead><tbody data-cp-rows><tr><td colspan="5" class="cp-empty-state"><?php esc_html_e('Loading...', 'client-portal'); ?></td></tr></tbody></table></div>
    <p class="cp-analytics-state" data-cp-state hidden></p>
</section>
