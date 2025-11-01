<?php
/**
 * TMW Lost Password — BULLETPROOF adapter
 * - Owns RetroTube's popup endpoint and calls WP core retrieve_password()
 * - Emits JSON compatible with multiple legacy handlers to avoid UI loops
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register earliest handlers for all variants the popup might use.
 * wp_send_json_* calls wp_die(), so once we answer, nothing else runs.
 */
add_action('wp_ajax_nopriv_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);
add_action('wp_ajax_tmw_lostpass_bp', 'tmw_lostpass_bp_handle', 0);

/**
 * Main handler — sanitize input, call core, return universal JSON.
 */
function tmw_lostpass_bp_handle()
{
    nocache_headers();

    $raw = isset($_POST) ? wp_unslash($_POST) : [];

    // Accept all front-end variants the popup might send.
    $candidates = [
        'wpst_user_or_email',   // popup uses this (primary)
        'user_or_email',        // legacy
        'user_login_or_email',  // legacy
        'user_login',
        'user_email',
        'email',
        'login',
        'username',
    ];

    // Nonce (expect from JS); fail closed with a clear message.
    $nonce = isset($raw['tmw_lostpass_bp_nonce']) ? $raw['tmw_lostpass_bp_nonce'] : '';
    if (!is_string($nonce) || !wp_verify_nonce($nonce, 'tmw_lostpass_bp_nonce')) {
        return tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html__('Security check failed. Please refresh and try again.', 'wpst') . '</p>',
            'invalid_nonce'
        );
    }

    // Extract identifier
    $identifier = '';
    $source     = '';
    foreach ($candidates as $key) {
        if (!empty($raw[$key])) {
            $identifier = trim((string) $raw[$key]);
            $source     = $key;
            break;
        }
    }

    if ($identifier === '') {
        error_log('[TMW-LOSTPASS] ERR msg="Missing username or email."');
        return tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html__('Missing username or email.', 'wpst') . '</p>',
            'missing_identifier'
        );
    }

    // Call WP core
    add_filter('allow_password_reset', function ($allow, $uid) {
        error_log('[TMW-LOSTPASS-AUDIT] filter:allow_password_reset user_id=' . intval($uid) . ' allow=' . var_export($allow, true));
        return $allow;
    }, 10, 2);

    $is_email = is_email($identifier);
    $login    = $identifier;

    // If email was provided, translate to user_login for retrieve_password() compatibility
    if ($is_email) {
        $user = get_user_by('email', $identifier);
        if ($user && $user instanceof WP_User) {
            $login = $user->user_login;
        }
    }

    $result = retrieve_password($login);

    if ($result instanceof WP_Error) {
        error_log('[TMW-LOSTPASS] core:retrieve_password error code=' . $result->get_error_code() . ' src=' . $source);
        $msg = $result->get_error_message();
        if (!$msg) {
            $msg = __('Password reset is not allowed for this user', 'wpst');
        }
        return tmw_lostpass_bp_json(
            false,
            '<p class="alert alert-danger">' . esc_html($msg) . '</p>',
            $result->get_error_code() ?: 'password_reset_denied'
        );
    }

    // Success
    error_log(sprintf('[TMW-LOSTPASS] core:retrieve_password ok user_login=%s src=%s', $login, $source));

    return tmw_lostpass_bp_json(
        true,
        '<p class="alert alert-success">' . esc_html__('Password Reset. Please check your email.', 'wpst') . '</p>',
        'ok'
    );
}

/**
 * Deterministic JSON envelope for the modal.
 */
function tmw_lostpass_bp_json($ok, $message, $code = 'ok')
{
    $payload = [
        'ok'      => (bool) $ok,
        'message' => (string) $message,
        'code'    => (string) $code,
    ];
    // Compatibility keys for any legacy JS that looks for "success"
    if (!isset($payload['success'])) {
        $payload['success'] = $payload['ok'];
    }
    wp_send_json($payload);
}
