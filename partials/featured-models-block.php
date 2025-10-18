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

$output = do_shortcode($shortcode_to_use);

if (!is_string($output) || $output === '') {
    echo $output;
    return;
}

$output = preg_replace(
    '~(<h3\\b[^>]*class=["\']([^"\']*\\s)?tmwfm-heading([^"\']*\\s)?[^"\']*["\'][^>]*>\\s*)(?!<i[^>]*\\bfa-random\\b[^>]*>)(FEATURED MODELS)~',
    '$1<i class="fa fa-random"></i> $4',
    $output,
    1
);

echo $output;
