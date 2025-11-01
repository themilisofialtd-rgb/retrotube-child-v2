<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) { exit; }

/**
 * TMW Lost Password Bridge
 * - Routes RetroTube modal → WP core retrieve_password()
 * - Returns JSON the way the popup expects
 * - Adds clear debug lines to help verification.
 */

if (!defined('TMW_CHILD_VERSION')) define('TMW_CHILD_VERSION', '4.1.0');

function tmw_lostpass_send_json($ok, $msg){
    if (function_exists('error_log')) {
        error_log('[TMW-LOSTPASS] ' . ($ok ? 'OK' : 'ERR') . ' msg="' . sanitize_text_field($msg) . '"');
    }
    $payload = array('message' => $msg);
    $ok ? wp_send_json_success($payload) : wp_send_json_error($payload);
}

/**
 * Accept multiple action names used by RetroTube variants.
 */
add_action('wp_ajax_nopriv_wpst_reset_password', 'tmw_lostpass_handle');
add_action('wp_ajax_wpst_reset_password',        'tmw_lostpass_handle');
add_action('wp_ajax_nopriv_wpst_lostpassword',   'tmw_lostpass_handle');
add_action('wp_ajax_wpst_lostpassword',          'tmw_lostpass_handle');

function tmw_lostpass_handle(){
    // Accept username/email in several common keys.
    $login = '';
    $keys  = array('username','user_login','user_email','login','email');
    foreach ($keys as $k) {
        if (isset($_POST[$k]) && $_POST[$k] !== '') {
            $login = sanitize_text_field(wp_unslash($_POST[$k]));
            break;
        }
    }
    if ($login === '') {
        tmw_lostpass_send_json(false, __('Missing username or email.', 'retrotube'));
    }

    // Core retrieve_password() expects $_POST['user_login'].
    $_POST['user_login'] = $login;

    // Call WordPress core. Returns true on success or WP_Error.
    $result = retrieve_password();

    if ($result === true) {
        tmw_lostpass_send_json(true, __('Password Reset. Please check your email.', 'wpst'));
    }

    if (is_wp_error($result)) {
        $msg = $result->get_error_message();
        tmw_lostpass_send_json(false, $msg ? $msg : __('We could not process your request.', 'retrotube'));
    }

    tmw_lostpass_send_json(false, __('Unexpected error. Please try again later.', 'retrotube'));
    // wp_send_json_* will exit.
}

