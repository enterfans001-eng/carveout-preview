<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!isset($_GET['id'])) {
    wp_safe_redirect(add_query_arg('id', get_the_ID(), get_permalink()), 302);
    exit;
}

require get_template_directory() . '/page-interview-detail.php';
