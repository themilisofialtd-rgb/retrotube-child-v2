<?php
if (!defined('ABSPATH')) exit;
/**
 * TMW Lost Password — AUDIT ONLY (no behavior changes)
 * v1.0.3 (2/4-arg compatible)
 */

add_action('init', function () {
    error_log('[TMW-REG-SETTINGS] users_can_register=' . get_option('users_can_register') .
              ' default_role=' . get_option('default_role'));
}, 11);

foreach (['wp_ajax_nopriv_wpst_reset_password','wp_ajax_wpst_reset_password','wp_ajax_nopriv_wpst_lostpassword','wp_ajax_wpst_lostpassword'] as $hook) {
    add_action($hook, function () use ($hook) {
        $keys = array_keys($_POST ?: []);
        $sample = [];
        foreach (['wpst_user_or_email','user_login','password-security','_wpnonce','_wp_http_referer','action'] as $k) {
            if (isset($_POST[$k])) $sample[$k] = is_scalar($_POST[$k]) ? (string) $_POST[$k] : '[complex]';
        }
        error_log('[TMW-LOSTPASS-AUDIT] hook=' . $hook .
                  ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '?') .
                  ' keys=' . implode(',', $keys) .
                  ' sample=' . json_encode($sample));
    }, 0);
}

add_filter('allow_password_reset', function ($allow, $user_id) {
    error_log('[TMW-LOSTPASS-AUDIT] filter:allow_password_reset user_id=' . (int)$user_id . ' allow=' . var_export($allow, true));
    return $allow;
}, 10, 2);

add_filter('retrieve_password_title', function ($title) {
    error_log('[TMW-LOSTPASS-AUDIT] filter:retrieve_password_title title="' . sanitize_text_field($title) . '"');
    return $title;
}, 10, 1);

// Accept BOTH theme(2) and core(4) calling styles
add_filter('retrieve_password_message', function ($message, $key = null, $user_login = null, $user_data = null) {
    $uid = (is_object($user_data) && isset($user_data->ID)) ? (int)$user_data->ID : 0;
    $key_short = is_string($key) ? substr($key, 0, 8) : '';
    error_log('[TMW-LOSTPASS-AUDIT] filter:retrieve_password_message key=' . $key_short .
              ' user_login=' . (string)$user_login . ' uid=' . $uid);
    return $message;
}, 10, 4);

add_filter('wp_mail', function ($args) {
    $to = is_array($args['to']) ? implode(',', $args['to']) : (string)$args['to'];
    $subj = isset($args['subject']) ? (string)$args['subject'] : '';
    $hdrs = isset($args['headers']) ? (is_array($args['headers']) ? implode('|', $args['headers']) : (string)$args['headers']) : '';
    error_log('[TMW-LOSTPASS-AUDIT] wp_mail to=' . $to . ' subject="' . $subj . '" headers="' . $hdrs . '"');
    return $args;
}, 10, 1);

add_action('wp_mail_failed', function ($wp_error) {
    $data = $wp_error instanceof WP_Error ? $wp_error->get_error_message() : (string)$wp_error;
    error_log('[TMW-LOSTPASS-AUDIT] wp_mail_failed msg=' . $data);
}, 10, 1);
