<?php

if (!defined('ABSPATH')) {
    exit;
}

$analytics = isset($analytics) ? $analytics : [];

?>
<div class="mb-4">
    <h2 class="h4 mb-1"><?php esc_html_e('Analytics', 'client-portal'); ?></h2>
    <p class="text-muted mb-0">A dedicated analytics workspace for future reporting integrations.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1"><?php esc_html_e('Total Users', 'client-portal'); ?></p>
                <h3 class="mb-0"><?php echo esc_html(number_format_i18n($analytics['total_users'])); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1"><?php esc_html_e('Total Posts', 'client-portal'); ?></p>
                <h3 class="mb-0"><?php echo esc_html(number_format_i18n($analytics['total_posts'])); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1"><?php esc_html_e('Published', 'client-portal'); ?></p>
                <h3 class="mb-0"><?php echo esc_html(number_format_i18n($analytics['published'])); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1"><?php esc_html_e('Drafts', 'client-portal'); ?></p>
                <h3 class="mb-0"><?php echo esc_html(number_format_i18n($analytics['drafts'])); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted mb-1"><?php esc_html_e('Categories', 'client-portal'); ?></p>
                <h3 class="mb-0"><?php echo esc_html(number_format_i18n($analytics['categories'])); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h3 class="h5 mb-0"><?php esc_html_e('Latest Articles', 'client-portal'); ?></h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Status', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Author', 'client-portal'); ?></th>
                        <th><?php esc_html_e('Date', 'client-portal'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($analytics['latest_posts'])) : ?>
                        <?php foreach ($analytics['latest_posts'] as $post) : ?>
                            <tr>
                                <td><?php echo esc_html($post->post_title); ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?php echo esc_html(ucfirst($post->post_status)); ?></span></td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $post->post_author)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($post->post_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-muted py-4 text-center"><?php esc_html_e('No articles found.', 'client-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
