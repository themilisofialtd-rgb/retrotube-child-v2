<?php
/**
 * Child theme override for the RetroTube breadcrumb output.
 *
 * Prefers Rank Math breadcrumbs for both the visible HTML and schema
 * markup. Falls back to the parent theme's breadcrumb template when
 * Rank Math is unavailable so legacy behaviour is preserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('rank_math_get_breadcrumbs')) {
    $breadcrumb_markup = rank_math_get_breadcrumbs([
        'wrap_before' => '<div id="breadcrumbs" class="rank-math-breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">',
        'wrap_after'  => '</div>',
    ]);

    if (!empty($breadcrumb_markup)) {
        echo $breadcrumb_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
}

if (function_exists('rank_math_the_breadcrumbs')) {
    echo '<div id="breadcrumbs" class="rank-math-breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">';
    rank_math_the_breadcrumbs();
    echo '</div>';
    return;
}

$parent_breadcrumb = trailingslashit(get_template_directory()) . 'breadcrumb.php';

if (is_readable($parent_breadcrumb)) {
    include $parent_breadcrumb;
}
