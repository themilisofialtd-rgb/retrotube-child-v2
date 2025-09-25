<?php
/**
 * Reusable Featured Models block partial.
 */

if (!defined('ABSPATH')) {
    exit;
}

$shortcode_to_use = get_query_var('tmw_featured_shortcode', '[tmw_featured_models]');

if (!is_string($shortcode_to_use)) {
    return;
}

$shortcode_to_use = trim($shortcode_to_use);

if ($shortcode_to_use === '') {
    return;
}

echo do_shortcode($shortcode_to_use);
