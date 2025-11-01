<?php
if (!defined('ABSPATH')) { exit; }

/**
 * TMW Lost Password override for RetroTube popup (child only)
 * Action: wpst_reset_password (parent uses this)
 */

add_filter('wp_mail_from', function ($from) {
    return 'no-reply@top-models.webcam';
}, 9999);

add_filter('wp_mail_from_name', function ($name) {
    return wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
}, 9999);

add_action('wp_ajax_nopriv_wpst_reset_password', 'tmw_wpst_reset_password_override', 0);
add_action('wp_ajax_wpst_reset_password', 'tmw_wpst_reset_password_override', 0);

function tmw_wpst_reset_password_override() {
    // Accept any common field name the popup might submit.
    $raw = '';
    foreach (['user_login', 'username', 'login', 'email', 'user_email'] as $key) {
        if (isset($_POST[$key])) {
            $raw = $_POST[$key];
            break;
        }
    }

    $login = sanitize_text_field(wp_unslash($raw));

    if ($login === '') {
        if (function_exists('error_log')) {
            error_log('[TMW-LOSTPASS] ERR msg="Missing username or email."');
        }
        wp_send_json_error([
            'message' => '<p class="alert alert-danger">' . esc_html__('Please enter your username or email.', 'wpst') . '</p>',
        ]);
    }

    // Hand off to core. (It accepts either user_login or email in the single field.)
    $_POST['user_login'] = $login;
    $result = retrieve_password();

    if (is_wp_error($result)) {
        if (function_exists('error_log')) {
            error_log('[TMW-LOSTPASS] WP_Error code=' . $result->get_error_code() . ' msg=' . $result->get_error_message());
        }
        // Return generic success to stop spinner and avoid enumeration.
        wp_send_json_success([
            'message' => '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>',
        ]);
    }

    if (function_exists('error_log')) {
        error_log('[TMW-LOSTPASS] OK login=' . $login);
    }

    wp_send_json_success([
        'message' => '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>',
    ]);
}
