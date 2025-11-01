<?php
if (!defined('ABSPATH')) { exit; }
/**
 * TMW Lost Password — AUDIT ONLY (no behavior changes)
 * Traces: popup AJAX → action → retrieve_password → wp_mail
 * Logs into wp-content/debug.log
 */

if (!defined('TMW_LOSTPASS_AUDIT')) {
    define('TMW_LOSTPASS_AUDIT', true);
}

if (!TMW_LOSTPASS_AUDIT) {
    return;
}

// --- 1) Tap likely AJAX actions and log incoming payload (no exit, just observe)
add_action('init', function () {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $actions = [
            'wp_ajax_nopriv_wpst_reset_password',
            'wp_ajax_wpst_reset_password',
            'wp_ajax_nopriv_wpst_lostpassword',
            'wp_ajax_wpst_lostpassword',
            // Safety nets in case the parent theme uses alternates:
            'wp_ajax_nopriv_lostpassword',
            'wp_ajax_lostpassword',
        ];
        foreach ($actions as $hook) {
            add_action($hook, 'tmw_lostpass_audit_log_request', -9999);
        }
    }
}, 0);

function tmw_lostpass_audit_log_request() {
    $action = current_action();
    $keys   = implode(',', array_keys($_POST));

    // Sample out the usual suspects to see which one actually carries the value
    $sample = [];
    foreach (['user_login','username','login','email','user_email','user','user_forgotten'] as $k) {
        if (isset($_POST[$k])) {
            $sample[$k] = sanitize_text_field(wp_unslash($_POST[$k]));
        }
    }

    error_log('[TMW-LOSTPASS-AUDIT] action=' . $action .
              ' method=' . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI') .
              ' keys=' . $keys .
              ' sample=' . wp_json_encode($sample));
    // Do NOT exit — we only observe.
}

// --- 2) Prove core retrieve_password() ran (fires when building the message)
add_filter('retrieve_password_message', function ($message, $key, $user_login, $user_data) {
    error_log('[TMW-LOSTPASS-AUDIT] core:retrieve_password user_login=' . $user_login .
              ' user_id=' . (is_object($user_data) ? $user_data->ID : 'n/a'));
    return $message;
}, 10, 4);

// Optional: confirm allow/deny path
add_filter('allow_password_reset', function ($allow, $user_id) {
    error_log('[TMW-LOSTPASS-AUDIT] filter:allow_password_reset user_id=' . $user_id . ' allow=' . var_export($allow, true));
    return $allow;
}, 10, 2);

// --- 3) Mail pipeline visibility
add_filter('wp_mail', function ($args) {
    $to = is_array($args['to']) ? implode(',', $args['to']) : $args['to'];
    error_log('[TMW-LOSTPASS-AUDIT] wp_mail to=' . $to .
              ' subject=' . (isset($args['subject']) ? $args['subject'] : '') .
              ' headers=' . (isset($args['headers']) ? wp_json_encode($args['headers']) : '[]'));
    return $args; // no changes
}, 9999);

add_action('wp_mail_failed', function ($wp_error) {
    $data = $wp_error->get_error_data();
    error_log('[TMW-LOSTPASS-AUDIT] wp_mail_failed code=' . $wp_error->get_error_code() .
              ' msg=' . $wp_error->get_error_message() .
              ' data=' . (is_array($data) ? wp_json_encode($data) : 'n/a'));
});
