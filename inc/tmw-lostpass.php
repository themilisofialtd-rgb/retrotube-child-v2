<?php
if (!defined('ABSPATH')) exit;

/**
 * Child-only Lost Password endpoint.
 * Calls WordPress core retrieve_password() and returns a generic success.
 * Debug tags: [TMW-LOSTPASS-OK], [TMW-LOSTPASS-FAIL]
 */

// Public + logged-in (for safety) AJAX routes
add_action('wp_ajax_nopriv_tmw_lostpass', 'tmw_lostpass');
add_action('wp_ajax_tmw_lostpass',        'tmw_lostpass');

function tmw_lostpass() {
    // Soft nonce check: only enforce if sent (keeps compatibility with simple forms)
    if (isset($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'tmw_auth')) {
        wp_send_json_error(['message' => __('Security check failed.', 'retrotube')], 200);
    }

    $login = isset($_POST['login']) ? sanitize_text_field(wp_unslash($_POST['login'])) : '';
    if (!$login) {
        wp_send_json_error(['message' => __('Please enter your username or email.', 'retrotube')], 200);
    }

    // Normalize: allow email or username
    if (is_email($login)) {
        $u = get_user_by('email', $login);
        $login = $u ? $u->user_login : $login; // WP still replies generically
    }

    $result = retrieve_password($login); // ← Core WP flow (creates key + sends email)

    if (function_exists('error_log')) {
        if (is_wp_error($result)) {
            error_log('[TMW-LOSTPASS-FAIL] codes=' . implode(',', $result->get_error_codes()));
        } else {
            error_log('[TMW-LOSTPASS-OK] login=' . $login);
        }
    }

    // Always generic response to avoid user enumeration
    wp_send_json_success(['message' => __('If an account exists, a reset email has been sent.', 'retrotube')], 200);
}
