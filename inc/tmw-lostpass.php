<?php
if (!defined('ABSPATH')) exit;

/**
 * TMW Lost Password — child override of RetroTube AJAX.
 * We unhook the parent's handler and handle the same action
 * with WordPress core retrieve_password().
 */

// Run after parent has added its AJAX callbacks
add_action('init', function () {
    // Remove parent handlers (if present)
    remove_action('wp_ajax_nopriv_wpst_reset_password', 'wpst_reset_password');
    remove_action('wp_ajax_wpst_reset_password',        'wpst_reset_password');

    // Re-bind to the same action so the popup keeps working
    add_action('wp_ajax_nopriv_wpst_reset_password', 'tmw_lostpass_core');
    add_action('wp_ajax_wpst_reset_password',        'tmw_lostpass_core');
}, 99);

function tmw_lostpass_core() {
    $login = isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '';
    if (!$login) {
        // RetroTube sometimes posts as "user_login" or "email"
        foreach (['user_login','email','login'] as $k) {
            if (!empty($_POST[$k])) { $login = sanitize_text_field(wp_unslash($_POST[$k])); break; }
        }
    }
    if (!$login) {
        wp_send_json_error(['message' => __('Please enter your username or email.','retrotube')], 200);
    }

    // Allow email or username
    if (is_email($login)) {
        $u = get_user_by('email', $login);
        $login = $u ? $u->user_login : $login;
    }

    $result = retrieve_password($login); // native WP reset flow

    if (function_exists('error_log')) {
        if (is_wp_error($result)) {
            error_log('[TMW-LOSTPASS-FAIL] ' . implode(',', $result->get_error_codes()));
        } else {
            error_log('[TMW-LOSTPASS-OK] login=' . $login);
        }
    }

    // Always generic message (avoids user enumeration)
    wp_send_json_success(['message' => __('If an account exists, a reset email has been sent.','retrotube')], 200);
}
