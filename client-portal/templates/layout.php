<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="cp-app">
    <aside class="cp-sidebar">
        <?php include CP_PATH . 'templates/sidebar.php'; ?>
    </aside>

    <div class="cp-main">

        <header class="cp-topbar">
            <?php include CP_PATH . 'templates/topbar.php'; ?>
        </header>

        <main class="cp-content">
            <?php echo $content; ?>
        </main>

    </div>
</div>