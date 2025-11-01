<?php
if (!defined('ABSPATH')) exit;
/**
 * TMW Lost Password FIX — Route RetroTube modal to WP core retrieve_password()
 * Stops spinner with a normalized JSON response and avoids user enumeration.
 * Logs with [TMW-LOSTPASS].
 */

if (!function_exists('tmw_lostpass_json')) {
    function tmw_lostpass_json($ok, $html, $status = 'ok') {
        $payload = array(
            'success'  => (bool) $ok,
            'loggedin' => false,         // legacy key some themes check
            'status'   => $status,
            'message'  => $html,
            'reload'   => false,
            'redirect' => ''
        );
        do_action('tmw_lostpass_json_before_send', $payload);
        wp_send_json($payload);
    }
}

/** Hook every variant the parent might use (your modal uses wpst_reset_password). */
add_action('init', function () {
    foreach ([
        'wp_ajax_nopriv_wpst_reset_password',
        'wp_ajax_wpst_reset_password',
        'wp_ajax_nopriv_wpst_lostpassword',
        'wp_ajax_wpst_lostpassword',
        'wp_ajax_nopriv_lostpassword',
        'wp_ajax_lostpassword',
    ] as $hook) {
        add_action($hook, 'tmw_lostpass_handle', 0);
    }
}, 0);

/** Core handler */
function tmw_lostpass_handle() {
    // Accept multiple possible field names from various templates.
    $raw = '';
    foreach (['wpst_user_or_email', 'user_or_email', 'user_login', 'user_email'] as $k) {
        if (isset($_POST[$k])) {
            $raw = trim(wp_unslash($_POST[$k]));
            break;
        }
    }

    if ($raw === '') {
        error_log('[TMW-LOSTPASS] ERR msg="Missing username or email."');
        tmw_lostpass_json(false, '<p class="alert alert-danger">' . esc_html__('Please enter your username or e-mail.', 'wpst') . '</p>', 'missing');
    }

    // Resolve user (do NOT reveal existence in responses).
    $user = is_email($raw) ? get_user_by('email', $raw) : get_user_by('login', $raw);
    if (!$user && is_email($raw)) {
        $user = get_user_by('email', $raw);
    }

    if ($user instanceof WP_User) {
        $allow = apply_filters('allow_password_reset', true, $user->ID);
        if (!$allow) {
            error_log('[TMW-LOSTPASS] deny_reset user_id=' . $user->ID);
            tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>');
        }

        // WP 6.x: retrieve_password returns true|WP_Error
        $r = retrieve_password($user->user_login);
        if (is_wp_error($r)) {
            error_log('[TMW-LOSTPASS] WP_Error code=' . $r->get_error_code() . ' msg=' . $r->get_error_message());
            // Still generic success to avoid user enumeration.
            tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>');
        } else {
            error_log('[TMW-LOSTPASS] core:retrieve_password user_login=' . $user->user_login . ' uid=' . $user->ID);
            tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>');
        }
    } else {
        error_log('[TMW-LOSTPASS] unknown_user supplied="' . $raw . '"');
        // Generic success for non-existent users.
        tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>');
    }
}
