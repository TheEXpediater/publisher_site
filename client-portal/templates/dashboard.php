<?php

if (!defined('ABSPATH')) {
    exit;
}

$stats = isset($stats) ? $stats : [];

?>
<div class="cp-page-header mb-4">
    <div>
        <h2 class="h3 mb-1"><?php esc_html_e('Publishing Overview', 'client-portal'); ?></h2>
        <p class="text-muted mb-0">Monitor article volume, drafts, readers, and categories in one place.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1"><?php esc_html_e('Total Articles', 'client-portal'); ?></p>
                        <h3 class="mb-0"><?php echo esc_html(number_format_i18n($stats['total_articles'])); ?></h3>
                    </div>
                    <span class="cp-stat-icon"><i class="bi bi-file-earmark-text"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1"><?php esc_html_e('Published', 'client-portal'); ?></p>
                        <h3 class="mb-0"><?php echo esc_html(number_format_i18n($stats['published'])); ?></h3>
                    </div>
                    <span class="cp-stat-icon"><i class="bi bi-check-circle"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1"><?php esc_html_e('Drafts', 'client-portal'); ?></p>
                        <h3 class="mb-0"><?php echo esc_html(number_format_i18n($stats['drafts'])); ?></h3>
                    </div>
                    <span class="cp-stat-icon"><i class="bi bi-pencil-square"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1"><?php esc_html_e('Users', 'client-portal'); ?></p>
                        <h3 class="mb-0"><?php echo esc_html(number_format_i18n($stats['users'])); ?></h3>
                    </div>
                    <span class="cp-stat-icon"><i class="bi bi-people"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted mb-1"><?php esc_html_e('Categories', 'client-portal'); ?></p>
                        <h3 class="mb-0"><?php echo esc_html(number_format_i18n($stats['categories'])); ?></h3>
                    </div>
                    <span class="cp-stat-icon"><i class="bi bi-tags"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h3 class="h5 mb-0"><?php esc_html_e('Recent Articles', 'client-portal'); ?></h3>
            <a class="btn btn-primary btn-sm" href="<?php echo esc_url(cp_admin_url('cp-articles')); ?>">
                <i class="bi bi-plus-lg me-1"></i>
                <?php esc_html_e('Manage Articles', 'client-portal'); ?>
            </a>
        </div>
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
                    <?php if (!empty($stats['recent_articles'])) : ?>
                        <?php foreach ($stats['recent_articles'] as $article) : ?>
                            <tr>
                                <td><?php echo esc_html($article->post_title); ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?php echo esc_html(ucfirst($article->post_status)); ?></span></td>
                                <td><?php echo esc_html(get_the_author_meta('display_name', $article->post_author)); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($article->post_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-muted py-4 text-center"><?php esc_html_e('No articles found yet.', 'client-portal'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>