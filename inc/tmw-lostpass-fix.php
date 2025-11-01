<?php
if (!defined('ABSPATH')) { exit; }
/**
 * TMW Lost Password FIX — RetroTube popup → WP core retrieve_password().
 * Supports parent actions and stops the "Loading..." loop by returning JSON.
 */
define('TMW_LP_FIX_VERSION', '1.1.0');

// Hook both observed and legacy actions.
add_action('wp_ajax_nopriv_wpst_reset_password', 'tmw_lostpass_handle');
add_action('wp_ajax_wpst_reset_password', 'tmw_lostpass_handle');
add_action('wp_ajax_nopriv_wpst_lostpassword', 'tmw_lostpass_handle');
add_action('wp_ajax_wpst_lostpassword', 'tmw_lostpass_handle');

/**
 * Handle RetroTube lost password AJAX requests.
 */
function tmw_lostpass_handle() {
    $action = current_action();

    $val = '';
    foreach (['wpst_user_or_email', 'user_login', 'username', 'email', 'user_email', 'login'] as $k) {
        if (!empty($_POST[$k])) {
            $val = sanitize_text_field(wp_unslash($_POST[$k]));
            break;
        }
    }

    if ($val === '') {
        tmw_lostpass_json(false, '<p class="alert alert-danger">' . esc_html__('Missing username or email.', 'wpst') . '</p>', 'missing_input');
    }

    if (isset($_POST['password-security'])) {
        $nonce = sanitize_text_field(wp_unslash($_POST['password-security']));
        if (!wp_verify_nonce($nonce, 'password-security')) {
            error_log('[TMW-LOSTPASS-FIX] nonce_failed action=' . $action);
        }
    }

    // Core expects this field populated.
    $_POST['user_login'] = $val;

    $result = retrieve_password();

    if (is_wp_error($result)) {
        error_log('[TMW-LOSTPASS-FIX] retrieve_password error codes=' . implode(',', $result->get_error_codes()));
        tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('If the email exists, you will receive a reset link.', 'wpst') . '</p>', 'ok');
    }

    tmw_lostpass_json(true, '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>', 'ok');
}

/**
 * Send a JSON response and exit.
 *
 * @param bool   $success Success flag.
 * @param string $message HTML message string.
 * @param string $code    Optional code identifier.
 */
function tmw_lostpass_json($success, $message, $code = '') {
    $payload = [
        'message' => $message,
        'code'    => $code,
        'event'   => 'lostpassword',
    ];

    if ($success) {
        wp_send_json_success($payload);
    }

    wp_send_json_error($payload, 400);
}
