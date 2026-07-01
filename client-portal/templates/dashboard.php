<?php

if (!is_user_logged_in()) {

    wp_redirect(site_url('/portal/login'));

    exit;

}

echo "<h1>Dashboard</h1>";