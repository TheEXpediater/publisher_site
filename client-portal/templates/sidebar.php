<?php

if (!defined('ABSPATH')) {
    exit;
}

$pages = [
    'cp-dashboard' => [
        'label' => __('Dashboard', 'client-portal'),
        'icon' => 'bi-speedometer2',
        'url' => cp_admin_url('cp-dashboard'),
    ],
    'cp-articles' => [
        'label' => __('Articles', 'client-portal'),
        'icon' => 'bi-file-earmark-text',
        'url' => cp_admin_url('cp-articles'),
    ],
    'cp-categories' => [
        'label' => __('Categories', 'client-portal'),
        'icon' => 'bi-tags',
        'url' => cp_admin_url('cp-categories'),
    ],
    'cp-users' => [
        'label' => __('Users', 'client-portal'),
        'icon' => 'bi-people',
        'url' => cp_admin_url('cp-users'),
    ],
    'cp-analytics' => [
        'label' => __('Analytics', 'client-portal'),
        'icon' => 'bi-bar-chart-line',
        'url' => cp_admin_url('cp-analytics'),
    ],
    'cp-settings' => [
        'label' => __('Settings', 'client-portal'),
        'icon' => 'bi-gear',
        'url' => cp_admin_url('cp-settings'),
    ],
];

?>
<div class="cp-sidebar-header">
    <div class="cp-brand-mark">E</div>
    <div>
        <h4 class="mb-0">Enterprise1979</h4>
        <p class="small text-muted mb-0">Publisher Portal</p>
    </div>
</div>

<div class="list-group list-group-flush mt-4">
    <?php foreach ($pages as $slug => $item) : ?>
        <a class="list-group-item list-group-item-action border-0 rounded-3 mb-2 <?php echo cp_is_active_page($slug) ? 'active' : ''; ?>"
           href="<?php echo esc_url($item['url']); ?>">
            <i class="bi <?php echo esc_attr($item['icon']); ?> me-2"></i>
            <?php echo esc_html($item['label']); ?>
        </a>
    <?php endforeach; ?>
</div>
