<?php

if (!defined('ABSPATH')) exit;

$pages = [
    'cp-dashboard' => [
        'label' => 'Dashboard',
        'icon'  => 'bi-grid-1x2-fill',
        'url'   => cp_admin_url('cp-dashboard'),
    ],
    'cp-articles' => [
        'label' => 'Articles',
        'icon'  => 'bi-journal-text',
        'url'   => cp_admin_url('cp-articles'),
    ],
    'cp-categories' => [
        'label' => 'Categories',
        'icon'  => 'bi-folder2-open',
        'url'   => cp_admin_url('cp-categories'),
    ],
    'cp-users' => [
        'label' => 'Users',
        'icon'  => 'bi-people-fill',
        'url'   => cp_admin_url('cp-users'),
    ],
    'cp-analytics' => [
        'label' => 'Analytics',
        'icon'  => 'bi-graph-up-arrow',
        'url'   => cp_admin_url('cp-analytics'),
    ],
    'cp-settings' => [
        'label' => 'Settings',
        'icon'  => 'bi-sliders',
        'url'   => cp_admin_url('cp-settings'),
    ],
];

$current = wp_get_current_user();

?>

<div class="cp-logo">

    <div class="cp-logo-icon">
        E
    </div>

    <div>
        <h3>Enterprise1979</h3>
        <span>Publisher Portal</span>
    </div>

</div>


<div class="cp-menu-title">
Navigation
</div>

<nav class="cp-sidebar-nav">

<?php foreach($pages as $slug=>$item): ?>

<a
href="<?php echo esc_url($item['url']); ?>"
class="cp-nav-link <?php echo cp_is_active_page($slug) ? 'active':'';?>">

<i class="bi <?php echo esc_attr($item['icon']); ?>"></i>

<span><?php echo esc_html($item['label']); ?></span>

</a>

<?php endforeach; ?>

</nav>


<div class="cp-sidebar-footer">

<div class="cp-avatar">

<?php echo strtoupper(substr($current->display_name,0,1)); ?>

</div>

<div>

<strong><?php echo esc_html($current->display_name); ?></strong>

<span><?php echo esc_html($current->user_email); ?></span>

</div>

</div>