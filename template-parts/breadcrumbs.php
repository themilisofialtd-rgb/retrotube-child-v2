<?php
if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('tmw_output_rank_math_breadcrumbs')) {
    tmw_output_rank_math_breadcrumbs();
    return;
}

$parent_template = trailingslashit(get_template_directory()) . 'template-parts/breadcrumbs.php';
if (is_readable($parent_template)) {
    load_template($parent_template, false);
}
