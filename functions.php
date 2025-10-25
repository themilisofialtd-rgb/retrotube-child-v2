<?php
/**
 * Retrotube Child (Flipbox Edition)
 * - Flipboxes + AWE helpers + admin tools
 * - Models virtual template with Banner (1200x350)
 * - Admin: right column on Model edit (RankMath box)
 *
 * IMPORTANT: Single PHP block. Do not add another <?php inside this file.
 */

if (!defined('TMW_BANNER_DEBUG')) {
    define('TMW_BANNER_DEBUG', false);
}

require_once get_stylesheet_directory() . '/assets/php/tmw-hybrid-model-scan.php';

add_action('after_setup_theme', function () {
    add_image_size('tmw-model-hero-land', 1440, 810, true);
    add_image_size('tmw-model-hero-banner', 1200, 350, true);
});

require_once get_stylesheet_directory() . '/inc/tmw-style-injectors.php';
require_once get_stylesheet_directory() . '/inc/tmw-model-hooks.php';
require_once get_stylesheet_directory() . '/inc/tmw-video-hooks.php';
require_once get_stylesheet_directory() . '/inc/tmw-admin-tools.php';

// Remove "Website" (URL) field from comment forms on Video/Model singles
require_once get_stylesheet_directory() . '/inc/comments-remove-website.php';

// v3.5.4 — Flip CTA offset loaded sentinel.
add_action('wp_head', function () {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[TMW-FLIP-CTA] v3.5.4 style.css CTA offset rules active');
    }
}, 1);

/**
 * Videos nav icon (inline, robust against ::before resets)
 * - Injects a real <i> for the Videos menu item in header/primary menu.
 */
require_once get_stylesheet_directory() . '/inc/nav-videos-icon.php';
