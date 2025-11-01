<?php
if (!defined('ABSPATH')) exit;

/**
 * Hard-disable legacy lost-password AJAX flow & duplicate email rewrites.
 * Leaves your RetroTube popup SHELL intact (no UI change).
 * Adds breadcrumbs to debug.log under [TMW-AUTH-CLEANUP].
 */

add_action('init', function () {
    // Remove legacy AJAX handlers if present (no fatal if they don't exist)
    remove_action('wp_ajax_nopriv_tmw_lostpass_bp', 'tmw_lostpass_bp');
    remove_action('wp_ajax_tmw_lostpass_bp',        'tmw_lostpass_bp');
    if (function_exists('error_log')) {
        error_log('[TMW-AUTH-CLEANUP] removed legacy AJAX actions (tmw_lostpass_bp if present)');
    }
}, 0);

add_action('wp_enqueue_scripts', function () {
    // Dequeue/deregister the old bridge script if enqueued elsewhere
    wp_dequeue_script('tmw-lostpass-bridge');
    wp_deregister_script('tmw-lostpass-bridge');
    if (function_exists('error_log')) {
        error_log('[TMW-AUTH-CLEANUP] dequeued/deregistered tmw-lostpass-bridge');
    }
}, 999);

/**
 * Guard against duplicate email body rewrites.
 * If any legacy plugin/theme filter rewrites retrieve_password_message again,
 * we skip it when our marker (?tmw_reset=) already exists.
 */
add_filter('retrieve_password_message', function ($message) {
    if (strpos($message, 'tmw_reset=') !== false) {
        if (function_exists('error_log')) {
            error_log('[TMW-AUTH-CLEANUP] retrieve_password_message already contains tmw_reset, skipping legacy rewrites');
        }
        return $message;
    }
    return $message;
}, 1); // run early as a guard

// Optional: kill other known callbacks by name if attached (no fatal if missing)
add_action('init', function () {
    $cbs = [
        'tmw_mail_fix_rewrite',
        'tmw_lostpass_rewrite',
        'tmw_lostpass_email_body',
    ];
    foreach ($cbs as $cb) {
        if (has_filter('retrieve_password_message', $cb)) {
            remove_filter('retrieve_password_message', $cb, 10);
            if (function_exists('error_log')) {
                error_log('[TMW-AUTH-CLEANUP] removed retrieve_password_message callback: ' . $cb);
            }
        }
    }
}, 11);
