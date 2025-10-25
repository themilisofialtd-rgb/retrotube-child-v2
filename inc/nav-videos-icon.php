<?php
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

/**
 * Inject an inline icon for the “Videos” top-nav item in the header/primary menu.
 *
 * Why:
 * - Inline <i> icons match how other items render and are immune to ::before resets.
 * - Robust URL check (last segment == 'videos'), tolerates language prefixes and slashes.
 * - Skips items that already have an icon to avoid duplicates.
 */
add_filter('walker_nav_menu_start_el', function ($item_output, $item, $depth, $args) {
    // Only affect the main/header menu. Adjust/add keys here if your location slug differs.
    $allowed_locations = array('primary', 'main', 'menu-1', 'header', 'top');
    if (empty($args->theme_location) || !in_array($args->theme_location, $allowed_locations, true)) {
        return $item_output;
    }

    // If the item already contains an icon (<i> or <svg>), do nothing (avoid duplicates).
    if (stripos($item_output, '<i ') !== false || stripos($item_output, '<svg') !== false) {
        return $item_output;
    }

    // Robust detector: is this URL the "Videos" page?
    $is_videos = static function ($url): bool {
        if (!$url) return false;

        // Normalize absolute URLs to site-relative.
        $home = trailingslashit(home_url('/'));
        $url  = preg_replace('#^' . preg_quote($home, '#') . '#i', '/', $url);

        // Strip query/fragment, decode, and trim slashes.
        $url   = strtok($url, '?#');
        $path  = trim(urldecode((string) $url), '/');
        if ($path === '') return false;

        // Match by last path segment (so /en/videos/ and /videos both match).
        $segments = explode('/', $path);
        $last     = strtolower(end($segments));
        return ($last === 'videos');
    };

    if (!$is_videos($item->url ?? '')) {
        return $item_output;
    }

    // Use FA4 class for best compatibility with existing menu icons (FA5 sites often ship FA4 shim).
    $icon_html = '<i class="fa fa-video-camera" aria-hidden="true"></i> ';

    // Insert icon immediately after the opening <a ...>
    $pos = stripos($item_output, '>');
    if ($pos === false) {
        return $item_output; // unexpected markup; fail safe
    }

    return substr($item_output, 0, $pos + 1) . $icon_html . substr($item_output, $pos + 1);
}, 10, 4);
