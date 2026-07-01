<?php

if (!defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>

    <meta charset="<?php bloginfo('charset'); ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Publisher Portal</title>

    <?php wp_head(); ?>

</head>

<body>

<div class="cp-wrapper">

    <!-- ================= Sidebar ================= -->

    <aside class="cp-sidebar">

        <div class="cp-logo">

            <h3>Enterprise1979</h3>

            <small>Publisher Portal</small>

        </div>

        <nav>

            <a class="<?php echo cp_active('dashboard'); ?>"
               href="<?php echo cp_dashboard_url(); ?>">

                <i class="bi bi-speedometer2"></i>

                Dashboard

            </a>

            <a class="<?php echo cp_active('articles'); ?>"
               href="<?php echo cp_articles_url(); ?>">

                <i class="bi bi-file-earmark-text"></i>

                Articles

            </a>

            <a class="<?php echo cp_active('categories'); ?>"
               href="<?php echo cp_categories_url(); ?>">

                <i class="bi bi-tags"></i>

                Categories

            </a>

            <a class="<?php echo cp_active('users'); ?>"
               href="<?php echo cp_users_url(); ?>">

                <i class="bi bi-people"></i>

                Users

            </a>

            <a class="<?php echo cp_active('settings'); ?>"
               href="<?php echo cp_settings_url(); ?>">

                <i class="bi bi-gear"></i>

                Settings

            </a>

        </nav>

    </aside>

    <!-- ================= Content ================= -->

    <div class="cp-main">

        <!-- Topbar -->

        <header class="cp-navbar">

            <div>

                <h4><?php the_title(); ?></h4>

            </div>

            <div class="cp-user">

                <span>

                    <?php echo esc_html($user->display_name); ?>

                </span>

                |

                <a href="<?php echo esc_url(wp_logout_url(cp_login_url())); ?>">

                    Logout

                </a>

            </div>

        </header>

        <!-- Page -->

        <section class="cp-content">

            <?php echo $content; ?>

        </section>

    </div>

</div>

<?php wp_footer(); ?>

</body>

</html>